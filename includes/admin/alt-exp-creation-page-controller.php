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


if ( !class_exists( NelioABAltExpCreationPageController ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alt-exp-edition-page-controller.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/alt-exp-creation-page.php' );

	class NelioABAltExpCreationPageController extends 
			NelioABAltExpEditionPageController {

		public static function build() {
			$title = __( 'Add Experiment', 'nelioab' );

			// Check settings
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			if ( !NelioABSettings::check_user_settings() ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/invalid-config-page.php' );
				$view = new NelioABInvalidConfigPage( $title );
				$view->render();
				return;
			}

			// Preparing labels for PAGE vs POST alternatives
			$alt_type = 'page';
			if ( $_GET['experiment-type'] === 'alt-exp-post' )
				$alt_type = 'post';
			$view  = new NelioABAltExpCreationPage( $title, $alt_type );

			global $nelioab_admin_controller;
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABAlternativesExperiment( -1 );
				$experiment->clear();
			}

			$view->set_experiment( $experiment );
			$view->set_wp_pages( get_pages() );
			$view->set_wp_posts( get_posts() );
			if ( $_POST['action'] == 'show_empty_quickedit_box' )
				$view->show_empty_quickedit_box();
			if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
				$view->show_copying_content_quickedit_box();
			$view->render();
		}

	}//NelioABAltExpCreationPageController

}

if ( isset( $_POST['nelioab_new_exp_form'] ) && isset( $_POST['action'] ) ) {

	if ( $_POST['action'] == 'validate' )
		if ( NelioABAltExpCreationPageController::validate() ) 
			NelioABAltExpCreationPageController::on_valid_submit();

	if ( $_POST['action'] == 'cancel' )
		NelioABAltExpCreationPageController::cancel_changes();

	if ( $_POST['action'] == 'remove_alternative' )
		NelioABAltExpCreationPageController::remove_alternative();

	if ( $_POST['action'] == 'add_empty_alt' )
		NelioABAltExpCreationPageController::add_empty_alternative();

	if ( $_POST['action'] == 'add_alt_copying_content' )
		NelioABAltExpCreationPageController::add_alternative_copying_content();

	if ( $_POST['action'] == 'update_alternative_name' )
		NelioABAltExpCreationPageController::update_alternative_name();

	if ( $_POST['action'] == 'show_empty_quickedit_box' )
		NelioABAltExpCreationPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
		NelioABAltExpCreationPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'edit_alt_content' )
		NelioABAltExpCreationPageController::edit_alternative_content();

	if ( $_POST['action'] == 'hide_new_alt_box' )
		NelioABAltExpCreationPageController::build_experiment_from_post_data();

}

?>
