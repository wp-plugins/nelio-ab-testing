/**
 * Adding a "trim" function to JavaScript Strings
 */
if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
  }
}

/**
 * Extending jQuery with a couple of new functions
 */
jQuery.fn.extend({

	getFullPath: function () {
		var path = '';
		var elem = this;
		while ( !elem.is('html') ) {
			var node  = elem.get(0);
			var name  = node.nodeName.toLowerCase();
			var id	 = elem.attr('id');
			var clazz = elem.attr('class');
			if ( !elem.is('body') ) {
				if ( typeof id != 'undefined' && id.length > 0 ) {
					name += '#' + id;
					}
				if ( typeof clazz != 'undefined' ) {
					clazz = jQuery.trim( clazz );
					if ( clazz.length > 0 )
						name += '.' + clazz.split(/[\s\n]+/).join('.');
				}
			}
			var siblings = elem.parent().children(name);
			if (siblings.length > 1) name += ':eq(' + siblings.index(node) + ')';
			path = '>' + name + path;
			elem = elem.parent();
		}
		return 'html' + path;
	},

	replaceText: function( search, replace, text_only ) {
		var pattern_found = false;
		var aux = this.each(function(){
			var node = this.firstChild,
				val,
				new_val,
				remove = [];
			if ( node ) {
				do {
					if ( node.nodeType === 3 ) {
						val = node.nodeValue;
						new_val = val.replace( search, replace );
						if ( new_val !== val ) {
							pattern_found = true;
							if ( !text_only && /</.test( new_val ) ) {
								jQuery(node).before( new_val );
								remove.push( node );
							} else {
								node.nodeValue = new_val;
							}
						}
					}
				} while ( node = node.nextSibling );
			}
			remove.length && jQuery(remove).remove();
		});
		return pattern_found;
	},

});


function nelioab_areCookiesEnabled() {
	document.cookie = "__verify=1;path=/";
	var supportsCookies = document.cookie.length > 1 &&
		document.cookie.indexOf("__verify=1") > -1;
	nelioab_delete_cookie("__verify");
	return supportsCookies;
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

function nelioab_clean_cookies() {
	var allCookies = document.cookie.split(';');
	for (var i = 0; i <= allCookies.length; ++i) {
		var cookie = allCookies[i];
		if (cookie == undefined)
			continue;
		var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
		if (cookieName.indexOf("nelioab_") == 0)
			if (cookieName.indexOf("userid") == -1)
				nelioab_delete_cookie(cookieName);
	}
}

function nelioab_delete_cookie( name ) {
	var thePast = new Date(1985, 1, 1);
	document.cookie = name + "=1;path=/;expires=" + thePast.toUTCString();
}

function nelioab_add_hidden_fields_on_forms($) {
	$('input[name="input_nelioab_form_cookies"]').attr('name', 'nelioab_form_cookies');
	$('input[name="input_nelioab_form_current_url"]').attr('name', 'nelioab_form_current_url');

	$('input[name="nelioab_form_cookies"]').attr('value',
		encodeURIComponent( JSON.stringify( nelioab_get_local_cookies() )
			.replace( "'", "%27") )
		);
	$('input[name="nelioab_form_current_url"]').attr('value',
		encodeURIComponent( JSON.stringify( document.URL )
			.replace( "'", "%27") )
		);
}

function nelioab_prepare_links_for_nav_to_external_pages($) {
	$.ajax({
		type:  'POST',
		async: true,
		url:   NelioABGeneric.ajaxurl,
		data: {
			action: 'nelioab_external_page_accessed_action_urls',
			nelioab_cookies: nelioab_get_local_cookies(),
		},
		success: function(data) {
			var ae_hrefs = data.ae_hrefs;
			var regex_hrefs = [];
			for ( var i = 0; i < data.regex_hrefs.length; ++i )
				regex_hrefs[i] = new RegExp( data.regex_hrefs[i] );
			if ( ae_hrefs.length > 0 ) {
				$(document).on('byebye',function(event, elem, href) {
					// Remove trailing slash
					href = href.replace(/\/+$/, '');
					// Remove https
					href = href.replace(/^https?:\/\//, 'http://');
					for ( i=0; i<ae_hrefs.length; ++i ) {
						if ( regex_hrefs[i].test(href) ) {
							elem.attr('target','_blank');
							nelioab_nav_to_external_page($,ae_hrefs[i]);
						}
					}
				});
			}
		}
	});
}

function nelioab_nav($) {
	$.ajax({
		type:  'POST',
		async: true,
		url:   NelioABGeneric.ajaxurl,
		data: {
			action: 'nelioab_send_navigation',
			current_url: document.URL,
			ori_url: document.referrer,
			dest_url: window.location.href,
			nelioab_cookies: nelioab_get_local_cookies(),
		},
	});
}

function nelioab_nav_to_external_page($, external_page_link) {
	$.ajax({
		type:  'POST',
		async: true,
		url:   NelioABGeneric.ajaxurl,
		data: {
			action: 'nelioab_send_navigation',
			current_url: document.URL,
			ori_url: window.location.href,
			dest_url: external_page_link,
			nelioab_cookies: nelioab_get_local_cookies(),
			is_external_page: 'yes',
		},
	});
}

jQuery(document).ready(function() {

	if ( typeof nelioabActivateGoogleTagMgr == 'function' ) {
		setTimeout(nelioabActivateGoogleTagMgr,2000);
	}

	jQuery('a').click(function(e) {
		var target = e.currentTarget;
		var dest = undefined;
		try { dest = target.href; } catch (e) {}
		if ( dest != undefined ) {
			e.type = 'byebye';
			jQuery(document).trigger( e, [ jQuery(this), dest ] );
		}
	});

	jQuery(document).on('submit', 'form', function(e) {
		jQuery(document).trigger( 'byebye', [ jQuery(this), jQuery(this).attr('action') ] );
	});

});
