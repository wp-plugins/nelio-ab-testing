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


if ( !class_exists( 'NelioABSelectExpCreationPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/select-exp-creation-page.php' );

	class NelioABSelectExpCreationPageController {

		public static function attempt_to_load_proper_controller() {
			if ( isset( $_POST['nelioab_exp_type'] ) )
				return NelioABSelectExpCreationPageController::get_controller( $_POST['nelioab_exp_type'] );
			return NULL;
		}

		public static function build() {
			$title = __( 'Experiment Type Selection', 'nelioab' );

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$controller = NelioABSelectExpCreationPageController::attempt_to_load_proper_controller();
			if ( $controller != NULL ) {
				call_user_func( array( $controller, 'build' ) );
			}
			else {
				if ( isset( $_GET['experiment-type'] ) ) {
					$controller = NelioABSelectExpCreationPageController::get_controller( $_GET['experiment-type'] );
					call_user_func( array( $controller, 'build' ) );
				}
				else {
					$view = new NelioABSelectExpCreationPage( $title );
					$view->render();
				}
			}

		}

		public static function get_controller( $type ) {

			// Determine the proper controller and give it the control...
			switch ( $type ) {
				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::PAGE_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/post-alt-exp-creation-page-controller.php' );
					return 'NelioABPostAltExpCreationPageController';

				case NelioABExperiment::THEME_ALT_EXP:
					require_once( NELIOAB_ADMIN_DIR . '/alternatives/theme-alt-exp-creation-page-controller.php' );
					return 'NelioABThemeAltExpCreationPageController';

				default:
					require_once( NELIOAB_UTILS_DIR . '/backend.php' );
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					$err = NelioABErrCodes::UNKNOWN_ERROR;
					$e = new Exception( NelioABErrCodes::to_string( $err ), $err );
					NelioABErrorController::build( $e );
			}

		}

	}//NelioABSelectExpCreationPageController

}

$aux = NelioABSelectExpCreationPageController::attempt_to_load_proper_controller();

?>
