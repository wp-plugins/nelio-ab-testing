/* CONTROLLING PAGINATION */
(function($) {

	/* PREVIOUS BUTTON */
	$( '#controllers .previous' ).click( function() {
		if ( $(this).hasClass( 'disabled' ) ) return;
		var prev = $( '#exp-tabs .nav-tab-active' ).prev();
		if ( prev.length > 0 )
			NelioABEditExperiment.useTab( prev.first().attr('id') );
	});

	/* NEXT BUTTON */
	$( '#controllers .next' ).click( function() {
		if ( $(this).hasClass( 'disabled' ) ) return;
		var next = $( '#exp-tabs .nav-tab-active' ).next();
		if ( next.length > 0 )
			NelioABEditExperiment.useTab( next.first().attr('id') );
	});

	/* SAVE BUTTON */
	$( '#controllers .save' ).click( function() {
		var aux = NelioABEditExperiment.validateCurrentTab();
		NelioABEditExperiment.manageProgress(aux[0],aux[1]);
		if ( $(this).hasClass( 'disabled' ) ) return;
		NelioABEditExperiment.save();
	});

})(jQuery);

var NelioABEditExperiment = {

	validateCurrentTab: false,

	init: function() {
		jQuery('#exp_id').attr('value', nelioabBasicInfo.id);
		jQuery('#exp_name').attr('value', nelioabBasicInfo.name);
		jQuery('#exp_descr').attr('value', nelioabBasicInfo.description);

		jQuery('input#exp_name').on( 'keyup focusout', function() {
			if ( NelioABEditExperiment.validateName() )
				NelioABEditExperiment.manageProgress(true,true);
			else
				NelioABEditExperiment.manageProgress(false,false);
		});

		jQuery(document).on( 'tab-changed', function( e, tabId ) {
			if ( tabId == 'tab-info' )
				NelioABEditExperiment.validateCurrentTab = NelioABEditExperiment.validate;
		});
	},

	/**
	 * This function activates the tab whose id is «id», but only if the
	 * tab is not disabled.
	 */
	useTab: function( id ) {
		var $ = jQuery;

		var ct = $( '#exp-tabs .nav-tab-active' );
		var nt = $( '#' + id );

		// If I can evaluate the current tab, I evaluate it
		if ( NelioABEditExperiment.validateCurrentTab !== false ) {
			var aux = NelioABEditExperiment.validateCurrentTab();
			NelioABEditExperiment.manageProgress(aux[0],aux[1]);
			if ( nt[0] == ct.prev()[0] && !aux[0] )
				return;
			if ( nt[0] == ct.next()[0] && !aux[1] )
				return;

			if ( nt.hasClass('disabled') )
				return;
		}

		var buttonsToHide = [];
		var buttonsToShow = [];
		if ( nt.prev().length == 0 )
			buttonsToHide.push( $( '#controllers .previous') );
		else
			buttonsToShow.push( $( '#controllers .previous') );
		if ( nt.next().length == 0 ) {
			buttonsToHide.push( $( '#controllers .next') );
			buttonsToShow.push( $( '#controllers .save') );
		}
		else {
			buttonsToHide.push( $( '#controllers .save') );
			buttonsToShow.push( $( '#controllers .next') );
		}

		// I'm just making sure that the current tab is visible
		if ( ct.attr('id') == id ) {
			var aux = id.replace( 'tab', 'content' );
			jQuery('#set-of-content-blocks > div').each(function() {
				if ( jQuery(this).attr('id') != aux )
					jQuery(this).hide();
			});
			jQuery('#'+aux).show();
			for ( var i = 0; i < buttonsToHide.length; ++i )
				buttonsToHide[i].hide();
			for ( var i = 0; i < buttonsToShow.length; ++i )
				buttonsToShow[i].show();
		}
		// ..or I animate to the new tab
		else {
			ct.removeClass( 'nav-tab-active' );
			nt.addClass( 'nav-tab-active' );
			$( '#' + ct.attr( 'id' ).replace( 'tab', 'content' ) ).fadeOut(200);
			$( '#controllers' ).fadeOut(200);
			$( '#' + nt.attr( 'id' ).replace( 'tab', 'content' ) ).delay(200).fadeIn(200);
			$( '#controllers' ).fadeIn(200);
			for ( var i = 0; i < buttonsToHide.length; ++i )
				buttonsToHide[i].delay(200).fadeOut(10);
			for ( var i = 0; i < buttonsToShow.length; ++i )
				buttonsToShow[i].delay(200).fadeIn(10);
		}

		NelioABEditExperiment.manageProgress(true,true);
		$( document ).trigger( 'tab-changed', [ id ] );
	},

	manageProgress: function(prev, next) {
		if ( prev ) {
			jQuery( '#controllers .previous' ).removeClass('disabled');
		}
		else {
			jQuery( '#controllers .previous' ).addClass('disabled');
		}

		if ( next ) {
			jQuery( '#controllers .next' ).removeClass('disabled');
			jQuery( '#controllers .save' ).removeClass('disabled');
		}
		else {
			jQuery( '#controllers .next' ).addClass('disabled');
			jQuery( '#controllers .save' ).addClass('disabled');
		}
	},

	previewOriginal: function() {
		var p = jQuery('#exp_original').attr('value');
		if (p != -1)
			window.open(NelioABHomeUrl + '/?p=' + p, '_blank');
	},

	save: function(action) {
		if ( action === undefined )
			action = 'save';
		jQuery(document).trigger('save-experiment');
		smoothTransitions();
		jQuery('.nelio-exp-form > #action').attr('value', action);
		jQuery.post(
			location.href,
			jQuery('.nelio-exp-form').serialize()
		).success(function(data) {
			data = jQuery.trim( data );
			if ( data.indexOf('[SUCCESS]') == 0) {
				location.href = data.replace('[SUCCESS]', '');
			}
			else {
				document.open();
				document.write(data);
				document.close();
			}
		});
	},

	validate: function() {
		var allOk = true;

		if ( !NelioABEditExperiment.validateName() )
			allOk = false;

		return [allOk,allOk];
	},

	validateName: function() {
		var elem = jQuery('#exp_name');
		var name = elem.attr('value').trim(); 
		var result = true;

		if ( name.length == 0 )
			result = false;

		for ( var i = 0; i < nelioabBasicInfo.otherNames.length; ++i ) {
			otherName = nelioabBasicInfo.otherNames[i].trim();
			if ( otherName == name ) result = false;
		}

		if ( result )
			elem.closest('tr').removeClass('error');
		else
			elem.closest('tr').addClass('error');

		return result;
	},

};


