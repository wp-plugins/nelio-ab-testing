/**
 * This object contains several helper functions.
 */
NelioAB.helpers = {};


/**
 * This function returns whether the current browser is IE8 or IE9.
 *
 * @return whether the current browser is IE8 or IE9.
 */
NelioAB.helpers.isOldIE = function() {
	if ( navigator.appName == 'Microsoft Internet Explorer' && window.XDomainRequest )
		return true;
	else
		return false;
};


/**
 * This function returns whether the current browser is a bot.
 *
 * @return whether the current browser is a bot.
 */
NelioAB.helpers.isBot = function() {
	return /bot|googlebot|crawler|spider|robot|crawling/i.test( navigator.userAgent );
};


/**
 * This function is a wrapper to jQuery.ajax interface. It's used to perform a
 * request to Nelio's Cloud Servers. If the browser in which the request is
 * performed is IE8 or IE9 (see NelioAB.helpers.isOldIE), then the request is
 * not performed to Nelio directly, but to a WordPress proxy.
 *
 * @param object obj
 *               the ajax object as required by jQuery. It type attribute is
 *               not required, for this function will set it to POST.
 */
NelioAB.helpers.remotePost = function( obj ) {
	obj.type = 'POST';
	if ( NelioAB.helpers.isOldIE() ) {
		obj.data = {
			data: jQuery.param( obj.data ),
			originalRequestUrl: obj.url
		};
		obj.url = NelioABParams.ieUrl;
	}
	jQuery.ajax( obj );
};


/**
 * This function returns whether the current page should load alternative
 * content or not. A page might require alternative content if:
 *  - The page is under test
 *  - There's a gobal experiment running
 *  - There's a headline experiment
 *
 * @return whether the current page should load alternative content or not.
 */
NelioAB.helpers.isAltLoadingRequired = function() {
	if ( NelioABBasic.settings.consistency )
		return true;
	if ( '*' == NelioABEnv.tids[0] )
		return true;
	for ( var i = 0; i < NelioABEnv.tids.length; ++i )
		if ( NelioABParams.info.currentId == NelioABEnv.tids[i] )
			return true;
	return false;
};


/**
 * This function returns whether the current page is relevant somehow and,
 * therefore, whether we have to send a navigation or not.
 *
 * @return whether the navigation is relevant or not.
 */
NelioAB.helpers.isNavigationRelevant = function() {
	if ( NelioABEnv.rids.length > 0 && '*' == NelioABEnv.rids[0] )
		return true;
	for ( var i = 0; i < NelioABEnv.rids.length; ++i )
		if ( NelioABEnv.rids[i] == NelioABParams.info.currentId )
			return true;
	for ( var i = 0; i < NelioABEnv.goals.regularNavigationIds.length; ++i )
		if ( NelioABEnv.goals.regularNavigationIds[i] == NelioABParams.info.currentId )
			return true;
	return false;
};


/**
 * This function hides the body during our script checks. This way, if an
 * alternative content has to be loaded, the user won't notice.
 */
NelioAB.helpers.hideBody = function() {
	NelioAB.checker.styleNode = document.createElement('style');
	NelioAB.checker.styleNode.setAttribute('type', 'text/css');
	var text = 'html{display:none !important;} body{display:none !important;}';
	if (NelioAB.checker.styleNode.styleSheet) {
		// IE
		NelioAB.checker.styleNode.styleSheet.cssText = '';
	} else {
		// Other browsers
		var textnode = document.createTextNode(text);
		NelioAB.checker.styleNode.appendChild(textnode);
	}
	document.getElementsByTagName('head')[0].appendChild(NelioAB.checker.styleNode);
};


/**
 * This function makes the body visible.
 */
NelioAB.helpers.showBody = function() {
	try {
		document.getElementsByTagName('head')[0].removeChild(NelioAB.checker.styleNode);
		jQuery.holdReady( false );
	}
	catch( e ) {}
};


/**
 * It removes the hash anchor of the given URL (if any).
 *
 * @param url A URL.
 *
 * @return The same given URL, without the hash anchor (if any).
 */
