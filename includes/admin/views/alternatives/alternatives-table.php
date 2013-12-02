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

		protected $form_name;

		function __construct( $items, $form_name ){
   	   parent::__construct( array(
				'singular'  => __( 'alternative', 'nelioab' ),
				'plural'    => __( 'alternatives', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->set_items( $items );
			$this->form_name = $form_name;
		}

		public function get_columns(){
			return array(
				'name'        => __( 'Name', 'nelioab' ),
			);
		}

		abstract protected function get_edit_code( $alt );
		abstract protected function hide_quick_actions();

		public function column_name( $alt ){

			//Build row actions
			if ( $this->hide_quick_actions() ) {
				$actions = array( 'none' => '&nbsp;' );
			}
			else {
				$actions = array(
					'rename'	=>
						$this->make_quickedit_button( __( 'Rename', 'nelioab' ) ),

					'edit-content'	=> $this->get_edit_code( $alt ),

					'delete'	=> sprintf(
						'<a style="cursor:pointer;" onClick="javascript:' .
							'jQuery(\'#action\').attr(\'value\', \'%s\');' .
							'jQuery(\'#alt_to_remove\').attr(\'value\', %s);' .
							'jQuery(\'#%s\').submit();'.
							'">%s</a>',
						'remove_alternative',
						$alt->get_id(),
						$this->form_name,
						__( 'Delete' ) ),
				);
			}

			//Return the title contents
			return sprintf(
				'<span class="row-title alt-name">%1$s</span>%2$s<span class="alt-id" style="display:none;">%3$s</span>',
				/*%1$s*/ $alt->get_name(),
				/*%2$s*/ $this->row_actions( $actions ),
				/*%3$s*/ $alt->get_id()
			);
		}

		// TODO document this operation
		public function inline_edit_form() {?>
			<fieldset class="inline-edit-col-left">
				<div class="inline-edit-col">
					<h4><?php _e( 'Rename Alternative', 'nelioab' ); ?></h4>
					<label>
						<span class="title"><?php _e( 'Name', 'nelioab' ); ?> </span>
						<span class="input-text-wrap"><input type="text" id="qe_alt_name" name="qe_alt_name" class="ptitle" value="" maxlength="200" /></span>
					</label>
					<input type="hidden" id="qe_alt_id" name="qe_alt_id" value="" />
				</div>
			</fieldset><?php
		}

		public function inline_edit_form_ok_button() {?>
			<a class="button-primary save alignleft" <?php
				echo $this->make_form_javascript( $this->form_name, 'update_alternative_name' );
				?>><?php _e( 'Update', 'nelioab' ); ?></a><?php
		}

		public function print_js_body_for_inline_form() {?>
			name = row.find("span.alt-name").first().html();
			id = row.find("span.alt-id").first().html();
			jQuery("#inline-edit").find("#qe_alt_name").first().attr("value", name);
			jQuery("#inline-edit").find("#qe_alt_id").first().attr("value", id);
			<?php
		}

		// TODO: extract this function to an utility (now copied from admin-page.php)
		// Original Name: make_form_javascript
		protected function make_form_javascript( $form_name, $hidden_action ) {
			return sprintf(
				' onClick="javascript:' .
				'jQuery(\'#%1$s > #action\').attr(\'value\', \'%2$s\');' .
				'jQuery(\'#%1$s\').submit();" ',
				$form_name, $hidden_action
			);
		}

		abstract protected function print_additional_info_for_new_alt_form();
		abstract protected function print_save_button_for_new_alt_form();

		public function print_new_alt_form() {?>
			<tr id="new-alt-form" class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:visible;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php _e( 'New Alternative Creation', 'nelioab' ); ?></h4>
							<label>
								<span class="title"><?php _e( 'Name', 'nelioab' ); ?> </span>
								<span class="input-text-wrap">
									<input type="text" id="new_alt_name" name="new_alt_name" class="ptitle" value="" style="width:300px;" maxlength="200" />
									<span class="description" style="display:block;"><?php
										_e( 'Set a descriptive name for the alternative.', 'nelioab' );
									?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-name-of-an-alternative-used-for" target="_blank"><?php
										_e( 'Help', 'nelioab' );
									?></a></small></span>
								</span>
							</label>
							<?php
							$this->print_additional_info_for_new_alt_form();
							?>
						</div>
					</fieldset>

					<p class="submit inline-edit-save">

						<?php
						$this->print_save_button_for_new_alt_form();
						?>

						<a class="button-secondary cancel alignleft" <?php
							echo $this->make_form_javascript( $this->form_name, 'hide_new_alt_box' );
							?>><?php _e( 'Cancel', 'nelioab' ); ?></a>

						<br class="clear" />
					</p>
				</td>
			</tr><?php

		}

		protected function prepare_pagination() {
			$total_items = count( $this->items ) + 1;
			$this->set_pagination_args( array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'	=> $total_items, //WE have to determine how many items to show on a page
				'total_pages' => 1
			) );
		}



	}// NelioABExperimentsTable

}

?>
