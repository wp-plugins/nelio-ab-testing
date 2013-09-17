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


if ( !class_exists( NelioABSettingsPage ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/settings.php' );

	class NelioABSettingsPage extends NelioABAdminAjaxPage {

		private $p_style;
		private $email;
		private $is_email_valid;
		private $reg_num;
		private $is_reg_num_valid;
		private $tac;
		private $sites;
		private $max_sites;

		private $current_site_status;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->p_style          = '';
			$this->email            = '';
			$this->reg_num          = '';
			$this->is_reg_num_valid = false;
			$this->tac              = false;
			$this->sites            = array();
			$this->max_sites        = 1;
		}

		public function set_current_site_status( $site_status ) {
			$this->current_site_status = $site_status; 
		}

		public function set_email( $email ) {
			$this->email = $email;
		}

		public function set_email_validity( $is_email_valid ) {
			$this->is_email_valid = $is_email_valid;
		}

		public function set_reg_num( $reg_num ) {
			$this->reg_num = $reg_num;
		}

		public function set_reg_num_validity( $is_reg_num_valid ) {
			$this->is_reg_num_valid = $is_reg_num_valid;
		}

		public function set_tac_checked( $tac ) {
			$this->tac = $tac;
		}

		public function set_registered_sites( $sites ) {
			$this->sites = $sites;
		}

		public function set_max_sites( $max_sites ) {
			$this->max_sites = $max_sites;
		}

		protected function do_render() {?>
			<form id="nelioab_account_form" method="post">
				<input type="hidden" name="nelioab_account_form" value="true" />
	
				<?php
				$this->make_section(
					__( 'Account Information', 'nelioab' ),
					array(
						array (
							'label'     => __( 'E-Mail', 'nelioab' ),
							'id'        => 'settings_email',
							'callback'  => array( &$this, 'print_email_field' ),
							'mandatory' => true ),
						array (
							'label'     => __( 'Registration Number', 'nelioab' ),
							'id'        => 'settings_reg_num',
							'callback'  => array( &$this, 'print_reg_num_field' ),
							'mandatory' => true ),
						array (
							'label'     => __( 'I have read and accept the ' .
						                  '<a href="http://wp-abtesting.com/terms-conditions" target="_blank">Terms and Conditions</a> ' .
						                  'of this service.', 'nelioab' ),
							'id'        => 'settings_tac',
							'mandatory' => true,
							'checkbox'  => true,
							'checked'   => $this->tac,
							'pre'       => '<br />' ),
					) );
				?>
	
			</form>

			<?php echo $this->make_submit_button(
					__( 'Save Account Information', 'nelioab' ),
					'nelioab_account_form'
				); ?>


			<?php if ( $this->is_email_valid && $this->is_reg_num_valid && $this->tac ) { ?>

			<form id="nelioab_registration_form" method="post">
				<input type="hidden" name="nelioab_registration_form" value="true" />
				<input type="hidden" id="nelioab_registration_action" name="nelioab_registration_action" value="" />
			</form>

			<br /><br /><br />
			<h2 style="margin-bottom:0px;padding-bottom:0px;"><?php
				$status_fg_color = '#CC0000';
				$status_bg_color = '#FFD9D9';
				$status_text     = __( 'NOT REGISTERED', 'nelioab' );

				if ( $this->current_site_status == NelioABSite::ACTIVE ) {
					$status_fg_color = '#008800';
					$status_bg_color = '#D9FFD9';
					$status_text     = __( 'ACTIVE', 'nelioab' );
				}

				if ( $this->current_site_status == NelioABSite::NON_MATCHING_URLS ) {
					$status_fg_color = '#D67300';
					$status_bg_color = '#FFE7BF';
					$status_text     = __( 'OUT OF SYNC', 'nelioab' );
				}

				$status_title = sprintf(
					'<span style="color:%s;background-color:%s;font-size:0.5em;" class="add-new-h2">%s</span>',
					$status_fg_color, $status_bg_color, $status_text );

				echo __( 'Site Status', 'nelioab' ) . '&nbsp;&nbsp;' . $status_title;

			?></h2>

			<!-- <p style="margin-top:0px;padding-top:0px;color:grey;"><?php
			if ( $this->max_sites <= 1 )
				_e( '* This account may register one site only.', 'nelioab' );
			else
				echo sprintf( __( 'This account may register up to %s sites.', 'nelioab' ),
					$this->max_sites );
			?></p>-->
	
			<?php
	
				switch( $this->current_site_status ) {
				case NelioABSite::NON_MATCHING_URLS:
					$this->print_site_non_matching();
					break;
	
				case NelioABSite::ACTIVE:
					$this->print_site_ok();
					break;
	
				case NelioABSite::NOT_REGISTERED:
				case NelioABSite::INVALID_ID:
				default:
					$this->print_site_to_be_registered();
					break;
	
				}
	
			?>

				<?php
				$other_sites = array();
				$this_url    = get_option( 'siteurl' );
				foreach( $this->sites as $site )
					if ( $site->get_url() != $this_url )
						array_push( $other_sites, $site );

				if ( count( $other_sites ) > 0 ) {?>
					<br />
					<h2><?php _e( 'Other Sites', 'nelioab' ); ?></h2>
					<p><?php _e( 'You also have the following sites registered to your account:', 'nelioab' ); ?></p>
					<ul style="margin-left: 1em;">
					<?php 
					foreach( $other_sites as $site ) 
						echo sprintf( '<li> - <a href="%s" target="_blank">%s</a></li>',
							$site->get_url(), $site->get_url() );
					?>
					</ul>
			<?php
			}?>

			<?php
			}
		}

		public function print_email_field() {?>
			<input name="settings_email" type="text" id="settings_email"
				class="regular-text" value="<?php echo $this->email; ?>"/><?php
		}

		public function print_reg_num_field() {?>
			<input name="settings_reg_num" type="text" id="settings_reg_num"
				class="regular-text" value="<?php echo $this->reg_num; ?>"/><?php
			if ( $this->is_email_valid) {
				if ( $this->is_reg_num_valid ) {?>
					<span style="color:#00AA00; font-weight:bold;"><?php _e( 'OK', 'nelioab' ); ?></span><?php
				}
				else {?>
					<span style="color:red; font-weight:bold;"><?php _e( 'INVALID', 'nelioab' ); ?></span><?php
				}
			}
		}

		private function print_site_non_matching() {
			echo '<p>Error #001 - Non matching URLs.<br />Please, report this error to Nelio using the Feedback form.</p>';
		}

		private function print_site_ok() {
			echo sprintf( "<p style=\"$this->p_style\">%s</p>\n",
				__( 'This site is currently registered in your account.', 'nelioab' )
			);

			$this->print_registration_buttons( false, true );
		}

		private function print_site_to_be_registered() {
			$can_register = count( $this->sites ) < $this->max_sites;
			$explanation  = '';

			if ( $can_register )
				$explanation = __( 'Please, use the «Register Site» button below to register this site. Once it ' .
					'has been successfully registered, you will be able to A/B-test your site.', 'nelioab' );
			else
				$explanation = sprintf( "%s</p><p style=\"$this->p_style\">%s",
					__( 'You must register this site in order to A/B-test it. Unfortunately, you cannot ' .
						'register any more sites with your current subscription.', 'nelioab' ),
					__( 'Please, <b>upgrade your <i>Nelio A/B Testing</i> subscription</b> so that you can ' .
						'register and manage more sites, or <b>cancel one of your registrations</b> and try again. Keep in ' .
						'mind that canceling the registration of a site will cause permanent loss of all experiments ' .
						'associated to that site.', 'nelioab' )
				);

			echo sprintf( "<p style=\"$this->p_style\">%s</p>\n",
				sprintf( __( 'Oops! This site is <b>not</b> registered in your account. %s', 'nelioab' ),
					$explanation )
			);

			$this->print_registration_buttons( $can_register, false );
		}

		private function print_registration_buttons( $register_enabled, $cancel_enabled ) {?>
			<div style="max-width:700px"><?php
				$register_js = 'javascript:' .
					'void(0);';
					$cancel_js = 'javascript:' .
						'void(0);';
				if ( $register_enabled ) {
					$register_js = 'javascript:' .
						'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'register\');' .
						'jQuery(\'#nelioab_registration_form\').submit();';
				}
				if ( $cancel_enabled ) {
					$cancel_js = 'javascript:' .
						'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'cancel\');' .
						'jQuery(\'#nelioab_registration_form\').submit();';
				}
				?>
				<?php echo $this->make_js_button( __( 'Register Site', 'nelioab' ), $register_js, $register_enabled, true ); ?>
				<?php echo $this->make_js_button( __( 'Cancel Registration', 'nelioab' ), $cancel_js, $cancel_enabled, false ); ?>
			</div>
		<?php
		}

	}//NelioABSettingsPage

	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABSitesTable extends NelioABAdminTable {

		function __construct( $sites ){
   	   parent::__construct( array(
				'singular'  => __( 'experiment', 'nelioab' ),
				'plural'    => __( 'experiments', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->set_items( $sites );
		}
		
		function get_columns(){
			return array(
				'url'         => __( 'URL', 'nelioab' ),
			//	'status'      => __( 'Status', 'nelioab' ),
			);
		}

		function column_url( $site ){
			return sprintf(
				'<span class="row-title">%s</span>&nbsp;',
				$site->get_url()
			);
		}

		public function column_creation( $exp ) {
			return date_i18n( get_option( 'date_format' ) . ' - ' . get_option('time_format'), $exp->get_creation_date() );
		}

		public function column_status( $exp ){
			return NelioABExperimentStatus::to_string( $exp->get_status() );
		}

	}// NelioABExperimentsTable


}



?>
