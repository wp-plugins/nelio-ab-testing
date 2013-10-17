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


if ( !class_exists( 'NelioABDashboardPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/dashboard-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	class NelioABDashboardPageController {

		public static function build() {
			$title = __( 'Nelio A/B Testing &mdash; Dashboard', 'nelioab' );

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

			$view = new NelioABDashboardPage( $title );
			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			// Render content
			$title = __( 'Nelio A/B Testing &mdash; Dashboard', 'nelioab' );
			$view = new NelioABDashboardPage( $title );
			$view->render_content();

			// Update cache
			NelioABExperimentsManager::update_running_experiments_cache();

			die();
		}

	}//NelioABDashboardPageController

}

?>
