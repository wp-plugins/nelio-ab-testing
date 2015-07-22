/**
 * Initializing a few vars
 */
var nelioab__phone = { "max":-1, "data":[] };
var nelioab__tablet = { "max":-1, "data":[] };
var nelioab__desktop = { "max":-1, "data":[] };
var nelioab__hd = { "max":-1, "data":[] };

var nelioab__phone_click = { "max":-1, "data":[] };
var nelioab__tablet_click = { "max":-1, "data":[] };
var nelioab__desktop_click = { "max":-1, "data":[] };
var nelioab__hd_click = { "max":-1, "data":[] };

var nelioab__nodata = { "max":0, "data":[] };
var nelioab__show_clicks = false;
var nelioab__current_type = 'desktop';

var nelioabHeatmapScale = 0.75;
var nelioabHoveringDevice = null;
var nelioabSwitchToClickEnabled = true;



jQuery(document).ready(function() {

	/**
	 * This function toggles between click- and heatmaps
	 */
	jQuery("#view-clicks").click(function() {
		if ( !nelioabSwitchToClickEnabled ) return;
		nelioabSwitchToClickEnabled = false;
		nelioab__show_clicks = !nelioab__show_clicks;
		if ( nelioab__show_clicks )
			jQuery("#view-clicks").text( NelioABHeatmapLabels.hm.view );
		else
			jQuery("#view-clicks").text( NelioABHeatmapLabels.cm.view );
		highlightData( nelioab__show_clicks );
		switchHeatmap();
	});


	/**
	 * This function changes the current resolution
	 */
	jQuery("#devices a").each(function() {
		jQuery(this).mouseenter(function() {
			nelioabHoveringDevice = jQuery(this);
			var size = jQuery(this).attr('data-viewport').split('x');
			var w=size[0], h=size[1], sw=w, sh=h;
			if ( w != 'auto' ) { sw=Math.ceil(w*nelioabHeatmapScale), sh=Math.ceil(h*nelioabHeatmapScale); }
			jQuery("#phantom").width(sw);jQuery("#phantom").height(sh);jQuery("#phantom").css('margin-left',-(sw/2));
		});
		jQuery(this).mouseleave(function() {
			jQuery("#phantom").width(0);jQuery("#phantom").height(0);jQuery("#phantom").css('margin-left',0);
		});
		jQuery(this).click(function() {
			nelioab__current_type = jQuery(this).attr('id');
			switchHeatmap();
			jQuery("#devices .active").each(function() { jQuery(this).removeClass("active"); } );
			var size = jQuery(this).attr('data-viewport').split('x');
			scaleTo( size[0], size[1],nelioabHeatmapScale );
			jQuery(this).addClass("active");
		});
	});

});// end of document.ready

/**
 * This function makes sure the iframe is properly scaled.
 */
function scaleTo(w,h,scale) {
	var sw=w, sh=h;
	if ( w != 'auto' ) { sw=Math.ceil(w*scale), sh=Math.ceil(h*scale); }
	if ( w == 'auto' ) {
		jQuery("#wrapper > iframe").css('width','100%');jQuery("#wrapper > iframe").css('height','100%');
		jQuery("#wrapper > iframe").removeClass("scaled");
		jQuery("#wrapper").css('width','100%');jQuery("#wrapper").css('height','100%');
		jQuery("#wrapper").css('margin-top','0px');
	} else {
		jQuery("#wrapper > iframe").css('width',w);jQuery("#wrapper > iframe").css('height',h);
		jQuery("#wrapper > iframe").addClass("scaled");
		jQuery("#wrapper").css('width',sw);jQuery("#wrapper").css('height',sh);
		jQuery("#wrapper").css('margin-top','25px');
	}
}


/**
 * This function changes the color of resolution selectors, depending on
 * whether they have data or not.
 */
