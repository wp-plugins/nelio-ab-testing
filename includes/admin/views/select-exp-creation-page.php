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


if ( !class_exists( NelioABSelectExpCreationPage ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
	class NelioABSelectExpCreationPage extends NelioABAdminPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		public function do_render() {
			$url = admin_url() . 'admin.php?page=nelioab-add-experiment&experiment-type=';
			?>
			<h2><a href="<?php
				echo $url . 'alt-exp-page';
				?>"><?php _e( 'New A/B or Multivariate Test for Pages', 'nelioab' ); ?></a></h2>
			<h2><a href="<?php
				echo $url . 'alt-exp-post';
				?>"><?php _e( 'New A/B or Multivariate Test for Posts', 'nelioab' ); ?></a></h2>
			<?php
		}

	}//NelioABAltExpCreationPage

}

?>
