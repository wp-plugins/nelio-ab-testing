var NelioABFormSearcher = {

	formatSearchResult: function(item, container, query, escapeMarkup) {
		var markup=[];
		window.Select2.util.markMatch(item.title, query.term, markup, escapeMarkup);
		var title = markup.join("");
		return  '<div class="result-content">'+
					'<div class="result-image">'+item.thumbnail+"</div>"+
					'<div class="result-item">'+
						'<div class="result-title">'+title+"</div>"+
						'<div class="result-type">'+item.type+"</div>"+
					'</div>'+
				'</div>';
	},

	formatSelectionResult: function(item, container) {
		container.data( "post-id", item.id );
		return item.title;
	},

	buildSearcher: function(elem, type, filter) {
		elem.select2({
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				type:"POST",
				data: function (term) {
					var res = {
						term: term,
						action: "nelioab_form_searcher",
						type: type,
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
			formatResult: NelioABFormSearcher.formatSearchResult,
			formatSelection: NelioABFormSearcher.formatSelectionResult,
			dropdownAutoWidth: true,
			dropdownCssClass: "bigdrop",
			escapeMarkup: function (m) { return m; }
		});
		NelioABFormSearcher.setPlaceholder(elem);
	},

	getInfo: function(elem) {
		var chosen = elem.parent().find('.select2-chosen');
		if ( chosen.hasClass('select2-default') )
			return false;
		return { value:elem.attr('value'), label:chosen.html() };
	},

	setDefault: function(elem, type) {
		jQuery.ajax( {
				url: ajaxurl,
				dataType: 'json',
				type:"POST",
				data: {
					action: "nelioab_form_searcher",
					type: type,
					default_id: elem.attr('value'),
				},
			}).done(function(data) {
				var item = data[0];
				var search = [];
				search.push( data[0] );
				elem.data('last-search', search);
				NelioABFormSearcher.doSetDefault( elem, item.title, item.id );
				elem.trigger('default-value-loaded');
			});
	},

	doSetDefault: function( elem, label, value ) {
		var chosen = elem.parent().find('.select2-chosen');
		chosen.html(label);
		chosen.parent().removeClass('select2-default');
		elem.attr('value', value);
	},

	setPlaceholder: function(elem) {
		var placeholder = elem.data('placeholder');
		var chosen = elem.parent().find('.select2-chosen');
		chosen.html(placeholder);
		chosen.parent().addClass('select2-default');
	},

};

