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
			return 0; // TODO
		}

		protected function print_js_function_for_post_data_overriding() {
			// Nothing to do here, because these experiments do not permit overriding
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
				$set_as_winner, $ori_label, $this->trunk( $this->get_original_name() ) );
		}

		protected function print_the_real_alternatives() {
			?>
			<script>
				function openCss( css ) {
					var id = "#css-" + css;
					var w = window.open("", "blank", "toolbar=no,location=no,width=600,height=350,top=0,left=0,scrollbars=yes");
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
				$edit_link = '';

				if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
					$edit_link = sprintf( ' <small>(<a href="javascript:if(nelioab_confirm_editing()) window.location.href=\'%s\'">%s</a>)</small></li>',
						admin_url( 'admin.php?page=nelioab-css-edit&exp_id=' . $this->exp->get_id() . '&css_id=' . $alt->get_id() ),
						__( 'Edit' ) );
				}

				$winner_button = '';
				if ( $this->is_winner( $alt->get_value() ) )
					$winner_button = '-primary';

				if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
					$edit_link = '';
				}

				if ( $this->is_winner( $alt->get_value() ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );
				echo '<li>';
				?>
				<span id="css-<?php echo $alt->get_id(); ?>" style="display:none;"><?php
					echo $alt->get_value();
				?></span>
				<?php
				echo sprintf( '<span class="alt-type add-new-h2 %s">%s</span><a href="#" onClick="javascript:openCss(%s)">%s</a>%s',
					$set_as_winner, $alt_label, $alt->get_id(), $this->trunk( $alt->get_name() ), $edit_link );
				echo '</li>';

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
