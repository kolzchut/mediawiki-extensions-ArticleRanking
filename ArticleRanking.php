<?php

class ArticleRanking {

	/**
	 * Save vote for a certain page ID
	 *
	 * @param int $page_id
	 * @param int $vote 1 for positive vote, 0 for negative vote
	 * @return bool
	 */
	public static function saveVote( Int $page_id, Int $vote ) {
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
	 * Get rank for a specific page ID
	 *
	 * @param int $page_id
	 * @return array|bool an array that includes the number of positive votes, total votes and
	 *                    total rank percentage, or false
	 */
	public static function getRank( Int $page_id ) {
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

	public static function createRankingSection() {
		global $wgArticleRankingCaptcha;

		$templateParser = new TemplateParser( __DIR__ . '/templates' );

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

