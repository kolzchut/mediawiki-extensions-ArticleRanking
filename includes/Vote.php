<?php

namespace MediaWiki\Extension\ArticleRanking;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use RequestContext;
use TemplateParser;
use Title;

class Vote {

	/**
	 * Save (or update) the current voter's vote for a page.
	 *
	 * One live vote per identity per page: a repeat vote replaces the prior
	 * value (last-wins) rather than accumulating a new row, which is what let
	 * the endpoint be ballot-stuffed. See ArticleRanking#8.
	 *
	 * @param Title $title
	 * @param int $vote 1 for a positive vote, -1 for a negative vote
	 *
	 * @return bool
	 */
	public static function saveVote( Title $title, int $vote ) {
		if ( !in_array( $vote, [ -1, 1 ], true ) ) {
			throw new InvalidArgumentException( '$vote can only be -1 or 1' );
		}
		if ( !$title->exists() ) {
			throw new InvalidArgumentException( "$title does not exist" );
		}

		$dbw = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getPrimaryDatabase();

		$dbw->upsert(
			'article_rankings2',
			[
				'ranking_page_id'   => $title->getArticleID(),
				'ranking_voter_key' => self::getVoterKey(),
				'ranking_value'     => $vote,
				'ranking_timestamp' => $dbw->timestamp(),
			],
			[ [ 'ranking_page_id', 'ranking_voter_key' ] ],
			[
				'ranking_value'     => $vote,
				'ranking_timestamp' => $dbw->timestamp(),
			],
			__METHOD__
		);

		return true;
	}

	/**
	 * A stable, pseudonymous identifier for the current voter, used to enforce
	 * one live vote per identity per page.
	 *
	 * Registered users key by user id; anonymous users key by a keyed hash of
	 * their IP — the HMAC secret is $wgSecretKey, which lives outside this
	 * table, so a DB-only compromise cannot recover the address. Deliberately
	 * does NOT call getActorId(), to keep anonymous voting decoupled from
	 * MediaWiki's actor / temporary-account machinery.
	 *
	 * @return string
	 */
	private static function getVoterKey(): string {
		$ctx  = RequestContext::getMain();
		$user = $ctx->getUser();
		if ( $user->isRegistered() ) {
			return 'u:' . $user->getId();
		}
		$secret = MediaWikiServices::getInstance()->getMainConfig()->get( 'SecretKey' );
		return 'ip:' . hash_hmac( 'sha256', $ctx->getRequest()->getIP(), $secret );
	}

	/**
	 * Get rank for a specific page ID
	 *
	 * @param int $page_id
	 * @return array the number of positive votes, negative votes and total votes, and the
	 *               percentage of positive votes; see computeTotals(). A page with no votes
	 *               yields all zeros.
	 */
	public static function getRankingTotals( int $page_id ) {
		$dbr = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getReplicaDatabase();

		// We use sums rather than counts for b/c: rows migrated from the v1 table hold a
		// whole page's tally in a single row (see ArticleRankingPager::getQueryInfo()).
		// SUM() over no rows is NULL, which the int casts turn into 0.
		$positiveVotes = (int)$dbr->selectField(
			'article_rankings2',
			'SUM(ranking_value)',
			[
				'ranking_page_id' => $page_id,
				'ranking_value > 0'
			],
			__METHOD__
		);
		$negativeVotes = (int)$dbr->selectField(
			'article_rankings2',
			'SUM(-ranking_value)',
			[
				'ranking_page_id' => $page_id,
				'ranking_value < 0'
			],
			__METHOD__
		);

		return self::computeTotals( $positiveVotes, $negativeVotes );
	}

	/**
	 * Compute the ranking totals from the number of positive and negative votes.
	 *
	 * @param int $positiveVotes number of up-votes (>= 0)
	 * @param int $negativeVotes number of down-votes, as a positive count (>= 0)
	 * @return array{positive_votes: int, negative_votes: int, total_votes: int, rank: float}
	 *               rank is the percentage of positive votes, 0-100; 0 when there are no votes
	 */
	public static function computeTotals( int $positiveVotes, int $negativeVotes ): array {
		if ( $positiveVotes < 0 || $negativeVotes < 0 ) {
			throw new InvalidArgumentException( 'Vote counts cannot be negative' );
		}

		$totalVotes = $positiveVotes + $negativeVotes;

		return [
			'positive_votes' => $positiveVotes,
			'negative_votes' => $negativeVotes,
			'total_votes'    => $totalVotes,
			'rank'           => $totalVotes > 0 ? ( $positiveVotes / $totalVotes ) * 100.0 : 0.0
		];
	}

	/**
	 * @deprecated replaced by getRankingTotals()
	 */
	public static function getRank( int $page_id ) {
		return self::getRankingTotals( $page_id );
	}

	/**
	 * @param string $msgName
	 * @return string
	 */
	private static function getMsgForContent( $msgName ) {
		return wfMessage( $msgName )->inContentLanguage()->text();
	}

	/**
	 * @param Title|null $title
	 *
	 * @return string
	 */
	public static function createRankingSection( $title = null ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();
		$articleRankingConfig = $conf->get( 'ArticleRankingConfig' );
		$templateFileName = $conf->get( 'ArticleRankingTemplateFileName' );
		$templatePath = $conf->get( 'ArticleRankingTemplatePath' ) ?: __DIR__ . '/../templates';
		$templateParser = new TemplateParser( $templatePath );

		$params = [
			'section-title'  => self::getMsgForContent( 'ranking-section-title' ),
			'yes'            => self::getMsgForContent( 'ranking-yes' ),
			'no'             => self::getMsgForContent( 'ranking-no' ),
			'is-captcha-enabled' => Captcha::isEnabled(),
			'siteKey'        => Captcha::getSiteKey(),
			// Explicit widget language (he/ar/ru) rather than browser auto-detect.
			'language'       => RequestContext::getMain()->getLanguage()->getCode()
		];

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$continue = $hookContainer->run( 'ArticleRankingTemplateParams', [ &$params ] );

		if ( $continue ) {
			return $templateParser->processTemplate( $templateFileName, $params );
		}

		return '';
	}

}
