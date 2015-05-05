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
			$exp = NelioABExperimentsManager::get_experiment_by_id( $_GET['id'], $exp_type );

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
					if ( isset( $heatmap->value ) ) {
						$value = json_decode( $heatmap->value );
						$counter += $value->max;
					}
				}
			}
			if ( $counter == 0 ) {
				if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
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
		$page_on_front = nelioab_get_page_on_front();
		if ( !$page_on_front && !$post_id ) // if the home page is the list of posts and the experiment is for the home page
			$url = get_option( 'home' ); // the url should be the home page
		else  // otherwise (1 - the heatmaps is NOT for the home page or 2 - the home page is a specific page, the heatmaps should display that page
			$url = get_permalink( $post_id );

		$aux = get_post_type( $post_id );
		if ( !$url ) {
			if ( 'page' == $aux )
				$url = esc_url( add_query_arg( array( 'page_id' => $post_id ), get_option( 'home' ) ) );
			else
				$url = esc_url( add_query_arg( array( 'p' => $post_id ), get_option( 'home' ) ) );
		}
		$url = esc_url( add_query_arg( array( 'nelioab_show_heatmap' => 'true' ), $url ) );
		$url = preg_replace( '/^https?:/', '', $url );
		?>
		<script type="text/javascript">
			window.onerror = function(msg, url, line, col, error) {
				var url = document.URL;
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
					background-color:#32363f;
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

			<script type="text/javascript">
				var nelioab__framekiller = true;
				window.onbeforeunload = function() {
					if ( nelioab__framekiller ) {
						return "<?php echo str_replace( '"', '\\"', str_replace( '\\', '\\\\',
								__( 'Apparently, there\'s a script that\'s trying to overwrite the location in the address bar and take you somewhere else. Please, in order to see the Heatmaps and Clickmaps of this experiment, make sure you stay in this page and don\'t leave.', 'nelioab' )
							) ); ?>";
					}
				};
			</script>
			<iframe id="content" name="content" frameborder="0"
				src="<?php echo $url; ?>"
				style="background-color:white;width:0px;height:0px;"></iframe>
			<script type="text/javascript">document.getElementById('content').onload = function() { nelioab__framekiller = false; }</script>

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

		</script>
		<?php
		die();
	}
?>
<html style="opacity:1;" class="complete">

<head>
	<title><?php _e( 'Nelio AB Testing &mdash; Heatmaps Viewer', 'nelioab' ); ?></title>
	<link rel="stylesheet" href="<?php echo nelioab_admin_asset_link( '/css/resizer.min.css' ); ?>">
	<link rel="stylesheet" href="<?php echo nelioab_admin_asset_link( '/css/nelioab-generic.min.css' ); ?>">
	<link rel="stylesheet" href="<?php echo nelioab_admin_asset_link( '/css/nelioab-heatmap.min.css' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9, chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta charset="utf-8">
</head>

<body>
	<script src="<?php echo nelioab_admin_asset_link( '/js/jquery4hm.min.js' ); ?>"></script>
	<script src="<?php echo nelioab_admin_asset_link( '/js/heatmap-viewer.min.js' ); ?>"></script>

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
			<?php
			if ( $show_back_link ) {
				$link = admin_url( 'admin.php?page=nelioab-experiments&action=progress&id=%1$s&exp_type=%2$s' );
				?>
				<li>|</li>
				<li><a style="font-size:12px;" href="<?php printf( $link, $_GET['id'], $_GET['exp_type'] ); ?>"><?php echo __( 'Back', 'nelioab' ); ?></a></li>
			<?php
			} ?>
			<li>|</li>
			<li><a style="font-size:12px;" id="show-dropdown-controls">Settings</a></li>
		</ul>
		<ul id="hm-dropdown-controls" style="display:none;">
			<li style="font-size:12px;text-align:center;"><input id="hm-opacity" type="range" min="1" max="10" value="10" /></li>
			<li><a id="view-clicks" style="font-size:12px;"><?php echo __( 'View Clickmap', 'nelioab' ); ?></a></li>
			<li><a style="font-size:12px;" href="<?php echo admin_url( 'admin.php?page=nelioab-experiments' ); ?>"><?php echo __( 'List of experiments', 'nelioab' ); ?></a></li>
		</ul>
	</div>

	<div id="container" style="color:rgb(255, 255, 255);" class="auto transition">
		<div style="text-align:center;height:50px;margin-top:80px;">
			<div class="nelioab_spinner white_spinner"></div>
		</div>
		<p id="ajax-loader-label1" style="color:white;font-size:20px;"><?php _e( 'Please, wait a moment<br /> while we are retrieving the heatmaps...', 'nelioab' ); ?></p>
	</div>

	<script>
		(function(){
			var dd = jQuery('#hm-dropdown-controls');
			jQuery('#show-dropdown-controls').on('click', function() { dd.toggle(); });
			jQuery('#hm-opacity').on('change input', function() {
				try {
					document.getElementById('content').contentWindow.nelioabModifyHeatmapOpacity(
						jQuery(this).val()
					);
				}
				catch(e){}
			});
			jQuery('#hm-dropdown-controls').mouseup(function() {
				dd.hide();
			});
		})();
		jQuery.ajax({
			type:'POST',
			async:true,
			url:document.URL,
			data: {load_from_appengine:'true'},
			success: function(data) {
				jQuery("#container").html(data);
			},
		});
	</script>

</body>
</html>
