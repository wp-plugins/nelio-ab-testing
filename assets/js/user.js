/**
 * This object contains information about the current user.
 */
NelioAB.user = {};

/**
 * Check if cookies are enabled.
 *
 * @return whether cookies are enabled or not.
 */
NelioAB.user.canInfoBeSaved = function() {
	document.cookie = '__verify=1;path=/';
	var supportsCookies = document.cookie.length > 1 &&
		document.cookie.indexOf('__verify=1') > -1;
	NelioAB.cookies.remove('__verify');
	return supportsCookies;
};


/**
 * This function loads the environment this user is supposed to be using (which
 * is stored in the nelioab_env cookie) or assigns one of the available ones if
 * she has none or if the one she had does no longer exist.
 *
 * @param params
 *               A list of pairs with all the GET params that are relevant for
 *               A/B testing.
 */
NelioAB.user.loadEnvironment = function( params ) {
	var expires = 'expires=' + NelioAB.cookies.EXPIRES_IN_TEN_YEARS;

	// Let's see if the user is trying to set a specific version for this page.
	// If she is, we'll have to check whether the current page is actually under
	// test and, if it is, find the specific experiment.
	var abForThisPage = false;
	for ( var i = 0; i < params.length && !abForThisPage; ++i )
		if ( NelioAB.checker.nabPrefix == params[i][0] )
			abForThisPage = params[i][1];
	if ( abForThisPage ) {
		for ( var i = 0; i < NelioABBasic.envs.length; ++i ) {
			var env = NelioABBasic.envs[i];
			for ( var j = 0; j < env.ab.length && !NelioABEnv; ++j ) {
				var pid = env.ab[j].alts[0];
				if ( typeof env.ab[j].pid != 'undefined' )
					pid = env.ab[j].pid;
				if ( NelioABParams.info.currentId == pid )
					NelioABEnv = env;
			}
		}

		if ( NelioABEnv ) {
			// I've found an environment that works... let's see if it's the
			// environment I already had (if any)
			var envId = NelioAB.cookies.get( 'nelioab_env' );
			var isNewEnv = envId != NelioABEnv.id;

			// If it is a new environment, I'll have to create a new user ID and
			// update the cookie
			if ( isNewEnv ) {
				NelioAB.cookies.remove( 'nelioab_userid' );
				NelioAB.cookies.remove( 'nelioab_session' );
				NelioAB.user.id();
				document.cookie = 'nelioab_env=' + NelioABEnv.id + '; ' + expires + '; path=/';
			}
		}
	}

	// If I haven't been able to load an environment yet, let's see what I have
	// in the cookies or, alternatively, let's assign one randomly.
	if ( !NelioABEnv ) {
		var envId = NelioAB.cookies.get( 'nelioab_env' );
		for ( var i = 0; i < NelioABBasic.envs.length && !NelioABEnv; ++i )
			if ( NelioABBasic.envs[i].id == envId )
				NelioABEnv = NelioABBasic.envs[i];
		if ( !NelioABEnv ) {
			var index = -1;
			if ( NelioABBasic.envs.length > 0 ) {
				index = Math.floor(Math.random()*NelioABBasic.envs.length);
				NelioABEnv = NelioABBasic.envs[index];
				envId = NelioABBasic.envs[index].id;
				document.cookie = 'nelioab_env=' + envId + '; ' + expires + '; path=/';
			}
		}
	}
};


/**
 * This function returns the ID of the current user.
 *
 * @return the ID of the current user.
 */
NelioAB.user.id = function() {
	var uuid = NelioAB.cookies.get( 'nelioab_userid' );
	if ( typeof uuid == 'undefined' ) {
		var d;
		if ( typeof performance != 'undefined' && typeof performance.now == 'function' )
			d = performance.now();
		else if ( typeof Date != 'undefined' && typeof Date().now == 'function' )
			d = Date().now();
		else
			d = new Date().getTime();
		uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = (d + Math.random()*16)%16 | 0;
			d = Math.floor(d/16);
			return (c=='x' ? r : (r&0x3|0x8)).toString(16);
		});
		var expires = 'expires=' + NelioAB.cookies.EXPIRES_IN_TEN_YEARS;
		document.cookie = 'nelioab_userid=' + uuid + '; ' + expires + '; path=/';
	}
	return uuid;
};


/**
 * This function returns a unique identifier for the current navigation session
 * of this user.
 *
 * @return a unique identifier for the current navigation session of this user.
 */
