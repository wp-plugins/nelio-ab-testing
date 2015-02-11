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

if ( !class_exists( 'NelioABWidgetAltExpProgressPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/progress/widget-alt-exp-progress-page.php' );
	require_once( NELIOAB_ADMIN_DIR . '/progress/alt-exp-progress-super-controller.php' );

	class NelioABWidgetAltExpProgressPageController extends NelioABAltExpProgressSuperController {

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$title = __( 'Results of the Experiment', 'nelioab' );
			$view  = new NelioABWidgetAltExpProgressPage( $title );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'exp_id', $_GET['id'] );

			if ( isset( $_GET['exp_type'] ) )
				$view->keep_request_param( 'exp_type', $_GET['exp_type'] );

			if ( isset( $_GET['goal'] ) )
				$view->keep_request_param( 'goal', $_GET['goal'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {

			global $nelioab_admin_controller;
			if ( isset( $nelioab_admin_controller->data ) ) {
				$exp    = $nelioab_admin_controller->data;
				$exp_id = $exp->get_id();
			}
			else {
				$exp_id = -time();
				if ( isset( $_REQUEST['exp_id'] ) )
					$exp_id = $_REQUEST['exp_id'];

				$exp_type = -1;
				if ( isset( $_POST['exp_type'] ) )
					$exp_type = $_POST['exp_type'];

				$exp = null;

				try {
					$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				}
				catch ( Exception $e ) {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}
			}

			$title = __( 'Results of the Experiment', 'nelioab' );
			$view  = new NelioABWidgetAltExpProgressPage( $title );
			$view->set_experiment( $exp );

			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
			$widgets_in_experiments = NelioABWidgetExpAdminController::get_widgets_in_experiments();
			$are_there_alternatives_to_apply = false;
			foreach ( $widgets_in_experiments as $info )
				if ( $info['exp'] == $exp->get_id() )
					$are_there_alternatives_to_apply = true;
			$view->set_there_are_alternatives_to_apply( $are_there_alternatives_to_apply );

			$goals = $exp->get_goals();
			$view->set_goals( $goals );

			$goal_id = -1;
			if ( isset( $_REQUEST['goal'] ) )
				$goal_id = $_REQUEST['goal'];
			$view->set_current_selected_goal( $goal_id );

			$view->render_content();

			die();
		}

		public function apply_alternative() {
			if ( isset( $_POST['original'] ) && isset( $_POST['alternative'] ) ) {
				require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
				$exp = $_GET['id'];
				$ori = $_POST['original'];
				$alt = $_POST['alternative'];
				if ( $ori == $alt )
					NelioABWidgetExpAdminController::apply_alternative_and_clean( $exp );
				else
					NelioABWidgetExpAdminController::apply_alternative_and_clean( $exp, $alt );
			}
		}

	}//NelioABWidgetAltExpProgressPageController

}

$aux = new NelioABWidgetAltExpProgressPageController();
$aux->manage_actions();

