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


if ( !class_exists( 'NelioABAltExpSuperController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/post-alt-exp-edition-page.php' );

	abstract class NelioABAltExpSuperController {

		protected abstract function do_build();
		protected abstract function build_experiment_from_post_data();

		public function remove_alternative() {
			global $nelioab_admin_controller;
			if ( isset( $_POST['alt_to_remove'] ) ) {
				$alt_id = $_POST['alt_to_remove'];

				$exp = $nelioab_admin_controller->data;
				$exp->remove_alternative_by_id( $alt_id );
			}
		}

		public function update_alternative_name() {
			global $nelioab_admin_controller;
			$exp = $nelioab_admin_controller->data;

			if ( isset( $_POST['qe_alt_id'] ) &&
			     isset( $_POST['qe_alt_name'] ) ) {

				$alt_id = $_POST['qe_alt_id'];
				$alt_new_name = $_POST['qe_alt_name'];

				foreach ( $exp->get_alternatives() as $alt ) {
					if ( $alt->get_id() == $alt_id ) {
						$alt->set_name( $alt_new_name );
						$alt->mark_as_dirty();
					}
				}
			}
		}

		public function print_ajax_errors() {
			global $nelioab_admin_controller;
			if ( !is_array( $nelioab_admin_controller->validation_errors ) ||
			     count( $nelioab_admin_controller->validation_errors ) == 0 )
				return;

			$result = array(
					'msg' => '',
					'ids' => array(),
				);
			$msg = '<p>';
			$msg .= __( 'The following errors have been encountered:', 'nelioab' );
			$msg .= '</p><ul>';
			foreach( $nelioab_admin_controller->validation_errors as $error ) {
				array_push( $result['ids'], $error[0] );
				$msg .= '<li> - ' . $error[1] . '</li>';
			}
			$msg .= '</ul>';

			$result['msg'] = $msg;
			echo json_encode( $result );
			die();
		}

		public function validate() {
			global $nelioab_admin_controller;
			$exp = $nelioab_admin_controller->data;

			$errors = array();

			try {
				if ( trim( $exp->get_name() ) == '' ) {
					array_push( $errors, array ( 'exp_name',
						__( 'Naming an experiment is mandatory. Please, choose a name for the experiment.', 'nelioab' )
					)	);
				}
				else {
					$duplicated_name_found = false;
					foreach( NelioABExperimentsManager::get_experiments() as $aux ) {
						if ( !$duplicated_name_found && $exp->get_name() == $aux->get_name() &&
							$exp->get_id() != $aux->get_id()) {
							array_push( $errors, array ( 'exp_name',
								__( 'There is another experiment with the same name. Please, choose a new one.', 'nelioab' )
							)	);
							$duplicated_name_found = true;
						}
					}
				}
			}
			catch ( Exception $e ) {
				$errors = array( array ( 'none', $e->getMessage() ) );
			}

			$nelioab_admin_controller->validation_errors = $errors;
			return count( $nelioab_admin_controller->validation_errors ) == 0;
		}

		public function on_valid_submit() {
			// 1. Save the data properly
			global $nelioab_admin_controller;
			$experiment = $nelioab_admin_controller->data;
			try {
				$experiment->save();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url( 'admin.php?page=nelioab-experiments&action=list' );
			die();
		}

		public function cancel_changes() {
			// 1. Delete any new alternatives created
			global $nelioab_admin_controller;
			$exp = $nelioab_admin_controller->data;

			$exp->discard_changes();

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url( 'admin.php?page=nelioab-experiments&action=list' );
			die();
		}

		protected function compose_basic_alt_exp_using_post_data( $exp ) {
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			if ( isset( $_POST['exp_finalization_mode'] ) )
				$exp->set_finalization_mode( $_POST['exp_finalization_mode'] );
			if ( isset( $_POST['exp_finalization_value'] ) )
				$exp->set_finalization_value( $_POST['exp_finalization_value'] );

			if ( isset( $_POST['nelioab_alternatives'] ) ) {
				$alts = stripslashes( $_POST['nelioab_alternatives'] );
				$alts = json_decode( urldecode( $alts ) );
				$exp->load_json4js_alternatives( $alts );
			}

			if ( isset( $_POST['nelioab_goals'] ) ) {
				$goals = json_decode( urldecode( $_POST['nelioab_goals'] ) );
				if ( count( $goals ) > 0 ) {
					foreach ( $goals as $json_goal ) {
						$goal = NelioABAltExpGoal::build_goal_using_json4js( $json_goal, $exp );
						if ( $goal )
							$exp->add_goal( $goal );
					}
					$goals = $exp->get_goals();
					$main_goal = $goals[0];
					$main_goal->set_as_main_goal( true );
				}
			}

			return $exp;
		}

		public function manage_actions() {
			$this->build_experiment_from_post_data();

			if ( !isset( $_POST['action'] ) )
				return;

			if ( $_POST['action'] == 'save' )
				if ( $this->validate() )
					$this->on_valid_submit();

			if ( $_POST['action'] == 'cancel' )
				$this->cancel_changes();

		}

	}//NelioABAltExpSuperController

}

