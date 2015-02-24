<?php
/**
 * Copyright 2013 Nelio Software S.L.
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

if ( !class_exists( 'NelioABDashboardPageController' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/dashboard-page.php' );

	class NelioABDashboardPageController {

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			// Build the page and query data via AJAX
			$title = __( 'Dashboard', 'nelioab' );
			$view = new NelioABDashboardPage( $title );
			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

			// Force update results
			global $nelioab_controller;
			$nelioab_controller->compute_results_for_running_experiments();

			try {
				$summary = NelioABExperimentsManager::get_running_experiments_summary();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			$title = __( 'Dashboard', 'nelioab' );
			$view = new NelioABDashboardPage( $title );
			$view->set_summary( $summary );
			$view->render_content();
			die();
		}

	}//NelioABDashboardPageController

}