/* GOAL CARDS */
var NelioABGoalCards = {

	lastId: 0,
	goals: [],

	init: function() {
		if ( jQuery( '#tab-goals' ).length == 0 )
			return;

	   var goals = JSON.parse( decodeURIComponent(
				jQuery('#nelioab_goals').attr('value')
			) );

		NelioABGoalCards.goals = goals;
		for ( var i = 0; i < goals.length; ++i ) {
			var g = goals[i];
			if ( g.id < NelioABGoalCards.id )
				NelioABGoalCards.id = g.id;
		}
		jQuery('#exp_original').change(function() {
			NelioABGoalCards.getList('.neliocard').each(function() {
				NelioABGoalCards.recomputeVisibleActions(jQuery(this));
			});
		});

		// Create the main card
		var mainGoalCard;
		if ( goals.length > 0 ) {
			var mainGoal = goals[0];
			mainGoalCard = NelioABGoalCards.createCard( goals[0].id );
			mainGoalCard.find( '.name .value' ).text( goals[0].name );
			for ( var a = 0; a < mainGoal.actions.length; ++a ) {
				var action = mainGoal.actions[a];
				NelioABGoalCards.addAction( action, mainGoalCard );
			}
		}
		else {
			NelioABGoalCards.create();
			mainGoalCard = NelioABGoalCards.getList().find( '.nelio-card' ).first();
			NelioABGoalCards.rename(
				mainGoalCard.data('goal-id'),
				jQuery('defaultNameForMainGoal').text() );
		}
		mainGoalCard.find('h3 .isMain').show();
		mainGoalCard.find( '.name .row-actions .delete' ).remove();
		mainGoalCard.find( '.name .row-actions .sep' ).remove();
		mainGoalCard.find( '.form' ).hide();
		mainGoalCard.find( '.name' ).show();

		// Create the other cards
		for ( var i = 1; i < goals.length; ++i ) {
			var g = goals[i];
			var newCard = NelioABGoalCards.createCard( g.id )
			for ( var a = 0; a < g.actions.length; ++a ) {
				var action = g.actions[a];
				NelioABGoalCards.addAction( action, newCard );
			}
			newCard.find( '.name .value' ).first().text( g.name );
			newCard.find( '.form' ).hide();
			newCard.find( '.name' ).show();
			newCard.show();
		}

		jQuery(document).on( 'tab-changed', function( e, tabId ) {
			if ( tabId == 'tab-goals' )
				NelioABEditExperiment.validateCurrentTab = NelioABGoalCards.validate;
		});


		// Save the experiment (and encode the alternatives)
		jQuery(document).on('save-experiment', function() {
			NelioABGoalCards.save();
		});

		NelioABGoalCards.getList('.neliocard').each(function() {
			NelioABGoalCards.recomputeVisibleActions(jQuery(this));
		});

	},

	getList: function() {
		return jQuery('#goal-list');
	},

	getGoalById: function( id ) {
		var goals = NelioABGoalCards.goals;
		for ( var i = 0; i < goals.length; ++i )
			if ( goals[i].id == id )
				return goals[i];
		return false;
	},

	create: function() {
		var list = NelioABGoalCards.getList();
		var defaultName = jQuery('#goal-template span.name > .value').text();

		// Update model
		--NelioABGoalCards.lastId;
		var goal = {
				id: NelioABGoalCards.lastId,
				isNew: true,
				name: defaultName + ' (' + (-NelioABGoalCards.lastId) + ')',
				wasDeleted: false,
			};
		NelioABGoalCards.goals.push(goal);

		// Update view
		var newCard = NelioABGoalCards.createCard(NelioABGoalCards.lastId);
		newCard.find('span.name .value').text(goal.name);
		newCard.find('span.form').hide();
		newCard.find('span.name').show();
		newCard.show();
	},

	rename: function( id, name ) {
		var goal = NelioABGoalCards.getGoalById( id );
		if ( goal )
			goal.name = name;
	},

	createCard: function( id ) {
		var newCard = jQuery('#goal-template').clone();
		newCard.data('goal-id', id);
		newCard.removeAttr('id');

		// CARD NAME OPERATIONS
		newCard.find('h3 .form input.new-name').on( 'keyup focusout', function() {
			NelioABGoalCards.validateGoalName( newCard );
		});
		newCard.find('h3 .form .rename').click(function() {
			if ( !NelioABGoalCards.validateGoalName( newCard ) )
				return;
			var newName = newCard.find('.new-name').first().attr('value');
			NelioABGoalCards.rename(id, newName);
			newCard.find('span.name > .value').text(newName);
			newCard.find('span.form').hide();
			newCard.find('span.name').show();
		});
		newCard.find('h3 .name .rename a').click(function() {
			var oldName = newCard.find('span.name > .value').text();
			newCard.find('.new-name').first().attr('value', oldName);
			newCard.find('span.form').show();
			newCard.find('span.name').hide();
		});
		newCard.find('h3 .name .delete a').click(function() {
			NelioABGoalCards.remove(newCard);
		});

		// NEW GOAL ACTIONS
		newCard.find('.new-actions .page').click(function() {
			if ( jQuery(this).hasClass('disabled') )
				return;
			NelioABGoalCards.addAction( { isNew:true, type:'page' }, newCard );
		});
		newCard.find('.new-actions .post').click(function() {
			if ( jQuery(this).hasClass('disabled') )
				return;
			NelioABGoalCards.addAction( { isNew:true, type:'post' }, newCard );
		});
		newCard.find('.new-actions .external-page').click(function() {
			if ( jQuery(this).hasClass('disabled') )
				return;
			NelioABGoalCards.addAction( { isNew:true, type:'external-page' }, newCard );
		});

		var list = NelioABGoalCards.getList();
		list.append(newCard);
		return newCard;
	},

	remove: function(card) {
		// Update model
		var goal = NelioABGoalCards.getGoalById(card.data('goal-id'));
		goal.wasDeleted = true;

		// Update view
		card.remove();
	},

	addAction: function(action, card) {
		var result;

		switch ( action.type ) {

			case 'post':
			case 'page':
				result = card.find( '.new-' + action.type + '-action' ).clone();
				result.removeClass( 'new-' + action.type + '-action' );
				result.find('select.' + action.type).first().change( function() {
					NelioABGoalCards.recomputeVisibleActions(card);
				});
				if ( action.isNew !== true ) {
					if ( action.isIndirect )
						result.find('.direct').attr( 'value', '0' );
					else
						result.find('.direct').attr( 'value', '1' );
					result.find('.' + action.type).attr('value', action.value );
				}
				card.find( '.actions' ).append( result );
				card.find( '.empty' ).hide();
				card.find( '.actions' ).show();
				result.show();
				NelioABGoalCards.recomputeVisibleActions( card );
				break;

			case 'external-page':
				result = card.find( '.new-external-page-action' ).clone();
				result.removeClass( 'new-external-page-action' );
				if ( action.isNew !== true ) {
					result.find('.name').attr( 'value', action.name );
					result.find('.url').attr( 'value', action.url );
				}
				result.find('.name').on( 'keyup focusout', function() {
					NelioABGoalCards.validateExternalPage(result, 'name');
				});
				result.find('.url').on( 'keyup focusout', function() {
					NelioABGoalCards.validateExternalPage(result, 'url');
				});
				card.find( '.actions' ).append( result );
				card.find( '.empty' ).hide();
				card.find( '.actions' ).show();
				result.show();
				break;

			default:
				return false;

		}
		result.data( 'type', action.type );
		result.find( 'a.delete' ).click(function() {
			result.remove();
			if ( card.find( '.actions .action' ).length == 0 ) {
				card.find( '.actions' ).hide();
				card.find( '.empty' ).show();
			}
			NelioABEditExperiment.manageProgress(true,true);
			NelioABGoalCards.recomputeVisibleActions( card );
		});
		return result;
	},

	recomputeVisibleActions: function(card) {
		var types = [ 'post', 'page' ];
		var oriId = jQuery('#exp_original').attr('value');

		// Remove any goal whose target is the original page
		card.find('.actions .action select').each(function() {
			if ( jQuery(this).attr('value') == oriId )
				if ( jQuery(this).hasClass('post') || jQuery(this).hasClass('page') )
					jQuery(this).closest('.action').remove();
		});
		if ( card.find( '.actions .action' ).length == 0 ) {
			card.find( '.actions' ).hide();
			card.find( '.empty' ).show();
		}

		// Prepare the other visible actions
		for ( var t = 0; t < types.length; ++t ) {
			var type = types[t];
			var values = [ oriId ];

			// Recover selected values of valid actions and making all options
			// visible (in a few lines we'll hide the ones relevant)
			card.find('.new-action-templates select.' + type + ' option').show();
			card.find('.actions .action select.' + type).each(function() {
				jQuery(this).find('option').show();
				values.push( jQuery(this).attr('value') );
			});

			// Disable already selected values for existing actions and new action
			// (Note that .actions is not here is not in the selector)
			for ( var i = 0; i < values.length; ++i )
				card.find('.action select.' + type + ' option[value=' + values[i] + ']').hide();

			// Do not hide the selected value of each selector
			card.find('.actions .action select.' + type).each(function() {
				jQuery(this).find('option[value=' + jQuery(this).attr('value') + ']').show();
			});

			// Update default value for new action
			var soval = 0;
			var s = card.find('.new-action-templates select.' + type);
			s.find('option').each(function() {
				jQuery(this).removeAttr('selected');
				if ( jQuery(this).css('display') != 'none' && soval == 0) {
					jQuery(this).attr('selected','selected');
					soval = jQuery(this).attr('value');
				}
			});
			s.attr('value', soval);
			if ( soval == 0 )
				jQuery('.new-actions a.' + type).addClass('disabled');
			else
				jQuery('.new-actions a.' + type).removeClass('disabled');
		}
	},

	validate: function() {
		var prevOk = true;
		var nextOk = true;

		jQuery('#goal-list .nelio-card').each(function() {
			if ( jQuery(this).find('.form').css('display') != 'none' ) {
				prevOk = nextOk = false;
				jQuery(this).find('.new-name').first().addClass('error');
			}
		});

		jQuery('#goal-list .actions .external-page input').each(function() {
			var action = jQuery(this).closest('.action');
			if ( jQuery(this).hasClass('name') )
				nextOk = NelioABGoalCards.validateExternalPage(action, 'name') && nextOk;
			if ( jQuery(this).hasClass('url') )
				nextOk = NelioABGoalCards.validateExternalPage(action, 'url') && nextOk;
		});

		return [prevOk,nextOk];
	},

	validateGoalName: function(card) {
		var input = card.find('.new-name').first();
		var name = input.attr('value').trim();
		var result = true;

		if ( name.length == 0 )
			result = false;

		for ( var i = 0; i < NelioABGoalCards.goals.length; ++i ) {
			var g = NelioABGoalCards.goals[i];
			if ( g.wasDeleted )
				continue;
			if ( g.id == card.data('goal-id') )
				continue;
			otherName = g.name;
			if ( otherName == name ) result = false;
		}

		if ( result ) {
			input.removeClass('error');
			card.find('.form .button.rename').removeClass('disabled');
			NelioABEditExperiment.manageProgress(true,true);
		}
		else {
			input.addClass('error');
			card.find('.form .button.rename').addClass('disabled');
			NelioABEditExperiment.manageProgress(false,false);
		}

		return result;
	},

	validateExternalPage: function(action, fieldClass, preventRecursive) {
		if (preventRecursive == undefined) preventRecursive = false;
		var card = action.closest('.nelio-card');
		var input = action.find('input.' + fieldClass);
		var value = input.attr('value').trim();

		var result = true;
		if ( value.length == 0 )
			result = false;

		if ( result ) {
			card.find('.actions .action.external-page').each(function() {
				if ( action[0] != jQuery(this)[0] ) {
					var offendedAction = jQuery(this).find('input.' + fieldClass);
					var offendedActionValue = offendedAction.attr('value').trim();
					if ( offendedActionValue == value ){
						offendedAction.addClass('error');
						result = false;
					}
				}
			});

			if ( !preventRecursive ) {
				card.find('.actions .action.external-page input.error.' + fieldClass).each(function() {
					NelioABGoalCards.validateExternalPage(jQuery(this).closest('.action'), fieldClass, true);
				});
			}
		}

		if ( result ) {
			input.removeClass('error');
			NelioABEditExperiment.manageProgress(true,true);
		}
		else {
			input.addClass('error');
			NelioABEditExperiment.manageProgress(false,false);
		}

		return result;
	},

	save: function() {
		var cards = NelioABGoalCards.getList().find('.nelio-card');
		for ( var i = 0; i < NelioABGoalCards.goals.length; ++i ) {
			var g = NelioABGoalCards.goals[i];
			g.actions = [];
		}
		cards.each(function() {
			var card = jQuery(this);
			var g = NelioABGoalCards.getGoalById( card.data( 'goal-id' ) );
			g.name = card.find( '.name .value' ).text();
			var actions = card.find('.actions .action').each(function() {
				var action = jQuery(this);
				var a = {};
				a.type = action.data( 'type' );
				switch( action.data( 'type' ) ) {
					case 'page':
					case 'post':
						a.value = action.find( '.' + action.data( 'type' ) ).attr('value');
						a.isIndirect = action.find( '.direct' ).attr('value') == '0';
						g.actions.push(a);
						break;
					case 'external-page':
						a.name = action.find( '.name' ).attr('value');
						a.url = action.find( '.url' ).attr('value');
						g.actions.push(a);
						break;
				}
			});
		});
		jQuery('#nelioab_goals').attr('value', 
			encodeURIComponent( JSON.stringify( NelioABGoalCards.goals ) ) );
	},

};


/* INITIALIZING ALL VARIABLES */
(function($) {

	// PREPARING BASIC INFO
	NelioABEditExperiment.init();

	// PRINTING THE LIST OF GOALS
	NelioABGoalCards.init();

	// ACTIVATING DEFAULT TAB (OR OVERRIDING IF AVAILABLE ON PARAMS)
	var getParams = window.location.search.replace('?', '').split('&');
	var currentTab = jQuery( '#exp-tabs .nav-tab-active' ).attr( 'id' );
	for ( var i = 0; i < getParams.length; ++i ) {
		var start = getParams[i].indexOf( 'ctab' );
		if ( start === 0 ) {
			var aux = getParams[i].substring(getParams[i].indexOf('=')+1);
			if ( aux != 'tab-goals' && jQuery( '#' + aux ).length > 0 )
				currentTab = aux;
			break;
		}
	}
	jQuery( '#exp-tabs .nav-tab-active' ).removeClass('nav-tab-active');
	jQuery( '#' + currentTab ).addClass('nav-tab-active');

})(jQuery);

