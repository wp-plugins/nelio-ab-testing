function nelioab_init() {
	// Check if the user accepts cookies...
	if ( !nelioab_areCookiesEnabled() ) {
		jQuery(document).trigger('nelioab-gtm-call');
		return;
	}

	if ( nelioab_get_cookie_by_name( 'nelioab_is_in' ) == 'no' ) {
		jQuery(document).trigger('nelioab-gtm-call');
		return;
	}

	// Making sure that nothing is executed too soon
	// (when calling show_body, we'll release the holding)
	jQuery.holdReady( true );

	// Make the body invisible
	nelioab_hide_body();

	// Synchronize cookies
	nelioab_sync_cookies_and_load_alternative_if_required(jQuery);
}

var nelioab_styleNode;
function nelioab_hide_body() {
	nelioab_styleNode = document.createElement('style');
	nelioab_styleNode.setAttribute('type', 'text/css');
	var text = 'html{display:none !important;} body{display:none !important;}';
	if (nelioab_styleNode.styleSheet) {
		// IE
		nelioab_styleNode.styleSheet.cssText = '';
	} else {
		// Other browsers
		var textnode = document.createTextNode(text);
		nelioab_styleNode.appendChild(textnode);
	}
	document.getElementsByTagName('head')[0].appendChild(nelioab_styleNode);
}

function nelioab_show_body() {
	try {
		document.getElementsByTagName('head')[0].removeChild(nelioab_styleNode);
		jQuery.holdReady( false );
		jQuery(document).trigger('nelioab-gtm-call');
	}
	catch( e ) {}
}

function nelioab_sync_cookies_and_load_alternative_if_required($) {
	var are_cookies_sync = false;
	var is_load_alt_required = false;

	$.ajax({
		type:  'POST',
		async: false,
		url:   NelioABChecker.ajaxurl,
		data: {
			action: 'nelioab_sync_cookies_and_check',
			current_url: document.URL,
			nelioab_cookies: nelioab_get_local_cookies(),
		},
		success: function(data) {
			try {
				json = JSON.parse(data);
				cookies = json.cookies;
				if ( cookies['__nelioab_new_version'] != undefined )
					nelioab_clean_cookies();
				$.each(cookies, function(name, value) {
					if (nelioab_get_cookie_by_name(name) == undefined) {
						document.cookie = name + '=' + value + ';path=/';
					}
					else if (name == 'nelioab_was_in') {
						nelioab_delete_cookie('nelioab_was_in');
						document.cookie = name + '=' + value + ';path=/';
					}
				});
				nelioab_delete_cookie('__nelioab_new_version');
				are_cookies_sync = true;

				if ( nelioab_get_cookie_by_name( 'nelioab_is_in' ) == 'no' ) {
					nelioab_show_body();
					return;
				}

				is_load_alt_required = ( json.load_alt == 'LOAD_ALT' );
				if ( !is_load_alt_required )
					nelioab_nav($);

				if ( are_cookies_sync && is_load_alt_required ) {
					nelioab_load_alt(jQuery);
				}
				else {
					nelioab_show_body();
					jQuery(document).ready(function(){
						if ( typeof( nelioab_prepare_links_for_nav_to_external_pages ) == 'function' )
							nelioab_prepare_links_for_nav_to_external_pages(jQuery);
						if ( typeof( nelioab_add_hidden_fields_on_forms ) == 'function' )
							nelioab_add_hidden_fields_on_forms(jQuery);
						if ( typeof( nelioabStartHeatmapTracking ) == 'function' )
							nelioabStartHeatmapTracking();
					});
				}
			}
			catch(e) {
				nelioab_show_body();
			}
		},
		error: function() {
			nelioab_show_body();
		},
	});
}

function nelioabExtractParams(query) {
	var params = [];
	if ( query.length == 0 )
		return params;

	var match,
		pl     = /\+/g,  // Regex for replacing addition symbol with a space
		search = /([^&=]+)=?([^&]*)/g,
		decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
		query  = query.substring(1);

	while (match = search.exec(query))
		params.push( [decode(match[1]), decode(match[2])] );

	return params;
}

