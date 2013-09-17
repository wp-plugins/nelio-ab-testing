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


if ( !class_exists( NelioABEditExperimentPageController ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/conversion-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/edit-experiment-page.php' );

	class NelioABEditExperimentPageController {

		public static function build() {
			$title = __( 'Edit Experiment', 'nelioab' );

			// Check settings
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			if ( !NelioABSettings::check_user_settings() ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/invalid-config-page.php' );
				$view = new NelioABInvalidConfigPage( $title );
				$view->render();
				return;
			}

			$view = new NelioABEditExperimentPage( $title );

			global $nelioab_admin_controller;
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
				$view->set_experiment( $experiment );
				$view->set_wp_pages( get_pages() );
				if ( $_POST['action'] == 'show_empty_quickedit_box' )
					$view->show_empty_quickedit_box();
				if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
					$view->show_copying_content_quickedit_box();
				$view->render();
			}
			else {
				$view->keep_request_param( 'id', $_GET['id'] );
				$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
			}
		}

		public static function generate_html_content() {
			// Obtain DATA from APPSPOT
			$experiments_manager = new NelioABExperimentsManager();
			$experiment = null;
			try {
				$experiment = $experiments_manager->get_experiment_by_id( $_POST['id'] );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// Render content
			$title = __( 'Edit Experiment', 'nelioab' );
			$view = new NelioABEditExperimentPage( $title );
			$view->set_experiment( $experiment );
			$view->set_wp_pages( get_pages() );
			$view->render_content();
			die();
		}

		public static function remove_alternative() {
			global $nelioab_admin_controller;
			NelioABEditExperimentPageController::build_experiment_from_post_data();
			$alt_id = $_POST['alt_to_remove'];

			$exp = $nelioab_admin_controller->data;
			$exp->remove_alternative_by_id( $alt_id );
		}

		public static function add_empty_alternative() {
			global $nelioab_admin_controller;
			NelioABEditExperimentPageController::build_experiment_from_post_data();
			$alt_name = $_POST['new_alt_name'];

			$exp = $nelioab_admin_controller->data;
			$exp->create_empty_alternative( $alt_name );
		}

		public static function add_alternative_copying_content() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			global $nelioab_admin_controller;
			NelioABEditExperimentPageController::build_experiment_from_post_data();
			$alt_name = $_POST['new_alt_name'];
			$alt_page_id = $_POST['new_alt_pageid'];

			$exp = $nelioab_admin_controller->data;
			$copy_metadata = isset( $_POST['new_alt_metadata'] );
			NelioABSettings::set_copy_metadata( $copy_metadata );
			$exp->create_alternative_copying_content( $alt_name, $alt_page_id, $copy_metadata );
		}

		public static function update_alternative_name() {
			global $nelioab_admin_controller;
			NelioABEditExperimentPageController::build_experiment_from_post_data();
			$exp = $nelioab_admin_controller->data;
			$alt_id = $_POST['qe_alt_id'];
			$alt_new_name = $_POST['qe_alt_name'];

			foreach ( $exp->get_alternatives() as $alt ) {
				if ( $alt->get_id() == $alt_id ) {
					$alt->set_name( $alt_new_name );
					$alt->mark_as_dirty();
				}
			}
		}

		public static function validate() {
			global $nelioab_admin_controller;
			NelioABEditExperimentPageController::build_experiment_from_post_data();
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
			NelioABEditExperimentPageController::build_experiment_from_post_data();
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
			NelioABEditExperimentPageController::build_experiment_from_post_data();
			$experiment = $nelioab_admin_controller->data;
			try {
				$experiment->save();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// 2. Redirect to the edit page
			$page_id = $_POST['content_to_edit'];
			echo '[SUCCESS]' . admin_url() . 'post.php?action=edit&post=' . $page_id;
			die();
		}

		public static function build_experiment_from_post_data() {
			$exp = new NelioABConversionExperiment( $_POST['exp_id'] );
			$exp->set_name( $_POST['exp_name'] );
			$exp->set_description( $_POST['exp_descr'] );
			$exp->set_original( $_POST['exp_original'] );
			$exp->set_conversion_page( $_POST['exp_goal'] );
			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

	}//NelioABEditExperimentPageController

}

if ( isset( $_POST['nelioab_edit_exp_form'] ) && isset( $_POST['action'] ) ) {

	if ( $_POST['action'] == 'validate' )
		if ( NelioABEditExperimentPageController::validate() ) 
			NelioABEditExperimentPageController::on_valid_submit();

	if ( $_POST['action'] == 'cancel' )
		NelioABEditExperimentPageController::cancel_changes();

	if ( $_POST['action'] == 'remove_alternative' )
		NelioABEditExperimentPageController::remove_alternative();

	if ( $_POST['action'] == 'add_empty_alt' )
		NelioABEditExperimentPageController::add_empty_alternative();

	if ( $_POST['action'] == 'add_alt_copying_content' )
		NelioABEditExperimentPageController::add_alternative_copying_content();

	if ( $_POST['action'] == 'update_alternative_name' )
		NelioABEditExperimentPageController::update_alternative_name();

	if ( $_POST['action'] == 'show_empty_quickedit_box' )
		NelioABEditExperimentPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
		NelioABEditExperimentPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'edit_alt_content' )
		if ( NelioABEditExperimentPageController::validate() ) 
			NelioABEditExperimentPageController::edit_alternative_content();

	if ( $_POST['action'] == 'hide_new_alt_box' )
		NelioABEditExperimentPageController::build_experiment_from_post_data();

}

?>
