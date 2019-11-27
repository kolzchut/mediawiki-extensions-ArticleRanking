( function () {
	'use strict';

	function isElementInViewport( el ) {
		var rect = el.getBoundingClientRect();
		return ( rect.top > -1 && rect.bottom <= $( window ).height() );
	}
	function scrollIfFocusedTextareaNotInFold() {
		var focusedTextarea = $( '.after-vote-form textarea:visible:focus' ),
			scrollTopOffset;
		if ( focusedTextarea.length && !isElementInViewport( focusedTextarea[ 0 ] ) ) {
			scrollTopOffset = focusedTextarea.offset().top -
				$( window ).height() +
				focusedTextarea.height() + 10;
			$( [ document.documentElement, document.body ] ).animate( {
				scrollTop: scrollTopOffset
			}, 800 );
		}
	}

	function openMessage( title ) {
		var messageDialog = new OO.ui.MessageDialog(),
			windowManager = new OO.ui.WindowManager();
		$( 'body' ).append( windowManager.$element );

		windowManager.addWindows( [ messageDialog ] );
		windowManager.openWindow( messageDialog, {
			title: title,
			actions: [
				{
					action: 'accept',
					label: mw.message( 'article-ranking-after-vote-OK' ).text(),
					flags: 'primary'
				}
			]
		} ).closed.done( function () {
			windowManager.$element.remove();
		} );
	}

	$( function () {
		$( '.after-vote-form textarea' ).on( 'keydown change', function () {
			if ( $( this ).val().trim() ) {
				$( '.after-voting-button' ).removeAttr( 'disabled' );
			} else {
				$( '.after-voting-button' ).attr( 'disabled', 'disabled' );
			}
		} ).trigger( 'keydown' );
		// catch virtual keyboard opening
		$( window ).on( 'resize', function () {

		} );
		$( '.after-voting-button' ).off( 'click' ).on( 'click', function () {
			var message = $( '.after-vote-form textarea:visible' ).val();

			$( '.after-voting-button' ).attr( 'disabled', 'disabled' );
			return new mw.Api().postWithToken( 'csrf', {
				action: 'rank-vote-message',
				id: mw.config.get( 'wgArticleId' ),
				captchaToken: mw.ranking.captchaToken || null,
				message: message,
				vote: Number( mw.ranking.positiveVote )
			} ).fail( function () {
				$( '.after-voting-button' ).removeAttr( 'disabled' );
				openMessage( mw.message( 'article-ranking-after-vote-failed' ).text() );
			} ).done( function () {
				var $el = $( '<div>' )
					.addClass( 'vote-message-sent-text' )
					.html( mw.msg( 'article-ranking-after-vote-success' ) );
				$( '.after-vote-form' ).addClass( 'vote-message-sent' ).append( $el );
			} );
		} );
	} );
}() );
