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


if ( !class_exists( 'NelioABBackend' ) ) {

	abstract class NelioABBackend {

		public static function remote_post_raw( $url, $params ) {
			if ( !isset( $params['timeout'] ) )
				$params['timeout'] = 30;
			$result = wp_remote_post( $url, $params );
			NelioABBackend::throw_exceptions_if_any( $result );
			return $result;
		}

		public static function remote_post( $url, $params = array() ) {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			$wrapped_params = array();
			$credential     = NelioABBackend::make_credential();

			if ( count( $params ) == 0 ) {
				$wrapped_params = $credential;
			}
			else {
				$wrapped_params['object']     = $params;
				$wrapped_params['credential'] = $credential;
			}

			$json_params = array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => json_encode( $wrapped_params ),
         );

			return NelioABBackend::remote_post_raw( $url, $json_params );
		}

		public static function remote_get( $url, $params = array() ) {
			return NelioABBackend::remote_post( $url, $params );
		}

		public static function make_credential( $skip_check = false ) {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// The following function makes sure that the registered URL and the
			// current siteurl are the same:
			// if ( !$skip_check )
			//    NelioABBackend::sync_site_url();

			// Creating the credential
			$result = array();
			$result['customerId']         = NelioABSettings::get_customer_id();
			$result['registrationNumber'] = NelioABSettings::get_reg_num();
			$result['siteId']             = NelioABSettings::get_site_id();
			$result['siteUrl']            = get_option( 'siteurl' );

			return $result;
		}

		/**
		 * Before creating a credential, we must check whether the registered url
		 * and 'siteurl' are the same. If the user changed his WP's URL after he
		 * registered the site, these urls differ and the credentials would be
		 * invalid.
		 *
		 * In principle, this is controlled by the following hook:
		 *   pre_update_option_siteurl -> NelioABSettings::update_registered_sites_if_required
		 */
		private static function sync_site_url() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			if ( NelioABSettings::has_a_configured_site() &&
			     get_option( 'siteurl' ) != NelioABSettings::get_site_url() ) {
				try {
					$special_credential = NelioABBackend::make_credential( true );
					$params = array( 'url' => get_option( 'siteurl' ) );

					$wrapped_params = array();
					$wrapped_params['object'] = $params;
					$wrapped_params['credential'] = $special_credential;

					$json_params = array(
						'headers' => array( 'Content-Type' => 'application/json' ),
						'body'    => json_encode( $wrapped_params ),
					);

					$url = sprintf( NELIOAB_BACKEND_URL . '/site/%s/update',
						NelioABSettings::get_site_id()
						);
					$aux = NelioABBackend::remote_post_raw( $url, $json_params );
					NelioABSettings::set_site_url( get_option( 'siteurl' ) );
				}
				catch ( Exception $e ) {
					// Hopefully, this will never happen
				}
			}
		}

		private static function throw_exceptions_if_any( $result ) {

			if ( is_wp_error( $result ) ) {
				$err = NelioABErrCodes::BACKEND_NOT_AVAILABLE;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( $result['response']['code'] == 404 ) {
				$err = NelioABErrCodes::ERROR_404;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			if ( $result['response']['code'] == 204 ) {
				$err = NelioABErrCodes::STATUS_204;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			$aux = json_decode( $result['body'] );

			if ( isset( $aux->error ) ) {
				$err = intval( $aux->error->message );
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

		}

	}//NelioABBackend

	abstract class NelioABErrCodes {
		// These are Error codes returned by the backend
		const INVALID_PRODUCT_REG_NUM          = 1;
		const INVALID_SITE                     = 2;
		const SITE_IS_NOT_ACTIVE               = 3;
		const MAX_SITES                        = 4;
		const NO_MORE_QUOTA                    = 5;
		const UNPAID_SUBSCRIPTION              = 6;
		const INVALID_MAIL                     = 7;
		const SEVERAL_CUSTOMERS_WITH_SAME_MAIL = 8;
		const TOO_FEW_PARAMETERS               = 9;
		const INVALID_SITE_URL                 = 10;
		const INVALID_PARAMETERS               = 11;
		const INVALID_EXPERIMENT               = 12;
		const INVALID_ALTERNATIVE              = 13;
		const RESULTS_NOT_AVAILABLE_YET        = 14;
		const DEACTIVATED_USER                 = 15;
		const EXPERIMENT_ID_NOT_FOUND          = 16;
		const INVALID_GOAL                     = 17;

		// Error codes corresponding to package details
		const MULTI_PAGE_GOAL_NOT_ALLOWED_IN_BASIC = 100;
		const HEATMAP_NOT_ALLOWED_IN_BASIC         = 101;

		// These are "private" error codes
		const BACKEND_NOT_AVAILABLE        = -1;
		const BACKEND_NO_SITE_CONFIGURED   = -2;
		const BACKEND_UNKNOWN_ERROR        = -3;
		const ERROR_404                    = -4;
		const NON_ACCEPTED_TAC             = -5;
		const STATUS_204                   = -6;
		const UNKNOWN_ERROR                = -7;
		const NO_HEATMAPS_AVAILABLE        = -8;
		const EXPERIMENT_CANNOT_BE_STARTED = -9;

		public static function to_string( $err ) {
			switch( $err ) {
				// Backend errors
				case NelioABErrCodes::INVALID_PRODUCT_REG_NUM:
					return __( 'Invalid product registration number.', 'nelioab' );
				case NelioABErrCodes::INVALID_SITE:
					return __( 'Invalid site.', 'nelioab' );
				case NelioABErrCodes::SITE_IS_NOT_ACTIVE:
					return __( 'This site is not active.', 'nelioab' );
				case NelioABErrCodes::MAX_SITES:
					return __( 'This account has reached the maximum allowed number of registered sites.', 'nelioab' );
				case NelioABErrCodes::NO_MORE_QUOTA:
					return __( 'There is no more quota available.', 'nelioab' );
				case NelioABErrCodes::UNPAID_SUBSCRIPTION:
					return __( 'Subscription has not been paid yet.', 'nelioab' );
				case NelioABErrCodes::INVALID_MAIL:
					return __( 'Invalid e-mail.', 'nelioab' );
				case NelioABErrCodes::SEVERAL_CUSTOMERS_WITH_SAME_MAIL:
					return __( 'This e-mail is already registered.', 'nelioab' );
				case NelioABErrCodes::TOO_FEW_PARAMETERS:
					return __( 'Too few parameters.', 'nelioab' );
				case NelioABErrCodes::INVALID_SITE_URL:
					return __( 'The URL of the site is invalid.', 'nelioab' );
				case NelioABErrCodes::INVALID_PARAMETERS:
					return __( 'Invalid parameters.', 'nelioab' );
				case NelioABErrCodes::INVALID_EXPERIMENT:
					return __( 'Invalid experiment.', 'nelioab' );
				case NelioABErrCodes::INVALID_ALTERNATIVE:
					return __( 'Invalid alternative.', 'nelioab' );
				case NelioABErrCodes::RESULTS_NOT_AVAILABLE_YET:
					return __( 'Results for this experiment are not yet available.', 'nelioab' );
				case NelioABErrCodes::DEACTIVATED_USER:
					return __( 'User account has been deactivated.', 'nelioab' );
				case NelioABErrCodes::EXPERIMENT_ID_NOT_FOUND:
					return __( 'Experiment not found.', 'nelioab' ) . '<br />' .
						'<small><a href="'. admin_url() . 'admin.php?page=nelioab-experiments">' .
						__( 'Go to my list of experiments...', 'nelioab' ) .
						'</a></small>';
				case NelioABErrCodes::INVALID_GOAL:
					return __( 'Goal not found.', 'nelioab' );


				// Error codes corresponding to package details
				case NelioABErrCodes::MULTI_PAGE_GOAL_NOT_ALLOWED_IN_BASIC:
					return sprintf(
						__( 'Oops! The experiment cannot be started because it defines more than one goal page. Please, modify your experiment so that it includes one goal page only or <a href="%s">upgrade your Nelio A/B Testing subscription package</a>.', 'nelioab' ),
						'http://wp-abtesting.com/inquiry-subscription-plans/' );

				case NelioABErrCodes::HEATMAP_NOT_ALLOWED_IN_BASIC:
					return sprintf(
						__( 'Oops! Your current subscription plan does not permit you to use Heatmap Experiments. Please, consider <a href="%s">upgrading your Nelio A/B subscription package</a>.', 'nelioab' ),
						'http://wp-abtesting.com/inquiry-subscription-plans/' );



				// Private errors
				case NelioABErrCodes::BACKEND_NOT_AVAILABLE:
					return __( 'Backend is not available.', 'nelioab' );
				case NelioABErrCodes::BACKEND_NO_SITE_CONFIGURED:
					return __( 'No site has been configured.', 'nelioab' );
				case NelioABErrCodes::ERROR_404:
					return __( 'Error 404 when accessing an endpoint.', 'nelioab' );
				case NelioABErrCodes::NON_ACCEPTED_TAC:
					return __( 'Terms and conditions are not accepted.', 'nelioab' );
				case NelioABErrCodes::STATUS_204:
					return __( 'Backend is not accessible.<br />Please, try again in just a few moments.', 'nelioab' );
				case NelioABErrCodes::NO_HEATMAPS_AVAILABLE:
					return __( 'Be patient... We are still collecting the data for your heatmaps.', 'nelioab' );
				case NelioABErrCodes::UNKNOWN_ERROR:
					return __( 'An unknown error has occurred.', 'nelioab' );
				case NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED:
					return __( 'Experiment cannot be started.', 'nelioab' );
				case NelioABErrCodes::BACKEND_UNKNOWN_ERROR:
				default:
					return __( 'An unknown error occurred while accessing the backend.', 'nelioab' );
			}
		}
	}//NelioABBackend

}
?>
