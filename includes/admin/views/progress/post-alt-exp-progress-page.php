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

if ( !class_exists( 'NelioABPostAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABPostAltExpProgressPage extends NelioABAltExpProgressPage {

		protected $ori;
		protected $post_type;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
			$this->post_type = array(
				'name'     => 'page',
				'singular' => 'Page',
				'plural'   => 'Pages'
			);
			$this->graphic_delay = 500;
		}

		public function set_experiment( $exp ) {
			parent::set_experiment( $exp );

			switch ( $exp->get_post_type() ) {
				case 'page':
					$this->post_type = array(
						'name'     => 'page',
						'singular' => __( 'Page', 'nelioab' ),
						'plural'   => __( 'Pages', 'nelioab' )
					);
					break;
				case 'post';
					$this->post_type = array(
						'name'     => 'post',
						'singular' => __( 'Post', 'nelioab' ),
						'plural'   => __( 'Posts', 'nelioab' )
					);
					break;
				default:
					require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
					$ptn = $exp->get_post_type();
					$pt = NelioABWpHelper::get_custom_post_types( $ptn );
					$this->post_type = array(
						'name'     => $pt->name,
						'singular' => __( $pt->labels->singular_name, 'nelioab' ),
						'plural'   => __( $pt->labels->name, 'nelioab' )
					);

			}
		}

		protected function get_original_name() {
			// Original title
			$exp = $this->exp;
			$aux = get_post( $exp->get_originals_id() );
			$this->ori = sprintf( __( 'Unknown (post_id is %s)', 'nelioab' ), $exp->get_originals_id() );
			$this->is_ori_page = true;
			if ( $aux ) {
				$this->ori = trim( $aux->post_title );
				if ( $aux->post_type == 'post' )
					$this->is_ori_page = false;
			}
			return $this->ori;
		}

		protected function get_original_value() {
			return $this->exp->get_originals_id();
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

		private function make_link_for_heatmap( $exp, $id, $primary = false ) {
			include_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			$url = sprintf(
				str_replace(
					'https://', 'http://',
					admin_url( 'admin.php?nelioab-page=heatmaps&id=%1$s&exp_type=%2$s&post=%3$s' ) ),
				$exp->get_id(), $exp->get_type(), $id );

			if ( $primary ) {
				return sprintf( ' <a class="button button-primary" href="%1$s">%2$s</a>', $url,
					__( 'View Heatmap', 'nelioab' ) );
			} else
				return sprintf( ' <a class="button" href="%1$s">%2$s</a>', $url,
					__( 'View Heatmap', 'nelioab' ) );
		}

		private function make_link_for_edit( $id, $primary = false ) {
			if ( $primary ) {
				return sprintf( ' <a class="button button-primary" href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
					admin_url( 'post.php?post=' . $id . '&action=edit' ),
					__( 'Edit' ) );
			} else
				return sprintf( ' <a class="button" href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
					admin_url( 'post.php?post=' . $id . '&action=edit' ),
					__( 'Edit' ) );
		}

		protected function get_action_links( $exp, $alt_id, $primary = false ) {
			$action_links = array();

			if ( $exp->are_heatmaps_tracked() )
				array_push( $action_links, $this->make_link_for_heatmap( $exp, $alt_id, $primary ) );
			switch ( $exp->get_status() ) {
				case NelioABExperiment::STATUS_RUNNING:
					array_push( $action_links, $this->make_link_for_edit( $alt_id, $primary ) );
					break;
				case NelioABExperiment::STATUS_FINISHED:
					if ( $alt_id == $exp->get_originals_id() )
						break;

					$img = '<span id="loading-' . $alt_id . '" class="dashicons dashicons-update fa-spin animated nelio-apply"></span>';

					if ( $primary ) {
						$aux = sprintf(
							' <a class="apply-link button button-primary" href="javascript:nelioab_confirm_overwriting(%1$s);">%2$s %3$s</a>',
							$alt_id, $img, __( 'Apply', 'nelioab' ) );
					} else
						$aux = sprintf(
							' <a class="apply-link button" href="javascript:nelioab_confirm_overwriting(%1$s);">%2$s %3$s</a>',
							$alt_id, $img, __( 'Apply', 'nelioab' ) );
					array_push( $action_links, $aux );
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
			$link            = get_permalink( $exp->get_originals_id() );
			$ori_label       = __( 'Original Version', 'nelioab' );
			$ori_name        = __( 'Original', 'nelioab' );

			$conversions_label      = __( 'Conversions', 'nelioab' );
			$pageviews_label        = __( 'Pageviews', 'nelioab' );
			$conversion_views_label = __( 'Conversions / Views', 'nelioab' );
			$conversion_rate_label  = __( 'Conversion Rate', 'nelioab' );
			$view_label             = __( 'View', 'nelioab' );

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
			$aux = sprintf(
				' <a class="button" href="javascript:window.open(\'%s\');">%s</a>',
				$link, __( 'View Content', 'nelioab' ) );
			array_unshift( $action_links, $aux );

			$buttons = implode( ' ', $action_links );

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
						if ('$link')
							window.open('$link');
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
			$view_label             = __( 'View', 'nelioab' );

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

				if ( $link ) {
					$name = $this->trunk( $alt->get_name() );
				}
				else {
					$name = __( '(Not found)', 'nelioab' );
				}

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
				if ( $this->is_winner( $alt->get_value() ) ) {
					$icon  = $this->get_winner_icon( $exp );
					$color = $colorscheme['winner'];
					$winner_color = 'style="background-color:' . $colorscheme['primary'] . ';color:' . $colorscheme['foreground'] . ';"';
					$winner = true;
				} else {
					$color = $base_color;
				}

				$action_links = $this->get_action_links( $exp, $alt->get_value(), $winner );
				$aux = sprintf(
					' <a class="button" href="javascript:window.open(\'%s\');">%s</a>',
					$link, __( 'View Content', 'nelioab' ) );
				array_unshift( $action_links, $aux );

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
						if ('$link')
							window.open('$link');
					})
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
				printf( __( 'You are about to overwrite the original %s with the content of an alternative. Please, remember <strong>this operation cannot be undone</strong>. Are you sure you want to overwrite it?', 'nelioab' ), $this->post_type['name'] );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url(
					'admin.php?page=nelioab-experiments&action=progress&' .
					'id=' . $exp->get_id() . '&' .
					'type=' . $exp->get_type() ); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $exp->get_type(); ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_originals_id(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
				<p><input type="checkbox" id="copy_content" name="copy_content" checked="checked" disabled="disabled" /><?php
					_e( 'Override title and content', 'nelioab' ); ?></p>
				<p><input type="checkbox" id="copy_meta" name="copy_meta" <?php
					if ( NelioABSettings::is_copying_metadata_enabled() ) echo 'checked="checked" ';
				?>/><?php _e( 'Override all metadata', 'nelioab' ); ?></p>
				<?php
				if ( ! 'page' == $this->post_type['name'] ) { ?>
					<p><input type="checkbox" id="copy_categories" name="copy_categories" <?php
						if ( NelioABSettings::is_copying_categories_enabled() ) echo 'checked="checked" ';
					?>/><?php _e( 'Override categories', 'nelioab' ); ?></p>
					<p><input type="checkbox" id="copy_tags" name="copy_tags" <?php
						if ( NelioABSettings::is_copying_tags_enabled() ) echo 'checked="checked" ';
					?>/><?php _e( 'Override tags', 'nelioab' ); ?></p><?php
				} ?>
			</form>
			<?php
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			$labels['subtitle'] = sprintf( __( 'for the original and the alternative %s', 'nelioab' ), strtolower( $this->post_type['plural'] ) );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = sprintf( __( 'with respect to the original %s', 'nelioab' ), $this->post_type['name'] );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = sprintf( __( 'for the original and the alternative %s', 'nelioab' ) , strtolower( $this->post_type['plural'] ) );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABPostAltExpProgressPage

}

?>
