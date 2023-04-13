( function () {
	'use strict';

	mw.ranking = {
		positiveVote: false,
		config: mw.config.get( 'wgArticleRankingConfig' ),
		$btns: $( '.ranking-section .sub-section1 .ranking-btn' ),
		$statusIcon: $( '<i>' ).addClass( 'fa fa-spinner fa-spin' ),
		$votingMessages: $( '.ranking-section .voting-messages' ),

		vote: function ( captchaToken ) {
			return new mw.Api().postWithToken( 'csrf', {
				action: 'rank-vote',
				pageid: mw.config.get( 'wgArticleId' ),
				captchaToken: captchaToken || null,
				vote: this.positiveVote ? 1 : -1
			} ).fail( function () {
				mw.ranking.informFailedVote();
			} ).done( function( response ) {
				if ( response.success ) {
					mw.ranking.setMessage( mw.messages.get( 'ranking-vote-success' ) );
					mw.ranking.$statusIcon.removeClass( 'fa-spinner fa-spin' ).addClass( 'fa-check' );
					mw.ranking.trackEvent( 'vote', mw.ranking.positiveVote ? 'yes' : 'no' );
				} else {
					mw.ranking.informFailedVote();
				}
			} );
		},
		setMessage: function ( msg ) {
			mw.ranking.$votingMessages.text( msg ).show();
		},
		informFailedVote: function () {
			mw.ranking.$btns.attr( 'disabled', false ).removeClass( 'selected' );
			mw.ranking.$statusIcon.detach();
			mw.ranking.setMessage( mw.messages.get( 'ranking-vote-fail' ) );
		},
		verifyCaptcha: function ( token ) {
			return mw.ranking.vote( token );
		},
		trackEvent: function ( action, label ) {
			if ( mw.ranking.config.trackClicks !== true ||
				mw.loader.getState( 'ext.googleUniversalAnalytics.utils' ) === null
			) {
				return;
			}

			mw.loader.using( 'ext.googleUniversalAnalytics.utils' ).then( function () {
				mw.googleAnalytics.utils.recordEvent( {
					eventCategory: 'article-ranking',
					eventAction: action,
					eventLabel: label,
					nonInteraction: false
				} );
			} );
		}
	};

	$( function () {
		$( mw.ranking.$btns ).on( 'click', function () {
			mw.ranking.$votingMessages.hide(); // In case we already displayed a message before
			mw.ranking.positiveVote = $( this ).hasClass( 'yes' );
			mw.ranking.$btns.attr( 'disabled', true );
			$( this ).prepend( mw.ranking.$statusIcon );
			$( this ).addClass( 'selected' );
			if ( mw.ranking.config.isCaptchaEnabled === true ) {
				hcaptcha.execute();
			} else {
				mw.ranking.vote();
			}
		} );

	} );

	window.verifyRankingCaptcha = mw.ranking.verifyCaptcha;
	window.handleRankingCaptchaError = mw.ranking.informFailedVote;

}() );
