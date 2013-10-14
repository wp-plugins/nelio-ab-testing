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
			// ---------------------------------------------------

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


			// Preparing labels for PAGE vs POST alternatives
			$alt_type = 'page';
			if ( $_GET['experiment-type'] === 'alt-exp-post' )
				$alt_type = 'post';


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			// ...pages...
			$list_of_pages = get_pages();
			if ( $alt_type == 'page' && count( $list_of_pages ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no pages available.', 'nelioab' ) );
				$view->render();
				return;
			}

			// ...posts...
			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );
			$list_of_posts = get_posts( $options_for_posts );
			if ( $alt_type == 'post' && count( $list_of_posts ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no posts available.', 'nelioab' ) );
				$view->render();
				return;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
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
			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );
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
