( function () {
	'use strict';
	var config = mw.config.get( 'wgArticleRankingConfig' ),
		uri,
		selector;

	if ( config.changerequest !== null && config.changerequest.url ) {
		uri = new mw.Uri( config.changerequest.url );
		uri.extend( {
			articleId: mw.config.get( 'wgArticleId' ),
			page: mw.config.get( 'wgTitle' ),
			lang: mw.config.get( 'wgContentLanguage' ),
			contentArea: mw.config.get( 'wgArticleContentArea' )
		} );

		selector = document.querySelector( '.ranking-btn.changerequest' );
		if ( selector !== null ) {
			selector.addEventListener( 'click', function () {
				mw.wrShareBar.openModal(
					uri.toString(),
					config.changerequest.width,
					config.changerequest.height,
					{ title: mw.msg( 'ranking-cr-form-title' ) }
				);
			} );
		}
	}
}() );
