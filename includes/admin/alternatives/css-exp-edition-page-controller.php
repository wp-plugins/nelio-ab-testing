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


if ( !class_exists( 'NelioABCssExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/css-exp-edition-page.php' );

	class NelioABCssExpEditionPageController {

		protected function do_build() {
			$title = __( 'CSS Experiment', 'nelioab' );

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
				$experiment->clear();
			}


//			// Checking whether there are pages or posts available
//			// ---------------------------------------------------
//
//			// ...pages...
//			$list_of_pages = get_pages();
//
//			// ...posts...
//			$options_for_posts = array(
//				'posts_per_page' => -1,
//				'orderby'        => 'title',
//				'order'          => 'asc' );
//			$list_of_posts = get_posts( $options_for_posts );


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view();

			$view->set_experiment( $experiment );
//			$view->set_wp_pages( $list_of_pages );
//			$view->set_wp_posts( $list_of_posts );
			if ( isset( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'show_empty_quickedit_box' )
					$view->show_empty_quickedit_box();
			}
			return $view;
		}

		public function create_view() {
			$title = __( 'Edit CSS Experiment', 'nelioab' );
			return new NelioABCssExpEditionPage( $title );
		}

		public static function build() {
			$aux  = new NelioABCssExpEditionPageController();
			$view = $this->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = new NelioABCssExpEditionPageController();
			$view = $this->do_build();
			$view->render_content();
			die();
		}

		public static function remove_alternative() {
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
			if ( isset( $_POST['alt_to_remove'] ) ) {
				$alt_id = $_POST['alt_to_remove'];

				$exp = $nelioab_admin_controller->data;
				$exp->remove_alternative_by_id( $alt_id );
			}
		}

		public static function add_empty_alternative() {
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
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

		public static function update_alternative_name() {
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
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

		public static function validate() {
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
			$exp = $nelioab_admin_controller->data;

			$errors = array();

			try {
				if ( trim( $exp->get_name() ) == '' ) {
					array_push( $errors, array ( 'exp_name',
						__( 'Naming an experiment is mandatory. Please, choose a name for the experiment.', 'nelioab' )
					)	);
				}
				else {
					$mgr = new NelioABExperimentsManager();
					foreach( $mgr->get_experiments() as $aux ) {
						if ( $exp->get_name() == $aux->get_name() &&
							$exp->get_id() != $aux->get_id()) {
							array_push( $errors, array ( 'exp_name',
								__( 'There is another experiment with the same name. Please, choose a new one.', 'nelioab' )
							)	);
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

		public static function on_valid_submit() {
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

		public static function cancel_changes() {
			// 1. Delete any new alternatives created
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
			$exp = $nelioab_admin_controller->data;

			$exp->discard_changes();

			// 2. Redirect to the appropiate page
			echo '[SUCCESS]' . admin_url() .
				'admin.php?page=nelioab-experiments&action=list';
			die();
		}

		public static function edit_alternative_content() {
			// 1. Save any local changes
			global $nelioab_admin_controller;
			NelioABCssExpEditionPageController::build_experiment_from_post_data();
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

		public static function build_experiment_from_post_data() {
			$exp = new NelioABPostAlternativeExperiment( $_POST['exp_id'] );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			$exp->set_original( $_POST['exp_original'] );
			$exp->add_conversion_post( $_POST['exp_goal'] );
			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

		public static function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			if ( $_POST['action'] == 'validate' )
				if ( NelioABCssExpEditionPageController::validate() )
					NelioABCssExpEditionPageController::on_valid_submit();

			if ( $_POST['action'] == 'cancel' )
				NelioABCssExpEditionPageController::cancel_changes();

			if ( $_POST['action'] == 'remove_alternative' )
				NelioABCssExpEditionPageController::remove_alternative();

			if ( $_POST['action'] == 'add_empty_alt' )
				NelioABCssExpEditionPageController::add_empty_alternative();

			if ( $_POST['action'] == 'update_alternative_name' )
				NelioABCssExpEditionPageController::update_alternative_name();

			if ( $_POST['action'] == 'show_empty_quickedit_box' )
				NelioABCssExpEditionPageController::build_experiment_from_post_data();

			if ( $_POST['action'] == 'edit_alt_content' )
				if ( NelioABCssExpEditionPageController::validate() )
					NelioABCssExpEditionPageController::edit_alternative_content();

			if ( $_POST['action'] == 'hide_new_alt_box' )
				NelioABCssExpEditionPageController::build_experiment_from_post_data();
		}

	}//NelioABCssExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_css_exp_form'] ) )
	NelioABCssExpEditionPageController::manage_actions();

?>