function nelioabMergeUrlParams( priority, inherit ) {
	// Extract URL params
	var uParams = [];
	aux = inherit.indexOf('?');
	if ( -1 != aux )
		uParams = nelioabExtractParams( inherit.substring(aux) );

	// Extrat PERMALINK params and set
	//  - stringParams to something like "?..."
	//  - priority to something like http://..../ without GET params
	var pParams = [];
	aux = priority.indexOf('?');
	if ( -1 != aux ) {
		pParams = nelioabExtractParams( priority.substring( aux ) );
		priority = priority.substring( 0, aux );
	}

	var newParams = [];
	for ( var i = 0; i < pParams.length; ++i )
		newParams.push( pParams[i] );

	for ( var i = 0; i < uParams.length; ++i ) {
		var isNew = true;
		for ( var j = 0; j < pParams.length; ++j ) {
			if ( uParams[i][0] == pParams[j][0] ) {
				isNew = false;
				break;
			}
		}
		if ( isNew )
			newParams.push( uParams[i] );
	}

	var stringParams = '';
	if ( newParams.length > 0 )
		stringParams = "?" + newParams[0][0] + "=" + encodeURIComponent(newParams[0][1]);
	for ( var i = 1; i < newParams.length; ++i )
		stringParams += "&" + newParams[i][0] + "=" + encodeURIComponent(newParams[i][1]);

	priority += stringParams;

	return priority;
}

function nelioab_load_alt($) {
	var aux;
	var url = document.URL;
	var permalink = NelioABChecker.permalink;

	permalink = nelioabMergeUrlParams( permalink, url );

	$.ajax({
		type:  'POST',
		async: false,
		url:   permalink,
		data: {
			nelioab_cookies:  nelioab_get_local_cookies(),
			nelioab_load_alt: 'true',
		},
		success: function(data) {
			if ( data.indexOf( 'nelio-ab-testing/assets/js/nelioab-check.min.js' ) != -1 ) {
				console.log( 'ERROR #1: nelioab checker script has been included again...' );
				nelioab_show_body();
				return;
			}
			// Removing jetpack stats scripts from the alternative
			data = data
				.replace(
					/<.cript src="https?:\/\/stats.(wordpress|wp).com\/e-([^\n]*)\n/g,
					'<!-- <scr'+'ipt src="http://stats.$1.com/e-$2 -->\n' +
					'\t<scr'+'ipt>function st_go(a){} function linktracker_init(a,b){}</scr'+'ipt>\n'
				);
			var docIsReady = function() {
				var aux = window.setTimeout(function() {}, 0);
				while (aux--) window.clearTimeout(aux);
				var aux = window.setInterval(function() {}, 20000) + 1;
				while (aux--) window.clearInterval(aux);
				window.onbeforeunload = window.onunload = false;
				if ( typeof document.open() === 'undefined' ) {
					console.log( 'WARNING #2: document.open is not working; trying to recover default functions...' );
					var doc = document.implementation.createHTMLDocument('');
					document.open = doc.open;
					document.write = doc.write;
					document.close = doc.close;
					document.open();
				}
				document.write(data);
				document.close();
			};
			if (document.addEventListener) {
				// For all major browsers, except IE 8 and earlier
				document.addEventListener('DOMContentLoaded',docIsReady);
			} else if (document.attachEvent) {
				// For IE 8 and earlier versions
				document.attachEvent('DOMContentLoaded',docIsReady);
			}
			else {
				nelioab_show_body();
			}

			// Manually calling `docIsReady` if the event DOMContentLoaded was already triggered
			if ( 'complete' == document.readyState || 'loaded' == document.readyState ) {
				docIsReady();
			}
		},
		error: function(data) {
			nelioab_show_body();
		}
	});
}

nelioab_init();