NelioAB.helpers.extractHashAnchor = function( url ) {
	var aux = url.indexOf( '#' );
	if ( -1 === aux )
		return '';
	var hash = url.substring( aux, url.length );
	aux = hash.indexOf( '?' );
	if ( -1 !== aux )
		hash = hash.substring( 0, aux-1 );
	return hash;
};

/**
 * This function processes a list of GET parameters and returns a list of pairs
 * with those params.
 *
 * @param query
 *            A string in the form "?p1=v1&p2=v2&..." or an empty string.
 *
 * @return a list of pairs <p, v> with all the parameters of the original URL.
 */
NelioAB.helpers.extractGetParams = function( query ) {
	var params = [];
	if ( query.length == 0 )
		return params;

	var match,
		pl     = /\+/g,  // Regex for replacing addition symbol with a space
		search = /([^&=]+)=?([^&]*)/g,
		decode = function (s) { return decodeURIComponent(s.replace(pl, ' ')); },
		query  = query.substring(1);

	while (match = search.exec(query))
		params.push( [decode(match[1]), decode(match[2])] );

	return params;
};


/**
 * Given two URLs A and B, this function merges all the GET params of B into A.
 * Any GET param that appears in both URLs will take the value it has on URL A.
 *
 * @param priority
 *             the first URL.
 * @param priority
 *             the second URL.
 *
 * @return a new URL that looks like URL A, but which includes all GET params
 *         from B that were not in A.
 */
