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

		const DEFAULT_CONVERSION_VALUE       = 25;
		const DEFAULT_CONVERSION_UNIT        = 'USD';
		const DEFAULT_EXPL_RATIO             = '90';
		const DEFAULT_IS_GREEDY_ENABLED      = false;
		const DEFAULT_USE_COLORBLIND_PALETTE = false;

		public static function get_settings() {
			return get_option( 'nelioab_settings', array() );
		}

		public static function sanitize( $input ) {
			$new_input = array();

			$new_input['def_conv_value'] = NelioABSettings::DEFAULT_CONVERSION_VALUE;
			if( isset( $input['def_conv_value'] ) )
				$new_input['def_conv_value'] = sanitize_text_field( $input['def_conv_value'] );

			$new_input['conv_unit'] = NelioABSettings::DEFAULT_CONVERSION_UNIT;
			if( isset( $input['conv_unit'] ) )
				$new_input['conv_unit'] = sanitize_text_field( $input['conv_unit'] );

			$new_input['greedy_enabled'] = NelioABSettings::DEFAULT_IS_GREEDY_ENABLED;
			if( isset( $input['greedy_enabled'] ) ) {
				$new_input['greedy_enabled'] = sanitize_text_field( $input['greedy_enabled'] );
				$new_input['greedy_enabled'] = $new_input['greedy_enabled'] == '1';
			}

			$new_input['use_colorblind'] = NelioABSettings::DEFAULT_USE_COLORBLIND_PALETTE;
			if( isset( $input['use_colorblind'] ) ) {
				$new_input['use_colorblind'] = sanitize_text_field( $input['use_colorblind'] );
				$new_input['use_colorblind'] = $new_input['use_colorblind'] == '1';
			}

			$new_input['expl_ratio'] = NelioABSettings::DEFAULT_EXPL_RATIO;
			if( isset( $input['expl_ratio'] ) )
				$new_input['expl_ratio'] = sanitize_text_field( $input['expl_ratio'] );

			return $new_input;
		}

		public static function get_def_conv_value() {
			$options = NelioABSettings::get_settings();
			$result = '';
			if ( isset( $options['def_conv_value'] ) )
				$result = $options['def_conv_value'];
			if ( strlen( $result ) == 0 )
				$result = NelioABSettings::DEFAULT_CONVERSION_VALUE;
			return $result;
		}

		public static function get_conv_unit() {
			$options = NelioABSettings::get_settings();
			$result = '';
			if ( isset( $options['conv_unit'] ) )
				$result = $options['conv_unit'];
			if ( strlen( $result ) == 0 )
				$result = NelioABSettings::DEFAULT_CONVERSION_UNIT;
			return $result;
		}

		public static function use_greedy_algorithm() {
			$options = NelioABSettings::get_settings();
			if ( isset( $options['greedy_enabled'] ) )
				return $options['greedy_enabled'];
			return NelioABSettings::DEFAULT_IS_GREEDY_ENABLED;
		}

		public static function get_exploitation_percentage() {
			$options = NelioABSettings::get_settings();
			if ( isset( $options['expl_ratio'] ) )
				return $options['expl_ratio'];
			return NelioABSettings::DEFAULT_EXPL_RATIO;
		}

		public static function use_colorblind_palette() {
			$options = NelioABSettings::get_settings();
			if ( isset( $options['use_colorblind'] ) )
				return $options['use_colorblind'];
			return NelioABSettings::DEFAULT_USE_COLORBLIND_PALETTE;
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

		public function is_upgrade_message_visible() {
			require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
			if ( NelioABAccountSettings::get_subscription_plan() != NelioABAccountSettings::BASIC_SUBSCRIPTION_PLAN )
				return false;
			$result = get_option( 'nelioab_hide_upgrade_message', false );
			if ( !$result )
				return true;
			else
				return false;
		}

		public function hide_upgrade_message() {
			update_option( 'nelioab_hide_upgrade_message', NELIOAB_PLUGIN_VERSION );
		}

	}//NelioABSettings

}

?>
