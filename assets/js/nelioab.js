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

function nelioab_areCookiesEnabled() {
	document.cookie = "__verify=1";
	var supportsCookies = document.cookie.length > 1 && 
		document.cookie.indexOf("__verify=1") > -1;
	delete_cookie("__verify");
	return supportsCookies;
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
					document.cookie = name + "=" + value;
			});
			delete_cookie("__nelioab_new_version");
			cookies_sync = true;
		}
		catch(e) {
		}
	});

	return cookies_sync;
}

function nelioab_get_cookie_by_name(name) {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		var cookieVal = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();
		if (cookieName == name)
			return cookieVal;
	}
	return undefined;
}

function nelioab_get_local_cookies() {
	var result = "{";
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		var cookieVal = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();
		if (cookieName.indexOf("nelioab_") == 0)
			result += " " + JSON.stringify(cookieName) + ":" +
				JSON.stringify(cookieVal) + ",";
	}
	if ( result[result.length-1] == "," )
		result = result.substring(0, result.length-1) + " ";
	result += "}";
	return JSON.parse(result);
}

function clean_cookies() {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		if (cookieName.indexOf("nelioab_") == 0)
			if (cookieName.indexOf("userid") == -1)
				delete_cookie(cookieName);
	}
}

function delete_cookie( name ) {
	var thePast = new Date(1985, 1, 1);
	document.cookie = name + "=1;expires=" + thePast.toUTCString();
}

function nelioab_nav($) {
	$.ajax({
		type:  'POST',
		async: true,
		url:   window.location.href,
		data: {
			referer: document.referrer,
			nelioab_cookies: nelioab_get_local_cookies(),
			nelioab_nav: 'true',
		},
	});
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
				nelioab_nav($);
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

var nelioab_styleNode;
function nelioab_hide_body() {
	nelioab_styleNode = document.createElement("style");
	nelioab_styleNode.setAttribute("type", "text/css");
	nelioab_styleNode.innerHTML = "body {display: none;}";
	document.getElementsByTagName('head')[0].appendChild(nelioab_styleNode);
}

function nelioab_show_body() {
	nelioab_styleNode.innerHTML = "";
}

nelioab_init();
