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


if( !class_exists( NelioABSettings ) ) {

	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	class NelioABSettings {

		public static function cookie_prefix() {
			return 'nelioab_';
		}

		public static function validate_email_and_reg_num( $email, $reg_num ) {
			update_option( 'nelioab_email', $email );
			update_option( 'nelioab_reg_num', $reg_num );

			$json_data = null;
			try {
				$params = array(
					'body' => array( 'mail' => $email, 'registrationNumber' => $reg_num )
				);

				$json_data = NelioABBackend::remote_post_raw(
					NELIOAB_BACKEND_URL . '/customer/validate',
					$params );

				$json_data = json_decode( $json_data['body'] );
			}
			catch ( Exception $e ) {
				$error = $e->getCode();

				if ( $error == NelioABErrCodes::INVALID_PRODUCT_REG_NUM )
					NelioABSettings::set_reg_num_validity( false );

				if ( $error == NelioABErrCodes::INVALID_MAIL )
					NelioABSettings::set_email_validity( false );

				throw $e;
			}

			NelioABSettings::set_reg_num_validity( true );
			NelioABSettings::set_email_validity( true );
			update_option( 'nelioab_customer_id', $json_data->key->id );

			// Check if the current site is already registered for this account
			$registered = false;
			$this_url   = get_option( 'siteurl' );
			$sites_info = NelioABSettings::get_registered_sites_information();
			$sites      = $sites_info->get_registered_sites();
			foreach( $sites as $s )
				if ( $s->get_url() == $this_url )
					$registered = true;
			if ( $registered )
				NelioABSettings::register_this_site();
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

		public static function set_copy_metadata( $enabled ) {
			update_option( 'nelioab_copy_metadata', $enabled );
		}

		public static function is_copying_metadata_enabled() {
			return get_option( 'nelioab_copy_metadata', true );
		}

		public static function check_user_settings() {

			if ( !NelioABSettings::is_email_valid() ) {
				$err = NelioABErrCodes::INVALID_MAIL;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABSettings::is_reg_num_valid() ) {
				$err = NelioABErrCodes::INVALID_PRODUCT_REG_NUM;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABSettings::are_terms_and_conditions_accepted() ) {
				$err = NelioABErrCodes::NON_ACCEPTED_TAC;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( !NelioABSettings::has_a_configured_site() ) {
				$err = NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			$the_past   = mktime( 0, 0, 0, 1, 1, 2000 );
			$last_check = get_option( 'nelioab_last_check_user_settings', $the_past);
			$now        = time();
			$offset     = 1800; // seg == 30min
			// if ( ( $the_past + $offset ) < $now ) {
				try {
					$url = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check', NelioABSettings::get_customer_id() );
					$aux = NelioABBackend::remote_get( $url );
					update_option( 'nelioab_last_check_user_settings', $now);
				}
				catch ( Exception $e ) {
					if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER )
						throw $e;
				}
			// }

			return true;
		}

		public static function get_registered_sites_information() {
			$res = new NelioABSitesInfo();
			$res->set_max_sites( 3 ); // TODO: recover data from json, when finally available

			$json_data = NelioABBackend::remote_get(
				sprintf( NELIOAB_BACKEND_URL . '/customer/%s/site', NelioABSettings::get_customer_id() )
			);

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

		public static function update_registered_sites_if_required( $url ) {

			while ( substr( $url, -1 ) === '/' )
				$url = substr( $url, 0, strlen($url) - 1 );

			if ( NelioABSettings::has_a_configured_site() ) {
				$id     = NelioABSettings::get_site_id();
				$params = array(
					'url' => $url,
				);
				try {
					$json_data = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/site/%s', $id ),
						$params
					);
				}
				catch ( Exception $e ) {}
			}

			return $url;
		}

		public static function register_this_site() {

			try {
				$params = array( 'url' => get_option( 'siteurl' ) );
				$json_data = NelioABBackend::remote_post( sprintf(
					NELIOAB_BACKEND_URL . '/customer/%s/site/activate',
					NelioABSettings::get_customer_id()
				), $params );

				$json_data = json_decode( $json_data['body'] );
				NelioABSettings::set_has_a_configured_site( true );
				NelioABSettings::set_site_id( $json_data->key->id );
			}
			catch ( Exception $e ) {
				NelioABSettings::set_has_a_configured_site( false );
				// TODO check errors
				throw $e;
			}

		}

		public static function deregister_this_site() {
			
			try {
				$json_data = NelioABBackend::remote_post( sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/deactivate',
					NelioABSettings::get_site_id()
				)	);
			}
			catch ( Exception $e ) {
				// TODO check errors
				throw $e;
			}

			NelioABSettings::set_has_a_configured_site( false );
		}

	}//NelioABSettings

	
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
