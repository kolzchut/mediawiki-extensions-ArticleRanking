( function ( mw, $ ) {
	'use strict';
	var wgArticleRankingConfig = mw.config.get( 'wgArticleRankingConfig' ),
		uri;

	if ( wgArticleRankingConfig.changerequest !== null && wgArticleRankingConfig.changerequest.url ) {
		uri = new mw.Uri( wgArticleRankingConfig.changerequest.url );
		uri.extend( {
			page: mw.config.get( 'wgTitle' ),
			lang: mw.config.get( 'wgContentLanguage' ),
			categories: mw.config.get( 'wgCategories' ).join()
		} );

		mw.log( uri.toString() );

		$( '.ranking-btn.changerequest' ).click( function() {
			mw.wrShareBar.openModal(
				uri.toString(),
				wgArticleRankingConfig.changerequest.width,
				wgArticleRankingConfig.changerequest.height
			);
		} );
	}
}( mediaWiki, jQuery ) );
