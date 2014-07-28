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

			// POST
			$this->print_beautiful_box(
				'post',
				__( 'New A/B Test for Posts', 'nelioab' ),
				array( &$this, 'print_new_exp_box',
					array(
						'post', $url . NelioABExperiment::POST_ALT_EXP,
						__( '<strong>Pure A/B Testing!</strong> Create one or more alternatives of a post and <strong>change whatever you want</strong>: the colors, the text, the layout... and do it using your default WordPress editor! Then define the goals and you\'re ready!', 'nelioab' )
					)
				)
			);

			// PAGE
			$this->print_beautiful_box(
				'page',
				__( 'New A/B Test for Pages', 'nelioab' ),
				array( &$this, 'print_new_exp_box',
					array(
						'page', $url . NelioABExperiment::PAGE_ALT_EXP,
						__( '<strong>Pure A/B Testing!</strong> Create one or more alternatives of a page and <strong>change whatever you want</strong>: the colors, the text, the layout... and do it using your default WordPress editor! Then define the goals and you\'re ready!', 'nelioab' )
					)
				)
			);

			// TITLE ONLY
			$this->print_beautiful_box(
				'title',
				__( 'New A/B Test for Page or Post Titles', 'nelioab' ),
				array( &$this, 'print_new_exp_box',
					array(
						'title', $url . NelioABExperiment::TITLE_ALT_EXP,
						__( 'With Title Experiments, you\'ll be able to <strong>test different titles and check which one is more appealing</strong> to your users. Every time the title is printed somewhere in your site, it is counted as a visit. When a visitor clicks the link and reads the post, then you have a conversion!', 'nelioab' )
					)
				)
			);

			// THEMES (enabled starting at version 3.4)
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$this->print_beautiful_box(
					'theme',
					__( 'New A/B Theme Test ', 'nelioab' ),
					array( &$this, 'print_new_exp_box',
					array(
						'theme', $url . NelioABExperiment::THEME_ALT_EXP,
						__( 'Would you like to <strong>change your WordPress completely</strong>? Do you want to <strong>test small variations of two child themes</strong>? Then this gives you what you need! Just keep in mind to configure each theme individually before using this kind of experiment.', 'nelioab' )
						)
					)
				);
			}


			// HEATMAPS
			$this->print_beautiful_box(
				'heatmap',
				__( 'New Heatmap Experiment for Page or Post', 'nelioab' ),
				array( &$this, 'print_new_exp_box',
					array(
						'heatmap', $url . NelioABExperiment::HEATMAP_EXP,
						__( 'If you don\'t know how to get started with A/B Testing, run a Heatmap Experiment and <strong>discover how your users behave when navigating through your website</strong>! This is one of the easiest ways to get ideas on what to do next.', 'nelioab' )
					)
				)
			);

			// CSS
			$this->print_beautiful_box(
				'css',
				__( 'New A/B CSS Test', 'nelioab' ),
				array( &$this, 'print_new_exp_box',
					array(
						'css', $url . NelioABExperiment::CSS_ALT_EXP,
						__( 'Do you want to <strong>change the appearence of your WordPress site, but tweaking only small elements here and there</strong>? Then CSS Tests is what you\'re looking for. Create one or more CSS fragments that will be applied to your website and discover which one offers the better results.', 'nelioab' )
					)
				)
			);

		}


		public function print_new_exp_box( $type, $url, $description ) { ?>
			<a href="<?php echo $url ?>">
				<div class="nelioab-image nelioab-image-<?php echo $type; ?>">&nbsp;</div>
				<div class="description">
					<?php echo $description; ?>
				</div>
			</a>
			<?php
		}


	}//NelioABSelectExpCreationPage

}

?>
