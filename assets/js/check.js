/**
 * This object contains some relevant functions for loading alternative
 * content.
 */
NelioAB.checker = {};


/**
 * This variable contains a style node (for CSS experiments).
 */
NelioAB.checker.styleNode;


/**
 * This variable holds the prefix of Nelio A/B Testing GET params.
 */
NelioAB.checker.nabPrefix = 'nab';


/**
 * This variable holds a regular expression for detecting whether a GET param
 * is related to Nelio A/B Testing or not.
 */
NelioAB.checker.nabMatcher = /^nab[cmtwe]?$/;


/**
 * Magic starts here. This function will check if there are any experiments
 * running and, if there are, if we should track some information about the
 * current user.
 */
NelioAB.checker.init = function() {
	if ( !NelioABBasic.hasExpsRunning )
		return;
	if ( !NelioAB.user.canInfoBeSaved() )
		return;
	if ( !NelioAB.user.participates() )
		return;
	if ( NelioABParams.wasPostRequest ) {
		console.log( 'Current page is the result of a POST request. Abort!' );
		return;
	}
	if ( NelioAB.helpers.isDuplicatedFrame() ) {
		console.log( 'Duplicated iFrame (`' + document.URL + '\') detected. Abort!' );
		return;
	}

	// Making sure that nothing is executed too soon
	// (when calling show_body, we'll release the holding)
	jQuery.holdReady( true );

	// Make sure she has all the alternatives assigned and check if we need to
	// load one
	try {
		console.log( 'Checking `' + window.document.URL + '\'...' );
		var isAltLoadingRequired = NelioAB.checker.assignAlternativesAndCheck();
		if ( isAltLoadingRequired ) {
			console.log( 'Loading `' + window.document.URL + '\'...' );
			NelioAB.checker.loadAlternative();
		}
		else {
			NelioAB.checker.cleanUrl();
			NelioAB.helpers.showBody();
			if ( NelioABBasic.hasQuota ) {
				NelioAB.helpers.addDocumentHooks();
				NelioAB.helpers.track();
			}
			console.log( 'Done!' );
		}
	}
	catch(e) {
		NelioAB.helpers.showBody();
		console.warn( 'Oops! ' + e.stack );
	}
};


/**
 * For each running experiment, this function assigns an alternative to the
 * user. It will also check whether the current page is under test and requires
 * to load an alternative.
 *
 * @return whether alternative content has to be loaded or not.
 */
NelioAB.checker.assignAlternativesAndCheck = function() {
	var isAltLoadingRequired = false;

	// Make the body invisible
	NelioAB.helpers.hideBody();

	// ****************************************************************************
	// First, we load the appropriate environment and make sure the user has an
	// alternative for each running experiment. Obviously, alternative
	// assignation has to take into account the values of the cookies (which
	// might overwrite the values she already has).
	var getParams = [];
	var aux = document.URL.indexOf( '?' );
	if ( -1 != aux )
		getParams = NelioAB.helpers.extractGetParams( document.URL.substring(aux) );

	var aux = [];
	for ( var i = 0; i < getParams.length; ++i ) {
		if ( NelioAB.checker.nabMatcher.test( getParams[i][0] ) )
			aux.push( getParams[i] );
	}
	NelioAB.user.loadEnvironmentAndSetAlternatives(aux);

	// ****************************************************************************
	// Next, we check if we're supposed to load an alternative
	isAltLoadingRequired = NelioAB.helpers.isAltLoadingRequired();


	// ****************************************************************************
	// Finally, it is possible that we're already seeing the alternative content
	// (all versions use the same tracking script). Therefore, we have to make
	// sure we aren't, or we might end up in an infinite loop.

	// These are the params the script generated for me
	var myParams = NelioAB.checker.generateParamsForAltLoading();

	// Let's check if we need to load an alternative (that is, rewrite the
	// URL). This will only occur if:
	// a) not all the relevant params are included
	var allParamsIncluded = true;
	for ( var i = 0; i < myParams.length && allParamsIncluded; ++i ) {
		var found = false;
		for ( var j = 0; j < getParams.length && !found; ++j ) {
			var myName = myParams[i][0];
			var myValue = myParams[i][1];
			if ( NelioAB.checker.nabPrefix + 'e' == myName ) {
				myValue = '';
				var aux = myParams[i][1];
				for ( var k = 0; k < aux.length; ++k )
					myValue += aux[k][0] + ':' + aux[k][1] + ',';
				if ( myValue.length > 0 )
					myValue = myValue.substring(0, myValue.length - 1);
			}
			if ( myName == getParams[j][0] && myValue == getParams[j][1])
				found = true;
		}
		if ( !found )
			allParamsIncluded = false;
	}

	// b) there are params that make no longer sense
	var areThereTooManyParams = false;
	for ( var i = 0; i < getParams.length && !areThereTooManyParams; ++i ) {
		var found = false;
		if ( !NelioAB.checker.nabMatcher.test( getParams[i][0] ) )
			continue;
		for ( var j = 0; j < myParams.length && !found; ++j )
			if ( getParams[i][0] == myParams[j][0] )
				found = true;
		if ( !found )
			areThereTooManyParams = true;
	}

	// As we said, if, and only if, all the required params are already in the
	// URL, we don't need to load alternative content (for we're already seeing
	// it).
	if ( allParamsIncluded && !areThereTooManyParams )
		isAltLoadingRequired = false;

	return isAltLoadingRequired;
};


