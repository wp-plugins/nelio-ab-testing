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


if ( !class_exists( 'NelioABExamplePageController' ) ) {

	class NelioABExamplePageController {

		public static function build() {
			require_once( NELIOAB_ADMIN_DIR . '/views/example-page.php' );

			register_setting(
				'nelioab_example_settings',
				'nelioab_example_settings',
				array( 'NelioABExamplePageController', 'validate' )
			);

			$view = new NelioABExamplePage();
			$view->render();
		}

		public static function validate() {
		}

	}//NelioABExamplePageController
}

?>
