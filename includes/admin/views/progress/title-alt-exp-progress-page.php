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


if ( !class_exists( 'NelioABTitleAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/post-alt-exp-progress-page.php' );

	class NelioABTitleAltExpProgressPage extends NelioABPostAltExpProgressPage {

		public function __construct( $title ) {
			parent::__construct( $title );
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Title Experiment', 'nelioab' );
		}

		protected function print_the_original_alternative() {
			// THE ORIGINAL
			// -----------------------------------------
			$exp       = $this->exp;
			$link      = get_permalink( $exp->get_original() );
			$ori_label = __( 'Original', 'nelioab' );

			$edit_link = '';
			if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
				$edit_link = sprintf( ' <small>(<a href="javascript:if(nelioab_confirm_editing()) window.location.href=\'%s\'">%s</a>)</small></li>',
					admin_url() . '/post.php?post=' . $exp->get_original() . '&action=edit',
					__( 'Edit' ) );
			}

			if ( $this->is_winner( $exp->get_original() ) )
				$set_as_winner = $this->winner_label;
			else
				$set_as_winner = '';

			echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span><a href="%s" target="_blank">%s</a>%s</li>',
				$set_as_winner, $ori_label, $link, $this->ori, $edit_link );
		}

		protected function print_js_function_for_post_data_overriding() {?>
			function nelioab_confirm_overriding(id, title) {
				jQuery("#apply_alternative #alternative").attr("value",title);
				nelioab_show_the_dialog_for_overriding(id);
			}
			<?php
		}

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$edit_link = '';

				$winner_button = '';
				if ( $this->is_winner( $alt->get_value() ) )
					$winner_button = '-primary';

				if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
					$edit_link = sprintf(
						' <small id="success-%4$s" style="display:none;">(%1$s)</small>' .
						'<img id="loading-%4$s" style="height:10px;width:10px;display:none;" src="%2$s" />' .
						'<span class="apply-link"><a class="button%3$s" ' .
						'style="font-size:96%%;padding-left:5px;padding-right:5px;margin-left:1em;" '.
						'href="javascript:nelioab_confirm_overriding(%4$s, \'%5$s\');">%6$s</a></span></li>',
						__( 'Done!', 'nelioab' ),
						NELIOAB_ASSETS_URL . '/images/loading-small.gif?' . NELIOAB_PLUGIN_VERSION,
						$winner_button, $alt->get_value(),
						urlencode( $alt->get_name() ),
						__( 'Apply', 'nelioab' ) );
				}

				if ( $this->is_winner( $alt->get_value() ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );
				echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span>%s%s',
					$set_as_winner, $alt_label, $alt->get_name(), $edit_link );

			}
		}

		protected function print_dialog_content() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			$exp = $this->exp;
			?>
			<p><?php
				_e( 'You are about to override the original title with an alternative. ' .
					'Do you want to continue?',
					'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url() . 'admin.php?page=nelioab-experiments&action=progress&id=' .
				$exp->get_id(); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::TITLE_ALT_EXP; ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_original(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
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

	}//NelioABTitleAltExpProgressPage

}



?>
