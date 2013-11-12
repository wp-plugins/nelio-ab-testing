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


if ( !class_exists( 'NelioABPostAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/progress/alt-exp-progress-page.php' );

	class NelioABPostAltExpProgressPage extends NelioABAltExpProgressPage {

		private $ori;
		private $is_ori_page;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
		}

		protected function get_original_name() {
			// Original title
			$exp = $this->exp;
			$aux = get_post( $exp->get_original() );
			$this->ori = sprintf( __( 'id is %s', 'nelioab' ), $aux->ID );
			if ( $aux ) {
				$this->ori = trim( $aux->post_title );
				if ( $aux->post_type == 'post' )
					$this->is_ori_page = false;
			}
			return $this->ori;
		}

		protected function get_original_value() {
			return $this->exp->get_original();
		}

		protected function print_js_function_for_post_data_overriding() {?>
			function nelioab_confirm_overriding(id) {
				jQuery("#apply_alternative #alternative").attr("value",id);
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
				if ( $the_winner == 0 ) {
					if ( $this->is_ori_page )
						echo '<p><b>' . __( 'Right now, no alternative is beating the original page.', 'nelioab' ) . '</b></p>';
					else
						echo '<p><b>' . __( 'Right now, no alternative is beating the original post.', 'nelioab' ) . '</b></p>';
				}
				if ( $the_winner > 0 ) {
					if ( $this->is_ori_page )
						echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original page.', 'nelioab' ), $the_winner ) . '</b></p>';
					else
						echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original post.', 'nelioab' ), $the_winner ) . '</b></p>';
				}
			}
			else {
				if ( $the_winner == 0 ) {
					if ( $this->is_ori_page )
						echo '<p><b>' . __( 'No alternative was better the original page.', 'nelioab' ) . '</b></p>';
					else
						echo '<p><b>' . __( 'No alternative was better the original post.', 'nelioab' ) . '</b></p>';
				}
				if ( $the_winner > 0 ) {
					if ( $this->is_ori_page )
						echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original page.', 'nelioab' ), $the_winner ) . '</b></p>';
					else
						echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original post.', 'nelioab' ), $the_winner ) . '</b></p>';
				}
			}
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

		protected function print_the_real_alternatives() {
			// REAL ALTERNATIVES
			// -----------------------------------------
			$exp = $this->exp;
			$i   = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				$link      = get_permalink( $alt->get_value() );
				$edit_link = '';
				
				if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
					$edit_link = sprintf( ' <small>(<a href="javascript:if(nelioab_confirm_editing()) window.location.href=\'%s\'">%s</a>)</small></li>',
						admin_url() . '/post.php?post=' . $alt->get_value() . '&action=edit',
						__( 'Edit' ) );
				}
		
				if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
					$edit_link = sprintf(
						' <small id="success-%3$s" style="display:none;">(%1$s)</small>' .
						'<img id="loading-%3$s" style="height:10px;width:10px;display:none;" src="%2$s" />' .
						'<small class="apply-link">(<a href="javascript:nelioab_confirm_overriding(%3$s);">%4$s</a>)</small></li>',
						__( 'Done!', 'nelioab' ),
						NELIOAB_ASSETS_URL . '/images/loading-small.gif?' . NELIOAB_PLUGIN_VERSION,
						$alt->get_value(), __( 'Apply', 'nelioab' ) );
				}
		
				if ( $this->is_winner( $alt->get_value() ) )
					$set_as_winner = $this->winner_label;
				else
					$set_as_winner = '';

				$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );
				echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span><a href="%s" target="_blank">%s</a>%s',
					$set_as_winner, $alt_label, $link, $alt->get_name(), $edit_link );
		
			}
		}

		protected function print_dialog_content() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			$exp = $this->exp;
			?>
			<p><?php
				if ( $this->is_ori_page ) {
					_e( 'You are about to override the original page with the content of ' .
						'an alternative. Please, remember this operation cannot be undone!',
						'nelioab' );
				}
				else {
					_e( 'You are about to override the original post with the content of ' .
						'an alternative. Please, remember this operation cannot be undone!',
						'nelioab' );
				}
			?></p>
			<form id="apply_alternative" method="post" action="<?php
				echo admin_url() . 'admin.php?page=nelioab-experiments&action=progress&id=' .
				$exp->get_id(); ?>">
				<input type="hidden" name="apply_alternative" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $exp->get_type(); ?>" />
				<input type="hidden" id="original" name="original" value="<?php echo $exp->get_original(); ?>" />
				<input type="hidden" id="alternative" name="alternative" value="" />
				<p><input type="checkbox" id="copy_content" name="copy_content" checked="checked" disabled="disabled" /><?php
					_e( 'Override title and content', 'nelioab' ); ?></p>
				<p><input type="checkbox" id="copy_meta" name="copy_meta" <?php
					if ( NelioABSettings::is_copying_metadata_enabled() ) echo 'checked="checked" ';
				?>/><?php _e( 'Override all metadata', 'nelioab' ); ?></p>
				<?php
				if ( !$this->is_ori_page ) {?>
					<p><input type="checkbox" id="copy_categories" name="copy_categories" <?php
						if ( NelioABSettings::is_copying_categories_enabled() ) echo 'checked="checked" ';
					?>/><?php _e( 'Override categories', 'nelioab' ); ?></p>
					<p><input type="checkbox" id="copy_tags" name="copy_tags" <?php
						if ( NelioABSettings::is_copying_tags_enabled() ) echo 'checked="checked" ';
					?>/><?php _e( 'Override tags', 'nelioab' ); ?></p><?php
				}?>
			</form>
			<?php
		}

		protected function get_labels_for_conversion_rate_js() {
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle'] = __( 'for the original and the alternative pages', 'nelioab' );
			else
				$labels['subtitle'] = __( 'for the original and the alternative posts', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_improvement_factor_js() {
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle'] = __( 'with respect to the original page', 'nelioab' );
			else
				$labels['subtitle'] = __( 'with respect to the original post', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
			return $labels;
		}

		protected function get_labels_for_visitors_js() {
			$labels = array();
			$labels['title']       = __( 'Page Views and Conversions', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle']    = __( 'for the original and the alternative pages', 'nelioab' );
			else
				$labels['subtitle']    = __( 'for the original and the alternative posts', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			return $labels;
		}

	}//NelioABPostAltExpProgressPage

}



?>
