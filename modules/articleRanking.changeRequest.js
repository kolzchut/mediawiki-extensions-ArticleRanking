( function () {
	'use strict';
	var config = mw.config.get( 'wgArticleRankingConfig' ),
		uri;

	if ( config.changerequest !== null && config.changerequest.url ) {
		uri = new mw.Uri( config.changerequest.url );
		uri.extend( {
			page: mw.config.get( 'wgTitle' ),
			lang: mw.config.get( 'wgContentLanguage' ),
			categories: mw.config.get( 'wgCategories' ).join()
		} );

		document.querySelector( '.ranking-btn.changerequest' ).addEventListener( 'click', function () {
			mw.wrShareBar.openModal(
				uri.toString(),
				config.changerequest.width,
				config.changerequest.height
			);
		} );
	}
}() );
