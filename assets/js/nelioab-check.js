function nelioab_init() {
	// Check if the user accepts cookies...
	if ( !nelioab_areCookiesEnabled() )
		return;

	// Make the body invisible
	nelioab_hide_body();

	// Synchronize cookies
	if ( !nelioab_sync_cookies(jQuery) ) {
		nelioab_show_body();
		return;
	}


	// Load alt
	nelioab_check_and_load_alt(jQuery);
}

var aux;
function nelioab_sync_cookies($) {
	var cookies_sync = false;
	$.ajax({
		type:  'POST',
		async: false,
		url:   window.location.href,
		data: {
			nelioab_cookies: nelioab_get_local_cookies(),
			nelioab_sync: 'true',
		},
	}).success(function(data) {
		try {
			json = JSON.parse(data);
			if ( json['__nelioab_new_version'] != undefined )
				clean_cookies();
			$.each(json, function(name, value) {
				if (nelioab_get_cookie_by_name(name) == undefined)
					document.cookie = name + "=" + value + ";path=/";
			});
			delete_cookie("__nelioab_new_version");
			cookies_sync = true;
		}
		catch(e) {
		}
	});

	return cookies_sync;
}

function nelioab_check_and_load_alt($) {
	$.ajax({
		type:  'POST',
		async: false,
		url:   window.location.href,
		data: {
			nelioab_cookies: nelioab_get_local_cookies(),
			nelioab_check_alt: 'true',
		},
		success: function(data) {
			try {
				is_load_required = JSON.parse(data);
			}
			catch(e) {
				nelioab_show_body();
				return;
			}

			if ( is_load_required ) {
				// nelioab_nav($) is called in the script nelioab-nav.js, which
				// is included in the alternative.
				nelioab_load_alt($);
			}
			else {
				nelioab_nav($);
				nelioab_show_body();
			}
		},
		error: function(data) {
			nelioab_show_body();
		}
	});
}

function nelioab_load_alt($) {
	$(document).ready(function() {
		$.ajax({
			type:  'POST',
			async: false,
			url:   window.location.href,
			data: {
				nelioab_cookies:  nelioab_get_local_cookies(),
				nelioab_load_alt: 'true',
			},
			success: function(data) {
				document.open();
				document.write(data);
				document.close();
			},
			error: function(data) {
				nelioab_show_body();
			}
		});
	});
}

nelioab_init();
