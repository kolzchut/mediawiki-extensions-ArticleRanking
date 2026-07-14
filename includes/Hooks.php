<?php
namespace MediaWiki\Extension\ArticleRanking;

use DatabaseUpdater;
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
		$out->addModules( [ 'ext.articleRanking' ] );

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
		$dir = __DIR__ . '/../sql';

		// The new table will replace the old one completely
		$updater->addExtensionTable(
			'article_rankings2',
			"$dir/ArticleRankingsNewTableFormat.2022-03-29.sql"
		);

		// Migrate data from article_rankings to article_rankings2, then drop article_ranking
		$updater->dropExtensionTable(
			'article_rankings',
			"$dir/ArticleRankingMigrateDataFromOldTable.2022-04-12.sql"
		);

		// Per-identity dedup: add the voter key, then the unique index that
		// enforces one live vote per (page, identity). See ArticleRanking#8.
		// On fresh installs the base table already carries both, so these are
		// no-ops; they only fire on existing installs.
		$updater->addExtensionField(
			'article_rankings2',
			'ranking_voter_key',
			"$dir/patch-add-voter-key.sql"
		);
		$updater->addExtensionIndex(
			'article_rankings2',
			'ranking_voter_dedup',
			"$dir/patch-add-voter-dedup-index.sql"
		);
	}
}
