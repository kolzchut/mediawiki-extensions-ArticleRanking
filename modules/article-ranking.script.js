( function ( mw, $ ) {
	'use strict';

	mw.ranking = {
		positiveVote: false,
		config: mw.config.get( 'wgArticleRankingConfig' ),
		$btns: $( '.ranking-section .sub-section1 .ranking-btn' ),
		$statusIcon: $( '<i class="fa fa-spinner fa-spin"></i>' ),
		$votingMessages: $( '.ranking-section .voting-messages' ),
		vote: function ( captchaToken ) {
			mw.ranking.captchaToken = captchaToken;
			return new mw.Api().postWithToken( 'csrf', {
				action: 'rank-vote',
				id: mw.config.get( 'wgArticleId' ),
				captchaToken: captchaToken || null,
				vote: Number( this.positiveVote )
			} ).fail( function() {
				mw.ranking.informFailedVote();
			} ).done( function( response ) {
				if ( response.ranking.success ) {
					mw.ranking.getClickedBtn().removeClass('on-call').addClass('after-success-call');
					mw.ranking.setMessageSuccess(Number( mw.ranking.positiveVote ));
					mw.ranking.trackEvent( 'vote', mw.ranking.positiveVote ? 'yes' : 'no' );
				} else {
					mw.ranking.informFailedVote();
				}
			} );
		},
		getClickedBtn(){
			return mw.ranking.$btns.filter('.selected');
		},
		setMessageSuccess: function ( voteType ) {
			console.log($('.ranking-section'));
			$('.ranking-section-wrapper').addClass('voted').addClass( voteType ? 'voted-positive' : 'voted-negative');
			$('.voting-messages').addClass('show').removeClass('voting-messages-wrp-failure').addClass('voting-messages-wrp-success');
		},
		setMessageFailure: function () {
			$('.voting-messages').addClass('show').removeClass('voting-messages-wrp-success').addClass('voting-messages-wrp-failure');
		},
		resetButtons: function () {
			mw.ranking.$btns.attr( 'disabled', false ).removeClass( 'selected on-call' );
		},
		informFailedVote: function () {
			mw.ranking.resetButtons();
			mw.ranking.setMessageFailure();
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

	$( document ).ready( function () {
		$( mw.ranking.$btns ).on( 'click', function () {
			mw.ranking.positiveVote = $( this ).hasClass( 'yes' );
			mw.ranking.$btns.attr( 'disabled', true );
			//$( this ).prepend( mw.ranking.$statusIcon );
			$( this ).addClass( 'selected on-call' );
			if ( mw.ranking.config.isCaptchaEnabled === true ) {
				grecaptcha.execute();
			} else {
				mw.ranking.vote();
			}
		} );

	} );

	window.verifyRankingCaptcha = mw.ranking.verifyCaptcha;
	window.handleRankingCaptchaError = mw.ranking.informFailedVote;

}( mediaWiki, jQuery ) );
