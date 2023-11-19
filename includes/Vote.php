<?php

namespace MediaWiki\Extension\ArticleRanking;

use InvalidArgumentException;
use MediaWiki\Extension\ArticleContentArea\ArticleContentArea;
use MediaWiki\MediaWikiServices;
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
		if ( !in_array( $vote, [ -1, 1 ] ) ) {
			throw new InvalidArgumentException( '$vote can only be -1 or 1' );
		}
		if ( !$title->exists() ) {
			throw new InvalidArgumentException( "$title does not exist" );
		}

		$requestContext = RequestContext::getMain();
		$dbw = wfGetDB( DB_PRIMARY );

		$result = $dbw->insert( 'article_rankings2', [
			'ranking_timestamp' => $dbw->timestamp(),
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
				'ranking_value > 0'
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
	 * @deprecated replaced by getRankingTotals()
	 */
	public static function getRank( int $page_id ) {
		return self::getRankingTotals( $page_id );
	}

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
			'siteKey'        => Captcha::getSiteKey()
		];

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$continue = $hookContainer->run( 'ArticleRankingTemplateParams', [ &$params ] );

		if ( $continue ) {
			return $templateParser->processTemplate( $templateFileName, $params );
		}

		return '';
	}

}
