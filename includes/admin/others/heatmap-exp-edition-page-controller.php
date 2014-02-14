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


if ( !class_exists( 'NelioABHeatmapExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/heatmap-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

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
			$title = __( 'Edit Experiment', 'nelioab' );

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			// We recover the experiment (if any)
			// ----------------------------------------------

			global $nelioab_admin_controller;
			$experiment = NULL;
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABHeatmapExperiment( -1 );
				$experiment->clear();
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view();

			// Experiment information
			$view->set_experiment_id( $experiment->get_id() );
			$view->set_experiment_name( $experiment->get_name() );
			$view->set_experiment_descr( $experiment->get_description() );
			$view->set_post_id( $experiment->get_post_id() );

			$list_of_pages = get_pages();
			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );
			$list_of_posts = get_posts( $options_for_posts );

			$is_there_a_static_front_page = get_option( 'page_on_front', 0 ) > 0;
			$view->show_latest_posts_option( !$is_there_a_static_front_page );
			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );

			return $view;
		}

		public function create_view() {
			$title = __( 'Edit Experiment', 'nelioab' );
			return new NelioABHeatmapExpEditionPage( $title );
		}

		public function validate() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
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
			$this->build_experiment_from_post_data();
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

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url() .
				'admin.php?page=nelioab-experiments&action=list';
			die();
		}

		public function manage_actions() {
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

?>
