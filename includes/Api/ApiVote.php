<?php

namespace MediaWiki\Extension\ArticleRanking\Api;

use ApiBase;
use MediaWiki\Extension\ArticleRanking\Vote;
use MediaWiki\Extension\ArticleRanking\Captcha;

class ApiVote extends ApiBase {

	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
	}

	protected function getAllowedParams() {
		return [
			'captchaToken' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => Captcha::isEnabled()
			],
			'pageid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'vote' => [
				ApiBase::PARAM_TYPE => [ '-1', '1' ],
				ApiBase::PARAM_REQUIRED => false
			]
		];
	}

	public function execute() {
		$queryResult = $this->getResult();
		$params      = $this->extractRequestParams();

		$captchaToken = $params['captchaToken'];
		// validation: getTitleOrPageId() will throw an api error if the page id isn't valid
		$page = $this->getTitleOrPageId( $params );
		$vote    = $params[ 'vote' ];
		$result = 0;

		// If the captcha is disabled, verifyToken() will always return true
		if ( Captcha::verifyToken( $captchaToken ) ) {
			$result = Vote::saveVote( $page->getTitle(), $vote );
		}

		$queryResult->addValue( null, 'success', $result ? 1 : 0 );
	}

	public function needsToken() {
		return 'csrf';
	}

}
