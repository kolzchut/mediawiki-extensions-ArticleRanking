( function ( mw, $ ) {
	'use strict';

	function isElementInViewport (el) {
	    var rect = el.getBoundingClientRect();
	    return (rect.top>-1 && rect.bottom <= $(window).height());
	}
	function scrollIfFocusedTextareaNotInFold(){
		var focusedTextarea = $('.after-vote-form textarea:visible:focus');
		if( focusedTextarea.length && !isElementInViewport( focusedTextarea[0])){
			$([document.documentElement, document.body]).animate({
		        scrollTop: focusedTextarea.offset().top  - $(window).height() + focusedTextarea.height() + 10
		    }, 800);
		}
	}
	
	function openMessage( title ){
		var messageDialog = new OO.ui.MessageDialog(),
            windowManager = new OO.ui.WindowManager();
        $( 'body' ).append( windowManager.$element );

        windowManager.addWindows( [ messageDialog ] );
        windowManager.openWindow( messageDialog, {
          title: title,
          actions: [
            {
              action: 'accept',
              label: mw.message('article-ranking-after-vote-OK').text(),
              flags: 'primary'
            }
          ]
        }).closed.done(function(data){
            windowManager.$element.remove();
        });
	}

	$(function(){
		$('.after-vote-form textarea').on('keydown change', function(){
			if( $(this).val().trim() ){
				$('.after-voting-button').removeAttr('disabled');
			}
			else{
				$('.after-voting-button').attr('disabled','disabled');
			}
		}).trigger('keydown')
		//catch virtual keyboard opening
		$(window).bind('resize', function(){

		});
		$('.after-voting-button').off('click').on('click', function(){
			$('.after-voting-button').attr('disabled','disabled');
			let message = $('.after-vote-form textarea:visible').val();
			return new mw.Api().postWithToken( 'csrf', {
				action: 'rank-vote-message',
				id: mw.config.get( 'wgArticleId' ),
				captchaToken: mw.ranking.captchaToken || null,
				message: message,
				vote: Number( mw.ranking.positiveVote )
			} ).fail( function() {
				$('.after-voting-button').removeAttr('disabled');
				openMessage(mw.message('article-ranking-after-vote-failed').text());
			} ).done( function( response ) {
				$('.after-vote-form').addClass('vote-message-sent').append( $('<div class="vote-message-sent-text">').html(mw.message('article-ranking-after-vote-success').text()) );
			} );
		});
	});
}( mediaWiki, jQuery ) );