NelioAB.helpers.mergeUrlParams = function( priority, inherit ) {
	// Extract URL params
	var uParams = [];
	aux = inherit.indexOf('?');
	if ( -1 != aux )
		uParams = NelioAB.helpers.extractGetParams( inherit.substring(aux) );

	// Extract PERMALINK params and set
	//  - stringParams to something like "?..."
	//  - priority to something like http://..../ without GET params
	var pParams = [];
	aux = priority.indexOf('?');
	if ( -1 != aux ) {
		pParams = NelioAB.helpers.extractGetParams( priority.substring( aux ) );
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
	if ( newParams.length > 0 ) {
		stringParams = '?' + newParams[0][0];
		var aux = '=' + encodeURIComponent(newParams[0][1]);
		if ( aux.length > 1 )
			stringParams += aux;
	}
	for ( var i = 1; i < newParams.length; ++i ) {
		stringParams += '&' + newParams[i][0];
		var aux = '=' + encodeURIComponent(newParams[i][1]);
		if ( aux.length > 1 )
			stringParams += aux;
	}

	priority += stringParams;

	return priority;
};


/**
 * This function adds some relevant hidden fields in forms right before they're
 * submitted. This way, Nelio A/B Testing can kick in when the data is received
 * in WordPress.
 */
NelioAB.helpers.addHiddenFormFieldsOnSubmission = function() {
	var setHiddenFieldsInForm = function( $form ) {
		if ( $form.length == 0 ) return;

		if ( typeof $form.data('has_nelioab_fields') == 'undefined' ) {
			// First Hidden Field
			var aux = NelioAB.checker.generateAjaxParams();
			$field = jQuery('<input type="hidden" name="nelioab_form_env" />');
			$field.attr('value',
				encodeURIComponent( JSON.stringify( aux.nelioab_env )
					.replace( /'/g, '%27') )
				);
			$form.append( $field );

			// Second Hidden Field
			$field = jQuery('<input type="hidden" name="nelioab_current_id" />');
			$field.attr('value', NelioABParams.info.currentId);
			$form.append( $field );

			// Third Hidden Field
			$field = jQuery('<input type="hidden" name="nelioab_current_actual_id" />');
			$field.attr('value', NelioABParams.info.currentActualId);
			$form.append( $field );

			// Fourth Hidden Field
			$field = jQuery('<input type="hidden" name="nelioab_userid" />');
			$field.attr('value', NelioAB.user.id() );
			$form.append( $field );

			// Write down that fields have been added
			$form.data('has_nelioab_fields', 'yes');
		}

	};

	jQuery(document).on('click', function(e) {
		if ( typeof e.target == 'undefined' ) return;
		setHiddenFieldsInForm( jQuery(e.target).closest('form') );
	});

	jQuery(document).on('onkeyup', function(e) {
		if ( typeof e.target == 'undefined' ) return;
		if ( 13 != e.keyCode ) return;
		setHiddenFieldsInForm( jQuery(e.target).closest('form') );
	});

};


/**
 * When the byebye event is received, this function will check whether the user
 * clicked on a link or submitted a form whose URL is one of the external URLs.
 * If it is, we'll send the information to AE.
 */
NelioAB.helpers.prepareOutwardsNavigationTracking = function() {
	var ae_hrefs = NelioABEnv.goals.outwardsNavigationUrls.ae_hrefs;
	var regex_hrefs = [];
	for ( var i = 0; i < NelioABEnv.goals.outwardsNavigationUrls.regex_hrefs.length; ++i )
		regex_hrefs[i] = new RegExp( NelioABEnv.goals.outwardsNavigationUrls.regex_hrefs[i] );
	if ( ae_hrefs.length > 0 ) {
		jQuery(document).on('byebye', function(event, elem, href) {
			// Remove trailing slash
			href = href.replace(/\/+$/, '');
			// Remove https
			href = href.replace(/^https?:\/\//, 'http://');
			for ( i=0; i<ae_hrefs.length; ++i ) {
				if ( regex_hrefs[i].test(href) ) {
					if ( NelioABParams.misc.useOutwardsNavigationsBlank )
						elem.attr('target','_blank');
					NelioAB.helpers.navigateOutwards(ae_hrefs[i]);
				}
			}
		});
	}
};


/**
 * This function detects when an element has been clicked and checks whether it
 * is relevant for click events or not. If it is, it'll send the relevant
 * events to AE.
 */
NelioAB.helpers.prepareClickOnElementTracking = function() {
	jQuery(document).on( 'click', NelioAB.helpers.clickFunctions.maybeSendClickElementEvent );
};


/**
 * This function returns the basic navigation object that will be processed by
 * AE.
 *
 * @return the basic navigation object that will be processed by AE.
 */
NelioAB.helpers.prepareNavObject = function() {
	var data = {
			siteId:     NelioABParams.site,
			customerId: NelioABParams.customer,
			user:       NelioAB.user.id(),
			session:    NelioAB.user.session()
		};

	data.activeCSS    = NelioAB.user.getGlobalAltValue( 'css' );
	data.activeMenu   = NelioAB.user.getGlobalAltValue( 'menu' );
	data.activeTheme  = NelioAB.user.getGlobalAltValue( 'theme' );
	data.activeWidget = NelioAB.user.getGlobalAltValue( 'widget' );

	return data;
}


/**
 * This function sends a navigation to AE.
 */
NelioAB.helpers.navigate = function() {
	var refererInfo = NelioAB.helpers.referer;

	var data = NelioAB.helpers.prepareNavObject();
	data.referer = document.referrer;
	data.origin = refererInfo.id;
	data.actualOrigin = refererInfo.actualId;
	data.destination = NelioABParams.info.currentId;
	data.actualDestination = NelioABParams.info.currentActualId;
	data.envHash = NelioABBasic.settings.envHash;

	NelioAB.helpers.remotePost({
		async: true,
		url:   NelioAB.backend.url + '/rn',
		data:  data
	});

};


/**
 * This function sends an external navigation to AE. The referer will be the
 * current page and the destination page is the external page.
 *
 * @param dest
 *             The URL of the page to which we're going, as expected by AE.
 */
NelioAB.helpers.navigateOutwards = function(dest) {
	var data = NelioAB.helpers.prepareNavObject();
	data.referer = document.URL;
	data.origin = NelioABParams.info.currentId;
	data.actualOrigin = NelioABParams.info.currentActualId;
	data.destination = dest;
	data.actualDestination = dest;
	data.envHash = NelioABBasic.settings.envHash;

	var async = NelioABParams.misc.useOutwardsNavigationsBlank;
	NelioAB.helpers.remotePost({
		async: async,
		url:   NelioAB.backend.url + '/on',
		data:  data
	});

};


/**
 * This function is called if Headlines where added using an AJAX call. It
 * sends these new headlines to AE.
 */
NelioAB.helpers.sendNewHeadlineViews = function() {
	if ( NelioABParams.sync.headlines.length > 0 )
		NelioAB.helpers.sendHeadlineViews();
};


/**
 * This function sends to AE which headlines where A/B tested in the current
 * page.
 */
NelioAB.helpers.sendHeadlineViews = function() {
	var data = {
			siteId: NelioABParams.site,
			customerId: NelioABParams.customer,
			user: NelioAB.user.id(),
			headlines: NelioABParams.sync.headlines,
			envHash: NelioABBasic.settings.envHash
		};

	NelioAB.helpers.remotePost({
		async: true,
		url:   NelioAB.backend.url + '/hl',
		data:  data
	});

};


/**
 * This function sends information about the element that has been clicked.
 *
 * @param ce
 *            The click event (as defined in AE) that matches user's click.
 */
NelioAB.helpers.sendClickElementEvent = function(ce) {
	var data = {
			siteId: NelioABParams.site,
			customerId: NelioABParams.customer,
			eventId: ce.id,
			user: NelioAB.user.id(),
			page: NelioABParams.info.currentId,
			actualPage: NelioABParams.info.currentActualId,
			envHash: NelioABBasic.settings.envHash
		};

	data.activeCSS    = NelioAB.user.getGlobalAltValue( 'css' );
	data.activeMenu   = NelioAB.user.getGlobalAltValue( 'menu' );
	data.activeTheme  = NelioAB.user.getGlobalAltValue( 'theme' );
	data.activeWidget = NelioAB.user.getGlobalAltValue( 'widget' );

	NelioAB.helpers.remotePost({
		async: true,
		url:   NelioAB.backend.url + '/ce',
		data:  data
	});

};


/**
 * This function triggers user tracking. Depending on the current state, it
 * will track different things (for instance, heatmap info, send navigations,
 * sync headlines, and so on).
 */
NelioAB.helpers.track = function() {
	// Send the navigation to the current page
	if ( NelioAB.helpers.isNavigationRelevant() )
		NelioAB.helpers.navigate();

	jQuery(document).ready(function() {
		// Track heatmaps
		if ( 'DONT_TRACK_HEATMAPS' != NelioABBasic.settings.hmMode )
			NelioAB.heatmaps.track();

		// Send all headline views
		if ( NelioABParams.sync.headlines.length > 0 )
			NelioAB.helpers.sendHeadlineViews();

		// Prepare to track outwards navigations
		NelioAB.helpers.prepareOutwardsNavigationTracking();

		// Prepare to track clicks on elements in the page
		NelioAB.helpers.prepareClickOnElementTracking();

		// Prepare to track form submissions
		NelioAB.helpers.addHiddenFormFieldsOnSubmission();
	});

};


/**
 * This function returns one of the options randomly. The algorithm used to
 * select that option depends on the site settings. The original version
 * must be the first option.
 *
 * @param exp
 *        an experiment tuple that contains its {name,alts,winner}.
 * @return one of the options (exp's alts) randomly.
 */
NelioAB.helpers.selectAltRandomly = function(exp) {
	var options = exp.alts;
	var winner  = exp.winner;

	var numOfOptions = options.length;
	var optionToIgnore = undefined;

	var algorithm = NelioABBasic.settings.algorithm;
	if ('p' == algorithm) {
		var originalVersion = options[0];
		var originalPercentage = NelioABBasic.settings.oriPrio;
		var rand = Math.floor(Math.random()*101);
		if (rand <= originalPercentage) {
			for ( i = 0; i < options.length; ++i )
				if ( options[i] == originalVersion )
					return i;
		}
		// The original should not be used
		--numOfOptions;
		optionToIgnore = originalVersion;
	}
	else if ('g' == algorithm) {
		if (typeof winner != 'undefined' && winner.length > 0) {
			var exploitationPercentage = NelioABBasic.settings.exploitPerc;
			var rand = Math.floor(Math.random()*101);
			if (rand <= exploitationPercentage) {
			for ( i = 0; i < options.length; ++i )
				if ( options[i] == winner )
					return i;
			}
			// The current winner option should not be used
			--numOfOptions;
			optionToIgnore = winner;
		}
	}
	else {
		// Nothing to be done here.
	}

	// Randomize
	var index = Math.floor(Math.random()*numOfOptions);
	if (options[index] == optionToIgnore)
		index = numOfOptions;
	return index;
};


/**
 * This function sets the current page ID (and actual ID) as the referer that
 * will be used for the page the user is about to visit.
 */
NelioAB.helpers.setRefererCookie = function() {
	var cookieDetails = ';expires=' + NelioAB.cookies.EXPIRES_IN_TWO_MINUTES + ';path=/';
	try {
		var value = NelioABParams.info.currentId + ':' +
			NelioABParams.info.currentActualId;
		document.cookie = 'nelioab_referer_ids=' + value + cookieDetails;
	}
	catch ( e ) {
		console.error('Unable to set referer cookie');
	}
};


/**
 * This variable stores information about the page I came from. In principle,
 * I'll be using window.history.state for storing and accessing this
 * information, but I need a fallback in case window.history.state is not
 * available.
 */
NelioAB.helpers.referer = false;


/**
 * This function returns the last page (ID and actual ID) I set as a possible
 * referer.
 *
 * @param string url the (current?) URL.
 *
 * @return the last page (ID and actual ID) I set as a possible referer.
 */
NelioAB.helpers.updateRefererInformation = function( url ) {
	var res = {'id' : -102, 'actualId' : -102 };
	if ( !NelioAB.helpers.referer ) {
		try {
			if ( window.history.state != null && typeof window.history.state.referer != 'undefined' ) {
				res = window.history.state.referer;
			}
			else {
				var cookie = NelioAB.cookies.get('nelioab_referer_ids');
				cookie = cookie.split(':');
				res = {'id' : cookie[0], 'actualId' : cookie[1] };
			}
		}
		catch (e) {}
	}
	else {
		res = NelioAB.helpers.referer;
	}
	NelioAB.helpers.referer = res;
	try {
		window.history.replaceState({referer:res}, '', url);
	}
	catch (e) {}
};



/**
 * This function processes the JSON resturned by an AJAX call that triggered
 * Nelio A/B Testing. If that's the case, then the JSON will contain at least
 * one object named "nelioab".
 *
 * @param json
 *              The JSON object returned by an AJAX call.
 */
NelioAB.helpers.processAjaxResult = function(json) {
	if ( typeof json == 'undefined' || typeof json.nelioab == 'undefined' )
		return;

	// Sync new headlines
	if ( typeof json.nelioab.headlines != 'undefined' )
		NelioABParams.sync.headlines = json.nelioab.headlines;
	NelioAB.helpers.sendNewHeadlineViews();
};


/**
 * This function returns the prefix for regular AB cookies.
 *
 * @return the prefix for regular AB cookies.
 */
NelioAB.helpers.getRegularCookiePrefix = function() {
	return 'nelioab_altexp_';
};


/**
 * This function returns the prefix for a global cookie whose type is type.
 *
 * @param type
 *             The type of the global cookie: [css | menu | theme | widget]
 *
 * @return the prefix for a global cookie whose type is type.
 */
NelioAB.helpers.getGlobalCookiePrefix = function( type ) {
	if ( 'css' == type || 'c' == type )
		return 'nelioab_cglobal_altexp_';
	if ( 'menu' == type || 'm' == type )
		return 'nelioab_mglobal_altexp_';
	if ( 'theme' == type || 't' == type )
		return 'nelioab_tglobal_altexp_';
	if ( 'widget' == type || 'w' == type )
		return 'nelioab_wglobal_altexp_';
	return '';
};


/**
 * Detects whether the script is running in an iframe and, if so, if the
 * tracked page is already tracked in one of the parent pages.
 *
 * @return boolean whether the script is running in an iframe and loading
 *                 duplicated content.
 */
NelioAB.helpers.isDuplicatedFrame = function() {
	if ( window == window.top )
		return false;
	var MAX_ATTEMPTS = 5;
	var ancestor = window.parent;
	for ( var i = 0; i < MAX_ATTEMPTS; ++i ) {
		if ( typeof ancestor.NelioABParams !== 'undefined' ) {
			if ( ancestor.NelioABParams.info.currentId == NelioABParams.info.currentId ) {
				return true;
			}
		}
		if ( ancestor == window.top )
			break;
		ancestor = ancestor.parent;
	}
	return false;
};


/**
 * This function adds some relevant hooks and events. On the one hand, it adds
 * the "byebye" event when the user clicks on a link and/or submits a form. On
 * the other hand, it adds an "onbeforeunload" function that sets the referer
 * cookie with information about the current page.
 */
NelioAB.helpers.addDocumentHooks = function() {
	// ***************************************************************************
	// Adding new event "byebye" when we click on a link and, therefore, we're
	// about to leave the page.
	jQuery(document).on( 'click', NelioAB.helpers.clickFunctions.byebye );


	// ***************************************************************************
	// Adding new event "byebye" when we submit a form and, therefore, we're
	// about to leave the page.
	jQuery(document).on('submit', 'form', function(e) {
		e.type = 'byebye';
		jQuery(document).trigger( e, [ jQuery(this), jQuery(this).attr('action') ] );
	});

	// ***************************************************************************
	// When we're about to leave the page, the current page has to create a
	// cookie with information about it. This way, the page I'm about to access
	// will know I came from here.
	window.onbeforeunload = window.onunload = function() {
		NelioAB.helpers.setRefererCookie();
	};

};


/**
 * This object contains a few function that are linked to click events.
 */
NelioAB.helpers.clickFunctions = {};


/**
 * This function triggers the byebye event when a click occurs.
 */
NelioAB.helpers.clickFunctions.byebye = function(e) {
	// We make sure that the "referer" cookie is set to the current page, so
	// that navigations from the current page have the proper referer. In
	// principle, it should be set using the "onbeforeunload" js hook... but,
	// in case the listener function is overwritten:
	NelioAB.helpers.setRefererCookie();

	var target;
	var dest;

	target = jQuery(e.target).closest('a');
	dest = undefined;
	try { dest = target.attr('href'); } catch (e) {}
	if ( dest != undefined ) {
		e.type = 'byebye';
		jQuery(document).trigger( e, [ target, dest ] );
		return;
	}

	target = jQuery(e.target).closest('area');
	dest = undefined;
	try { dest = target.attr('href'); } catch (e) {}
	if ( dest != undefined ) {
		e.type = 'byebye';
		jQuery(document).trigger( e, [ target, dest ] );
		return;
	}
};


/**
 * This function sends a click element event if required.
 */
NelioAB.helpers.clickFunctions.maybeSendClickElementEvent = function(e) {
	var target = jQuery(e.target);

	for( var i = 0; i < NelioABEnv.goals.clickableElements.length; ++i ) {
		var ce = NelioABEnv.goals.clickableElements[i];
		switch ( ce.mode ) {

			case 'id':
				var id = '#' + ce.value;
				if ( target.attr('id') == ce.value || target.closest(id).length > 0 ) {
					NelioAB.helpers.sendClickElementEvent(ce);
				}
				break;

			case 'css-path':
				var elemsInPath = jQuery(ce.value);
				var found = false;
				elemsInPath.each(function() {
					if ( !found ) {
						var aux = jQuery(this)[0];
						if ( aux == target[0] || target.closest(aux).length > 0 ) {
							found = true;
						}
					}
				});
				if ( found ) {
					NelioAB.helpers.sendClickElementEvent(ce);
				}
				break;

			case 'text-is':
				var value = ce.value.trim().toLowerCase();
				var successfulClick = false;

				// Let's see if we have an element whose value is the text we're searching
				// (for instance, a button)
				if ( target.attr('value') == value ) {
					successfulClick = true;
				}

				// If it isn't, let's look for the current element and/or (some of) its
				// parents
				var aux = target;
				for ( var j = 0; j < 5 && !successfulClick; ++j ) {
					if ( typeof aux == 'undefined' ) break;
					var auxValue = aux.text()
						.replace(/\n/g,' ')
						.replace(/\s+/g,' ')
						.trim()
						.toLowerCase();
					if ( auxValue == value ) {
						successfulClick = true;
					}
					aux = target.parent();
				}

				// If I found the element, let's send the event
				if ( successfulClick ) {
					NelioAB.helpers.sendClickElementEvent(ce);
				}
				break;

		}
	}
};


