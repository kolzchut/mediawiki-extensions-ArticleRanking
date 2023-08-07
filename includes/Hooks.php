<?php
namespace MediaWiki\Extension\ArticleRanking;

use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Skin;

class Hooks {

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 *
	 * @throws \ConfigException
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$conf = MediaWikiServices::getInstance()->getMainConfig();

		$modules = [ 'ext.articleRanking' ];
		if ( $conf->get( 'ArticleRankingAddChangeRequest' ) === true ) {
			$modules[] = 'ext.articleRanking.changeRequest';
		}

		$out->addModules( $modules );

		if ( Captcha::isEnabled() ) {
			$out->addHeadItem(
				'captcha',
				Captcha::getScript()
			);
		}
	}

	/**
	 * Hook: ResourceLoaderGetConfigVars called right before
	 * ResourceLoaderStartUpModule::getConfig returns
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 *
	 * @param array &$vars variables to be added into the output of the startup module.
	 */
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		global $wgArticleRankingConfig;
		$vars['wgArticleRankingConfig'] = $wgArticleRankingConfig;
		$vars['wgArticleRankingConfig']['isCaptchaEnabled'] = Captcha::isEnabled();
	}

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		// The new table will replace the old one completely
		$updater->addExtensionTable(
			'article_rankings2',
			__DIR__ . '/../sql/ArticleRankingsNewTableFormat.2022-03-29.sql'
		);

		// Migrate data from article_rankings to article_rankings2, then drop article_ranking
		$updater->dropExtensionTable(
			'article_rankings',
			__DIR__ . '/../sql/ArticleRankingMigrateDataFromOldTable.2022-04-12.sql'
		);
	}
}
