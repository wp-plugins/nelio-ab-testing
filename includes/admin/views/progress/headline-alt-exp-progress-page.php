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

if ( !class_exists( 'NelioABHeadlineAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/post-alt-exp-progress-page.php' );

	class NelioABHeadlineAltExpProgressPage extends NelioABPostAltExpProgressPage {

		public function __construct( $title ) {
			parent::__construct( $title );
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Headline Experiment', 'nelioab' );
		}

		protected function print_js_function_for_post_data_overwriting() { ?>
			function nelioab_confirm_overwriting(id, title, excerpt, image) {
				jQuery("#alternative_alternative").attr("value",id);
				jQuery("#alternative_title").attr("value",title);
				jQuery("#alternative_image").attr("value",image);
				jQuery("#alternative_excerpt").attr("value",excerpt);
				nelioab_show_the_dialog_for_overwriting(id);
			}
			<?php
		}

		protected function who_wins() {
			$exp = $this->exp;
			$winner_id = $this->who_wins_real_id();
			if ( $winner_id == $exp->get_originals_id() )
				return 0;
			$i = 1;
			foreach ( $exp->get_alternatives() as $alt ) {
				$value = $alt->get_value();
				if ( $winner_id == $value['id'] )
					return $i;
				$i++;
			}
			return self::NO_WINNER;
		}

		public function set_experiment( $exp ) {
			NelioABAltExpProgressPage::set_experiment( $exp );
		}

		public function do_render() {
			parent::do_render(); ?>
			<div id="preview-dialog-modal" title="<?php _e( 'Headline', 'nelioab' ); ?>" style="display:none;">
				<div class="nelioab-row">
					<div class="nelioab-image">
						<img src="" />
					</div>
					<div class="nelioab-text">
						<p class="nelioab-title"></p>
						<p class="nelioab-excerpt"></p>
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
			function nelioabPreviewLink(title, excerpt, imageUrl) {
				jQuery('#preview-dialog-modal img').attr('src', imageUrl);
				jQuery('#preview-dialog-modal .nelioab-title').html(title);
				jQuery('#preview-dialog-modal .nelioab-excerpt').html(excerpt);
				$nelioabPreviewDialog.dialog('open');
			}
			</script>
			<?php
		}

		protected function get_action_links( $exp, $alt_id, $primary = false ) {
			$action_links = array();

			$alternative = false;
			$the_value = array();

			if ( $alt_id == $exp->get_originals_id() ) {
				$post      = get_post( $alt_id );
				$excerpt   = $post->post_excerpt;
				$name      = $post->post_title;
				$image_id  = get_post_thumbnail_id( $alt_id );
				$aux       = wp_get_attachment_image_src( $image_id );
				$image_src = ( count( $aux ) > 0 ) ? $aux[0] : '';
			}
			else {
				foreach( $exp->get_alternatives() as $alt ) {
					$value = $alt->get_value();
					if ( is_array( $value ) && $alt->get_id() == $alt_id ) {
						$alternative = $alt;
						$the_value = $value;
					}
				}

				$name = $alternative->get_name();
				$excerpt = $the_value['excerpt'];
				$image_id = $the_value['image_id'];
				$attach = wp_get_attachment_image_src( $image_id );
				$image_src = ( count( $attach ) > 0 ) ? $attach[0] : '';
			}

			$json_param_name = str_replace( '"', urlencode( '"' ), json_encode( $name ) );
			$json_param_excerpt = str_replace( '"', urlencode( '"' ), json_encode( $excerpt ) );
			$json_param_image_src = str_replace( '"', urlencode( '"' ), json_encode( $image_src ) );

			$aux = sprintf(
				' <a class="button" href="javascript:nelioabPreviewLink(%s, %s, %s);">%s</a>',
				$json_param_name, $json_param_excerpt, $json_param_image_src,
				__( 'Preview', 'nelioab' ) );
			array_push( $action_links, $aux );

			if ( $exp->get_status() == NelioABExperiment::STATUS_FINISHED ) {

				$img = '<span id="loading-' . $alt_id . '" class="dashicons dashicons-update fa-spin animated nelio-apply"></span>';

				if ( $primary ) {
					$aux = sprintf(
						' <a class="apply-link button button-primary" href="javascript:nelioab_confirm_overwriting(%s, %s, %s, \'%s\');">%s %s</a>',
						$alt_id, $json_param_name, $json_param_excerpt, $image_id,
						$img,
						__( 'Apply', 'nelioab' ) );
				} else {
					$aux = sprintf(
						' <a class="apply-link button" href="javascript:nelioab_confirm_overwriting(%s, %s, %s, \'%s\');">%s %s</a>',
						$alt_id, $json_param_name, $json_param_excerpt, $image_id,
						$img,
						__( 'Apply', 'nelioab' ) );
				}
				array_push( $action_links, $aux );
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
			$ori_id          = $exp->get_originals_id();
			$link            = get_permalink( $ori_id );
			$ori_label       = __( 'Original Version', 'nelioab' );
			$ori_name        = __( 'Original', 'nelioab' );

			$post      = get_post( $ori_id );
			$excerpt   = $post->post_excerpt;
			$image_id  = get_post_thumbnail_id( $ori_id );
			$aux       = wp_get_attachment_image_src( $image_id );
			$image_src = ( count( $aux ) > 0 ) ? $aux[0] : '';

			$conversions_label      = __( 'Conversions', 'nelioab' );
			$pageviews_label        = __( 'Pageviews', 'nelioab' );
			$conversion_views_label = __( 'Conversions / Views', 'nelioab' );
			$conversion_rate_label  = __( 'Conversion Rate', 'nelioab' );
			$view_label             = __( 'Preview', 'nelioab' );

			if ( $link ) {
				$name = $this->trunk( $this->ori );
			}
			else {
				$name = __( '(Not found)', 'nelioab' );
			}

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
			if ( $this->is_winner( $exp->get_originals_id() ) ) {
				$winner = true;
				$icon  = $this->get_winner_icon( $exp );
				$color = $colorscheme['winner'];
				$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
			}

			$action_links = $this->get_action_links( $exp, $exp->get_originals_id(), $winner );
			$buttons = implode( ' ', $action_links );

			$json_name = json_encode( $json_name );
			$json_excerpt = json_encode( $json_excerpt );
			$json_image_src = json_encode( $json_image_src );

			$result = <<<HTML
				<div class="nelio-alternative original-alternative postbox nelio-card">
					<div class="alt-info-header masterTooltip" $winner_color title="$original">
						$icon
						<span class="alt-title">$name</span>
					</div>
					<div class="alt-info-body">
						<div class="alt-screen" id="$id" style="color:$color;">
							<span class="more-details">$view_label</span>
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

					jQuery('#$id').click(function() {
						nelioabPreviewLink($json_name, $json_excerpt, $json_image_src);
					})
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
			$view_label             = __( 'Preview', 'nelioab' );

			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			$color = $colorscheme['primary'];

			$base_color = $color;

			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$link = get_permalink( $alt->get_value() );

				if ( $this->is_ori_page )
					$link = esc_url( add_query_arg( array(
						'preview' => 'true',
					), $link ) );

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

				$the_value = $alt->get_value();
				$name = $alt->get_name();
				$name = str_replace( "\\", "\\\\", $name );
				$name = str_replace( "'", "\\'", $name );
				$excerpt = $the_value['excerpt'];
				$excerpt = str_replace( "\\", "\\\\", $excerpt );
				$excerpt = str_replace( "'", "\\'", $excerpt );
				$image_id = $the_value['image_id'];
				$aux = wp_get_attachment_image_src( $image_id );
				$image_src = ( count( $aux ) > 0 ) ? $aux[0] : '';

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
				if ( $this->is_winner( $alt->get_value() ) ) {
					$icon  = $this->get_winner_icon( $exp );
					$color = $colorscheme['winner'];
					$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
					$winner = true;
				} else {
					$color = $base_color;
				}

				$action_links = $this->get_action_links( $exp, $id, $winner );
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
						nelioabPreviewLink('$name', '$excerpt', '$image_src' );
					});
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
				_e( 'You are about to overwrite the original title with an alternative. Are you sure you want overwrite it?', 'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url(
					'admin.php?page=nelioab-experiments&action=progress&id=' . $exp->get_id() ); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" id="alternative_title" name="alternative_title" value="" />
				<input type="hidden" id="alternative_image" name="alternative_image" value="" />
				<input type="hidden" id="alternative_excerpt" name="alternative_excerpt" value="" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_originals_id(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::HEADLINE_ALT_EXP; ?>" />
			</form>
			<?php
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = parent::get_labels_for_conversion_rate_js();
			$labels['subtitle'] = __( 'for the original and the alternative titles', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = parent::get_labels_for_improvement_factor_js();
			$labels['subtitle'] = __( 'with respect to the original title', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = parent::get_labels_for_visitors_js();
			$labels['subtitle']    = __( 'for the original and the alternative titles', 'nelioab' );
			return $labels;
		}

	}//NelioABHeadlineAltExpProgressPage

}

