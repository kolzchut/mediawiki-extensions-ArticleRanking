( function ( mw, $ ) {
	'use strict';
	var wgArticleRankingConfig = mw.config.get( 'wgArticleRankingConfig' );

	if ( wgArticleRankingConfig.changerequest !== null && wgArticleRankingConfig.changerequest.url ) {
		$( '.ranking-btn.changerequest' ).click( function() {
			mw.wrShareBar.openModal(
				wgArticleRankingConfig.changerequest.url,
				wgArticleRankingConfig.changerequest.width,
				wgArticleRankingConfig.changerequest.height
			);
		} );
	}
}( mediaWiki, jQuery ) );
