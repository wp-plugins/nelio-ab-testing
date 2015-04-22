/* ALTERNATIVES TABLE */
if ( typeof NelioABAltTable !== 'undefined' ) {

	NelioABAltTable.NO_IMAGE = NelioABAltTableParams.noImageSrc;

	NelioABAltTable.originalInfo = {
		id: 0,
		name: '',
		imageSrc: NelioABAltTable.NO_IMAGE,
	};

	NelioABAltTable.init = function(alts) {

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

		// Opening Media Selector when clicking feat img
		jQuery('div.headline_image_hover').on('click', function() {
			NelioABAltTable.changeImage();
		});

		// Making sure we use the latest info available
		var changeOriginalInfo = function() {
			NelioABAltTable.originalInfo.id = jQuery(this).attr('value');
			var alt = false;
			var arr = jQuery(this).data('last-search');
			if ( typeof arr == 'undefined' )
				return;
			for ( var i = 0; i < arr.length; ++i ) {
				var aux = arr[i];
				if ( aux.id == NelioABAltTable.originalInfo.id ) {
					alt = aux;
					break;
				}
			}
			if ( alt != false ) {
				var image = alt.thumbnail.replace( /^.*src="([^"]+)".*$/, '$1' );
				if ( image.indexOf( 'data:image/gif;base64' ) == 0 )
					image = NelioABAltTable.NO_IMAGE;
				NelioABAltTable.originalInfo.name = alt.title;
				NelioABAltTable.originalInfo.imageSrc = image;
			}
			jQuery('#original-headline-row').text(NelioABAltTable.originalInfo.name);
			jQuery('#original-headline-row').closest('tr').find('.feat-image').
				css( 'background-image', 'url(\'' + NelioABAltTable.originalInfo.imageSrc + '\')');
		};
		jQuery('#exp_original').on('change', changeOriginalInfo);
		jQuery('#exp_original').on('default-value-loaded', changeOriginalInfo);

		// Controlling the NEW ALT FORM
		var table = jQuery('#alt-table');
		table.on( 'new-form-shown', function( ev, editor ) {
			NelioABAdminTable.hideInlineEdit( table );
			jQuery('.headline_title').closest('label').removeClass('error');
			var oriRow = editor.prev();
			editor.find('.headline_title').attr('value', '');
			NelioABAltTable.lastImageSelected.id = 'inherit';
			NelioABAltTable.lastImageSelected.src = NelioABAltTable.originalInfo.imageSrc;
			editor.find('img.headline_image').css('background-image',
				'url(\'' + NelioABAltTable.originalInfo.imageSrc + '\')');
			editor.find('.headline_excerpt').attr('value','');
			editor.find('.save').addClass('disabled');
			NelioABEditExperiment.manageProgress(false,false);
		});
		table.on( 'new-form-hidden', function( ev, editor ) {
			if ( NelioABEditExperiment.validateCurrentTab() )
				NelioABEditExperiment.manageProgress(true,true);
		});

		// Controlling the INLINE EDIT FORM
		table.on( 'inline-edit-shown', function( ev, editor ) {
			var row = editor.prev();
			var altId = row.data('alt-id');
			var alt = NelioABAltTable.getAltById( altId );
			if ( alt === false )
				return;
			NelioABAltTable.hideNewAltForm( table );
			jQuery('.headline_title').closest('label').removeClass('error');
			editor.find('.headline_title').attr('value', alt.name);
			NelioABAltTable.lastImageSelected.id = alt.imageId;
			NelioABAltTable.lastImageSelected.src = alt.imageSrc;
			editor.find('img.headline_image').css('background-image', 'url(\'' + alt.imageSrc + '\')');
			editor.find('.headline_excerpt').attr('value', alt.excerpt);
			editor.find('.save').removeClass('disabled');
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
		jQuery('.headline_title').on( 'keyup focusout', function() {
			var newName =  jQuery(this).attr('value').trim();
			NelioABAltTable.validateName( newName);
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
	};

	NelioABAltTable.validateName = function(name) {
		name = name.trim();
		var nameOk = true;
		if ( name.length <= 0 ) nameOk = false;

		var inlineEditor = jQuery('#alt-table .nelioab-quick-edit-row');
		var newEditor = jQuery('#alt-table .new-alt-form');
		if ( !nameOk ) {
			jQuery('.headline_title').closest('label').addClass('error');
			inlineEditor.find('.button.save').addClass('disabled');
			newEditor.find('.button.save').addClass('disabled');
		}
		else {
			jQuery('.headline_title').closest('label').removeClass('error');
			inlineEditor.find('.save').removeClass('disabled');
			newEditor.find('.button.save').removeClass('disabled');
		}

		return nameOk;
	};

	NelioABAltTable.create = function() {

		var newName  = jQuery('.headline_title').attr('value').trim();
		if ( !NelioABAltTable.validateName(newName) )
			return;

		var excerpt  = jQuery('.headline_excerpt').attr('value').trim();

		// Update model
		--NelioABAltTable.lastId;
		var alt = {
				id: NelioABAltTable.lastId,
				isNew: true,
				name: newName,
				imageId: NelioABAltTable.lastImageSelected.id,
				imageSrc: NelioABAltTable.lastImageSelected.src,
				excerpt: excerpt,
				wasDeleted: false,
			};
		NelioABAltTable.alts.push(alt);

		// Update view
		var newRow = NelioABAltTable.createRow(NelioABAltTable.lastId);
		newRow.find('.row-title').first().text(newName);
		newRow.find('img.feat-image').css('background-image', 'url(\'' + alt.imageSrc + '\')');
		NelioABAltTable.hideNewAltForm(NelioABAltTable.getTable());
		newRow.show();
		NelioABAdminTable.repaint( NelioABAltTable.getTable() );
	};

	NelioABAltTable.editContent = undefined;
	NelioABAltTable.rename = undefined;
	NelioABAltTable.showNewPageOrPostAltForm = undefined;

	NelioABAltTable.showNewHeadlineForm = function( table ) {
		if ( 'none' !== table.find('.new-alt-form').css('display') )
			return;
		table.find('.new-alt-form').show();
		if ( table.find('.headline_title').attr('value').trim() == 0 )
			table.find('.new-alt-form .button.save').addClass('disabled');
		table.trigger('new-form-shown', [ table.find( '.new-alt-form' ) ]);
		// Setting first values
		var info = NelioABPostSearcher.getInfo( jQuery('#exp_original') );
		jQuery('tr.new-alt-form .headline_title').val(info.title);
		jQuery('tr.new-alt-form .headline_excerpt').val(info.excerpt);
	};

	NelioABAltTable.saveQuickEdit = function() {
		var inlineEditor = jQuery('#alt-table .nelioab-quick-edit-row');
		if ( inlineEditor.find('a.button-primary').hasClass('disabled') )
			return;

		var row = inlineEditor.prev();
		var newName  = inlineEditor.find('.headline_title').attr('value').trim();
		var excerpt  = inlineEditor.find('.headline_excerpt').attr('value').trim();

		var row = inlineEditor.prev();
		// Update model
		var alt = NelioABAltTable.getAltById(row.data('alt-id'));
		alt.name = newName;
		alt.excerpt = excerpt;
		alt.imageId = NelioABAltTable.lastImageSelected.id,
		alt.imageSrc = NelioABAltTable.lastImageSelected.src,
		alt.isDirty = true;

		// Update view
		row.find('.row-title').first().text(newName );
		row.find('img.feat-image').css('background-image', 'url(\'' + alt.imageSrc + '\')');
	};

	NelioABAltTable.lastImageSelected = { id:0, src:'' };
	NelioABAltTable.imageSelectorFrame = undefined;

	NelioABAltTable.changeImage = function() {
		// If the media frame already exists, reopen it.
		if ( typeof NelioABAltTable.imageSelectorFrame != 'undefined' ) {
			NelioABAltTable.imageSelectorFrame.open();
			return;
		}

		// Create the media frame.
		NelioABAltTable.imageSelectorFrame =
			wp.media.frames.file_frame = wp.media({
				title: jQuery( this ).data( 'uploader_title' ),
				button: {
					text: jQuery( this ).data( 'uploader_button_text' ),
				},
				multiple: false
			});

		// When an image is selected, run a callback.
		NelioABAltTable.imageSelectorFrame.on( 'select', function() {
			var attachment = NelioABAltTable.imageSelectorFrame.state().get('selection').first().toJSON();
			NelioABAltTable.lastImageSelected.id = attachment.id;
			NelioABAltTable.lastImageSelected.src = attachment.url;
			jQuery('img.headline_image').css('background-image', 'url(\'' + attachment.url + '\')');
		});

		// Finally, open the modal
		NelioABAltTable.imageSelectorFrame.open();
	};

}

