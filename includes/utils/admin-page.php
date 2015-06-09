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

if ( !class_exists( 'NelioABAdminPage' ) ) {

	/**
	 * This class is an abstract page.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABAdminPage {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		protected $title;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		protected $title_action;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		protected $icon_id;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		protected $message;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		protected $classes;


		/**
		 * It creates a new instance of this class.
		 *
		 * @param string $title The title of the page.
		 *                      Default: empty string.
		 *
		 * @return NelioABAdminPage a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $title = '' ) {
			$this->title        = $title;
			$this->title_action = '';
			$this->icon_id      = 'icon-options-general';
			$this->classes      = array();

			$this->message = false;
			try {
				$config = NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				$config = false;
			}
			if ( $config && NelioABSettings::is_upgrade_message_visible() ) {
				$this->message = sprintf(
					__( '<b><a href="%s">Upgrade to our Professional Plan</a></b> and get the most out of Nelio A/B Testing. Track <b>more visitors</b>, use the service on <b>more sites</b>, and benefit from our <b>consulting services</b>. <small><a class="dismiss-upgrade-notice" href="#" onClick="javascript:dismissUpgradeNotice();">Dismiss</a></small>', 'nelioab' ),
					'mailto:support@neliosoftware.com?' .
						'subject=Nelio%20A%2FB%20Testing%20-%20Upgrade%20my%20Subscription&' .
						'body=' . esc_html( 'I\'d like to upgrade my subscription plan. I\'m subscribed to Nelio A/B Testing with the following e-mail address: ' . NelioABAccountSettings::get_email() . '.' )
				);
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param string $message PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_message( $message ) {
			$this->message = $message;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $title PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_title( $title ) {
			$this->title = $title;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $icon_id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_icon( $icon_id ) {
			$this->icon_id = $icon_id;
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected abstract function do_render();


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function print_page_buttons() {
		}


		/**
		 * PHPDOC
		 *
		 * @param string $class PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function add_class( $class ) {
			array_push( $this->classes, $class );
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function render() { ?>
			<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/tablesorter.min.js' ); ?>"></script>
			<div class="wrap <?php echo implode( ' ', $this->classes ); ?>">
				<div class="icon32" id="<?php echo $this->icon_id; ?>"></div>
				<h2><?php echo $this->title . ' ' . $this->title_action; ?></h2>
				<?php
						$this->print_global_warnings();
						$this->print_error_message();
						$this->print_message();
						$this->print_errors();
				?>
				<br />
				<?php $this->do_render(); ?>
				<br />
				<div class="actions"><?php
					$this->print_page_buttons(); ?>
				</div>
			</div>
			<div id="dialog-modal" title="Basic modal dialog" style="display:none;">
				<div id="dialog-content">
					<?php $this->print_dialog_content(); ?>
				</div>
			</div>
			<?php
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_dialog_content() {
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_global_warnings() {
			global $nelioab_admin_controller;
			$warnings = $nelioab_admin_controller->global_warnings;
			if ( !isset( $warnings ) || $warnings == NULL || count( $warnings ) == 0 )
				return;
			?>
			<div id="global-warnings-div"
				class="updated below-h2">
				<ul style="padding-left:1em;"><?php
					foreach ( $warnings as $warning )
						echo "<li>$warning</li>";
				?>
				</ul>
			</div><?php
		}


		/**
		 * PHPDOC
		 *
		 * @param string $display PHPDOC
		 *                        Default: block.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_error_message( $display = 'block' ) {
			global $nelioab_admin_controller;
			$message = $nelioab_admin_controller->error_message;
			$aux_class = '';
			if ( !isset( $message ) || $message == NULL || strlen( $message ) == 0 )
				$display = 'none';
			else
				$aux_class = 'to-be-shown';
			?>
			<div id="error-message-div"
				class="error below-h2 <?php echo $aux_class; ?>"
				style="display:<?php echo $display; ?>">
					<?php $this->print_error_message_content(); ?>
			</div><?php
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_error_message_content() {
			global $nelioab_admin_controller;
			$message = $nelioab_admin_controller->error_message;
			if ( isset( $message ) && $message != NULL && strlen( $message ) > 0 )
				echo '<p>' . $message . '</p>';
		}


		/**
		 * PHPDOC
		 *
		 * @param string $display PHPDOC
		 *                        Default: block.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_message( $display = 'block' ) {
			global $nelioab_admin_controller;
			$message = $nelioab_admin_controller->message;
			$aux_class = '';
			if ( !isset( $message ) || $message == NULL || strlen( $message ) == 0 ) {
				if ( !$this->message )
					$display = 'none';
			}
			else {
				$aux_class = 'to-be-shown';
			}
			?>
			<div id="message-div"
				class="updated below-h2 <?php echo $aux_class; ?>"
				style="display:<?php echo $display; ?>">
					<?php $this->print_message_content(); ?>
			</div>
			<script type="text/javascript" >
			function dismissUpgradeNotice() {
				var data = { action: 'dismiss_upgrade_notice' };
				jQuery.post(ajaxurl, data, function() {
					jQuery("#message-div").fadeOut();
				});
			}
			</script>
			<?php
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_message_content() {
			if ( $this->message ) {
				echo '<p>' . $this->message . '</p>';
			}
			else {
				global $nelioab_admin_controller;
				$message = $nelioab_admin_controller->message;
				if ( isset( $message ) && $message != NULL && strlen( $message ) > 0 )
					echo '<p>' . $message . '</p>';
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param string $display PHPDOC
		 *                        Default: block.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_errors( $display = 'block' ) {
			global $nelioab_admin_controller;
			$aux_class = '';
			if ( count( $nelioab_admin_controller->validation_errors ) == 0 )
				$display = 'none';
			else
				$aux_class = 'to-be-shown';
			?>
			<div id="errors-div"
				class="error <?php echo $aux_class; ?>"
				style="display:<?php echo $display; ?>"><?php
					$this->print_errors_content();
			?>
			</div><?php
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_errors_content() {
			global $nelioab_admin_controller;
			if ( count( $nelioab_admin_controller->validation_errors ) > 0 ) { ?>
				<p><?php echo _('The following errors have been encountered:'); ?></p>
				<ul style="padding-left:2em;"><?php
					foreach ( $nelioab_admin_controller->validation_errors as $err )
						echo '<li>&ndash; ' . $err[1] . '</li>'; ?>
				</ul>
			<?php
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param string $section_title PHPDOC
		 * @param array  $fields        PHPDOC
		 * @param string $help          Optional. PHPDOC
		 *                              Default: empty string.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function make_section( $section_title, $fields, $help = '' ) { ?>
			<div class="nelio-sect stuffbox">
				<h3><label><?php echo $section_title; ?></label><?php
					if ( strlen( $help ) > 0 ) { ?>
						<div class="help"><?php echo $help; ?></div>
					<?php } ?></h3>
				<div class="inside">
					<?php
					foreach ( $fields as $field ) {
						if ( isset( $field['checkbox'] ) && $field['checkbox'] )
							$this->make_checkbox_field( $field );
						else
							$this->make_field( $field );
					}
				?>
				</div>
			</div><?php
		}


		/**
		 * PHPDOC
		 *
		 * @param string $id    PHPDOC
		 * @param string $title PHPDOC
		 * @param array|string|boolean $callback PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_beautiful_box( $id, $title, $callback=false, $classes=array() ) {
			$this->print_linked_beautiful_box( $id, $title, false, $callback, $classes );
		}


		/**
		 * PHPDOC
		 *
		 * @param string         $id       PHPDOC
		 * @param string         $title    PHPDOC
		 * @param string|boolean $link     PHPDOC
		 * @param array|string|boolean $callback PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_linked_beautiful_box( $id, $title, $link=false, $callback=false, $classes=array() ) {
			array_push( $classes, 'postbox', 'nelio-card' );
			$classes = implode( ' ', $classes );
			?>
			<div id="<?php echo $id; ?>" class="<?php echo $classes; ?>">
				<?php if ( $link ) echo "<a href='$link' target='_blank' class='simple'>"; ?>
					<h3><span><?php echo $title; ?></span></h3>
				<?php if ( $link ) echo "</a>"; ?>
				<div class="inside">
				<?php if ( $link ) echo "<a href='$link' target='_blank' class='simple'>"; ?>
					<div class="main"><?php
						if ( $callback ) {
							if ( is_array( $callback ) && count( $callback ) > 2 )
								call_user_func_array( array( $callback[0], $callback[1] ), $callback[2] );
							else
								call_user_func( $callback );
						}
						?>
					</div>
				<?php if ( $link ) echo "</a>"; ?>
				</div>
			</div><?php
		}


		/**
		 * PHPDOC
		 *
		 * @param array $field PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function make_field( $field ) {
			$field_name   = $field['label'];
			$field_id     = $field['id'];
			$callback     = $field['callback'];
			$is_mandatory = false;
			if ( isset( $field['mandatory'] ) && $field['mandatory'] )
				$is_mandatory = true;

			$can_be_used = true;
			$explanation = false;
			if ( isset( $field['min_plan'] ) ) {
				if ( NelioABAccountSettings::get_subscription_plan() < $field['min_plan'] )
					$can_be_used = false;
				switch ( $field['min_plan'] ) {
					case NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN:
						$explanation = __( 'This option is only available for users subscribed to our Professional Plan.', 'nelioab' );
						break;
					case NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN:
						$explanation = __( 'This option is only available for users subscribed to our Enterprise Plan.', 'nelioab' );
						break;
				}
			}

			$error = '';

			if ( $this->is_invalid( $field_id ) )
				$error = ' class="error"';
			?>
			<table <?php
				if ( $can_be_used ) {
					echo 'class="form-table"';
				}
				else {
					echo 'class="form-table setting-disabled"';
					if ( $explanation )
						echo ' title="' . $explanation . '"';
				}?>>
				<tr valign="top">
					<th scope="row"<?php echo $error; ?>>
						<?php if ( $is_mandatory ) { ?>
							<label class="mandatory" for="<?php echo $field_id; ?>"><?php echo $field_name; ?></label>
						<?php } else { ?>
							<label for="<?php echo $field_id; ?>"><?php echo $field_name; ?></label>
						<?php } ?>
					</th>
					<td class="input_for_<?php echo $field_id; ?>">
					<?php call_user_func($callback); ?>
					</td>
					<?php if ( !$can_be_used ) { ?>
					<script type="text/javascript">(function($) {
						var selector = "" +
						  "td.input_for_<?php echo $field_id; ?> input,"+
						  "td.input_for_<?php echo $field_id; ?> select,"+
						  "td.input_for_<?php echo $field_id; ?> textarea";
						$(selector).attr('disabled','disabled');
					})(jQuery);</script><?php
					} ?>
				</tr>
			</table>
		<?php
		}


		/**
		 * PHPDOC
		 *
		 * @param array $field PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function make_checkbox_field( $field ) {
			$label   = $field['label'];
			$id      = $field['id'];
			$checked = $field['checked'] == true;

			if ( isset( $field['pre'] ) )
				echo $field['pre'];
			?>
			<div style="height:1em;">&nbsp;</div>
			<input
				type="checkbox" <?php if ( $checked ) echo 'checked="checked" '; ?>
				id="<?php echo $id; ?>" name="<?php echo $id; ?>" />
					&nbsp;&nbsp;&nbsp;<?php echo $label; ?>
			<?php
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name          PHPDOC
		 * @param string $form_name     PHPDOC
		 * @param string $hidden_action PHPDOC
		 *                              Default: none.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		protected function make_submit_button( $name, $form_name, $hidden_action = 'none' ) {
			return sprintf(
				'<input type="submit" class="button button-primary" ' .
				'value="%1$s" %2$s></input>&nbsp;',
				$name,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $name       PHPDOC
		 * @param string  $link       PHPDOC
		 * @param boolean $is_primary PHPDOC
		 *                            Default: false.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		protected function make_button( $name, $link, $is_primary=false ) {
			$primary = 'button';
			if ( $is_primary )
				$primary = 'button button-primary';

			return sprintf(
				'<a class="%3$s" href="%2$s">%1$s</a>&nbsp;',
				$name, $link, $primary );
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name          PHPDOC
		 * @param string $form_name     PHPDOC
		 * @param string $hidden_action PHPDOC
		 *                              Default: none.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		protected function make_form_button( $name, $form_name, $hidden_action = 'none' ) {
			return sprintf(
				'<a class="button" %2$s>%1$s</a>&nbsp;',
				$name,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $name       PHPDOC
		 * @param string  $js         PHPDOC
		 * @param boolean $is_enabled PHPDOC
		 *                            Default: true.
		 * @param boolean $is_primary PHPDOC
		 *                            Default: false.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		protected function make_js_button( $name, $js, $is_enabled = true, $is_primary = false ) {
			$primary = 'button';
			if ( $is_primary )
				$primary = 'button button-primary';

			$disabled = '';
			if ( !$is_enabled )
				$disabled = $primary . '-disabled';

			return sprintf(
				'<a class="nelioab-js-button %3$s %4$s" href="%2$s">%1$s</a>&nbsp;',
				$name, $js, $primary, $disabled );
		}


		/**
		 * PHPDOC
		 *
		 * @param string $field_id PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		private function is_invalid( $field_id ) {
			global $nelioab_admin_controller;
			foreach ( $nelioab_admin_controller->validation_errors as $err )
				if ( $err[0] == $field_id )
					return true;
			return false;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $label PHPDOC
		 * @param string $url   PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function add_title_action( $label, $url ) {
			$this->title_action = $this->make_action_link( $label, $url );
		}


		/**
		 * PHPDOC
		 *
		 * @param string $label PHPDOC
		 * @param string $url   PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function make_action_link( $label, $url ){
			return sprintf(
				'<a href="%1$s" class="add-new-h2">%2$s</a>',
				$url,
				$label
			);
		}


		/**
		 * PHPDOC
		 *
		 * @param string $label         PHPDOC
		 * @param string $form_name     PHPDOC
		 * @param string $hidden_action PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function make_form_action_link( $label, $form_name, $hidden_action ){
			return sprintf(
				'<a class="add-new-h2" style="cursor:pointer;" %2$s>%1$s</a>',
				$label,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}


		/**
		 * PHPDOC
		 *
		 * @param string $form_name     PHPDOC
		 * @param string $hidden_action PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		protected function make_form_javascript( $form_name, $hidden_action ) {
			return sprintf(
				' onclick="javascript:' .
				'jQuery(\'#%1$s > #action\').attr(\'value\', \'%2$s\');' .
				'jQuery(\'#%1$s\').submit();" ',
				$form_name, $hidden_action
			);
		}

	}//NelioABAdminPage

}

