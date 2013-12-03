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


if ( !class_exists( 'NelioABAdminTable' ) ) {
	if ( !class_exists( 'WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	abstract class NelioABAdminTable extends WP_List_Table {


		/** ************************************************************************
		 * REQUIRED. Set up a constructor that references the parent constructor. We
		 * use the parent reference to set some default configs.
		 ***************************************************************************/
		public function __construct( $super_params ){
			global $status, $page;

			//Set parent defaults
			parent::__construct( $super_params );
		}

		public function set_items( $items ) {
			$this->items = $items;
		}


		/** ************************************************************************
		 * Recommended. This method is called when the parent class can't find a method
		 * specifically build for a given column. Generally, it's recommended to include
		 * one method for each column you want to render, keeping your package class
		 * neat and organized. For example, if the class needs to process a column
		 * named 'title', it would first see if a method named $this->column_title()
		 * exists - if it does, that method will be used. If it doesn't, this one will
		 * be used. Generally, you should try to use custom column methods as much as
		 * possible.
		 *
		 * For more detailed insight into how columns are handled, take a look at
		 * WP_List_Table::single_row_columns()
		 *
		 * @param array $item A singular item (one full row's worth of data)
		 * @param array $column_name The name/slug of the column to be processed
		 * @return string Text or HTML to be placed inside the column <td>
		 **************************************************************************/
		public function column_default($item, $column_name){
			$functions = $this->get_display_functions();
			try {
				return call_user_func( array( $item, $functions[$column_name] ) );
			}
			catch ( Exception $e ) {
				return sprintf( __( 'Error displaying column `%s\'.', 'nelioab' ), $column_name );
			}
		}


		/** ************************************************************************
		 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
		 * is given special treatment when columns are processed. It ALWAYS needs to
		 * have it's own method.
		 *
		 * @see WP_List_Table::::single_row_columns()
		 * @param array $item A singular item (one full row's worth of data)
		 * @return string Text to be placed inside the column <td> (movie title only)
		 **************************************************************************/
		public function column_cb($item){
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*%1$s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
				/*%2$s*/ $item['ID']				//The value of the checkbox should be the record's id
			);
		}


		public function process_bulk_action() {
		}


		public function get_display_functions() {
			return array();
		}

		protected function prepare_pagination() {
			/**
			 * REQUIRED. Now we can add our *sorted* data to the items property, where
			 * it can be used by the rest of the class.
			 */
			$total_items = count( $this->items );

			/**
			 * REQUIRED. We also have to register our pagination options & calculations.
			 */
			$this->set_pagination_args( array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'	=> $total_items, //WE have to determine how many items to show on a page
				'total_pages' => 1
			) );
		}


		/** ************************************************************************
		 * REQUIRED! This is where you prepare your data for display. This method will
		 * usually be used to query the database, sort and filter the data, and generally
		 * get it ready to be displayed. At a minimum, we should set $this->items and
		 * $this->set_pagination_args(), although the following properties and methods
		 * are frequently interacted with here...
		 *
		 * @global WPDB $wpdb
		 * @uses $this->_column_headers
		 * @uses $this->items
		 * @uses $this->get_columns()
		 * @uses $this->get_sortable_columns()
		 * @uses $this->get_pagenum()
		 * @uses $this->set_pagination_args()
		 **************************************************************************/
		public function prepare_items() {
			global $wpdb; //This is used only if making any database queries

			/**
			 * REQUIRED. Now we need to define our column headers. This includes a complete
			 * array of columns to be displayed (slugs & titles), a list of columns
			 * to keep hidden, and a list of columns that are sortable. Each of these
			 * can be defined in another method (as we've done here) before being
			 * used to build the value for our _column_headers property.
			 */
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();


			/**
			 * REQUIRED. Finally, we build an array to be used by the class for column
			 * headers. The $this->_column_headers property takes an array which contains
			 * 3 other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array($columns, $hidden, $sortable);


			/**
			 * Optional. You can handle your bulk actions however you see fit. In this
			 * case, we'll handle them within our package just to keep things clean.
			 */
			$this->process_bulk_action();

			$this->prepare_pagination();
		}

		public function display_rows() {
			$this->print_inline_edit_form();
			parent::display_rows();
		}

		public function display_rows_or_placeholder() {
			parent::display_rows_or_placeholder();
		}

		// TODO: aixo hauria d'estar ben identificat (la var nelioabEditingItem,
		// per si tinc mes d'una taula i tal...)
		public function display() {
			parent::display(); ?>
			<script>
				var nelioabEditingItem = null;
				function showInlineEdit( row ) {
					if ( nelioabEditingItem != null )
						hideInlineEdit();


					// Get the ROW and place the INLINE_EDIT after it
					jQuery("#inline-edit").insertAfter(row);

					// Hide the ROW and show the INLINE_EDIT
					row.hide();
					try {
						fillInlineEdit(row);
					}
					catch (e) {}
					jQuery("#inline-edit").show();

					// Update the global var
					nelioabEditingItem = row;
				}

				function hideInlineEdit() {
					jQuery("#inline-edit").hide();
					if (nelioabEditingItem != null) {
						nelioabEditingItem.show();
						nelioabEditingItem = null;
					}
				}

				function fillInlineEdit(row) {
					<?php $this->print_js_body_for_inline_form(); ?>
				}

				jQuery("a.show-inline-edit").each(function() {
					jQuery(this).click(function() {
						row = jQuery(this).parent().parent().parent().parent();
						showInlineEdit(row);
					});
				});


			</script>
			<?php
		}

		// TODO: fix this operation AND documentation
		// Mejor identificador para el inline-edit?
		private function print_inline_edit_form() { ?>
			<tr id="inline-edit" class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:none;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
					<?php $this->inline_edit_form(); ?>
					<p class="submit inline-edit-save">
						<?php $this->inline_edit_form_ok_button(); ?>
						<a class="button-secondary cancel alignleft" style="margin-left:0.4em;"
							onClick="javascript:hideInlineEdit();">
							<?php _e( 'Cancel', 'nelioab' ); ?></a>
						<br class="clear" />
					</p>
				</td>
			</tr><?php
		}

		// TODO: Implemented this function
		public function inline_edit_form_ok_button() {
		}

		// TODO: fix this operation AND documentation
		public function inline_edit_form() {
		}

		// TODO document this operation
		public function make_quickedit_button( $label ) {
			return '<a class="show-inline-edit" style="cursor:pointer;">' .
				$label . '</a>';
		}

		// TODO document this operation
		public function print_js_body_for_inline_form() { ?>
			// No code provided
			<?php
		}

	}// NelioABAdminTable

}

?>
