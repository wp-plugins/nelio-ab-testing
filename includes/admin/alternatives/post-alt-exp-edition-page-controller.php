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


if ( !class_exists( 'NelioABPostAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/post-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABPostAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABPostAltExpEditionPageController();
		}

		public static function build() {
			$aux  = NelioABPostAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABPostAltExpEditionPageController::get_instance();
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

			// Preparing labels for PAGE vs POST alternatives
			// ----------------------------------------------

			$alt_type = NelioABExperiment::PAGE_ALT_EXP;
			if ( isset( $_GET['experiment-type'] ) &&
			     $_GET['experiment-type'] == NelioABExperiment::POST_ALT_EXP )
				$alt_type = NelioABExperiment::POST_ALT_EXP;


			// We recover the experiment (if any)
			// ----------------------------------------------

			global $nelioab_admin_controller;
			$experiment = NULL;
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
				$alt_type   = $experiment->get_type();
			}
			else {
				$experiment = new NelioABPostAlternativeExperiment( -1 );
				$experiment->clear();
			}


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			// ...pages...
			$list_of_pages = get_pages();
			if ( $alt_type == NelioABExperiment::PAGE_ALT_EXP && count( $list_of_pages ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no pages available.', 'nelioab' ) );
				return $view;
			}

			// ...posts...
			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );
			$list_of_posts = get_posts( $options_for_posts );
			if ( $alt_type == NelioABExperiment::POST_ALT_EXP && count( $list_of_posts ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no posts available.', 'nelioab' ) );
				return $view;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view( $alt_type );

			$view->set_experiment( $experiment );
			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );
			if ( isset( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'show_empty_quickedit_box' )
					$view->show_empty_quickedit_box();
				if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
					$view->show_copying_content_quickedit_box();
			}
			return $view;
		}

		public function create_view( $alt_type ) {
			$title = __( 'Edit Experiment', 'nelioab' );
			return new NelioABPostAltExpEditionPage( $title, $alt_type );
		}

		public function add_empty_alternative() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
			$exp_type = NelioABExperiment::PAGE_ALT_EXP;
			if ( isset( $_POST['nelioab_exp_type'] ) &&
			     $_POST['nelioab_exp_type'] == NelioABExperiment::POST_ALT_EXP )
				$exp_type = NelioABExperiment::POST_ALT_EXP;

			if ( isset( $_POST['new_alt_name'] ) ) {
				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$exp = $nelioab_admin_controller->data;
				$exp->create_empty_alternative( $alt_name, $exp_type );
			}
		}

		public function add_alternative_copying_content() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();

			if ( isset( $_POST['new_alt_name'] ) &&
			     isset( $_POST['new_alt_postid'] ) ) {

				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$alt_post_id = $_POST['new_alt_postid'];
	
				$exp = $nelioab_admin_controller->data;
				$exp->create_alternative_copying_content( $alt_name, $alt_post_id );
			}
		}

		public function validate() {
			$ok_parent = parent::validate();

			// Check whatever is needed
			$ok = true;

			return $ok_parent && $ok;
		}

		public function edit_alternative_content() {
			// 1. Save any local changes
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

			// 2. Redirect to the edit page
			$post_id = 0;
			if ( isset( $_POST['content_to_edit'] ) )
				$post_id = $_POST['content_to_edit'];
			echo '[SUCCESS]' . admin_url() . 'post.php?action=edit&post=' . $post_id;
			die();
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABPostAlternativeExperiment( $_POST['exp_id'] );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			$exp->set_original( $_POST['exp_original'] );
			$exp->set_conversion_post( $_POST['exp_goal'] );
			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

		public function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			parent::manage_actions();

			if ( $_POST['action'] == 'add_empty_alt' )
				$this->add_empty_alternative();

			if ( $_POST['action'] == 'add_alt_copying_content' )
				$this->add_alternative_copying_content();

			if ( $_POST['action'] == 'show_empty_quickedit_box' )
				$this->build_experiment_from_post_data();

			if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
				$this->build_experiment_from_post_data();

			if ( $_POST['action'] == 'edit_alt_content' )
				if ( $this->validate() )
					$this->edit_alternative_content();

			if ( $_POST['action'] == 'hide_new_alt_box' )
				$this->build_experiment_from_post_data();

		}

	}//NelioABPostAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_post_exp_form'] ) ) {
	$controller = NelioABPostAltExpEditionPageController::get_instance();
	$controller->manage_actions();
}

?>
