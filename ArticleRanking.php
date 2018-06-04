<?php

class ArticleRanking {

	protected $displayMinVotes = 5;

	/**
	 * Save vote for a certain page ID
	 *
	 * @param int $page_id
	 * @param int $vote 1 for positive vote, 0 for negative vote
	 * @return bool
	 */
	public function saveVote( Int $page_id, Int $vote ) {
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

			$dbw->begin();
			$result = $dbw->update( 'article_rankings',
				[
					'positive_votes' => $positiveVotes,
					'total_votes'    => $totalVotes
				],
				[
					'page_id' => $page_id
				]
			);
			$dbw->commit();
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
	 * @return int|bool number of positive votes, or false when no entry exists
	 */
	public function getRank( Int $page_id ) {
		$dbr = wfGetDB( DB_REPLICA );

		$result = $dbr->select(
			'article_rankings',
			[ '*' ],
			[ 'page_id' => $page_id ]
		);

		$result = $result->fetchRow();

		if ( !$result || $result[ 'positive_votes' ] < $this->displayMinVotes ) {
			return false;
		}

		return $result[ 'positive_votes' ] ? (int)$result[ 'positive_votes' ] : false;
	}

	/**
	 * Sets minimum votes needed in order to see the current ranking
	 *
	 * @param int $minVotes
	 * @return $this
	 */
	public function setMinVotes( $minVotes ) {
		$this->displayMinVotes = $minVotes;

		return $this;
	}

	/**
	 * Returns minimum votes needed in order to see the current ranking
	 *
	 * @return int
	 */
	public function getMinVotes() {
		return $this->displayMinVotes;
	}

}