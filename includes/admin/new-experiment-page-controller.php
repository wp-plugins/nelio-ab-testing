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


if ( !class_exists( NelioABNewExperimentPageController ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/conversion-experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/edit-experiment-page-controller.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/new-experiment-page.php' );

	class NelioABNewExperimentPageController extends 
			NelioABEditExperimentPageController {

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

			$view  = new NelioABNewExperimentPage( $title );

			global $nelioab_admin_controller;
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABConversionExperiment( -1 );
				$experiment->clear();
			}

			$view->set_experiment( $experiment );
			$view->set_wp_pages( get_pages() );
			if ( $_POST['action'] == 'show_empty_quickedit_box' )
				$view->show_empty_quickedit_box();
			if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
				$view->show_copying_content_quickedit_box();
			$view->render();
		}

	}//NelioABNewExperimentPageController

}

if ( isset( $_POST['nelioab_new_exp_form'] ) && isset( $_POST['action'] ) ) {

	if ( $_POST['action'] == 'validate' )
		if ( NelioABNewExperimentPageController::validate() ) 
			NelioABNewExperimentPageController::on_valid_submit();

	if ( $_POST['action'] == 'cancel' )
		NelioABNewExperimentPageController::cancel_changes();

	if ( $_POST['action'] == 'remove_alternative' )
		NelioABNewExperimentPageController::remove_alternative();

	if ( $_POST['action'] == 'add_empty_alt' )
		NelioABNewExperimentPageController::add_empty_alternative();

	if ( $_POST['action'] == 'add_alt_copying_content' )
		NelioABNewExperimentPageController::add_alternative_copying_content();

	if ( $_POST['action'] == 'update_alternative_name' )
		NelioABNewExperimentPageController::update_alternative_name();

	if ( $_POST['action'] == 'show_empty_quickedit_box' )
		NelioABNewExperimentPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'show_copying_content_quickedit_box' )
		NelioABNewExperimentPageController::build_experiment_from_post_data();

	if ( $_POST['action'] == 'edit_alt_content' )
		NelioABNewExperimentPageController::edit_alternative_content();

	if ( $_POST['action'] == 'hide_new_alt_box' )
		NelioABNewExperimentPageController::build_experiment_from_post_data();

}

?>
