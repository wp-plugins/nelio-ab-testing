/**
 * These functions are auxiliary. No one should use them directly!
 */
NelioAB.cookies = {};


/**
 * This function returns whether a string can be the name of a Nelio A/B
 * Testing cookie (but only for AB experiments).
 *
 * @param name
 *              The name of a cookie.
 *
 * @return whether a string can be the name of a Nelio A/B Testing cookie (but
 *         only for AB experiments).
 */
NelioAB.cookies.isABCookieName = function( name ) {
	return ( name.indexOf( 'nelioab_' ) == 0 && name.indexOf( '_altexp' ) > 0 );
};


/**
 * This variable stores the current time.
 */
NelioAB.cookies.now = new Date().getTime();


/**
 * This variable stores a date that is exactly a couple of minutes in the
 * future from now.
 */
NelioAB.cookies.EXPIRES_IN_TWO_MINUTES = new Date();
NelioAB.cookies.EXPIRES_IN_TWO_MINUTES.setTime( NelioAB.cookies.now + 1000*60*2 );
NelioAB.cookies.EXPIRES_IN_TWO_MINUTES = NelioAB.cookies.EXPIRES_IN_TWO_MINUTES.toUTCString();


/**
 * This variable stores a date that is exactly three months in the future from
 * now.
 */
NelioAB.cookies.EXPIRES_IN_THREE_MONTHS = new Date();
NelioAB.cookies.EXPIRES_IN_THREE_MONTHS.setTime( NelioAB.cookies.now + 1000*60*60*24*90 );
NelioAB.cookies.EXPIRES_IN_THREE_MONTHS = NelioAB.cookies.EXPIRES_IN_THREE_MONTHS.toUTCString();


/**
 * This variable stores a date that is exactly ten years in the future from
 * now.
 */
NelioAB.cookies.EXPIRES_IN_TEN_YEARS = new Date();
NelioAB.cookies.EXPIRES_IN_TEN_YEARS.setTime( NelioAB.cookies.now + 1000*60*60*24*365 );
NelioAB.cookies.EXPIRES_IN_TEN_YEARS = NelioAB.cookies.EXPIRES_IN_TEN_YEARS.toUTCString();


/**
 * This function returns the value of the cookie named name or undefined if the
 * cookie does not exist.
 *
 * @return the value of the cookie named name or undefined if the cookie does
 *         not exist.
 */
NelioAB.cookies.get = function(name) {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (undefined == cookie)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		var cookieVal = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();
		if (cookieName == name)
			return cookieVal;
	}

	return undefined;
};


/**
 * This function returns the value of the first cookie whose name starts with
 * name or an empty string if no cookie was found.
 *
 * @return the value of the first cookie whose name starts with name or an
 *         empty string if no cookie was found.
 */
NelioAB.cookies.getFuzzy = function(name) {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (undefined == cookie)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		var cookieVal = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();
		if (cookieName.indexOf(name) == 0)
			return cookieVal;
	}
	return '';
};


/**
 * This function removes all the cookies whose name starts with "nelioab_",
 * except the "nelioab_userid" cookie.
 */
NelioAB.cookies.clean = function() {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		if (cookieName.indexOf('nelioab_') == 0)
			if (cookieName.indexOf('userid') == -1)
				NelioAB.cookies.remove(cookieName);
	}
};


/**
 * This function removes the cookie named name (if any).
 */
NelioAB.cookies.remove = function( name ) {
	var thePast = new Date(1985, 1, 1);
	document.cookie = name + '=1;path=/;expires=' + thePast.toUTCString();
};