NelioAB.user.session = function() {
	var session = NelioAB.cookies.get( 'nelioab_session' );
	if ( typeof session == 'undefined' ) {
		var d;
		if ( typeof performance != 'undefined' && typeof performance.now == 'function' )
			d = performance.now();
		else if ( typeof Date != 'undefined' && typeof Date().now == 'function' )
			d = Date().now();
		else
			d = new Date().getTime();
		session = NelioAB.user.id() + '-' + d;
		document.cookie = 'nelioab_session=' + session + '; path=/';
	}
	return session
};


/**
 * This function specifies whether a user participates in the running
 * experiments (and, therefore, her activity has to be tracked) or not.
 *
 * @return whether a user participates in the running experiments (and,
 *         therefore, her activity has to be tracked) or not.
 */
NelioAB.user.participates = function() {
	var isIn = NelioAB.cookies.get( 'nelioab_is_in' );
	if ( typeof isIn == 'undefined' ) {
		isIn  = 'no';
		var expires = 'expires=' + NelioAB.cookies.EXPIRES_IN_TEN_YEARS;
		var wasIn = 'true';
		var prevPerc = NelioABBasic.settings.partChance;
		try {
			var aux = NelioAB.cookies.get('nelioab_was_in').split(':');
			wasIn = aux[0];
			prevPerc = aux[1];
			isIn = wasIn;
			if ( prevPerc != NelioABBasic.settings.partChance )
				throw 0;
		}
		catch (e) {
			var aux = Math.floor((Math.random() * 101));
			if ( aux <= NelioABBasic.settings.partChance )
				isIn = 'yes';
			else
				isIn = 'no';
			document.cookie = 'nelioab_was_in=' +
				isIn + ':' + NelioABBasic.settings.partChance + ';' +
				expires + ';path=/';
		}
		document.cookie = 'nelioab_is_in=' + isIn + ';path=/';
	}
	return ('yes' == isIn);
};


/**
 * Returns the index of the alternative this user has assigned for the
 * experiment with ID id. If there's no experiment with ID id, undefined is
 * returned.
 *
 * @param id
 *            The experiment ID (or the cookie name) for which we want to know
 *            which alternative the user is supposed to see.
 *
 * @return the index of the alternative this user has assigned for the
 *         experiment with ID id. If there's no experiment with ID id,
 *         undefined is returned.
 */
NelioAB.user.getAlt = function( id ) {
	var exp = false;
	var name = id;
	if ( name.indexOf( 'nelioab_' ) != 0 )
		name = NelioAB.helpers.getRegularCookiePrefix() + id;
	for ( var i = 0; i < NelioABEnv.ab.length && !exp; ++i )
		if ( name == NelioABEnv.ab[i].name )
			exp = NelioABEnv.ab[i];
	if ( exp ) {
		var value = NelioAB.cookies.get( name );
		if ( value < exp.alts.length )
			return value;
	}
	return 0;
};


/**
 * Returns the index of the alternative this user has assigned for the global
 * experiment of type type. If there's no experiment defined for the given
 * type, an empty string will be returned.
 *
 * @param type
 *            The type of global experiment for which we want to know the
 *            alternative that should be loaded. It can either be
 *            css | menu | theme | widget.
 *
 * @return the index of the alternative this user has assigned for the global
 *         experiment of type type. If there's no experiment defined for the
 *         given type, an empty string will be returned.
 */
NelioAB.user.getGlobalAlt = function( type ) {
	var name = NelioAB.helpers.getGlobalCookiePrefix( type );
	var aux = NelioAB.cookies.getFuzzy(name);
	if ( aux < 0 || aux > 100 )
		return '0';
	else
		return aux;
};


/**
 * Returns the cookie value of alternative this user has assigned for the
 * global experiment of type type. If there's no experiment defined for the
 * given type, an empty string will be returned.
 *
 * @param type
 *            The type of global experiment for which we want to know the
 *            alternative that should be loaded. It can either be
 *            css | menu | theme | widget.
 *
 * @return the cookie value of the  alternative this user has assigned for the
 *         global experiment of type type. If there's no experiment defined for
 *         the given type, an empty string will be returned.
 */
NelioAB.user.getGlobalAltValue = function( type ) {
	var exp = false;
	var value = '';
	if ( type ) {
		var name = NelioAB.helpers.getGlobalCookiePrefix( type );
		for ( var i = 0; i < NelioABEnv.ab.length && !exp; ++i )
			if ( NelioABEnv.ab[i].name.indexOf( name ) == 0 )
				exp = NelioABEnv.ab[i];
		if ( exp ) {
			var index = NelioAB.user.getGlobalAlt( type );
			if ( index < exp.alts.length )
				value = '' + exp.alts[index];
		}
	}
	return value;
};

