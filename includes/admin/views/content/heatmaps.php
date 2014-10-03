<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	$show_back_link = false;
	if ( isset( $_GET['exp_type'] ) )
		if ( $_GET['exp_type'] == NelioABExperiment::PAGE_ALT_EXP ||
		     $_GET['exp_type'] == NelioABExperiment::POST_ALT_EXP )
			$show_back_link = true;

	if ( isset( $_POST['load_from_appengine'] ) ) {
		try {
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$exp_type = 0;
			if ( !isset( $_GET['exp_type'] ) || !isset( $_GET['id'] ) ) {
				$err = NelioABErrCodes::TOO_FEW_PARAMETERS;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			// Get the Heatmap
			$exp_type = $_GET['exp_type'];
			$mgr = new NelioABExperimentsManager();
			$exp = $mgr->get_experiment_by_id( $_GET['id'], $exp_type );

			if ( $exp_type == NelioABExperiment::HEATMAP_EXP ) {
				$url = sprintf( NELIOAB_BACKEND_URL . '/exp/hm/%s/result', $exp->get_id() );
				$post_id = $exp->get_post_id();
				global $nelioab_controller;
				if ( $post_id == NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS )
					$post_id = false;
			}
			else {
				if ( !isset( $_GET['post'] ) ) {
					$err = NelioABErrCodes::TOO_FEW_PARAMETERS;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
				$url = sprintf( NELIOAB_BACKEND_URL . '/exp/post/%s/hm/%s/result', $exp->get_id(), $_GET['post'] );
				$post_id = $_GET['post'];
			}

			$result = NelioABBackend::remote_get( $url );
			$result = json_decode( $result['body'] );

			$counter = 0;
			if ( isset( $result->data ) ) {
				foreach ( $result->data as $heatmap ) {
					$value = json_decode( $heatmap->value );
					$counter += $value->max;
				}
			}
			if ( $counter == 0 ) {
				if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
					$err = NelioABErrCodes::NO_HEATMAPS_AVAILABLE;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
				else {
					$err = NelioABErrCodes::NO_HEATMAPS_AVAILABLE_FOR_NON_RUNNING_EXPERIMENT;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
			}
		}
		catch ( Exception $e ) {
			echo sprintf( '<img src="%s" alt="%s" style="margin-top:50px;"/>',
				nelioab_asset_link( '/admin/images/white-error-icon.png' ),
				__( 'Funny image to graphically notify of an error.', 'nelioab' )
			);
			?>
			<p id="ajax-loader-label1"
				style="margin-top:10px;color:white;font-size:20px;"><?php echo $e->getMessage(); ?></p><?php
			die();
		}

		// Prepare the content
		$page_on_front = get_option( 'page_on_front' );
		if ( !$page_on_front && !$post_id ) // if the home page is the list of posts and the experiment is for the home page
			$url = get_option( 'home' ); // the url should be the home page
		else  // otherwise (1 - the heatmaps is NOT for the home page or 2 - the home page is a specific page, the heatmaps should display that page
			$url = get_permalink( $post_id );

		if ( !$url )
			$url = add_query_arg( array( 'p' => $post_id ), get_option( 'home' ) );
		$url = add_query_arg( array( 'nelioab_show_heatmap' => 'true' ), $url );

		?>
		<script type="text/javascript">
			window.onerror = function(msg, url, line, col, error) {
				var url = window.location.href;
				if ( msg.indexOf('SecurityError') >= 0 && url.indexOf( 'retry-with-https=true' ) === -1 ) {
					window.location.href = 'https://' + url.replace('http://','') + '&retry-with-https=true';
					return true;
				}
				return false;
			};
		</script>
		<div id="phantom" style="width:0px;height:0px;"></div>
		<div id="wrapper" style="width:100%;height:100%;">
			<div id="builder" style="
					display:none;
					z-index:11;
					background-color:#585c62;
					color:white;
					font-size:15px;
					text-align:center;
					position:relative;
					top:0px;
					left:0px;
					width:100%;
					height:100%;
					min-height:100%;
				">
				<br><br>
				<div style="text-align:center;height:50px;">
					<div class="nelioab_spinner white_spinner"></div>
				</div>
				<p><?php
					_e( 'Building heatmap...<br>This might take a while. Please, wait.', 'nelioab' );
				?></p>
			</div>
			<iframe id="content" name="content" frameborder="0"
				src="<?php echo $url; ?>"
				style="background-color:white;width:0px;height:0px;"></iframe>
		</div>
		<script>
			var NelioABHeatmapLabels = { hm:{}, cm:{} };<?php ?>

			NelioABHeatmapLabels.hm.view      = "<?php echo esc_html( __( 'View Heatmap', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.phone     = "<?php echo esc_html( __( 'Smartphone', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.phoneNo   = "<?php echo esc_html( __( 'Smartphone (no Heatmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.tablet    = "<?php echo esc_html( __( 'Tablet', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.tabletNo  = "<?php echo esc_html( __( 'Tablet (no Heatmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.desktop   = "<?php echo esc_html( __( 'Laptop Monitor', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.desktopNo = "<?php echo esc_html( __( 'Laptop Monitor (no Heatmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.hd        = "<?php echo esc_html( __( 'Regular Desktop Monitor', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.hm.hdNo      = "<?php echo esc_html( __( 'Regular Desktop Monitor (no Heatmap available)', 'nelioab' ) ); ?>";

			NelioABHeatmapLabels.cm.view      = "<?php echo esc_html( __( 'View Clickmap', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.phone     = "<?php echo esc_html( __( 'Smartphone', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.phoneNo   = "<?php echo esc_html( __( 'Smartphone (no Clickmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.tablet    = "<?php echo esc_html( __( 'Tablet', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.tabletNo  = "<?php echo esc_html( __( 'Tablet (no Clickmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.desktop   = "<?php echo esc_html( __( 'Laptop Monitor', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.desktopNo = "<?php echo esc_html( __( 'Laptop Monitor (no Clickmap available)', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.hd        = "<?php echo esc_html( __( 'Regular Desktop Monitor', 'nelioab' ) ); ?>";
			NelioABHeatmapLabels.cm.hdNo      = "<?php echo esc_html( __( 'Regular Desktop Monitor (no Clickmap available)', 'nelioab' ) ); ?>";

		</script>
		<script>
			var nelioabHeatmapScale = 0.75;
			var nelioabHoveringDevice = null;
			var nelioabSwitchToClickEnabled = true;
			$("#view-clicks").click(function() {
				if ( !nelioabSwitchToClickEnabled ) return;
				nelioabSwitchToClickEnabled = false;
				nelioab__show_clicks = !nelioab__show_clicks;
				if ( nelioab__show_clicks )
					$("#view-clicks").text( NelioABHeatmapLabels.hm.view );
				else
					$("#view-clicks").text( NelioABHeatmapLabels.cm.view );
				highlightData( nelioab__show_clicks );
				switchHeatmap();
			});
			$("#devices a").each(function() {
				$(this).mouseenter(function() {
					nelioabHoveringDevice = $(this);
					var size = $(this).attr('data-viewport').split('x');
					var w=size[0], h=size[1], sw=w, sh=h;
					if ( w != 'auto' ) { sw=Math.ceil(w*nelioabHeatmapScale), sh=Math.ceil(h*nelioabHeatmapScale); }
					$("#phantom").width(sw);$("#phantom").height(sh);$("#phantom").css('margin-left',-(sw/2));
				});
				$(this).mouseleave(function() {
					$("#phantom").width(0);$("#phantom").height(0);$("#phantom").css('margin-left',0);
				});
				$(this).click(function() {
					nelioab__current_type = $(this).attr('id');
					switchHeatmap();
					$("#devices .active").each(function() { $(this).removeClass("active"); } );
					var size = $(this).attr('data-viewport').split('x');
					scaleTo( size[0], size[1],nelioabHeatmapScale );
					$(this).addClass("active");
				});
			});
			function scaleTo(w,h,scale) {
				var sw=w, sh=h;
				if ( w != 'auto' ) { sw=Math.ceil(w*scale), sh=Math.ceil(h*scale); }
				if ( w == 'auto' ) {
					$("#wrapper > iframe").css('width','100%');$("#wrapper > iframe").css('height','100%');
					$("#wrapper > iframe").removeClass("scaled");
					$("#wrapper").css('width','100%');$("#wrapper").css('height','100%');
					$("#wrapper").css('margin-top','0px');
				} else {
					$("#wrapper > iframe").css('width',w);$("#wrapper > iframe").css('height',h);
					$("#wrapper > iframe").addClass("scaled");
					$("#wrapper").css('width',sw);$("#wrapper").css('height',sh);
					$("#wrapper").css('margin-top','25px');
				}
			}
			function switchHeatmap() {
				document.getElementById('content').contentWindow.clearHeatmapObject();
				jQuery("#builder").show();
				setTimeout('doSwitchHeatmap()', 400);
			}
			function doSwitchHeatmap() {
				var nelioabHeatmapObject;
				var key = '#' + nelioab__current_type;
				switch( nelioab__current_type ) {
					case 'mobile':
						var size = jQuery(key).attr('data-viewport').split('x');
						var w=size[0], h=size[1];
						nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
						if ( nelioab__phone.max == -1 && !nelioab__show_clicks ) buildHeatmap( nelioab__pre_phone, nelioab__phone);
						if ( nelioab__phone_click.max == -1 && nelioab__show_clicks ) buildHeatmap( nelioab__pre_phone_click, nelioab__phone_click );
						jQuery("#builder").hide();
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
						var size = jQuery(key).attr('data-viewport').split('x');
						var w=size[0], h=size[1];
						nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
						if ( nelioab__tablet.max == -1 && !nelioab__show_clicks ) buildHeatmap( nelioab__pre_tablet, nelioab__tablet);
						if ( nelioab__tablet_click.max == -1 && nelioab__show_clicks ) buildHeatmap( nelioab__pre_tablet_click, nelioab__tablet_click );
						jQuery("#builder").hide();
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
						var size = jQuery(key).attr('data-viewport').split('x');
						var w=size[0], h=size[1];
						nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
						if ( nelioab__desktop.max == -1 && !nelioab__show_clicks ) buildHeatmap( nelioab__pre_desktop, nelioab__desktop);
						if ( nelioab__desktop_click.max == -1 && nelioab__show_clicks ) buildHeatmap( nelioab__pre_desktop_click, nelioab__desktop_click );
						jQuery("#builder").hide();
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
						var size = jQuery(key).attr('data-viewport').split('x');
						var w=size[0], h=size[1];
						nelioabHeatmapObject = document.getElementById('content').contentWindow.createHeatmapObject(w,h);
						if ( nelioab__hd.max == -1 && !nelioab__show_clicks ) buildHeatmap( nelioab__pre_hd, nelioab__hd);
						if ( nelioab__hd_click.max == -1 && nelioab__show_clicks ) buildHeatmap( nelioab__pre_hd_click, nelioab__hd_click );
						jQuery("#builder").hide();
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
			}
			<?php
			foreach ( $result->data as $heatmap ) {
				$name = $heatmap->resolution;
				if ( $heatmap->click ) {
					$name .= '_click';
					$views = sprintf(
						_n( 'Clickmap built using data from only one page view', 'Clickmap built using data from %s page views',
							$heatmap->views, 'nelioab' ),
						$heatmap->views );
				}
				else {
					$views = sprintf(
						_n( 'Heatmap built using data from only one page view', 'Heatmap built using data from %s page views',
							$heatmap->views, 'nelioab' ),
						$heatmap->views );
				}
				$value = $heatmap->value;
				$value = substr( $value, strpos( $value, '{' ) + 1 );
				$value = '{"views": "' . $views . '", ' . $value;
				?>var nelioab__pre_<?php echo $name . ' = ' . $value; ?>;
			<?php
			}
			?>
			</script>
			<script>
			var nelioab__phone = { "max":-1, "data":[] };
			var nelioab__tablet = { "max":-1, "data":[] };
			var nelioab__desktop = { "max":-1, "data":[] };
			var nelioab__hd = { "max":-1, "data":[] };

			var nelioab__phone_click = { "max":-1, "data":[] };
			var nelioab__tablet_click = { "max":-1, "data":[] };
			var nelioab__desktop_click = { "max":-1, "data":[] };
			var nelioab__hd_click = { "max":-1, "data":[] };

			function buildHeatmap( src, dest ) {
				dest.max = 0;
				var data = [];
				for( var path in src.data ) {
					var partial_hm = src.data[path];
					var elem = jQuery(path, document.getElementById('content').contentWindow.document);

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
						var x = offset_x + Math.round( w * val.x );
						var y = offset_y + Math.round( h * val.y );
						count = addPointCount( data, x, y, val.count );
						if ( count > dest.max )
							dest.max = count;
					}
				}
				dest.data = data;
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

			var nelioab__nodata = { "max":0, "data":[] };
			var nelioab__show_clicks = false;
			var nelioab__current_type = 'desktop';
			$("#content").load(function() {
				if ( nelioab__pre_desktop.max + nelioab__pre_desktop_click.max > 0 ) nelioab__current_type = 'desktop';
				else if ( nelioab__pre_hd.max + nelioab__pre_hd_click.max > 0 ) nelioab__current_type = 'hd';
				else if ( nelioab__pre_tablet.max + nelioab__pre_tablet_click.max > 0 ) nelioab__current_type = 'tablet';
				else if ( nelioab__pre_phone.max + nelioab__pre_phone_click.max > 0 ) nelioab__current_type = 'phone';
				highlightData(false);
				$("#" + nelioab__current_type).addClass("active");
				var size = $("#" + nelioab__current_type).attr('data-viewport').split('x');
				scaleTo( size[0], size[1], nelioabHeatmapScale );
				switchHeatmap();
			});
			function highlightData(isClick) {
				var values;
				if ( isClick ) values = NelioABHeatmapLabels.cm;
				else values = NelioABHeatmapLabels.hm;
				if ( nelioab__pre_phone_click.max > 0 ) $("#mobile").attr('title',values.phone).removeClass("disabled");
				else $("#mobile").attr('title',values.phoneNo).addClass("disabled");
				if ( nelioab__pre_tablet_click.max > 0 ) $("#tablet").attr('title',values.tablet).removeClass("disabled");
				else $("#tablet").attr('title',values.tabletNo).addClass("disabled");
				if ( nelioab__pre_desktop_click.max > 0 ) $("#desktop").attr('title',values.desktop).removeClass("disabled");
				else $("#desktop").attr('title',values.desktopNo).addClass("disabled");
				if ( nelioab__pre_hd_click.max > 0 ) $("#hd").attr('title',values.hd).removeClass("disabled");
				else $("#hd").attr('title',values.hdNo).addClass("disabled");
			}
		</script><?php
		die();
	}
?>
<html style="opacity:1;" class="complete">

<head>
	<title><?php _e( 'Nelio AB Testing &mdash; Heatmaps Viewer', 'nelioab' ); ?></title>
	<link rel="stylesheet" href="<?php echo nelioab_admin_asset_link( '/css/nelioab-generic.min.css' ); ?>">
	<link rel="stylesheet" href="<?php echo nelioab_admin_asset_link( '/css/resizer.min.css' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9, chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta charset="utf-8">
</head>

<body>
	<script src="<?php echo nelioab_admin_asset_link( '/js/jquery4hm.min.js' ); ?>"></script>

	<div id="toolbar" data-resizer="basic">
		<ul id="devices">
			<?php $name = __( 'Smartphone (no Heatmap available)', 'nelioab' ); ?>
			<li><a id='mobile' data-viewport="360x640" data-icon="mobile" title="<?php echo $name; ?>" class="portrait"><?php echo $name; ?></a></li>
			<?php $name = __( 'Tablet (no Heatmap available)', 'nelioab' ); ?>
			<li><a id='tablet' data-viewport="768x1024" data-icon="tablet" title="<?php echo $name; ?>" class="portrait"><?php echo $name; ?></a></li>
			<?php $name = __( 'Laptop Monitor (no Heatmap available)', 'nelioab' ); ?>
			<li><a id='desktop' data-viewport="1024x768" data-icon="notebook" title="<?php echo $name; ?>" class="landscape"><?php echo $name; ?></a></li>
			<?php $name = __( 'Regular Desktop Monitor (no Heatmap available)', 'nelioab' ); ?>
			<li><a id='hd' data-viewport="1440x900" data-icon="display" title="<?php echo $name; ?>" class="landscape"><?php echo $name; ?></a></li>
		</ul>
		<ul id="hm-additional-controls">
			<li style="color:white;font-size:12px;font-weight:bold;" id="visitors-count"><?php printf( __( 'Heatmap built using data from %s page views', 'nelioab' ), 0 ); ?></li>
			<li>|</li>
			<li><a id="view-clicks" style="font-size:12px;"><?php echo __( 'View Clickmap', 'nelioab' ); ?></a></li>
			<li>|</li>
			<li><a style="font-size:12px;" href="<?php echo admin_url( 'admin.php?page=nelioab-experiments' ); ?>"><?php echo __( 'List of experiments', 'nelioab' ); ?></a></li>
			<?php
			if ( $show_back_link ) {
				$link = admin_url( 'admin.php?page=nelioab-experiments&action=progress&id=%1$s&exp_type=%2$s' );
				?>
				<li>|</li>
				<li><a style="font-size:12px;" href="<?php printf( $link, $_GET['id'], $_GET['exp_type'] ); ?>"><?php echo __( 'Back', 'nelioab' ); ?></a></li>
			<?php
			} ?>
		</ul>
	</div>

	<div id="container" style="color:rgb(255, 255, 255);" class="auto transition">
		<div style="text-align:center;height:50px;margin-top:80px;">
			<div class="nelioab_spinner white_spinner"></div>
		</div>
		<p id="ajax-loader-label1" style="color:white;font-size:20px;"><?php _e( 'Please, wait a moment<br /> while we are retrieving the heatmaps...', 'nelioab' ); ?></p>
	</div>

	<script>
		jQuery.ajax({
			type:'POST',
			async:true,
			url:window.location.href,
			data: {load_from_appengine:'true'},
		}).success(function(data) {
			jQuery("#container").html(data);
		});
	</script>

</body>
</html>
