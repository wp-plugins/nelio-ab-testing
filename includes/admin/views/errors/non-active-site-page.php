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


if ( !class_exists( 'NelioABNonActiveSitePage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	class NelioABNonActiveSitePage extends NelioABAdminAjaxPage {

		public function __construct( $title = false ) {
			if ( !$title )
				$title = __( 'This site is not registered', 'nelioab' );
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		protected function do_render() {
			$style = 'font-size:130%%;color:grey;max-width:450px;line-height:150%%;';

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'Appearently, your account information is properly configured, but ' .
					'this site has not been registered yet. In order to create, manage, and ' .
					'execute experiments, you have to register the site to your account.',
					'nelioab' )
				);

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'Please, go to the settings page and make sure you register ' .
					'this site to your account.', 'nelioab' )
				);

			echo sprintf( "<br /><div style=\"text-align:center;$style\">%s</div>",
					$this->make_button(
						__( 'Go to Settings', 'nelioab' ),
						get_admin_url() . '/admin.php?page=nelioab-settings',
						true
					)
				);
		}

	}//NelioABNonActiveSitePage

}

?>
