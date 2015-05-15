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

if ( !class_exists( 'NelioABMenuAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABMenuAltExpProgressPage extends NelioABAltExpProgressPage {

		protected $ori;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
		}

		protected function print_experiment_details_title() {
			_e( 'Details of the Menu Experiment', 'nelioab' );
		}

		protected function get_original_name() {
			// Original title
			$exp = $this->exp;
			$menus = wp_get_nav_menus();
			$menu = false;
			foreach ( $menus as $aux )
				if ( $aux->term_id == $exp->get_original()->get_value() )
					$menu = $aux;
			$this->ori = sprintf( __( 'Unknown (menu id is %s)', 'nelioab' ), $exp->get_original()->get_value() );
			if ( $menu )
				$this->ori = $menu->name;
			return $this->ori;
		}

		protected function get_original_value() {
			return $this->exp->get_original()->get_value();
		}

		protected function print_js_function_for_post_data_overwriting() { ?>
			function nelioab_confirm_overwriting(id) {
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
			if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'Right now, no alternative is beating the original menu.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original menu.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
			else {
				if ( $the_winner == 0 )
					echo '<p><b>' . __( 'No alternative was better the original menu.', 'nelioab' ) . '</b></p>';
				if ( $the_winner > 0 )
					echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original menu.', 'nelioab' ), $the_winner ) . '</b></p>';
			}
		}


		protected function print_alternatives_block() {
			echo '<table id="alternatives-in-progress">';
			$this->print_the_original_alternative();
			$this->print_the_real_alternatives();
			echo '</table>';
		}


		private function make_link_for_edit( $id ) {
			$exp = $this->exp;
			$exp_id = $exp->get_id();
			if ( $exp->get_original()->get_id() == $id ) {
				$menu_alt_id = $exp->get_original()->get_id();
				$menu_id = $exp->get_original()->get_value();
				$link = 'nav-menus.php?menu=' . $menu_id;
			}
			else {
				foreach ( $exp->get_alternatives() as $alt ) {
					if ( $alt->get_id() == $id ) {
						$menu_alt_id = $alt->get_id();
						$menu_id = $alt->get_value();
						break;
					}
				}
				$link = 'nav-menus.php?' .
					'nelioab_exp=' . $exp_id . '&nelioab_alt=' . $menu_alt_id .
					'&nelioab_check=' . md5( $exp_id . $menu_alt_id . $menu_id ) .
					'&menu=' . $menu_id;
			}
			return sprintf( ' <a href="javascript:nelioabConfirmEditing(\'%s\',\'dialog\');">%s</a>',
				admin_url( $link ), __( 'Edit' ) );
		}


		protected function get_action_links( $exp, $alt_id ) {
			$action_links = array();
			switch ( $exp->get_status() ) {
				case NelioABExperiment::STATUS_RUNNING:
					array_push( $action_links, $this->make_link_for_edit( $alt_id ) );
					break;
				case NelioABExperiment::STATUS_FINISHED:
					if ( $alt_id == $exp->get_originals_id() )
						break;
					$menu = false;
					foreach ( $exp->get_alternatives() as $alt ) {
						if ( $alt->get_id() == $alt_id )
							$menu = $alt->get_value();
					}
					if ( $menu ) {
						$aux = sprintf(
							' <a class="apply-link" href="javascript:nelioab_confirm_overwriting(%1$s);">%2$s</a>',
							$menu, __( 'Apply', 'nelioab' ) );
						array_push( $action_links, $aux );
					}
					break;
			}
			return $action_links;
		}


		protected function print_the_original_alternative() {
			// THE ORIGINAL
			// -----------------------------------------
			$exp       = $this->exp;
			$ori_label = __( 'Original', 'nelioab' );

			$action_links = $this->get_action_links( $exp, $exp->get_originals_id() );

			if ( $this->is_winner( $exp->get_originals_id() ) )
				$set_as_winner = $this->winner_label;
			else
				$set_as_winner = '';

			$entry = '<strong>' . $this->trunk( $this->ori ) . '</strong>';

			echo sprintf( '<tr>' .
				'<td><span class="alt-type add-new-h2 %s">%s</span></td>' .
				'<td>%s<br />' .
				'<small>%s&nbsp;</small></td>' .
				'</tr>',
				$set_as_winner, $ori_label, $entry, implode( ' | ', $action_links ) );
		}

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;

			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;

				$action_links = $this->get_action_links( $exp, $alt->get_id() );

				if ( $this->is_winner( $alt->get_id() ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );

				$entry = '<strong>' . $this->trunk( $alt->get_name() ) . '</strong>';

				echo sprintf( '<tr>' .
					'<td><span class="alt-type add-new-h2 %1$s">%2$s</span></td>' .
					'<td>%3$s ' .
					'<img id="loading-%4$s" style="display:none;width:1em;margin-top:-1em;" src="%5$s" />' .
					'<strong><small id="success-%4$s" style="display:none;">%6$s</small></strong><br />' .
					'<small>%7$s&nbsp;</small></td>' .
					'</tr>',
					$set_as_winner, $alt_label,
					$entry,
					$alt->get_value(), nelioab_asset_link( '/images/loading-small.gif' ),
					__( '(Done!)', 'nelioab' ),
					implode( ' | ', $action_links ) );
			}

		}

		protected function print_dialog_content() {
			$exp = $this->exp;
			?>
			<p><?php
				_e( 'You are about to overwrite the original menu items with the alternative ones. Please, remember <strong>this operation cannot be undone</strong>. Are you sure you want to overwrite the menu?', 'nelioab' );
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url(
					'admin.php?page=nelioab-experiments&action=progress&' .
					'id=' . $exp->get_id() . '&' .
					'type=' . $exp->get_type() ); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $exp->get_type(); ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_original()->get_value(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
			</form>
			<?php
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			$labels['subtitle'] = __( 'for the original and the alternative menus', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to the original menu', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['subtitle']    = __( 'for the original and the alternative menus', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABMenuAltExpProgressPage

}

