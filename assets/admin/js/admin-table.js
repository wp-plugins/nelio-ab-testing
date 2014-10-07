var NelioABAdminTable = {

	showInlineEdit: function( row ) {
		var table = row.closest('table.wp-list-table');
		var editor = table.find('.nelioab-quick-edit-row');

		if ( editor.length != 1 )
			return false;

		NelioABAdminTable.hideInlineEdit( table );

		// Get the ROW and place the INLINE_EDIT after it
		editor.insertAfter(row);

		// Hide the ROW and show the INLINE_EDIT
		row.hide();
		editor.show();
		NelioABAdminTable.repaint( table );

		// Update the global var
		nelioabEditingItem = row;

		jQuery(table).trigger( 'inline-edit-shown', [ editor ] );
		return true;
	},


	hideInlineEdit: function( table ) {
		var editor = table.find('.nelioab-quick-edit-row');
		if ( editor.length != 1 )
			return false;

		// Hide the INLINE_EDIT and show the ROW
		if ( editor.css('display') != 'none' ) {
			editor.hide();
			editor.prev().show();
			NelioABAdminTable.repaint( table );
			jQuery(table).trigger( 'inline-edit-hidden', [ editor ] );
		}

		return true;
	},


	repaint: function( table ) {
		var alternate=false;
		table.find('tr').each(function(){
			var tr = jQuery(this);
			if ( tr.css('display') == 'none' ) return;
			tr.removeClass('alternate');
			if ( alternate )
				tr.addClass('alternate');
			alternate = !alternate;
		});
	},

};

