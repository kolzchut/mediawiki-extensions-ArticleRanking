<?php

class ARVoteAPI extends ApiBase {

	protected $secret = '';

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );

		global $wgArticleRankingCaptcha;

		$this->secret = $wgArticleRankingCaptcha[ 'secret' ];
	}

	protected function getAllowedParams() {
		return [
			'token' => [
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

		$token   = $params[ 'token' ];
		$page_id = $params[ 'id' ];
		$vote    = $params[ 'vote' ];
		$output  = [ 'success' => false ];

		$captchaResult = ARCaptcha::verifyToken( $this->secret, $token );

		if ( $captchaResult ) {
			$result = ArticleRanking::saveVote( $page_id, $vote );
			$output[ 'success' ] = (int)$result;
		} else {
			$queryResult->addValue( null, 'ranking', $output );
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

}