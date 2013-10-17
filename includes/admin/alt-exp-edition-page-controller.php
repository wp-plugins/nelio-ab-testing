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


if ( !class_exists( 'NelioABAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alt-exp-edition-page.php' );

	class NelioABAltExpEditionPageController {

		private static function do_build() {
			$title = __( 'Edit Experiment', 'nelioab' );

			// Check settings
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			try {
				$aux = NelioABSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				switch ( $e->getCode() ) {
					case NelioABErrCodes::DEACTIVATED_USER:
						require_once( NELIOAB_ADMIN_DIR . '/views/errors/deactivated-user-page.php' );
						$view = new NelioABDeactivatedUserPage();
						$view->render();
						return;
					case NelioABErrCodes::INVALID_MAIL:
					case NelioABErrCodes::INVALID_PRODUCT_REG_NUM:
					case NelioABErrCodes::NON_ACCEPTED_TAC:
					case NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED:
						require_once( NELIOAB_ADMIN_DIR . '/views/errors/invalid-config-page.php' );
						$view = new NelioABInvalidConfigPage( $title );
						$view->render();
						return;
					default:
						break;
				}
			}

			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );

			global $nelioab_admin_controller;
			$experiment = $nelioab_admin_controller->data;
			$view = new NelioABAltExpEditionPage( $title, $experiment->get_alt_type() );
			$view->set_experiment( $experiment );
			$view->set_wp_pages( get_pages() );
			$view->set_wp_posts( get_posts( $options_for_posts ) );

			if ( isset( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'show_empty_quickedit_box' )
					$view->show_empty_quickedit_box();
				if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
					$view->show_copying_content_quickedit_box();
			}

			return $view;
		}

		public static function build() {
			$view = NelioABAltExpEditionPageController::do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$view = NelioABAltExpEditionPageController::do_build();
			$view->render_content();
			die();
		}

		public static function remove_alternative() {
			global $nelioab_admin_controller;
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
			if ( isset( $_POST['alt_to_remove'] ) ) {
				$alt_id = $_POST['alt_to_remove'];

				$exp = $nelioab_admin_controller->data;
				$exp->remove_alternative_by_id( $alt_id );
			}
		}

		public static function add_empty_alternative() {
			global $nelioab_admin_controller;
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
			$exp_type = 'page';
			if ( isset( $_POST['nelioab_edit_exp_type'] ) &&
			     $_POST['nelioab_edit_exp_type'] === 'alt-exp-post' )
				$exp_type = 'post';

			if ( isset( $_POST['new_alt_name'] ) ) {
				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$exp = $nelioab_admin_controller->data;
				$exp->create_empty_alternative( $alt_name, $exp_type );
			}
		}

		public static function add_alternative_copying_content() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			global $nelioab_admin_controller;
			NelioABAltExpEditionPageController::build_experiment_from_post_data();

			if ( isset( $_POST['new_alt_name'] ) &&
			     isset( $_POST['new_alt_postid'] ) &&
			     isset( $_POST['new_alt_metadata'] ) ) {

				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$alt_post_id = $_POST['new_alt_postid'];
	
				$exp = $nelioab_admin_controller->data;
				$copy_metadata = isset( $_POST['new_alt_metadata'] );
				NelioABSettings::set_copy_metadata( $copy_metadata );
				$exp->create_alternative_copying_content( $alt_name, $alt_post_id, $copy_metadata );
			}
		}

		public static function update_alternative_name() {
			global $nelioab_admin_controller;
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
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
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
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

//			if ( count( $nelioab_admin_controller->validation_errors ) == 0 ) {
//				return true;
//			}
//			else {
//				echo '[ERRORS]' . json_encode( $errors );
//				die();
//			}

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
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
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
			NelioABAltExpEditionPageController::build_experiment_from_post_data();
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
			$exp = new NelioABAlternativesExperiment( $_POST['exp_id'] );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );
			$exp->set_original( $_POST['exp_original'] );
			$exp->set_conversion_post( $_POST['exp_goal'] );
			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

	}//NelioABAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_exp_form'] ) && isset( $_POST['action'] ) ) {

	if ( $_POST['action'] == 'validate' )
		if ( NelioABAltExpEditionPageController::validate() ) 
			NelioABAltExpEditionPageController::on_valid_submit();

	if ( $_POST['action'] == 'cancel' )
		NelioABAltExpEditionPageController::cancel_changes();

	if ( $_POST['action'] == 'remove_alternative' )
		NelioABAltExpEditionPageController::remove_alternative();

	if ( $_POST['action'] == 'add_empty_alt' )
		NelioABAltExpEditionPageController::add_empty_alternative();

	if ( $_POST['action'] == 'add_alt_copying_content' )
		NelioABAltExpEditionPageController::add_alternative_copying_content();

	if ( $_POST['action'] == 'update_alternative_name' )
		NelioABAltExpEditionPageController::update_alternative_name();

	if ( $_POST['action'] == 'show_empty_quickedit_box' )
		NelioABAltExpEditionPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
		NelioABAltExpEditionPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'edit_alt_content' )
		if ( NelioABAltExpEditionPageController::validate() ) 
			NelioABAltExpEditionPageController::edit_alternative_content();

	if ( $_POST['action'] == 'hide_new_alt_box' )
		NelioABAltExpEditionPageController::build_experiment_from_post_data();

}

?>
