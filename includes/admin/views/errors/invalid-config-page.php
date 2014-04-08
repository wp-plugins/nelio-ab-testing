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


if ( !class_exists( 'NelioABInvalidConfigPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	class NelioABInvalidConfigPage extends NelioABAdminAjaxPage {

		public function __construct( $title = false ) {
			if ( !$title )
				$title = __( 'Welcome!', 'nelioab' );
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		protected function do_render() {
			$style = 'font-size:130%%;color:#555;max-width:450px;line-height:150%%;';

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'Thank you very much for installing <b>Nelio A/B Testing</b> by <i>Nelio Software</i>. We are very excited you chose our solution for optimizing your site.', 'nelioab' )
				);

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'In order to use our service, please make sure you have already configured the plugin properly. In order to do so, just click the button below to access the plugin\'s settings page and fill in the required fields. These include your <i>e-mail</i> and your <i>Product Registration Number</i>. Finally, do not forget to register this site to your account!', 'nelioab' )
				);

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( 'If, on the other hand, you have not subscribed to any of our plans yet, please <a href="http://wp-abtesting.com/subscription-plans/" target="_blank">check them out and choose the one that best fits you</a>! Keep in mind <b>all our plans come with a 14-day free trial period</b>.', 'nelioab' )
				);

			echo sprintf( "<p style=\"$style\">%s</p>\n",
					__( '<b>Optimize your site based on real data</b>, not opinions!', 'nelioab' )
				);

			echo sprintf( "<br /><div style=\"text-align:center;$style\">%s</div>",
					$this->make_button(
						__( 'Configure now', 'nelioab' ),
						get_admin_url() . '/admin.php?page=nelioab-settings',
						true
					)
				);
		}

	}//NelioABInvalidConfigPage

}

?>
