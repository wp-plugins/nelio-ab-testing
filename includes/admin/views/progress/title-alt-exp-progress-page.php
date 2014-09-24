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

		protected function print_js_function_for_post_data_overriding() { ?>
			function nelioab_confirm_overriding(id, title) {
				jQuery("#apply_alternative #alternative").attr("value",title);
				nelioab_show_the_dialog_for_overriding(id);
			}
			<?php
		}


		protected function get_action_links( $exp, $alt_id ) {
			if ( $alt_id == $exp->get_originals_id() )
				return parent::get_action_links( $exp, $alt_id );
			if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
				$alternative = false;
				foreach( $exp->get_alternatives() as $alt )
					if ( $alt->get_value() == $alt_id )
						$alternative = $alt;
				$name = $alternative->get_name();
				$name = str_replace( "\\", "\\\\", $name );
				$name = str_replace( "'", "\\'", $name );
				$aux = sprintf(
					' <a href="javascript:nelioab_confirm_overriding(%s, \'%s\');">%s</a>',
					$alt_id, $name, __( 'Apply', 'nelioab' ) );
				return array( $aux );
			}
			return array();
		}


		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;

			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;

				$action_links = $this->get_action_links( $exp, $alt->get_value() );

				if ( $this->is_winner( $alt->get_value() ) )
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
					$alt->get_name(),
					$alt->get_value(), nelioab_asset_link( '/images/loading-small.gif' ),
					__( '(Done!)', 'nelioab' ),
					implode( ' | ', $action_links ) );
			}
		}


		protected function print_dialog_content() {
			require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
			$exp = $this->exp;
			?>
			<p><?php
				_e( 'You are about to override the original title with an alternative. Do you want to continue?', 'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url(
					'admin.php?page=nelioab-experiments&action=progress&id=' . $exp->get_id() ); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::TITLE_ALT_EXP; ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_originals_id(); ?>" />
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
