<?php
namespace MediaWiki\Extension\ArticleRanking;

use ApiBase;
use ApiMain;

class ARVoteMessageAPI extends ApiBase {

	protected $secret = '';

	/**
	 * ARVoteMessageAPI constructor.
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName Name of this module
	 */
	public function __construct( $mainModule, $moduleName ) {
		parent::__construct( $mainModule, $moduleName );

		global $wgArticleRankingCaptcha;

		$this->secret = $wgArticleRankingCaptcha[ 'secret' ];
	}

	/**
	 * @return array
	 */
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
			'message' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'vote' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	public function execute() {
		$queryResult = $this->getResult();
		$params      = $this->extractRequestParams();

		$captchaToken = $params['captchaToken'];
		$page_id = $params[ 'id' ];
		$vote    = $params[ 'vote' ];
		$message    = $params[ 'message' ];
		$output  = [ 'success' => false ];

		if ( !ArticleRanking::isCaptchaEnabled() || ARCaptcha::verifyToken( $this->secret, $captchaToken ) ) {
			$result = ArticleRanking::saveVoteMessage( $page_id, $vote, $message );
			$output[ 'success' ] = (int)$result;
		}

		$queryResult->addValue( null, 'ranking', $output );
	}

	public function needsToken() {
		return 'csrf';
	}

}
