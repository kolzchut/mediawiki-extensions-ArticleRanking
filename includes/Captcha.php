<?php

namespace MediaWiki\Extension\ArticleRanking;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;

class Captcha {

	/**
	 * Verify a Cloudflare Turnstile token against the siteverify endpoint.
	 *
	 * The captcha is dormant unless $wgArticleRankingCaptcha is configured, in
	 * which case verification fails open. This service is identified within the
	 * shared platform widget by cData 'articleranking'.
	 *
	 * @param string $token Turnstile response token from the client widget
	 *
	 * @return bool
	 */
	public static function verifyToken( $token ) {
		// If the captcha is disabled, always return true
		if ( !self::isEnabled() ) {
			return true;
		}

		$logger = LoggerFactory::getInstance( 'ArticleRanking' );
		$services = MediaWikiServices::getInstance();

		$data = [
			'secret'   => self::getSecret(),
			'response' => $token,
			'remoteip' => RequestContext::getMain()->getRequest()->getIP(),
		];

		// Verify via MediaWiki's HttpRequestFactory (matches ConfirmEdit /
		// KZChangeRequest) instead of a raw cURL handle.
		$httpRequest = $services->getHttpRequestFactory()
			->create( self::getVerificationUrl(), [ 'method' => 'POST', 'postData' => $data ] );

		$status = $httpRequest->execute();
		if ( !$status->isOK() ) {
			$logger->error( 'Turnstile request failed: {msg}', [ 'msg' => (string)$status ] );
			return false;
		}

		$result = json_decode( $httpRequest->getContent(), true );
		if ( !is_array( $result ) ) {
			$logger->error( 'Failed to parse Turnstile response' );
			return false;
		}

		if ( empty( $result['success'] ) ) {
			$logger->info( 'Turnstile validation failed: {errs}', [
				'errs' => implode( ',', (array)( $result['error-codes'] ?? [] ) ),
			] );
			return false;
		}

		// Confirm the token was minted for THIS service, not another consumer of
		// the shared platform widget.
		if ( ( $result['cdata'] ?? '' ) !== 'articleranking' ) {
			$logger->warning( 'Turnstile cData mismatch: {cdata}',
				[ 'cdata' => $result['cdata'] ?? '(none)' ] );
			return false;
		}

		// Defence-in-depth on top of the widget's allowed-hostnames list: the token
		// must have been solved on this wiki's own host. Only enforced when
		// siteverify reports a hostname (the always-pass test keys omit it).
		$expectedHost = parse_url( (string)$services->getMainConfig()->get( 'Server' ), PHP_URL_HOST );
		$returnedHost = $result['hostname'] ?? '';
		if ( $expectedHost && $returnedHost && $returnedHost !== $expectedHost ) {
			$logger->warning( 'Turnstile hostname mismatch: {got} != {expected}',
				[ 'got' => $returnedHost, 'expected' => $expectedHost ] );
			return false;
		}

		return true;
	}

	/** @return string The Turnstile site key from $wgArticleRankingCaptcha */
	public static function getSiteKey() {
		global $wgArticleRankingCaptcha;
		return $wgArticleRankingCaptcha[ 'siteKey' ];
	}

	/** @return string The Turnstile secret from $wgArticleRankingCaptcha */
	public static function getSecret() {
		global $wgArticleRankingCaptcha;
		return $wgArticleRankingCaptcha[ 'secret' ];
	}

	/** @return string The script tag that loads the Turnstile widget */
	public static function getScript() {
		return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
	}

	/** @return string The Turnstile siteverify endpoint */
	public static function getVerificationUrl() {
		return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	}

	/** @return bool Whether both the site key and the secret are configured */
	public static function isEnabled() {
		global $wgArticleRankingCaptcha;
		return ( $wgArticleRankingCaptcha[ 'secret' ] && $wgArticleRankingCaptcha[ 'siteKey' ] );
	}

}
