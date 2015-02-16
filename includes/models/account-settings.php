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


if( !class_exists( 'NelioABAccountSettings' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	class NelioABAccountSettings {

		const BETA_SUBSCRIPTION_PLAN         = 0;
		const BASIC_SUBSCRIPTION_PLAN        = 1;
		const PROFESSIONAL_SUBSCRIPTION_PLAN = 2;
		const ENTERPRISE_SUBSCRIPTION_PLAN   = 3;

		private static $settings = false;
		private static function settings() {
			if ( !self::$settings )
				self::$settings = get_option( 'nelioab_account_settings', array() );
			return self::$settings;
		}

		public static function get_nelioab_option( $name, $default = false ) {
			self::$settings = self::settings();
			if ( !isset( self::$settings[$name] ) ) {
				self::$settings[$name] = get_option( "nelioab_$name", $default );
				update_option( 'nelioab_account_settings', self::$settings );
				delete_option( "nelioab_$name" );
			}
			return self::$settings[$name];
		}

		public static function update_nelioab_option( $name, $value ) {
			self::$settings = self::settings();
			self::$settings[$name] = $value;
			update_option( 'nelioab_account_settings', self::$settings );
		}

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

				if ( $error == NelioABErrCodes::INVALID_PRODUCT_REG_NUM )
					NelioABAccountSettings::set_reg_num_validity( false );

				if ( $error == NelioABErrCodes::INVALID_MAIL )
					NelioABAccountSettings::set_email_validity( false );

				throw $e;
			}

			NelioABAccountSettings::set_reg_num_validity( true );
			NelioABAccountSettings::set_email_validity( true );
			self::update_nelioab_option( 'customer_id', $json_data->key->id );

			// Store the current subscription plan

			// Check if the current site is already registered for this account
			$registered = false;
			if ( NelioABAccountSettings::has_a_configured_site() ) {
				$this_id    = NelioABAccountSettings::get_site_id();
				$sites_info = NelioABAccountSettings::get_registered_sites_information();
				$sites      = $sites_info->get_registered_sites();
				foreach( $sites as $s )
					if ( $s->get_id() == $this_id )
						$registered = true;
			}
			NelioABAccountSettings::set_has_a_configured_site( $registered );
		}

		public static function get_customer_id() {
			return self::get_nelioab_option( 'customer_id', '' );
		}

		public static function get_email() {
			return self::get_nelioab_option( 'email', '' );
		}

		private static function set_email_validity( $validity ) {
			self::update_nelioab_option( 'is_email_valid', $validity );
		}

		public static function is_email_valid() {
			return self::get_nelioab_option( 'is_email_valid', false );
		}

		public static function get_reg_num() {
			return self::get_nelioab_option( 'reg_num', '' );
		}

		private static function set_reg_num_validity( $validity ) {
			self::update_nelioab_option( 'is_reg_num_valid', $validity );
		}

		public static function is_reg_num_valid() {
			return self::get_nelioab_option( 'is_reg_num_valid', false );
		}

		public static function has_a_configured_site() {
			return self::get_nelioab_option( 'has_a_configured_site', false );
		}

		public static function set_has_a_configured_site( $configured ) {
			self::update_nelioab_option( 'has_a_configured_site', $configured );
		}

		public static function get_site_id() {
			return self::get_nelioab_option( 'site_id', '' );
		}

		public static function set_site_id( $site_id ) {
			self::update_nelioab_option( 'site_id', $site_id );
		}

		public static function check_terms_and_conditions( $accepted ) {
			self::update_nelioab_option( 'are_tac_accepted', $accepted );
		}

		public static function are_terms_and_conditions_accepted() {
			return self::get_nelioab_option( 'are_tac_accepted', false );
		}

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

		public static function sync_plugin_version() {
			$last_synced_version = self::get_nelioab_option( 'last_synced_version', '3.3.7' );
			try {
				if ( NELIOAB_PLUGIN_VERSION !== $last_synced_version && self::check_user_settings() ) {
					try {
						$url  = sprintf( NELIOAB_BACKEND_URL . '/site/%s/version',
							NelioABAccountSettings::get_site_id() );
						$body = array( 'version' => NELIOAB_PLUGIN_VERSION );
						NelioABBackend::remote_post( $url, $body );
						self::update_nelioab_option( 'last_synced_version', NELIOAB_PLUGIN_VERSION );
					} catch ( Exception $e ) {}
				}
			} catch ( Exception $e ) {}
		}

		public static function check_account_status( $mode = 'none' ) {
			$the_past   = mktime( 0, 0, 0, 1, 1, 2000 );
			$last_check = self::get_nelioab_option( 'last_check_user_settings', $the_past );
			$now        = time();
			$offset     = 1800; // sec (== 30min)
			if ( ( $last_check + $offset ) < $now || 'force-check' === $mode ) {
				try {
					$url  = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check', NelioABAccountSettings::get_customer_id() );
					$json = NelioABBackend::remote_get( $url, true );
					$json = json_decode( $json['body'] );
					NelioABAccountSettings::set_account_as_active();
					self::update_nelioab_option( 'subscription_plan', $json->subscriptionPlan );
					self::update_nelioab_option( 'last_check_user_settings', $now );
				}
				catch ( Exception $e ) {
					if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER ) {
						NelioABAccountSettings::set_account_as_active( false );
						self::update_nelioab_option( 'last_check_user_settings', $now );
					}
					else {
						NelioABAccountSettings::set_account_as_active( false );
						self::update_nelioab_option( 'last_check_user_settings', $now - 1800 + 60);
					}
				}
			}
		}

		public static function is_account_active() {
			return self::get_nelioab_option( 'is_account_active', false );
		}

		public static function set_account_as_active( $active = true ) {
			self::update_nelioab_option( 'is_account_active', $active );
		}

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
				NelioABAccountSettings::set_has_a_configured_site( true );
				NelioABAccountSettings::set_site_id( $json_data->key->id );
			}
			catch ( Exception $e ) {
				NelioABAccountSettings::set_has_a_configured_site( false );
				throw $e;
			}

		}

		public static function fix_registration_info( $registered, $id = false ) {
			NelioABAccountSettings::set_has_a_configured_site( 'registered' === $registered );
			NelioABAccountSettings::set_site_id( $id );
		}

		public static function deregister_this_site() {
			try {
				$json_data = NelioABBackend::remote_post( sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/deactivate',
					NelioABAccountSettings::get_site_id()
				), array(), true );
			}
			catch ( Exception $e ) {
				throw $e;
			}
			NelioABAccountSettings::set_has_a_configured_site( false );
		}

		public static function unlink_this_site() {
			NelioABAccountSettings::set_has_a_configured_site( false );
		}

		public static function has_quota_left() {
			return self::get_nelioab_option( 'has_quota_left', true );
		}

		public static function set_has_quota_left( $has_quota_left ) {
			self::update_nelioab_option( 'has_quota_left', $has_quota_left );
			self::update_nelioab_option( 'last_quota_check', time() );
		}

		public static function assume_quota_check_will_occur_shortly() {
			// Simulate the last check was 28 minutes (=1680s) ago
			self::update_nelioab_option( 'last_quota_check', time() - 1680 );
		}

		public static function is_quota_check_required() {
			$last_check = self::get_nelioab_option( 'last_quota_check', 0 );
			$now        = time();
			$offset     = 1800; // seg == 30min
			return ( ( $last_check + $offset ) < $now );
		}

	}//NelioABAccountSettings


	class NelioABSitesInfo {
		private $sites;
		private $max_sites;

		public function __construct() {
			$this->sites     = array();
			$this->max_sites = 1;
		}

		public function add_registered_site( $site ) {
			array_push( $this->sites, $site );
		}

		public function get_registered_sites() {
			return $this->sites;
		}

		public function set_max_sites( $max_sites ) {
			$this->max_sites = $max_sites;
		}

		public function get_max_sites() {
			return $this->max_sites;
		}

	}//NelioABSitesInfo

	class NelioABSite {
		const INACTIVE          = 0;
		const ACTIVE            = 1;
		const NON_MATCHING_URLS = 2;
		const INVALID_ID        = 3;
		const NOT_REGISTERED    = 4;

		private $id;
		private $url;
		private $status;

		public function __construct( $id, $url, $status ) {
			$this->id     = $id;
			$this->url    = $url;
			$this->status = $status;
		}

		public function get_id() {
			return $this->id;
		}

		public function get_url() {
			return $this->url;
		}

		public function get_status() {
			return $this->status;
		}

	}//NelioABSite

}
