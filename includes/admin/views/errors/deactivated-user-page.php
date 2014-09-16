<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABDeactivatedUserPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	class NelioABDeactivatedUserPage extends NelioABAdminAjaxPage {

		public function __construct( $title = false ) {
			if ( !$title )
				$title = __( 'User Account Deactivated', 'nelioab' );
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		protected function do_render() {
			$style = 'font-size:130%%;color:#555;max-width:450px;line-height:150%%;';

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'The user account has been deactivated. Normally, this occurs once you unsubscribed from our Nelio A/B Testing service. If you want to use the service, you may want to consider subscribing to one of our packages again.', 'nelioab' )
				);

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'Please, go to the settings page and check your subscription details.', 'nelioab' )
				);

			echo sprintf( "<br /><div style=\"text-align:center;$style\">%s</div>",
					$this->make_button(
						__( 'Go to My Account', 'nelioab' ),
						admin_url( 'admin.php?page=nelioab-account' ),
						true
					)
				);
		}

	}//NelioABDeactivatedUserPage

}

