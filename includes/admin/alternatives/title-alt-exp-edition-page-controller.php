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


if ( !class_exists( 'NelioABTitleAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/page-description.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/title-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABTitleAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABTitleAltExpEditionPageController();
		}

		public static function build() {
			$aux  = NelioABTitleAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABTitleAltExpEditionPageController::get_instance();
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
				$experiment = new NelioABPostAlternativeExperiment( -1 );
				$experiment->set_type( NelioABExperiment::TITLE_ALT_EXP );
				$experiment->clear();
			}


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			// ...pages...
			$list_of_pages = get_pages();
			$options_for_posts = array(
				'posts_per_page' => 150 );
			$list_of_posts = get_posts( $options_for_posts );
			require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
			NelioABArrays::sort_posts( $list_of_posts );

			if ( count( $list_of_pages ) == 0 && count( $list_of_posts ) == 0) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no pages nor posts available.', 'nelioab' ) );
				return $view;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view();

			// Experiment information
			$view->set_original_id( $experiment->get_originals_id() );
			$view->set_experiment_id( $experiment->get_id() );
			$view->set_experiment_name( $experiment->get_name() );
			$view->set_experiment_descr( $experiment->get_description() );
			$view->set_alternatives( $experiment->get_alternatives() );
			$view->set_encoded_appspot_alternatives( $experiment->encode_appspot_alternatives() );
			$view->set_encoded_local_alternatives( $experiment->encode_local_alternatives() );
			$view->set_goal( new NelioABPageAccessedGoal( $experiment ) );

			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );
			if ( isset( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'show_quickedit_box' )
					$view->show_title_quickedit_box();
			}
			return $view;
		}

		public function create_view() {
			$title = __( 'Edit Experiment', 'nelioab' );
			return new NelioABTitleAltExpEditionPage( $title );
		}

		public function add_title_alternative() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();

			if ( isset( $_POST['new_alt_name'] ) ) {
				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$exp = $nelioab_admin_controller->data;
				$exp->create_title_alternative( $alt_name );
			}
		}

		public function validate() {
			$ok_parent = parent::validate();

			// Check whatever is needed
			$ok = true;

			return $ok_parent && $ok;
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABPostAlternativeExperiment( $_POST['exp_id'] );
			$exp->set_type( NelioABExperiment::TITLE_ALT_EXP );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			$exp->set_original( $_POST['exp_original'] );

			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			$goal = new NelioABPageAccessedGoal( $exp );
			$page = new NelioABPageDescription( $_POST['exp_original'] );
			$page->set_indirect_navigations_enabled( true );
			$goal->add_page( $page );
			$exp->add_goal( $goal );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

		public function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			parent::manage_actions();

			if ( $_POST['action'] == 'add_alt' )
				$this->add_title_alternative();

			if ( $_POST['action'] == 'show_quickedit_box' )
				$this->build_experiment_from_post_data();

			if ( $_POST['action'] == 'hide_new_alt_box' )
				$this->build_experiment_from_post_data();

		}

	}//NelioABTitleAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_title_exp_form'] ) ) {
	$controller = NelioABTitleAltExpEditionPageController::get_instance();
	$controller->manage_actions();
}

?>
