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


if ( !class_exists( 'NelioABSettingsPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/settings.php' );

	class NelioABSettingsPageController {

		public static function build() {
			require_once( NELIOAB_ADMIN_DIR . '/views/settings-page.php' );

			$title = __( 'Settings', 'nelioab' );

			$view = new NelioABSettingsPage( $title );
			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			require_once( NELIOAB_ADMIN_DIR . '/views/settings-page.php' );

			// Check data against APPENGINE
			$email   = NelioABSettings::get_email();
			$reg_num = NelioABSettings::get_reg_num();

			$sites = array();
			$max_sites = 1;
			try {
				NelioABSettings::validate_email_and_reg_num( $email, $reg_num );
			}
			catch ( Exception $e ) {}

			$current_site_status = NelioABSite::NOT_REGISTERED;
			$error_retrieving_registered_sites = false;
			try {
				$sites_info = NelioABSettings::get_registered_sites_information();
				$max_sites  = $sites_info->get_max_sites();
				$sites      = $sites_info->get_registered_sites();

				if ( NelioABSettings::has_a_configured_site() ) {
					$site_id = NelioABSettings::get_site_id();
					$current_site_status = NelioABSite::INVALID_ID;
					foreach( $sites as $site ) {
						if ( $site->get_id() == $site_id ) {
							if ( $site->get_url() == get_option( 'siteurl' ) )
								$current_site_status = NelioABSite::ACTIVE;
							else
								$current_site_status = NelioABSite::NON_MATCHING_URLS;
						}
					}
				}

			}
			catch ( Exception $e ) {
				$error_retrieving_registered_sites = true;
			}

			// Querying account information
			$user_info = array();
			$user_info['firstname']    = '&ndash;';
			$user_info['lastname']     = '&ndash;';
			try {
				$url  = sprintf( NELIOAB_BACKEND_URL . '/customer/%s', NelioABSettings::get_customer_id() );
				$json = NelioABBackend::remote_get( $url, true );
				$json = json_decode( $json['body'] );

				$user_info['firstname']         = $json->firstname;
				$user_info['lastname']          = $json->lastname;
				$user_info['subscription_url']  = $json->subscriptionUrl;
				$user_info['subscription_plan'] = $json->subscriptionPlan;
				$user_info['status']            = $json->status;
				$user_info['total_quota']       = intval( $json->quotaPerMonth );
				$user_info['quota']             = intval( $json->quota + $json->quotaExtra );

				// TODO: fix agency info
				$user_info['agency']            = false;
				$user_info['agencyname']        = 'Nelio Software';
				$user_info['agencymail']        = 'cusomters@neliosoftware.com';

			}
			catch ( Exception $e ) {
			}

			// Render content
			$title = __( 'Settings', 'nelioab' );
			$view = new NelioABSettingsPage( $title );
			$view->set_email( $email );
			$view->set_email_validity( NelioABSettings::is_email_valid() );
			$view->set_reg_num( $reg_num );
			$view->set_reg_num_validity( NelioABSettings::is_reg_num_valid() );
			$view->set_tac_checked( NelioABSettings::are_terms_and_conditions_accepted() );
			$view->set_registered_sites( $sites );
			$view->set_max_sites( $max_sites );
			$view->set_current_site_status( $current_site_status );
			$view->set_user_info( $user_info );
			if ( $error_retrieving_registered_sites )
				$view->set_error_retrieving_registered_sites();
			$view->render_content();
			die();
		}

		public static function validate_account() {
			global $nelioab_admin_controller;

			$email = '';
			if ( isset( $_POST['settings_email'] ) )
				$email = $_POST['settings_email'];

			$reg_num = '';
			if ( isset( $_POST['settings_reg_num'] ) )
				$reg_num = $_POST['settings_reg_num'];

			$errors = array();
			try {
				NelioABSettings::validate_email_and_reg_num( $email, $reg_num );
				$nelioab_admin_controller->message =
					__( 'Account information was successfully updated.', 'nelioab' );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_UTILS_DIR . '/backend.php' );
				$errCode = $e->getCode();

				if ( $errCode == NelioABErrCodes::INVALID_PRODUCT_REG_NUM )
					array_push( $errors, array ( 'settings_reg_num',
						__( 'Invalid Registration Number', 'nelioab' )
					)	);

				if ( $errCode == NelioABErrCodes::INVALID_MAIL )
					array_push( $errors, array ( 'settings_email',
						__( 'E-Mail is not registered in our service', 'nelioab' )
					)	);

			}

			$settings_tac = false;
			if ( isset( $_POST['settings_tac'] ) )
				$settings_tac = $_POST['settings_tac'];
			NelioABSettings::check_terms_and_conditions( $settings_tac );

			$nelioab_admin_controller->validation_errors = $errors;
			return count( $nelioab_admin_controller->validation_errors ) == 0;
		}

		public static function manage_site_registration() {
			global $nelioab_admin_controller;

			if ( isset( $_POST['nelioab_registration_action'] ) ) {
				$action = $_POST['nelioab_registration_action'];

				try {
					if ( $action == 'register' ) {
						NelioABSettings::register_this_site();
						$nelioab_admin_controller->message = __( 'This site has been successfully registered to your account.', 'nelioab' );
					}
					else {
						NelioABSettings::deregister_this_site();
						$nelioab_admin_controller->message = __( 'This site is no longer registered to your account.', 'nelioab' );
					}
				}
				catch ( Exception $e ) {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}

			}
		}

	}//NelioABSettingsPageController

}

if ( isset( $_POST['nelioab_account_form'] ) ) {
	NelioABSettingsPageController::validate_account();
}

if ( isset( $_POST['nelioab_registration_form'] ) ) {
	NelioABSettingsPageController::manage_site_registration();
}

?>
