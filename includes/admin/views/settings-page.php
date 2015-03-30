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
				if ( in_array( 'algorithm', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Algorithm', 'nelioab' ) . '</li>';
				if ( in_array( 'make_site_consistent', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Site-wide Consistency', 'nelioab' ) . '</li>';
				if ( in_array( 'expl_ratio', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Exploitation or Exploration (using Greedy Algorithm)', 'nelioab' ) . '</li>';
				if ( in_array( 'get_params_visibility', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'A/B GET Params', 'nelioab' ) . '</li>';
				if ( in_array( 'hm_tracking_mode', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Heatmap Tracking', 'nelioab' ) . '</li>';
				if ( in_array( 'user_split', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Test Mode', 'nelioab' ) . '</li>';
				if ( in_array( 'ori_perc', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Original Percentage (using Prioritize Original Algorithm)', 'nelioab' ) . '</li>';
				if ( in_array( 'perc_of_tested_users', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Num. of Tested Users', 'nelioab' ) . '</li>';
				if ( in_array( 'quota_limit_per_exp', $ae_sync_errors ) )
					$errors .= '<li>- ' . __( 'Quota Limit', 'nelioab' ) . '</li>';
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
				'get_params_visibility',
				self::prepare_basic_label( __( 'A/B GET Params', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_get_params_visibility_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
			);

			add_settings_field(
				'user_split',
				self::prepare_basic_label( __( 'Test Mode', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_user_split_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
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
				'on_blank',
				self::prepare_basic_label( __( 'External Page Actions', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_on_blank_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
			);

			add_settings_field(
				'hm_tracking_mode',
				self::prepare_basic_label( __( 'Heatmap Tracking', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_heatmap_tracking_mode_field' ),
				'nelioab-settings',
				'nelioab_basic_section_efficiency'
			);


			if ( is_multisite() && is_super_admin() ) {
				add_settings_section(
					'nelioab_basic_section_ms', '',
				// ===============================================================
					array( 'NelioABSettingsPage', 'print_basic_section_ms' ),
				// ===============================================================
					'nelioab-settings'
				);

				add_settings_field(
					'plugin_available_to',
					self::prepare_basic_label( __( 'Plugin Available To', 'nelioab' ) ),
					// -------------------------------------------------------------
					array( 'NelioABSettingsPage', 'print_plugin_available_to_field' ),
					'nelioab-settings',
					'nelioab_basic_section_ms'
				);
			}


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
				'theme_landing_page',
				self::prepare_basic_label( __( 'Front Page', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_theme_landing_page_field' ),
				'nelioab-settings',
				'nelioab_basic_section_misc'
			);

			add_settings_field(
				'def_conv_value',
				self::prepare_basic_label( __( 'Default Conversion Value', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_def_conv_value_field' ),
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
				self::prepare_pro_label( __( 'Quota Limit', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_quota_limit_per_experiment_field' ),
				'nelioab-settings',
				'nelioab_pro_section'
			);

			add_settings_field(
				'headlines_quota_mode',
				self::prepare_pro_label( __( 'Headline Testing', 'nelioab' ) ),
				// -------------------------------------------------------------
				array( 'NelioABSettingsPage', 'print_headlines_quota_mode_field' ),
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
				self::prepare_pro_label( __( 'Notifications', 'nelioab' ), false ),
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
			echo '<h3>' . __( 'Plugin Behavior', 'nelioab' ) . '</h3>';
		}

		public static function print_basic_section_ms() {
			echo '<br><br>';
			echo '<h3>' . __( 'Multi-Site Settings', 'nelioab' ) . '</h3>';
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
			?>
			<script type="text/javascript">
			jQuery('.dashicons-editor-help').each(function() {
				if ( !jQuery(this).parent().hasClass('setting-disabled') )
					jQuery(this).hover(function() {
						jQuery(this).css('cursor','hand');
						jQuery(this).css('cursor','pointer');
					}, function() {
						jQuery(this).css('cursor','default');
					});
			});
			jQuery('.dashicons-editor-help').each(function() {
				if ( !jQuery(this).parent().hasClass('setting-disabled') )
					jQuery(this).click(function() {
						jQuery(this).parent().parent().parent().find('.the-descr').slideToggle();
					});
			});
			</script>
			<?php
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

		private static function get_icon( $color ) {
			return '<span class="dashicons dashicons-editor-help"' .
				' style="color:' . $color . '"></span>';
		}

		private static function prepare_basic_label( $label, $help = true ) {
			$aux = NelioABWpHelper::get_current_colorscheme();
			$color = $aux['primary'];
			$res = '<span class="basic-setting-label">' . $label;
			if ( $help )
				$res .= ' ' . self::get_icon( $color );
			$res .= '</span>';
			return $res;
		}

		private static function prepare_pro_label( $label, $help = true ) {
			if ( NelioABAccountSettings::get_subscription_plan() <
			     NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) {
				$res = '<span class="pro-setting-label setting-disabled">' . $label;
				if ( $help )
					$res .= ' ' . self::get_icon( '#AAA' );
				$res .= '</span>';
				return $res;
			}
			else {
				$aux = NelioABWpHelper::get_current_colorscheme();
				$color = $aux['primary'];
				$res = '<span class="pro-setting-label">' . $label;
				if ( $help )
					$res .= ' ' . self::get_icon( $color );
				$res .= '</span>';
				return $res;
			}
		}

		public static function print_def_conv_value_field() {
			$field_name = 'def_conv_value';
			$value = sprintf(
				'<input type="text" id="%1$s" name="nelioab_settings[%1$s]" value="%2$s" placeholder="%3$s" $4%s style="max-width:5em;" />',
				$field_name, NelioABSettings::get_def_conv_value(), NelioABSettings::DEFAULT_CONVERSION_VALUE, self::get_basic_details()
			);

			$field_name = 'conv_unit';
			ob_start();
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<?php $val = NelioABSettings::CONVERSION_UNIT_DOLLAR; ?>
				<option value='<?php echo $val ?>'><?php echo $val; ?></option>
				<?php $val = NelioABSettings::CONVERSION_UNIT_EURO; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_conv_unit() == $val )
						echo ' selected="selected"';
				?>><?php echo $val; ?></option>
				<?php $val = NelioABSettings::CONVERSION_UNIT_POUND; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_conv_unit() == $val )
						echo ' selected="selected"';
				?>><?php echo $val; ?></option>
				<?php $val = NelioABSettings::CONVERSION_UNIT_YEN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_conv_unit() == $val )
						echo ' selected="selected"';
				?>><?php echo $val; ?></option>
				<?php $val = NelioABSettings::CONVERSION_UNIT_BITCOIN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_conv_unit() == $val )
						echo ' selected="selected"';
				?>><?php echo $val; ?></option>
			</select>
			<?php
			$unit = ob_get_contents();
			ob_end_clean();

			printf( __( '%1$s %2$s', 'nelioab', 'conversion-unit-and-value' ), $unit, $value );
			?>
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'Define a default value (in money units) for your conversions. This value could be, for instance, the average benefit you obtain every time a visitor converts (that is, she buys a product or subscribes to a service). The conversion value can be overwritten when adding goals into an experiment.', 'nelioab' );
			?></span></div><?php
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
			<br><div class="the-descr" style="display:none;"><span <?php echo self::get_pro_details( 'description' ); ?>><?php
				_e( 'Nelio A/B Testing implements different algorithms for deciding which alternative should see each of your visitors.', 'nelioab' );
				echo '<ul style="list-style-type:disc;margin-left:3em;">';
				_e( '<li><strong>Pure Random.</strong> All alternatives have the exact same chance to be seen by any of your visitors.</li>', 'nelioab' );
				_e( '<li><strong>Prioritize Original.</strong> The original version is more likely to be shown.</li>', 'nelioab' );
				_e ('<li><strong>Greedy.</strong> Also known as <em>multi-armed bandit</em>, this algorithm makes the best alternative (that is, the one that\'s converting the best) more likely to appear.</li>', 'nelioab' );
				echo '</ul>';
			?></span></div>
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
			<br><div class="the-descr" style="display:none;"><span <?php echo self::get_pro_details( 'description' ); ?>><?php
				_e( 'This setting will automatically stop the experiment as soon as the results of an experiment are updated and the number of page views is equal to (or a little bit greater than) the limit you set. Don\'t waste your quota anymore!', 'nelioab' );
			?></span></div>
			<?php
		}

		public static function print_headlines_quota_mode_field() {
			$field_name = 'headlines_quota_mode';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_pro_details()
			);
			?>
				<option value='<?php
						echo NelioABSettings::HEADLINES_QUOTA_MODE_ALWAYS
					?>'><?php _e( 'All Pages Are Relevant for Headline Tracking', 'nelioab' ); ?></option>
				<option value='<?php
							echo NelioABSettings::HEADLINES_QUOTA_MODE_ON_FRONT_PAGE;
						?>'<?php
					if ( NelioABSettings::get_headlines_quota_mode() == NelioABSettings::HEADLINES_QUOTA_MODE_ON_FRONT_PAGE )
						echo ' selected="selected"';
				?>><?php _e( 'Only the Front Page Is Relevant for Headline Tracking', 'nelioab' ); ?></option>
			</select>
			<br><div class="the-descr" style="display:none;"><span <?php echo self::get_pro_details( 'description' ); ?>><?php
				_e( 'During a Headline Test, Page Views are counted every time a tested Headline appears on a page in your website. This includes sidebars, latest posts, menus, and so on. If you want to limit the quota usage of your Headline experiments, you can now test them on the Front Page only.<br>Regardless of your setting, Headlines will replaced all over your site, so that users always see coherent headlines for your posts.', 'nelioab' );
			?></span></div>
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'When a page or post experiment is created, alternative titles, contents, featured images, and excerpts may be defined. Where and when is this alternative content loaded?', 'nelioab' );
				echo '<ul style="list-style-type:disc;margin-left:3em;">';
				_e( '<li><strong>Force Consistency</strong>. It ensures that your users see the same alternative version all along the site. If enabled, all pages will be loading the alternative information defined in your experiments. Note this setting does <strong>not</strong> consume more quota.</li>', 'nelioab' );
				_e( '<li><strong>Tested Elements Only</strong>. If consistency is not forced, the alternative contents will be loaded when accessing the tested page or post only. Thus, for instance, it is possible that a user sees the original title and featured image of a tested post in a widget, but a different, alternative title and featured image when she accesses that very same post (which may be confusing).</li>', 'nelioab' );
				echo '</ul>';
			?></span></div><?php
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'If you enable the Colorblind Palette, Nelio A/B Testing icons will not be simple, plain colors, but they will include visual clues.', 'nelioab' );
			?></span></div><?php
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'Set the location of «Nelio A/B Testing» in the left pane of the Dashboard.', 'nelioab' );
			?></span></div><?php
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
			</select>
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'When browsing your site as an admin, the plugin adds a «Nelio A/B Testing» entry in the top bar, which includes some shortcuts to common A/B Testing functions.', 'nelioab' );
			?></span></div><?php
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				printf(
					__( 'If you type an e-mail address, all Nelio A/B Testing notifications will be sent to both the new address and «%s».', 'nelioab' ),
					NelioABAccountSettings::get_email()
				);
			?></span></div>
			<script type="text/javascript">
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'By default, the list of experiments in our plugin shows all the experiments you defined. You may filter the experiments by status. When viewing all experiments, you can decide whether <em>finished</em> experiments should be visible or not.', 'nelioab' );
			?></span></div><?php
		}

		public static function print_min_confidence_for_significance_field() {
			$field_name = 'min_confidence_for_significance';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="90" max="100" step="1" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_min_confidence_for_significance(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#<?php echo $field_name; ?>").on("input change", function() {
					var str = "<?php
						$str = __( 'Minimum confidence value is set to <strong>{value}%</strong> (at least 95% is recommended).', 'nelioab' );
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
			<div class="the-descr" style="display:none;"><span <?php echo self::get_pro_details( 'description' ); ?>><?php
				_e( 'The confidence value tells you how "trustable" is the fact that one alternative is better than the original. Changing this value will modify the visual clues in Nelio\'s Dashboard and Progress of the Experiment (lower confidence values means that the "green light" appears sooner).', 'nelioab' );
			?></span></div>
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
			<div class="the-descr" style="display:none;"><span <?php echo self::get_pro_details( 'description' ); ?>><?php
				_e( 'When a user accesses your website she may participate in your running experiments. This setting defines how likely it is for a visitor to be part of your tests.', 'nelioab' );
			?></span></div>
			<?php
		}

		public static function print_ori_perc_field() {
			$field_name = 'ori_perc';
			echo '<b>' . self::prepare_pro_label( __( 'Original Percentage', 'nelioab' ), false ) . '</b><br><br>';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="55" max="95" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_original_percentage(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#algorithm").on("change", function() {
					var option = jQuery("#<?php echo $field_name; ?>").parent().parent();
					if ( jQuery(this).attr('value') == '<?php echo NelioABSettings::ALGORITHM_PRIORITIZE_ORIGINAL; ?>' ) option.show();
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
			echo '<b>' . self::prepare_pro_label( __( 'Exploitation or Exploration', 'nelioab' ), false ) . '</b><br><br>';
			printf(
				'<input type="range" id="%1$s" name="nelioab_settings[%1$s]" min="10" max="90" step="5" value="%2$s" %3$s /><br>',
				$field_name, NelioABSettings::get_exploitation_percentage(), self::get_pro_details()
			);
			?>
			<span <?php echo self::get_pro_details( 'description' ); ?> id="value_<?php echo $field_name; ?>"></span>
			<script type="text/javascript">
				jQuery("#algorithm").on("change", function() {
					var option = jQuery("#<?php echo $field_name; ?>").parent().parent();
					if ( jQuery(this).attr('value') == '<?php echo NelioABSettings::ALGORITHM_GREEDY; ?>' ) option.show();
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
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'By default, Nelio A/B Testing takes into account the elements that are below your users\' cursor to track and build heatmaps and clickmaps. This offers a higher accuracy when Heatmaps are displayed, because hot spots are based on elements and not pages. Unfortunately, this approach may not work if, for instance, your page loads elements dynamically or HTML element IDs are randomly generated. If this is your case, track Heatmaps using the body tag.', 'nelioab' );
			?></span></div><?php
		}

		public static function print_plugin_available_to_field() {
			$field_name = 'plugin_available_to';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_pro_details()
			);
			?>
				<?php $val = NelioABSettings::PLUGIN_AVAILABLE_TO_SITE_SETTING; ?>
				<option value='<?php echo $val ?>'><?php
					printf(
						__( 'Inherit Multi-Site Setting (%s)', 'nelioab' ),
						( NelioABSettings::get_site_option_regular_admins_can_manage_plugin() ) ?
							__( 'All Admins', 'nelioab' ) : __( 'Super Admins Only', 'nelioab' )
					);
				?></option>
				<?php $val = NelioABSettings::PLUGIN_AVAILABLE_TO_ANY_ADMIN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_plugin_available_to() == $val )
						echo ' selected="selected"';
				?>><?php
					_e( 'Super Admins and Site Admins', 'nelioab' );
				?></option>
				<?php $val = NelioABSettings::PLUGIN_AVAILABLE_TO_SUPER_ADMIN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_plugin_available_to() == $val )
						echo ' selected="selected"';
				?>><?php
					_e( 'Super Admins Only', 'nelioab' );
				?></option>
			</select>
			<?php
		}

		public static function print_on_blank_field() {
			$field_name = 'on_blank';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='1'><?php
					_e( 'Force New Tab (add «target="_blank"» attribute)', 'nelioab' ); ?></option>
				<option value='0'<?php
					if ( !NelioABSettings::use_outwards_navigations_blank() )
						echo ' selected="selected"';
				?>><?php _e( 'Do Not Force New Tab', 'nelioab' ); ?></option>
			</select>
			<div class="the-descr" style="display:none;"><span class="description"><?php
				_e( 'When using external page conversion actions, a conversion should be counted when users access the external page. However, Nelio A/B Testing has no access to pages outside WordPress, which means it has to count a conversion when a user clicks on the link that will take the user to that page. Whenever such a click occurs, Nelio A/B Testing will detect it and send the tracking information to Nelio\'s servers (which takes a few miliseconds). If you want the website to be as responsive as possible, select «Force New Tab». This way, links will be opened in a new tab and the synchronization process will occur in the original tab. If, on the other hand, you don\'t want pages to be opened in new tabs, select «Do Not Force New Tab».', 'nelioab' );
			?></span></div><?php
		}

		public static function print_user_split_field() {
			$field_name = 'user_split';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<?php $val = NelioABSettings::USER_SPLIT; ?>
				<option value='<?php echo $val ?>'><?php
					_e( 'Group Experiments and Divide Visitors', 'nelioab' ); ?></option>
				?></option>
				<?php $val = NelioABSettings::USER_ALLIN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_split_user_mode() == $val )
						echo ' selected="selected"';
				?>><?php
					_e( 'Test All Experiments with All Visitors', 'nelioab' );
				?></option>
			</select>
			<div class="the-descr" style="display:none;"><span class="description"><?php
				printf( __( 'When running more than one experiment with multiple alternatives each one, the total number of combinations grows exponentially, which may degrade your server\'s performance. To overcome this issue, enable the option «Group Experiments and Divide Visitors». If enabled, the plugin creates one or more experiment groups, each of which will test a few experiments only. When a visitor accesses your website, she only participates in the experiments of one of the available groups. <a%s>Read More</a>', 'nelioab' ),
				' href="http://support.nelioabtesting.com/solution/articles/1000167944" target="_blank"' );
			?></span></div><?php
		}

		public static function print_get_params_visibility_field() {
			$field_name = 'get_params_visibility';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<?php $val = NelioABSettings::GET_PARAMS_VISIBILITY_HIDE_NONE; ?>
				<option value='<?php echo $val ?>'><?php
					_e( 'Show All A/B Testing Parameters (including context)', 'nelioab' );
				?></option>
				<?php $val = NelioABSettings::GET_PARAMS_VISIBILITY_HIDE_CONTEXT; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_params_visibility() == $val )
						echo ' selected="selected"';
				?>><?php
					_e( 'Show This Page A/B Testing Parameter (hide context)', 'nelioab' );
				?></option>
				<?php $val = NelioABSettings::GET_PARAMS_VISIBILITY_HIDE_ALL; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( NelioABSettings::get_params_visibility() == $val )
						echo ' selected="selected"';
				?>><?php
					_e( 'Hide All A/B Testing Parameters', 'nelioab' );
				?></option>
			</select>
			<div class="the-descr" style="display:none;"><span class="description"><?php
			_e( 'Nelio A/B Testing loads alternative content by adding a few parameters in your URL. In principle, this parameters are visible to your visitors, but you can decide whether they should be hidden or not and, if they should, which ones.', 'nelioab' );
			echo '<ul style="list-style-type:disc;margin-left:3em;">';
			_e( '<li><strong>Show All Params.</strong> All A/B Testing parameters will be shown.</li>', 'nelioab' );
			_e( '<li><strong>Hide Context Params.</strong> If you\'re on a tested page (and/or if you\'re running a global experiment, such as a Widget or a Menu experiment), the URL will contain a parameter <code>nab</code> that specifies the alternative to be loaded (and/or another parameter <code>nabx</code> for the global experiment).</li>', 'nelioab' );
			_e( '<li><strong>Hide All Params.</strong> Once the alternative content has been loaded, all A/B Testing params are removed from the URL.</li>', 'nelioab' );
			echo '</ul>';
			?></span></div><?php
		}

		public static function print_theme_landing_page_field() {
			$field_name = 'theme_landing_page';
			printf(
				'<select id="%1$s" name="nelioab_settings[%1$s]" %2$s>',
				$field_name, self::get_basic_details()
			);
			?>
				<option value='0'><?php
					_e( 'Regular Front Page («Latest Posts» or «Static Page»)', 'nelioab' ); ?></option>
				<option value='1'<?php
					if ( NelioABSettings::does_theme_use_a_custom_landing_page() )
						echo ' selected="selected"';
				?>><?php _e( 'Theme-based Front Page', 'nelioab' ); ?></option>
			</select>
			<div class="the-descr" style="display:none;"><span class="description"><?php
				printf( __( 'As stated in the <a href="%s">WordPress Codex</a>, by default WordPress shows your most recent posts in reverse chronological order on the front page (also known as "landing page") of your site. If you want a static front page or splash page as the front page instead, you may select it using the "Front page display" setting Dashboard » Settings » Reading.<br>Some themes, however, define "dynamic front pages", which can not be A/B tested by Nelio. If you want to track Heatmaps for such a front page, simply select "Theme-based Front Page". ', 'nelioab' ), 'http://codex.wordpress.org/Creating_a_Static_Front_Page' );
			?></span></div><?php
		}

	}//NelioABSettingsPage

}

