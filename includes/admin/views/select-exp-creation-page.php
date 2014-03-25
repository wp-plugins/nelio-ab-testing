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

			// TITLE ONLY
			$this->do_box(
				__( 'New A/B Test<br />for Page or Post Titles', 'nelioab' ),
				'title', $url . NelioABExperiment::TITLE_ALT_EXP );

			// PAGE
			$this->do_box(
				__( 'New A/B Test<br />for Pages', 'nelioab' ),
				'page', $url . NelioABExperiment::PAGE_ALT_EXP );

			// POST
			$this->do_box(
				__( 'New A/B Test<br />for Posts', 'nelioab' ),
				'post', $url . NelioABExperiment::POST_ALT_EXP );

			// THEMES (enabled starting at version 3.4)
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$this->do_box(
					__( 'New A/B Theme Test<br />', 'nelioab' ),
					'theme', $url . NelioABExperiment::THEME_ALT_EXP );
			}


			?><h2></h2><?php

			// CSS
			$this->do_box(
				__( 'New Heatmap Experiment<br />for Page or Post', 'nelioab' ),
				'heatmap', $url . NelioABExperiment::HEATMAP_EXP );

			// CSS
			$this->do_box(
				__( 'New A/B CSS Test<br />', 'nelioab' ),
				'css', $url . NelioABExperiment::CSS_ALT_EXP );

			// MENU
			$this->do_box(
				__( 'New A/B Menu Test<br />', 'nelioab' ),
				'menu'/*, $url . NelioABExperiment::MENU_ALT_EXP*/ );

			// WIDGET
			$this->do_box(
				__( 'New A/B Widget Test<br />', 'nelioab' ),
				'widget'/*, $url . NelioABExperiment::WIDGET_ALT_EXP*/ );

		}


		private function do_box( $label, $icon, $url = false ) {
			if ( $url ) {
				$open_tag  = '<a href="' . $url . '">';
				$close_tag = '</a>';
			}
			else {
				$open_tag  = '<span class="nelioab-option-disabled">';
				$close_tag = '</span>';
			}
			?>
			<?php echo $open_tag; ?>
				<div class="nelioab-option">
					<div class="nelioab-option-image-holder">
						&nbsp;
						<div class="nelioab-option-image nelioab-image nelioab-image-<?php echo $icon; ?>">&nbsp;</div>
						&nbsp;
					</div>
					<p style="line-height:1.2em;margin-top:0em;"><?php echo $label; ?></p>
				</div>
			<?php echo $close_tag; ?>

			<?php
		}

	}//NelioABSelectExpCreationPage

}

?>
