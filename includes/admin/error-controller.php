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


if ( !class_exists( 'NelioABErrorController' ) ) {

	include_once( NELIOAB_UTILS_DIR . '/backend.php' );
	class NelioABErrorController {

		public static function get_view( $exception ) {
			switch ( $exception->getCode() ) {
				case NelioABErrCodes::SITE_IS_NOT_ACTIVE:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/non-active-site-page.php' );
					$view = new NelioABNonActiveSitePage();
					break;
				case NelioABErrCodes::DEACTIVATED_USER:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/deactivated-user-page.php' );
					$view = new NelioABDeactivatedUserPage();
					break;
				case NelioABErrCodes::INVALID_MAIL:
				case NelioABErrCodes::INVALID_PRODUCT_REG_NUM:
				case NelioABErrCodes::NON_ACCEPTED_TAC:
				case NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/invalid-config-page.php' );
					$view = new NelioABInvalidConfigPage();
					break;
				default:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
					$view = new NelioABErrorPage( $exception->getMessage() );
			}

			return $view;
		}

		public static function build( $exception ) {
			$view = NelioABErrorController::get_view( $exception );
			$view->render_content();
			die();
		}

		public static function build_error_page_on_invalid_settings() {
			// Check settings
			try {
				$aux = NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				switch ( $e->getCode() ) {
					case NelioABErrCodes::DEACTIVATED_USER:
					case NelioABErrCodes::INVALID_MAIL:
					case NelioABErrCodes::INVALID_PRODUCT_REG_NUM:
					case NelioABErrCodes::NON_ACCEPTED_TAC:
					case NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED:
						require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
						$view = NelioABErrorController::get_view( $e );
						$view->render();
						return true;
					default:
						return false;
				}
			}
			return false;
		}

	}//NelioABErrorController

}

?>
