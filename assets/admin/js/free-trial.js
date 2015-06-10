NelioABFreeTrial = {


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	$basicInfoAction: jQuery( '#nelio-ft-basic-info' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	$siteInfoAction: jQuery( '#nelio-ft-site-info' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	$goalsAction: jQuery( '#nelio-ft-goals' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	$tweetAction: jQuery( '#nelio-ft-tweet' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	$connectAction: jQuery( '#nelio-ft-connect' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var RegExp
	 */
	$recommendAction: jQuery( '#nelio-ft-recommend' ),


	/**
	 * JAVADOC
	 *
	 * @since 4.1.3
	 * @var object
	 */
	mailValidator: /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i,


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	init: function() {
		jQuery( '.free-trial-action select, .free-trial-action input' ).prop( 'disabled', true );
		jQuery( '.cta a.button' ).click(function() {
			var action = jQuery( this ).parent().data( 'action' );
			if ( jQuery.inArray( action, [ 'tweet', 'connect' ] ) >= 0 ) {
				return;
			}
			NelioABFreeTrial.performAction( action );
		});
		this.configureBasicInfoAction();
	},


	/**
	 * JAVADOC
	 *
	 * @param object $action JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	markAsDisabled: function( $action ) {
		$action.removeClass( 'action-completed' );
		$action.addClass( 'action-disabled' );
		$action.addClass( 'no-shadow' );
		$action.find( '.cta a.button' ).addClass( 'disabled' );
	},


	/**
	 * JAVADOC
	 *
	 * @param object $action JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	markAsAvailableIfPossible: function( $action ) {
		if ( $action.hasClass( 'action-completed' ) )
			return;
		$action.removeClass( 'action-disabled' );
		$action.removeClass( 'no-shadow' );
		$action.find( 'select,input' ).prop( 'disabled', false );
	},


	/**
	 * JAVADOC
	 *
	 * @param object $action JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	markAsCompleted: function( $action ) {
		$action.removeClass( 'action-disabled' );
		$action.addClass( 'action-completed' );
		$action.addClass( 'no-shadow' );
		$action.find( '.cta a.button' ).addClass( 'disabled' );
		$action.find( 'select,input' ).prop( 'disabled', true );
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureOtherCards: function() {
		// Disable all fields
		jQuery( '.free-trial-action' ).find( 'input,select' ).each( function() {
			jQuery(this).prop( 'disabled', false );
		});

		this.configureSiteInfoAction();
		this.configureGoalsAction();
		this.configureTweetAction();
		this.configureConnectAction();
		this.configureRecommendAction();

		jQuery( '.free-trial-action.action-disabled,.free-trial-action.action-completed' ).
			find( 'input,select' ).each( function() {
				jQuery(this).prop( 'disabled', true );
			});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureBasicInfoAction: function() {
		var $action = this.$basicInfoAction;
		var $cta = $action.find( '.cta a.button' );

		if ( $action.hasClass( 'action-disabled' ) ) {
			this.markAsAvailableIfPossible( $action );
		}
		else if ( $action.hasClass( 'pending-confirmation' ) ) {
			this.markAsAvailableIfPossible( $action );
			$cta.addClass( 'disabled' );
			jQuery.ajax({
				url:    ajaxurl,
				async:  true,
				method: 'POST',
				data: {
					action: 'nelioab_free_trial_promo',
					promo:  'basic-info-check'
				},
				success: function( res ) {
					$cta.removeClass( 'disabled' );
					if ( 'OK' === res ) {
						$action.removeClass( 'pending-confirmation' );
						NelioABFreeTrial.markAsCompleted( $action );
						NelioABFreeTrial.configureOtherCards();
					}
				},
				error: function() {
					$cta.removeClass( 'disabled' );
					NelioABFreeTrial.configureOtherCards();
				}
			});
		}
		else {
			this.configureOtherCards();
		}

		var $nameField = jQuery( '#your-name' );
		var validateName = function() {
			var str = $nameField.attr( 'value' );
			if ( typeof str === 'undefined' )
				return false;
			if ( str.length === 0 )
				return false;
			return true;
		};

		$nameField.on( 'change keyup', function() {
			if ( !validateName() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateMail() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

		var $mailField = jQuery( '#your-email' );
		var validateMail = function() {
			var str = $mailField.attr( 'value' );
			if ( typeof str === 'undefined' )
				return false;
			if ( str.length === 0 )
				return false;
			return NelioABFreeTrial.mailValidator.test( str );
		};

		$mailField.on( 'change keyup', function() {
			if ( !validateMail() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateName() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureSiteInfoAction: function() {
		var $action = this.$siteInfoAction;
		NelioABFreeTrial.markAsAvailableIfPossible( $action );
		if ( $action.hasClass( 'action-completed' ) )
			return;

		var $cta = $action.find( '.cta a.button' );

		var $typeField = jQuery( '#business-type-selector' );
		var validateType = function() {
			return $typeField.attr( 'value' ) != 'unknown';
		};

		$typeField.on( 'change', function() {
			if ( !validateType() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateSector() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

		var $sectorField = jQuery( '#business-sector-selector' );
		var validateSector = function() {
			return $sectorField.attr( 'value' ) != 'unknown';
		};

		$sectorField.on( 'change', function() {
			if ( !validateSector() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateType() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureGoalsAction: function() {
		var $action = this.$goalsAction;
		NelioABFreeTrial.markAsAvailableIfPossible( $action );
		if ( $action.hasClass( 'action-completed' ) )
			return;

		var $cta = $action.find( '.cta a.button' );

		var $goalField = jQuery( '#the-goal' );
		var validateGoal = function() {
			var str = $goalField.attr( 'value' );
			if ( typeof str === 'undefined' )
				return false;
			if ( str.length < 5 )
				return false;
			return true;
		};

		$goalField.on( 'change keyup', function() {
			if ( !validateGoal() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateSuccess() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

		var $successField = jQuery( '#success' );
		var validateSuccess = function() {
			var str = $successField.attr( 'value' );
			if ( typeof str === 'undefined' )
				return false;
			if ( str.length < 5 )
				return false;
			return true;
		};

		$successField.on( 'change keyup', function() {
			if ( !validateSuccess() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				if ( validateGoal() ) {
					$cta.removeClass( 'disabled' );
				} else {
					$cta.addClass( 'disabled' );
				}
			}
		});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureTweetAction: function() {
		var $action = this.$tweetAction;
		NelioABFreeTrial.markAsAvailableIfPossible( $action );
		if ( $action.hasClass( 'action-completed' ) )
			return;

		twttr.ready( function () {
			twttr.events.bind(
				'tweet',
				function (event) {
					NelioABFreeTrial.tweet();
				}
			);
		});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureConnectAction: function() {
		jQuery( document ).ready(function() {
			var $action = NelioABFreeTrial.$connectAction;
			NelioABFreeTrial.markAsAvailableIfPossible( $action );
			if ( $action.hasClass( 'action-completed' ) )
				return;

			var $dialog = jQuery( '#dialog-modal' );
			var $cta = $action.find( '.cta a' );
			$cta.removeClass( 'disabled' );

			$dialog.dialog({
				title: 'Facebook',
				dialogClass: 'wp-dialog',
				modal: true,
				autoOpen: false,
				width: 460,
				height: 250,
				closeOnEscape: true
			});

			$cta.click(function() {
				$dialog.dialog( 'open' );
				return false;
			});
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	configureRecommendAction: function() {
		var $action = this.$recommendAction;
		NelioABFreeTrial.markAsAvailableIfPossible( $action );
		if ( $action.hasClass( 'action-completed' ) )
			return;

		var $cta = $action.find( '.cta a.button' );

		var $listOfMailsField = jQuery( '#list-of-emails' );
		var validateListOfMails = function() {
			var str = $listOfMailsField.attr( 'value' );
			if ( typeof str === 'undefined' )
				return false;
			var mails;
			str = str.replace( /\s/g, '' );
			str = str.replace( /,,+/g, ',' );
			if ( str.indexOf( ',' ) === -1 )
				mails = [ str ];
			else
				mails = str.split( ',' );
			for ( var i = 0; i < mails.length; ++i ) {
				if ( ! NelioABFreeTrial.mailValidator.test( mails[i] ) ) {
					return false;
				}
			}
			return true;
		};

		$listOfMailsField.on( 'change keyup', function() {
			if ( !validateListOfMails() ) {
				jQuery(this).addClass( 'error' );
				$cta.addClass( 'disabled' );
			} else {
				jQuery(this).removeClass( 'error' );
				$cta.removeClass( 'disabled' );
			}
		});

	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	performAction: function( action ) {
		var $cta = jQuery( '#nelio-ft-' + action + ' .cta a.button' );
		if ( $cta.hasClass( 'disabled' ) ) {
			return;
		}
		else if ( 'basic-info' === action ) {
			this.submitBasicInformation();
		}
		else if ( 'goals' === action ) {
			this.submitGoals();
		}
		else if ( 'site-info' === action ) {
			this.submitSiteInformation();
		}
		else if ( 'recommend' === action ) {
			this.submitFriendList();
		}
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	submitBasicInformation: function() {
		var $action = this.$basicInfoAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );

		var name = jQuery( '#your-name' ).attr( 'value' );
		name = name.replace( /\s+/g, ' ' );
		name = name.replace( /^\s+/g, '' );
		name = name.replace( /\s+$/g, '' );

		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action: 'nelioab_free_trial_promo',
				promo:  'basic-info',
				name:   name,
				email:  jQuery( '#your-email' ).attr( 'value' )
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					$cta.addClass( 'disabled' );
					if ( $action.hasClass( 'pending-confirmation' ) ) {
						var regularIcon = $action.find( '.nelio-freetrial-heading img' );
						var completedIcon = $action.find( '.nelio-freetrial-heading.completed img' );
						completedIcon.attr( 'src', regularIcon.attr( 'src' ) );
					}
					$dialog = jQuery( '#email-confirmation-dialog' );
					if ( ! $dialog.hasClass( 'ui-dialog-content' ) ) {
						$dialog.dialog({
							title: $dialog.find( '.title' ).text(),
							dialogClass: 'wp-dialog',
							modal: true,
							autoOpen: false,
							width: 460,
							height: 250,
							closeOnEscape: true,
							buttons: [
								{
									text: $dialog.find( '.button' ).text(),
									click: function() {
										jQuery(this).dialog( 'close' );
									}
								}
							]
						});
					}
					if ( name.indexOf( ' ' ) > 0 ) {
						name = name.substring( 0, name.indexOf( ' ' ) );
					}
					$dialog.html( $dialog.html().replace( '%s', name ) );
					$dialog.dialog( 'open' );
					NelioABFreeTrial.markAsCompleted( $action );
					$action.addClass( 'pending-confirmation' );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
			}
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	submitGoals: function() {
		var $action = this.$goalsAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );
		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action:  'nelioab_free_trial_promo',
				promo:   'goals',
				goal:    jQuery( '#the-goal' ).attr( 'value' ),
				success: jQuery( '#success' ).attr( 'value' )
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					NelioABFreeTrial.markAsCompleted( $action );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
				NelioABFreeTrial.markAsAvailableIfPossible( $action );
			}
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	submitSiteInformation: function() {
		var $action = this.$siteInfoAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );
		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action: 'nelioab_free_trial_promo',
				promo:  'site-info',
				type:   jQuery( '#business-type-selector' ).attr( 'value' ),
				sector: jQuery( '#business-sector-selector' ).attr( 'value' )
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					NelioABFreeTrial.markAsCompleted( $action );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
				NelioABFreeTrial.markAsAvailableIfPossible( $action );
			}
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	tweet: function() {
		var $action = this.$tweetAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );

		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action: 'nelioab_free_trial_promo',
				promo:  'tweet',
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					NelioABFreeTrial.markAsCompleted( $action );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
				NelioABFreeTrial.markAsAvailableIfPossible( $action );
			}
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	connect: function() {
		var $action = NelioABFreeTrial.$connectAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );

		jQuery( '#dialog-modal' ).dialog( 'close' );

		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action: 'nelioab_free_trial_promo',
				promo:  'connect',
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					NelioABFreeTrial.markAsCompleted( $action );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
				NelioABFreeTrial.markAsAvailableIfPossible( $action );
			}
		});
	},


	/**
	 * JAVADOC
	 *
	 * @return void JAVADOC
	 *
	 * @since 4.1.3
	 */
	submitFriendList: function() {
		var $action = this.$recommendAction;
		var $cta = $action.find( '.cta a.button' );
		$cta.addClass( 'disabled' );

		var mails;
		var str = jQuery( '#list-of-emails' ).attr( 'value' );
		str = str.replace( /\s/g, '' );
		str = str.replace( /,,+/g, ',' );
		if ( str.indexOf( ',' ) === -1 )
			mails = [ str ];
		else
			mails = str.split( ',' );

		mails = mails.join( ',' );

		jQuery.ajax({
			url:    ajaxurl,
			async:  true,
			method: 'POST',
			data: {
				action: 'nelioab_free_trial_promo',
				promo:  'recommend',
				value:  mails
			},
			success: function( res ) {
				$cta.removeClass( 'disabled' );
				if ( 'OK' === res ) {
					NelioABFreeTrial.markAsCompleted( $action );
				}
			},
			error: function() {
				$cta.removeClass( 'disabled' );
				NelioABFreeTrial.markAsAvailableIfPossible( $action );
			}
		});
	}

};


NelioABFreeTrial.init();
