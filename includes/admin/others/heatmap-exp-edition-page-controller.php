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


if ( !class_exists( 'NelioABHeatmapExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/heatmap-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/others/heatmap-exp-edition-page.php' );

	class NelioABHeatmapExpEditionPageController {

		public static function get_instance() {
			return new NelioABHeatmapExpEditionPageController();
		}

		public static function build() {
			$aux  = NelioABHeatmapExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABHeatmapExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		protected function do_build() {
			$title = __( 'Edit Heatmap Experiment', 'nelioab' );

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			// We recover the experiment (if any)
			// ----------------------------------------------

			global $nelioab_admin_controller;
			$experiment = NULL;
			$other_names = array();
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABHeatmapExperiment( -1 );
				$experiment->clear();
			}


			// ...and we also recover other experiment names (if any)
			if ( isset( $_POST['other_names'] ) ) {
				$other_names = json_decode( urldecode( $_POST['other_names'] ) );
			}
			else {
				$mgr = new NelioABExperimentsManager();
				foreach( $mgr->get_experiments() as $aux ) {
					if ( $aux->get_id() != $experiment->get_id() )
						array_push( $other_names, $aux->get_name() );
				}
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view();
			foreach ( $other_names as $name )
				$view->add_another_experiment_name( $name );


			// Experiment information
			$view->set_experiment_id( $experiment->get_id() );
			$view->set_experiment_name( $experiment->get_name() );
			$view->set_experiment_descr( $experiment->get_description() );
			$view->set_post_id( $experiment->get_post_id() );

			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			// ...pages...
			$list_of_pages = get_pages();
			$options_for_posts = array(
				'posts_per_page' => 1 );
			$list_of_posts = get_posts( $options_for_posts );
			require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
			NelioABArrays::sort_posts( $list_of_posts );

			if ( count( $list_of_pages ) + count( $list_of_posts ) == 0) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no pages nor posts available.', 'nelioab' ) );
				return $view;
			}

			$is_there_a_static_front_page = get_option( 'page_on_front', 0 ) > 0;
			$view->show_latest_posts_option( !$is_there_a_static_front_page );

			return $view;
		}

		public function create_view() {
			$title = __( 'Edit Heatmap Experiment', 'nelioab' );
			return new NelioABHeatmapExpEditionPage( $title );
		}

		public function validate() {
			global $nelioab_admin_controller;
			$exp = $nelioab_admin_controller->data;

			$errors = array();

			try {
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
			catch ( Exception $e ) {
				$errors = array( array ( 'none', $e->getMessage() ) );
			}

			$nelioab_admin_controller->validation_errors = $errors;
			return count( $nelioab_admin_controller->validation_errors ) == 0;
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABHeatmapExperiment( $_POST['exp_id'] );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			$exp->set_post_id( stripslashes( $_POST['exp_post_id'] ) );
			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
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

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url( 'admin.php?page=nelioab-experiments&action=list' );
			die();
		}

		public function manage_actions() {
			$this->build_experiment_from_post_data();

			if ( !isset( $_POST['action'] ) )
				return;

			if ( $_POST['action'] == 'validate' )
				if ( $this->validate() )
					$this->on_valid_submit();

			if ( $_POST['action'] == 'cancel' )
				$this->cancel_changes();

		}

	}//NelioABHeatmapExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_heatmap_exp_form'] ) ) {
	$controller = NelioABHeatmapExpEditionPageController::get_instance();
	$controller->manage_actions();
}
