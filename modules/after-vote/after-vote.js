( function ( mw, $ ) {
	'use strict';
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
		$('.after-vote-form textarea').bind('keydown change', function(){
			if( $(this).val().trim() ){
				$('.after-voting-button').removeAttr('disabled');
			}
			else{
				$('.after-voting-button').attr('disabled','disabled');
			}
		}).trigger('keydown');
		$('.after-voting-button').unbind('click').bind('click', function(){
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