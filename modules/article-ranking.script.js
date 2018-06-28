( function ( mw, $ ) {

	'use strict';

	mw.ranking = {
		positiveVote: false,
		config: mw.config.get( 'wgArticleRankingConfig' ),
		vote: function ( token ) {
			return $.ajax( {
				method: 'POST',
				url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
				data: {
					action: 'rank-vote',
					id: mw.config.get( 'wgArticleId' ),
					format: 'json',
					token: token,
					vote: Number( this.positiveVote )
				},
				success: function( response ) {
					if ( response.ranking.success ) {
						mw.ranking.setMessage( mw.messages.get( 'ranking-vote-success' ) );

						mw.ranking.trackEvent( 'click', 'vote', mw.ranking.positiveVote )
					} else {
						$( '.ranking-section .sub-section1 .ranking-btn' ).attr( 'disabled', false );
						mw.ranking.setMessage( mw.messages.get( 'ranking-vote-fail' ) );
					}
				}.bind( this )
			} );
		},
		setMessage: function ( msg ) {
			$( '.ranking-section .voting-messages' ).text( msg );
		},
		verifyCaptcha: function( token ) {
			return mw.ranking.vote( token );
		},
		trackEvent: function(action, label, value) {
			if ( mw.loader.getState( 'ext.googleUniversalAnalytics.utils' ) === null ) {
				return;
			}

			mw.loader.using( 'ext.googleUniversalAnalytics.utils' ).then( function () {
				if ( mw.ranking.config.trackClicks === true ) {
					mw.googleAnalytics.utils.recordEvent( {
						eventCategory: 'ranking',
						eventAction: action,
						eventLabel: label,
						eventValue: value,
						nonInteraction: false
					} );
				}
			} );
		}
	};
	$( document ).ready( function () {
		var btns = $( '.ranking-section .sub-section1 .ranking-btn' );

		btns.click( function () {
			mw.ranking.positiveVote = $( this ).hasClass( 'yes' );
			btns.attr( 'disabled', true );
			grecaptcha.execute();
		} );

	} );

	window.verifyRankingCaptcha = mw.ranking.verifyCaptcha;

}( mediaWiki, jQuery ) );
