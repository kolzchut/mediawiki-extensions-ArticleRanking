<?php

class ARGetVotesAPI extends ApiBase {

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

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
		}
		else {
			$output[ 'success' ] = 0;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}