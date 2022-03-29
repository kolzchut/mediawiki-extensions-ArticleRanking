<?php

namespace MediaWiki\Extension\ArticleRanking;

use ApiBase;

class ApiGetVotes extends ApiBase {

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

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

		if ( $result !== false ) {
			$output[ 'success' ] = 1;
			$output[ 'votes' ]   = ceil( $result[ 'rank' ] );
		}
		else {
			$output[ 'success' ] = 0;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}
