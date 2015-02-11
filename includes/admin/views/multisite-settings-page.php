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


if ( !class_exists( 'NelioABMultisiteSettingsPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
	class NelioABMultisiteSettingsPage extends NelioABAdminPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->add_class( 'settings-page' );
			$this->set_icon( 'icon-nelioab' );
		}

		public function do_render() { ?>
			<form
				action="<?php echo admin_url( '/network/settings.php?page=nelioab-multisite-settings' ); ?>"
				method="POST">
				<input type="hidden" value="yes"
					name="nelioab_multisite_settings_form"
					id="nelioab_multisite_settings_form" />

				<?php
				$this->add_field(
					__( 'Plugin Available To', 'nelioab' ),
					array( $this, 'print_plugin_available_to_field' )
				);
				?>

				<input type="submit" id="submit-button" class="button button-primary"
					value="<?php _e( 'Save Changes' ); ?>">

			</form>
		<?php
		}

		public function add_field( $name, $callback ) { ?>
			<table class="form-table">
				<tbody><tr>
					<th scope="row"><?php echo $name; ?></th>
					<td><?php call_user_func( $callback ); ?></td>
				</tr></tbody>
			</table><?php
		}

		public function print_plugin_available_to_field() {
			$field_name = 'plugin_available_to';
			printf( '<select id="%1$s" name="%1$s">', $field_name );
			?>
				<?php $val = NelioABSettings::PLUGIN_AVAILABLE_TO_ANY_ADMIN; ?>
				<option value='<?php echo $val ?>'><?php
					_e( 'Super Admins and Site Admins', 'nelioab' );
				?></option>
				<?php $val = NelioABSettings::PLUGIN_AVAILABLE_TO_SUPER_ADMIN; ?>
				<option value='<?php echo $val; ?>'<?php
					if ( !NelioABSettings::get_site_option_regular_admins_can_manage_plugin() )
						echo ' selected="selected"';
				?>><?php
					_e( 'Super Admins Only', 'nelioab' );
				?></option>
			</select>
			<?php
		}

	}//NelioABMultisiteSettingsPage

}
