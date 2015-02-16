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
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABMultisiteSettingsPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/multisite-settings-page.php' );

	class NelioABMultisiteSettingsPageController {

		public static function get_instance() {
			return new NelioABMultisiteSettingsPageController();
		}

		public static function build() {
			$title = __( 'Network Admin Settings', 'nelioab' );
			$view = new NelioABMultisiteSettingsPage( $title );
			$view->render();
		}

		public function save_settings() {

			$aux = $_POST['plugin_available_to'];
			$enabled_for_everybody = $_POST['plugin_available_to'] == NelioABSettings::PLUGIN_AVAILABLE_TO_ANY_ADMIN;
			NelioABSettings::set_site_option_regular_admins_can_manage_plugin( $enabled_for_everybody );

		}

	}//NelioABMultisiteSettingsPageController

}

if ( isset( $_POST['nelioab_multisite_settings_form'] ) ) {
	$controller = NelioABMultisiteSettingsPageController::get_instance();
	$controller->save_settings();
}

