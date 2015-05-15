<?php
/**
 * Copyright 2015 Nelio Software S.L.
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


if( !class_exists( 'NelioABAccountSettings' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	/**
	 * Account settings contain information about Nelio's customers and their sites.
	 *
	 * @package \NelioABTesting\Models\Settings
	 * @since 2.1.0
	 */
	class NelioABAccountSettings {

		/**
		 * Constant for identifying Beta subscriptions.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const BETA_SUBSCRIPTION_PLAN = 0;


		/**
		 * Constant for identifying a Basic Subscription.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const BASIC_SUBSCRIPTION_PLAN = 1;


		/**
		 * Constant for identifying a Professional Subscription.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const PROFESSIONAL_SUBSCRIPTION_PLAN = 2;


		/**
		 * Constant for identifying a Enterprise Subscription.
		 *
		 * @since 3.2.0
		 * @var int
		 */
		const ENTERPRISE_SUBSCRIPTION_PLAN = 3;



		/**
		 * Nelio A/B Account Settings and Details array.
		 *
		 * @since 3.4.0
		 * @var boolean|array
		 */
		private static $settings = false;


		/**
		 * Returns the account settings and details.
		 *
		 * @return array the account settings and details.
		 *
		 * @since 3.4.0
		 * @var int
		 */
		private static function settings() {
			if ( !self::$settings )
				self::$settings = get_option( 'nelioab_account_settings', array() );
			return self::$settings;
		}


		/**
		 * Returns the value of the account option named $name.
		 *
		 * @param string $name    The name of the option whose value we want to retrieve.
		 * @param mixed  $default The default value of the option named `$name`.
		 *
		 * @return mixed The value of the option named `$name`.
		 *
		 * @since 3.4.0
		 */
		public static function get_nelioab_option( $name, $default = false ) {
			self::$settings = self::settings();
			if ( !isset( self::$settings[$name] ) ) {
				self::$settings[$name] = get_option( "nelioab_$name", $default );
				update_option( 'nelioab_account_settings', self::$settings );
				delete_option( "nelioab_$name" );
			}
			return self::$settings[$name];
		}


		/**
		 * Updates the account option named $name with the given $value.
		 *
		 * @param string $name  The name of the option whose value we want to update.
		 * @param mixed  $value The new value of the option.
		 *
		 * @since 3.4.0
		 */
		public static function update_nelioab_option( $name, $value ) {
			self::$settings = self::settings();
			self::$settings[$name] = $value;
			update_option( 'nelioab_account_settings', self::$settings );
		}


		/**
		 * Returns the subscription plan of this user.
		 *
		 * @return int the subscription plan of this user.
		 *
		 * @since 2.1.0
		 */
		public static function get_subscription_plan() {
			try {
				NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				// Nothing to catch here
			}

			return self::get_nelioab_option( 'subscription_plan',
				NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN );
		}


		/**
		 * Requests AppEngine to validate the given email and product registration number.
		 *
		 * If the validation was successful, several account options are saved.
		 *
		 * @param string $email   An e-mail address.
		 * @param string $reg_num A Nelio A/B Testing's product registration number.
		 *
		 * @return void
		 *
		 * @throws Exception `INVALID_MAIL`
		 *                   If the e-mail is not registered to Nelio's servers, an
		 *                   `INVALID_MAIL` exception is thrown.
		 * @throws Exception `INVALID_PRODUCT_REG_NUM`
		 *                   If the product registration number is not valid, an
		 *                   `INVALID_PRODUCT_REG_NUM` exception is thrown.
		 *
		 * @since 2.1.0
		 */
		public static function validate_email_and_reg_num( $email, $reg_num ) {
			self::update_nelioab_option( 'email', $email );
			self::update_nelioab_option( 'reg_num', $reg_num );

			$json_data = null;
			try {
				$params = array(
					'body' => array( 'mail' => $email, 'registrationNumber' => $reg_num )
				);

				if ( $email == NULL || strlen( $email ) == 0 ) {
					$err = NelioABErrCodes::INVALID_MAIL;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}

				if ( $reg_num == NULL || strlen( $reg_num ) == 0 ) {
					$err = NelioABErrCodes::INVALID_PRODUCT_REG_NUM;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
				}

				$json_data = NelioABBackend::remote_post_raw(
					NELIOAB_BACKEND_URL . '/customer/validate',
					$params, true );

				$json_data = json_decode( $json_data['body'] );
			}
			catch ( Exception $e ) {
				$error = $e->getCode();

				if ( $error == NelioABErrCodes::INVALID_MAIL )
					self::update_nelioab_option( 'is_email_valid', false );

				if ( $error == NelioABErrCodes::INVALID_PRODUCT_REG_NUM )
					self::update_nelioab_option( 'is_reg_num_valid', false );

				throw $e;
			}

			self::update_nelioab_option( 'is_email_valid', true );
			self::update_nelioab_option( 'is_reg_num_valid', true );
			self::update_nelioab_option( 'customer_id', $json_data->key->id );

			// Store the current subscription plan

			// Check if the current site is already registered for this account
			$registered = false;
			if ( NelioABAccountSettings::has_a_configured_site() ) {
				/** @var NelioABSitesInfo $sites_info */
				$sites_info = NelioABAccountSettings::get_registered_sites_information();
				$this_id = NelioABAccountSettings::get_site_id();
				$sites = $sites_info->get_registered_sites();
				foreach ( $sites as $s ) {
					/** @var NelioABSite $s **/
					if ( $s->get_id() == $this_id )
						$registered = true;
				}
			}
			self::update_nelioab_option( 'has_a_configured_site', $registered );
		}


		/**
		 * Returns the ID of this customer, as stored in AppEngine.
		 *
		 * @return string the ID of this customer, as stored in AppEngine.
		 *
		 * @since 2.1.0
		 */
		public static function get_customer_id() {
			return self::get_nelioab_option( 'customer_id', '' );
		}


		/**
		 * Returns the e-mail address that was used to login to Nelio.
		 *
		 * @return string the e-mail address that was used to login to Nelio.
		 *
		 * @since 2.1.0
		 */
		public static function get_email() {
			return self::get_nelioab_option( 'email', '' );
		}


		/**
		 * Returns whether the e-mail address used to login to Nelio is the e-mail address of an existing subscriber.
		 *
		 * @return boolean whether the e-mail address used to login to Nelio is the e-mail address of an existing subscriber.
		 *
		 * @since 2.1.0
		 */
		public static function is_email_valid() {
			return self::get_nelioab_option( 'is_email_valid', false );
		}


		/**
		 * Returns the registration number used to login to Nelio.
		 *
		 * @return string the registration number used to login to Nelio.
		 *
		 * @since 2.1.0
		 */
		public static function get_reg_num() {
			return self::get_nelioab_option( 'reg_num', '' );
		}


		/**
		 * Returns whether the product registration number used to login to Nelio is valid.
		 *
		 * @return boolean whether the product registration number used to login to Nelio is valid.
		 *
		 * @since 2.1.0
		 */
		public static function is_reg_num_valid() {
			return self::get_nelioab_option( 'is_reg_num_valid', false );
		}


		/**
		 * Returns whether the current site is registered to Nelio.
		 *
		 * @return boolean whether the current site is registered to Nelio.
		 *
		 * @since 2.1.0
		 */
		public static function has_a_configured_site() {
			return self::get_nelioab_option( 'has_a_configured_site', false );
		}


		/**
		 * Returns the ID of the current site, as stored in AppEngine.
		 *
		 * @return string the ID of the current site, as stored in AppEngine.
		 *
		 * @since 2.1.0
		 */
		public static function get_site_id() {
			return self::get_nelioab_option( 'site_id', '' );
		}


		/**
		 * Overwrites the AppEngine ID of the current site to $site_id.
		 *
		 * @param string $site_id the new AppEngine ID of the current site.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function set_site_id( $site_id ) {
			self::update_nelioab_option( 'site_id', $site_id );
		}


		/**
		 * Sets the Terms And Conditions checkbox as (un)marked.
		 *
		 * @param boolean $accepted whether the Terms and Conditions checkbox is marked or not.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function check_terms_and_conditions( $accepted ) {
			self::update_nelioab_option( 'are_tac_accepted', $accepted );
		}


		/**
		 * Returns whether the Terms and Conditions are accepted or not.
		 *
		 * @return boolean whether the Terms and Conditions are accepted or not.
		 *
		 * @since 2.1.0
		 */
		public static function are_terms_and_conditions_accepted() {
			return self::get_nelioab_option( 'are_tac_accepted', false );
		}


		/**
		 * Returns true if everything is OK. If it isn't, an exception is thrown.
		 *
		 * @return boolean true if everything is OK. Otherwise, an exception is thrown.
		 *
		 * @throws Exception `INVALID_MAIL`
		 * @throws Exception `INVALID_PRODUCT_REG_NUM`
		 * @throws Exception `NON_ACCEPTED_TAC`
		 * @throws Exception `BACKEND_NO_SITE_CONFIGURED`
		 * @throws Exception `DEACTIVATED_USER`
		 *
		 * @since 2.1.0
		 */
		public static function check_user_settings() {

			if ( !NelioABAccountSettings::is_email_valid() ) {
				$err = NelioABErrCodes::INVALID_MAIL;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABAccountSettings::is_reg_num_valid() ) {
				$err = NelioABErrCodes::INVALID_PRODUCT_REG_NUM;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABAccountSettings::are_terms_and_conditions_accepted() ) {
				$err = NelioABErrCodes::NON_ACCEPTED_TAC;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABAccountSettings::has_a_configured_site() ) {
				$err = NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			NelioABAccountSettings::check_account_status();

			if ( !NelioABAccountSettings::is_account_active() ) {
				$err = NelioABErrCodes::DEACTIVATED_USER;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			return true;
		}


		/**
		 * Synchronizes with AppEngine the latest version installed of Nelio A/B Testing.
		 *
		 * If the synchornization was successful, the `last_synced_version` option
		 * is updated with the value of the latest version synchronized. This
		 * option is used by our plugin to prevent resynching already synched
		 * values.
		 *
		 * @return void
		 *
		 * @since 3.4.0
		 */
		public static function sync_plugin_version() {
			$last_synced_version = self::get_nelioab_option( 'last_synced_version', '3.3.7' );
			try {
				if ( NELIOAB_PLUGIN_VERSION !== $last_synced_version && self::check_user_settings() ) {
					try {
						$url  = sprintf( NELIOAB_BACKEND_URL . '/site/%s/version',
							NelioABAccountSettings::get_site_id() );
						try {
							$php_version = ' (PHP: ' . preg_replace( '/-.*$/', '', phpversion() ) . ')';
						}
						catch ( Exception $e ) {
							$php_version = '';
						}
						$version = NELIOAB_PLUGIN_VERSION . $php_version;
						$body = array( 'version' => $version );
						NelioABBackend::remote_post( $url, $body );
						self::update_nelioab_option( 'last_synced_version', NELIOAB_PLUGIN_VERSION );
					} catch ( Exception $e ) {}
				}
			} catch ( Exception $e ) {}
		}


		/**
		 * Checks the status of the account (request to AppEngine).
		 *
		 * The status check involves:
		 * * Checking whether the site is active or not.
		 * * Updating the subscription plan our customer is subscribed to (if
		 * required).
		 * * Writing down when was the last time the check was performed (so
		 * that we do not keep
		 *
		 * @param string $mode whether the check has to be performed right now or only if a certain amount of time has passed by.
		 *                     The accepted values are:
		 *                     * `if-required`: a certain amount of time has passed by.
		 *                     * `now`: the update has to be performed right now
		 *
		 * @return void
		 *
		 * @since 3.0.10
		 */
		public static function check_account_status( $mode = 'if-required' ) {
			$the_past   = mktime( 0, 0, 0, 1, 1, 2000 );
			$last_check = self::get_nelioab_option( 'last_check_user_settings', $the_past );
			$now        = time();
			$offset     = 1800; // sec (== 30min)
			if ( ( $last_check + $offset ) < $now || 'now' === $mode ) {
				try {
					$url  = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check', NelioABAccountSettings::get_customer_id() );
					$json = NelioABBackend::remote_get( $url, true );
					$json = json_decode( $json['body'] );
					self::update_nelioab_option( 'is_account_active', true );
					self::update_nelioab_option( 'subscription_plan', $json->subscriptionPlan );
					self::update_nelioab_option( 'last_check_user_settings', $now );
				}
				catch ( Exception $e ) {
					if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER ) {
						self::update_nelioab_option( 'is_account_active', false );
						self::update_nelioab_option( 'last_check_user_settings', $now );
					}
					else {
						self::update_nelioab_option( 'is_account_active', false );
						self::update_nelioab_option( 'last_check_user_settings', $now - 1800 + 60);
					}
				}
			}
		}


		/**
		 * Returns whether the account is active or not.
		 *
		 * @return boolean whether the account is active or not.
		 *
		 * @since 2.1.0
		 */
		public static function is_account_active() {
			return self::get_nelioab_option( 'is_account_active', false );
		}


		/**
		 * Returns information about all the sites that are registered to this account.
		 *
		 * @return NelioABSitesInfo information about all the sites that are registered to this account.
		 *
		 * @see NelioABSite
		 * @see NelioABSitesInfo
		 *
		 * @since 2.1.0
		 */
		public static function get_registered_sites_information() {
			$res = new NelioABSitesInfo();
			$customer_id = NelioABAccountSettings::get_customer_id();
			if ( strlen( $customer_id ) <= 0 )
				return $res;

			// Set max number of sites
			$url = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check', $customer_id );
			$json_data = NelioABBackend::remote_get( $url, true );
			$json_data = json_decode( $json_data['body'] );
			$res->set_max_sites( $json_data->allowedSites );

			// Retrieve information about each site
			$json_data = NelioABBackend::remote_get( sprintf(
				NELIOAB_BACKEND_URL . '/customer/%s/site',
				$customer_id
			), true );

			$json_data = json_decode( $json_data['body'] );

			if ( isset( $json_data->items ) ) {
				foreach ( $json_data->items as $item ) {
					$id     = $item->key->id;
					$url    = $item->url;
					$status = $item->status;
					$res->add_registered_site( new NelioABSite( $id, $url, $status ) );
				}
			}

			return $res;
		}


		/**
		 * Registers this site to the currently-configured account.
		 *
		 * @param string $type   The type of website.
		 * @param string $sector The sector in which this website operates.
		 *
		 * @return void
		 *
		 * @throws Exception an exception triggered by AppEngine.
		 *
		 * @since 2.1.0
		 */
		public static function register_this_site( $type, $sector ) {

			try {
				$params = array(
					'url'    => get_option( 'siteurl' ),
					'type'   => $type,
					'sector' => $sector,
				);
				$json_data = NelioABBackend::remote_post( sprintf(
					NELIOAB_BACKEND_URL . '/customer/%s/site/activate',
					NelioABAccountSettings::get_customer_id()
				), $params, true );

				$json_data = json_decode( $json_data['body'] );
				self::update_nelioab_option( 'has_a_configured_site', true );
				NelioABAccountSettings::set_site_id( $json_data->key->id );
			}
			catch ( Exception $e ) {
				self::update_nelioab_option( 'has_a_configured_site', false );
				throw $e;
			}

		}


		/**
		 * This operation overwrites registration information.
		 *
		 * Sometimes, it might happen that the current site is already registered
		 * to our customer's account. This happens when our customer has a
		 * registered site whose URL corresponds to this site URL. If that's the
		 * case, then we simply use the already-registered site's ID and assume
		 * this site is the site that we registered some time ago.
		 *
		 * @param string      $registered Whether the current site is registered or not.
		 *                                If `registered`, the site is registered
		 *                                (and the `$id` will be specified).
		 *                                Otherwise, `not-registered` should be
		 *                                used.
		 * @param boolean|int $id         The ID of the already-registered site.
		 *
		 * @return void
		 *
		 * @see NelioABAccountPageController
		 *
		 * @since 3.2.0
		 */
		public static function fix_registration_info( $registered, $id = false ) {
			self::update_nelioab_option( 'has_a_configured_site', 'registered' === $registered );
			NelioABAccountSettings::set_site_id( $id );
		}


		/**
		 * Removes this site from the account of our customer.
		 *
		 * @return void
		 *
		 * @throws Exception an exception triggered by AppEngine.
		 *
		 * @since 2.1.0
		 */
		public static function deregister_this_site() {
			try {
				NelioABBackend::remote_post( sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/deactivate',
					NelioABAccountSettings::get_site_id()
				), array(), true );
			}
			catch ( Exception $e ) {
				throw $e;
			}
			self::update_nelioab_option( 'has_a_configured_site', false );
		}


		/**
		 * Unlinks this site from the account of our customer.
		 *
		 * Sometimes, the current site is accounted as "registered", because it has
		 * a registered site ID. However, the URL of this site and the registered
		 * site do not match. This occurs, for instance, when a user copies a live
		 * site to staging; the staging site inherits all live's data (including
		 * the site ID), but the URLs do not match. If that happens, then the
		 * staging site is "Linked" to our customer's account, but not registered
		 * by itself.
		 *
		 * @return void
		 *
		 * @since 3.2.0
		 */
		public static function unlink_this_site() {
			self::update_nelioab_option( 'has_a_configured_site', false );
		}

	}//NelioABAccountSettings


	/**
	 * This site contains information about the sites registered to this account and the number of free slots available.
	 *
	 * @package \NelioABTesting\Models\Settings
	 * @since 2.1.0
	 */
	class NelioABSitesInfo {

		/**
		 * A list of NelioABSite objects.
		 *
		 * @since 2.1.0
		 * @var array
		 */
		private $sites;


		/**
		 * The number of sites that can be registered to this account.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		private $max_sites;


		/**
		 * Creates a new instance of this class.
		 *
		 * @return NelioABSitesInfo the new instance of this class.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			$this->sites     = array();
			$this->max_sites = 1;
		}


		/**
		 * Adds the NelioABSite object to the list of registered sites.
		 *
		 * @param NelioABSite $site the site to be added to the list of registered sites.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public function add_registered_site( $site ) {
			array_push( $this->sites, $site );
		}


		/**
		 * Returns the list of registered sites.
		 *
		 * @return array the list of registered sites.
		 *
		 * @since 2.1.0
		 */
		public function get_registered_sites() {
			return $this->sites;
		}


		/**
		 * Sets the max number of sites that can be registered to this account.
		 *
		 * @param int $max_sites the max number of sites that can be registered to this account.
		 *
		 * @sine 2.1.0
		 */
		public function set_max_sites( $max_sites ) {
			$this->max_sites = $max_sites;
		}


		/**
		 * Returns the max number of sites that can be registered to this account.
		 *
		 * @return int the max number of sites that can be registered to this account.
		 *
		 * @since 2.1.0
		 */
		public function get_max_sites() {
			return $this->max_sites;
		}

	}//NelioABSitesInfo


	/**
	 * This class represents a Site.
	 *
	 * @package \NelioABTesting\Models\Settings
	 * @since 2.1.0
	 */
	class NelioABSite {

		/**
		 * Inactive status.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const INACTIVE = 0;


		/**
		 * Active status.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const ACTIVE = 1;


		/**
		 * This site instance and the site in which the plugin runs have the same ID, but they URLs do not match.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const NON_MATCHING_URLS = 2;


		/**
		 * This site instance and the site in which the plugin runs have the same URL, but their IDs do not match.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const INVALID_ID = 3;


		/**
		 * The site in which the plugin runs is not registered to Nelio.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const NOT_REGISTERED = 4;


		/**
		 * The AppEngine ID of the site.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		private $id;


		/**
		 * The site's URL.
		 *
		 * @since 2.1.0
		 * @var string
		 */
		private $url;


		/**
		 * The status of the site.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		private $status;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param int    $id     The AppEngine's ID.
		 * @param string $url    The URL of the new site.
		 * @param int    $status The status of the new site.
		 *
		 * @return NelioABSite a new instance of this class.
		 *
		 * @since 2.1.0
		 */
		public function __construct( $id, $url, $status ) {
			$this->id     = $id;
			$this->url    = $url;
			$this->status = $status;
		}


		/**
		 * Returns the AppEngine ID of this site.
		 *
		 * @return int the AppEngine ID of this site.
		 *
		 * @since 2.1.0
		 */
		public function get_id() {
			return $this->id;
		}


		/**
		 * Returns the URL of this site.
		 *
		 * @return string the URL of this site.
		 *
		 * @since 2.1.0
		 */
		public function get_url() {
			return $this->url;
		}

		/**
		 * Returns the status of this site.
		 *
		 * @return int the status of this site.
		 *
		 * @since 2.1.0
		 */
		public function get_status() {
			return $this->status;
		}

	}//NelioABSite

}