/**
 * This function builds a new URL using the address url, adding all
 * myParams list and completing it with the getParams.
 *
 * @param url
 *              a URL without get params.
 * @param myParams
 *              a list of params relevant for AB experiments.
 * @param getParams
 *              any other params the original URL could have had (including
 *              AB-related params too, which will be ignored).
 *
 * @return the new URL with a list of GET params obtained from the
 *          arrays myParams and getParams.
 */
NelioAB.checker.buildUrl = function( url, myParams, getParams ) {
	url += '?';

	// First of all, we add all the relevant AB params
	for ( var i = 0; i < myParams.length; ++i ) {
		var name = myParams[i][0];
		var vals = myParams[i][1];
		if ( NelioAB.checker.nabPrefix + 'e' == name ) {
			var aux = '';
			for ( var j = 0; j < vals.length; ++j )
				aux += vals[j][0] + ':' + vals[j][1] + ',';
			aux = aux.substring( 0, aux.length - 1);
			url += NelioAB.checker.nabPrefix + 'e=' + encodeURIComponent( aux ) + '&';
		}
		else {
			var val = '' + myParams[i][1];
			if ( val.length > 0 )
				val = '=' + val;
			url += myParams[i][0] + val + '&';
		}
	}

	// Then, we add the remaining, non-AB-relevant params
	for ( var i = 0; i < getParams.length; ++i ) {
		var name = getParams[i][0];
		if ( !NelioAB.checker.nabMatcher.test( name ) ) {
			var val = '' + getParams[i][1];
			if ( val.length > 0 )
				val = '=' + val;
			url += name + val + '&';
		}
	}
	url = url.substring( 0, url.length - 1);

	return url;
};


/**
 * This function prepares all the GET params for loading alternative content.
 *
 * @return a list with GET params.
 */
NelioAB.checker.generateParamsForAltLoading = function() {
	// First, we create the result object
	var result = [];

	// Then, we need to see if the current page is under a non-global AB
	// experiment
	var abForThisPage = false;
	for ( var i = 0; i < NelioABEnv.ab.length && !exp; ++i ) {
		var pid = NelioABEnv.ab[i].alts[0];
		if ( typeof NelioABEnv.ab[i].pid != 'undefined' )
			pid = NelioABEnv.ab[i].pid;
		if ( NelioABParams.info.currentId == pid )
			abForThisPage = NelioABEnv.ab[i];
	}
	if ( abForThisPage ) {
		result.push( [NelioAB.checker.nabPrefix, NelioAB.user.getAlt( abForThisPage.name )] );
		abForThisPage = abForThisPage.name;
	}

	// Next, we get all the global AB experiments alphabetically
	var aux;
	aux = NelioAB.user.getGlobalAlt( 'css' );
	if ( aux.length > 0 )
		result.push( [NelioAB.checker.nabPrefix + 'c', aux] );
	aux = NelioAB.user.getGlobalAlt( 'menu' );
	if ( aux.length > 0 )
		result.push( [NelioAB.checker.nabPrefix + 'm', aux] );
	aux = NelioAB.user.getGlobalAlt( 'theme' );
	if ( aux.length > 0 )
		result.push( [NelioAB.checker.nabPrefix + 't', aux] );
	aux = NelioAB.user.getGlobalAlt( 'widget' );
	if ( aux.length > 0 )
		result.push( [NelioAB.checker.nabPrefix + 'w', aux] );

	// Finally, we need to add all the remaining experiments. These experiments
	// make the site "consistent", so that alternative content is displayed
	// consistently, regardless of where it's seen.
	// Obviously, we'll only load alternative data if:
	//  a) Site consistency is enabled (all page have to trigger an alternative
	//     loading request, to ensure consistency).
	//  b) There's at least one headline experiment running (all pages will
	//     load alternative content, just to make sure that alternative
	//     headlines--if any--are properly replaced).
	//  c) We already need to load an alternative (because there's an AB
	//     experiment for the current page or a global experiment).
	if ( NelioABBasic.settings.consistency ||
	     '*' == NelioABEnv.tids[0] ||
	     result.length > 0 ) {
		// Since they sorted alphabetically in AE, we simply need to iterate over
		// all AB exps, excluding the one that affects this page (if any) and the
		// global ones.
		var otherExps = [];
		for ( var i = 0; i < NelioABEnv.ab.length; ++i ) {
			var exp = NelioABEnv.ab[i];
			if ( abForThisPage == exp.name )
				continue;
			if ( exp.name.indexOf( NelioAB.helpers.getRegularCookiePrefix() ) == -1 )
				continue;
			var id = exp.name.replace( NelioAB.helpers.getRegularCookiePrefix(), '' );
			otherExps.push( [id, NelioAB.user.getAlt( id )] );
		}
		if ( otherExps.length > 0 )
			result.push( [NelioAB.checker.nabPrefix + 'e', otherExps] );
	}

	return result;
};


