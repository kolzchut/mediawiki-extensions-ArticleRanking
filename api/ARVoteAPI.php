<?php

class ARVoteAPI extends ApiBase {

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

	protected function getAllowedParams() {
		return [
			'id' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'vote' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			]
		];
	}

	public function execute() {
		$queryResult = $this->getResult();
		$params      = $this->extractRequestParams();

		$page_id = $params[ 'id' ];
		$vote    = $params[ 'vote' ];
		$output  = [ 'success' => false ];

		$result = ArticleRanking::saveVote( $page_id, $vote );
		$output[ 'success' ] = (int)$result;

		$queryResult->addValue( null, 'ranking', $output );
	}

}