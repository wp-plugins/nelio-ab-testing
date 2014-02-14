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


if ( !class_exists( 'NelioABThemeAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABThemeAltExpProgressPage extends NelioABAltExpProgressPage {

		private $ori;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp     = null;
			$this->results = null;
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Theme Experiment', 'nelioab' );
		}

		protected function get_original_name() {
			// Original title
			$exp = $this->exp;
			$this->ori = $exp->get_original()->get_name();
		}

		protected function get_original_value() {
			$exp = $this->exp;
			return $exp->get_original()->get_value();
		}

		protected function print_js_function_for_post_data_overriding() { ?>
			function nelioab_confirm_overriding(id, stylesheet, template) {
				jQuery("#apply_alternative #stylesheet").attr("value",stylesheet);
				jQuery("#apply_alternative #template").attr("value",template);
				nelioab_show_the_dialog_for_overriding(id);
			}
			<?php
		}

		protected function print_winner_info() {
			// Winner (if any) details
			$the_winner            = $this->who_wins();
			$the_winner_confidence = $this->get_winning_confidence();

			$exp = $this->exp;
			if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'Right now, no alternative is beating the original theme.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original theme.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
			else {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'No alternative was better the original theme.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original theme.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
		}

		protected function print_the_original_alternative() {
			// THE ORIGINAL
			// -----------------------------------------
			$exp       = $this->exp;
			$ori_label = __( 'Original', 'nelioab' );

			if ( $this->is_winner( $this->get_original_value() ) )
				$set_as_winner = $this->winner_label;
			else
				$set_as_winner = '';

			echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span>%s</li>',
				$set_as_winner, $ori_label, $this->ori );
		}

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp        = $this->exp;
			$i          = 0;
			$the_themes = wp_get_themes();
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$edit_link = '';

				if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
					$theme = NULL;
					foreach ( $the_themes as $t ) {
						if ( $t['Stylesheet'] == $alt->get_value() ) {
							$theme = $t;
							break;
						}
					}

					$winner_button = '';
					if ( $this->is_winner( $alt->get_value() ) )
						$winner_button = '-primary';

					if ( $theme != NULL ) {
						$edit_link = sprintf(
							' <small id="success-%4$s" style="display:none;">(%1$s)</small>' .
							'<img id="loading-%4$s" style="height:10px;width:10px;display:none;" src="%2$s" />' .
							'<span class="apply-link"><a class="button%3$s" ' .
							'style="font-size:96%%;padding-left:5px;padding-right:5px;margin-left:1em;" '.
							'href="javascript:nelioab_confirm_overriding(\'%4$s\', \'%5$s\', \'%6$s\');">%7$s</a></span></li>',
							__( 'Done!', 'nelioab' ),
							NELIOAB_ASSETS_URL . '/images/loading-small.gif?' . NELIOAB_PLUGIN_VERSION,
							$winner_button,
							$alt->get_value(),
							$theme['Stylesheet'], $theme['Template'],
							__( 'Apply', 'nelioab' ) );
					}
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
				_e( 'You are about to override the original theme with the alternative. Do you want to continue?', 'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url() . 'admin.php?page=nelioab-experiments&action=progress&id=' .
				$exp->get_id(); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $exp->get_type(); ?>" />
				<input type="hidden" id="stylesheet" name="stylesheet" value="" />
				<input type="hidden" id="template" name="template" value="" />
			</form>
			<?php
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			$labels['subtitle'] = __( 'for the original and the alternative themes', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to the original theme', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = __( 'for the original and the alternative themes', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABThemeAltExpProgressPage

}



?>
