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

if ( !class_exists( 'NelioABBackend' ) ) {

	/**
	 * Simple class for accessing Nelio's cloud servers and processing the results.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABBackend {


		/**
		 * PHPDOC
		 *
		 * @param string  $url               PHPDOC
		 * @param array   $params            PHPDOC
		 * @param boolean $skip_status_check PHPDOC
		 *                                   Default: false.
		 *
		 * @return WP_Error|array The response or WP_Error on failure.
		 *
		 * @throws Exception with the appropriate error code.
		 *
		 * @since PHPDOC
		 */
		public static function remote_post_raw( $url, $params, $skip_status_check = false ) {
			if ( !$skip_status_check ) {
				require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
				try {
					NelioABAccountSettings::check_user_settings();
				}
				catch ( Exception $e ) {
					throw $e;
				}
			}
			if ( !isset( $params['timeout'] ) )
				$params['timeout'] = 30;
			$params['sslverify'] = false;
			$result = wp_remote_post( $url, $params );
			NelioABBackend::throw_exceptions_if_any( $result );
			return $result;
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $url               PHPDOC
		 * @param array   $params            PHPDOC
		 * @param boolean $skip_status_check PHPDOC
		 *
		 * @return WP_Error|array The response or WP_Error on failure.
		 *
		 * @throws Exception with the appropriate error code.
		 *
		 * @since PHPDOC
		 */
		public static function remote_post( $url, $params = array(), $skip_status_check = false ) {
			$json_params = NelioABBackend::build_json_object_with_credentials( $params );
			return NelioABBackend::remote_post_raw( $url, $json_params, $skip_status_check );
		}


		/**
		 * PHPDOC
		 *
		 * @param array $params PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function build_json_object_with_credentials( $params = array() ) {
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
			return $json_params;
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $url               PHPDOC
		 * @param boolean $skip_status_check PHPDOC
		 *
		 * @return WP_Error|array The response or WP_Error on failure.
		 *
		 * @throws Exception with the appropriate error code.
		 *
		 * @since PHPDOC
		 */
		public static function remote_get( $url, $skip_status_check = false ) {
			return NelioABBackend::remote_post( $url, array(), $skip_status_check );
		}


		/**
		 * PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function make_credential() {
			// Creating the credential
			$result = array();

			$aux = NelioABAccountSettings::get_customer_id();
			if ( $aux ) {
				$result['customerId'] = $aux;
			}

			$aux = NelioABAccountSettings::get_reg_num();
			if ( $aux ) {
				$result['registrationNumber'] = $aux;
			}

			$aux = NelioABAccountSettings::get_site_id();
			if ( $aux ) {
				$result['siteId'] = $aux;
			}

			$result['siteUrl'] = get_option( 'siteurl' );

			return $result;
		}


		/**
		 * PHPDOC
		 *
		 * @param WP_Error|array $result The response to a call to Nelio's servers.
		 *
		 * @return void
		 *
		 * @throws Exception with the appropriate error code.
		 *
		 * @see NelioABErrCodes
		 *
		 * @since PHPDOC
		 */
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

			if ( isset( $aux->beErrCode ) && $aux->beErrCode ) {
				$err = intval( $aux->beErrCode );
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

		}

	}//NelioABBackend


	/**
	 * This class contains all the error codes returned by Nelio's cloud servers.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABErrCodes {

		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const NO_ERROR = 0;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_PRODUCT_REG_NUM = 1;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_SITE = 2;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const SITE_IS_NOT_ACTIVE = 3;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const MAX_SITES = 4;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const NO_MORE_QUOTA = 5;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const UNPAID_SUBSCRIPTION = 6;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_MAIL = 7;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const SEVERAL_CUSTOMERS_WITH_SAME_MAIL = 8;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const TOO_FEW_PARAMETERS = 9;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_SITE_URL = 10;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_PARAMETERS = 11;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_EXPERIMENT = 12;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_ALTERNATIVE = 13;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const RESULTS_NOT_AVAILABLE_YET = 14;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const DEACTIVATED_USER = 15;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const EXPERIMENT_ID_NOT_FOUND = 16;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_GOAL = 17;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_MODIFICATION = 18;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const EXPERIMENT_NOT_RUNNING = 19;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const FEATURE_NOT_AVAILABLE_FOR_CUSTOMER = 20;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_SCHEDULE_DATE = 21;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const EXPERIMENT_CANNOT_BE_DUPLICATED = 22;


		/**
		 * PHPDOC
		 * This error code is returned by the backend.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INCOMPLETE_EXPERIMENT = 23;


		/**
		 * PHPDOC
		 * This error code is returned by the backend and corresponds to package
		 * details.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const MULTI_PAGE_GOAL_NOT_ALLOWED_IN_BASIC = 100;


		/**
		 * PHPDOC
		 * This error code is returned by the backend and corresponds to package
		 * details.
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const HEATMAP_NOT_ALLOWED_IN_BASIC = 101;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const BACKEND_NOT_AVAILABLE = -1;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const BACKEND_NO_SITE_CONFIGURED = -2;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const BACKEND_UNKNOWN_ERROR = -3;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const ERROR_404 = -4;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const NON_ACCEPTED_TAC = -5;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const STATUS_204 = -6;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const UNKNOWN_ERROR = -7;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const NO_HEATMAPS_AVAILABLE = -8;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const NO_HEATMAPS_AVAILABLE_FOR_NON_RUNNING_EXPERIMENT = -9;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const EXPERIMENT_CANNOT_BE_STARTED = -10;


		/**
		 * PHPDOC
		 * This error code is private (it only appears in the plugin).
		 *
		 * @since PHPDOC
		 * @var int
		 */
		const INVALID_NONCE = -11;


		/**
		 * PHPDOC
		 *
		 * @var int $err The error code.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
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
					return __( 'This account has reached the maximum allowed number of active sites.', 'nelioab' );
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
						'<small><a href="'. admin_url( 'admin.php?page=nelioab-experiments' ) . '">' .
						__( 'Go to my list of experiments...', 'nelioab' ) .
						'</a></small>';
				case NelioABErrCodes::INVALID_GOAL:
					return __( 'Goal not found.', 'nelioab' );
				case NelioABErrCodes::INVALID_MODIFICATION:
					return __( 'The experiment cannot be modified.', 'nelioab' );
				case NelioABErrCodes::EXPERIMENT_NOT_RUNNING:
					return __( 'The experiment is not running.', 'nelioab' );
				case NelioABErrCodes::FEATURE_NOT_AVAILABLE_FOR_CUSTOMER:
					return __( 'The feature you are trying to use is not available.', 'nelioab' );
				case NelioABErrCodes::INVALID_SCHEDULE_DATE:
					return __( 'The experiment cannot be scheduled for the given date.', 'nelioab' );
				case NelioABErrCodes::EXPERIMENT_CANNOT_BE_DUPLICATED:
					return __( 'The experiment cannot be duplicated.', 'nelioab' );
				case NelioABErrCodes::INCOMPLETE_EXPERIMENT:
					return __( 'The experiment cannot be started: missing information (for instance, an alternative or a set of actions in a goal).', 'nelioab' );



				// Error codes corresponding to package details
				case NelioABErrCodes::MULTI_PAGE_GOAL_NOT_ALLOWED_IN_BASIC:
					return sprintf(
						__( 'Oops! The experiment cannot be started because it defines more than one goal page. Please, modify your experiment so that it includes one goal page only or <a href="%s">upgrade your Nelio A/B Testing subscription package</a>.', 'nelioab' ),
						'http://nelioabtesting.com/inquiry-subscription-plans/' );

				case NelioABErrCodes::HEATMAP_NOT_ALLOWED_IN_BASIC:
					return sprintf(
						__( 'Oops! Your current subscription plan does not permit you to use Heatmap Experiments. Please, consider <a href="%s">upgrading your Nelio A/B subscription package</a>.', 'nelioab' ),
						'http://nelioabtesting.com/inquiry-subscription-plans/' );



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
					return __( 'Be patient... We are still collecting the data for your heatmaps and clickmaps.', 'nelioab' );
				case NelioABErrCodes::NO_HEATMAPS_AVAILABLE_FOR_NON_RUNNING_EXPERIMENT:
					return __( 'We did not collect enough data for building your heatmaps and clickmaps. Sorry.', 'nelioab' );
				case NelioABErrCodes::UNKNOWN_ERROR:
					return __( 'An unknown error has occurred.', 'nelioab' );
				case NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED:
					return __( 'Experiment cannot be started.', 'nelioab' );
				case NelioABErrCodes::INVALID_NONCE:
					return __( 'Invalid «_nonce». Are you sure you want to do this?', 'nelioab' );
				case NelioABErrCodes::BACKEND_UNKNOWN_ERROR:
				default:
					return __( 'An unknown error occurred while accessing the backend.', 'nelioab' );
			}
		}

	}//NelioABBackend

}

