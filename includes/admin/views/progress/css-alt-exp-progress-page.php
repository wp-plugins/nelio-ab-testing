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
			if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
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
			$exp       = $this->exp;
			$ori_label = __( 'Original', 'nelioab' );

			if ( $this->is_winner( $this->get_original_value() ) )
				$set_as_winner = $this->winner_label;
			else
				$set_as_winner = '';

			echo sprintf( '<tr>' .
				'<td><span class="alt-type add-new-h2 %s">%s</span></td>' .
				'<td><strong>%s</strong><br />' .
				'<small>&nbsp;</small></td>' .
				'</tr>',
				$set_as_winner, $ori_label, $this->trunk( $this->get_original_name() ) );
		}

		protected function print_the_real_alternatives() {
			?>
			<script>
				function openCss( css ) {
					var id = '#css-' + css;
					var w = window.open('', 'blank', 'toolbar=no,location=no,width=600,height=350,top=0,left=0,scrollbars=yes');
					w.document.open();
					w.document.write('<pre>' + jQuery(id).text() + '</pre>');
					w.document.close();
				}
			</script>
			<?php
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$action_links = array();

				if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
					$action_links['edit'] = sprintf( ' <a href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
						admin_url( 'admin.php?page=nelioab-css-edit&exp_id=' . $this->exp->get_id() . '&css_id=' . $alt->get_id() ),
						__( 'Edit' ) );
				}

				$winner_button = '';
				if ( $this->is_winner( $alt->get_value() ) )
					$winner_button = '-primary';

				if ( $this->is_winner( $alt->get_value() ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );

				echo sprintf( '<tr>' .
					'<td><span class="alt-type add-new-h2 %1$s">%2$s</span></td>' .
					'<td><strong><a href="javascript:openCss(%4$s);">%3$s</a></strong> ' .
					'<span id="css-%4$s" style="display:none;">%5$s</span> ' .
					'<img id="loading-%4$s" style="display:none;width:1em;margin-top:-1em;" src="%6$s" />' .
					'<strong><small id="success-%4$s" style="display:none;">%7$s</small></strong><br />' .
					'<small>%8$s&nbsp;</small></td>' .
					'</tr>',
					$set_as_winner, $alt_label,
					$this->trunk( $alt->get_name() ),
					$alt->get_id(), $alt->get_value(),
					nelioab_asset_link( '/images/loading-small.gif' ),
					__( '(Done!)', 'nelioab' ),
					implode( ' | ', $action_links ) );

			}

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
