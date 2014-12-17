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

if ( !class_exists( 'NelioABWidgetAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABWidgetAltExpProgressPage extends NelioABAltExpProgressPage {

		private $alts_to_apply;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp           = null;
			$this->results       = null;
			$this->alts_to_apply = false;
		}

		public function set_there_are_alternatives_to_apply( $alts_to_apply ) {
			$this->alts_to_apply = $alts_to_apply;
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Widget Experiment', 'nelioab' );
		}

		protected function get_original_name() {
			return __( 'Default Widget Set', 'nelioab' );
		}

		protected function get_original_value() {
			return $this->exp->get_originals_id();
		}

		protected function print_js_function_for_post_data_overwriting() { ?>
			function nelioab_confirm_overwriting(id, elem) {
				if ( 'apply-ori-and-clean' == elem ) {
					jQuery('#dialog-content p.apply-ori-and-clean').show();
					jQuery('#dialog-content p.apply-alt-and-clean').hide();
				}
				else {
					jQuery('#dialog-content p.apply-alt-and-clean').show();
					jQuery('#dialog-content p.apply-ori-and-clean').hide();
				}
				jQuery("#apply_alternative #alternative").attr("value",id);
				nelioab_show_the_dialog_for_overwriting(id);
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
					echo '<p><b>' . __( 'Right now, no alternative Widget set is helping to improve your site.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Right now, the alternative %s is better than none to improve your site.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
			else {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'No alternative Widget set helped to improve your site.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'The alternative Widget set %s was better than the original set.', 'nelioab' ), $the_winner ) . '</b></p>';
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

			$action_links = array();

			switch ( $exp->get_status() ) {

				case NelioABExperimentStatus::RUNNING:
					$action_links['edit'] = sprintf( ' ' .
						'<a href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
						admin_url( 'widgets.php' ),
						__( 'Edit' ) );
					break;

				case NelioABExperimentStatus::FINISHED:
					if ( $this->alts_to_apply ) {
						$action_links['apply-and-clean'] = sprintf( ' ' .
							'<a class="apply-link" href="javascript:nelioab_confirm_overwriting(%1$s,\'apply-ori-and-clean\');">%2$s</a>',
							$exp->get_originals_id(), __( 'Apply and Clean' ) );
					}
					break;

			}

			echo sprintf( '<tr>' .
				'<td><span class="alt-type add-new-h2 %1$s">%2$s</span></td>' .
				'<td><strong>%3$s</strong> ' .
				'<img id="loading-%4$s" style="display:none;width:1em;margin-top:-1em;" src="%5$s" />' .
				'<strong><small id="success-%4$s" style="display:none;">%6$s</small></strong><br />' .
				'<small>%7$s&nbsp;</small></td>' .
				'</tr>',
				$set_as_winner, $ori_label, $this->trunk( $this->get_original_name() ),
				$exp->get_originals_id(),
				nelioab_asset_link( '/images/loading-small.gif' ),
				__( '(Done!)', 'nelioab' ),
				implode( ' | ', $action_links ) );
		}

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$action_links = array();

				switch ( $exp->get_status() ) {

					case NelioABExperimentStatus::RUNNING:
						$action_links['edit'] = sprintf( ' ' .
							'<a href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
							admin_url( 'widgets.php?nelioab_exp=' . $exp->get_id() .
								'&nelioab_alt=' . $alt->get_id() . '&nelioab_check=' .
								md5( $exp->get_id() . $alt->get_id() ) ),
							__( 'Edit' ) );
						break;

				case NelioABExperimentStatus::FINISHED:
					if ( $this->alts_to_apply ) {
						$action_links['edit-and-clean'] = sprintf( ' ' .
							'<a class="apply-link" href="javascript:nelioab_confirm_overwriting(%1$s,\'apply-alt-and-clean\');">%2$s</a>',
							$alt->get_id(), __( 'Apply and Clean' ) );
					}
					break;

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
					'<td><strong>%3$s</strong> ' .
					'<img id="loading-%4$s" style="display:none;width:1em;margin-top:-1em;" src="%5$s" />' .
					'<strong><small id="success-%4$s" style="display:none;">%6$s</small></strong><br />' .
					'<small>%7$s&nbsp;</small></td>' .
					'</tr>',
					$set_as_winner, $alt_label,
					$this->trunk( $alt->get_name() ),
					$alt->get_id(),
					nelioab_asset_link( '/images/loading-small.gif' ),
					__( '(Done!)', 'nelioab' ),
					implode( ' | ', $action_links ) );

			}

		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			$labels['subtitle'] = __( 'for default and alternative Widget sets', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to the original Widget set', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = __( 'for default and alternative Widget sets', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

		protected function print_dialog_content() {
			$exp = $this->exp;
			?>
			<p class="apply-ori-and-clean" style="display:none;"><?php
				_e( 'You are about to <strong>make your current widgets permanent</strong>.<br><br>Please note alternative widget sets will be removed. <strong>This operation cannot be undone</strong>.<br><br>Are you sure you want to make them permanent?', 'nelioab' );
			?></p>
			<p class="apply-alt-and-clean" style="display:none;"><?php
				_e( 'You are about to <strong>replace your current widgets with the alternative ones</strong>.<br><br>Please note alternative widget sets will be removed. <strong>This operation cannot be undone</strong>.<br><br>Are you sure you want to replace your current set of widgets?', 'nelioab' );
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
			</form>
			<?php
		}

	}//NelioABWidgetAltExpProgressPage

}

