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


if ( !class_exists( 'NelioABExamplePage' ) ) {

	class NelioABExamplePage {

		public function __construct() {
			$this->init();
		}

		private function init() {

			// FIRST SECTION
			add_settings_section(
				'nelioab_authorbox',
				'A/B Tests (definition and results)',
				array( &$this, 'authorbox_desc' ),
				'nelioab_example'
			);

			add_settings_field(
				'nelio_testab_textarea_example',
				'Test definition',
				array( &$this, 'authorbox_field' ),
				'nelioab_example',
				'nelioab_authorbox'
			);

			// SECOND SECTION
			add_settings_section(
				'nelioab_flipa',
				'Another section',
				array( &$this, 'authorbox_desc' ),
				'nelioab_example'
			);

			add_settings_field(
				'nelio_testab_flipa_template1',
				'Test definition A',
				array( &$this, 'authorbox_field' ),
				'nelioab_example',
				'nelioab_flipa'
			);

			add_settings_field(
				'nelio_testab_flipa_template2',
				'Test definition B',
				array( &$this, 'authorbox_field' ),
				'nelioab_example',
				'nelioab_flipa'
			);

		}

		public function render() {
			?>
			<div class="wrap">
<div class="icon32" id="icon-options-general"></div>
				<h2>Hola</h2>

				<form id="nelioab_example_settings" action="options.php" method="post">
					<?php
					settings_fields( 'nelioab_example_settings' );
					do_settings_sections( 'nelioab_example' );
					submit_button( 'Save Options', 'primary', 'nelioab_example_settings_submit' );
					?>
				</form>
			</div>
			<?php
		}

		public function authorbox_desc() {?>
			<p>Enter the tests using the form 'opA:opB-goal'.</p>
			<p>For example, if default option is post id 3, and its alternative is 4,
				and the target post is 10, the test is defined as '3:4-10'.</p>
			<?php
		}

		public function authorbox_field() {
			$options   = get_option( 'nelio_testab_options' );
			$authorbox = ( isset( $options['textarea_example'] ) ) ?
				$options['textarea_example'] : '';
			$authorbox = esc_textarea( $authorbox ); //sanitise output
			?>

			<textarea id="textarea_example" maxlength="450"
					name="nelioab_options[textarea_example]" cols="20" rows="5"
					class="large-text code"><? echo $authorbox; ?></textarea>
			<?php
		}

	}//NelioABExamplePage
}

?>
