<?php

namespace MediaWiki\Extension\ArticleRanking;

use ApiBase;
use ApiMain;

class ARGetVotesAPI extends ApiBase {

	/**
	 * ARGetVotesAPI constructor.
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName Name of this module
	 */
	public function __construct( $mainModule, $moduleName ) {
		parent::__construct( $mainModule, $moduleName );
	}

	/**
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'id' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	public function execute() {
		$queryResult = $this->getResult();
		$params      = $this->extractRequestParams();

		$page_id = $params[ 'id' ];
		$output  = [ 'success' => false ];

		$result = ArticleRanking::getRank( $page_id );

		if ( $result !== false ) {
			$output[ 'success' ] = 1;
			$output[ 'votes' ]   = ceil( $result[ 'rank' ] );
		} else {
			$output[ 'success' ] = 0;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}
