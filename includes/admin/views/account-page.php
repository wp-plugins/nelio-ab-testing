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


if ( !class_exists( 'NelioABAccountPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABAccountPage extends NelioABAdminAjaxPage {

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

		protected function do_render() { ?>
			<form id="nelioab_account_form" method="post">

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
							'label'     => __( 'I have read and accept the <a href="http://wp-abtesting.com/terms-conditions" target="_blank">Terms and Conditions</a> of this service.', 'nelioab' ),
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
			<?php
				$print_plans = true;
				if ( isset( $this->user_info['status'] ) &&
				   ( $this->user_info['status'] == 1 ||
				      $this->user_info['status'] == 2 ) )
					$print_plans = false;

				if ( $print_plans ) {
					$this->set_message(
						__( 'Haven\'t you subscribed to any of our plans? <b><a href="http://wp-abtesting.com/subscription-plans/" target="_blank">Check them out and choose the one that best fits you</a></b>! All our plans come with a <b>14-day free trial period</b>.', 'nelioab' ) );
				}
			?>
			<h2 style="margin-bottom:0px;padding-bottom:0px;"><?php
				$is_status_defined = false;
				$status_fg_color = '#777777';
				$status_bg_color = '#EFEFEF';
				$status_text     = __( 'UNDEFINED', 'nelioab' );

				if ( isset( $this->user_info['status'] ) ) {
					if ( $this->user_info['status'] == 1 ) {
						$status_fg_color = '#008800';
						$status_bg_color = '#D9FFD9';
						$status_text     = __( 'ACTIVE', 'nelioab' );
						$is_status_defined = true;
					}
					else if ( $this->user_info['status'] == 2 ) {
						$status_fg_color = '#CC0000';
						$status_bg_color = '#FD9D9';
						$status_text     = __( 'NOT ACTIVE', 'nelioab' );
						$is_status_defined = true;
					}
				}

				if ( !$this->tac ) {
					$status_fg_color = '#777777';
					$status_bg_color = '#EFEFEF';
					$status_text     = __( 'UNKNOWN', 'nelioab' );
				}

				$status_title = sprintf(
					'<span style="color:%s;background-color:%s;font-size:0.5em;" class="add-new-h2">%s</span>',
					$status_fg_color, $status_bg_color, $status_text );

				echo __( 'Account Information', 'nelioab' ) . '&nbsp;&nbsp;' . $status_title;

			?></h2>

			<?php
			if ( !$this->tac || !$is_status_defined ) {
				echo '<p>' .
					__( 'Please, fill in all required fields in order to view your account details and use our service.', 'nelioab' ) . '</p>';
			}
			else { ?>

				<?php if ( !$this->user_info['agency'] ) { ?>
					<h3><?php _e( 'Name', 'nelioab' ); ?></h3>
					<p style="margin-top:0em;margin-left:3em;"><?php echo $this->user_info['lastname'] . ', ' . $this->user_info['firstname']; ?></p>
				<?php } ?>

				<h3><?php _e( 'Subscription Details', 'nelioab' ); ?></h3>
				<p style="margin-top:0em;margin-left:3em;"><?php
					if ( !isset( $this->user_info['subscription_url'] ) ) {
						_e( 'No subscription information available.', 'nelioab' );
					}
					else if ( $this->user_info['subscription_url'] == 'BETA' ) {
						_e( 'You are using a <b>beta free-pass</b>.', 'nelioab' );
					}
					else {
						if ( $this->user_info['subscription_plan'] == 0 ) {
							printf( '%s<br /><a href="%s">%s</a>',
								__( 'You are using the <b>Free Trial</b> version of Nelio A/B Testing.', 'nelioab' ),
								$this->user_info['subscription_url'],
								__( 'Subscribe now and continue using our service!', 'nelioab' ) );
						}
						else {
							if ( ! $this->user_info['agency'] ) {
								switch ( $this->user_info['subscription_plan'] ) {
									case NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN:
										_e( 'You are subscribed to our <b>Basic Plan</b>.', 'nelioab' );
										break;
									case NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN:
										_e( 'You are subscribed to our <b>Professional Plan</b>.', 'nelioab' );
										break;
									case NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN:
										_e( 'You are subscribed to our <b>Enterprise Plan</b>.', 'nelioab' );
										break;
								}
								echo ' ';
								printf( '<small><a href="%s">%s</a></small>', $this->user_info['subscription_url'],
									__( 'Check your subscription details.', 'nelioab' ) );

								if ( $this->user_info['subscription_plan'] == NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN ) {
									echo '<br />';
									printf(
										'<a href="%2$s">%1$s</a>',
										__( 'Upgrade to our Professional Plan.', 'nelioab' ),
										'mailto:support@neliosoftware.com?' .
											'subject=Nelio%20A%2FB%20Testing%20-%20Upgrade%20to%20Professional%20Plan&' .
											'body=' . esc_html( 'I\'d like to upgrade to the Professional Plan. I\'m subscribed to Nelio A/B Testing with the following e-mail address: ' . NelioABAccountSettings::get_email() . '.' )
									);
								}

								if ( $this->user_info['subscription_plan'] == NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) {
									echo '<br />';
									printf(
										'<a href="%2$s">%1$s</a>',
										__( 'Upgrade to our Enterprise Plan.', 'nelioab' ),
										'mailto:support@neliosoftware.com?' .
											'subject=Nelio%20A%2FB%20Testing%20-%20Upgrade%20to%20Enterprise%20Plan&' .
											'body=' . esc_html( 'I\'d like to upgrade to the Enterprise Plan. I\'m subscribed to Nelio A/B Testing with the following e-mail address: ' . NelioABAccountSettings::get_email() . '.' )
									);
								}

							}
							else {
								switch ( $this->user_info['subscription_plan'] ) {
									case NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN:
										printf( __( 'You are subscribed to Nelio A/B Testing <b>Basic Plan</b> thanks to %s.', 'nelioab' ),
											$this->user_info['agencyname'] );
										echo '<br />';
										break;
									case NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN:
										printf( __( 'You are subscribed to Nelio A/B Testing <b>Professional Plan</b> thanks to %s.', 'nelioab' ),
											$this->user_info['agencyname'] );
										echo '<br />';
										break;
									case NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN:
										printf( __( 'You are subscribed to Nelio A/B Testing <b>Enterprise Plan</b> thanks to %s.', 'nelioab' ),
											$this->user_info['agencyname'] );
										echo '<br />';
										break;
								}
							}
						}
					}
				?></p>

				<?php
				$post_quota = '';
				if ( !$this->user_info['agency'] ) {
					if ( isset( $this->user_info['total_quota'] ) )
						$post_quota = sprintf( __( '<br />Your current plan permits up to %1$s page views under test per month. If you need more quota, please consider buying additional page views as you need them using the button below, or <a href="%2$s">contact us for an update of your monthly quota</a>.', 'nelioab' ),
							number_format_i18n( $this->user_info['total_quota'] ),
							'mailto:support@neliosoftware.com?subject=Nelio%20A%2FB%20Testing%20-%20Monthly%20Quota%20Update' );
				}
				else {
					$post_quota = sprintf( __( '<br />Your current plan permits up to %1$s page views under test per month. If you need more quota, please contact <a href="%2$s">%3$s</a>.', 'nelioab' ),
							number_format_i18n( $this->user_info['total_quota'] ),
							$this->user_info['agencymail'],
							$this->user_info['agencyname'] );
				}

				if ( isset( $this->user_info['quota'] ) ) { ?>
					<p style="margin-top:0em;margin-left:3em;max-width:600px;">
					<b><?php _e( 'Available Quota:', 'nelioab' ); ?></b>
					<?php
						$the_total_quota = $this->user_info['total_quota'];
						$the_quota       = $this->user_info['quota'];
						$quota_color     = '#00AA00';
						if ( $the_quota < ( $the_total_quota * 0.15 ) )
							$quota_color = '#FF9532';
						if ( $the_quota < ( $the_total_quota * 0.05 ) )
							$quota_color = 'red';
					?>
					<b><?php
						printf( __( '<span style="font-size:120%%;color:%1$s;">%2$s</span> Page Views' ),
							$quota_color, number_format_i18n( $the_quota ) ); ?></b>
					<small>(<a href="http://wp-abtesting.com/faqs/what-is-a-tested-pageview"><?php
						_e( 'Help', 'nelioab' );
					?></a>)</small><?php echo $post_quota; ?></p><?php

				if ( !$this->user_info['agency'] ) { ?>
						<a style="margin-left:3em;margin-bottom:1.5em;" class="button" target="_blank"
							href="http://sites.fastspring.com/nelio/product/nelioextrapageviewsforthepersonalserviceplan"><?php
							_e( 'Buy More', 'nelioab' );
						?></a>

					<?php
					}
				} ?>


				<?php if ( $this->is_email_valid && $this->is_reg_num_valid && $this->tac ) { ?>

					<?php
					if ( $this->error_retrieving_registered_sites ) { ?>
						<h3><?php _e( 'Registered Sites', 'nelioab' ); ?></h3><?php


						?><p style="margin-top:0em;margin-left:3em;"><?php
							echo __( 'There was an error while retrieving the list of all registered sites to this account.', 'nelioab' );

						if ( NelioABAccountSettings::has_a_configured_site() ) {
								echo __( 'Nonetheless, please note this site is registered to your account, which means you can still use all plugin\'s functionalities.', 'nelioab' );
						}

						?></p><?php

					}
					else {
						$print_table = true;
						$sites = array();

						switch( $this->current_site_status ) {
							case NelioABSite::NON_MATCHING_URLS: ?>
								<h3><?php _e( 'Registered Sites', 'nelioab' ); ?></h3><?php
								$this->print_site_non_matching();
								$print_table = false;
								break;

							case NelioABSite::ACTIVE:
								$this->print_table_of_registered_sites();
								break;

							default:
								?> <h3><?php _e( 'This Site is Not Registered', 'nelioab' ); ?></h3> <?php
								$can_register = count( $this->sites ) < $this->max_sites;
								if ( $can_register )
									$this->print_site_should_be_registered();
								else
									$this->print_site_cannot_be_registered();

								$this->print_table_of_registered_sites();
						} ?>

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
		}

		public function print_email_field() { ?>
			<input name="settings_email" type="text" id="settings_email" maxlength="400"
				class="regular-text" value="<?php echo $this->email; ?>"/><?php
		}

		public function print_reg_num_field() { ?>
			<input name="settings_reg_num" type="text" id="settings_reg_num" maxlength="26"
				class="regular-text" value="<?php echo $this->reg_num; ?>"/><?php
			if ( $this->is_email_valid) {
				if ( $this->is_reg_num_valid ) { ?>
					<span style="color:#00AA00; font-weight:bold;"><?php _e( 'OK', 'nelioab' ); ?></span><?php
				}
				else { ?>
					<span style="color:red; font-weight:bold;"><?php _e( 'INVALID', 'nelioab' ); ?></span><?php
				}
			}
		}

		private function print_site_non_matching() {
			echo '<p>Error #001 - Non matching URLs.<br />Please, report this error to Nelio using the Feedback form.</p>';
		}

		private function print_site_should_be_registered() {
			$style = 'style="' . $this->p_style . 'margin-left:3em;font-size:120%%;line-height:1.3em;"';
			echo sprintf( "<p $style>%s <strong><a href=\"%s\">%s</a></strong></p>\n",
				__( 'This site is not yet registered to your account.', 'nelioab' ),
				$this->do_registration_link(),
				__( 'Register it now!', 'nelioab' ) );
		}

		private function print_site_cannot_be_registered() {
			$style = 'style="' . $this->p_style . 'margin-left:3em;font-size:120%%;line-height:1.3em;"';
			echo sprintf( "<p $style>%s</p><p $style>%s</p>\n",
				__( 'It looks like this site is not connected to you account. In order to use our service in this site, first you have to register it. Unfortunately, you already reached the maximum number of sites allowed by your current subscription plan (see the table below).', 'nelioab' ),
				sprintf( __( 'Please, <a href="%s"><b>upgrade your <i>Nelio A/B Testing</i> subscription</b></a> so that you can register and manage more sites, or <b>access one of the other sites to cancel its subscription</b> and try again. Keep in mind that canceling the registration of a site may cause permanent loss of all experiments associated to that site.', 'nelioab' ),
					'http://wp-abtesting.com/inquiry-subscription-plans' )
			);
		}

		private function do_registration_link() {
			$register_js = 'javascript:' .
				'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'register\');' .
				'jQuery(\'#nelioab_registration_form\').submit();';
			return $register_js;
		}

		private function print_table_of_registered_sites() {
			$sites = array();

			$other_sites = array();
			$this_url    = get_option( 'siteurl' );
			$this_id     = NelioABAccountSettings::get_site_id();
			$reg_name    = false;
			foreach( $this->sites as $site ) {
				if ( $site->get_id() != $this_id )
					array_push( $other_sites, $site );
				else
					$reg_name = $site->get_url();
			}

			if( $this->current_site_status == NelioABSite::ACTIVE ) {
				$live = ( false === $reg_name ) ? 'live' : 'staging';

				array_push( $sites, array(
						'this_site' => true,
						'name'      => $this_url,
						'reg_name'  => $reg_name,
						'live'      => $live,
					) );
			}

			foreach ( $other_sites as $site )
				array_push( $sites, array(
						'this_site' => false,
						'name'      => $site->get_url(),
						'goto_link' => $site->get_url()
					) );

			for ( $i = count( $sites ); $i < $this->max_sites; ++$i )
				array_push( $sites, array(
						'this_site' => false,
						'name'      => __( 'Empty Slot', 'nelioab' )
					) );

			echo '<div id="registered-sites-table" style="margin-left:2em;">';
			$aux = new NelioABRegisteredSitesTable( $sites );
			$aux->prepare_items();
			$aux->display();
			echo '</div>';
		}

	}//NelioABAccountPage


	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABRegisteredSitesTable extends NelioABAdminTable {

		function __construct( $items ){
   	   parent::__construct( array(
				'singular'  => __( 'registered site', 'nelioab' ),
				'plural'    => __( 'registered sites', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->set_items( $items );
		}

		public function get_columns() {
			return array(
				'name' => __( 'Registered Sites', 'nelioab' ),
			);
		}

		public function column_name( $site ) {
			if ( $site['this_site'] )
				return $this->make_this_site( $site );
			else
				return $this->make_other_site( $site );
		}

		private function make_this_site( $site ) {
			$name = $this->beautify_url( $site['name'] );
			$name = sprintf( $name, '#000000', '#909090' );
			$name = '<span class="row-title"%s><strong style="font-weight:bold;">' . $name . '</strong>';

			$is_live = true;
			$reg_title = '';
			if ( $site['reg_name'] !== $site['name'] ) {
				$is_live = false;
				$name .= '*';
				$reg_title = $site['reg_name'];
				$reg_title = sprintf( __( 'In your account, the site is actually registered as «%s»', 'nelioab' ), $reg_title );
				$reg_title = ' title="' . esc_html( $reg_title ) . '"';
			}
			$name = sprintf( $name, $reg_title );

			$name .= ' <strong style="font-variant:small-caps;">(' . __( 'this site', 'nelioab' ) . ')</strong> ';

			$name .= '</span>';

			if ( $is_live ) {
				$link = 'javascript:' .
					'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'deregister\');' .
					'jQuery(\'#nelioab_registration_form\').submit();';
				$actions = $this->row_actions( array(
						'delete' => '<a href="' . $link . '">' . __( 'Cancel Registration', 'nelioab' ) . '</a>'
					) );
			}
			else {
				$link = 'javascript:' .
					'jQuery(\'#nelioab_registration_action\').attr(\'value\', \'unlink\');' .
					'jQuery(\'#nelioab_registration_form\').submit();';
				$actions = $this->row_actions( array(
						'delete' => '<a href="' . $link . '">' . __( 'Unlink Site', 'nelioab' ) . '</a>'
					) );
			}

			$careful = '';
			if ( !$is_live ) {
				$careful = sprintf(
						'<br><small style="font-weight:normal;"%s>%s</small> ',
						$reg_title,
						__( '*The site was registered using a different URL', 'nelioab' )
					);
			}


			return $name . $careful . $actions;
		}

		private function make_other_site( $site ) {
			if ( isset( $site['goto_link'] ) ) {
				$name = $this->beautify_url( $site['name'] );
				$name = sprintf( $name, '#404040', '#AAAAAA' );
				$name = '<span class="row-title" style="font-weight:normal;">' . $name . '</span>';
				$actions = $this->row_actions( array(
						'edit-content' => '<a href="' . $site['goto_link'] . '">Go to this site</a>'
					) );
			}
			else {
				$style   = 'color:#AAAAAA;font-weight:normal;font-style:italic;';
				$name    = '<span class="row-title" style="' . $style . '">' . $site['name'] . '</span>';
				$actions = $this->row_actions( array( 'none' => '&nbsp;' ) );
			}

			return $name . $actions;
		}

		private function beautify_url( $url ) {
			$result = '<span style="color:%2$s">';
			if ( strpos( $url, 'http://' ) === 0 ) {
				$result .= 'http://</span>';
				$url = substr( $url, 7 );
			}
			else if ( strpos( $url, 'https://' ) === 0 ) {
				$result .= 'https://</span>';
				$url = substr( $url, 8 );
			}
			$result .= '<span style="color:%1$s">';

			$first_slash = strpos( $url, '/' );
			if ( $first_slash ) {
				$result .= substr( $url, 0, $first_slash );
				$result .= '</span><span style="color:%2$s">';
				$result .= substr( $url, $first_slash );
				$result .= '</span>';
			}
			else {
				$result .= substr( $url, 0, strlen( $url ) );
				$result .= '</span><span style="color:%2$s"></span>';
			}

			return $result;
		}

		public function print_column_headers( $with_id = true ) {
			if ( $with_id )
				parent::print_column_headers();
		}

	}//NelioABRegisteredSitesTable
}



?>
