( function () {
	'use strict';
	var config = mw.config.get( 'wgArticleRankingConfig' ),
		selector;

	// If the ShareBar extension is not loaded, or Bootstrap's modal is not available, return
	// @todo this is actually a mess, as we should be agnostic about ShareBar's modal implementation,
	// but this workaround is needed because ShareBar doesn't load Bootstrap's modal itself,
	// Instead relying on skin:Helena, which might not be available
	if ( mw.loader.getState( 'ext.wr.ShareBar.js' ) === null || typeof $().modal !== 'function' ) {
		return;
	}

	selector = document.querySelector( '.ranking-btn.changerequest' );
	if ( selector !== null ) {
		selector.addEventListener( 'click', function ( e ) {
			var url = e.target.href;
			mw.loader.using( 'ext.wr.ShareBar.js' ).then( function () {
				mw.wrShareBar.openModal(
					url,
					config.changerequest.width,
					config.changerequest.height,
					{ title: mw.msg( 'ranking-cr-form-title' ) }
				);
			} );
			e.preventDefault();
		} );
	}
}() );
