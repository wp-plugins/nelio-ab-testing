/**
 * Cookie Management
 */
NelioAB.cookies = {};
NelioAB.cookies.areEnabled = function() {
	document.cookie = "__verify=1;path=/";
	var supportsCookies = document.cookie.length > 1 &&
		document.cookie.indexOf("__verify=1") > -1;
	NelioAB.cookies.remove("__verify");
	return supportsCookies;
};

NelioAB.cookies.get = function(name) {
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
};

NelioAB.cookies.list = function() {
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
};

NelioAB.cookies.listForAltLoading = function() {
	var result = "{";
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		var cookieVal = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();
		if (cookieName.indexOf("nelioab_altexp_") == 0)
			result += " " + JSON.stringify(cookieName) + ":" +
				JSON.stringify(cookieVal) + ",";
		else if (cookieName.indexOf("nelioab_global_altexp_") == 0)
			result += " " + JSON.stringify(cookieName) + ":" +
				JSON.stringify(cookieVal) + ",";
	}
	if ( result[result.length-1] == "," )
		result = result.substring(0, result.length-1) + " ";
	result += "}";
	return JSON.parse(result);
};

NelioAB.cookies.clean = function() {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		if (cookieName.indexOf("nelioab_") == 0)
			if (cookieName.indexOf("userid") == -1)
				NelioAB.cookies.remove(cookieName);
	}
};

NelioAB.cookies.remove = function( name ) {
	var thePast = new Date(1985, 1, 1);
	document.cookie = name + "=1;path=/;expires=" + thePast.toUTCString();
};

