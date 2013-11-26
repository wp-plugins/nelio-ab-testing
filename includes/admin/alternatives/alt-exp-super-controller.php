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
	require_once( NELIOAB_MODELS_DIR . '/page-description.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/post-alt-exp-edition-page.php' );

	abstract class NelioABAltExpSuperController {

		abstract public static function get_instance();
		abstract public static function build();
		abstract public static function generate_html_content();

		abstract protected function do_build();
		abstract protected function build_experiment_from_post_data();

		public function remove_alternative() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
			if ( isset( $_POST['alt_to_remove'] ) ) {
				$alt_id = $_POST['alt_to_remove'];

				$exp = $nelioab_admin_controller->data;
				$exp->remove_alternative_by_id( $alt_id );
			}
		}

		public function update_alternative_name() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
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

		public function validate() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
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
					$mgr = new NelioABExperimentsManager();
					foreach( $mgr->get_experiments() as $aux ) {
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
			echo '[SUCCESS]' . admin_url() .
				'admin.php?page=nelioab-experiments&action=list';
			die();
		}

		public function cancel_changes() {
			// 1. Delete any new alternatives created
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
			$exp = $nelioab_admin_controller->data;

			$exp->discard_changes();

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url() .
				'admin.php?page=nelioab-experiments&action=list';
			die();
		}

		public function build_goal_from_post_data( $experiment ) {
			$exp_goal = new NelioABPageAccessedGoal( $experiment );
			if ( isset( $_POST['goal_id'] ) )
				$exp_goal->set_id( $_POST['goal_id'] );

			$goals_from_post = array();
			if ( isset( $_POST['exp_goal'] ) ) {
				if ( is_array( $_POST['exp_goal'] ) )
					$goals_from_post = $_POST['exp_goal'];
				else
					$goals_from_post = array( $_POST['exp_goal'] );
			}

			foreach ( $goals_from_post as $goal ) {
				if ( is_numeric( $goal ) ) {
					$page = new NelioABPageDescription( $goal );
					$exp_goal->add_page( $page );
				}
				else {
					$goal = json_decode( urldecode( $goal ) );
					$page = new NelioABPageDescription( $goal->url, false );
					$page->set_title( $goal->name );
					$exp_goal->add_page( $page );
				}
			}
			return $exp_goal;
		}

		public function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			if ( $_POST['action'] == 'validate' )
				if ( $this->validate() )
					$this->on_valid_submit();

			if ( $_POST['action'] == 'cancel' )
				$this->cancel_changes();

			if ( $_POST['action'] == 'remove_alternative' )
				$this->remove_alternative();

			if ( $_POST['action'] == 'update_alternative_name' )
				$this->update_alternative_name();

		}

	}//NelioABAltExpSuperController

}

?>
