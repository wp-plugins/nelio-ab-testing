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


if ( !class_exists( NelioABExperimentsPageController ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/experiments-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	class NelioABExperimentsPageController {

		public static function build() {
			$title = __( 'Experiments', 'nelioab' );

			// Check settings
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			if ( !NelioABSettings::check_user_settings() ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/invalid-config-page.php' );
				$view = new NelioABInvalidConfigPage( $title );
				$view->render();
				return;
			}

			$view = new NelioABExperimentsPage( $title );

			// Some GET options require APPSPOT connection. In order to make
			// them available in the ``generate_html_content'' method, we
			// use our ``keep_request_param'' function.

			if ( isset( $_GET['status'] ) )
				 // Used for sorting
				$view->keep_request_param( 'status', $_REQUEST['status'] );

			if ( isset( $_GET['action'] ) )
				// Which GET action was requested
				$view->keep_request_param( 'GET_action', $_REQUEST['action'] );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'exp_id', $_REQUEST['id'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			// Before rendering content we check whether an action was required
			switch ( $_REQUEST['GET_action'] ) {
				case 'start':
					NelioABExperimentsPageController::start_experiment( $_REQUEST['exp_id'] );
					break;
				case 'stop':
					NelioABExperimentsPageController::stop_experiment( $_REQUEST['exp_id'] );
					break;
				case 'trash':
					NelioABExperimentsPageController::trash_experiment( $_REQUEST['exp_id'] );
					break;
				case 'restore':
					NelioABExperimentsPageController::untrash_experiment( $_REQUEST['exp_id'] );
					break;
				case 'delete':
					NelioABExperimentsPageController::remove_experiment( $_REQUEST['exp_id'] );
					break;
				default:
					// Nothing to be done by default
			}

			// Obtain DATA from APPSPOT
			$mgr = new NelioABExperimentsManager();
			$experiments = array();
			try {
				$experiments = $mgr->get_experiments();
			}
			catch( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// Render content
			$title = __( 'Experiments', 'nelioab' );
			$view  = new NelioABExperimentsPage( $title );
			$view->set_experiments( $experiments );
			if ( isset( $_REQUEST['status'] ) )
				$view->filter_by_status( $_REQUEST['status'] );
			$view->render_content();

			// Update cache
			NelioABExperimentsManager::update_running_experiments_cache();

			die();
		}

		public static function remove_experiment( $exp_id ) {
			$mgr = new NelioABExperimentsManager();
			try {
				$mgr->remove_experiment_by_id( $exp_id );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function start_experiment( $exp_id ) {
			try {
				$mgr = new NelioABExperimentsManager();
				$exp = $mgr->get_experiment_by_id( $exp_id );
				$exp->start();
				NelioABExperimentsManager::update_running_experiments_cache( true );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function stop_experiment( $exp_id ) {
			try {
				$mgr = new NelioABExperimentsManager();
				$exp = $mgr->get_experiment_by_id( $exp_id );
				$exp->stop();
				NelioABExperimentsManager::update_running_experiments_cache( true );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function trash_experiment( $exp_id ) {
			try {
				$mgr = new NelioABExperimentsManager();
				$exp = $mgr->get_experiment_by_id( $exp_id );
				$exp->update_status_and_save( NelioABExperimentStatus::TRASH );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function untrash_experiment( $exp_id ) {
			try {
				$mgr = new NelioABExperimentsManager();
				$exp = $mgr->get_experiment_by_id( $exp_id );
				$exp->untrash();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

	}//NelioABExperimentsPageController

}

?>
