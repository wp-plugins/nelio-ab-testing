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


		protected function get_action_links( $exp, $alt_id ) {
			$result = array();

			$alternative = false;
			$the_value = array();

			if ( $alt_id == $exp->get_originals_id() ) {
				$post = get_post( $alt_id );
				$name = $post->post_title;
				$excerpt = $post->post_excerpt;
				$image_id = get_post_thumbnail_id( $alt_id );
				$aux = wp_get_attachment_image_src( $image_id );
				$image_src = ( count( $aux ) > 0 ) ? $aux[0] : '';
			}
			else {
				foreach( $exp->get_alternatives() as $alt ) {
					$value = $alt->get_value();
					if ( is_array( $value ) && $value['id'] == $alt_id ) {
						$alternative = $alt;
						$the_value = $value;
					}
				}
				$name = $alternative->get_name();
				$name = str_replace( "\\", "\\\\", $name );
				$name = str_replace( "'", "\\'", $name );
				$excerpt = $the_value['excerpt'];
				$excerpt = str_replace( "\\", "\\\\", $excerpt );
				$excerpt = str_replace( "'", "\\'", $excerpt );
				$image_id = $the_value['image_id'];
				$aux = wp_get_attachment_image_src( $image_id );
				$image_src = ( count( $aux ) > 0 ) ? $aux[0] : '';
			}

			$result['preview'] = sprintf(
				' <a class="preview-link" href="javascript:nelioabPreviewLink(\'%s\', \'%s\', \'%s\' );">%s</a>',
				$name, $excerpt, $image_src,
				__( 'Preview', 'nelioab' ) );

			if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
				$result['apply'] = sprintf(
					' <a class="apply-link" href="javascript:nelioab_confirm_overwriting(%s, \'%s\', \'%s\', \'%s\' );">%s</a>',
					$alt_id,
					$name, $excerpt, $image_id,
					__( 'Apply', 'nelioab' ) );
			}

			// The original alternative can never have an Apply button
			if ( $alt_id == $exp->get_originals_id() )
				unset( $result['apply'] );

			return $result;
		}


		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;

			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;

				$value = $alt->get_value();
				$action_links = $this->get_action_links( $exp, $value['id'] );

				if ( $this->is_winner( $value['id'] ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );
				echo sprintf( '<tr>' .
					'<td><span class="alt-type add-new-h2 %1$s">%2$s</span></td>' .
					'<td><strong>%3$s</strong> ' .
					'<img id="loading-%4$s" style="display:none;width:1em;margin-top:-1em;" src="%5$s" />' .
					'<strong><small id="success-%4$s" style="display:none;">%6$s</small></strong><br />' .
					'<small>%7$s&nbsp;</small></td>' .
					'</tr>',
					$set_as_winner, $alt_label,
					$this->trunk( $alt->get_name() ),
					$value['id'], nelioab_asset_link( '/images/loading-small.gif' ),
					__( '(Done!)', 'nelioab' ),
					implode( ' | ', $action_links ) );
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

		protected function print_actions_info() { ?>
			<h3><?php _e( 'Conversion Action', 'nelioab' ); ?></h3>
			<ul style="margin-left:2em;">
				<li>- <?php _e( 'The user accesses the tested post in order to, hopefully, further read it', 'nelioab' ); ?></li>
			</ul>
			<?php
		}

	}//NelioABHeadlineAltExpProgressPage

}

