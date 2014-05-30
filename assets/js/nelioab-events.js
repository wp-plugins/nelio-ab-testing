jQuery(document).ready(function() {

	jQuery.fn.extend({
		getFullPath: function () {
			var path = '';
			var elem = this;
			while ( !elem.is('html') ) {
				var node  = elem.get(0);
				var name  = node.nodeName.toLowerCase();
				var id	 = elem.attr('id');
				var clazz = elem.attr('class');
				if ( !elem.is('body') ) {
					if ( typeof id != 'undefined' && id.length > 0 ) {
						name += '#' + id;
					}
					if ( typeof clazz != 'undefined' ) {
						clazz = jQuery.trim( clazz );
						if ( clazz.length > 0 )
							name += '.' + clazz.split(/[\s\n]+/).join('.');
					}
				}
				var siblings = elem.parent().children(name);
				if (siblings.length > 1) name += ':eq(' + siblings.index(node) + ')';
				path = '>' + name + path;
				elem = elem.parent();
			}
			return 'html' + path;
		}
	});

	jQuery('a').click(function(e) {
		var target = e.currentTarget;
		var dest = undefined;
		try { dest = target.href; } catch (e) {}
		if ( dest != undefined ) {
			e.type = 'byebye';
			jQuery(document).trigger( e, [ dest ] );
		}
	});

	jQuery(document).on('submit', 'form', function(e) {
		jQuery(document).trigger( 'byebye', [ jQuery(this).attr('action') ] );
	});

});
