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


if ( !class_exists( 'NelioABSelectExpCreationPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
	class NelioABSelectExpCreationPage extends NelioABAdminPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		public function do_render() {
			$url = admin_url() . 'admin.php?page=nelioab-add-experiment&experiment-type=';

			// PAGE
			$this->do_box( $url . NelioABExperiment::PAGE_ALT_EXP,
				__( 'New A/B or Multivariate<br />Test for Pages', 'nelioab' ),
				'page' );

			// POST
			$this->do_box( $url . NelioABExperiment::POST_ALT_EXP,
				__( 'New A/B or Multivariate<br />Test for Posts', 'nelioab' ),
				'post' );

			// // CSS
			// $this->do_box( $url . NelioABExperiment::CSS_ALT_EXP,
			// 	__( 'New A/B or Multivariate<br />CSS Test', 'nelioab' ),
			// 	'css' );

			// THEMES (enabled starting at version 3.4)
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$this->do_box( $url . NelioABExperiment::THEME_ALT_EXP,
					__( 'New A/B or Multivariate<br />Theme Test', 'nelioab' ),
					'theme' );
			}

		}


		private function do_box( $url, $label, $icon ) {?>
			<a href="<?php echo $url; ?>">
				<div class="nelioab-option">
					<div class="nelioab-option-image-holder">
						&nbsp;
						<div class="nelioab-option-image nelioab-image nelioab-image-<?php echo $icon; ?>">&nbsp;</div>
						&nbsp;
					</div>
					<p style="line-height:1.2em;"><?php echo $label; ?></p>
				</div>
			</a>

			<?php
		}

	}//NelioABSelectExpCreationPage

}

?>
