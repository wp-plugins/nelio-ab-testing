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

if ( !class_exists( 'NelioABAdminTable' ) ) {

	if ( !class_exists( 'WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	/**
	 * This class is an abstract table.
	 *
	 * @see WP_List_Table
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABAdminTable extends WP_List_Table {


		/**
		 * It creates a new instance of this class.
		 *
		 * @param array $super_params A few params required by WP_List_Table.
		 *
		 * @return NelioABAdminTable a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $super_params ){
			if ( !isset( $super_params['screen'] ) )
				$super_params['screen'] = 'nelio-ab-testing';

			//Set parent defaults
			parent::__construct( $super_params );
		}


		/**
		 * PHPDOC
		 *
		 * @param array $items PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_items( $items ) {
			$this->items = $items;
		}


		/**
		 * PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_jquery_sortable_columns() {
			return array();
		}


		/**
		 * This method is called when the parent class can't find a method specifically build for a given column.
		 * Generally, it's recommended to include one method for each column you
		 * want to render, keeping your package class neat and organized. For
		 * example, if the class needs to process a column named 'title', it would
		 * first see if a method named $this->column_title() exists - if it does,
		 * that method will be used. If it doesn't, this one will be used.
		 * Generally, you should try to use custom column methods as much as
		 *
		 * @param array  $item        A singular item (one full row's worth of data)
		 * @param string $column_name The name/slug of the column to be processed
		 *
		 * @return string Text or HTML to be placed inside the column <td>
		 *
		 * @since PHPDOC
		 */
		// @Override
		public function column_default( $item, $column_name){
			$functions = $this->get_display_functions();
			try {
				return call_user_func( array( $item, $functions[$column_name] ) );
			}
			catch ( Exception $e ) {
				return sprintf( __( 'Error displaying column `%s\'.', 'nelioab' ), $column_name );
			}
		}


		/**
		 * The 'cb' column is given special treatment when columns are processed.
		 * It ALWAYS needs to have it's own method.
		 *
		 * @param array $item A singular item (one full row's worth of data)
		 *
		 * @return string Text to be placed inside the column <td> (movie title only)
		 *
		 * @see WP_List_Table::::single_row_columns()
		 *
		 * @since PHPDOC
		 */
		// @Override
		public function column_cb($item ){
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*%1$s*/ $this->_args['singular'],
				/*%2$s*/ $item['ID']
			);
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function process_bulk_action() {
		}


		/**
		 * PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_display_functions() {
			return array();
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
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


		// @Override
		public function prepare_items() {
			// REQUIRED. Now we need to define our column headers. This includes a complete
			// array of columns to be displayed (slugs & titles), a list of columns
			// to keep hidden, and a list of columns that are sortable. Each of these
			// can be defined in another method (as we've done here) before being
			// used to build the value for our _column_headers property.
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();


			// REQUIRED. Finally, we build an array to be used by the class for column
			// headers. The $this->_column_headers property takes an array which contains
			// 3 other arrays. One for all columns, one for hidden columns, and one
			// for sortable columns.
			$this->_column_headers = array( $columns, $hidden, $sortable );


			// Optional. You can handle your bulk actions however you see fit. In this
			// case, we'll handle them within our package just to keep things clean.
			$this->process_bulk_action();

			$this->prepare_pagination();
		}


		/**
		 * PHPDOC
		 *
		 * @return string|boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_table_id() {
			return false;
		}


		// @Override
		public function display_rows() {
			$this->print_inline_edit_form();
			parent::display_rows();
		}


		// @Override
		public function display() {
			if ( $this->get_table_id() )
				echo '<span style="display:none;" id="span-' . $this->get_table_id() . '"></span>';
			parent::display();

			// And now this is OK:
			$id = $this->get_table_id();
			if ( $id ) {
				$cols = $this->get_columns();
				$sort = $this->get_jquery_sortable_columns();
				$head = '';
				$i = 0;
				foreach ( $cols as $col => $val ) {
					if ( !in_array( $col, $sort ) )
						$head .= "$i:{sorter:false},";
					++$i;
				}
				?>
				<script>if (typeof jQuery !== 'undefined') {
					jQuery("span#span-<?php echo $id; ?>").nextAll("table").first().
						attr("id", "<?php echo $id; ?>");
					jQuery("span#span-<?php echo $id; ?>").remove();
					jQuery("table#<?php echo $id; ?>").
					find("th").each(function() {
						jQuery(this).html(
							"<a><span>" + jQuery(this).text() + "</span>" +
							"<span class='sorting-indicator'></span></a>");
					});
					jQuery("table#<?php echo $id; ?>").first().
					tablesorter({
						headers: {
							<?php echo $head; ?>
						},
						cssAsc: 'sorted desc',
						cssDesc: 'sorted asc',
						cssHeader: 'sortable desc',
						widgets: ['zebra'],
						widgetZebra: { css: [ "non-alternate", "alternate" ] }
					});
				}</script><?php
			}
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function print_inline_edit_form() { ?>
			<tr class="nelioab-quick-edit-row inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:none;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
					<?php $this->inline_edit_form(); ?>
					<p class="submit inline-edit-save">
						<a class="button button-primary save alignleft"
							style="margin-left:0.4em;"
							href="javascript:;"
							onClick="javascript:
							jQuery(this).trigger('pre-clicked');
							if (!jQuery(this).hasClass('disabled')) {
								jQuery(this).trigger('clicked');
								NelioABAdminTable.hideInlineEdit(jQuery(this).closest('table.wp-list-table'));
							}"><?php
							echo $this->inline_save_button_label(); ?></a>
						<a class="button button-secondary cancel alignleft"
							style="margin-left:0.4em;"
							href="javascript:;"
							onClick="javascript:
							jQuery(this).trigger('pre-clicked');
							if (!jQuery(this).hasClass('disabled')) {
								jQuery(this).trigger('clicked');
								NelioABAdminTable.hideInlineEdit(jQuery(this).closest('table.wp-list-table') );
							}"><?php
							_e( 'Cancel', 'nelioab' ); ?></a>
						<br class="clear" />
					</p>
				</td>
			</tr><?php
		}


		/**
		 * PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function inline_save_button_label() {
			return __( 'Save' );
		}


		/**
		 * PHPDOC
		 *
		 * @return void PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function inline_edit_form() {
			// TODO: fix this operation AND documentation
		}


		/**
		 * This function adds a simple button with the class "show-inline-edit".
		 *
		 * @param string $label PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function make_quickedit_button( $label ) {
			// TODO acaba de documentar
			return
				'<a class="show-inline-edit"' .
				'  style="cursor:pointer;"' .
				'  href="javascript:;"' .
				'  onClick="javascript:NelioABAdminTable.showInlineEdit( ' .
				'    jQuery(this).closest(\'tr\')' .
				'  );">' .
				$label . '</a>';
		}

	}// NelioABAdminTable

}

