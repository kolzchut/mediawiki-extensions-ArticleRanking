<?php

class ArticleRankingHooks {

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( 'ext.articleranking' );

		return true;
	}

	public static function onSkinHelenaAfterTitle( HelenaTemplate &$template, String &$output ) {
		return true;
	}

}
