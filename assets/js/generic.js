NelioAB = {};

/**
 * Preparing the object NelioAB, which will hold all relevant
 * functions and information.
 */
NelioAB.backend = {};
NelioAB.backend.url = '//' + NelioABParams.backend.domain + '/v' + NelioABParams.backend.version;


/**
 * Adding a "trim" function to JavaScript Strings
 */
if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
  }
}

/**
 * Extending jQuery with a couple of new functions
 */
NelioAB.jquery = {};
NelioAB.jquery.extend = function() {
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
};
NelioAB.jquery.extend();


/**
 * Adding code for Google Tag Manager support
 */
(function() {
	var isGtmReady = false;
	var shouldGtmBeCalled = false;
	var isGtmCalled = false;

	function callGtmActivationFunction() {
		if ( isGtmReady && shouldGtmBeCalled && typeof nelioabActivateGoogleTagMgr == 'function' && !isGtmCalled ) {
			isGtmCalled = true;
			nelioabActivateGoogleTagMgr();
		}
	}

	jQuery(document).on('nelioab-gtm-ready', function() {
		isGtmReady = true;
		callGtmActivationFunction();
	});

	jQuery(document).on('nelioab-gtm-call', function() {
		shouldGtmBeCalled = true;
		callGtmActivationFunction();
	});

})();


/**
 * Adding new event "byebye" when we're about to leave the current page.
 */
jQuery(document).ready(function() {

	jQuery(document).click(function(e) {
		var target = jQuery(e.target).closest('a');
		var dest = undefined;
		try { dest = target.attr('href'); } catch (e) {}
		if ( dest != undefined ) {
			e.type = 'byebye';
			jQuery(document).trigger( e, [ target, dest ] );
		}
	});

	jQuery(document).on('submit', 'form', function(e) {
		jQuery(document).trigger( 'byebye', [ jQuery(this), jQuery(this).attr('action') ] );
	});

});

