NelioAB.checker = {};

NelioAB.checker.styleNode;

NelioAB.checker.init = function() {
	// Check if the user accepts cookies...
	if ( !NelioAB.cookies.areEnabled() ) {
		jQuery(document).trigger('nelioab-gtm-call');
		return;
	}

	if ( NelioAB.cookies.get( 'nelioab_is_in' ) == 'no' ) {
		jQuery(document).trigger('nelioab-gtm-call');
		return;
	}

	// Making sure that nothing is executed too soon
	// (when calling show_body, we'll release the holding)
	jQuery.holdReady( true );

	// Synchronize cookies and load an alt if it's required
	try {
		var res = NelioAB.checker.syncCookiesAndCheck();
		if ( 'DO_NOT_LOAD_ANYTHING' != res.action)
			NelioAB.checker.loadAlternative( res.mode, res.action );
	}
	catch(e) {
		NelioAB.helpers.showBody();
	}
};

NelioAB.checker.q = function() {
	jQuery.ajax({
		type:  'POST',
		async: true,
		url:   NelioABParams.ajaxurl,
		data: { action:'nelioab_qc' },
	});
};

NelioAB.checker.syncCookiesAndCheck = function() {
	var result = { action:'DO_NOT_LOAD_ANYTHING', mode:'' };

	// Make the body invisible
	NelioAB.helpers.hideBody();

	jQuery.ajax({
		type:  'POST',
		async: false,
		url:   NelioABParams.ajaxurl,
		data: {
			action: 'nelioab_sync_cookies_and_check',
			current_url: document.URL,
			referer_url: document.referrer,
			nelioab_cookies: NelioAB.cookies.list(),
		},
		success: function(data) {
			try {
				json = JSON.parse(data);
				NelioABParams.sync = json.sync;
				cookies = json.cookies;
				if ( cookies['__nelioab_new_version'] != undefined )
					NelioAB.cookies.clean();
				jQuery.each(cookies, function(name, value) {
					if (NelioAB.cookies.get(name) == undefined) {
						document.cookie = name + '=' + value + ';path=/';
					}
					else if (name == 'nelioab_was_in') {
						NelioAB.cookies.remove(name);
						document.cookie = name + '=' + value + ';path=/';
					}
					if ( value == '__delete_cookie' ) {
						NelioAB.cookies.remove(name);
					}
				});
				NelioAB.cookies.remove('__nelioab_new_version');

				if ( NelioAB.cookies.get('nelioab_session') == undefined )
					document.cookie = 'nelioab_session=' +
						NelioAB.cookies.get('nelioab_userid') + '-' +
						new Date().getTime()  +
						';path=/';

				// If the user is not in the test, leave
				if ( NelioAB.cookies.get( 'nelioab_is_in' ) == 'no' ) {
					NelioAB.helpers.showBody();
					return result;
				}

				// If we should load an alternative...
				if ( 'LOAD_ALTERNATIVE' == json.action || 'LOAD_CONSISTENT_VERSION' == json.action ) {
					result.action = json.action;
					result.mode = json.mode;
				}
				else {
					NelioAB.helpers.showBody();
					NelioAB.helpers.trackAndSync();
				}
			}
			catch(e) {
				NelioAB.helpers.showBody();
			}
		},
		error: function() {
			NelioAB.helpers.showBody();
		},
	});

	return result;
};

NelioAB.checker.loadAlternative = function( mode, action ) {
	var data = {
		'nelioab_cookies': NelioAB.cookies.listForAltLoading(),
		'current_url': document.URL,
		'referer_url': document.referrer,
	};

	if ( 'LOAD_ALTERNATIVE' == action )
		data['nelioab_load_alt'] = 'true';
	else if ( 'LOAD_CONSISTENT_VERSION' == action )
		data['nelioab_load_consistent_version'] = 'true';
	else
		return;

	var permalink = NelioAB.helpers.mergeUrlParams( NelioABParams.permalink, document.URL );

	jQuery.ajax({
		type:  mode,
		async: false,
		url:   permalink,
		data: data,
		success: function(data) {
			if ( data.indexOf( '\"nelioab_perform_check_request\":\"yes\"' ) != -1 ) {
				console.error("Recursive load of alternative content detected");
				NelioAB.helpers.showBody();
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
				NelioAB.helpers.showBody();
			}

			// Manually calling `docIsReady` if the event DOMContentLoaded was already triggered
			if ( 'complete' == document.readyState || 'loaded' == document.readyState ) {
				docIsReady();
			}
		},
		error: function(data) {
			NelioAB.helpers.showBody();
		}
	});
}

if ( NelioABParams.nelioab_perform_check_request == "yes" )
	NelioAB.checker.init();
else
	NelioAB.helpers.trackAndSync();

