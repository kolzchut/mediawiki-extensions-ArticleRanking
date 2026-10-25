<?php

namespace MediaWiki\Extension\ArticleRanking\Api;

use ApiBase;
use MediaWiki\Extension\ArticleRanking\Vote;

class ApiGetVotes extends ApiBase {

	/**
	 * @param \ApiMain $main
	 * @param string $moduleName
	 */
	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return [
			'pageid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	public function execute() {
		$queryResult = $this->getResult();
		$params = $this->extractRequestParams();

		$page = $this->getTitleOrPageId( $params );
		$result = Vote::getRank( $page->getId() );

		// A page with no votes has no rank; report it as before, with success = 0
		if ( $result[ 'total_votes' ] > 0 ) {
			$output[ 'success' ] = 1;
			$output[ 'votes' ]   = ceil( $result[ 'rank' ] );
		} else {
			$output[ 'success' ] = 0;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}
