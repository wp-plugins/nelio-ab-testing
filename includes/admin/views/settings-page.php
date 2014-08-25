<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
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
				__( 'General', 'nelioab' ),
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
				__( 'Icons and Colors', 'nelioab' ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_colorblindness_field' ),
				'nelioab-settings',
				'nelioab_general_section'
			);

			add_settings_field(
				'exact_url_external',
				__( 'External Goals', 'nelioab' ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_exact_url_external_field' ),
				'nelioab-settings',
				'nelioab_general_section'
			);




			add_settings_section(
				'nelioab_behavior_section',
				// =============================================================
				__( 'Behavior', 'nelioab' ),
				// =============================================================
				array( 'NelioABSettingsPage', 'print_behavior_section' ),
				'nelioab-settings'
			);

			add_settings_field(
				'algorithm',
				__( 'Algorithm', 'nelioab' ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_algorithm_field' ),
				'nelioab-settings',
				'nelioab_behavior_section'
			);

			add_settings_field(
				'expl_ratio',
				__( 'Exploitation or Exploration', 'nelioab' ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_expl_ratio_field' ),
				'nelioab-settings',
				'nelioab_behavior_section'
			);

			add_settings_field(
				'use_php_cookies',
				__( 'PHP cookies', 'nelioab' ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_cookies_field' ),
				'nelioab-settings',
				'nelioab_behavior_section'
			);

		}

		public static function print_section_without_info() {
			// Nothing to print here
		}

		public static function print_behavior_section() {
			self::print_mu_plugin_row();
		}

		public static function print_mu_plugin_row() { ?>
			<table class="form-table">
				<tbody><tr>
					<th scope="row">AJAX Performance</th>
					<td><?php
						self::print_mu_plugin_settings();
					?></td>
				</tr></tbody>
			</table>
			<?php
		}

		public static function print_mu_plugin_settings() {
			$status = __( 'In order to boost response times of all AJAX requests triggered by Nelio A/B Testing, we include a tiny <a %s>Must Use Plugin</a> that disables other plugins when they\'re not necessary.<br><br><strong>AJAX Performance MU-Plugin Status: %s</strong>.<br>%s', 'nelioab' );
			if ( NelioABSettings::is_performance_muplugin_installed() ) {
				$status = sprintf( $status,
					'target="_blank" href="http://codex.wordpress.org/Must_Use_Plugins"',
					'<span class="status installed">' . __( 'Installed', 'nelioab' ) . '</span>',
					__( 'In order to uninstall the plugin, please use the previous «Uninstall» button.', 'nelioab' ) );
				$button = __( 'Uninstall', 'nelioab' );
			}
			else {
				$status = sprintf( $status,
					'target="_blank" href="http://codex.wordpress.org/Must_Use_Plugins"',
					'<span class="status uninstalled">' . __( 'Not Installed', 'nelioab' ) . '</span>',
					__( 'In order to install the plugin, please use the previous «Install» button.', 'nelioab' ) );
				$button = __( 'Install', 'nelioab' );
			}
			printf( '<a id="muplugin-installer" class="button">%s</a>', $button );
			?>
			<span
				id="muplugin-descr" class="description"
				style="display:block;margin-top:0.4em;"><?php echo $status; ?></span>
			<span
				id="muplugin-installation-feedback" class="description"
				style="display:block;margin-top:0.4em;display:none;"></span>
			<script type="text/javascript">
			(function($){
				$("#muplugin-installer").click(function() {
					var descr = $("#muplugin-descr");
					var button = $(this);
					if ( button.hasClass("disabled") )
						return;
					button.addClass( "disabled" );
					$.post( ajaxurl, {action:"nelioab_install_performance_muplugin"}, function(response) {
						console.log( response );
						if ( response.status === "OK" ) {
							button.text( "<?php _e( "Done!", "nelioab" ) ?>" );
							var s = descr.find(".status").first();
							if ( s.hasClass( "installed" ) ){
								s.removeClass("installed");
								s.addClass("uninstalled");
								s.text( "<?php _e( 'Not Installed', 'nelioab' ); ?>" );
							}
							else {
								s.removeClass("uninstalled");
								s.addClass("installed");
								s.text( "<?php _e( 'Installed', 'nelioab' ); ?>" );
							}
						}
						else {
							var feedback = $("#muplugin-installation-feedback");
							feedback.html( response.error );
							descr.hide();
							feedback.css("display","block");
						}
					} );
				});
			})(jQuery);
			</script>
			<?php
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
			<span class="description" id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
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

		public static function print_cookies_field() {
			$field_name = 'use_php_cookies';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" style="width:100%;">',
				$field_name
			);
			?>
				<option value='0'><?php _e( 'Disabled (use JavaScript)', 'nelioab' ); ?></option>
				<option value='1'<?php
					if ( NelioABSettings::use_php_cookies() )
						echo ' selected="selected"';
				?>><?php _e( 'Enabled', 'nelioab' ); ?></option>
			</select>
			<br><span class="description"><?php
				_e( 'Select how alternatives are loaded. With PHP cookies, page load times might be faster, because regular cookies reduce the amount of queries your visitors will perform to your server. However, they cannot be used everywhere (for instance, <a href="http://wpengine.com">WPEngine</a> does not permit them). If you are not sure, use JavaScript.',
					'nelioab' );
			?></span>
			<?php
		}

	}//NelioABSettingsPage

}

