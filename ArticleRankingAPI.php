<?php

class ArticleRankingAPI extends ApiBase {

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

	protected function getAllowedParams() {
		return [
			'purpose' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
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

		$ranking = new ArticleRanking();
		
		$purpose = $params[ 'purpose' ];
		$page_id = $params[ 'id' ];
		$vote    = $params[ 'vote' ];
		$output  = [ 'success' => false ];

		if ( $purpose === 'vote' ) {
			$result = $ranking->saveVote( $page_id, $vote );
			$output[ 'success' ] = (int)$result;
		}

		if ( $purpose === 'votes' ) {
			$result = $ranking->getRank( $page_id );

			if ( $result !== false ) {
				$output[ 'success' ] = 1;
				$output[ 'votes' ]   = $result;
			}
			else {
				$output[ 'success' ] = 0;
			}
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}