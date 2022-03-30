<?php

namespace MediaWiki\Extension\ArticleRanking;

use InvalidArgumentException;
use RequestContext;
use TemplateParser;
use Title;

class Vote {

	/**
	 * Save vote for a certain page ID
	 *
	 * @param Title $title
	 * @param int $vote 1 for positive vote, 0 for negative vote
	 *
	 * @return bool
	 */
	public static function saveVote( Title $title, int $vote ) {
		if ( !in_array( $vote, [-1, 1] ) ) {
			throw new InvalidArgumentException( '$vote can only be -1 or 1' );
		}
		if ( !$title->exists() ) {
			throw new InvalidArgumentException( "$title does not exist" );
		}

		$requestContext = RequestContext::getMain();
		$dbw = wfGetDB( DB_PRIMARY );

		$result = $dbw->insert( 'article_rankings2', [
			'ranking_timestamp' => wfTimestampNow(),
			'ranking_value' => $vote,
			'ranking_page_id' => $title->getArticleID(),
			'ranking_ip' => $requestContext->getRequest()->getIP(),
			'ranking_actor' => $requestContext->getUser()->getActorId()
		] );

		return (bool)$result;
	}


	/**
	 * Get rank for a specific page ID
	 *
	 * @param int $page_id
	 * @return array|bool an array that includes the number of positive votes, total votes and
	 *                    total rank percentage, or false
	 */
	public static function getRankingTotals( int $page_id ) {
		$dbr = wfGetDB( DB_REPLICA );

		$positiveVotes = $dbr->selectField(
			'article_rankings2',
			'SUM(ranking_value)',
			[
				'ranking_page_id' => $page_id,
				'ranking_value' => 1
			]
		);
		$negativeVotes = $dbr->selectField(
			'article_rankings2',
			'SUM(ranking_value)',
			[
				'ranking_page_id' => $page_id,
				'ranking_value' => -1
			]
		);

		// No results
		if ( $positiveVotes === false && $negativeVotes === false ) {
			return false;
		}

		$totalVotes = $positiveVotes + $negativeVotes;

		return [
			'positive_votes' => $positiveVotes,
			'negative_votes' => $negativeVotes,
			'total_votes'    => $totalVotes,
			'rank'           => ( $positiveVotes / $totalVotes ) * 100
		];
	}

	/**
	 * Get rank for a specific page ID
	 *
	 * @param int $page_id
	 * @return array|bool an array that includes the number of positive votes, total votes and
	 *                    total rank percentage, or false
	 */
	public static function getRank( Int $page_id ) {
		$dbr = wfGetDB( DB_REPLICA );

		$result = $dbr->selectField(
			'article_rankings2',
			'SUM(ranking_value)',
			[ 'page_id' => $page_id ]
		);

		$result = $result->fetchRow();

		if ( !$result ) {
			return false;
		}

		return [
			'positive_votes' => $result[ 'positive_votes' ],
			'total_votes'    => $result[ 'total_votes' ],
			'rank'           => ( (int)$result[ 'positive_votes' ] / (int)$result[ 'total_votes' ] ) * 100
		];
	}

	public static function createRankingSection() {
		global $wgArticleRankingCaptcha;

		$templateParser = new TemplateParser( __DIR__ . '/../templates' );

		return $templateParser->processTemplate( 'voting', [
			'section1title'  => wfMessage( 'ranking-section1-title' ),
			'yes'            => wfMessage( 'ranking-yes' ),
			'no'             => wfMessage( 'ranking-no' ),
			'section2title'  => wfMessage( 'ranking-section2-title' ),
			'proposeChanges' => wfMessage( 'ranking-propose-change' ),
			'is-captcha-enabled' => self::isCaptchaEnabled(),
			'siteKey'        => $wgArticleRankingCaptcha[ 'siteKey' ]
		] );
	}

	public static function isCaptchaEnabled() {
		global $wgArticleRankingCaptcha;
		return ( $wgArticleRankingCaptcha[ 'secret' ] && $wgArticleRankingCaptcha[ 'siteKey' ] );
	}
}

