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


if ( !class_exists( 'NelioABSettingsPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/settings.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
	class NelioABSettingsPage extends NelioABAdminPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->add_class( 'settings-page' );
			$this->set_icon( 'icon-nelioab' );
		}

		public function do_render() {
			// Set class property
			$this->options = NelioABSettings::get_settings();
			?>
			<div class="wrap">
				<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'nelioab_settings_group' );
					do_settings_sections( 'nelioab-settings' );
					submit_button();
				?>
				</form>
			</div>

		<?php
		}

		public static function register_settings() {

			register_setting(
				'nelioab_settings_group',
				'nelioab_settings',
				array( 'NelioABSettings', 'sanitize' )
			);

			add_settings_section(
				'nelioab_general_section',
				// =============================================================
				'General',
				// =============================================================
				array( 'NelioABSettingsPage', 'print_section_without_info' ),
				'nelioab-settings'
			);

//			add_settings_field(
//				'def_conv_value',
//				'Default Conversion Value',
//				// -------------------------------------------------------------
//				array( 'NelioABSettingsPage', 'print_def_conv_value_field' ),
//				'nelioab-settings',
//				'nelioab_general_section'
//			);
//
//			add_settings_field(
//				'conv_unit',
//				'Conversion Unit',
//				// -------------------------------------------------------------
//				array( 'NelioABSettingsPage', 'print_conv_unit_field' ),
//				'nelioab-settings',
//				'nelioab_general_section'
//			);

			add_settings_field(
				'use_colorblind_palette',
				'Icons and Colors',
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_colorblindness_field' ),
				'nelioab-settings',
				'nelioab_general_section'
			);

			add_settings_field(
				'exact_url_external',
				'External Goals',
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_exact_url_external_field' ),
				'nelioab-settings',
				'nelioab_general_section'
			);




			add_settings_section(
				'nelioab_behavior_section',
				// =============================================================
				'Behavior',
				// =============================================================
				array( 'NelioABSettingsPage', 'print_section_without_info' ),
				'nelioab-settings'
			);

			add_settings_field(
				'algorithm',
				'Algorithm',
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_algorithm_field' ),
				'nelioab-settings',
				'nelioab_behavior_section'
			);

			add_settings_field(
				'expl_ratio',
				'Exploitation or Exploration',
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_expl_ratio_field' ),
				'nelioab-settings',
				'nelioab_behavior_section'
			);

		}

		public static function print_section_without_info() {
			// Nothing to print here
		}

		public static function print_def_conv_value_field() {
			$field_name = 'def_conv_value';
			printf(
				'<input type="text" id="%1$s" name="nelioab_settings[%1$s]" value="%2$s" placeholder="%3$s" />',
				$field_name, NelioABSettings::get_def_conv_value(), NelioABSettings::DEFAULT_CONVERSION_VALUE
			);
		}

		public static function print_conv_unit_field() {
			$field_name = 'conv_unit';
			printf(
				'<input type="text" id="%1$s" name="nelioab_settings[%1$s]" value="%2$s" placeholder="%3$s" />',
				$field_name, NelioABSettings::get_conv_unit(), NelioABSettings::DEFAULT_CONVERSION_UNIT
			);
		}

		public static function print_algorithm_field() {
			$field_name = 'greedy_enabled';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" style="width:100%;">',
				$field_name
			);
			?>
				<option value='0'><?php _e( 'Default - Pure Random', 'nelioab' ); ?></option>
				<option value='1'<?php
					if ( NelioABSettings::use_greedy_algorithm() )
						echo ' selected="selected"';
				?>><?php _e( 'Greedy - Prioritize Winner', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_colorblindness_field() {
			$field_name = 'use_colorblind';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" style="width:100%;">',
				$field_name
			);
			?>
				<option value='0'><?php _e( 'Regular Palette', 'nelioab' ); ?></option>
				<option value='1'<?php
					if ( NelioABSettings::use_colorblind_palette() )
						echo ' selected="selected"';
				?>><?php _e( 'Colorblind Palette', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_exact_url_external_field() {
			$field_name = 'exact_url_external';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" style="width:100%;">',
				$field_name
			);
			?>
				<option value='1'><?php _e( 'Match whole URL', 'nelioab' ); ?></option>
				<option value='0'<?php
					if ( !NelioABSettings::match_exact_url_for_external_goals() )
						echo ' selected="selected"';
				?>><?php _e( 'Ignore GET parameters', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_expl_ratio_field() {
			$field_name = 'expl_ratio';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="10" max="90" step="5" value="%2$s" /><br>',
				$field_name, NelioABSettings::get_exploitation_percentage()
			);
			?>
			<span id="value_<?php echo $field_name; ?>"></span>
			<script>
				jQuery("#greedy_enabled").on("change", function() {
					var option = jQuery("#<?php echo $field_name; ?>").parent().parent();
					if ( jQuery(this).attr('value') == 1 ) option.show();
					else option.hide();
				});
				jQuery("#greedy_enabled").trigger("change");

				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( '{value}% of your visitors will see the winning alternative.<br>The others will randomly see one of the other possible alternatives.', 'nelioab' );
						$str = str_replace( '"', '\\"', $str );
						echo $str;
					?>";
					var value = jQuery(this).attr('value');
					str = str.replace( '{value}', value );
					jQuery("#value_<?php echo $field_name; ?>").html(str);
				});
				jQuery("#<?php echo $field_name; ?>").trigger("change");
			</script>
			<?php
		}

	}//NelioABSettingsPage

}

?>
