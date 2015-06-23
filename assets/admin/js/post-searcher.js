var NelioABPostSearcher = {

	formatSearchResult: function(item, container, query, escapeMarkup) {
		var markup=[];
		window.Select2.util.markMatch(item.title, query.term, markup, escapeMarkup);
		var title = markup.join("");
		return '<div class="result-content">'+
					'<div class="result-image">'+item.thumbnail+"</div>"+
					'<div class="result-item">'+
						'<div class="result-title">'+title+"</div>"+
						'<div class="result-author">'+item.author+"</div>"+
						'<div class="result-date">'+item.date.toLocaleString()+"</div>"+
						'<div class="result-type">'+item.type+" ("+item.id+")</div>"+
						'<div class="result-status">'+item.status+"</div>"+
					'</div>'+
				'</div>';
	},

	formatSelectionResult: function(item, container) {
		container.data( "post-id", item.id );
		container.data( "excerpt", item.excerpt );
		return item.title;
	},

	buildSearcher: function(elem, type, drafts, filter) {
		elem.data('current-type', type);
		elem.select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				type:"POST",
				data: function (term) {
					var res = {
						term: term,
						action: "nelioab_post_searcher",
						type: elem.data('current-type'),
						drafts: drafts,
					};
					return res;
				},
				results: function (data) {
					if ( filter !== undefined )
						data = filter( elem, data );
					elem.data('last-search', data);
					return { results:data };
				}
			},
			formatResult: NelioABPostSearcher.formatSearchResult,
			formatSelection: NelioABPostSearcher.formatSelectionResult,
			dropdownAutoWidth: true,
			dropdownCssClass: "bigdrop",
			escapeMarkup: function (m) { return m; }
		});
		NelioABPostSearcher.setPlaceholder(elem);
	},

	getInfo: function(elem) {
		var chosen = elem.parent().find('.select2-chosen');
		if ( chosen.hasClass('select2-default') )
			return false;
		return { id:elem.attr('value'), value:elem.attr('value'), label:chosen.html(),
			title:chosen.text(), excerpt:chosen.data('excerpt') };
	},

	setDefault: function(elem, type, drafts) {
		jQuery.ajax( {
				url: ajaxurl,
				dataType: 'json',
				type:"POST",
				data: {
					action: "nelioab_post_searcher",
					type: type,
					drafts: drafts,
					default_id: elem.attr('value'),
				},
			}).done(function(data) {
				var item = data[0];
				var search = [];
				search.push( data[0] );
				elem.data('last-search', search);
				NelioABPostSearcher.doSetDefault( elem, item );
				elem.trigger('default-value-loaded');
			});
	},

	changeType: function(elem, newType) {
		elem.attr('value', -1);
		elem.data('current-type', newType);
		NelioABPostSearcher.setDefault(elem, newType, 'no-drafts');
	},

	doSetDefault: function( elem, item ) {
		elem.attr('value', item.id);
		var chosen = elem.parent().find('.select2-chosen');
		chosen.html(item.title);
		chosen.parent().removeClass('select2-default');
		NelioABPostSearcher.formatSelectionResult( item, chosen );
	},

	setPlaceholder: function(elem) {
		var placeholder = elem.data('placeholder');
		var chosen = elem.parent().find('.select2-chosen');
		chosen.html(placeholder);
		chosen.parent().addClass('select2-default');
	},

};

