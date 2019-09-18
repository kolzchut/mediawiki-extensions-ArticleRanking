<?php
namespace MediaWiki\Extension\ArticleRanking;

use Hooks;
use MediaWiki\MediaWikiServices;
use TemplateParser;

class ArticleRanking {

	/**
	 * Save vote for a certain page ID
	 *
	 * @param int $page_id
	 * @param int $vote 1 for positive vote, 0 for negative vote
	 * @return bool
	 */
	public static function saveVote( int $page_id, int $vote ) {
		$dbw = wfGetDB( DB_MASTER );

		$votes = $dbw->select( 'article_rankings',
			[ '*' ],
			[ 'page_id' => $page_id ]
		);

		$ranking = $votes->fetchObject();

		if ( $ranking ) {
			$positiveVotes = $ranking->positive_votes;
			$totalVotes    = $ranking->total_votes;

			if ( $vote === 1 ) {
				$positiveVotes = $positiveVotes + 1;
			} else {
				$totalVotes = $totalVotes + 1;
			}

			$result = $dbw->update( 'article_rankings',
				[
					'positive_votes' => $positiveVotes,
					'total_votes'    => $totalVotes
				],
				[
					'page_id' => $page_id
				]
			);
		} else {
			$result = $dbw->insert( 'article_rankings', [
				'positive_votes' => $vote,
				'total_votes'    => 1,
				'page_id'        => $page_id
			] );
		}

		return (bool)$result;
	}

	/**
	 * Save vote nessage for a certain page ID
	 *
	 * @param int $page_id
	 * @param int $vote 1 for positive vote, 0 for negative vote
	 * @param string $message message for vote
	 * @return bool
	 */
	public static function saveVoteMessage( int $page_id, int $vote, string $message ) {
		$dbw = wfGetDB( DB_MASTER );
		$fields = [
				'positive_or_negative' => $vote,
				'votes_messages'    => $message,
				'votes_messages_page_id'        => $page_id,
				'votes_timestamp'        => $dbw->timestamp( wfTimestampNow() )
			];
		$result = $dbw->insert( 'article_rankings_votes_messages', $fields );
		return (bool)$result;
	}

	/**
	 * Get rank for a specific page ID
	 *
	 * @param int $page_id
	 * @return array|bool an array that includes the number of positive votes, total votes and
	 *                    total rank percentage, or false
	 */
	public static function getRank( int $page_id ) {
		$dbr = wfGetDB( DB_REPLICA );

		$result = $dbr->select(
			'article_rankings',
			[ '*' ],
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

	/**
	 * @param array $additionalParams
	 *
	 * @return string
	 * @throws \ConfigException
	 * @throws \FatalError
	 * @throws \MWException
	 */
	public static function createRankingSection( $additionalParams = [] ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();
		$wgArticleRankingCaptcha = $conf->get( 'ArticleRankingCaptcha' );
		$wgArticleRankingTemplateFileName = $conf->get( 'ArticleRankingTemplateFileName' );
		$wgArticleRankingTemplatePath = $conf->get( 'ArticleRankingTemplatePath' );
		$wgArticleRankingTemplatePath = $wgArticleRankingTemplatePath ? $wgArticleRankingTemplatePath : __DIR__ . '/templates';
		$templateParser = new TemplateParser( $wgArticleRankingTemplatePath );
		$params = [
			'section1title'  => wfMessage( 'ranking-section1-title' ),
			'yes'            => wfMessage( 'ranking-yes' ),
			'no'             => wfMessage( 'ranking-no' ),
			'section2title'  => wfMessage( 'ranking-section2-title' ),
			'ranking-vote-success'  => wfMessage( 'ranking-vote-success' ),
			'ranking-vote-fail'  => wfMessage( 'ranking-vote-fail' ),
			'proposeChanges' => wfMessage( 'ranking-propose-change' ),
			'voting-messages-positive-placeholder' => wfMessage( 'voting-messages-positive-placeholder' ),
			'voting-messages-negative-placeholder' => wfMessage( 'voting-messages-negative-placeholder' ),
			'is-captcha-enabled' => self::isCaptchaEnabled(),
			'is-after-vote-form' => $conf->get( 'ArticleRankingAddAfterVote' ),
			'after-voting-button' => wfMessage( 'after-vote-button' )->text() . '<i class="fas fa-chevron-left"></i>',
			'siteKey'        => $wgArticleRankingCaptcha[ 'siteKey' ]
		];
		$continue = Hooks::run( 'ArticleRankingTemplateParams', [ &$params , $additionalParams ] );
		if ( $continue ) {
			return $templateParser->processTemplate( $wgArticleRankingTemplateFileName, $params );
		}

		return '';
	}

	public static function isCaptchaEnabled() {
		global $wgArticleRankingCaptcha;
		return ( $wgArticleRankingCaptcha[ 'secret' ] && $wgArticleRankingCaptcha[ 'siteKey' ] );
	}
}