/**
 * Returns the same params as generateParamsForAltLoading with one difference:
 * the param "nab" does not exist; instead, its value is specified in "nabe".
 *
 * @return the same params as generateParamsForAltLoading with one difference:
 *         the param "nab" does not exist; instead, its value is specified in
 *         "nabe".
 */
NelioAB.checker.generateAjaxParams = function() {
	var types = [ 'css', 'menu', 'theme', 'widget' ];
	var result = { 'nelioab_env' : {} };
	for ( var i = 0; i < NelioABEnv.ab.length; ++i ) {
		var exp  = NelioABEnv.ab[i];
		var name = exp.name;
		var val  = false;
		var id = false;
		if ( name.indexOf( NelioAB.helpers.getRegularCookiePrefix() ) == 0 ) {
			id = name.replace( NelioAB.helpers.getRegularCookiePrefix(), '' );
			val = NelioAB.user.getAlt(id);
		}
		for ( var j = 0; j < types.length && !val; ++j ) {
			var type = types[j];
			if ( name.indexOf( NelioAB.helpers.getGlobalCookiePrefix( type ) ) == 0 ) {
				val = NelioAB.user.getGlobalAlt( type );
				id = name.replace( NelioAB.helpers.getGlobalCookiePrefix( type ), '' );
			}
		}
		if ( val !== false )
			result.nelioab_env[id] = parseInt(val);
	}
	return result;
};


/**
 * This function loads the appropriate alternative overwriding the current URL
 * with a new URL that contains a list of AB Testing GET params.
 */
NelioAB.checker.loadAlternative = function() {
	var url = document.URL;

	var getParams = [];
	var aux = url.indexOf( '?' );
	if ( -1 != aux )
		getParams = NelioAB.helpers.extractGetParams( url.substring(aux) );
	var myParams = NelioAB.checker.generateParamsForAltLoading();

	try {
		var utm_referrer = '';
		for ( var i = 0; i < getParams.length && utm_referrer.length == 0; ++i )
			if ( getParams[i][0] == 'utm_referrer' )
				utm_referrer = getParams[i][1];
		if ( utm_referrer.length == 0 && document.referrer.length > 0 ) {
			var refDomain = document.referrer;
			var thisDomain = document.URL;

			refDomain = refDomain.replace( /^https?:\/\//, '' );
			refDomain = refDomain.replace( /\/.*$/, '' );

			thisDomain = thisDomain.replace( /^https?:\/\//, '' );
			thisDomain = thisDomain.replace( /\/.*$/, '' );

			if ( thisDomain != refDomain )
				getParams.push( ['utm_referrer', encodeURIComponent( document.referrer )] );
		}
	}
	catch ( e ) {
	}

	var aux = document.URL.indexOf( '?' );
	if ( aux != -1 )
		url = url.substring( 0, aux );
	url = NelioAB.checker.buildUrl( url, myParams, getParams );

	window.location.replace( url );
};


/**
 * This function cleans the URL, removing (if necessary) some A/B-Testing GET
 * params. They are removed depending on the value of hideParms, which can
 * either be [all|context|none].
 */
NelioAB.checker.cleanUrl = function() {
	var url = document.URL;
	var aux = url.indexOf( '?' );
	var getParams = [];
	if ( -1 != aux ) {
		getParams = NelioAB.helpers.extractGetParams( url.substring(aux) );
		url = url.substring( 0, aux ) ;
	}
	if ( 'all' == NelioABBasic.settings.hideParams ) {
		url += '?';
		for ( var i = 0; i < getParams.length; ++i ) {
			if ( NelioAB.checker.nabMatcher.test( getParams[i][0] ) )
				continue;
			var val = '' + getParams[i][1];
			if ( val.length > 0 )
				val = '=' + val;
			url += getParams[i][0] + val + '&';
		}
		url = url.substring( 0, url.length-1 );
		NelioAB.helpers.updateRefererInformation( url );
	}
	else if ( 'context' == NelioABBasic.settings.hideParams ) {
		url += '?';
		for ( var i = 0; i < getParams.length; ++i ) {
			if ( NelioAB.checker.nabPrefix + 'e' == getParams[i][0] )
				continue;
			var val = '' + getParams[i][1];
			if ( val.length > 0 )
				val = '=' + val;
			url += getParams[i][0] + val + '&';
		}
		url = url.substring( 0, url.length-1 );
		NelioAB.helpers.updateRefererInformation( url );
	}
	else if ( 'none' == NelioABBasic.settings.hideParams ) {
		// No params should be removed, but we have to save the actual referer
		NelioAB.helpers.updateRefererInformation();
	}
};


/**
 * Let's start!
 */
NelioAB.checker.init();
