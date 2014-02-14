function nelioab_hmdata() {
	this.data = {};
	this.max = 0;
}

function nelioab_hmdata_for_elem() {
	this.data = [];
	this.max = 0;
}

nelioab_hmdata.prototype = {
	addDataPoint: function(path, x, y){
		if(x < 0 || x == undefined || y < 0 || y == undefined)
			return;
		var data = this.data;
		var hm_for_elem = false;
		if ( data[path] != undefined )
			hm_for_elem = data[path];
		if ( !hm_for_elem ) {
			hm_for_elem = new nelioab_hmdata_for_elem();
			this.data[path] = hm_for_elem;
		}
		hm_for_elem.addDataPoint(x,y);
		if(this.max < hm_for_elem.max){
			// max changed, we need to save the new max
			this.max = hm_for_elem.max;
		}
	},
	exportDataSet: function(){
		var data = this.data;
		var exportData = {};
		for ( var k in this.data )
			exportData[k] = data[k].exportDataSet();
		return { max:this.max, data:exportData };
	},
};

nelioab_hmdata_for_elem.prototype = {
	addDataPoint: function(x, y){
		var data = this.data;
		if(!data[x])
			data[x] = [];
		if(!data[x][y])
			data[x][y] = 0;
		// if count parameter is set increment by count otherwise by 1
		data[x][y]+=(arguments.length<3)?1:arguments[2];
		// do we have a new maximum?
		if(this.max < data[x][y]){
			// max changed, we need to save the new max
			this.max = data[x][y];
		}
	},
	exportDataSet: function(){
		var data = this.data;
		var exportData = [];
		for(var one in data){
			// jump over undefined indexes
			if(one === undefined)
				continue;
			for(var two in data[one]) {
				if(two === undefined)
					continue;
				exportData.push({x: one, y: two, count: data[one][two]});
			}
		}
		return { max:this.max, data:exportData };
	},
};

function nelioab_selectDatastore(w) {
	if (w <= 360) return nelioab_phone_data;
	else if (w <= 768) return nelioab_tablet_data;
	else if (w <= 1024) return nelioab_desktop_data;
	else return nelioab_hd_data;
}

function nelioab_selectClickDatastore(w) {
	if (w <= 360) return nelioab_phone_data_click;
	else if (w <= 768) return nelioab_tablet_data_click;
	else if (w <= 1024) return nelioab_desktop_data_click;
	else return nelioab_hd_data_click;
}

var nelioab_actual_data;
var nelioab_actual_data_click;

var nelioab_phone_data   = new nelioab_hmdata();
var nelioab_tablet_data  = new nelioab_hmdata();
var nelioab_desktop_data = new nelioab_hmdata();
var nelioab_hd_data      = new nelioab_hmdata();

var nelioab_phone_data_click   = new nelioab_hmdata();
var nelioab_tablet_data_click  = new nelioab_hmdata();
var nelioab_desktop_data_click = new nelioab_hmdata();
var nelioab_hd_data_click      = new nelioab_hmdata();


