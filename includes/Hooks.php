<?php
namespace MediaWiki\Extension\ArticleRanking;

use DatabaseUpdater;
use OutputPage;
use Skin;

class Hooks {

	/**
	 * Adds VisualEditor JS to the output.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage &$out The page view.
	 * @param Skin $skin The skin that's going to build the UI.
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( [ 'ext.articleRanking', 'ext.articleRanking.changeRequest' ] );

		// $out->showErrorPage( 'ranking-invalid-captcha-title', 'ranking-invalid-captcha-keys-message' );
		if ( Vote::isCaptchaEnabled() ) {
			$out->addHeadItem(
				'recaptcha',
				'<script async defer src="https://www.google.com/recaptcha/api.js"></script>'
			);
		}
	}

	/**
	 * Hook: ResourceLoaderGetConfigVars called right before
	 * ResourceLoaderStartUpModule::getConfig returns
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 *
	 * @param &$vars array of variables to be added into the output of the startup module.
	 */
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		global $wgArticleRankingConfig;
		$vars['wgArticleRankingConfig'] = $wgArticleRankingConfig;
		$vars['wgArticleRankingConfig']['isCaptchaEnabled'] = Vote::isCaptchaEnabled();
	}

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'article_ranking',
			__DIR__ . '/../sql/ArticleRankings.sql'
		);

		// The new table will replace the old one completely
		$updater->addExtensionTable(
			'article_ranking2',
			__DIR__ . '/../sql/ArticleRankingsNewTableFormat.2022-03-29.sql'
		);
	}

}
