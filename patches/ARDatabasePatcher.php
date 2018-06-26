<?php
/**
 * @file
 * @license GNU General Public Licence 2.0 or later
 */

/**
 * Maintenance helper class that updates the database schema when required.
 *
 * Apply patches with /maintenance/update.php
 */
class ARDatabasePatcher {
	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * This function makes sure that the database schema is up to date.
	 *
	 * @param $updater DatabaseUpdater|null
	 * @return bool
	 */
	public static function applyUpdates( $updater = null ) {
		if ( $updater->getDB()->getType() == 'mysql' ) {
			$updater->addExtensionTable(
				 'article_ranking',
				 __DIR__ . '/ArticleRankings.sql'
			);
		}
		return true;
	}
}
