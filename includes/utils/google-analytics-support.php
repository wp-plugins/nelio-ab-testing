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


if ( !class_exists( 'NelioABGoogleAnalyticsSupport' ) ) {

	abstract class NelioABGoogleAnalyticsSupport {

		private static function is_active( $name ) {
			return in_array( $name, (array) get_option( 'active_plugins', array() ) );
		}

		public static function try_to_delay_script_executions() {
			// Support with WP Google Analytics Events
			$plugin = 'wp-google-analytics-events/ga-scroll-event.php';
			if ( self::is_active( $plugin ) )
				add_action( 'wp', array( 'NelioABGoogleAnalyticsSupport',
					'wp_google_analytics_events' ) );

			// Support with Google Analytics by Yoast
			// TODO Implement this in the future
		}

		public static function delay_execution( $script ) {
			$open = '<script\1>NelioAB.delay(function(){' . "\n";
			$close = '})</script>';

			$script = preg_replace( '/<script([^>]*)>/', $open, $script );
			$script = str_replace( '</script>', $close, $script );
			return $script;
		}


		// ==========================================================================
		// ==========================================================================
		// WP GOOGLE ANALYTICS EVENTS
		// ==========================================================================
		// ==========================================================================

		public static function wp_google_analytics_events() {
			// Remove the original hooks
			remove_action( 'wp_head', 'ga_events_header', 100 );
			remove_action( 'wp_footer', 'ga_events_footer', 100 );

			// Create our own hooks, where the scripts are properly replaced
			add_action( 'wp_head',
				array( 'NelioABGoogleAnalyticsSupport', 'ga_events_header' ) );
			add_action( 'wp_footer',
				array( 'NelioABGoogleAnalyticsSupport', 'ga_events_footer' ) );
		}

		public static function ga_events_header() {
			if ( !function_exists( 'ga_events_header' ) )
				return;
			ob_start();
			ga_events_header();
			$script = ob_get_contents();
			ob_end_clean();
			echo self::delay_execution( $script );
		}

		public static function ga_events_footer() {
			if ( !function_exists( 'ga_events_footer' ) )
				return;
			ob_start();
			ga_events_footer();
			$script = ob_get_contents();
			ob_end_clean();
			echo self::delay_execution( $script );
		}



		// ==========================================================================
		// ==========================================================================
		// WP GOOGLE ANALYTICS EVENTS
		// ==========================================================================
		// ==========================================================================
		// TODO Add the required functions

	}//NelioABGoogleAnalyticsSupport

}

