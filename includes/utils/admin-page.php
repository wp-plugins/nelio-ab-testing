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


if ( !class_exists( 'NelioABAdminPage' ) ) {

	abstract class NelioABAdminPage {

		protected $title;
		protected $title_action;
		protected $icon_id;
		protected $uses_two_columns;
		protected $message;

		public function __construct( $title = '' ) {
			$this->title            = $title;
			$this->title_action     = '';
			$this->icon_id          = 'icon-options-general';
			$this->uses_two_columns = false;
			$this->message          = false;
		}

		public function set_message( $message ) {
			$this->message = $message;
		}

		public function set_title( $title ) {
			$this->title = $title;
		}

		public function set_icon( $icon_id ) {
			$this->icon_id = $icon_id;
		}

		public function enable_two_columns( $uses ) {
			$this->uses_two_columns = $uses;
		}

		protected abstract function do_render();

		public function print_page_buttons() {
		}

		public function render() {
			?>
			<div class="wrap">
				<div class="icon32" id="<?php echo $this->icon_id; ?>"></div>
				<h2><?php echo $this->title . ' ' . $this->title_action; ?></h2>
				<?php
						$this->print_global_warnings();
						$this->print_error_message();
						$this->print_message();
						$this->print_errors();
				?>
				<br />
				<div id="poststuff" class="metabox-hold <?php
				if ( $this->uses_two_columns )
					echo 'has-right-sidebar'
				?>"><?php
					$this->do_render();?>
					<br />
					<div class="actions"><?php
						$this->print_page_buttons();?>
					</div>
				</div>
			</div>
			<div id="dialog-modal" title="Basic modal dialog" style="display:none;">
				<div id="dialog-content">
					<?php $this->print_dialog_content(); ?>
				</div>
			</div>
			<?php
		}

		protected function print_dialog_content() {
		}

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
						echo "<li>&ndash; $warning</li>";
				?>
				</ul>
			</div><?php
		}

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

		protected function print_error_message_content() {
			global $nelioab_admin_controller;
			$message = $nelioab_admin_controller->error_message;
			if ( isset( $message ) && $message != NULL && strlen( $message ) > 0 )
				echo '<p>' . $message . '</p>';
		}

		protected function print_message( $display = 'block' ) {
			global $nelioab_admin_controller;
			$message = $nelioab_admin_controller->message;
			$aux_class = '';
			if ( !isset( $message ) || $message == NULL || strlen( $message ) == 0 )
				$display = 'none';
			else
				$aux_class = 'to-be-shown';
			?>
			<div id="message-div"
				class="updated below-h2 <?php echo $aux_class; ?>"
				style="display:<?php echo $display; ?>">
					<?php $this->print_message_content(); ?>
			</div><?php
		}

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

		protected function print_errors_content() {
			global $nelioab_admin_controller;
			if ( count( $nelioab_admin_controller->validation_errors ) > 0 ) {?>
				<p><?php echo _('The following errors have been encountered:'); ?></p>
				<ul style="padding-left:2em;"><?php
					foreach ( $nelioab_admin_controller->validation_errors as $err )
						echo '<li>&ndash; ' . $err[1] . '</li>';?>
				</ul>
			<?php
			}
		}

		protected function make_section( $section_title, $fields ) {?>
			<div class="stuffbox">
				<h3><label><?php echo $section_title; ?></label></h3>
				<div class="inside"><?php
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

		protected function make_field( $field ) {
			$field_name   = $field['label'];
			$field_id     = $field['id'];
			$callback     = $field['callback'];
			$is_mandatory = false;
			if ( isset( $field['mandatory'] ) && $field['mandatory'] )
				$is_mandatory = true;

			$pre_err = '';
			$post_err = '';

			if ( $this->is_invalid( $field_id ) ) {
				$pre_err = '<strong style="color:red;">';
				$post_err = '</strong>';
			}
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<?php echo $pre_err; ?>
						<?php if ( $is_mandatory ) { ?>
							<label for="<?php echo $field_id; ?>"><b>* <?php echo $field_name; ?></b></label>
						<?php } else { ?>
							<label for="<?php echo $field_id; ?>"><?php echo $field_name; ?></label>
						<?php }
						echo $post_err; ?>
					</th>
					<td>
					<?php call_user_func($callback); ?>
					</td>
				</tr>
			</table>
		<?php
		}

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
				id="<?php echo $id; ?>" name="<?php echo $id; ?>">
					&nbsp;&nbsp;&nbsp;<?php echo $label; ?>
			</input>
			<?php
		}

		protected function make_submit_button( $name, $form_name, $hidden_action = 'none' ) {
			return sprintf(
				'<input type="submit" class="button-primary" ' .
				'value="%1$s" %2$s></input>&nbsp;',
				$name,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}

		protected function make_button( $name, $link, $is_primary=false ) {
			$primary = 'button';
			if ( $is_primary )
				$primary = 'button-primary';

			return sprintf(
				'<a class="%3$s" href="%2$s">%1$s</a>&nbsp;',
				$name, $link, $primary );
		}

		protected function make_form_button( $name, $form_name, $hidden_action = 'none' ) {
			return sprintf(
				'<a class="button" %2$s>%1$s</a>&nbsp;',
				$name,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}

		protected function make_js_button( $name, $js, $is_enabled = true, $is_primary = false ) {
			$primary = 'button';
			if ( $is_primary )
				$primary = 'button-primary';

			$disabled = '';
			if ( !$is_enabled )
				$disabled = $primary . '-disabled';

			return sprintf(
				'<a class="%3$s %4$s" href="%2$s">%1$s</a>&nbsp;',
				$name, $js, $primary, $disabled );
		}

		private function is_invalid( $field_id ) {
			global $nelioab_admin_controller;
			foreach ( $nelioab_admin_controller->validation_errors as $err )
				if ( $err[0] == $field_id )
					return true;
			return false;
		}

		public function add_title_action( $label, $url ) {
			$this->title_action = $this->make_action_link( $label, $url );
		}

		public function make_action_link( $label, $url ){
			return sprintf(
				'<a href="%1$s" class="add-new-h2">%2$s</a>',
				$url,
				$label
			);
		}

		public function make_form_action_link( $label, $form_name, $hidden_action ){
			return sprintf(
				'<a class="add-new-h2" style="cursor:pointer;" %2$s>%1$s</a>',
				$label,
				$this->make_form_javascript( $form_name, $hidden_action )
			);
		}

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

?>
