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


if ( !class_exists( NelioABSelectExpEditionPageController ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/empty-ajax-page.php' );

	class NelioABSelectExpEditionPageController {

		public static function build() {
			$title = __( 'Edit Experiment', 'nelioab' );

			$view  = new NelioABEmptyAjaxPage( $title );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'id', $_REQUEST['id'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {

			// Obtain DATA from APPSPOT and check its type dynamically
			$experiments_manager = new NelioABExperimentsManager();
			$experiment = null;
			try {
				$experiment = $experiments_manager->get_experiment_by_id( $_POST['id'] );
				global $nelioab_admin_controller;
				$nelioab_admin_controller->data = $experiment;
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// Determine the proper controller and give it the control...
			require_once( NELIOAB_ADMIN_DIR . '/alt-exp-edition-page-controller.php' );
			NelioABAltExpEditionPageController::generate_html_content();
		}

	}//NelioABSelectExpEditionPageController

}

?>
