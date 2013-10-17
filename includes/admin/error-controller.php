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

		public static function build( $exception ) {
			switch ( $exception->getCode() ) {
				case NelioABErrCodes::DEACTIVATED_USER:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/deactivated-user-page.php' );
					$view = new NelioABDeactivatedUserPage();
					break;
				default:
					require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
					$view = new NelioABErrorPage( $exception->getMessage() );
			}

			$view->render_content();
			die();
		}

	}//NelioABErrorController

}

?>
