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

if ( !class_exists( 'NelioABFreeTrialPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );

	/**
	 * PHPDOC
	 *
	 * @since 4.1.3
	 */
	class NelioABFreeTrialPage extends NelioABAdminPage {

		/**
		 * Creates a new instance of this class.
		 *
		 * @return NelioABFreeTrialPage the new instance of this class.
		 *
		 * @since 4.1.3
		 */
		public function __construct() {
			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			$title = __( 'Nelio A/B Testing &mdash; Free Trial', 'nelioab' );
			$title .= sprintf(
				' <span class="nelio-ftcode" style="color:%1$s;border-color:%1$s;">%2$s%3$s</span>',
				$colorscheme['primary'],
				__( 'Code:', 'nelioab' ),
				NelioABAccountSettings::get_nelioab_option( 'free_trial_code' )
			);
			parent::__construct( $title );
		}


		// @Implements
		public function do_render() {

			$exp = __( 'Complete the following steps and get more quota for your A/B testing campaigns!', 'nelioab' );

			echo "<div class='nelio-message'>";
			printf( '<img class="animated flipInY" src="%s" alt="%s" />',
				nelioab_admin_asset_link( '/images/message-icon.png' ),
				__( 'Information Notice', 'nelioab' )
			);
			echo "<p class='nelio-admin-explanation'>$exp</p>";
			echo '</div>';

			echo '<div id="free-trial-actions">';


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'basic-info-check' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else if ( NelioABAccountSettings::is_promo_completed( 'basic-info' ) ) {
				array_push( $classes, 'pending-confirmation' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-basic-info',
				$this->get_action_heading( 'basic-info' ),
				array( &$this, 'print_email_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'goals' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-goals',
				$this->get_action_heading( 'goals' ),
				array( &$this, 'print_life_goals_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'site-info' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-site-info',
				$this->get_action_heading( 'site-info' ),
				array( &$this, 'print_site_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'tweet' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-tweet',
				$this->get_action_heading( 'tweet' ),
				array( &$this, 'print_twitter_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'connect' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-connect',
				$this->get_action_heading( 'connect' ),
				array( &$this, 'print_facebook_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			if ( NelioABAccountSettings::is_promo_completed( 'recommend' ) ) {
				array_push( $classes, 'action-completed', 'no-shadow' );
			} else {
				array_push( $classes, 'action-disabled' );
			}
			$this->print_beautiful_box(
				'nelio-ft-recommend',
				$this->get_action_heading( 'recommend' ),
				array( &$this, 'print_friends_content' ),
				$classes );


			// -------------------------------------------------------------------------
			$classes = array( 'free-trial-action', 'no-link' );
			$this->print_beautiful_box(
				'nelio-ft-subscribe',
				$this->get_action_heading( 'subscribe' ),
				array( &$this, 'print_subscribe_content' ),
				$classes );

			echo '</div>';

			echo '<script type="text/javascript" src="' .
				nelioab_admin_asset_link( '/js/free-trial.min.js' ) .
				'"></script>';

			$exp = __( 'Or <a href="%s">subscribe to one of our plans</a> and get the most out of Nelio!', 'nelioab' );

		}


		/**
		 * Prints the content of the E-Mail action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_email_content() {
			$name = NelioABAccountSettings::get_nelioab_option( 'free_trial_name', '' );
			$mail = NelioABAccountSettings::get_nelioab_option( 'free_trial_mail', '' );

			printf( '<p><input id="your-name" type="text" style="width:100%%;" placeholder="%s" value="%s" /></p>',
				__( 'Your Name', 'nelioab' ), $name
			);
			printf( '<p><input id="your-email" type="text" style="width:100%%;" placeholder="%s" value="%s" /></p>',
				__( 'Your e-Mail', 'nelioab' ), $mail
			);

			?>
			<div id="email-confirmation-dialog" style="display:none;">
				<p class="title" style="display:none;"><?php
					_e( 'E-Mail Confirmation', 'nelioab' );
				?></p>
				<p class="message"><?php
					_e( 'Thanks for sharing your basic information with us, %s! <strong>Check your inbox and confirm your e-mail address</strong> to get the additional quota and unlock the other actions.', 'nelioab' );
				?></p>
				<p class="button" style="display:none;"><?php
					_e( 'OK', 'nelioab' );
				?></p>
			</div>
			<?php

			$this->print_cta(
				__( 'Send Confirmation E-Mail', 'nelioab' ),
				'basic-info'
			);

		}


		/**
		 * Prints the content of the Twitter action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_twitter_content() {
			echo '<p>';
			_e( 'Do you have a Twitter account? Then tweet about us and get 200 extra page views for your free trial!', 'nelioab' );
			echo '</p>';

			if ( !NelioABAccountSettings::is_promo_completed( 'tweet' ) ) { ?>
				<script>
				window.twttr = (function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0],
					t = window.twttr || {};
					if (d.getElementById(id)) return t;
					js = d.createElement(s);
					js.id = id;
					js.src = "https://platform.twitter.com/widgets.js";
					fjs.parentNode.insertBefore(js, fjs);
					t._e = [];
					t.ready = function(f) {
						t._e.push(f);
					};
					return t;
				}(document, "script", "twitter-wjs"));</script>
			<?php
			}

			echo '<p class="cta" data-action="tweet">';
			$nelio_url = 'https://nelioabtesting.com/?social=ftr';
			$text = __( 'I\'m trying out Nelio A/B Testing for #WordPress by @NelioSoft and it rocks! Check it out at', 'nelioab' );
			$link = sprintf(
				'https://twitter.com/intent/tweet?url=%s&text=%s',
				urlencode( $nelio_url ), urlencode( $text )
			);
			echo $this->make_button( __( 'Tweet', 'nelioab' ), $link, false );
			echo '</p>';

		}


		/**
		 * Prints the content of the Site action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_site_content() { ?>
			<p><select style="width:100%;" id="business-type-selector">
				<option value="unknown" disabled="disabled" selected="selected"><?php
					_e( 'How would you describe your site?', 'nelioab' );
				?></option>
				<?php
				$types = array(
					'publisher' => __( 'Publishing Platform', 'nelioab' ),
					'personal'  => __( 'Personal Blog', 'nelioab' ),
					'company'   => __( 'Business/Corporate Website', 'nelioab' ),
					'ecommerce' => __( 'E-Commerce', 'nelioab' ),
				);
				asort( $types );
				foreach ( $types as $value => $name )
					printf( '<option value="%s">%s</option>', $value, $name );
				?>
			</select></p>
			<p><select style="width:100%;" id="business-sector-selector">
				<option value="unknown" disabled="disabled" selected="selected"><?php
				_e( 'What is it focused on?', 'nelioab' );
				?></option>
				<?php
				$sectors = array(
					'software-services'  => __( 'Computer Software & Services', 'nelioab' ),
					'education'          => __( 'Education', 'nelioab' ),
					'financial-services' => __( 'Financial Services', 'nelioab' ),
					'food-and-beverage'  => __( 'Food & Beverage', 'nelioab' ),
					'health'             => __( 'Health Services', 'nelioab' ),
					'leisure'            => __( 'Leisure', 'nelioab' ),
					'media'              => __( 'Media (Adv & Mkt Ag & Publishing)', 'nelioab' ),
					'real-estate'        => __( 'Real Estate', 'nelioab' ),
					'retail'             => __( 'Retail', 'nelioab' ),
				);
				asort( $sectors );
				$sectors['others'] = __( 'Others', 'nelioab' );
				foreach ( $sectors as $value => $name )
					printf( '<option value="%s">%s</option>', $value, $name );
				?>
			</select></p><?php

			$this->print_cta(
				__( 'Submit', 'nelioab' ),
				'site-info'
			);

		}


		/**
		 * Prints the content of the Facebook action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_facebook_content() {
			printf( '<p>%s</p>',
				__( 'If you have a Facebook account, then help us reach more people and like our Facebook profile.', 'nelioab' )
			);

			if ( ! NelioABAccountSettings::is_promo_completed( 'connect' ) ) { ?>
				<script>
        jQuery.getScript('//connect.facebook.net/en_US/sdk.js', function() {
          FB._https = false;
          FB.init({
            appId: '843163782443938',
            version: 'v2.3',
            xfbml: true,
            cookie: false
          });
					jQuery( document ).ready(function() {
						FB.Event.subscribe( 'edge.create', NelioABFreeTrial.connect );
					});
				});</script>
			<?php
			}

			$this->print_cta(
				__( 'Like', 'nelioab' ),
				'connect'
			);

		}


		/**
		 * Prints the content of the Friends action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_friends_content() {
			printf( '<p>%s</p>',
				__( 'If you like Nelio A/B Testing, then recommend us to some of your friends or colleagues! Please, enter a list of comma-separated e-mails:', 'nelioab' )
			);

			printf( '<p><input id="list-of-emails" type="text" style="width:100%%;" placeholder="%s" /></p>',
				__( 'List of e-mails', 'nelioab' )
			);

			$this->print_cta(
				__( 'Submit', 'nelioab' ),
				'recommend'
			);

		}


		/**
		 * Prints the content of the Subscribe action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_subscribe_content() {
			echo '<p>';
			_e( 'Have you exhausted your Free Trial Quota? Do you want to get the most out of Nelio A/B Testing? Then subscribe to one of our plans and revamp your WordPress site with our help!', 'nelioab' );
			echo '</p>';

			echo '<p class="cta">';
			$link = 'https://nelioabtesting.com/subscription-plans/';
			echo str_replace( '<a ', '<a target="_blank" ',
				$this->make_button( __( 'Subscribe', 'nelioab' ), $link, false ) );
			echo ' ';
			$link = admin_url( 'admin.php?page=nelioab-account&nabmode=my-account' );
			echo $this->make_button( __( 'Use My Nelio Account', 'nelioab' ), $link, true );
			echo '</p>';

		}

		/**
		 * Prints the content of the Life Goals action box.
		 *
		 * @return void
		 *
		 * @since 4.1.3
		 */
		public function print_life_goals_content() {
			$name = '';
			$mail = '';

			printf( '<p><input id="the-goal" type="text" style="width:100%%;" placeholder="%s" value="%s" /></p>',
				__( 'The goal of my website is...', 'nelioab' ), $name
			);
			printf( '<p><input id="success" type="text" style="width:100%%;" placeholder="%s" value="%s" /></p>',
				__( 'Success to me means...', 'nelioab' ), $mail
			);

			$this->print_cta(
				__( 'Submit', 'nelioab' ),
				'goals'
			);

		}


		/**
		 * Returns the heading of a certain box type.
		 *
		 * This heading contains the title of the box and a beautiful icon.
		 *
		 * @param string $type the type of the heading we want to create.
		 *
		 * @return string the heading of a certain box type.
		 *
		 * @since 4.1.3
		 */
		protected function get_action_heading( $type ) {

			switch ( $type ) {
				case 'basic-info':
					$icon = nelioab_admin_asset_link( '/images/freetrial-mail.png' );
					$aux = __( 'Your Basic Information (%s)', 'nelioab' );
					$alt = __( 'You and Your E-Mail', 'nelioab' );
					$num = 100;
					break;
				case 'tweet':
					$icon = nelioab_admin_asset_link( '/images/freetrial-twitter.png' );
					$aux = __( 'Tweet About Us (%s)', 'nelioab' );
					$alt = __( 'Twitter', 'nelioab' );
					$num = 200;
					break;
				case 'site-info':
					$icon = nelioab_admin_asset_link( '/images/freetrial-site.png' );
					$aux = __( 'Information About Your Site (%s)', 'nelioab' );
					$alt = __( 'Tiny form about your site', 'nelioab' );
					$num = 100;
					break;
				case 'connect':
					$icon = nelioab_admin_asset_link( '/images/freetrial-facebook.png' );
					$aux = __( 'Like Our Facebook Profile (%s)', 'nelioab' );
					$alt = __( 'Facebook', 'nelioab' );
					$num = 200;
					break;
				case 'recommend':
					$icon = nelioab_admin_asset_link( '/images/freetrial-friends.png' );
					$aux = __( 'Recommend Us to Your Friends (%s)', 'nelioab' );
					$alt = __( 'Friends', 'nelioab' );
					$num = 100;
					break;
				case 'goals':
					$icon = nelioab_admin_asset_link( '/images/freetrial-life-goals.png' );
					$aux = __( 'The Goals You Pursue (%s)', 'nelioab' );
					$alt = __( 'Goals', 'nelioab' );
					$num = 200;
					break;
				case 'subscribe':
					$icon = nelioab_admin_asset_link( '/images/freetrial-subscribe.png' );
					$aux = __( 'Subscribe to Nelio A/B Testing (%s)', 'nelioab' );
					$alt = __( 'Subscribe', 'nelioab' );
					$num = 5000;
					break;
			}

			$num = '+' . number_format_i18n( $num );
			$title = sprintf( $aux, $num );
			$completed_title = sprintf( $aux, __( 'Done!', 'nelioab' ) );
			$completed_icon = nelioab_admin_asset_link( '/images/action-completed.png' );

			// Workaround for BASIC INFO (which might use Confirmation...)
			if ( 'basic-info' === $type ) {
				if ( NelioABAccountSettings::is_promo_completed( 'basic-info' ) ) {
					$icon = nelioab_admin_asset_link( '/images/action-pending.png' );
				} else {
					$completed_icon = nelioab_admin_asset_link( '/images/action-pending.png' );
				}
				$type = 'basic-info-check';
				$completed_title = sprintf( $aux, __( 'Awaiting Confirmation...', 'nelioab' ) );
			}
			// End of the workaround

			if ( NelioABAccountSettings::is_promo_completed( $type ) ) {
				$animation = '';
			} else {
				$animation = 'animated flipInY';
			}

			$html = <<<HTML
			<span class="nelio-freetrial-heading regular">
				<img width="32" height="32" src="$icon" alt="$alt"/>
				<span>$title</span>
			</span>
			<span class="nelio-freetrial-heading completed">
				<img class="$animation" width="32" height="32" src="$completed_icon" alt="$alt"/>
				<span>$completed_title</span>
			</span>
HTML;
			return $html;
		}


		// @Override
		protected function print_dialog_content() { ?>
			<div style="width:100%; overflow:hidden;">
				<div class="fb-like"
					data-href="https://www.facebook.com/NelioSoftware"
					data-layout="standard"
					data-action="like"
					data-show-faces="true"
					data-share="false"></div>
			</div><?php
		}

		private function print_cta( $name, $action ) {
			echo '<p class="cta" data-action="' . $action . '">';
			echo str_replace(
				'class="',
				'class="disabled ',
				$this->make_button( $name, '#', false ) );
			echo '</p>';
		}

	}

}

