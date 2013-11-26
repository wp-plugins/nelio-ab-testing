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


if ( !class_exists( 'NelioABPostAltExpProgressPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/progress/post-alt-exp-progress-page.php' );
	require_once( NELIOAB_ADMIN_DIR . '/progress/alt-exp-progress-super-controller.php' );

	class NelioABPostAltExpProgressPageController extends NelioABAltExpProgressSuperController {

		public static function build() {
			$title = __( 'Results of the Experiment', 'nelioab' );
			$view  = new NelioABPostAltExpProgressPage( $title );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'exp_id', $_GET['id'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {

			global $nelioab_admin_controller;
			if ( isset( $nelioab_admin_controller->data ) ) {
				$exp    = $nelioab_admin_controller->data;
				$exp_id = $exp->get_id();
			}
			else {
				$exp_id = -1;
				if ( isset( $_REQUEST['exp_id'] ) )
					$exp_id = $_REQUEST['exp_id'];
	
				$exp_type = -1;
				if ( isset( $_POST['exp_type'] ) )
					$exp_type = $_POST['exp_type'];

				$mgr = new NelioABExperimentsManager();
				$exp = null;
	
				try {
					$exp = $mgr->get_experiment_by_id( $exp_id, $exp_type );
				}
				catch ( Exception $e ) {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}
			}

			$title = __( 'Results of the Experiment', 'nelioab' );
			$view  = new NelioABPostAltExpProgressPage( $title );
			$view->set_experiment( $exp );

			try {
				// The function "get_results" may throw an exception. This is why
				// we use it in here.
				$goals = $exp->get_goals();
				$goal  = $goals[0];
				$view->set_results( $goal->get_results() );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_UTILS_DIR . '/backend.php' );
				if ( $e->getCode() == NelioABErrCodes::RESULTS_NOT_AVAILABLE_YET ) {
					$view->set_results( null );
				}
				else {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}
			}

			$view->render_content();

			die();
		}

		public function apply_alternative() {
			if ( isset( $_POST['original'] ) && isset( $_POST['alternative'] ) ) {
				$ori_id = $_POST['original'];
				$alt_id = $_POST['alternative'];

				$categories = isset( $_POST['copy_categories'] ) && $_POST['copy_categories'] == 'on';
				$tags       = isset( $_POST['copy_tags'] ) && $_POST['copy_tags'] == 'on';
				$meta       = isset( $_POST['copy_meta'] ) && $_POST['copy_meta'] == 'on';

				require_once( NELIOAB_MODELS_DIR . '/settings.php' );
				NelioABSettings::set_copy_metadata( $meta );
				NelioABSettings::set_copy_categories( $categories );
				NelioABSettings::set_copy_tags( $tags );

				require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				NelioABWPHelper::override( $ori_id, $alt_id, $meta, $categories, $tags );
				echo 'OK';
				die();
			}
		}

	}//NelioABPostAltExpProgressPageController

}

$aux = new NelioABPostAltExpProgressPageController();
$aux->manage_actions();

?>
