jQuery.fn.extend({
	getFullPath: function () {
		var path = '';
		var elem = this;
		while ( !elem.is('html') ) {
			var node  = elem.get(0);
			var name  = node.nodeName.toLowerCase();
			var id    = elem.attr('id');
			var clazz = elem.attr('class');
			if ( typeof id != 'undefined' )    name += '#' + id;
			if ( typeof clazz != 'undefined' ) name += '.' + clazz.split(/[\s\n]+/).join('.');
			var siblings = elem.parent().children(name);
			if (siblings.length > 1) name += ':eq(' + siblings.index(node) + ')';
			path = '>' + name + path;
			elem = elem.parent();
		}
		return 'html' + path;
	}
});
jQuery(document).ready(function() {
   jQuery('body').click(function(e) {
      var target = e.target;
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
