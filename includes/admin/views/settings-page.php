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
		private $user_info;

		private $current_site_status;
		private $error_retrieving_registered_sites;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->p_style          = '';
			$this->email            = '';
			$this->reg_num          = '';
			$this->is_reg_num_valid = false;
			$this->tac              = false;
			$this->sites            = array();
			$this->max_sites        = 1;

			$this->error_retrieving_registered_sites = false;
		}

		public function set_current_site_status( $site_status ) {
			$this->current_site_status = $site_status; 
		}

		public function set_user_info( $user_info ) {
			$this->user_info = $user_info;
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

		public function set_error_retrieving_registered_sites() {
			$this->error_retrieving_registered_sites = true;
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

				<?php
					if ( !$this->is_email_valid ) {
						_e( '<p>Don\'t you have an account yet? ' .
							'<a href="http://wp-abtesting.com/subscription-plans/">' .
							'Subscribe now!</a></p>',
							'nelioab' );
						echo '<br /><br />';
					}
				?>

				<input type="hidden" name="nelioab_account_form" value="true" />
	
				<?php
				$this->make_section(
					__( 'Nelio AB Testing &ndash; Account Access Details', 'nelioab' ),
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
					__( 'Access', 'nelioab' ),
					'nelioab_account_form'
				); ?>


			<br /><br /><br />
			<h2 style="margin-bottom:0px;padding-bottom:0px;"><?php
				$status_fg_color = '#777777';
				$status_bg_color = '#EFEFEF';
				$status_text     = __( 'UNDEFINED', 'nelioab' );
	
				if ( $this->user_info['status'] == 1 ) {
					$status_fg_color = '#008800';
					$status_bg_color = '#D9FFD9';
					$status_text     = __( 'ACTIVE', 'nelioab' );
				}
				else if ( $this->user_info['status'] == 2 ) {
					$status_fg_color = '#CC0000';
					$status_bg_color = '#FD9D9';
					$status_text     = __( 'NOT ACTIVE', 'nelioab' );
				}
	
				$status_title = sprintf(
					'<span style="color:%s;background-color:%s;font-size:0.5em;" class="add-new-h2">%s</span>',
					$status_fg_color, $status_bg_color, $status_text );
	
				echo __( 'Account Information', 'nelioab' ) . '&nbsp;&nbsp;' . $status_title;
	
			?></h2>

			<h3><?php _e( 'Name', 'nelioab' ); ?></h3>
			<p style="margin-top:0em;margin-left:3em;"><?php echo $this->user_info['lastname'] . ', ' . $this->user_info['firstname']; ?></p>
			<h3><?php _e( 'Subscription Details', 'nelioab' ); ?></h3>
			<p style="margin-top:0em;margin-left:3em;"><?php
				if ( !isset( $this->user_info['subscription'] ) )
					_e( 'No subscription information available.', 'nelioab' );
				else if ( $this->user_info['subscription'] == 'BETA' )
					_e( 'You are using your BETA free-pass.', 'nelioab' );
				else
					printf( '<a href="%s">%s</a>', $this->user_info['subscription'],
						__( 'Check your subscription details.', 'nelioab' ) );
			?></p>
			<p style="margin-top:0em;margin-left:3em;"><?php
				printf( __( 'This subscription plan permits up to %d page views per month.', 'nelioab' ),
					$this->user_info['total_quota'] );
			?></p>

			<h3><?php _e( 'Available Quota', 'nelioab' ); ?></h3>
			<?php
				$the_quota   = $this->user_info['quota'];
				$quota_color = '#00AA00';
				if ( $the_quota < 1000 )
					$quota_color = '#FF9532';
				if ( $the_quota < 200 )
					$quota_color = 'red';
			?>
			<p style="color:<?php echo $quota_color; ?>;margin-top:0em;margin-left:3em;font-size:120%;"><b><?php
				echo $the_quota;
			?></b></p>

			<?php if ( $this->is_email_valid && $this->is_reg_num_valid && $this->tac ) { ?>

				<?php
				$other_sites = array();
				$this_url    = get_option( 'siteurl' );
				foreach( $this->sites as $site )
					if ( $site->get_url() != $this_url )
						array_push( $other_sites, $site ); ?>

				<h3><?php _e( 'Registered Sites', 'nelioab' ); ?></h3>

				<?php
				if ( $this->error_retrieving_registered_sites ) {

					?><p style="margin-top:0em;margin-left:3em;"><?php
						echo __( 'There was an error while retrieving the list of all ' .
							'registered sites to this account. ',
							'nelioab' );

					if ( NelioABSettings::has_a_configured_site() ) {
							echo __( 'Nonetheless, please note this site is registered to your ' .
								'account, which means you can still use use all plugin\'s ' .
								'functionalities.',
								'nelioab' );
					}

					?></p><?php

				}
				else {
					switch( $this->current_site_status ) {
					case NelioABSite::NON_MATCHING_URLS:
						$this->print_site_non_matching();
						break;
			
					case NelioABSite::ACTIVE:
						$this->print_site_ok();
						break;
					}?>
			
					<?php
					if ( count( $other_sites ) > 0 ) {?>
						<ul style="margin-left:3em;margin-top:0px;">
						<?php 
						foreach( $other_sites as $site ) 
							echo sprintf( '<li> - <a href="%s" target="_blank">%s</a></li>',
								$site->get_url(), $site->get_url() );
						?>
						</ul>
					<?php
					}?>
	
					<?php
					switch( $this->current_site_status ) {
					case NelioABSite::NOT_REGISTERED:
					case NelioABSite::INVALID_ID:
						$this->print_site_to_be_registered();
						break;
					}?>
	
					<form id="nelioab_registration_form" method="post">
						<input type="hidden" name="nelioab_registration_form" value="true" />
						<input type="hidden" id="nelioab_registration_action" name="nelioab_registration_action" value="" />
					</form>

				<?php
				}
				?>
	
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
			echo '<ul style="margin:0em 0em 0em 3em;">';
			printf( '<li> - <b>%s</b> <small>(%s)</small></li>',
				get_option( 'siteurl' ), $this->cancel_registration_link() );
			echo '</ul>';
//			echo sprintf( "<p style=\"$this->p_style\">%s</p>\n",
//				__( 'This site is currently registered in your account.', 'nelioab' )
//			);
//
//			$this->print_registration_buttons( false, true );
		}

		private function print_site_to_be_registered() {
			$can_register = count( $this->sites ) < $this->max_sites;
			$explanation  = '';

			if ( $can_register )
				$explanation = $this->do_registration_link();
			else
				$explanation = sprintf( "%s</p><p style=\"$this->p_style margin-left:3em;\">%s",
					__( 'In order to use our service, you have to register it. Unfortunately, you already ' .
						'reached the maximum number of sites allowed by your current subscription.', 'nelioab' ),
					__( 'Please, <b>upgrade your <i>Nelio A/B Testing</i> subscription</b> so that you can ' .
						'register and manage more sites, or <b>access one of the other sites to cancel its subscription</b> ' .
						'and try again. Keep in mind that canceling the registration of a site will cause permanent ' .
						'loss of all experiments associated to that site.', 'nelioab' )
				);

			echo sprintf( "<p style=\"$this->p_style margin-left:3em;\">%s</p>\n",
				sprintf( __( 'Oops! This site is <b>not</b> registered in your account. %s', 'nelioab' ),
					$explanation )
			);
		}

		private function do_registration_link() {
			$register_js = 'javascript:' .
				'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'register\');' .
				'jQuery(\'#nelioab_registration_form\').submit();';
			return sprintf( '<a href="%s">%s</a>',
				$register_js, __( 'Register this site now!', 'nelioab' ) );
		}

		private function cancel_registration_link() {
			$cancel_js = 'javascript:' .
				'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'cancel\');' .
				'jQuery(\'#nelioab_registration_form\').submit();';
			return sprintf( '<a href="%s">%s</a>',
				$cancel_js, __( 'Cancel Registration', 'nelioab' ) );
		}

	}//NelioABSettingsPage

}



?>
