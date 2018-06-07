<?php

class ArticleRankingHooks {

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( 'ext.articleRanking' );

		return true;
	}

}
