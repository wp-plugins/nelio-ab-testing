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


if ( !class_exists( 'NelioABErrorPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	class NelioABErrorPage extends NelioABAdminAjaxPage {

		private $error_message;

		public function __construct( $error_message ) {
			parent::__construct( __( 'Error', 'nelioab' ) );
			$this->error_message = $error_message;
		}

		protected function do_render() {
			echo '<center>';
			echo sprintf( '<img src="%s" alt="%s" />',
				NELIOAB_ASSETS_URL . '/admin/images/error-icon.png?' . NELIOAB_PLUGIN_VERSION,
				__( 'Funny image to graphically notify of an error.', 'nelioab' )
			);
			echo "<h2>$this->error_message</h2>";
			echo '</center>';
		}

	}//NelioABErrorPage

}

?>