function nelioabStartHeatmapTracking() {
	nelioab_actual_data = nelioab_selectDatastore(jQuery(window).width());
	nelioab_actual_data_click = nelioab_selectClickDatastore(jQuery(window).width());

	var body = jQuery(document);
	
	var active = true,
		idle = false,
		focus = true,
		over = false,
		path = 0,
		nx = 0,
		ny = 0,
		simulate = false,
		stop = false,
		touch = false,
		timeout = false;
		
	// activate capture mode
	setInterval(function(){
		if (!stop) {
			active = true;
		}
	}, 80);
	
	// check whether the mouse is idling
	var idlechecker = setInterval(function(){
		if(over && focus && !simulate && !stop && !touch){
			// if it's idling -> start the simulation 
			// and add the last x/y coords
			simulate = setInterval(function(){
				nelioab_actual_data.addDataPoint(path, nx, ny);
			}, 1000);
			
			timeout = setTimeout(function(){
				if(simulate && !stop){
					clearInterval(simulate);
					simulate = false;
					stop = true;
				}
			},10000);
		}
	}, 150);
	
	var add = function( e, isclick ) {
		if ( e.pageX == undefined || e.pageY == undefined ) return;
		var target = jQuery(e.target);

		var pl = target.css('padding-left');   if ( pl == undefined ) pl = "0";
		var pr = target.css('padding-right');  if ( pr == undefined ) pr = "0";
		var pt = target.css('padding-top');    if ( pt == undefined ) pt = "0";
		var pb = target.css('padding-bottom'); if ( pb == undefined ) pb = "0";

		pl = Math.round( pl.replace(/[^0-9\.]/g,'') );
		pr = Math.round( pr.replace(/[^0-9\.]/g,'') );
		pt = Math.round( pt.replace(/[^0-9\.]/g,'') );
		pb = Math.round( pb.replace(/[^0-9\.]/g,'') );

		var width = target.width() + pl + pr;
		var height = target.height() + pt + pb;
		var posX = e.pageX - target.offset().left - pl;
		var posY = e.pageY - target.offset().top - pt;

		path = target.getFullPath();
		nx = (posX/width).toFixed(2);
		ny = (posY/height).toFixed(2);
		if ( nx == Infinity || nx == NaN ) nx = "0.00";
		if ( ny == Infinity || ny == NaN ) ny = "0.00";

		if (isclick) nelioab_actual_data_click.addDataPoint(path, nx, ny);
		nelioab_actual_data.addDataPoint(path, nx, ny);
	};

	body.mousemove(function(e) {
	    if (touch) return;
		over = true;
		
		if(simulate){
			clearInterval(simulate);
			simulate = false;
		}
		if (timeout) {
			clearTimeout(timeout);
			stop = false;
		}
 
		if(active && focus){
			add(e, false);
			active = false;
		}
	});
	
	// Mouse events
	body.click(function(e){
		over = true;
		
		if(simulate){
			clearInterval(simulate);
			simulate = false;
		}
		if (timeout) {
			clearTimeout(timeout);
			stop = false;
		}
		
		if ( !touch )
			add(e, true);
	});
	
	body.mouseleave(function(){
		over = false;
	});
	
	body.mouseenter(function(){
		over = true;
	});
	
	// Touch events
	jQuery('body').bind('touchstart', function(e){
		touch = true;
		var touchlist = e.originalEvent.touches;
		for (var i=0; i<touchlist.length; i++) { 
			// loop through all touch points currently in contact with surface
			add(touchlist[i], true);
		}
	});

	// Focus and Blur events to control focus on tab/window
	jQuery(window).focus(function() {
		focus = true;
	});

	jQuery(window).blur(function() {
		focus = false;
		if(simulate){
			clearInterval(simulate);
			simulate = false;
		}
		if (timeout) {
			clearTimeout(timeout);
			stop = false;
		}
	});

	jQuery(document).bind( 'byebye', function( e, href ) {
		if ( href instanceof String && href.indexOf( "#" ) == 0 ) return;
		add(e, true);
		sendHeatmapDataToWordpress();
	});

	window.onunload = window.onbeforeunload = ( function() {
		sendHeatmapDataToWordpress();
	} );

	var isDataSentOrBeingSent = false;
	function sendHeatmapDataToWordpress() {
		if ( isDataSentOrBeingSent ) return;
		isDataSentOrBeingSent = true;
		// Send Heatmap Data to WordPress
		jQuery.ajax({
			type: 'POST',
			async: false,
			url:	window.location.href,
			data: {
				'nelioab_send_heatmap_info': 'true',
				'phone-data':   JSON.stringify(nelioab_phone_data.exportDataSet()),
				'tablet-data':  JSON.stringify(nelioab_tablet_data.exportDataSet()),
				'desktop-data': JSON.stringify(nelioab_desktop_data.exportDataSet()),
				'hd-data':      JSON.stringify(nelioab_hd_data.exportDataSet()),
				'phone-data-click':   JSON.stringify(nelioab_phone_data_click.exportDataSet()),
				'tablet-data-click':  JSON.stringify(nelioab_tablet_data_click.exportDataSet()),
				'desktop-data-click': JSON.stringify(nelioab_desktop_data_click.exportDataSet()),
				'hd-data-click':      JSON.stringify(nelioab_hd_data_click.exportDataSet()),
				'hm-post-id': jQuery("#hm-post-id").text(),
			},
		});
	}

	jQuery(window).resize(function(e) {
		nelioab_actual_data = nelioab_selectDatastore(jQuery(window).width());
		nelioab_actual_data_click = nelioab_selectClickDatastore(jQuery(window).width());
	});
}
