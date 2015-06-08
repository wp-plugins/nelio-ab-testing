/* ALTERNATIVES TABLE */
var NelioABAltTable = {

	lastId: 0,
	alts: [],

	init: function(alts) {

		// PREPARE THE ALTERNATIVE TABLE
		var templateRow = NelioABAltTable.getTable().find('tr').last();
		templateRow.hide();
		templateRow.addClass('template');
		// We add the deleted class so that this row does not count when repainting the table
		templateRow.addClass('deleted');

		NelioABAltTable.alts = alts;
		for ( var i = 0; i < alts.length; ++i ) {
			var a = alts[i];
			if ( a.id < NelioABAltTable.id )
				NelioABAltTable.id = a.id;
		}

		// Controlling the NEW ALT FORM
		var table = jQuery('#alt-table');
		table.on( 'new-form-shown', function( ev, editor ) {
			NelioABAdminTable.hideInlineEdit( table );
			var oriRow = editor.prev();
			var name = oriRow.find('.alt-name').text();
			editor.find('#qe_alt_name').attr('value', name);
			editor.find('label').removeClass('error');
			editor.find('.save').addClass('disabled');
			NelioABEditExperiment.manageProgress(false,false);
		});
		table.on( 'new-form-hidden', function( ev, editor ) {
			if ( NelioABEditExperiment.validateCurrentTab() )
				NelioABEditExperiment.manageProgress(true,true);
		});

		// Controlling the INLINE EDIT FORM
		table.on( 'inline-edit-shown', function( ev, editor ) {
			NelioABAltTable.hideNewAltForm( table );
			var oriRow = editor.prev();
			var name = oriRow.find('.alt-name').text();
			editor.find('#qe_alt_name').attr('value', name);
			editor.find('label').removeClass('error');
			editor.find('.save').addClass('disabled');
			NelioABEditExperiment.manageProgress(false,false);
		});
		table.on( 'inline-edit-hidden', function( ev, editor ) {
			var aux = NelioABEditExperiment.validateCurrentTab();
			NelioABEditExperiment.manageProgress(aux[0],aux[1]);
		});

		// Save the experiment (and encode the alternatives)
		jQuery(document).on('save-experiment', function() {
			NelioABAltTable.save();
		});

		// Saving forms
		table.find('.nelioab-quick-edit-row .button.save').on('clicked', function() {
			NelioABAltTable.saveQuickEdit();
		});

		// Validate INPUT fields
		jQuery('#qe_alt_name').on( 'keyup focusout', function() {
			var row = jQuery(this).closest('tr').prev();
			var newName =  jQuery('#qe_alt_name').attr('value').trim();
			NelioABAltTable.validateName( row.data('alt-id'), newName );
		});
		jQuery('#new_alt_name').on( 'keyup focusout', function() {
			var row = jQuery(this).closest('tr').prev();
			var newName =  jQuery('#new_alt_name').attr('value').trim();
			NelioABAltTable.validateName( 0, newName );
		});
		jQuery(document).on( 'tab-changed', function( e, tabId ) {
			if ( tabId == 'tab-alts' )
				NelioABEditExperiment.validateCurrentTab = NelioABAltTable.validate;
		});

		// Other hooks
		table.on('row-removed', function() {
			var aux = NelioABEditExperiment.validateCurrentTab();
			NelioABEditExperiment.manageProgress(aux[0],aux[1]);
		});
	},

	getTable: function() {
		var table = jQuery('#the-list');
		if ( table.length > 0 )
			return jQuery('#the-list');
		else
			return false;
	},

	getAltById: function( id ) {
		var alts = NelioABAltTable.alts;
		for ( i = 0; i < alts.length; ++i )
			if ( alts[i].id == id )
				return alts[i];
		return false;
	},

	create: function() {
		var table = NelioABAltTable.getTable();

		var newName = jQuery('#new_alt_name').attr('value');
		var copyFrom = jQuery('#based_on').attr('value');
		if ( !NelioABAltTable.validateName( 0, newName ) )
			return;

		// Update model
		--NelioABAltTable.lastId;
		var alt = {
				id: NelioABAltTable.lastId,
				isNew: true,
				name: newName,
				base: copyFrom,
				wasDeleted: false,
			};
		NelioABAltTable.alts.push(alt);

		// Update view
		var newRow = NelioABAltTable.createRow(NelioABAltTable.lastId);
		newRow.find('.row-title').first().text(newName);
		NelioABAltTable.hideNewAltForm(NelioABAltTable.getTable());
		newRow.show();
		NelioABAdminTable.repaint( NelioABAltTable.getTable() );
	},

	createRow: function( id ) {
		var table = NelioABAltTable.getTable();
		var newRow = table.find('tr.template').first().clone();
		newRow.removeClass('template');
		newRow.removeClass('deleted');
		newRow.data('alt-id', id);
		table.append(newRow);
		return newRow;
	},

	editContent: function(row) {
		// Edit the contents of this alternative
		var id = row.data('alt-id');
		jQuery('#content_to_edit').attr('value', id);
		NelioABEditExperiment.save('edit_alt_content');
	},

	remove: function(row) {
		// Update view
		row.addClass('deleted');
		row.css('display','none');
		NelioABAdminTable.repaint( NelioABAltTable.getTable() );

		// Update model
		var alt = NelioABAltTable.getAltById(row.data('alt-id'));
		alt.wasDeleted = true;

		row.closest('table').trigger('row-removed', [ row ] );
	},

	showNewPageOrPostAltForm: function(table, copyingContent) {
		if ( copyingContent ) {
			var aux = table.find('#based_on');
			var info = NelioABPostSearcher.getInfo( jQuery('#exp_original') );
			NelioABPostSearcher.doSetDefault( aux, info );
			table.find('.new-alt-form .copying-content').show();
		}
		else {
			table.find('.new-alt-form .copying-content').hide();
			table.find('#based_on').attr('value', -1 );
		}
		table.find('.new-alt-form').show();
		if ( table.find('#qe_alt_name').attr('value').trim() == 0 )
			table.find('.new-alt-form .button.save').addClass('disabled');
		table.trigger('new-form-shown', [ table.find( '.new-alt-form' ) ]);
	},

	hideNewAltForm: function(table) {
		table.find('.new-alt-form').hide();
		table.find('#new_alt_name').attr('value','');
		table.trigger('new-form-hidden', [ table.find( '.new-alt-form' ) ]);
	},

	rename: function() {
		var inlineEditor = jQuery('#alt-table .nelioab-quick-edit-row');
		if ( inlineEditor.find('a.button-primary').hasClass('disabled') )
			return;

		var row = inlineEditor.prev();
		var newName =  jQuery('#qe_alt_name').attr('value').trim();

		// Update model
		var alt = NelioABAltTable.getAltById(row.data('alt-id'));
		alt.name = newName.trim();
		alt.isDirty = true;

		// Update view
		row.find('.row-title').first().text( newName );
	},

	validate: function() {
		var prevOk = true;
		var nextOk = true;

		if ( jQuery('#alt-table .nelioab-quick-edit-row').css('display') != 'none' )
			prevOk = nextOk = false;

		if ( jQuery('#alt-table .new-alt-form').css('display') != 'none' )
			prevOk = nextOk = false;

		if ( !NelioABAltTable.validateNumOfAlternatives() )
			nextOk = false;

		return [prevOk, nextOk];
	},

	validateNumOfAlternatives: function() {
		var numOfAlts = 0;
		for ( var i = 0; i < NelioABAltTable.alts.length; ++i ) {
			var a = NelioABAltTable.alts[i];
			if ( !a.wasDeleted )
				++numOfAlts;
		}
		if ( numOfAlts == 0 )
			return false;
		return true;
	},

	validateName: function( id, name ) {
		name = name.trim();
		var nameOk = true;
		if ( name.length <= 0 ) nameOk = false;
		var oldNames = NelioABAltTable.getTable().find('tr:not(.deleted)');
		for ( var i=1; i<oldNames.length && nameOk; ++i ) {
			var row = oldNames.eq(i);
			var otherId = row.data('alt-id');
			if ( otherId == undefined || row.data('alt-id') == id ) continue;
			if ( row.find('.alt-name').text() == name ) nameOk = false;
		}

		var inlineEditor = jQuery('#alt-table .nelioab-quick-edit-row');
		var newEditor = jQuery('#alt-table .new-alt-form');
		if ( !nameOk ) {
			// Inline Edit
			jQuery('#qe_alt_name').closest('label').addClass('error');
			inlineEditor.find('.button.save').addClass('disabled');
			// New Alt
			jQuery('#new_alt_name').closest('label').addClass('error');
			newEditor.find('.button.save').addClass('disabled');
		}
		else {
			// Inline Edit
			jQuery('#qe_alt_name').closest('label').removeClass('error');
			inlineEditor.find('.save').removeClass('disabled');
			// New Alt
			jQuery('#new_alt_name').closest('label').removeClass('error');
			newEditor.find('.button.save').removeClass('disabled');
		}
		return nameOk;
	},

	saveQuickEdit: function() {
		NelioABAltTable.rename();
	},

	save: function() {
		jQuery('#nelioab_alternatives').attr('value',
			encodeURIComponent( JSON.stringify( NelioABAltTable.alts ) )
				.replace( /'/g, "%27") );
	},

};

