<?php

namespace MediaWiki\Extension\ArticleRanking;

class Captcha {

	/**
	 * Verify a reCaptcha token
	 * @param $token
	 *
	 * @return bool
	 */
	public static function verifyToken( $token ) {
		// If the captcha is disabled, always return true
		if ( !self::isEnabled() ) {
			return true;
		}

		$data = [
			'secret'   => self::getSecret(),
			'response' => $token
		];

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, self::getVerificationUrl() );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $data ) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $curl );
		$result = json_decode( $result );

		return ( $result->success === true );
	}

	public static function getSiteKey() {
		global $wgArticleRankingCaptcha;
		return $wgArticleRankingCaptcha[ 'siteKey' ];
	}

	public static function getSecret() {
		global $wgArticleRankingCaptcha;
		return $wgArticleRankingCaptcha[ 'secret' ];
	}

	public static function getScript() {
		return '<script src="https://www.hCaptcha.com/1/api.js" async defer></script>';
	}

	public static function getVerificationUrl() {
		return 'https://hcaptcha.com/siteverify';
	}

	public static function isEnabled() {
		global $wgArticleRankingCaptcha;
		return ( $wgArticleRankingCaptcha[ 'secret' ] && $wgArticleRankingCaptcha[ 'siteKey' ] );
	}

}
