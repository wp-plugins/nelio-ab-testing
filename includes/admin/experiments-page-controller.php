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

if ( !class_exists( 'NelioABExperimentsPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/experiments-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	class NelioABExperimentsPageController {

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$title = __( 'Experiments', 'nelioab' );
			$view = new NelioABExperimentsPage( $title );

			// Some GET options require APPSPOT connection. In order to make
			// them available in the ``generate_html_content'' method, we
			// use our ``keep_request_param'' function.

			if ( isset( $_GET['_nonce'] ) )
				 // Used for sorting
				$view->keep_request_param( '_nonce', $_GET['_nonce'] );

			if ( isset( $_GET['status'] ) )
				 // Used for sorting
				$view->keep_request_param( 'status', $_GET['status'] );

			if ( isset( $_GET['action'] ) )
				// Which GET action was requested
				$view->keep_request_param( 'GET_action', $_GET['action'] );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'exp_id', $_GET['id'] );

			if ( isset( $_GET['exp_type'] ) )
				// The type of the experiment (relevant along with its ID)
				$view->keep_request_param( 'exp_type', $_GET['exp_type'] );

			if ( isset( $_GET['schedule_date'] ) )
				// Scheduled Date is relevant for scheduling an experiment
				$view->keep_request_param( 'schedule_date', $_GET['schedule_date'] );

			if ( isset( $_GET['name'] ) )
				// Scheduled Date is relevant for scheduling an experiment
				$view->keep_request_param( 'name', $_GET['name'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			// Before rendering content we check whether an action was required
			if ( isset( $_REQUEST['GET_action'] ) &&
			     isset( $_REQUEST['exp_id'] ) &&
			     isset( $_REQUEST['exp_type'] ) ) {

				switch ( $_REQUEST['GET_action'] ) {
					case 'schedule':
						NelioABExperimentsPageController::schedule_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'], $_REQUEST['schedule_date'] );
						break;
					case 'cancel-schedule':
						NelioABExperimentsPageController::cancel_scheduling_of_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'start':
						NelioABExperimentsPageController::start_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'duplicate':
						$new_name = 'New Experiment';
						if ( isset( $_REQUEST['name'] ) && !empty( $_REQUEST['name'] ) )
							$new_name = $_REQUEST['name'];
						NelioABExperimentsPageController::duplicate_experiment( $new_name, $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'stop':
						NelioABExperimentsPageController::stop_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'trash':
						NelioABExperimentsPageController::trash_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'restore':
						NelioABExperimentsPageController::untrash_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					case 'delete':
						NelioABExperimentsPageController::remove_experiment( $_REQUEST['exp_id'], $_REQUEST['exp_type'] );
						break;
					default:
						// Nothing to be done by default

						// REMEMBER: if something has to be done "by default", YOU must pay
						// attention to the IF checking of ISSET variables...
				}
			}

			// Obtain DATA from APPSPOT
			$experiments = array();
			try {
				$experiments = NelioABExperimentsManager::get_experiments();
			}
			catch( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// Clean inconsistent data
			self::clean_inconsistent_data( $experiments );

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

		/**
		 *
		 * @return
		 */
		private static function clean_inconsistent_data( $experiments ) {
			if ( count( $experiments ) == 0 )
				return;
			$last_clean = get_option( 'nelioab_last_clean_of_inconsitent_data', false );
			if ( NELIOAB_PLUGIN_VERSION == $last_clean )
				return;
			$ids = array();
			foreach ( $experiments as $exp )
				array_push( $ids, $exp->get_id() );

			// Clean old widgets
			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
			NelioABWidgetExpAdminController::remove_alternatives_not_in( $ids );

			update_option( 'nelioab_last_clean_of_inconsitent_data', NELIOAB_PLUGIN_VERSION );
		}


		public static function remove_experiment( $exp_id, $exp_type ) {
			try {
				NelioABExperimentsManager::remove_experiment_by_id( $exp_id, $exp_type );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function start_experiment( $exp_id, $exp_type ) {
			$exp = NULL;

			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			try {
				if ( $exp == NULL ) {
					$err = NelioABErrCodes::INVALID_EXPERIMENT;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
				$exp->start();
				NelioABExperimentsManager::update_running_experiments_cache( 'now' );
			}
			catch ( Exception $e ) {
				global $nelioab_admin_controller;
				switch ( $e->getCode() ) {
					case NelioABErrCodes::MULTI_PAGE_GOAL_NOT_ALLOWED_IN_BASIC:
					case NelioABErrCodes::HEATMAP_NOT_ALLOWED_IN_BASIC:
						$nelioab_admin_controller->error_message = $e->getMessage();
						return;

					case NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED:
						$nelioab_admin_controller->error_message = $e->getMessage();
						return;

					default:
						require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
						NelioABErrorController::build( $e );
				}

			}
		}

		public static function schedule_experiment( $exp_id, $exp_type, $date ) {
			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				$exp->schedule( $date );
			}
			catch ( Exception $e ) {
				global $nelioab_admin_controller;
				switch ( $e->getCode() ) {
					case NelioABErrCodes::INVALID_SCHEDULE_DATE:
						$nelioab_admin_controller->error_message = $e->getMessage();
						return;

					default:
						require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
						NelioABErrorController::build( $e );
				}
			}
		}

		public static function cancel_scheduling_of_experiment( $exp_id, $exp_type ) {
			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				$exp->cancel_scheduling();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function stop_experiment( $exp_id, $exp_type ) {
			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				$exp->stop();
				NelioABExperimentsManager::update_running_experiments_cache( 'now' );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function trash_experiment( $exp_id, $exp_type ) {
			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				$exp->update_status_and_save( NelioABExperiment::STATUS_TRASH );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function untrash_experiment( $exp_id, $exp_type ) {
			try {
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
				$exp->untrash();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}
		}

		public static function duplicate_experiment( $new_name, $exp_id, $exp_type ) {
			$exp = NULL;

			try {
				$action = 'duplicate-' . $exp_id;
				if ( !isset( $_REQUEST['_nonce'] ) ||
				     !nelioab_verify_onetime_nonce( $_REQUEST['_nonce'], $action ) ) {
					require_once( NELIOAB_UTILS_DIR . '/backend.php' );
					$err = NelioABErrCodes::INVALID_NONCE;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
				$exp = NelioABExperimentsManager::get_experiment_by_id( $exp_id, $exp_type );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			try {
				if ( $exp == NULL ) {
					$err = NelioABErrCodes::INVALID_EXPERIMENT;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}
				$exp->duplicate( $new_name );
			}
			catch ( Exception $e ) {
				global $nelioab_admin_controller;
				switch ( $e->getCode() ) {
					case NelioABErrCodes::EXPERIMENT_CANNOT_BE_DUPLICATED:
						$nelioab_admin_controller->error_message = $e->getMessage();
						return;

					default:
						require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
						NelioABErrorController::build( $e );
				}

			}
		}

		}//NelioABExperimentsPageController

}

