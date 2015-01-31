NelioAB.helpers = {};

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

NelioAB.helpers.showBody = function() {
	try {
		document.getElementsByTagName('head')[0].removeChild(NelioAB.checker.styleNode);
		jQuery.holdReady( false );
		jQuery(document).trigger('nelioab-gtm-call');
	}
	catch( e ) {}
};

NelioAB.helpers.ure = function() {
	jQuery.ajax({
		type:  'POST',
		async: true,
		url:   NelioABParams.ajaxurl,
		data: { action:'nelioab_ure' }
	});
};

NelioAB.helpers.extractGetParams = function( query ) {
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
};

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
	if ( newParams.length > 0 )
		stringParams = "?" + newParams[0][0] + "=" + encodeURIComponent(newParams[0][1]);
	for ( var i = 1; i < newParams.length; ++i )
		stringParams += "&" + newParams[i][0] + "=" + encodeURIComponent(newParams[i][1]);

	priority += stringParams;

	return priority;
};

NelioAB.helpers.addHiddenFormFieldsOnSubmission = function() {
	var setHiddenFieldsInForm = function( $form ) {
		if ( $form.length == 0 ) return;

		if ( typeof $form.data('has_nelioab_fields') == 'undefined' ) {
			// First Hidden Field
			$cookiesField = jQuery('<input type="hidden" name="nelioab_form_cookies" />');
			$cookiesField.attr('value',
				encodeURIComponent( JSON.stringify( NelioAB.cookies.list() )
					.replace( /'/g, "%27") )
				);
			$form.append( $cookiesField );

			// Second Hidden Field
			$currentUrlField = jQuery('<input type="hidden" name="nelioab_form_current_url" />');
			$currentUrlField.attr('value',
				encodeURIComponent( JSON.stringify( document.URL )
					.replace( /'/g, "%27") )
				);
			$form.append( $currentUrlField );

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

NelioAB.helpers.prepareOutwardsNavigationTracking = function() {
	var ae_hrefs = NelioABParams.sync.outwardsNavigationUrls.ae_hrefs;
	var regex_hrefs = [];
	for ( var i = 0; i < NelioABParams.sync.outwardsNavigationUrls.regex_hrefs.length; ++i )
		regex_hrefs[i] = new RegExp( NelioABParams.sync.outwardsNavigationUrls.regex_hrefs[i] );
	if ( ae_hrefs.length > 0 ) {
		jQuery(document).on('byebye', function(event, elem, href) {
			// Remove trailing slash
			href = href.replace(/\/+$/, '');
			// Remove https
			href = href.replace(/^https?:\/\//, 'http://');
			for ( i=0; i<ae_hrefs.length; ++i ) {
				if ( regex_hrefs[i].test(href) ) {
					elem.attr('target','_blank');
					NelioAB.helpers.navigateOutwards(ae_hrefs[i]);
				}
			}
		});
	}
};

NelioAB.helpers.prepareNavObject = function() {
	var data = {
			siteId: NelioABParams.site,
			customerId: NelioABParams.customer,
			user: NelioAB.cookies.get('nelioab_userid'),
			session: NelioAB.cookies.get('nelioab_session')
		};

	if ( NelioABParams.sync.nav.activeCss.length > 0 )
		data.activeCSS = NelioABParams.sync.nav.activeCss;
	if ( NelioABParams.sync.nav.activeTheme.length > 0 )
		data.activeTheme = NelioABParams.sync.nav.activeTheme;
	if ( NelioABParams.sync.nav.activeWidget.length > 0 )
		data.activeWidget = NelioABParams.sync.nav.activeWidget;

	return data;
}

NelioAB.helpers.navigate = function() {
	var data = NelioAB.helpers.prepareNavObject();
	data.referer = NelioABParams.sync.nav.referer;
	data.origin = NelioABParams.sync.nav.refererId;
	data.actualOrigin = NelioABParams.sync.nav.refererActualId;
	data.destination = NelioABParams.sync.nav.currentId;
	data.actualDestination = NelioABParams.sync.nav.currentActualId;
	data.s = NelioABParams.sync.nav.rsec;

	jQuery.ajax({
		type:  'POST',
		async: true,
		url:   NelioAB.backend.url + '/rn',
		data: data
	});

};

NelioAB.helpers.navigateOutwards = function(dest) {
	var data = NelioAB.helpers.prepareNavObject();
	data.referer = document.URL;
	data.origin = NelioABParams.sync.nav.currentId;
	data.actualOrigin = NelioABParams.sync.nav.currentActualId;
	data.destination = dest;
	data.actualDestination = dest;
	data.s = NelioABParams.sync.nav.osec;

	jQuery.ajax({
		type:  'POST',
		async: true,
		url:   NelioAB.backend.url + '/on',
		data: data
	});

};

NelioAB.helpers.sendHeadlineViews = function() {
	var data = {
			siteId: NelioABParams.site,
			customerId: NelioABParams.customer,
			user: NelioAB.cookies.get('nelioab_userid'),
			headlines: NelioABParams.sync.headlines.list,
			s: NelioABParams.sync.headlines.sec
		};

	jQuery.ajax({
		type:  'POST',
		async: true,
		url:   NelioAB.backend.url + '/hl',
		data: data
	});

}

NelioAB.helpers.trackAndSync = function() {

	if ( 'y' == NelioABParams.misc.qc )
		NelioAB.checker.q();

	if ( 'y' == NelioABParams.misc.ure )
		NelioAB.helpers.ure();

	if ( 'n' == NelioABParams.misc.hq )
		return;

	// Track heatmaps
	if ( 'DONT_TRACK_HEATMAPS' != NelioABParams.sync.heatmaps.action )
		NelioAB.heatmaps.track();

	// Send the navigation to the current page
	if ( NelioABParams.sync.nav.isRelevant )
		NelioAB.helpers.navigate();

	// Send all headline views
	jQuery(document).ready(function() {
		if ( NelioABParams.sync.headlines.list.length > 0 ) {
			NelioAB.helpers.sendHeadlineViews();
			if ( !NelioABParams.sync.nav.isRelevant ) {
				NelioABParams.sync.nav.isRelevant = true;
				NelioAB.helpers.navigate();
			}
		}

		// Prepare to track outwards navigations
		NelioAB.helpers.prepareOutwardsNavigationTracking();

		// Prepare to track form submissions
		NelioAB.helpers.addHiddenFormFieldsOnSubmission();
	});

};


NelioAB.helpers.getNavigator = function(){
	var ua = navigator.userAgent, tem,
		M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
	if (/trident/i.test(M[1])) {
		tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
		return 'IE ' + (tem[1] || '');
	}
	if (M[1] === 'Chrome') {
		tem = ua.match(/\bOPR\/(\d+)/)
		if (tem != null)
			return 'Opera ' + tem[1];
	}
	M = M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
	if ( (tem = ua.match(/version\/(\d+)/i)) != null)
		M.splice(1, 1, tem[1]);
	return M.join(' ');
}


