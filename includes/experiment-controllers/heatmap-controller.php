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
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


class NelioABHeatmapController {

	public function __construct() {
		if ( isset( $_GET['nelioab_show_heatmap'] ) ) {
			add_action( 'wp_enqueue_scripts', array( &$this, 'add_heatmap_script' ) );
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	public function make_user_regular( $allcaps, $caps, $args ) {
		wp_set_current_user( NULL, '__nelioab_no_one' );
		foreach( $allcaps as $key => $val )
			$allcaps[$key] = 0;
		return $allcaps;
	}

	public function add_heatmap_script() {
		wp_enqueue_script( 'nelio-heatmap', nelioab_admin_asset_link( '/js/heatmap.min.js' ) );
	}

}//NelioABHeatmapController

