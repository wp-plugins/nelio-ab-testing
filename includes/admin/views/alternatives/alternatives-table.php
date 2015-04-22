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


if ( !class_exists( 'NelioABAlternativesTable' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	abstract class NelioABAlternativesTable extends NelioABAdminTable {

		private $alternatives;

		function __construct( $items ){
			parent::__construct( array(
				'singular'  => __( 'alternative', 'nelioab' ),
				'plural'    => __( 'alternatives', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->alternatives = $items;
			$this->set_items( array(
				array( 'id' => '{ID}', 'name' => '{NAME}' ),
			)	);
		}

		public function get_table_id() {
			return 'alt-table';
		}

		public function get_columns(){
			return array(
				'name' => __( 'Name', 'nelioab' ),
			);
		}

		protected abstract function get_inline_edit_title();
		protected abstract function get_inline_name_field_label();

		public function inline_edit_form() { ?>
			<fieldset class="inline-edit-col-left">
				<div class="inline-edit-col">
					<h4><?php echo $this->get_inline_edit_title(); ?></h4>
					<label>
						<span class="title"><?php echo $this->get_inline_name_field_label() ?> </span>
						<span class="input-text-wrap"><input type="text" id="qe_alt_name" name="qe_alt_name" class="ptitle" value="" maxlength="200" /></span>
					</label>
					<input type="hidden" id="qe_alt_id" name="qe_alt_id" value="" />
				</div>
			</fieldset><?php
		}

		protected function print_additional_info_for_new_alt_form() {
			// By default, no additional info is required
		}

		protected function get_default_description_for_new_alternative() {
			return sprintf( '%s <small><a href="%s" target="_blank">%s</a></small>',
				__( 'Set a descriptive name for the alternative.', 'nelioab' ),
				'http://support.nelioabtesting.com/support/solutions/articles/1000129198',
				__( 'Help', 'nelioab' ) );
		}

		public function print_new_alt_form() { ?>
			<tr class="new-alt-form inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page alternate" style="display:none;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php _e( 'New Alternative Creation', 'nelioab' ); ?></h4>
							<label>
								<span class="title"><?php echo $this->get_inline_name_field_label(); ?> </span>
								<span class="input-text-wrap">
									<input type="text" id="new_alt_name" name="new_alt_name" class="ptitle" value="" style="width:300px;" maxlength="200" />
									<span class="description" style="display:block;"><?php
										echo $this->get_default_description_for_new_alternative();
									?></span>
								</span>
							</label>
							<?php $this->print_additional_info_for_new_alt_form(); ?>
						</div>
					</fieldset>
					<p class="submit inline-edit-save">
						<a class="button button-primary save alignleft" style="margin-right:0.4em;"
							onClick="javascript:NelioABAltTable.create();"><?php
							_e( 'Create', 'nelioab' );
						?></a>
						<a class="button button-secondary cancel alignleft"
							onClick="javascript:NelioABAltTable.hideNewAltForm(jQuery(this).closest('table'));"><?php
							_e( 'Cancel', 'nelioab' );
						?></a>
						<br class="clear" />
					</p>
				</td>
			</tr><?php
		}

		public function inline_save_button_label() {
			return __( 'Update', 'nelioab' );
		}

		protected function prepare_pagination() {
			$total_items = count( $this->items ) + 1;
			$this->set_pagination_args( array() );
		}

	}// NelioABExperimentsTable

}

