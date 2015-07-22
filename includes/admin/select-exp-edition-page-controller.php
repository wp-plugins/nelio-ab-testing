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


if ( !class_exists( 'NelioABSelectExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/empty-ajax-page.php' );

	class NelioABSelectExpEditionPageController {

		public static function attempt_to_load_proper_controller() {
			if ( isset( $_POST['nelioab_exp_type'] ) )
				return NelioABSelectExpEditionPageController::get_controller( $_POST['nelioab_exp_type'] );
			return NULL;
		}

		public static function build() {
			$title = __( 'Edit Experiment', 'nelioab' );

			$view = new NelioABEmptyAjaxPage( $title );

			$controller = NelioABSelectExpEditionPageController::attempt_to_load_proper_controller();
			if ( $controller != NULL ) {
				call_user_func( array( $controller, 'build' ) );
			}
			else {
				if ( isset( $_GET['id'] ) )
					// The ID of the experiment to which the action applies
					$view->keep_request_param( 'id', $_GET['id'] );

				if ( isset( $_GET['exp_type'] ) )
					$view->keep_request_param( 'exp_type', $_GET['exp_type'] );

				$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
			}

		}

		public static function generate_html_content() {

			// Obtain DATA from APPSPOT and check its type dynamically
			$experiment = null;
			try {
				$exp_id = -time();
				if ( isset( $_POST['id'] ) )
					$exp_id = $_POST['id'];

				$exp_type = -1;
				if ( isset( $_POST['exp_type'] ) )
					$exp_type = $_POST['exp_type'];

				$experiment = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				global $nelioab_admin_controller;
				$nelioab_admin_controller->data = $experiment;
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			$type = $experiment->get_type();
			$controller = NelioABSelectExpEditionPageController::get_controller( $type );
			call_user_func( array( $controller, 'generate_html_content' ) );
		}

		public static function get_controller( $type ) {

			// Determine the proper controller and give it the control...
			switch ( $type ) {
				case NelioABExperiment::HEADLINE_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/headline-alt-exp-edition-page-controller.php' );
					return 'NelioABHeadlineAltExpEditionPageController';

				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::PAGE_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/post-alt-exp-edition-page-controller.php' );
					return 'NelioABPostAltExpEditionPageController';

				case NelioABExperiment::THEME_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/theme-alt-exp-edition-page-controller.php' );
					return 'NelioABThemeAltExpEditionPageController';

				case NelioABExperiment::CSS_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/css-alt-exp-edition-page-controller.php' );
					return 'NelioABCssAltExpEditionPageController';

				case NelioABExperiment::WIDGET_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/widget-alt-exp-edition-page-controller.php' );
					return 'NelioABWidgetAltExpEditionPageController';

				case NelioABExperiment::MENU_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/menu-alt-exp-edition-page-controller.php' );
					return 'NelioABMenuAltExpEditionPageController';

				case NelioABExperiment::HEATMAP_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/others/heatmap-exp-edition-page-controller.php' );
					return 'NelioABHeatmapExpEditionPageController';

				case NelioABExperiment::CPT_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/cpt-alt-exp-edition-page-controller.php' );
					return 'NelioABCptAltExpEditionPageController';

				case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/woocommerce/product-summary-alt-exp-edition-page-controller.php' );
					return 'NelioABProductSummaryAltExpEditionPageController';

				default:
					require_once( NELIOAB_UTILS_DIR . '/backend.php' );
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					$err = NelioABErrCodes::UNKNOWN_ERROR;
					$e = new Exception( NelioABErrCodes::to_string( $err ), $err );
					NelioABErrorController::build( $e );
			}

		}

	}//NelioABSelectExpEditionPageController

}

$aux = NelioABSelectExpEditionPageController::attempt_to_load_proper_controller();

