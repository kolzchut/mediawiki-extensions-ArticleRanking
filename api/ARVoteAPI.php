<?php

namespace MediaWiki\Extension\ArticleRanking;

use ApiBase;

class ARVoteAPI extends ApiBase {

	protected $secret = '';

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );

		global $wgArticleRankingCaptcha;

		$this->secret = $wgArticleRankingCaptcha[ 'secret' ];
	}

	protected function getAllowedParams() {
		return [
			'captchaToken' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => ArticleRanking::isCaptchaEnabled()
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

		$captchaToken = $params['captchaToken'];
		$page_id = $params[ 'id' ];
		$vote    = $params[ 'vote' ];
		$output  = [ 'success' => false ];

		if ( !ArticleRanking::isCaptchaEnabled() || ARCaptcha::verifyToken( $this->secret, $captchaToken ) ) {
			$result = ArticleRanking::saveVote( $page_id, $vote );
			$output[ 'success' ] = (int)$result;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

	public function needsToken() {
		return 'csrf';
	}

}
