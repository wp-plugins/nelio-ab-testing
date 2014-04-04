<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */


class NelioABHeatmapController {

	public function __construct() {
		if ( isset( $_GET['nelioab_show_heatmap'] ) ) {
			add_action( 'wp_print_scripts', array( &$this, 'show_heatmap' ) );
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	public function make_user_regular( $allcaps, $caps, $args ) {
		wp_set_current_user( NULL, '__nelioab_no_one' );
		foreach( $allcaps as $key => $val )
			$allcaps[$key] = 0;
		return $allcaps;
	}

	public function show_heatmap() { ?>
		<style>
			body > canvas:last-child {
				background: rgba(0,0,0,0.4);
			}
		</style>
		<script type="text/javascript" src="<?php
			echo NELIOAB_ADMIN_ASSETS_URL . '/js/heatmap.min.js' ?>"></script>
		<script type="text/javascript">
		var nelioabHeatmapObject = undefined;
		function clearHeatmapObject() {
			try {
				nelioabHeatmapObject.cleanup();
			} catch(e) {}
		}
		function createHeatmapObject( w, h ) {
			if ( w == undefined ) w = 1024;
			if ( h == undefined ) h = 768;
			w = Math.max(jQuery(document).width(), w)
			h = Math.max(jQuery(document).height(), h)
			jQuery("html").width(w);
			jQuery("html").height(h);
			// heatmap configuration
			var gradient = {
				0.0: "rgba(000,000,255,0)",
				0.2: "rgba(000,000,255,1)",
				0.4: "rgba(000,255,255,1)",
				0.6: "rgba(000,255,000,1)",
				0.8: "rgba(255,255,000,1)",
				1.0: "rgba(255,000,000,1)"
			};
			var config = {
				element: jQuery("html").get(0),
				radius: 40,
				blurRadius: 60,
				opacity: 65,
				gradient: gradient
			};
			//creates and initializes the heatmap
			nelioabHeatmapObject = h337.create(config);
			return nelioabHeatmapObject;
		};
		</script>
		<?php
	}

}//NelioABHeatmapController

?>
