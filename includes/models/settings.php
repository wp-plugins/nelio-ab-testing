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


if ( !class_exists( 'NelioABSettings' ) ) {

	/**
	 * This class contains all the settings of our plugin.
	 *
	 * @package \NelioABTesting\Models\Settings
	 * @since 2.1.0
	 */
	class NelioABSettings {

		/**
		 * Constant for the pure random algorithm.
		 *
		 * @since 3.2.0
		 * @var string
		 */
		const ALGORITHM_PURE_RANDOM = 'r';


		/**
		 * Constant for the algorithm that prioritizes the original version.
		 *
		 * @since 3.2.0
		 * @var string
		 */
		const ALGORITHM_PRIORITIZE_ORIGINAL = 'p';


		/**
		 * Constant for the greedy algorithm.
		 *
		 * @since 3.2.0
		 * @var string
		 */
		const ALGORITHM_GREEDY = 'g';


		/**
		 * Constant for hiding all finished experiments from the Experiments page.
		 *
		 * @since 3.2.1
		 * @var int
		 */
		const FINISHED_EXPERIMENTS_HIDE_ALL = 0;


		/**
		 * Constant for showing all finished experiments in the Experiments page.
		 *
		 * @since 3.2.1
		 * @var int
		 */
		const FINISHED_EXPERIMENTS_SHOW_ALL = 1;


		/**
		 * Constant for showing only the recently-finished experiments in the Experiments page.
		 *
		 * @since 3.2.1
		 * @var int
		 */
		const FINISHED_EXPERIMENTS_SHOW_RECENT = 2;


		const NOTIFICATION_EXP_FINALIZATION = 'exp-finalization';


		/**
		 * Constant for locating Nelio A/B Testing's menu under the Dashboard.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const MENU_LOCATION_DASHBOARD = 2;


		/**
		 * Constant for locating Nelio A/B Testing's menu over the appearance section.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const MENU_LOCATION_APPEARANCE = 59;


		/**
		 * Constant for locating Nelio A/B Testing's menu under the under the tools entry.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const MENU_LOCATION_TOOLS = 75;


		/**
		 * Constant for locating Nelio A/B Testing's menu as the first element of the last block.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const MENU_LOCATION_LAST_BLOCK = 99;


		/**
		 * Constant for locating Nelio A/B Testing's menu in the end.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const MENU_LOCATION_END = 9999;


		/**
		 * Constant for counting title views on all pages.
		 *
		 * @since 3.3.8
		 * @var int
		 */
		const HEADLINES_QUOTA_MODE_ALWAYS = 0;


		/**
		 * Constant for counting title views on the front page only.
		 *
		 * @since 3.3.8
		 * @var int
		 */
		const HEADLINES_QUOTA_MODE_ON_FRONT_PAGE = 1;


		/**
		 * Constant for making the plugin available to all admin users of a multi-site.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const PLUGIN_AVAILABLE_TO_ANY_ADMIN = 'any-admin';


		/**
		 * Constant for making the plugin available to only super-admin users in a multi-site environment.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const PLUGIN_AVAILABLE_TO_SUPER_ADMIN = 'super-admin';


		/**
		 * Constant for specifying that the plugin availability depends on the super-admin's setting.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const PLUGIN_AVAILABLE_TO_SITE_SETTING = 'inherit-multisite-setting';


		/**
		 * Constant for tracking heatmaps based on the elements behind the cursor.
		 *
		 * @since 3.3.0
		 * @var string
		 */
		const ELEMENT_BASED_HEATMAP_TRACKING = 'ELEM_HEATMAP_TRACKING';


		/**
		 * Constant for tracking heatmaps based on the HTML/body tags.
		 *
		 * @since 3.3.0
		 * @var string
		 */
		const HTML_BASED_HEATMAP_TRACKING = 'HTML_HEATMAP_TRACKING';


		/**
		 * Constant for hiding all Nelio GET tracking parameters.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const GET_PARAMS_VISIBILITY_HIDE_ALL = 'all';


		/**
		 * Constant for showing all Nelio GET tracking parameters.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const GET_PARAMS_VISIBILITY_HIDE_NONE = 'none';


		/**
		 * Constant for hiding only the context GET tracking parameters (that is, "nabe").
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const GET_PARAMS_VISIBILITY_HIDE_CONTEXT = 'context';


		/**
		 * Constant for grouping running experiments into groups.
		 *
		 * This constant is used to prevent an exponential combination of
		 * alternatives.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const USER_SPLIT = 'split';


		/**
		 * Constant for having all running experiments into one single group.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const USER_ALLIN = 'allin';


		/**
		 * The default value our customer obtains from a Conversion.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const DEFAULT_CONVERSION_VALUE = 25;


		/**
		 * Default money unit of conversions.
		 *
		 * @since 2.1.0
		 * @var string
		 */
		const DEFAULT_CONVERSION_UNIT = '$';


		/**
		 * Colorblind palettes default value.
		 *
		 * @since 2.1.0
		 * @var string
		 */
		const DEFAULT_USE_COLORBLIND_PALETTE = false;


		/**
		 * Default value for showing/hiding finished experiments.
		 *
		 * @since 3.2.0
		 * @var int
		 */
		const DEFAULT_SHOW_FINISHED_EXPERIMENTS = 2;


		/**
		 * Default value for minimum required confidence to consider an experiment "significant".
		 *
		 * @since 3.2.0
		 * @var int
		 */
		const DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE = 95;


		/**
		 * Default percentage of teseted users.
		 *
		 * @since 3.2.0
		 * @var int
		 */
		const DEFAULT_PERCENTAGE_OF_TESTED_USERS = 100;


		/**
		 * Default exploitation ratio.
		 *
		 * @since 2.1.0
		 * @var int
		 */
		const DEFAULT_EXPL_RATIO = 90;


		/**
		 * Default percentage stablishing how likely it is to show the original version (instead of one of the alternatives).
		 *
		 * @since 3.2.0
		 * @var int
		 */
		const DEFAULT_ORIGINAL_PERCENTAGE = 60;


		/**
		 * Default quota limit per experiment (no limit).
		 *
		 * @since 3.2.1
		 * @var int
		 */
		const DEFAULT_QUOTA_LIMIT_PER_EXP = -1;


		/**
		 * Default location for Nelio A/B Testing's menu.
		 *
		 * @since 3.3.0
		 * @var int
		 */
		const DEFAULT_MENU_LOCATION = 2;


		/**
		 * Default value for showing the menu in the admin bar.
		 *
		 * @since 3.3.0
		 * @var boolean
		 */
		const DEFAULT_MENU_IN_ADMIN_BAR = true;


		/**
		 * Default list of enabled notifications.
		 *
		 * @since 3.2.1
		 * @var string
		 */
		const DEFAULT_NOTIFICATIONS = ' exp-finalization ';


		/**
		 * Default value for making site consistent.
		 *
		 * @since 3.3.0
		 * @var boolean
		 */
		const DEFAULT_MAKE_SITE_CONSISTENT = true;


		/**
		 * Default value for tracking heatmaps.
		 *
		 * @since 3.3.0
		 * @var string
		 */
		const DEFAULT_HEATMAP_TRACKING_MODE = self::ELEMENT_BASED_HEATMAP_TRACKING;


		/**
		 * Default value specifying whether the theme uses a custom landing page or not.
		 *
		 * @since 3.4.0
		 * @var boolean
		 */
		const DEFAULT_THEME_LANDING_PAGE = false;


		/**
		 * Default value for opening external links into new tabs.
		 *
		 * @since 3.4.0
		 * @var boolean
		 */
		const DEFAULT_OUTWARDS_NAVIGATION_BLANK = true;


		/**
		 * Default value that specifies who can manage the plugin.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const DEFAULT_PLUGIN_AVAILABLE_TO = self::PLUGIN_AVAILABLE_TO_SITE_SETTING;


		/**
		 * Default value for grouping running experiments into buckets.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const DEFAULT_USER_SPLIT = self::USER_SPLIT;


		/**
		 * Default value for hiding Nelio's GET tracking parameters.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const DEFAULT_GET_PARAMS_VISIBILITY = self::GET_PARAMS_VISIBILITY_HIDE_CONTEXT;


		/**
		 * Dollar symbol.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const CONVERSION_UNIT_DOLLAR = '$';


		/**
		 * Euro symbol.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const CONVERSION_UNIT_EURO = '€';


		/**
		 * Pounds symbol.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const CONVERSION_UNIT_POUND = '£';


		/**
		 * Yen symbol.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const CONVERSION_UNIT_YEN = '¥';


		/**
		 * Bitcoin symbol.
		 *
		 * @since 3.4.0
		 * @var string
		 */
		const CONVERSION_UNIT_BITCOIN = 'B⃦';


		/**
		 * Returns a hashmap with all the Settings of Nelio A/B Testing.
		 *
		 * @return array a hashmap with all the Settings of Nelio A/B Testing.
		 *
		 * @since 2.1.0
		 */
		public static function get_settings() {
			return get_option( 'nelioab_settings', array() );
		}


		/**
		 * Returns whether the field can be edited by subscribers of the current plan.
		 *
		 * @param string $field_name the name of one setting.
		 *
		 * @return boolean whether the field can be edited by subscribers of the current plan.
		 *
		 * @since 3.2.0
		 */
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


		/**
		 * Sanitizes all settings and stores some of them in AppEngine.
		 *
		 * This function parses the settings recovered from the form in the
		 * Settings Page and sanitizes all string values to their appropriate
		 * types. Also, it synchronizes some of these settings with AppEngine.
		 *
		 * @param array $input all settings as recovered from the form in the Settings page.
		 *
		 * @return array the same value as $input, but with the appropriate types.
		 *
		 * @since 2.1.0
		 */
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

			$new_input['min_confidence_for_significance'] = self::DEFAULT_CONFIDENCE_FOR_SIGNIFICANCE;
			if ( isset( $input['min_confidence_for_significance'] ) )
				$new_input['min_confidence_for_significance'] = intval( $input['min_confidence_for_significance'] );
			if ( 100 == $new_input['min_confidence_for_significance'] )
				$new_input['min_confidence_for_significance'] = 99;

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

				$algorithm = self::ALGORITHM_PURE_RANDOM;
				if ( isset( $input['algorithm'] ) )
					$algorithm = sanitize_text_field( $input['algorithm'] );

				$make_site_consistent = self::DEFAULT_MAKE_SITE_CONSISTENT;
				if ( isset( $input['make_site_consistent'] ) ) {
					$make_site_consistent = sanitize_text_field( $input['make_site_consistent'] );
					$make_site_consistent = $make_site_consistent == '1';
				}

				$expl_ratio = self::DEFAULT_EXPL_RATIO;
				if ( isset( $input['expl_ratio'] ) )
					$expl_ratio = intval( $input['expl_ratio'] );

				$get_params_visibility = self::DEFAULT_GET_PARAMS_VISIBILITY;
				if ( isset( $input['get_params_visibility'] ) )
					$get_params_visibility = sanitize_text_field( $input['get_params_visibility'] );

				$hm_tracking_mode = self::DEFAULT_HEATMAP_TRACKING_MODE;
				if ( isset( $input['hm_tracking_mode'] ) )
					$hm_tracking_mode = sanitize_text_field( $input['hm_tracking_mode'] );

				$user_split = self::DEFAULT_USER_SPLIT;
				if ( isset( $input['user_split'] ) )
					$user_split = sanitize_text_field( $input['user_split'] );

				$ori_perc = self::DEFAULT_ORIGINAL_PERCENTAGE;
				if ( isset( $input['ori_perc'] ) )
					$ori_perc = intval( $input['ori_perc'] );

				$perc_of_tested_users = self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
				if ( isset( $input['perc_of_tested_users'] ) )
					$perc_of_tested_users = intval( $input['perc_of_tested_users'] );

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
				$new_input['algorithm']             = self::get_algorithm();
				$new_input['make_site_consistent']  = self::make_site_consistent();
				$new_input['expl_ratio']            = self::get_exploitation_percentage();
				$new_input['get_params_visibility'] = self::get_params_visibility();
				$new_input['hm_tracking_mode']      = self::get_heatmap_tracking_mode();
				$new_input['user_split']            = self::get_split_user_mode();
				$new_input['ori_perc']              = self::get_original_percentage();
				$new_input['perc_of_tested_users']  = self::get_percentage_of_tested_users();
				$new_input['quota_limit_per_exp']   = self::get_quota_limit_per_exp();
				$new_input['notification_email']    = self::get_notification_email();
				$new_input['notifications']         = self::get_notifications();

				$new_input['try_algorithm']             = $algorithm;
				$new_input['try_make_site_consistent']  = $make_site_consistent;
				$new_input['try_expl_ratio']            = $expl_ratio;
				$new_input['try_get_params_visibility'] = $get_params_visibility;
				$new_input['try_hm_tracking_mode']      = $hm_tracking_mode;
				$new_input['try_user_split']            = $user_split;
				$new_input['try_ori_perc']              = $ori_perc;
				$new_input['try_perc_of_tested_users']  = $perc_of_tested_users;
				$new_input['try_quota_limit_per_exp']   = $limit;
				$new_input['try_notification_email']    = $email;
				$new_input['try_notifications']         = $notifications;

				// Send data to Google
				$url = sprintf(
						NELIOAB_BACKEND_URL . '/site/%s/settings',
						NelioABAccountSettings::get_site_id()
					);
				$object = array(
						'algorithm'         => $algorithm,
						'consistency'       => $make_site_consistent,
						'exploitPerc'       => $expl_ratio,
						'hideParams'        => $get_params_visibility,
						'hmMode'            => $hm_tracking_mode,
						'mode'              => $user_split,
						'partChance'        => $perc_of_tested_users,
						'oriPrio'           => $ori_perc,
						'notificationEmail' => $email,
						'notifications'     => $notifications,
						'quotaLimit'        => $limit,
					);
				NelioABBackend::remote_post( $url, $object );

				$new_input['algorithm']             = $algorithm;
				$new_input['make_site_consistent']  = $make_site_consistent;
				$new_input['expl_ratio']            = $expl_ratio;
				$new_input['get_params_visibility'] = $get_params_visibility;
				$new_input['hm_tracking_mode']      = $hm_tracking_mode;
				$new_input['user_split']            = $user_split;
				$new_input['ori_perc']              = $ori_perc;
				$new_input['perc_of_tested_users']  = $perc_of_tested_users;
				$new_input['quota_limit_per_exp']   = $limit;
				$new_input['notification_email']    = $email;
				$new_input['notifications']         = $notifications;
			}
			catch ( Exception $e ) {
			}

			return $new_input;
		}


		/**
		 * Returns a list of options that must be stored in AppEngine but, for some reason, they aren't.
		 *
		 * When changing the settings of our plugin, some of the options must be
		 * synced to AppEngine. If the request for updating those options fails, we
		 * have to notify the user. This operation returns a list with the names of
		 * the fields that could not be synced the last time the options were
		 * edited. If all options were properly synced, the list is empty.
		 *
		 * @return array a list of options that must be stored in AppEngine but, for some reason, they aren't.
		 *
		 * @since 3.2.1
		 */
		public static function get_unsync_fields() {
			$options = self::get_settings();
			$names = array( 'algorithm', 'make_site_consistent', 'expl_ratio',
				'get_params_visibility', 'hm_tracking_mode', 'user_split', 'ori_perc',
				'perc_of_tested_users', 'quota_limit_per_exp', 'notification_email',
				'notifications' );
			$result = array();
			foreach ( $names as $n ) {
				if ( !isset( $options['try_' . $n] ) )
					continue;
				if ( !isset( $options[$n] ) || $options[$n] !== $options['try_' . $n] )
					array_push( $result, $n );
			}
			return $result;
		}


		/**
		 * Returns the heatmap tracking mode.
		 *
		 * @return string the heatmap tracking mode.
		 *
		 * @since 3.3.0
		 */
		public static function get_heatmap_tracking_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'hm_tracking_mode' ) )
				return self::DEFAULT_HEATMAP_TRACKING_MODE;
			$options = self::get_settings();
			if ( isset( $options['hm_tracking_mode'] ) )
				return $options['hm_tracking_mode'];
			return self::DEFAULT_HEATMAP_TRACKING_MODE;
		}


		/**
		 * Returns the user split mode.
		 *
		 * If the mode is set to `split`, experiments are grouped into buckets, so
		 * that the combination of alternatives does not become too large.
		 *
		 * @return string the user split mode.
		 *
		 * @since 4.0.0
		 */
		public static function get_split_user_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'user_split' ) )
				return self::DEFAULT_USER_SPLIT;
			$options = self::get_settings();
			if ( isset( $options['user_split'] ) )
				return $options['user_split'];
			return self::DEFAULT_USER_SPLIT;
		}


		/**
		 * Returns the visibility settings for Nelio's GET parameters.
		 *
		 * @return string the visibility settings for Nelio's GET parameters.
		 *
		 * @since 4.0.0
		 */
		public static function get_params_visibility() {
			if ( !self::is_field_enabled_for_current_plan( 'get_params_visibility' ) )
				return self::DEFAULT_GET_PARAMS_VISIBILITY;
			$options = self::get_settings();
			if ( isset( $options['get_params_visibility'] ) )
				return $options['get_params_visibility'];
			return self::DEFAULT_GET_PARAMS_VISIBILITY;
		}


		/**
		 * Returns the default conversion value.
		 *
		 * @return int the default conversion value.
		 *
		 * @since 2.1.0
		 */
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


		/**
		 * Specifies whether regular admins can manage the plugin.
		 *
		 * This setting is useful for Multi-Site environments.
		 *
		 * @param boolean $value whether regular admins can manage the plugin or not.
		 *
		 * @return void
		 *
		 * @since 3.4.0
		 */
		public static function set_site_option_regular_admins_can_manage_plugin( $value ) {
			update_site_option( 'nelioab_regular_admins_can_manage_plugin', $value );
		}


		/**
		 * Returns whether regular admins can manage the plugin.
		 *
		 * This setting is useful for Multi-Site environments.
		 *
		 * @return boolean Whether regular admins can manage the plugin.
		 *
		 * @since 3.4.0
		 */
		public static function get_site_option_regular_admins_can_manage_plugin() {
			return get_site_option( 'nelioab_regular_admins_can_manage_plugin', true );
		}


		/**
		 * Returns whether regular admins can manage the plugin.
		 *
		 * This setting is useful for Multi-Site environments.
		 *
		 * @return boolean whether regular admins can manage the plugin.
		 *
		 * @since 3.4.0
		 */
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


		/**
		 * Returns the "set" of users that can see and manage the plugin.
		 *
		 * The result can be:
		 * * `PLUGIN_AVAILABLE_TO_ANY_ADMIN`
		 * * `PLUGIN_AVAILABLE_TO_SUPER_ADMIN`
		 * * `PLUGIN_AVAILABLE_TO_SITE_SETTING`
		 *
		 * This setting is useful for Multi-Site environments.
		 *
		 * @return string the "set" of users that can see and manage the plugin.
		 *
		 * @since 3.4.0
		 */
		public static function get_plugin_available_to() {
			if ( !self::is_field_enabled_for_current_plan( 'plugin_available_to' ) )
				return self::DEFAULT_PLUGIN_AVAILABLE_TO;
			$options = self::get_settings();
			if ( isset( $options['plugin_available_to'] ) )
				return $options['plugin_available_to'];
			return self::DEFAULT_PLUGIN_AVAILABLE_TO;
		}


		/**
		 * Returns the conversion unit (a "money symbol").
		 *
		 * @return int the conversion unit (a "money symbol").
		 *
		 * @since 2.1.0
		 */
		public static function get_conv_unit() {
			if ( !self::is_field_enabled_for_current_plan( 'conv_unit' ) )
				return self::DEFAULT_CONVERSION_UNIT;
			$options = self::get_settings();
			if ( isset( $options['conv_unit'] ) )
				return $options['conv_unit'];
			return self::DEFAULT_CONVERSION_UNIT;
		}


		/**
		 * Returns how likely (percentage) is for the original version to be assigned to a visitor.
		 *
		 * @return int how likely (percentage) is for the original version to be assigned to a visitor.
		 *
		 * @since 3.2.0
		 */
		public static function get_original_percentage() {
			if ( !self::is_field_enabled_for_current_plan( 'ori_perc' ) )
				return self::DEFAULT_ORIGINAL_PERCENTAGE;
			$options = self::get_settings();
			if ( isset( $options['ori_perc'] ) )
				return $options['ori_perc'];
			return self::DEFAULT_ORIGINAL_PERCENTAGE;
		}


		/**
		 * Returns how likely (percentage) is for the winning alternative to be assigned to a visitor.
		 *
		 * @return int how likely (percentage) is for the winning alternative to be assigned to a visitor.
		 *
		 * @since 2.1.0
		 */
		public static function get_exploitation_percentage() {
			if ( !self::is_field_enabled_for_current_plan( 'expl_ratio' ) )
				return self::DEFAULT_EXPL_RATIO;
			$options = self::get_settings();
			if ( isset( $options['expl_ratio'] ) )
				return $options['expl_ratio'];
			return self::DEFAULT_EXPL_RATIO;
		}


		/**
		 * Returns whether alternative content has to be loaded on all pages of the site or not.
		 *
		 * @return boolean whether alternative content has to be loaded on all pages of the site or not.
		 *
		 * @since 3.3.0
		 */
		public static function make_site_consistent() {
			if ( !self::is_field_enabled_for_current_plan( 'make_site_consistent' ) )
				return self::DEFAULT_MAKE_SITE_CONSISTENT;
			$options = self::get_settings();
			if ( isset( $options['make_site_consistent'] ) )
				return $options['make_site_consistent'];
			return self::DEFAULT_MAKE_SITE_CONSISTENT;
		}


		/**
		 * Returns whether a colorblind palette has to be used or not.
		 *
		 * @return boolean whether a colorblind palette has to be used or not.
		 *
		 * @since 2.1.0
		 */
		public static function use_colorblind_palette() {
			if ( !self::is_field_enabled_for_current_plan( 'use_colorblind' ) )
				return self::DEFAULT_USE_COLORBLIND_PALETTE;
			$options = self::get_settings();
			if ( isset( $options['use_colorblind'] ) )
				return $options['use_colorblind'];
			return self::DEFAULT_USE_COLORBLIND_PALETTE;
		}


		/**
		 * Returns which finished experiments (if any) are shown in the Experiments page.
		 *
		 * This function specifies whether finished experiments are shown in the
		 * experiment list and, if they are, if all of them or only the
		 * "recently-finished" ones.
		 *
		 * @return int which finished experiments (if any) are shown in the Experiments page.
		 *
		 * @since 3.2.0
		 */
		public static function show_finished_experiments() {
			if ( !self::is_field_enabled_for_current_plan( 'show_finished_experiments' ) )
				return self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
			$options = self::get_settings();
			if ( isset( $options['show_finished_experiments'] ) )
				return $options['show_finished_experiments'];
			return self::DEFAULT_SHOW_FINISHED_EXPERIMENTS;
		}


		/**
		 * Returns the minimum confidence for considering the results of an experiment "significant".
		 *
		 * @return int the minimum confidence for considering the results of an experiment "significant".
		 *             Minimum confidence is 90%.
		 *
		 * @since 3.2.0
		 */
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


		/**
		 * Returns the percentage of tested users.
		 *
		 * @return int the percentage of tested users.
		 *
		 * @since 3.2.0
		 */
		public static function get_percentage_of_tested_users() {
			if ( !self::is_field_enabled_for_current_plan( 'perc_of_tested_users' ) )
				return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
			$options = self::get_settings();
			if ( isset( $options['perc_of_tested_users'] ) )
				return $options['perc_of_tested_users'];
			return self::DEFAULT_PERCENTAGE_OF_TESTED_USERS;
		}


		/**
		 * Returns the maximum number of page views that a single experiment can use.
		 *
		 * @return int the maximum number of page views that a single experiment can use.
		 *
		 * @since 3.2.1
		 */
		public static function get_quota_limit_per_exp() {
			if ( !self::is_field_enabled_for_current_plan( 'quota_limit_per_exp' ) )
				return self::DEFAULT_QUOTA_LIMIT_PER_EXP;
			$options = self::get_settings();
			if ( isset( $options['quota_limit_per_exp'] ) )
				return $options['quota_limit_per_exp'];
			return self::DEFAULT_QUOTA_LIMIT_PER_EXP;
		}


		/**
		 * Returns the e-mail address in which notification e-mails are sent.
		 *
		 * @return string the e-mail address in which notification e-mails are sent.
		 *
		 * @since 3.2.1
		 */
		public static function get_notification_email() {
			if ( !self::is_field_enabled_for_current_plan( 'notification_email' ) )
				return '';
			$options = self::get_settings();
			if ( isset( $options['notification_email'] ) )
				return $options['notification_email'];
			return '';
		}


		/**
		 * Returns the list of enabled notifications.
		 *
		 * @return string the list of enabled notifications.
		 *
		 * @since 3.2.1
		 */
		public static function get_notifications() {
			if ( !self::is_field_enabled_for_current_plan( 'notifications' ) )
				return self::DEFAULT_NOTIFICATIONS;
			$options = self::get_settings();
			if ( isset( $options['notifications'] ) )
				return $options['notifications'];
			return self::DEFAULT_NOTIFICATIONS;
		}


		/**
		 * Returns whether the notification is enabled or not.
		 *
		 * @param string $notification the name of the notification that may or may not be enabled.
		 *
		 * @return boolean whether a concrete notification is enabled or not.
		 *
		 * @since 3.2.1
		 */
		public static function is_notification_enabled( $notification ) {
			return strpos( self::get_notifications(), ' ' . $notification . ' ' ) !== false;
		}


		/**
		 * Returns the location of Nelio A/B Testing's menu in the Dashboard's sidebar.
		 *
		 * @return int the location of Nelio A/B Testing's menu in the Dashboard's sidebar.
		 *
		 * @since 3.3.0
		 */
		public static function get_menu_location() {
			if ( !self::is_field_enabled_for_current_plan( 'menu_location' ) )
				return self::DEFAULT_MENU_LOCATION;
			$options = self::get_settings();
			if ( isset( $options['menu_location'] ) )
				return $options['menu_location'];
			return self::DEFAULT_MENU_LOCATION;
		}


		/**
		 * Returns whether Nelio A/B Testing menu is enabled in the admin bar.
		 *
		 * @return boolean whether Nelio A/B Testing menu is enabled in the admin bar.
		 *
		 * @since 3.3.0
		 */
		public static function is_menu_enabled_for_admin_bar() {
			if ( !self::is_field_enabled_for_current_plan( 'menu_in_admin_bar' ) )
				return self::DEFAULT_MENU_IN_ADMIN_BAR;
			$options = self::get_settings();
			if ( isset( $options['menu_in_admin_bar'] ) )
				return $options['menu_in_admin_bar'];
			return self::DEFAULT_MENU_IN_ADMIN_BAR;
		}


		/**
		 * Returns whether the currently active theme uses a custom landing page (specified by the user).
		 *
		 * @return boolean whether the currently active theme uses a custom landing page (specified by the user).
		 *
		 * @since 3.4.0
		 */
		public static function does_theme_use_a_custom_landing_page() {
			if ( !self::is_field_enabled_for_current_plan( 'theme_landing_page' ) )
				return self::DEFAULT_THEME_LANDING_PAGE;
			$options = self::get_settings();
			if ( isset( $options['theme_landing_page'] ) )
				return $options['theme_landing_page'];
			return self::DEFAULT_THEME_LANDING_PAGE;
		}


		/**
		 * Returns whether navigations to external pages have to be opened on new tabs or not.
		 *
		 * @return boolean whether navigations to external pages have to be opened on new tabs or not.
		 *
		 * @since 3.4.0
		 */
		public static function use_outwards_navigations_blank() {
			if ( !self::is_field_enabled_for_current_plan( 'on_blank' ) )
				return self::DEFAULT_OUTWARDS_NAVIGATION_BLANK;
			$options = self::get_settings();
			if ( isset( $options['on_blank'] ) )
				return $options['on_blank'];
			return self::DEFAULT_OUTWARDS_NAVIGATION_BLANK;
		}


		/**
		 * Returns which algorithm has to be used for alternative assignation.
		 *
		 * @return int which algorithm has to be used for alternative assignation.
		 *
		 * @since 3.2.0
		 */
		public static function get_algorithm() {
			if ( !self::is_field_enabled_for_current_plan( 'algorithm' ) )
				return self::ALGORITHM_PURE_RANDOM;
			$options = self::get_settings();
			if ( isset( $options['algorithm'] ) )
				return $options['algorithm'];
			return self::ALGORITHM_PURE_RANDOM;
		}


		/**
		 * Returns the headlines quota mode.
		 *
		 * By default, headline views are counted on every single page of a
		 * WordPress site. However, it is possible to count only those appearing on
		 * the front page. In other words, this setting helps reduce the overall
		 * quota consumption.
		 *
		 * @return int the headlines quota mode.
		 *
		 * @since 3.3.8
		 */
		public static function get_headlines_quota_mode() {
			if ( !self::is_field_enabled_for_current_plan( 'headlines_quota_mode' ) )
				return self::HEADLINES_QUOTA_MODE_ALWAYS;
			$options = self::get_settings();
			if ( isset( $options['headlines_quota_mode'] ) )
				return $options['headlines_quota_mode'];
			return self::HEADLINES_QUOTA_MODE_ALWAYS;
		}


		/**
		 * Specifies whether metadata has to be duplicated.
		 *
		 * This operation is used when new alternative posts are created
		 * duplicating the content of an already-existing post.
		 *
		 * @param boolean $enabled whether metadata has to be duplicated.
		 *
		 * @return void
		 *
		 * @since 1.0.10
		 */
		public static function set_copy_metadata( $enabled ) {
			update_option( 'nelioab_copy_metadata', $enabled );
		}


		/**
		 * Returns whether whether metadata has to be duplicated.
		 *
		 * @return boolean whether whether metadata has to be duplicated.
		 *
		 * @since 1.0.10
		 */
		public static function is_copying_metadata_enabled() {
			return get_option( 'nelioab_copy_metadata', true );
		}


		/**
		 * Specifies whether tags have to be duplicated.
		 *
		 * This operation is used when new alternative posts are created
		 * duplicating the content of an already-existing post.
		 *
		 * @param boolean $enabled whether tags have to be duplicated.
		 *
		 * @return void
		 *
		 * @since 1.2.0
		 */
		public static function set_copy_tags( $enabled ) {
			update_option( 'nelioab_copy_tags', $enabled );
		}


		/**
		 * Returns whether whether tags have to be duplicated.
		 *
		 * @return boolean whether whether tags have to be duplicated.
		 *
		 * @since 1.2.0
		 */
		public static function is_copying_tags_enabled() {
			return get_option( 'nelioab_copy_tags', true );
		}


		/**
		 * Specifies whether categories have to be duplicated.
		 *
		 * This operation is used when new alternative posts are created
		 * duplicating the content of an already-existing post.
		 *
		 * @param boolean $enabled whether categories have to be duplicated.
		 *
		 * @return void
		 *
		 * @since 1.2.0
		 */
		public static function set_copy_categories( $enabled ) {
			update_option( 'nelioab_copy_categories', $enabled );
		}


		/**
		 * Returns whether whether categories have to be duplicated.
		 *
		 * @return boolean whether whether categories have to be duplicated.
		 *
		 * @since 1.2.0
		 */
		public static function is_copying_categories_enabled() {
			return get_option( 'nelioab_copy_categories', true );
		}


		/**
		 * Returns whether the upgrade notice is visible or not.
		 *
		 * @return boolean whether the upgrade notice is visible or not.
		 *
		 * @since 2.0.11
		 */
		public static function is_upgrade_message_visible() {
			if ( NelioABAccountSettings::get_subscription_plan() != NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN )
				return false;
			$result = get_option( 'nelioab_hide_upgrade_message', false );
			if ( !$result )
				return true;
			else
				return false;
		}


		/**
		 * Hides the upgrade message for the current version of the plugin.
		 *
		 * @return void
		 *
		 * If the plugin is upgraded, the message will be visible again.
		 */
		public static function hide_upgrade_message() {
			update_option( 'nelioab_hide_upgrade_message', NELIOAB_PLUGIN_VERSION );
		}

	}//NelioABSettings

}

