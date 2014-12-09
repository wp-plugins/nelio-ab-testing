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

	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
	class NelioABSettingsPage extends NelioABAdminPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->add_class( 'settings-page' );
			$this->set_icon( 'icon-nelioab' );

			$ae_sync_errors = NelioABSettings::get_unsync_fields();
			if ( count( $ae_sync_errors ) > 0 ) {
				$msg = __( 'There was a problem while updating some of your options. The following fields could not be properly updated:</p>%s<p>Please, try it again in a few moments.', 'nelioab' );

				$errors = '<ul>';
				if ( in_array( 'quota_limit_per_exp', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Quota Limit per Experiment', 'nelioab' ) . '</li>';
				if ( in_array( 'notification_email', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Notification E-Mail', 'nelioab' ) . '</li>';
				if ( in_array( 'notifications', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Notifications', 'nelioab' ) . '</li>';
				$errors .= '</ul>';
				if ( '<ul></ul>' == $errors )
					$errors = '';
				$errors = str_replace( '<ul', '<ul style="padding-left:1em;"', $errors );

				global $nelioab_admin_controller;
				$nelioab_admin_controller->error_message = sprintf( $msg, $errors );
			}
		}

		public function do_render() { ?>
			<div id="nelioab-settings" class="wrap">
				<form method="post" action="options.php">
					<h3 id="settings-tabs" class="nav-tab-wrapper" style="margin:0em;padding:0em;padding-left:2em;margin-bottom:2em;"><?php
						$tab = '<span id="tab-%1$s" class="nav-tab %2$s">%3$s</span>';

						printf( $tab, 'basic', 'nav-tab-active',
							__( 'Basic', 'nelioab' ) );

						printf( $tab, 'pro', '',
							__( 'Advanced', 'nelioab' ) );


					?></h3>
					<input type="hidden" value="false"
						id="reset_settings" name="nelioab_settings[reset_settings]" />
					<?php
						// This prints out all hidden setting fields
						settings_fields( 'nelioab_settings_group' );
						do_settings_sections( 'nelioab-settings' );
					?>
					<p>
						<input type="button" id="reset-button" class="button"
							value="<?php echo esc_html( __( 'Reset to Defaults', 'nelioab' ) ); ?>" />
						&nbsp;
						<input type="submit" id="submit-button" class="button button-primary"
							value="<?php echo esc_html( __( 'Save Changes' ) ); ?>" />
						<script type="text/javascript">
						jQuery(document).ready( function() {
							var $ = jQuery;
							var $dialog = $('#dialog-modal').dialog({
								title: '<?php echo esc_html( __( 'Reset Settings', 'nelioab' ) ); ?>',
								dialogClass   : 'wp-dialog',
								modal         : true,
								autoOpen      : false,
								closeOnEscape : true,
								buttons: [
									{
										text: "<?php echo esc_html( __( 'Cancel', 'nelioab' ) ); ?>",
										click: function() {
											$(this).dialog('close');
										}
									},
									{
										text: "<?php echo esc_html( __( 'Reset to Defaults', 'nelioab' ) ); ?>",
										'class': 'button-primary',
										click: function() {
											$(this).dialog('close');
											$('#reset_settings').val('do_reset');
											$('#nelioab-settings > form').submit();
										}
									}
								]
							});
							jQuery('#dialog-content').html("<?php
								echo str_replace( '"', '\\"', str_replace( '\\', '\\\\',
									__( 'This operation will set all Settings to their default values. Do you want to continue?', 'nelioab' ) ) );
							?>");

							$('#reset-button').on('click', function() {
								$dialog.dialog('open');
							});
						});
						</script>
					</p>
				</form>
			</div>
			<script type="text/javascript">
			(function($) {
				$('#nelioab-settings #tab-basic').click(function() {
					$('#nelioab-settings .nav-tab-active').removeClass('nav-tab-active');
					$('#nelioab-pro-section').hide();
					$(this).addClass('nav-tab-active');
					$('#nelioab-basic-section').show();
				});
				$('#nelioab-settings #tab-pro').click(function() {
					$('#nelioab-settings .nav-tab-active').removeClass('nav-tab-active');
					$('#nelioab-basic-section').hide();
					$(this).addClass('nav-tab-active');
					$('#nelioab-pro-section').show();
				});
			})(jQuery);
			</script>
		<?php
		}

		public static function register_settings() {

			register_setting(
				'nelioab_settings_group',
				'nelioab_settings',
				array( 'NelioABSettings', 'sanitize' )
			);

			// ===============================================================
			// ===============================================================
			//    BASIC SETTINGS
			// ===============================================================
			// ===============================================================

			add_settings_section(
				'nelioab_basic_section_efficiency', '',
			// ===============================================================
				array( 'NelioABSettingsPage', 'print_basic_section_efficiency' ),
			// ===============================================================
				'nelioab-settings'
			);

			add_settings_field(
				'make_site_consistent',
				self::prepare_basic_label( __( 'Site-wide Consistency', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_site_consistency_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
			);

			add_settings_field(
				'alt_load_mode',
				self::prepare_basic_label( __( 'Cache Support', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_alternative_loading_mode_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
			);


			add_settings_section(
				'nelioab_basic_section_ui', '',
			// ===============================================================
				array( 'NelioABSettingsPage', 'print_basic_section_ui' ),
			// ===============================================================
				'nelioab-settings'
			);

			add_settings_field(
				'menu_in_admin_bar',
				self::prepare_basic_label( __( 'Admin Bar', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_menu_in_admin_bar_field' ),
				'nelioab-settings',
				'nelioab_basic_section_ui'
			);

			add_settings_field(
				'menu_location',
				self::prepare_basic_label( __( 'Plugin Menu Location', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_menu_location_field' ),
				'nelioab-settings',
				'nelioab_basic_section_ui'
			);

			add_settings_field(
				'show_finished_experiments',
				self::prepare_basic_label( __( 'Experiment List', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_show_finished_experiments_field' ),
				'nelioab-settings',
				'nelioab_basic_section_ui'
			);

			add_settings_field(
				'use_colorblind_palette',
				self::prepare_basic_label( __( 'Icons and Colors', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_colorblindness_field' ),
				'nelioab-settings',
				'nelioab_basic_section_ui'
			);

			add_settings_section(
				'nelioab_basic_section_misc', '',
				array( 'NelioABSettingsPage', 'print_basic_section_misc' ),
			// ===============================================================
				'nelioab-settings'
			);

			add_settings_field(
				'hm_tracking_mode',
				self::prepare_basic_label( __( 'Heatmap Tracking', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_heatmap_tracking_mode_field' ),
				'nelioab-settings',
				'nelioab_basic_section_misc'
			);

			add_settings_field(
				'email',
				self::prepare_basic_label( __( 'Notification E-Mail', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_notification_email_field' ),
				'nelioab-settings',
				'nelioab_basic_section_misc'
			);

//			add_settings_field(
//				'def_conv_value',
//				self::prepare_basic_label( __( 'Default Conversion Value', 'nelioab' ) ),
//				// -------------------------------------------------------------
//				array( 'NelioABSettingsPage', 'print_def_conv_value_field' ),
//				'nelioab-settings',
//				'nelioab_basic_section_ui'
//			);
//
//			add_settings_field(
//				'conv_unit',
//				self::prepare_basic_label( 'Conversion Unit' ),
//				// -------------------------------------------------------------
//				array( 'NelioABSettingsPage', 'print_conv_unit_field' ),
//				'nelioab-settings',
//				'nelioab_basic_section_ui'
//			);



			// ===============================================================
			// ===============================================================
			//    PROFESSIONAL SETTINGS
			// ===============================================================
			// ===============================================================

			add_settings_section(
				'nelioab_pro_section', '',
			// ===============================================================
				array( 'NelioABSettingsPage', 'print_pro_section' ),
			// ===============================================================
				'nelioab-settings'
			);

			add_settings_field(
				'quota_limit_for_exp',
				self::prepare_pro_label( __( 'Quota Limit per Experiment', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_quota_limit_per_experiment_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);

			add_settings_field(
				'min_confidence_for_significance',
				self::prepare_pro_label( __( 'Min. Confidence', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_min_confidence_for_significance_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);

			add_settings_field(
				'perc_of_tested_users',
				self::prepare_pro_label( __( 'Num. of Tested Users', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_perc_of_tested_users_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);

			add_settings_field(
				'algorithm',
				self::prepare_pro_label( __( 'Algorithm', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_algorithm_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);

				add_settings_field(
					'ori_perc', '', array( 'NelioABSettingsPage', 'print_ori_perc_field' ),
					'nelioab-settings', 'nelioab_pro_section' );

				add_settings_field(
					'expl_ratio', '', array( 'NelioABSettingsPage', 'print_expl_ratio_field' ),
					'nelioab-settings', 'nelioab_pro_section' );

			add_settings_field(
				'notifications',
				self::prepare_pro_label( __( 'Notifications', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_notifications_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);


			// ===============================================================
			add_settings_section(
				'nelioab_fake_section', '',
				array( 'NelioABSettingsPage', 'close_last_section' ),
				'nelioab-settings'
			);

		}

		public static function print_basic_section_efficiency() {
			echo '<div id="nelioab-basic-section">';
			echo '<h3>' . __( 'Efficiency', 'nelioab' ) . '</h3>';
			self::print_mu_plugin_row();
		}

		public static function print_basic_section_ui() {
			echo '<br><br>';
			echo '<h3>' . __( 'User Interface', 'nelioab' ) . '</h3>';
		}

		public static function print_basic_section_misc() {
			echo '<br><br>';
			echo '<h3>' . __( 'Miscellaneous', 'nelioab' ) . '</h3>';
		}

		public static function print_pro_section() {
			echo '</div>';
			echo '<div id="nelioab-pro-section" style="display:none;">';
			if ( NelioABAccountSettings::get_subscription_plan() <
			     NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) {
				echo '<p>';
				printf(
					__( 'The following settings can only be modified by users subscribed to our <b>Professional</b> or <b>Enterprise Plans</b>.<br>If you want to have a finer control of the plugin\'s settings, <a target="_blank" href="%s">please upgrade your current subscription</a>.', 'nelioab' ),
					'mailto:support@neliosoftware.com?' .
						'subject=Nelio%20A%2FB%20Testing%20-%20Upgrade%20my%20Subscription&' .
						'body=' . esc_html( 'I\'d like to upgrade my subscription plan. I\'m subscribed to Nelio A/B Testing with the following e-mail address: ' . NelioABAccountSettings::get_email() . '.' )
				);
				echo '</p>';
			}
		}

		public static function close_last_section() {
			echo '</div>';
		}

		private static function get_basic_details( $classes = '' ) {
			return sprintf( ' class="basic %s" ', $classes );
		}

		private static function get_pro_details( $classes = '' ) {
			$result = '';
			if ( NelioABAccountSettings::get_subscription_plan() <
			     NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) {
				$result .= 'disabled="disabled" ';
				$classes .= ' setting-disabled';
			}
			$result .= sprintf( ' class="pro %s" ', $classes );
			return $result;
		}

		private static function prepare_basic_label( $label ) {
			return '<span class="basic-setting-label">' . $label . '</span>';
		}

		private static function prepare_pro_label( $label ) {
			if ( NelioABAccountSettings::get_subscription_plan() <
			     NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN )
				return '<span class="pro-setting-label setting-disabled">' . $label . '</span>';
			else
				return '<span class="pro-setting-label">' . $label . '</span>';
		}

		public static function print_mu_plugin_row() { ?>
			<table class="form-table">
				<tbody><tr>
					<th scope="row"><?php _e( 'AJAX Performance', 'nelioab' ); ?></th>
					<td><?php
						self::print_mu_plugin_settings();
					?></td>
				</tr></tbody>
			</table>
			<?php
		}

		public static function print_mu_plugin_settings() {
			$status = __( 'In order to boost response times of all AJAX requests triggered by Nelio A/B Testing, we include a tiny <a %1$s>Must Use Plugin</a> that disables other plugins when they\'re not necessary.<br><br><strong>AJAX Performance MU-Plugin Status: <span class="status %2$s">%3$s</span></strong>.<br><span class="explanation">%4$s</span>', 'nelioab' );
			if ( NelioABSettings::is_performance_muplugin_installed() &&
			     NelioABSettings::is_performance_muplugin_up_to_date() ) {
				$status = sprintf( $status,
					'target="_blank" href="http://codex.wordpress.org/Must_Use_Plugins"',
					'installed', __( 'Installed', 'nelioab' ),
					__( 'In order to uninstall the plugin, please use the previous «Uninstall» button.', 'nelioab' ) );
				$button = __( 'Uninstall', 'nelioab' );
			}
			elseif ( NelioABSettings::is_performance_muplugin_installed() ) {
				$status = sprintf( $status,
					'target="_blank" href="http://codex.wordpress.org/Must_Use_Plugins"',
					'outdated', __( 'Outdated', 'nelioab' ),
					__( 'In order to update the plugin, please use the previous «Update» button.', 'nelioab' ) );
				$button = __( 'Update', 'nelioab' );
			}
			else {
				$status = sprintf( $status,
					'target="_blank" href="http://codex.wordpress.org/Must_Use_Plugins"',
					'uninstalled', __( 'Not Installed', 'nelioab' ),
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
						if ( response.status === "OK" ) {
							button.text( "<?php _e( "Done!", "nelioab" ) ?>" );
							var s = descr.find(".status").first();
							var e = descr.find(".explanation").first();
							if ( s.hasClass( "outdated" ) ) {
								s.removeClass("outdated");
								s.addClass("installed");
								s.text( "<?php _e( 'Installed', 'nelioab' ); ?>" );
							}
							else if ( s.hasClass( "installed" ) ){
								s.removeClass("installed");
								s.addClass("uninstalled");
								s.text( "<?php _e( 'Not Installed', 'nelioab' ); ?>" );
							}
							else {
								s.removeClass("uninstalled");
								s.addClass("installed");
								s.text( "<?php _e( 'Installed', 'nelioab' ); ?>" );
							}
							e.css('visibility','hidden');
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
				'<input type="text" id="%1$s" name="nelioab_settings[%1$s]" value="%2$s" placeholder="%3$s" disabled="disabled" $4%s />',
				$field_name, NelioABSettings::get_def_conv_value(), NelioABSettings::DEFAULT_CONVERSION_VALUE, self::get_pro_details()
			);
		}

		public static function print_conv_unit_field() {
			$field_name = 'conv_unit';
			printf(
				'<input type="text" id="%1$s" name="nelioab_settings[%1$s]" value="%2$s" placeholder="%3$s" %4$s />',
				$field_name, NelioABSettings::get_conv_unit(), NelioABSettings::DEFAULT_CONVERSION_UNIT, self::get_basic_details()
			);
		}

		public static function print_algorithm_field() {
			$field_name = 'algorithm';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_pro_details()
			);
			?>
				<option value='<?php
					echo NelioABSettings::ALGORITHM_PURE_RANDOM; ?>'><?php
						_e( 'Default - Pure Random', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::ALGORITHM_PRIORITIZE_ORIGINAL; ?>' <?php
					if ( NelioABSettings::get_algorithm() == NelioABSettings::ALGORITHM_PRIORITIZE_ORIGINAL )
						echo ' selected="selected"'; ?>><?php
						_e( 'Prioritize Original Version', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::ALGORITHM_GREEDY; ?>' <?php
					if ( NelioABSettings::get_algorithm() == NelioABSettings::ALGORITHM_GREEDY )
						echo ' selected="selected"'; ?>><?php
						_e( 'Prioritize Winner (Greedy)', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_quota_limit_per_experiment_field() {
			$limit = NelioABSettings::get_quota_limit_per_exp();
			$field_name = 'quota_limit_per_exp';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_pro_details()
			);
			?>
				<option value='-1'><?php _e( 'Unlimited', 'nelioab' ); ?></option>
			<?php
			$options = array( 500, 1000, 1500, 2500, 3000 );
			if ( NelioABAccountSettings::get_subscription_plan() >=
			     NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN )
				array_push( $options, 4000, 5000, 7500, 10000 );
			foreach ( $options as $v ) {
				printf( '<option value="%2$s" %3$s>%1$s</option>',
					sprintf( __( '%s page views', 'nelioab' ), number_format_i18n( $v ) ),
					strval( $v ),
					( $limit == $v ) ? 'selected="selected"' : ''
				);
			}
			?>
			</select>
			<?php
		}

		public static function print_site_consistency_field() {
			$field_name = 'make_site_consistent';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='1'><?php _e( 'Force Consistency All Along the Site', 'nelioab' ); ?></option>
				<option value='0'<?php
					if ( !NelioABSettings::make_site_consistent() )
						echo ' selected="selected"';
				?>><?php _e( 'Load Alternative Content for Tested Elements Only', 'nelioab' ); ?></option>
			</select>
			<br><span class="description">
				<span class="<?php echo $field_name; ?> non-consistent"><?php
				_e( 'When a page or post experiment is created, alternative titles, contents, featured images, and excerpts may be defined. If consistency is not forced, the alternative contents will be loaded when accessing the tested page or post only. As a result, it is possible, for instance, that a user sees one title and featured image for a certain post in a widget, but a different title and featured image when she accesses that very same post (which may be confusing).', 'nelioab' );
			?></span>
				<span class="<?php echo $field_name; ?> consistent"><?php
				_e( 'When a page or post experiment is created, alternative titles, contents, featured images, and excerpts may be defined. Forcing consistency will ensure that your users see the same alternative version all along the site, because all pages will be loading the alternative information defined in your experiments. Note this setting does <strong>not</strong> consume more quota.', 'nelioab' );
			?></span>
			</span>
			<script type="text/javascript">
			(function($) {
				var selector = $('#<?php echo $field_name; ?>');
				var con = $('.<?php echo $field_name; ?>.consistent');
				var noncon = $('.<?php echo $field_name; ?>.non-consistent');
				selector.on('change', function() {
					if ( selector.val() == '1' ) {
						con.show();
						noncon.hide();
					}
					else {
						con.hide();
						noncon.show();
					}
				});
				selector.trigger('change');
			})(jQuery);
			</script>
			<?php
		}

		public static function print_alternative_loading_mode_field() {
			$field_name = 'alt_load_mode';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='<?php
							echo NelioABSettings::POST_ALTERNATIVE_LOADING_MODE;
					?>'><?php _e( 'Bypass Cache and Load Content Using POST Requests', 'nelioab' ); ?></option>
				<option value='<?php
							echo NelioABSettings::GET_ALTERNATIVE_LOADING_MODE;
						?>'<?php
					if ( NelioABSettings::get_alternative_loading_mode() == NelioABSettings::GET_ALTERNATIVE_LOADING_MODE )
						echo ' selected="selected"';
				?>><?php _e( 'Cache Alternative Content by Using GET Requests (Experimental)', 'nelioab' ); ?></option>
			</select>
			<br><span class="description">
				<span class="<?php echo $field_name; ?>"><?php
				_e( 'By default, Nelio A/B Testing loads alternative content using an AJAX POST request. Nelio uses POST requests because they are usually ignored by cache mechanisms, ensuring that your users will be able to see different alternatives (instead of all users seeing the cached version). Unfortunately, this results in a higher server load, for alternative content is always processed by WordPress.<br>Starting at version 3.3, Nelio introduces experimental support for loading alternatives using GET requests. This approach permits your cache system to cache alternative contents too, boosting the overall performance.', 'nelioab' );
			?></span>
			</span>
			<?php
		}

		public static function print_colorblindness_field() {
			$field_name = 'use_colorblind';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
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

		public static function print_menu_location_field() {
			$field_name = 'menu_location';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='<?php
					echo NelioABSettings::MENU_LOCATION_DASHBOARD; ?>'><?php
						_e( 'Top of the Dashboard', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::MENU_LOCATION_APPEARANCE; ?>' <?php
					if ( NelioABSettings::get_menu_location() == NelioABSettings::MENU_LOCATION_APPEARANCE )
						echo ' selected="selected"'; ?>><?php
						_e( 'Above Appearance', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::MENU_LOCATION_TOOLS; ?>' <?php
					if ( NelioABSettings::get_menu_location() == NelioABSettings::MENU_LOCATION_TOOLS )
						echo ' selected="selected"'; ?>><?php
						_e( 'Below Tools', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::MENU_LOCATION_LAST_BLOCK; ?>' <?php
					if ( NelioABSettings::get_menu_location() == NelioABSettings::MENU_LOCATION_LAST_BLOCK )
						echo ' selected="selected"'; ?>><?php
						_e( 'First Option in Last Block', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::MENU_LOCATION_END; ?>' <?php
					if ( NelioABSettings::get_menu_location() == NelioABSettings::MENU_LOCATION_END )
						echo ' selected="selected"'; ?>><?php
						_e( 'Latest Option', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_menu_in_admin_bar_field() {
			$field_name = 'menu_in_admin_bar';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='1'><?php _e( 'Show «Nelio A/B Testing» Menu in Admin Bar', 'nelioab' ); ?></option>
				<option value='0'<?php
					if ( !NelioABSettings::is_menu_enabled_for_admin_bar() )
						echo ' selected="selected"';
				?>><?php _e( 'Hide «Nelio A/B Testing» Menu from Admin Bar', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_notification_email_field() {
			$field_name = 'notification_email';
			printf(
				'<div class="nelio-sect"><input type="text" id="%1$s" name="nelioab_settings[%1$s]" style="max-width:400px;width:100%%;" value="%3$s" placeholder="%4$s" %2$s></div>',
				$field_name, self::get_basic_details(),
				esc_html( NelioABSettings::get_notification_email() ),
				sprintf( __( 'Default: %s', 'nelioab' ), esc_html( NelioABAccountSettings::get_email() ) )
			);
			?>
			<br><span class="description"><?php
				printf(
					__( 'If you type an e-mail address, all Nelio A/B Testing notifications will be sent to both the new address and «%s».', 'nelioab' ),
					NelioABAccountSettings::get_email()
				);
			?></span>
			<script>
				(function($) {
					var mail = $('#<?php echo $field_name; ?>');
					var form = $('#nelioab-settings');
					var save;
					function validateMail() {
						var x = mail.attr('value');
						if ( x.length == 0 )
							return true;
						var atpos = x.indexOf('@');
						var dotpos = x.lastIndexOf('.');
						if (atpos< 1 || dotpos<atpos+2 || dotpos+2>=x.length)
							return false;
						return true;
					}
					function control() {
						if ( validateMail() ) {
							mail.removeClass('error');
							save.removeClass('disabled');
							form.unbind('submit', returnFalse);
						}
						else {
							mail.addClass('error');
							save.addClass('disabled');
							form.on('submit', returnFalse);
						}
					}
					function returnFalse() { return false; }
					mail.on('keyup focusout', control);
					$(document).ready(function() {
						save = $('#submit');
						control();
					});
				})(jQuery);
			</script>
			<?php
		}

		public static function print_notifications_field() {
			$cb = '<p><input type="checkbox" id="%1$s" name="nelioab_settings[%1$s]" %3$s %4$s />%2$s</p>';
			printf( $cb, 'notify_exp_finalization',
					__( 'Notify me when an experiment is automatically stopped.', 'nelioab' ),
					self::checked( NelioABSettings::is_notification_enabled( NelioABSettings::NOTIFICATION_EXP_FINALIZATION ) ),
					self::get_pro_details()
				);
		}

		private static function checked( $checked ) {
			if ( $checked )
				return 'checked="checked"';
			else
				return '';
		}

		public static function print_show_finished_experiments_field() {
			$field_name = 'show_finished_experiments';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='<?php
					echo NelioABSettings::FINISHED_EXPERIMENTS_HIDE_ALL;
					?>'><?php _e( 'Hide Finished Experiments', 'nelioab' ); ?></option>
				<option value='<?php
					echo NelioABSettings::FINISHED_EXPERIMENTS_SHOW_RECENT; ?>'<?php
					if ( NelioABSettings::FINISHED_EXPERIMENTS_SHOW_RECENT == NelioABSettings::show_finished_experiments() )
						echo ' selected="selected"';
				?>><?php _e( 'Show Recently Finished Experiments', 'nelioab' ); ?></option>
				<option
					value='<?php echo NelioABSettings::FINISHED_EXPERIMENTS_SHOW_ALL; ?>'<?php
					if ( NelioABSettings::FINISHED_EXPERIMENTS_SHOW_ALL == NelioABSettings::show_finished_experiments() )
						echo ' selected="selected"';
				?>><?php _e( 'Show All Finished Experiments', 'nelioab' ); ?></option>
			</select>
			<?php
		}

		public static function print_min_confidence_for_significance_field() {
			$field_name = 'min_confidence_for_significance';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="50" max="100" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_min_confidence_for_significance(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( 'Minimum confidence value is set to <strong>{value}%</strong>.<br>The confidence value tells you how "trustable" is the fact that one alternative is better than the original.', 'nelioab' );
						$str = str_replace( '"', '\\"', $str );
						echo $str;
					?>";
					var value = jQuery(this).attr('value');
					if ( value == '100' )
						value = '99';
					str = str.replace( '{value}', value );
					jQuery("#value_<?php echo $field_name; ?>").html(str);
				});
				jQuery("#<?php echo $field_name; ?>").trigger("change");
			</script>
			<?php
		}

		public static function print_perc_of_tested_users_field() {
			$field_name = 'perc_of_tested_users';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="10" max="100" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_percentage_of_tested_users(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( '<strong>{value}%</strong> of the users that access your site will participate in the running experiments.', 'nelioab' );
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

		public static function print_ori_perc_field() {
			$field_name = 'ori_perc';
			echo '<b>' . self::prepare_pro_label( __( 'Original Percentage', 'nelioab' ) ) . '</b><br><br>';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="55" max="95" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_original_percentage(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#algorithm").on("change", function() {
					var option = jQuery("#<?php echo $field_name; ?>").parent().parent();
					if ( jQuery(this).attr('value') == <?php echo NelioABSettings::ALGORITHM_PRIORITIZE_ORIGINAL; ?> ) option.show();
					else option.hide();
				});
				jQuery("#algorithm").trigger("change");
				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( '<strong>{value}%</strong> of your visitors will see the original version of the experiment.<br>The rest of the users will see the other alternatives.', 'nelioab' );
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

		public static function print_expl_ratio_field() {
			$field_name = 'expl_ratio';
			echo '<b>' . self::prepare_pro_label( __( 'Exploitation or Exploration', 'nelioab' ) ) . '</b><br><br>';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="10" max="90" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_exploitation_percentage(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#algorithm").on("change", function() {
					var option = jQuery("#<?php echo $field_name; ?>").parent().parent();
					if ( jQuery(this).attr('value') == <?php echo NelioABSettings::ALGORITHM_GREEDY; ?> ) option.show();
					else option.hide();
				});
				jQuery("#algorithm").trigger("change");
				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( '<strong>{value}%</strong> of your visitors will see the winning alternative.<br>The rest of the users will see the other alternatives.', 'nelioab' );
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

		public static function print_heatmap_tracking_mode_field() {
			$field_name = 'hm_tracking_mode';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='<?php
							echo NelioABSettings::ELEMENT_BASED_HEATMAP_TRACKING;
					?>'><?php _e( 'High Accuracy - Use All HTML Elements', 'nelioab' ); ?></option>
				<option value='<?php
							echo NelioABSettings::HTML_BASED_HEATMAP_TRACKING;
						?>'<?php
					if ( NelioABSettings::get_heatmap_tracking_mode() == NelioABSettings::HTML_BASED_HEATMAP_TRACKING )
						echo ' selected="selected"';
				?>><?php _e( 'Tolerance to Random IDs - Use Body Tag', 'nelioab' ); ?></option>
			</select>
			<br><span class="description">
				<span class="<?php echo $field_name; ?>"><?php
				_e( 'By default, Nelio A/B Testing takes into account the elements that are below your users\' cursor to track and build heatmaps and clickmaps. This offers a higher accuracy when Heatmaps are displayed, because hot spots are based on elements and not pages. Unfortunately, this approach may not work if, for instance, your page loads elements dynamically or HTML element IDs are randomly generated. If this is your case, track Heatmaps using the body tag.', 'nelioab' );
			?></span>
			</span>
			<?php
		}

	}//NelioABSettingsPage

}