function highlightData(isClick) {
	var values;
	if ( isClick ) values = NelioABHeatmapLabels.cm;
	else values = NelioABHeatmapLabels.hm;
	if ( nelioab__pre_phone_click.max > 0 ) jQuery("#mobile").attr('title',values.phone).removeClass("disabled");
	else jQuery("#mobile").attr('title',values.phoneNo).addClass("disabled");
	if ( nelioab__pre_tablet_click.max > 0 ) jQuery("#tablet").attr('title',values.tablet).removeClass("disabled");
	else jQuery("#tablet").attr('title',values.tabletNo).addClass("disabled");
	if ( nelioab__pre_desktop_click.max > 0 ) jQuery("#desktop").attr('title',values.desktop).removeClass("disabled");
	else jQuery("#desktop").attr('title',values.desktopNo).addClass("disabled");
	if ( nelioab__pre_hd_click.max > 0 ) jQuery("#hd").attr('title',values.hd).removeClass("disabled");
	else jQuery("#hd").attr('title',values.hdNo).addClass("disabled");
}



/**
 * Let's start by changing stuff!
 */
function switchHeatmap() {
	document.getElementById('content').contentWindow.clearHeatmapObject();
	jQuery("#builder").show();
	setTimeout('buildAndSwitchHeatmap()', 400);
}

function buildAndSwitchHeatmap() {
	switch( nelioab__current_type ) {
		case 'mobile':
			if ( !nelioab__show_clicks ) buildHeatmap( nelioab__pre_phone, nelioab__phone);
			else buildHeatmap( nelioab__pre_phone_click, nelioab__phone_click );
			break;
		case 'tablet':
			if ( !nelioab__show_clicks ) buildHeatmap( nelioab__pre_tablet, nelioab__tablet);
			else buildHeatmap( nelioab__pre_tablet_click, nelioab__tablet_click );
			break;
		case 'desktop':
			if ( !nelioab__show_clicks ) buildHeatmap( nelioab__pre_desktop, nelioab__desktop);
			else buildHeatmap( nelioab__pre_desktop_click, nelioab__desktop_click );
			break;
		case 'hd':
			if ( !nelioab__show_clicks ) buildHeatmap( nelioab__pre_hd, nelioab__hd);
			else buildHeatmap( nelioab__pre_hd_click, nelioab__hd_click);
			break;
		default:
			// Nothing to be done, here
	}
}

jQuery(document).on('heatmap-built', function() {
	var nelioabHeatmapObject;
	var key = '#' + nelioab__current_type;
	var size = jQuery(key).attr('data-viewport').split('x');
	var w=size[0], h=size[1];
	switch( nelioab__current_type ) {
		case 'mobile':
			nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
			jQuery("#builder").fadeOut();
			if ( nelioab__show_clicks ) {
				jQuery("#visitors-count").html( nelioab__pre_phone_click.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__phone_click );
			}
			else {
				jQuery("#visitors-count").html( nelioab__pre_phone.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__phone );
			}
			break;
		case 'tablet':
			nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
			jQuery("#builder").fadeOut();
			if ( nelioab__show_clicks ) {
				jQuery("#visitors-count").html( nelioab__pre_tablet_click.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__tablet_click );
			}
			else {
				jQuery("#visitors-count").html( nelioab__pre_tablet.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__tablet );
			}
			break;
		case 'desktop':
			nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
			jQuery("#builder").fadeOut();
			if ( nelioab__show_clicks ) {
				jQuery("#visitors-count").html( nelioab__pre_desktop_click.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__desktop_click );
			}
			else {
				jQuery("#visitors-count").html( nelioab__pre_desktop.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__desktop );
			}
			break;
		case 'hd':
			nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
			jQuery("#builder").fadeOut();
			if ( nelioab__show_clicks ) {
				jQuery("#visitors-count").html( nelioab__pre_hd_click.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__hd_click );
			}
			else {
				jQuery("#visitors-count").html( nelioab__pre_hd.views );
				nelioabHeatmapObject.store.setDataSet( nelioab__hd );
			}
			break;
		default:
			nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject();
			nelioabHeatmapObject.store.setDataSet( nelioab__nodata );
	}
	nelioabSwitchToClickEnabled = true;
});

