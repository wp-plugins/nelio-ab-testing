/**
 * This object contains some variables and functions relevant for tracking
 * Heatmaps.
 */
NelioAB.heatmaps = {};

/**
 * Auxiliar variables for tracking the cursor position. It'll point to the
 * actual resolution.
 */
NelioAB.heatmaps.current = {};
NelioAB.heatmaps.current.regular;
NelioAB.heatmaps.current.click;


/**
 * Heatmap information for the Phone resolution.
 */
NelioAB.heatmaps.phone = {};
NelioAB.heatmaps.phone.regular;
NelioAB.heatmaps.phone.click;


/**
 * Heatmap information for the Tablet resolution.
 */
NelioAB.heatmaps.tablet = {};
NelioAB.heatmaps.tablet.regular;
NelioAB.heatmaps.tablet.click;


/**
 * Heatmap information for the Desktop resolution.
 */
NelioAB.heatmaps.desktop = {};
NelioAB.heatmaps.desktop.regular;
NelioAB.heatmaps.desktop.click;


/**
 * Heatmap information for the HD resolution.
 */
NelioAB.heatmaps.hd = {};
NelioAB.heatmaps.hd.regular;
NelioAB.heatmaps.hd.click;


/**
 * Defining all tracking information
 */
(function() {
	NelioAB.heatmaps.HeatmapData = function() {
		this.data = {};
		this.max = 0;
	};

	NelioAB.heatmaps.HeatmapDataForElement = function() {
		this.data = [];
		this.max = 0;
	};

	NelioAB.heatmaps.HeatmapData.prototype = {
		addDataPoint: function(path, x, y){
			if(typeof x == 'undefined' || x < 0 || typeof y == 'undefined' || y < 0)
				return;
			if ( 0 === path )
				return;
			var data = this.data;
			var hm_for_elem = false;
			if ( typeof data[path] != 'undefined' )
				hm_for_elem = data[path];
			if ( !hm_for_elem ) {
				hm_for_elem = new NelioAB.heatmaps.HeatmapDataForElement();
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
			return { max:this.max, data:exportData, session:NelioAB.user.session() };
		}
	};

	NelioAB.heatmaps.HeatmapDataForElement.prototype = {
		addDataPoint: function(x, y){
			var data = this.data;
			if(!data[x])
				data[x] = [];
			if(!data[x][y])
				data[x][y] = 0;
			data[x][y] += 1;
			// do we have a new maximum?
			if(this.max < data[x][y])
				this.max = data[x][y];
		},
		exportDataSet: function(){
			var data = this.data;
			var exportData = [];
			for(var one in data){
				// jump over undefined indexes
				if(typeof one == 'undefined' || !data.hasOwnProperty(one))
					continue;
				for(var two in data[one]) {
					if(typeof two == 'undefined' || !data[one].hasOwnProperty(two))
						continue;
					exportData.push({x: one, y: two, count: data[one][two]});
				}
			}
			return { max:this.max, data:exportData };
		}
	};

	NelioAB.heatmaps.phone.regular   = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.tablet.regular  = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.desktop.regular = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.hd.regular      = new NelioAB.heatmaps.HeatmapData();

	NelioAB.heatmaps.phone.click   = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.tablet.click  = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.desktop.click = new NelioAB.heatmaps.HeatmapData();
	NelioAB.heatmaps.hd.click      = new NelioAB.heatmaps.HeatmapData();


})();

/**
 * Depending on the current width w of the screen, we'll use one tracking
 * object or the other.
 *
 * @param w
 *            current window width.
 *
 * @return the appropriate regular data store.
 */
NelioAB.heatmaps.selectRegularDatastore = function(w) {
	if (w <= 360) return NelioAB.heatmaps.phone.regular;
	else if (w <= 768) return NelioAB.heatmaps.tablet.regular;
	else if (w <= 1024) return NelioAB.heatmaps.desktop.regular;
	else return NelioAB.heatmaps.hd.regular;
};

/**
 * Depending on the current width w of the screen, we'll use one tracking
 * object or the other.
 *
 * @param w
 *            current window width.
 *
 * @return the appropriate click data store.
 */
NelioAB.heatmaps.selectClickDatastore = function(w) {
	if (w <= 360) return NelioAB.heatmaps.phone.click;
	else if (w <= 768) return NelioAB.heatmaps.tablet.click;
	else if (w <= 1024) return NelioAB.heatmaps.desktop.click;
	else return NelioAB.heatmaps.hd.click;
};

/**
 * Start tracking the information and collecting data to build heatmaps.
 *
 * @param mode
 *            Either "elem" or "html", this variable specifies how Heatmaps
 *            should be collected.
 */
NelioAB.heatmaps.doTrack = function(mode) {
	NelioAB.heatmaps.current.regular = NelioAB.heatmaps.selectRegularDatastore(jQuery(window).width());
	NelioAB.heatmaps.current.click = NelioAB.heatmaps.selectClickDatastore(jQuery(window).width());

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
				NelioAB.heatmaps.current.regular.addDataPoint(path, nx, ny);
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

	var add;
	if ( 'html' == mode ) {
		path = 'html>body';
		add = function( e, isclick ) {
			if ( typeof e.pageX == 'undefined' || typeof e.pageY == 'undefined' ) return;

			var width = jQuery('body').width();
			var height = jQuery('body').height();
			var posX = e.pageX;
			var posY = Math.floor( e.pageY / 40 ) * 40 + 20;

			if ( posY > height )
				posY = Math.floor( (height - 20) / 40 ) * 40 + 20;

			nx = normalizer(posX,width);
			ny = parseFloat(posY/height).toFixed(5);

			if (isclick) NelioAB.heatmaps.current.click.addDataPoint(path, nx, ny);
			NelioAB.heatmaps.current.regular.addDataPoint(path, nx, ny);
		}
	}
	else {
		add = function( e, isclick ) {
			if ( typeof e.pageX == 'undefined' || typeof e.pageY == 'undefined' ) return;
			var target = jQuery(e.target);

			try {
				path = target.getFullPath();
			}
			catch ( e ) {
				NelioAB.jquery.extend();
				return;
			}

			var pl = target.css('padding-left');   if ( typeof pl == 'undefined' ) pl = '0';
			var pr = target.css('padding-right');  if ( typeof pr == 'undefined' ) pr = '0';
			var pt = target.css('padding-top');    if ( typeof pt == 'undefined' ) pt = '0';
			var pb = target.css('padding-bottom'); if ( typeof pb == 'undefined' ) pb = '0';

			pl = Math.round( pl.replace(/[^0-9\.]/g,'') );
			pr = Math.round( pr.replace(/[^0-9\.]/g,'') );
			pt = Math.round( pt.replace(/[^0-9\.]/g,'') );
			pb = Math.round( pb.replace(/[^0-9\.]/g,'') );

			var width = target.width() + pl + pr;
			var height = target.height() + pt + pb;
			var posX = e.pageX - target.offset().left - pl;
			var posY = e.pageY - target.offset().top - pt;

			nx = normalizer( posX, width );
			ny = normalizer( posY, height );
			if ( nx == Infinity || nx == NaN ) nx = '0';
			if ( ny == Infinity || ny == NaN ) ny = '0';

			if (isclick) NelioAB.heatmaps.current.click.addDataPoint(path, nx, ny);
			NelioAB.heatmaps.current.regular.addDataPoint(path, nx, ny);
		};
	}

	var normalizer = function( position, length ) {
		if ( length <= 50 )
			return '0.5';

		if ( length <= 500 ) {
			var result = (position/length).toFixed(1);
			if ( length <= 150 ) {
				if ( result == '0.0' || result == '0.1' || result == '0.2' || result == '0.3' )
					return '0.2';
				else if ( result == '0.4' || result == '0.5' || result == '0.6' )
					return '0.5';
				else
					return '0.8';
			}
			return result;
		}

		var result = (position/length).toFixed(3);
		var cent = parseInt(result.substring(3,4));
		if ( length <= 800 ) {
			if ( cent <= 5 ) cent = '5';
			else cent = '';
			return result.substring(0,3) + cent;
		}
		else if ( length <= 1500 ) {
			if ( cent == 0 ) cent = '';
			else if ( cent <= 2 ) cent = '2';
			else if ( cent <= 5 ) cent = '5';
			else if ( cent <= 7 ) cent = '7';
			else cent = '9';
			return result.substring(0,3) + cent;
		}
		else if ( length <= 5000 ) {
			return result.replace(/0?.$/, '');
		}
		else {
			return result.replace(/0+$/, '');
		}
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

	jQuery(document).bind( 'byebye', function( e, elem, href ) {
		if ( href instanceof String && href.indexOf( '#' ) == 0 ) return;
		add(e, true);
		NelioAB.heatmaps.sync( true );
	});

	window.onunload = window.onbeforeunload = ( function() {
		NelioAB.helpers.setRefererCookie();
		NelioAB.heatmaps.sync( true );
	} );

	jQuery(window).resize(function(e) {
		NelioAB.heatmaps.current.regular = NelioAB.heatmaps.selectRegularDatastore(jQuery(window).width());
		NelioAB.heatmaps.current.click = NelioAB.heatmaps.selectClickDatastore(jQuery(window).width());
	});
};


NelioAB.heatmaps.syncInterval = 1000;


/**
 * Heatmap information is being sent to AE periodically. This function
 * specifies when the next sync has to occur.
 */
NelioAB.heatmaps.scheduleNextSync = function() {
	setTimeout( function() {
		NelioAB.heatmaps.sync( false );
	}, NelioAB.heatmaps.syncInterval );
	if ( NelioAB.heatmaps.syncInterval <= 60000 )
		NelioAB.heatmaps.syncInterval += 5000;
};

/**
 * This function sends the data to AE, using the appropriate API function.
 *
 * @param data
 *            the collected Heatmaps data.
 */
NelioAB.heatmaps.doSync = function(data) {
	var async = NelioABParams.misc.useOutwardsNavigationsBlank;
	data.envHash = NelioABBasic.settings.envHash;
	NelioAB.helpers.remotePost({
		async: async,
		url: NelioAB.backend.url + '/hm',
		data: data
	});
};

/**
 * This function prepares the collected Heatmap data, transforms it to the
 * format AE expects, and sends it to AE.
 *
 * @param lastSending
 *            Specifies whether the execution of this function is the last time
 *            it should be executed or not. If it isn't, it'll schedule a next
 *            sync.
 */
NelioAB.heatmaps.isSyncing = false;
NelioAB.heatmaps.sync = function( lastSending ) {
	if ( NelioAB.heatmaps.isSyncing )
		return;
	if ( lastSending )
		NelioAB.heatmaps.isSyncing = true;

	// Swap data
	var phoneRegular = NelioAB.heatmaps.phone.regular;
	NelioAB.heatmaps.phone.regular = new NelioAB.heatmaps.HeatmapData();
	var tabletRegular = NelioAB.heatmaps.tablet.regular;
	NelioAB.heatmaps.tablet.regular = new NelioAB.heatmaps.HeatmapData();
	var desktopRegular = NelioAB.heatmaps.desktop.regular;
	NelioAB.heatmaps.desktop.regular = new NelioAB.heatmaps.HeatmapData();
	var hdRegular = NelioAB.heatmaps.hd.regular;
	NelioAB.heatmaps.hd.regular = new NelioAB.heatmaps.HeatmapData();

	var phoneClick = NelioAB.heatmaps.phone.click;
	NelioAB.heatmaps.phone.click = new NelioAB.heatmaps.HeatmapData();
	var tabletClick = NelioAB.heatmaps.tablet.click;
	NelioAB.heatmaps.tablet.click = new NelioAB.heatmaps.HeatmapData();
	var desktopClick = NelioAB.heatmaps.desktop.click;
	NelioAB.heatmaps.desktop.click = new NelioAB.heatmaps.HeatmapData();
	var hdClick = NelioAB.heatmaps.hd.click;
	NelioAB.heatmaps.hd.click = new NelioAB.heatmaps.HeatmapData();

	NelioAB.heatmaps.current.regular = NelioAB.heatmaps.selectRegularDatastore(jQuery(window).width());
	NelioAB.heatmaps.current.click = NelioAB.heatmaps.selectClickDatastore(jQuery(window).width());

	// Send Heatmap Data to WordPress
	var data = {
			customerId: NelioABParams.customer,
			siteId: NelioABParams.site,
			post: NelioABParams.info.currentActualId,
			session: NelioAB.user.session()
		};

	if ( phoneClick.max > 0 ) {
		data['resolution'] = 'phone';
		data['isClick'] = true;
		data['value'] = JSON.stringify( phoneClick.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( tabletClick.max > 0 ) {
		data['resolution'] = 'tablet';
		data['isClick'] = true;
		data['value'] = JSON.stringify( tabletClick.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( desktopClick.max > 0 ) {
		data['resolution'] = 'desktop';
		data['isClick'] = true;
		data['value'] = JSON.stringify( desktopClick.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( hdClick.max > 0 ) {
		data['resolution'] = 'hd';
		data['isClick'] = true;
		data['value'] = JSON.stringify( hdClick.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( phoneRegular.max > 0 ) {
		data['resolution'] = 'phone';
		data['isClick'] = false;
		data['value'] = JSON.stringify( phoneRegular.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( tabletRegular.max > 0 ) {
		data['resolution'] = 'tablet';
		data['isClick'] = false;
		data['value'] = JSON.stringify( tabletRegular.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( desktopRegular.max > 0 ) {
		data['resolution'] = 'desktop';
		data['isClick'] = false;
		data['value'] = JSON.stringify( desktopRegular.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( hdRegular.max > 0 ) {
		data['resolution'] = 'hd';
		data['isClick'] = false;
		data['value'] = JSON.stringify( hdRegular.exportDataSet() );
		NelioAB.heatmaps.doSync(data);
	}

	if ( !lastSending )
		NelioAB.heatmaps.scheduleNextSync();
};


/**
 * This function enables Heatmap tracking for the current user and session.
 */
NelioAB.heatmaps.track = function() {
	var trackHeatmaps = false;
	// Check if there's a Heatmap Experiment running
	for ( var i = 0; i < NelioABBasic.hm.length && !trackHeatmaps; ++i )
		if ( NelioABBasic.hm[i] == NelioABParams.info.currentActualId )
			trackHeatmaps = true;
	// Check if there's an AB Experiment running and heatmaps must be tracked
	for ( var i = 0; i < NelioABEnv.hm.length && !trackHeatmaps; ++i )
		if ( NelioABEnv.hm[i] == NelioABParams.info.currentActualId )
			trackHeatmaps = true;
	if ( trackHeatmaps ) {
		NelioAB.heatmaps.doTrack(NelioABBasic.settings.hmMode);
		NelioAB.heatmaps.scheduleNextSync();
	}
};

