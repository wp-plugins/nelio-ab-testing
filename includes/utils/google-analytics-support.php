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

if ( !class_exists( 'NelioABGoogleAnalyticsSupport' ) ) {

	/**
	 * This class helps us make sure that Google Analytics scripts are included after Nelio's.
	 *
	 * @since 4.1.0
	 */
	abstract class NelioABGoogleAnalyticsSupport {

		/**
		 * This function checks whether the given plugin <code>$plugin</code> is
		 * active or not.
		 *
		 * @since 4.1.0
		 *
		 * @param string $plugin The name of the plugin we want to check.
		 *                       It usually follows the form
		 *                       `plugin-dir/main-file.php`.
		 *
		 * @return boolean Whether the given plugin is active or not.
		 *
		 * @since PHPDOC
		 */
		private static function is_plugin_active( $plugin ) {
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
		}


		/**
		 * This function checks whether there is some Google Analytics plugin enabled.
		 *
		 * Supported plugins include:
		 *
		 * * Google Analytics by Yoast
		 *
		 * @return void
		 *
		 * @since 4.1.0
		 */
		public static function move_google_analytics_after_nelio() {
			/** @var string $plugin */

			// Google Analytics by Yoast
			$plugin = 'google-analytics-for-wordpress/googleanalytics.php';
			if ( self::is_plugin_active( $plugin ) ) {
				add_filter( 'wp_enqueue_scripts', array(
					'NelioABGoogleAnalyticsSupport',
					'relocate_google_analytics_by_yoast' ), 99 );
			}
		}


		/**
		 * This function moves Google Analytics by Yoast after Nelio scripts.
		 *
		 * @return void
		 *
		 * @since 4.1.0
		 */
		public static function relocate_google_analytics_by_yoast() {
			global $wp_filter;
			/** @var array $func */
			$func = array();

			foreach ( $wp_filter['wp_head'][8] as $key => $value ) {
				if ( is_array( $value['function'] ) ) {
					$func = $value['function'];
					/** @noinspection PhpUndefinedClassInspection */
					if ( $func[0] instanceof Yoast_GA_Tracking && $func[1] == 'tracking' )
						break;
				}
			}

			// If we found GA by Yoast (we should have), we reduce its priority
			if ( count( $func ) == 2 ) {
				remove_action( 'wp_head', $func, 8 );
				add_action( 'wp_head', $func, 10 );
			}
		}

	}//NelioABGoogleAnalyticsSupport

}