function maxAcceptableValue( src ) {
	var maxs = [];
	var mean = 0;
	var median = 0;
	var maxAcceptableValue = 999999;

	var sum = 0;
	for ( var path in src.data ) {
		maxs.push( src.data[path].max );
		sum += src.data[path].max;
	}
	maxs = maxs.sort(function(a,b){return b-a});

	mean = Math.floor(sum/maxs.length);
	median = maxs[Math.floor(maxs.length/2)];

	var removing = 0;
	var onePerCent = Math.floor(maxs.length * 0.01);
	while ( mean > median * 1.6 && removing < onePerCent ) {
		var size = maxs.length-(removing+1);
		sum -= maxs[removing];
		mean = Math.floor(sum/size);
		maxAcceptableValue = maxs[removing+1];
		removing++;
	}

	var maxValue = maxAcceptableValue * 2;
	if ( maxs.length > 0 && maxValue > maxs[0] )
		maxValue = maxs[0];

	return {maxAcceptableValue: maxAcceptableValue, maxValue: maxValue};
}

function buildHeatmap( src, dest ) {
	if ( dest.max == -1 ) {
		var aux = function() {
			doBuildHeatmap(src,dest);
			jQuery(document).unbind('nelioab-scroll-done', aux);
		};
		jQuery(document).on('nelioab-scroll-done', aux);
		document.getElementById('content').contentWindow.nelioabLoadScrollableElements();
	}
	else {
		jQuery(document).trigger('heatmap-built');
	}
}

function doBuildHeatmap( src, dest ) {
	dest.max = 0;
	var data = [];

	var values = maxAcceptableValue( src );
	var maxVal = values.maxValue;
	var maxAccVal = values.maxAcceptableValue;

	var scaleForBiggerValues = Math.floor( maxAccVal * 0.2 );
	if ( maxVal == maxAccVal )
		maxAccVal--;

	for( var path in src.data ) {
		var partial_hm = src.data[path];
		var elem;
		try {
			elem = jQuery(path, document.getElementById('content').contentWindow.document);
		}
		catch ( e ) {
			continue;
		}

		var pl = elem.css('padding-left');   if ( pl == undefined ) pl = "0";
		var pr = elem.css('padding-right');  if ( pr == undefined ) pr = "0";
		var pt = elem.css('padding-top');    if ( pt == undefined ) pt = "0";
		var pb = elem.css('padding-bottom'); if ( pb == undefined ) pb = "0";

		pl = Math.round( pl.replace(/[^0-9\.]/g,'') );
		pr = Math.round( pr.replace(/[^0-9\.]/g,'') );
		pt = Math.round( pt.replace(/[^0-9\.]/g,'') );
		pb = Math.round( pb.replace(/[^0-9\.]/g,'') );

		if ( !elem.is(':visible') )
			continue;
		var w = elem.width() + pl + pr;
		var h = elem.height() + pt + pb;
		var offset_x = Math.round(elem.offset().left) - pl;
		var offset_y = Math.round(elem.offset().top) - pt;
		for ( var i = 0; i < partial_hm.data.length; ++i ) {
			var val = partial_hm.data[i];
			var theCount = val.count;
			if ( theCount > maxAccVal ) {
				theCount = Math.floor( (theCount-maxAccVal)/(maxVal-maxAccVal) ) * scaleForBiggerValues + maxAccVal;
			}
			var x = offset_x + Math.round( w * val.x );
			var y = offset_y + Math.round( h * val.y );
			if ( !isNaN(x) && !isNaN(y) ) {
				count = addPointCount( data, x, y, theCount );
				if ( theCount > dest.max )
					dest.max = theCount;
			}
		}
	}
	dest.data = data;
	jQuery(document).trigger('heatmap-built');
}

function addPointCount( data, x, y, count ) {
	var new_count = 0;
	var found = false;
	var elem;
	for( var i = 0; i < data.length; ++i ) {
		var elem = data[i];
		if ( elem.x == x && elem.y == y ) {
			elem.count += count;
			new_count = elem.count;
			found = true;
			break;
		}
	}
	if ( !found ) {
		elem = { 'x': x, 'y': y, 'count': count };
		new_count = elem.count;
		data.push( elem );
	}
	return new_count;
}


