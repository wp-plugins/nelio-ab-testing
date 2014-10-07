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


if( !class_exists( 'NelioABSettings' ) ) {

	class NelioABSettings {

		const ALGORITHM_PURE_RANDOM         = 0;
		const ALGORITHM_PRIORITIZE_ORIGINAL = 1;
		const ALGORITHM_GREEDY              = 2;

		const DEFAULT_CONVERSION_VALUE            = 25;
		const DEFAULT_CONVERSION_UNIT             = 'USD';
		const DEFAULT_USE_COLORBLIND_PALETTE      = false;
		const DEFAULT_SHOW_FINISHED_EXPERIMENTS   = false;
		const DEFAULT_USE_PHP_COOKIES             = false;
		const DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE = 90;
		const DEFAULT_PERCENTAGE_OF_TESTED_USERS  = 100;
		const DEFAULT_EXPL_RATIO                  = 90;
		const DEFAULT_ORIGINAL_PERCENTAGE         = 60;

		/**
		 * @deprecated
		 */
		const DEFAULT_IS_GREEDY_ENABLED           = false;

		public static function get_settings() {
			return get_option( 'nelioab_settings', array() );
		}

		public static function is_field_enabled_for_current_plan( $field_name ) {
			$plan = NelioABAccountSettings::get_subscription_plan();

			if ( $plan < NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN ) {
				// Nothing here
			}

			if ( $plan < NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) {
				switch ( $field_name )  {
					case 'expl_ratio':
					case 'ori_perc':
					case 'min_confidence_for_significance':
					case 'algorithm':
					case 'perc_of_tested_users':
						return false;
				}
			}

			return true;
		}

		public static function sanitize( $input ) {
			$new_input = array();

			$new_input['def_conv_value'] = self::DEFAULT_CONVERSION_VALUE;
			if( isset( $input['def_conv_value'] ) )
				$new_input['def_conv_value'] = sanitize_text_field( $input['def_conv_value'] );

			$new_input['conv_unit'] = self::DEFAULT_CONVERSION_UNIT;
			if( isset( $input['conv_unit'] ) )
				$new_input['conv_unit'] = sanitize_text_field( $input['conv_unit'] );

			$new_input['use_colorblind'] = self::DEFAULT_USE_COLORBLIND_PALETTE;
			if( isset( $input['use_colorblind'] ) ) {
				$new_input['use_colorblind'] = sanitize_text_field( $input['use_colorblind'] );
				$new_input['use_colorblind'] = $new_input['use_colorblind'] == '1';
			}

			$new_input['show_finished_experiments'] = self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
			if( isset( $input['show_finished_experiments'] ) ) {
				$new_input['show_finished_experiments'] = sanitize_text_field( $input['show_finished_experiments'] );
				$new_input['show_finished_experiments'] = $new_input['show_finished_experiments'] == '1';
			}

			$new_input['algorithm'] = self::ALGORITHM_PURE_RANDOM;
			if( isset( $input['algorithm'] ) )
				$new_input['algorithm'] = intval( $input['algorithm'] );

			$new_input['expl_ratio'] = self::DEFAULT_EXPL_RATIO;
			if( isset( $input['expl_ratio'] ) )
				$new_input['expl_ratio'] = intval( $input['expl_ratio'] );

			$new_input['ori_perc'] = self::DEFAULT_ORIGINAL_PERCENTAGE;
			if( isset( $input['ori_perc'] ) )
				$new_input['ori_perc'] = intval( $input['ori_perc'] );

			$new_input['use_php_cookies'] = self::DEFAULT_USE_PHP_COOKIES;
			if( isset( $input['use_php_cookies'] ) ) {
				$new_input['use_php_cookies'] = sanitize_text_field( $input['use_php_cookies'] );
				$new_input['use_php_cookies'] = $new_input['use_php_cookies'] == '1';
			}

			$new_input['min_confidence_for_significance'] = self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			if( isset( $input['min_confidence_for_significance'] ) )
				$new_input['min_confidence_for_significance'] = intval( $input['min_confidence_for_significance'] );
			if ( 100 == $new_input['min_confidence_for_significance'] )
				$new_input['min_confidence_for_significance'] = 99;

			$new_input['perc_of_tested_users'] = self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
			if( isset( $input['perc_of_tested_users'] ) )
				$new_input['perc_of_tested_users'] = intval( $input['perc_of_tested_users'] );

			return $new_input;
		}

		public static function get_def_conv_value() {
			if ( !self::is_field_enabled_for_current_plan( 'def_conv_value' ) )
				return self::DEFAULT_CONVERSION_VALUE;
			$options = self::get_settings();
			$result = '';
			if ( isset( $options['def_conv_value'] ) )
				$result = $options['def_conv_value'];
			if ( strlen( $result ) == 0 )
				$result = self::DEFAULT_CONVERSION_VALUE;
			return $result;
		}

		public static function is_performance_muplugin_installed() {
			$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
			$mu_dir = untrailingslashit( $mu_dir );
			$mu_plugin = $mu_dir . '/nelioab-performance.php';
			return file_exists( $mu_plugin );
		}

		public static function toggle_performance_muplugin_installation() {
			$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
			$mu_dir = untrailingslashit( $mu_dir );
			$source = NELIOAB_ROOT_DIR . '/mu-plugins/nelioab-performance.php';
			$dest = $mu_dir . '/nelioab-performance.php';

			if ( !self::is_performance_muplugin_installed() ) {
				$result = self::install_performance_muplugin( $mu_dir, $source, $dest );
			}
			elseif ( !self::is_performance_muplugin_up_to_date() ) {
				$result = self::uninstall_performance_muplugin( $dest );
				if ( $result['status'] !== 'ERROR' )
					$result = self::install_performance_muplugin( $mu_dir, $source, $dest );
			}
			else {
				$result = self::uninstall_performance_muplugin( $dest );
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $result );
			die();
		}

		public static function update_performance_muplugin() {
			$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
			$mu_dir = untrailingslashit( $mu_dir );
			$source = NELIOAB_ROOT_DIR . '/mu-plugins/nelioab-performance.php';
			$dest = $mu_dir . '/nelioab-performance.php';
			$result = self::uninstall_performance_muplugin( $dest );
			if ( $result['status'] !== 'ERROR' )
				$result = self::install_performance_muplugin( $mu_dir, $source, $dest );
		}

		private static function install_performance_muplugin( $mu_dir, $source, $dest ) {
			$result = array( 'status' => 'OK', 'error' => '' );
			if ( !wp_mkdir_p( $mu_dir ) ) {
				$result['error'] = sprintf(
					__( '<strong>Error!</strong> The following directory could not be created: <code>%s</code>.', 'nelioab' ),
					$mu_dir );
				$result['status'] = 'ERROR';
			}
			if ( $result['status'] !== 'ERROR' && !copy( $source, $dest ) ) {
				$result['error'] = sprintf(
					__( '<strong>Error!</strong> Could not copy Nelio\'s performance MU-Plugin from <code>%1$s</code> to <code>%2$s</code>.', 'nelioab' ),
					$source, $dest );
				$result['status'] = 'ERROR';
			}
			return $result;
		}

		private static function uninstall_performance_muplugin( $dest ) {
			$result = array( 'status' => 'OK', 'error' => '' );
			if ( file_exists( $dest ) && !unlink( $dest ) ) {
				$result['error'] = sprintf(
						__( '<strong>Error!</strong> Could not remove the Nelio\'s performance MU-Plugin from <code>%s</code>.', 'nelioab' ),
					$dest );
				$result['status'] = 'ERROR';
			}
			return $result;
		}

		public static function is_performance_muplugin_up_to_date() {
			if ( !is_admin() || !function_exists( 'get_plugin_data' ) )
				return true;

			$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
			$mu_dir = untrailingslashit( $mu_dir );
			$mu_plugin = $mu_dir . '/nelioab-performance.php';

			if ( !file_exists( $mu_plugin ) )
				return true;

			$mu_plugin_data = get_plugin_data( $mu_plugin );
			$installed_version = $mu_plugin_data['Version'];

			$source = NELIOAB_ROOT_DIR . '/mu-plugins/nelioab-performance.php';
			$source_data = get_plugin_data( $source );
			$new_version = $source_data['Version'];

			if ( $new_version === $installed_version )
				return true;
			else
				return false;
		}

		public static function get_conv_unit() {
			if ( !self::is_field_enabled_for_current_plan( 'conv_unit' ) )
				return self::DEFAULT_CONVERSION_UNIT;
			$options = self::get_settings();
			$result = '';
			if ( isset( $options['conv_unit'] ) )
				$result = $options['conv_unit'];
			if ( strlen( $result ) == 0 )
				$result = self::DEFAULT_CONVERSION_UNIT;
			return $result;
		}

		public static function get_original_percentage() {
			if ( !self::is_field_enabled_for_current_plan( 'ori_perc' ) )
				return self::DEFAULT_ORIGINAL_PERCENTAGE;
			$options = self::get_settings();
			if ( isset( $options['ori_perc'] ) )
				return $options['ori_perc'];
			return self::DEFAULT_ORIGINAL_PERCENTAGE;
		}

		public static function get_exploitation_percentage() {
			if ( !self::is_field_enabled_for_current_plan( 'expl_ratio' ) )
				return self::DEFAULT_EXPL_RATIO;
			$options = self::get_settings();
			if ( isset( $options['expl_ratio'] ) )
				return $options['expl_ratio'];
			return self::DEFAULT_EXPL_RATIO;
		}

		public static function use_colorblind_palette() {
			if ( !self::is_field_enabled_for_current_plan( 'use_colorblind' ) )
				return self::DEFAULT_USE_COLORBLIND_PALETTE;
			$options = self::get_settings();
			if ( isset( $options['use_colorblind'] ) )
				return $options['use_colorblind'];
			return self::DEFAULT_USE_COLORBLIND_PALETTE;
		}

		public static function show_finished_experiments() {
			if ( !self::is_field_enabled_for_current_plan( 'show_finished_experiments' ) )
				return self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
			$options = self::get_settings();
			if ( isset( $options['show_finished_experiments'] ) )
				return $options['show_finished_experiments'];
			return self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
		}

		public static function use_php_cookies() {
			if ( !self::is_field_enabled_for_current_plan( 'use_php_cookies' ) )
				return self::DEFAULT_USE_PHP_COOKIES;
			$options = self::get_settings();
			if ( isset( $options['use_php_cookies'] ) )
				return $options['use_php_cookies'];
			return self::DEFAULT_USE_PHP_COOKIES;
		}


		public static function get_min_confidence_for_significance() {
			if ( !self::is_field_enabled_for_current_plan( 'min_confidence_for_significance' ) )
				return self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			$options = self::get_settings();
			if ( isset( $options['min_confidence_for_significance'] ) )
				return $options['min_confidence_for_significance'];
			return self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
		}

		public static function get_percentage_of_tested_users() {
			if ( !self::is_field_enabled_for_current_plan( 'perc_of_tested_users' ) )
				return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
			$options = self::get_settings();
			if ( isset( $options['perc_of_tested_users'] ) )
				return $options['perc_of_tested_users'];
			return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
		}

		public static function get_algorithm() {
			if ( !self::is_field_enabled_for_current_plan( 'algorithm' ) )
				return self::ALGORITHM_PURE_RANDOM;
			$options = self::get_settings();
			if ( isset( $options['algorithm'] ) )
				return $options['algorithm'];

			/**
			 * We need this check for if the user comes from a previous version. We'll have to
			 * delete it someday in the future.
			 * @deprecated
			 */
			if ( isset( $options['greedy_enabled'] ) && $options['greedy_enabled'] )
				return self::ALGORITHM_GREEDY;

			return self::ALGORITHM_PURE_RANDOM;
		}

		public static function cookie_prefix() {
			return 'nelioab_';
		}

		public static function set_copy_metadata( $enabled ) {
			update_option( 'nelioab_copy_metadata', $enabled );
		}

		public static function is_copying_metadata_enabled() {
			return get_option( 'nelioab_copy_metadata', true );
		}

		public static function set_copy_tags( $enabled ) {
			update_option( 'nelioab_copy_tags', $enabled );
		}

		public static function is_copying_tags_enabled() {
			return get_option( 'nelioab_copy_tags', true );
		}

		public static function set_copy_categories( $enabled ) {
			update_option( 'nelioab_copy_categories', $enabled );
		}

		public static function is_copying_categories_enabled() {
			return get_option( 'nelioab_copy_categories', true );
		}

		public static function is_upgrade_message_visible() {
			if ( NelioABAccountSettings::get_subscription_plan() != NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN )
				return false;
			$result = get_option( 'nelioab_hide_upgrade_message', false );
			if ( !$result )
				return true;
			else
				return false;
		}

		public static function hide_upgrade_message() {
			update_option( 'nelioab_hide_upgrade_message', NELIOAB_PLUGIN_VERSION );
		}

	}//NelioABSettings

}