/**
 * This function iterates over all running experiments that are relevant to
 * this user and assigns an alternative for each of them. It also removes old
 * cookies (that is, information about experiments that are no longer running).
 *
 * @param params
 *               A list of pairs with all the GET params that are relevant for
 *               A/B testing.
 */
NelioAB.user.loadEnvironmentAndSetAlternatives = function( params ) {
	var cookieDetails = ';expires=' + NelioAB.cookies.EXPIRES_IN_THREE_MONTHS + ';path=/';

	// ***************************************************************************
	// First, we load the appropriate environment
	NelioAB.user.loadEnvironment( params );


	// ***************************************************************************
	// Then, we remove old cookies
	var allCookies = document.cookie.split(';');
	for (var c = 0; c <= allCookies.length; ++c) {
		var aux = allCookies[c];
		if (aux == undefined) continue;
		var name = aux.substr(0, aux.indexOf('=')).trim();
		var removeCookie = false;
		if ( NelioAB.cookies.isABCookieName( name ) )
			removeCookie = true;
		for ( var i=0; i < NelioABEnv.ab.length && removeCookie; ++i )
			if ( name == NelioABEnv.ab[i].name )
				removeCookie = false;
		if ( removeCookie )
			NelioAB.cookies.remove(name);
	}

	// ***************************************************************************
	// Next, we create the alternatives taking into account current GET params
	if ( params.length > 0 ) {
		var abForThisPage = false;
		var globalVals = [];

		for ( var i = 0; i < params.length; ++i ) {
			if ( NelioAB.checker.nabPrefix == params[i][0] )
				abForThisPage = params[i][1];
			if ( NelioAB.checker.nabPrefix + 'c' == params[i][0] )
				globalVals.push( ['c', params[i][1]] );
			if ( NelioAB.checker.nabPrefix + 'm' == params[i][0] )
				globalVals.push( ['m', params[i][1]] );
			if ( NelioAB.checker.nabPrefix + 't' == params[i][0] )
				globalVals.push( ['t', params[i][1]] );
			if ( NelioAB.checker.nabPrefix + 'w' == params[i][0] )
				globalVals.push( ['w', params[i][1]] );
		}

		if ( abForThisPage ) {
			var exp = false;
			for ( var i = 0; i < NelioABEnv.ab.length && !exp; ++i ) {
				var pid = NelioABEnv.ab[i].alts[0];
				if ( typeof NelioABEnv.ab[i].pid != 'undefined' )
					pid = NelioABEnv.ab[i].pid;
				if ( NelioABParams.info.currentId == pid )
					exp = NelioABEnv.ab[i];
			}
			if ( exp ) {
				if ( abForThisPage < exp.alts.length ) {
					document.cookie = exp.name + '=' + abForThisPage + cookieDetails;
				}
			}
		}

		for ( var g = 0; g < globalVals.length; ++g ) {
			var type = globalVals[g][0];
			var value = globalVals[g][1];
			var exp = false;
			for ( var i = 0; i < NelioABEnv.ab.length && !exp; ++i )
				if ( NelioABEnv.ab[i].name.indexOf( NelioAB.helpers.getGlobalCookiePrefix( type ) ) == 0 )
					exp = NelioABEnv.ab[i];
			if ( exp ) {
				if ( value < exp.alts.length ) {
					document.cookie = exp.name + '=' + value + cookieDetails;
				}
			}
		}
	}

	// ***************************************************************************
	// Finally, we assign an alternative to the user for each running exp whose
	// alternatives have not yet been assigned
	for ( var i = 0; i < NelioABEnv.ab.length; ++i ) {
		var exp = NelioABEnv.ab[i];
		if ( typeof NelioAB.cookies.get(exp.name) == 'undefined' ) {
			var val = NelioAB.helpers.selectAltRandomly(exp);
			document.cookie = exp.name + '=' + val + cookieDetails;
		}
	}

};


/**
 * This function iterates over all running experiments that are relevant to
 * this user and assigns an alternative for each of them. It also removes old
 * cookies (that is, information about experiments that are no longer running).
 */
NelioAB.user.overwriteAlternatives = function( params ) {
	var cookieDetails = ';expires=' + NelioAB.cookies.EXPIRES_IN_THREE_MONTHS + ';path=/';


};
