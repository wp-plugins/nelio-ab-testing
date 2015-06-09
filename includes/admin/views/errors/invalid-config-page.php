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


if ( !class_exists( 'NelioABInvalidConfigPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	class NelioABInvalidConfigPage extends NelioABAdminAjaxPage {


		public function __construct() {
			parent::__construct( '' );
			$this->set_icon( 'icon-nelioab' );
		}


		protected function do_render() {

			echo "<div class='nelio-message'>";

			printf( '<img class="animated flipInY" src="%s" alt="%s" />',
				nelioab_admin_asset_link( '/images/settings-icon.png' ),
				__( 'Information Notice', 'nelioab' )
			);

			$tac_text = '';

			if ( NelioABAccountSettings::can_free_trial_be_started() ) {

				echo '<h2>' . __( 'Welcome!', 'nelioab' ) . '</h2>';
				printf( "<p class=\"nelio-admin-explanation\">%s</p>\n",
						__( 'Thank you very much for installing <strong>Nelio A/B Testing</strong> by <em>Nelio Software</em>. You\'re just one step away from optimizing your WordPress site.', 'nelioab' )
					);
				printf( "<p class=\"nelio-admin-explanation\"><strong>%s</strong></p>\n",
						__( 'Let\'s get started!', 'nelioab' )
					);

				$account_url = admin_url( 'admin.php?page=nelioab-account&nabmode=my-account' );
				$my_account_button = $this->make_button(
					__( 'Use Nelio Account', 'nelioab' ), $account_url, false );
				$free_trial_button = $this->make_button(
					__( 'Start Free Trial', 'nelioab' ), '#', true );

				$tac_text = sprintf(
					__( 'By starting the free trial you agree to be legally bound by these <a href="%s" target="_blank">terms</a>.', 'nelioab' ),
					'https://nelioabtesting.com/terms-and-conditions/'
				);

			} else if ( ! NelioABAccountSettings::is_email_valid() ||
				! NelioABAccountSettings::is_reg_num_valid() ||
				! NelioABAccountSettings::are_terms_and_conditions_accepted() ) {

				echo '<h2>' . __( 'Welcome!', 'nelioab' ) . '</h2>';
				printf( "<p class=\"nelio-admin-explanation\">%s</p>\n",
						__( 'Thank you very much for installing <strong>Nelio A/B Testing</strong> by <em>Nelio Software</em>. You\'re just one step away from optimizing your WordPress site.', 'nelioab' )
					);
				printf( "<p class=\"nelio-admin-explanation\"><strong>%s</strong></p>\n",
						__( 'Let\'s get started!', 'nelioab' )
					);

				$account_url = admin_url( 'admin.php?page=nelioab-account&nabmode=my-account' );
				$my_account_button = $this->make_button(
					__( 'Use Nelio Account', 'nelioab' ), $account_url, true );
				$free_trial_button = '';

			} else {

				echo '<h2>' . __( 'Setup', 'nelioab' ) . '</h2>';
				printf( "<p class=\"nelio-admin-explanation\">%s</p>\n",
						__( 'You\'re just one step away from optimizing WordPress with <strong style="white-space:nowrap;">Nelio A/B Testing</strong> by <em>Nelio Software</em>. Are you ready?', 'nelioab' )
					);
				printf( "<p class=\"nelio-admin-explanation\"><strong>%s</strong></p>\n",
						__( 'Activate this site in your account.', 'nelioab' )
					);

				$account_url = admin_url( 'admin.php?page=nelioab-account&nabmode=my-account' );
				$my_account_button = $this->make_button(
					__( 'Open My Account', 'nelioab' ), $account_url, true );
				$free_trial_button = '';
			}

			printf( "<p id=\"nelio-cta-buttons\" class=\"nelio-admin-explanation\">%s %s</p>\n",
				$my_account_button, $free_trial_button );

			if ( strlen( $tac_text ) > 0 ) {
				echo '<p style="padding-top:3em;font-size:95%;color:gray;">' . $tac_text . '</p>';
			}

			if ( NelioABAccountSettings::can_free_trial_be_started() ) { ?>
				<script type="text/javascript">
				(function($) {
					$('#nelio-cta-buttons .button-primary').click(function() {
						smoothTransitions();
						$.ajax({
							url: ajaxurl,
							data: {
								action: 'nelioab_start_free_trial'
							},
							type: 'post',
							success: function(res) {
								if ( "OK" === res ) {
									window.location = "<?php echo admin_url( 'admin.php?page=nelioab-account&nabmode=free-trial' ); ?>";
								} else {
									window.location.reload();
								}
							},
						});
					});
				})(jQuery);
				</script>
				<?php
			}

			echo '</div>';
		}

	}//NelioABInvalidConfigPage

}

