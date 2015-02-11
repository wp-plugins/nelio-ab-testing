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


if ( !class_exists( 'NelioABSettings' ) ) {

	class NelioABSettings {

		const ALGORITHM_PURE_RANDOM         = 0;
		const ALGORITHM_PRIORITIZE_ORIGINAL = 1;
		const ALGORITHM_GREEDY              = 2;

		const FINISHED_EXPERIMENTS_HIDE_ALL    = 0;
		const FINISHED_EXPERIMENTS_SHOW_ALL    = 1;
		const FINISHED_EXPERIMENTS_SHOW_RECENT = 2;

		const NOTIFICATION_EXP_FINALIZATION = 'exp-finalization';

		const MENU_LOCATION_DASHBOARD  = 2;
		const MENU_LOCATION_APPEARANCE = 59;
		const MENU_LOCATION_TOOLS      = 75;
		const MENU_LOCATION_LAST_BLOCK = 99;
		const MENU_LOCATION_END        = 9999;

		const HEADLINES_QUOTA_MODE_ALWAYS        = 0;
		const HEADLINES_QUOTA_MODE_ON_FRONT_PAGE = 1;

		const GET_ALTERNATIVE_LOADING_MODE  = 'GET';
		const POST_ALTERNATIVE_LOADING_MODE = 'POST';

		const PLUGIN_AVAILABLE_TO_ANY_ADMIN    = 'any-admin';
		const PLUGIN_AVAILABLE_TO_SUPER_ADMIN  = 'super-admin';
		const PLUGIN_AVAILABLE_TO_SITE_SETTING = 'inherit-multisite-setting';

		const ELEMENT_BASED_HEATMAP_TRACKING = 'ELEM_HEATMAP_TRACKING';
		const HTML_BASED_HEATMAP_TRACKING    = 'HTML_HEATMAP_TRACKING';

		const DEFAULT_CONVERSION_VALUE            = 25;
		const DEFAULT_CONVERSION_UNIT             = '$';
		const DEFAULT_USE_COLORBLIND_PALETTE      = false;
		const DEFAULT_SHOW_FINISHED_EXPERIMENTS   = 2;
		const DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE = 95;
		const DEFAULT_PERCENTAGE_OF_TESTED_USERS  = 100;
		const DEFAULT_EXPL_RATIO                  = 90;
		const DEFAULT_ORIGINAL_PERCENTAGE         = 60;
		const DEFAULT_QUOTA_LIMIT_PER_EXP         = -1;
		const DEFAULT_MENU_LOCATION               = 2;
		const DEFAULT_MENU_IN_ADMIN_BAR           = true;
		const DEFAULT_NOTIFICATIONS               = ' exp-finalization ';
		const DEFAULT_MAKE_SITE_CONSISTENT        = true;
		const DEFAULT_ALTERNATIVE_LOADING_MODE    = self::POST_ALTERNATIVE_LOADING_MODE;
		const DEFAULT_HEATMAP_TRACKING_MODE       = self::ELEMENT_BASED_HEATMAP_TRACKING;
		const DEFAULT_THEME_LANDING_PAGE          = false;
		const DEFAULT_OUTWARDS_NAVIGATION_BLANK   = true;
		const DEFAULT_PLUGIN_AVAILABLE_TO         = self::PLUGIN_AVAILABLE_TO_SITE_SETTING;

		const CONVERSION_UNIT_DOLLAR  = '$';
		const CONVERSION_UNIT_EURO    = '€';
		const CONVERSION_UNIT_POUND   = '£';
		const CONVERSION_UNIT_YEN     = '¥';
		const CONVERSION_UNIT_BITCOIN = 'B⃦';


		/**
		 * @deprecated
		 */
		const DEFAULT_IS_GREEDY_ENABLED = false;

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
					case 'quota_limit_per_exp':
					case 'notifications':
					case 'headlines_quota_mode':
					case 'plugin_available_to':
						return false;
				}
			}

			return true;
		}

		public static function sanitize( $input ) {
			$new_input = array();

			if ( isset( $input['reset_settings'] ) && 'do_reset' == $input['reset_settings'] )
				return $new_input;

			$new_input['def_conv_value'] = self::DEFAULT_CONVERSION_VALUE;
			if ( isset( $input['def_conv_value'] ) )
				$new_input['def_conv_value'] = sanitize_text_field( $input['def_conv_value'] );

			$new_input['plugin_available_to'] = self::DEFAULT_PLUGIN_AVAILABLE_TO;
			if ( isset( $input['plugin_available_to'] ) )
				$new_input['plugin_available_to'] = sanitize_text_field( $input['plugin_available_to'] );

			$new_input['conv_unit'] = self::DEFAULT_CONVERSION_UNIT;
			if ( isset( $input['conv_unit'] ) )
				$new_input['conv_unit'] = sanitize_text_field( $input['conv_unit'] );

			$new_input['use_colorblind'] = self::DEFAULT_USE_COLORBLIND_PALETTE;
			if ( isset( $input['use_colorblind'] ) ) {
				$new_input['use_colorblind'] = sanitize_text_field( $input['use_colorblind'] );
				$new_input['use_colorblind'] = $new_input['use_colorblind'] == '1';
			}

			$new_input['make_site_consistent'] = self::DEFAULT_MAKE_SITE_CONSISTENT;
			if ( isset( $input['make_site_consistent'] ) ) {
				$new_input['make_site_consistent'] = sanitize_text_field( $input['make_site_consistent'] );
				$new_input['make_site_consistent'] = $new_input['make_site_consistent'] == '1';
			}

			$new_input['alt_load_mode'] = self::DEFAULT_ALTERNATIVE_LOADING_MODE;
			if ( isset( $input['alt_load_mode'] ) )
				$new_input['alt_load_mode'] = sanitize_text_field( $input['alt_load_mode'] );

			$new_input['hm_tracking_mode'] = self::DEFAULT_HEATMAP_TRACKING_MODE;
			if ( isset( $input['hm_tracking_mode'] ) )
				$new_input['hm_tracking_mode'] = sanitize_text_field( $input['hm_tracking_mode'] );

			$new_input['theme_landing_page'] = self::DEFAULT_THEME_LANDING_PAGE;
			if ( isset( $input['theme_landing_page'] ) ) {
				$new_input['theme_landing_page'] = sanitize_text_field( $input['theme_landing_page'] );
				$new_input['theme_landing_page'] = $new_input['theme_landing_page'] == '1';
			}

			$new_input['on_blank'] = self::DEFAULT_OUTWARDS_NAVIGATION_BLANK;
			if ( isset( $input['on_blank'] ) ) {
				$new_input['on_blank'] = sanitize_text_field( $input['on_blank'] );
				$new_input['on_blank'] = $new_input['on_blank'] == '1';
			}

			$new_input['show_finished_experiments'] = self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
			if ( isset( $input['show_finished_experiments'] ) )
				$new_input['show_finished_experiments'] = intval( $input['show_finished_experiments'] );

			$new_input['algorithm'] = self::ALGORITHM_PURE_RANDOM;
			if ( isset( $input['algorithm'] ) )
				$new_input['algorithm'] = intval( $input['algorithm'] );

			$new_input['expl_ratio'] = self::DEFAULT_EXPL_RATIO;
			if ( isset( $input['expl_ratio'] ) )
				$new_input['expl_ratio'] = intval( $input['expl_ratio'] );

			$new_input['ori_perc'] = self::DEFAULT_ORIGINAL_PERCENTAGE;
			if ( isset( $input['ori_perc'] ) )
				$new_input['ori_perc'] = intval( $input['ori_perc'] );

			$new_input['min_confidence_for_significance'] = self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			if ( isset( $input['min_confidence_for_significance'] ) )
				$new_input['min_confidence_for_significance'] = intval( $input['min_confidence_for_significance'] );
			if ( 100 == $new_input['min_confidence_for_significance'] )
				$new_input['min_confidence_for_significance'] = 99;

			$new_input['perc_of_tested_users'] = self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
			if ( isset( $input['perc_of_tested_users'] ) )
				$new_input['perc_of_tested_users'] = intval( $input['perc_of_tested_users'] );

			$new_input['menu_location'] = self::DEFAULT_MENU_LOCATION;
			if ( isset( $input['menu_location'] ) )
				$new_input['menu_location'] = intval( $input['menu_location'] );

			$new_input['menu_in_admin_bar'] = self::DEFAULT_MENU_IN_ADMIN_BAR;
			if ( isset( $input['menu_in_admin_bar'] ) )
				$new_input['menu_in_admin_bar'] = intval( $input['menu_in_admin_bar'] ) == 1;

			$new_input['headlines_quota_mode'] = self::HEADLINES_QUOTA_MODE_ALWAYS;
			if ( isset( $input['headlines_quota_mode'] ) )
				$new_input['headlines_quota_mode'] = intval( $input['headlines_quota_mode'] );

			// SYNC SOME SETTINGS WITH GOOGLE APP ENGINE
			try {
				$limit = self::get_quota_limit_per_exp();
				if ( isset( $input['quota_limit_per_exp'] ) )
					$limit = intval( $input['quota_limit_per_exp'] );

				$email = '';
				if ( isset( $input['notification_email'] ) )
					$email = trim( $input['notification_email'] );

				$notifications = ' ';
				if ( isset( $input['notify_exp_finalization'] ) && 'on' == $input['notify_exp_finalization'] )
					$notifications .= self::NOTIFICATION_EXP_FINALIZATION . ' ';

				// Attributes to control if sync was OK
				$new_input['quota_limit_per_exp'] = self::get_quota_limit_per_exp();
				$new_input['notification_email']  = self::get_notification_email();
				$new_input['notifications']       = self::get_notifications();

				$new_input['try_quota_limit_per_exp'] = $limit;
				$new_input['try_notification_email']  = $email;
				$new_input['try_notifications']       = $notifications;

				// Send data to Google
				$url = sprintf(
						NELIOAB_BACKEND_URL . '/site/%s/settings',
						NelioABAccountSettings::get_site_id()
					);
				$object = array(
						'notificationEmail' => $email,
						'notifications'     => $notifications,
						'quotaLimit'        => $limit,
					);
				$result = NelioABBackend::remote_post( $url, $object );

				$new_input['quota_limit_per_exp'] = $limit;
				$new_input['notification_email']  = $email;
				$new_input['notifications']  = $notifications;
			}
			catch ( Exception $e ) {
			}

			return $new_input;
		}

		public static function get_alternative_loading_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'alt_load_mode' ) )
				return self::DEFAULT_ALTERNATIVE_LOADING_MODE;
			$options = self::get_settings();
			if ( isset( $options['alt_load_mode'] ) )
				return $options['alt_load_mode'];
			return self::DEFAULT_ALTERNATIVE_LOADING_MODE;
		}

		public static function get_heatmap_tracking_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'hm_tracking_mode' ) )
				return self::DEFAULT_HEATMAP_TRACKING_MODE;
			$options = self::get_settings();
			if ( isset( $options['hm_tracking_mode'] ) )
				return $options['hm_tracking_mode'];
			return self::DEFAULT_HEATMAP_TRACKING_MODE;
		}

		public static function get_unsync_fields( $new_options = false ) {
			$options = self::get_settings();
			if ( !$new_options )
				$new_options = $options;
			$names = array( 'quota_limit_per_exp', 'notification_email', 'notifications' );
			$result = array();
			foreach ( $names as $n ) {
				if ( !isset( $new_options['try_' . $n] ) )
					continue;
				if ( !isset( $options[$n] ) || $options[$n] !== $new_options['try_' . $n] )
					array_push( $result, $n );
			}
			return $result;
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

		public static function set_site_option_regular_admins_can_manage_plugin( $value ) {
			update_site_option( 'nelioab_regular_admins_can_manage_plugin', $value );
		}

		public static function get_site_option_regular_admins_can_manage_plugin() {
			return get_site_option( 'nelioab_regular_admins_can_manage_plugin', true );
		}

		public static function regular_admins_can_manage_plugin() {
			if ( !is_multisite() )
				return true;
			$available_to = self::get_plugin_available_to();
			switch ( $available_to ) {
				case self::PLUGIN_AVAILABLE_TO_SUPER_ADMIN:
					return false;
				case self::PLUGIN_AVAILABLE_TO_ANY_ADMIN:
					return true;
				case self::PLUGIN_AVAILABLE_TO_SITE_SETTING:
					return self::get_site_option_regular_admins_can_manage_plugin();
			}
			return false;
		}

		public static function get_plugin_available_to() {
			if ( !self::is_field_enabled_for_current_plan( 'plugin_available_to' ) )
				return self::DEFAULT_PLUGIN_AVAILABLE_TO;
			$options = self::get_settings();
			if ( isset( $options['plugin_available_to'] ) )
				return $options['plugin_available_to'];
			return self::DEFAULT_PLUGIN_AVAILABLE_TO;
		}

		public static function get_conv_unit() {
			if ( !self::is_field_enabled_for_current_plan( 'conv_unit' ) )
				return self::DEFAULT_CONVERSION_UNIT;
			$options = self::get_settings();
			if ( isset( $options['conv_unit'] ) )
				return $options['conv_unit'];
			return self::DEFAULT_CONVERSION_UNIT;
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

		public static function make_site_consistent() {
			if ( !self::is_field_enabled_for_current_plan( 'make_site_consistent' ) )
				return self::DEFAULT_MAKE_SITE_CONSISTENT;
			$options = self::get_settings();
			if ( isset( $options['make_site_consistent'] ) )
				return $options['make_site_consistent'];
			return self::DEFAULT_MAKE_SITE_CONSISTENT;
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

		public static function get_min_confidence_for_significance() {
			if ( !self::is_field_enabled_for_current_plan( 'min_confidence_for_significance' ) )
				return self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			$options = self::get_settings();
			$confidence = self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			if ( isset( $options['min_confidence_for_significance'] ) )
				$confidence = $options['min_confidence_for_significance'];
			if ( $confidence < 90 )
				$confidence = 90;
			return $confidence;
		}

		public static function get_percentage_of_tested_users() {
			if ( !self::is_field_enabled_for_current_plan( 'perc_of_tested_users' ) )
				return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
			$options = self::get_settings();
			if ( isset( $options['perc_of_tested_users'] ) )
				return $options['perc_of_tested_users'];
			return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
		}

		public static function get_quota_limit_per_exp() {
			if ( !self::is_field_enabled_for_current_plan( 'quota_limit_per_exp' ) )
				return self::DEFAULT_QUOTA_LIMIT_PER_EXP;
			$options = self::get_settings();
			if ( isset( $options['quota_limit_per_exp'] ) )
				return $options['quota_limit_per_exp'];
			return self::DEFAULT_QUOTA_LIMIT_PER_EXP;
		}

		public static function get_notification_email() {
			if ( !self::is_field_enabled_for_current_plan( 'notification_email' ) )
				return '';
			$options = self::get_settings();
			if ( isset( $options['notification_email'] ) )
				return $options['notification_email'];
			return '';
		}

		public static function get_notifications() {
			if ( !self::is_field_enabled_for_current_plan( 'notifications' ) )
				return self::DEFAULT_NOTIFICATIONS;
			$options = self::get_settings();
			if ( isset( $options['notifications'] ) )
				return $options['notifications'];
			return self::DEFAULT_NOTIFICATIONS;
		}

		public static function is_notification_enabled( $notification ) {
			return strpos( self::get_notifications(), ' ' . $notification . ' ' ) !== false;
		}

		public static function get_menu_location() {
			if ( !self::is_field_enabled_for_current_plan( 'menu_location' ) )
				return self::DEFAULT_MENU_LOCATION;
			$options = self::get_settings();
			if ( isset( $options['menu_location'] ) )
				return $options['menu_location'];
			return self::DEFAULT_MENU_LOCATION;
		}

		public static function is_menu_enabled_for_admin_bar() {
			if ( !self::is_field_enabled_for_current_plan( 'menu_in_admin_bar' ) )
				return self::DEFAULT_MENU_IN_ADMIN_BAR;
			$options = self::get_settings();
			if ( isset( $options['menu_in_admin_bar'] ) )
				return $options['menu_in_admin_bar'];
			return self::DEFAULT_MENU_IN_ADMIN_BAR;
		}

		public static function does_theme_use_a_custom_landing_page() {
			if ( !self::is_field_enabled_for_current_plan( 'theme_landing_page' ) )
				return self::DEFAULT_THEME_LANDING_PAGE;
			$options = self::get_settings();
			if ( isset( $options['theme_landing_page'] ) )
				return $options['theme_landing_page'];
			return self::DEFAULT_THEME_LANDING_PAGE;
		}

		public static function use_outwards_navigations_blank() {
			if ( !self::is_field_enabled_for_current_plan( 'on_blank' ) )
				return self::DEFAULT_OUTWARDS_NAVIGATION_BLANK;
			$options = self::get_settings();
			if ( isset( $options['on_blank'] ) )
				return $options['on_blank'];
			return self::DEFAULT_OUTWARDS_NAVIGATION_BLANK;
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

		public static function get_headlines_quota_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'headlines_quota_mode' ) )
				return self::HEADLINES_QUOTA_MODE_ALWAYS;
			$options = self::get_settings();
			if ( isset( $options['headlines_quota_mode'] ) )
				return $options['headlines_quota_mode'];
			return self::HEADLINES_QUOTA_MODE_ALWAYS;
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

