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

if ( !class_exists( 'NelioABMenuAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABMenuAltExpProgressPage extends NelioABAltExpProgressPage {

		protected $ori;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Menu Experiment', 'nelioab' );
		}

		protected function get_original_name() {
			// Original title
			$exp = $this->exp;
			$menus = wp_get_nav_menus();
			$menu = false;
			foreach ( $menus as $aux )
				if ( $aux->term_id == $exp->get_original()->get_value() )
					$menu = $aux;
			$this->ori = sprintf( __( 'Unknown (menu id is %s)', 'nelioab' ), $exp->get_original()->get_value() );
			if ( $menu )
				$this->ori = $menu->name;
			return $this->ori;
		}

		protected function get_original_value() {
			return $this->exp->get_original()->get_value();
		}

		protected function print_js_function_for_post_data_overwriting() { ?>
			function nelioab_confirm_overwriting(id) {
				jQuery("#apply_alternative #alternative").attr("value",id);
				nelioab_show_the_dialog_for_overwriting(id);
			}
			<?php
		}


		protected function print_alternatives_block() {
			echo '<table id="alternatives-in-progress">';
			$this->print_the_original_alternative();
			$this->print_the_real_alternatives();
			echo '</table>';
		}


		private function make_link_for_edit( $id ) {
			$exp = $this->exp;
			$exp_id = $exp->get_id();
			if ( $exp->get_original()->get_id() == $id ) {
				$menu_id = $exp->get_original()->get_value();
				$link = 'nav-menus.php?menu=' . $menu_id;
			}
			else {
				foreach ( $exp->get_alternatives() as $alt ) {
					if ( $alt->get_id() == $id ) {
						$menu_alt_id = $alt->get_id();
						$menu_id = $alt->get_value();
						break;
					}
				}
				$link = 'nav-menus.php?' .
					'nelioab_exp=' . $exp_id . '&nelioab_alt=' . $menu_alt_id .
					'&nelioab_check=' . md5( $exp_id . $menu_alt_id . $menu_id ) .
					'&menu=' . $menu_id;
			}

			return sprintf( ' <a class="apply-link button" href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
				admin_url( $link ), __( 'Edit' ) );
		}


		protected function get_action_links( $exp, $alt_id, $primary = false ) {
			$action_links = array();
			switch ( $exp->get_status() ) {
				case NelioABExperiment::STATUS_RUNNING:
					array_push( $action_links, $this->make_link_for_edit( $alt_id, $primary ) );
					break;
				case NelioABExperiment::STATUS_FINISHED:
					if ( $alt_id == $exp->get_originals_id() )
						break;
					$menu = false;
					foreach ( $exp->get_alternatives() as $alt ) {
						if ( $alt->get_id() == $alt_id )
							$menu = $alt->get_value();
					}
					if ( $menu ) {
						$img = '<span id="loading-' . $menu . '" class="dashicons dashicons-update fa-spin animated nelio-apply"></span>';

						if ( $primary )
							$aux = sprintf(
								' <a class="apply-link button button-primary" href="javascript:nelioab_confirm_overwriting(%1$s);">%2$s %3$s</a>',
								$menu, $img, __( 'Apply', 'nelioab' ) );
						else
							$aux = sprintf(
								' <a class="apply-link button" href="javascript:nelioab_confirm_overwriting(%1$s);">%2$s %3$s</a>',
								$menu, $img, __( 'Apply', 'nelioab' ) );
						array_push( $action_links, $aux );
					}
					break;
			}
			return $action_links;
		}


		protected function print_the_original_alternative() {
			// THE ORIGINAL
			// -----------------------------------------

			if( $this->results == null ) {
				$pageviews       = 0;
				$conversions     = 0;
				$conversion_rate = 0.0;
			} else {
				$alt_results     = $this->results->get_alternative_results();
				$ori_result      = $alt_results[0];
				$pageviews       = $ori_result->get_num_of_visitors();
				$conversions     = $ori_result->get_num_of_conversions();
				$conversion_rate = $ori_result->get_conversion_rate();
			}

			$exp             = $this->exp;
			$ori             = $exp->get_original();
			$ori_label       = __( 'Original Version', 'nelioab' );
			$ori_name        = __( 'Original', 'nelioab' );

			$conversions_label      = __( 'Conversions', 'nelioab' );
			$pageviews_label        = __( 'Pageviews', 'nelioab' );
			$conversion_views_label = __( 'Conversions / Views', 'nelioab' );
			$conversion_rate_label  = __( 'Conversion Rate', 'nelioab' );

			$name = $this->trunk( $this->get_original_name() );

			$original = __( 'This is the original version', 'nelioab' );
			$icon = $this->get_experiment_icon( $exp );

			$id = $ori->get_id();
			$graphic_id = 'graphic-' . $id;

			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			$color = $colorscheme['primary'];

			$ori_conversion_rate  = number_format_i18n( floatval( $conversion_rate ), 2 ) . ' %';
			$ori_conversion_views = $conversions . ' / ' . $pageviews;

			$winner_color = '';
			$winner = false;

			if ( $this->is_winner( $this->exp->get_originals_id() ) ) {
				$winner = true;
				$icon  = $this->get_winner_icon( $exp );
				$color = $colorscheme['winner'];
				$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
			}

			$action_links = $this->get_action_links( $exp, $exp->get_originals_id(), $winner );
			$buttons = implode( ' ', $action_links );

			$result = <<<HTML
				<div class="nelio-alternative original-alternative postbox nelio-card">
					<div class="alt-info-header masterTooltip" $winner_color title="$original">
						$icon
						<span class="alt-title">$name</span>
					</div>
					<div class="alt-info-body">
						<div class="alt-screen no-hover" id="$id" style="color:$color;">
							<div class="alt-name">
									$ori_name
								</div>
						</div>
						<div class="alt-stats">
							<div class="alt-stats-graphic" id="$graphic_id"></div>
							<div class="alt-status">
								<div class="alt-cv">
									<span class="alt-cv-title">$conversion_views_label</span>
									<span class="alt-cv">$ori_conversion_views</span>
								</div>
								<div class="alt-cr">
									<span class="alt-cr-title">$conversion_rate_label</span>
									<span class="alt-cr">$ori_conversion_rate</span>
								</div>
								<div class="alt-stats">
									<span>$ori_label</span>
								</div>
							</div>
						</div>
					</div>
					<div class="alt-info-footer">
						<div class="alt-info-footer-content">
							$buttons
						</div>
					</div>
				</div>
				<script>
				jQuery(document).ready(function() {
					drawAlternativeGraphic('$graphic_id',
						$conversions,
						'$conversions_label',
						'$color',
						$pageviews,
						'$pageviews_label');
				});
				</script>
HTML;

			echo $result;
		}

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;

			if( $this->results == null ) {
				$alt_results     = null;
				$ori_conversions = 0;
			} else {
				$alt_results     = $this->results->get_alternative_results();
				$ori_conversions = $alt_results[0]->get_num_of_conversions();
				// in this function, the original alternative is NOT used
				$alt_results = array_slice( $alt_results, 1 );
			}

			$conversions_label      = __( 'Conversions', 'nelioab' );
			$pageviews_label        = __( 'Pageviews', 'nelioab' );
			$conversion_views_label = __( 'Conversions / Views', 'nelioab' );
			$conversion_rate_label  = __( 'Conversion Rate', 'nelioab' );
			$alternative_label      = __( 'Alternative', 'nelioab' );

			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			$color = $colorscheme['primary'];

			$base_color = $color;

			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;

				$name = $this->trunk( $alt->get_name() );

				$icon = $this->get_experiment_icon( $exp );
				$id = $alt->get_id();
				$graphic_id = 'graphic-' . $id;

				if ( $alt_results != null ) {
					$alt_result         = $alt_results[ $i - 1 ];
					$pageviews          = $alt_result->get_num_of_visitors();
					$conversions        = $alt_result->get_num_of_conversions();
					$conversion_rate    = $alt_result->get_conversion_rate();
					$improvement_factor = $alt_result->get_improvement_factor();
				} else {
					$pageviews          = 0;
					$conversions        = 0;
					$conversion_rate    = 0.0;
					$improvement_factor = 0.0;
				}

				$alt_conversion_views = $conversions . ' / ' . $pageviews;

				$aux = ( $ori_conversions * $this->goal->get_benefit() * $improvement_factor )/100;

				$print_improvement = true;
				// format improvement factor
				if ( $improvement_factor < 0 ) {
					$arrow                       = 'fa-arrow-down';
					$stats_color                 = 'red';
					$improvement_factor = $improvement_factor * - 1;
				} else if ( $improvement_factor > 0 ) {
					$arrow       = 'fa-arrow-up';
					$stats_color = 'green';
				} else { // $improvement_factor = 0.0
					$arrow       = 'fa-none';
					$stats_color = 'black';
					$print_improvement = false;
				}

				if ( $aux > 0 ) {
					$gain = sprintf( __( '%1$s%2$s', 'nelioab', 'money' ),
						NelioABSettings::get_conv_unit(),
						number_format_i18n( $aux, 2 )
					);
				} else {
					$gain = sprintf( __( '%1$s%2$s', 'nelioab', 'money' ),
						NelioABSettings::get_conv_unit(),
						number_format_i18n( $aux * -1, 2 )
					);
				}

				$alt_conversion_rate = number_format_i18n( floatval( $conversion_rate ), 2 ) . ' %';
				$alt_improvement_factor = number_format_i18n( floatval( $improvement_factor ), 2 ) . ' %';
				$alternative_number = $i;

				$winner = false;
				$winner_color = '';
				if ( $this->is_winner( $alt->get_id() ) ) {
					$icon  = $this->get_winner_icon( $exp );
					$color = $colorscheme['winner'];
					$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
					$winner = true;
				} else {
					$color = $base_color;
				}

				$action_links = $this->get_action_links( $exp, $alt->get_id(), $winner );
				$buttons = implode( ' ', $action_links );

				if ( !$print_improvement ) {
					$gain = '';
					$alt_improvement_factor = '';
				}

				$result = <<<HTML
				<div class="nelio-alternative alternative-$i postbox nelio-card">
					<div class="alt-info-header" $winner_color>
						$icon
						<span class="alt-title">$name</span>
					</div>
					<div class="alt-info-body">
						<div class="alt-screen no-hover" id="$id" style="color:$color;">
								<div class="alt-name">
									$alternative_label
								</div>
								<div class="alt-number">
									$alternative_number
								</div>
						</div>
						<div class="alt-stats">
							<div class="alt-stats-graphic" id="$graphic_id"></div>
							<div class="alt-status">
								<div class="alt-cv">
									<span class="alt-cv-title">$conversion_views_label</span>
									<span class="alt-cv">$alt_conversion_views</span>
								</div>
								<div class="alt-cr">
									<span class="alt-cr-title">$conversion_rate_label</span>
									<span class="alt-cr">$alt_conversion_rate</span>
								</div>
								<div class="alt-stats" style="color:$stats_color;">
									<span class="alt-if"><i class="fa $arrow" style="vertical-align: top;"></i>$alt_improvement_factor</span>
									<span class="alt-ii"><i class="fa $arrow" style="vertical-align: top;"></i>$gain</span>
								</div>
							</div>
						</div>
					</div>
					<div class="alt-info-footer">
						<div class="alt-info-footer-content">
							$buttons
						</div>
					</div>
				</div>
				<script>
				jQuery(document).ready(function() {
					drawAlternativeGraphic('$graphic_id',
						$conversions,
						'$conversions_label',
						'$color',
						$pageviews,
						'$pageviews_label');
				});
				</script>
HTML;

				echo $result;
			}
		}

		protected function print_dialog_content() {
			$exp = $this->exp;
			?>
			<p><?php
				_e( 'You are about to overwrite the original menu items with the alternative ones. Please, remember <strong>this operation cannot be undone</strong>. Are you sure you want to overwrite the menu?', 'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url(
					'admin.php?page=nelioab-experiments&action=progress&' .
					'id=' . $exp->get_id() . '&' .
					'type=' . $exp->get_type() ); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $exp->get_type(); ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_original()->get_value(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
			</form>
			<?php
		}

		protected function who_wins() {
			$exp = $this->exp;
			$winner_id = $this->who_wins_real_id();
			if ( $winner_id == $exp->get_originals_id() )
				return 0;
			$i = 1;
			foreach ( $exp->get_alternatives() as $alt ) {
				if ( $winner_id == $alt->get_id() )
					return $i;
				$i++;
			}
			return self::NO_WINNER;
		}

		protected function get_winning_gtest() {
			$res = $this->results;
			if ( $res == null )
				return false;

			$gtests = $res->get_gtests();

			if ( count( $gtests ) == 0 )
				return false;

			/** @var NelioABGTest $bestg */
			$bestg = $gtests[count( $gtests ) - 1];

			if ( $bestg->is_original_the_best() ) {
				if ( $bestg->get_type() == NelioABGTest::WINNER )
					return $bestg;
			}
			else {
				$aux = null;
				foreach ( $gtests as $gtest )
					if ( $gtest->get_min() == $this->exp->get_originals_id() )
						$aux = $gtest;
				if ( $aux )
					if ( $aux->get_type() == NelioABGTest::WINNER ||
					     $aux->get_type() == NelioABGTest::DROP_VERSION )
						return $aux;
			}

			return false;
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			$labels['subtitle'] = __( 'for the original and the alternative menus', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to the original menu', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = __( 'for the original and the alternative menus', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABMenuAltExpProgressPage

}

