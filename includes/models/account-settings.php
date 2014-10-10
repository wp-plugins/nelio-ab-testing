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

		public static function get_subscription_plan() {
			try {
				NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				// Nothing to catch here
			}

			return get_option(
				'nelioab_subscription_plan',
				NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN );
		}

		public static function validate_email_and_reg_num( $email, $reg_num ) {
			update_option( 'nelioab_email', $email );
			update_option( 'nelioab_reg_num', $reg_num );

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
			update_option( 'nelioab_customer_id', $json_data->key->id );

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
			return get_option( 'nelioab_customer_id', '' );
		}

		public static function get_email() {
			return get_option( 'nelioab_email', '' );
		}

		private static function set_email_validity( $validity ) {
			update_option( 'nelioab_is_email_valid', $validity );
		}

		public static function is_email_valid() {
			return get_option( 'nelioab_is_email_valid', false );
		}

		public static function get_reg_num() {
			return get_option( 'nelioab_reg_num', '' );
		}

		private static function set_reg_num_validity( $validity ) {
			update_option( 'nelioab_is_reg_num_valid', $validity );
		}

		public static function is_reg_num_valid() {
			return get_option( 'nelioab_is_reg_num_valid', false );
		}

		public static function has_a_configured_site() {
			return get_option( 'nelioab_has_a_configured_site', false );
		}

		public static function set_has_a_configured_site( $configured ) {
			update_option( 'nelioab_has_a_configured_site', $configured );
		}

		public static function get_site_id() {
			return get_option( 'nelioab_site_id', '' );
		}

		public static function set_site_id( $site_id ) {
			update_option( 'nelioab_site_id', $site_id );
		}

		public static function check_terms_and_conditions( $accepted ) {
			update_option( 'nelioab_are_tac_accepted', $accepted );
		}

		public static function are_terms_and_conditions_accepted() {
			return get_option( 'nelioab_are_tac_accepted', false );
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

		public static function check_account_status( $mode = 'none' ) {
			$the_past   = mktime( 0, 0, 0, 1, 1, 2000 );
			$last_check = get_option( 'nelioab_last_check_user_settings', $the_past );
			$now        = time();
			$offset     = 1800; // sec (== 30min)
			if ( ( $last_check + $offset ) < $now || 'force-check' === $mode ) {
				try {
					$url  = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check', NelioABAccountSettings::get_customer_id() );
					$json = NelioABBackend::remote_get( $url, true );
					$json = json_decode( $json['body'] );
					NelioABAccountSettings::set_account_as_active();
					update_option( 'nelioab_subscription_plan', $json->subscriptionPlan );
					update_option( 'nelioab_last_check_user_settings', $now );
				}
				catch ( Exception $e ) {
					if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER ) {
						NelioABAccountSettings::set_account_as_active( false );
						update_option( 'nelioab_last_check_user_settings', $now );
					}
				}
			}
		}

		public static function is_account_active() {
			return get_option( 'nelioab_is_account_active', false );
		}

		public static function set_account_as_active( $active = true ) {
			update_option( 'nelioab_is_account_active', $active );
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

		public static function register_this_site() {

			try {
				$params = array( 'url' => get_option( 'siteurl' ) );
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
			return get_option( 'nelioab_has_quota_left', true );
		}

		public static function set_has_quota_left( $has_quota_left ) {
			update_option( 'nelioab_has_quota_left', $has_quota_left );
			update_option( 'nelioab_last_quota_check', time() );
		}

		public static function is_quota_check_required() {
			$last_check = get_option( 'nelioab_last_quota_check', 0 );
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

?>
