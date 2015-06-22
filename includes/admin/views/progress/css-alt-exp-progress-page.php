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


if ( !class_exists( 'NelioABCssAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABCssAltExpProgressPage extends NelioABAltExpProgressPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
		}

		public function do_render() {
			parent::do_render(); ?>
			<div id="preview-dialog-modal" title="<?php _e( 'CSS', 'nelioab' ); ?>" style="display:none;">
				<div class="nelioab-row">
					<div class="nelioab-text">
						<p class="nelioab-title"></p>
						<p class="nelioab-css" style="font-family: Consolas, Monaco, Courier, monospace"></p>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				var $nelioabPreviewDialog;
				jQuery(document).ready(function() {
					$nelioabPreviewDialog = jQuery('#preview-dialog-modal').dialog({
						dialogClass   : 'wp-dialog',
						modal         : true,
						autoOpen      : false,
						closeOnEscape : true,
						width         : 600,
						buttons : [
							{
								text: "<?php echo esc_html( __( 'Close', 'nelioab' ) ); ?>",
								click: function() {
									jQuery(this).dialog('close');
								}
							},
						]
					});
				});
				function openCss( css, title ) {
					var id = '#css-' + css;

					jQuery('#preview-dialog-modal .nelioab-title').html(title);
					jQuery('#preview-dialog-modal .nelioab-css').html(jQuery(id).text());
					$nelioabPreviewDialog.dialog('open');
				}
			</script>
		<?php
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the CSS Experiment', 'nelioab' );
		}

		protected function get_original_name() {
			return __( 'By default, no additional CSS code is used', 'nelioab' );
		}

		protected function get_original_value() {
			return $this->exp->get_originals_id();
		}

		protected function print_js_function_for_post_data_overwriting() {
			// Nothing to do here, because these experiments do not permit overwriting
		}

		protected function print_winner_info() {
			// Winner (if any) details
			$the_winner            = $this->who_wins();
			$the_winner_confidence = $this->get_winning_confidence();

			$exp = $this->exp;
			if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'Right now, no CSS alternative is helping to improve your site.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Right now, the alternative %s is better than none to improve your site.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
			else {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'No CSS alternative helped to improve your site.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'CSS alternative %s was better than none to improve your site.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
		}


		protected function print_alternatives_block() {
			echo '<table id="alternatives-in-progress">';
			$this->print_the_original_alternative();
			$this->print_the_real_alternatives();
			echo '</table>';
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

			if ( $this->is_winner( $this->exp->get_originals_id() ) ) {
				$icon  = $this->get_winner_icon( $exp );
				$color = $colorscheme['winner'];
				$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
			}

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
			$view_label             = __( 'View', 'nelioab' );

			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			$color = $colorscheme['primary'];

			$base_color = $color;

			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;

				$name = $this->trunk( $alt->get_name() );
				$css_value = $alt->get_value();

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
					$arrow = 'fa-arrow-down';
					$stats_color = 'red';
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

				$winner_color = '';
				if ( $this->is_winner( $alt->get_id() ) ) {
					$icon  = $this->get_winner_icon( $exp );
					$color = $colorscheme['winner'];
					$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
				} else {
					$color = $base_color;
				}

				$action_links = array();

				$aux = sprintf(
					' <a class="button" href="javascript:openCss(\'%s\', \'%s\');">%s</a>',
					$id, $name,
					__( 'View', 'nelioab' ) );
				array_push( $action_links, $aux );

				if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
					$aux = sprintf( ' <a class="apply-link button" href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
						admin_url( 'admin.php?page=nelioab-css-edit&exp_id=' . $this->exp->get_id() . '&css_id=' . $alt->get_id() ),
						__( 'Edit' ) );
					array_push( $action_links, $aux );
				}
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
						<span id="css-$id" style="display:none;">$css_value</span>
						<div class="alt-screen" id="$id" style="color:$color;">
							<span class="more-details">$view_label</span>
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

					jQuery('#$id').click(function() {
						openCss('$id','$name');
					});
				});
				</script>
HTML;
				echo $result;
			}
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
			$labels['subtitle'] = __( 'for default CSS and the alternatives CSS fragments', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to no additional CSS fragment', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = __( 'for default CSS and the alternatives CSS fragments', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABCssAltExpProgressPage

}


?>
