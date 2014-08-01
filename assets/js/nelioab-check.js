function nelioab_init() {
	// Check if the user accepts cookies...
	if ( !nelioab_areCookiesEnabled() )
		return;

	// Make the body invisible
	nelioab_hide_body();

	// Synchronize cookies
	nelioab_sync_cookies_and_load_alternative_if_required(jQuery);
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
	}).success(function(data) {
		try {
			json = JSON.parse(data);
			cookies = json.cookies;
			if ( cookies['__nelioab_new_version'] != undefined )
				clean_cookies();
			$.each(cookies, function(name, value) {
				if (nelioab_get_cookie_by_name(name) == undefined)
					document.cookie = name + "=" + value + ";path=/";
			});
			delete_cookie("__nelioab_new_version");
			are_cookies_sync = true;

			is_load_alt_required = ( json.load_alt == 'LOAD_ALT' );
			if ( !is_load_alt_required )
				nelioab_nav($);

			if ( are_cookies_sync && is_load_alt_required ) {
				nelioab_load_alt(jQuery);
			}
			else {
				nelioab_show_body();
				jQuery(document).ready(function(){
					if ( typeof( nelioabStartHeatmapTracking ) == "function" )
						nelioabStartHeatmapTracking();
				});
			}
		}
		catch(e) {
			nelioab_show_body();
		}
	});
}

function nelioab_load_alt($) {
	$.ajax({
		type:  'POST',
		async: false,
		url:   NelioABChecker.permalink,
		data: {
			nelioab_cookies:  nelioab_get_local_cookies(),
			nelioab_load_alt: 'true',
		},
		success: function(data) {
			if ( data.indexOf( 'nelio-ab-testing/assets/js/nelioab-check.min.js' ) != -1 ) {
				console.log( 'ERROR: nelioab checker script has been included again...' );
				nelioab_show_body();
				return;
			}
			data = data
				.replace(
					/<script src="https?:\/\/stats.wordpress.com\/e-([^\n]*)\n/g,
					'<!-- <script src="http://stats.wordpress.com/e-$1 -->\n' +
					'\t<script>function st_go(a){} function linktracker_init(a,b){}</scr'+'ipt>\n'
				);
			data = data
				.replace(
					/<\/head>/,
					'<script>try { if ( nelioab_cssExpNode !== undefined )' +
					'document.getElementsByTagName("head")[0].' +
					'appendChild(nelioab_cssExpNode);' +
					'} catch ( e ) {}' +
					'</scr'+'ipt>\n</head>'
				);
			$(document).ready(function() {
				var aux = window.setTimeout(function() {}, 0);
				while (aux--) window.clearTimeout(aux);
				document.open();
				document.write(data);
				document.close();
			});
		},
		error: function(data) {
			nelioab_show_body();
		}
	});
}

nelioab_init();
