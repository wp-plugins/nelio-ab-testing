<?php
/**
 * Copyright 2015 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Nelio AB Testing controller for heatmap experiments.
 *
 * @since PHPDOC
 * @package \NelioABTesting\Controllers
 */
class NelioABHeatmapController {

	/**
	 * It creates a new instance of this controller.
	 *
	 * In principle, this class should be used as if it implemented the
	 * `singleton` pattern.
	 *
	 * @return NelioABHeatmapController a new instance of this class.
	 *
	 * @since PHPDOC
	 */
	public function __construct() {
		if ( isset( $_GET['nelioab_show_heatmap'] ) ) {
			add_action( 'wp_enqueue_scripts', array( &$this, 'add_heatmap_script' ) );
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function add_heatmap_script() {
		wp_enqueue_script( 'nelio-heatmap',
			nelioab_admin_asset_link( '/js/heatmap.min.js' ),
			array(), NELIOAB_PLUGIN_VERSION );
	}

}//NelioABHeatmapController

