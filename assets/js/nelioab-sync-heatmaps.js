jQuery.ajax( {
	type:  'POST',
	async: true,
	url:   NelioABHMSync.ajaxurl,
	data:  {
		action: 'nelioab_sync_heatmaps',
		current_url: document.URL
	}
} );
