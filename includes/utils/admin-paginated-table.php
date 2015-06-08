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

	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );

	/**
	 * This class is an abstract paginate table.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABAdminPaginatedTable extends NelioABAdminTable {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $items_per_page;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var iNelioABDataManager
		 */
		private $data_manager;


		/**
		 * It creates a new instance of this class.
		 *
		 * @param array $super_params A few params required by WP_List_Table.
		 *
		 * @return NelioABAdminPaginatedTable a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		function __construct( $super_params ) {
			//Set parent defaults
			parent::__construct( $super_params );
			$this->items_per_page = 10;
		}


		/**
		 * PHPDOC
		 *
		 * @param iNelioABDataManager $data_manager PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_data_manager( $data_manager ) {
			// TODO: what's the type of data_manager?
			$this->data_manager = $data_manager;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $items_per_page PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_items_per_page( $items_per_page ) {
			$this->items_per_page = $items_per_page;
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
			die ( 'You are not allowed to explicitly set the ' .
				'list of items within a PaginatedTable. Please, ' .
				'use a basic AdminTable instead' );
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		protected function prepare_pagination() {
			// REQUIRED. Now we can add our *sorted* data to the items property, where
			// it can be used by the rest of the class.
			$this->items = $this->data_manager->list_elements();
			$total_items = count( $this->items );


			// REQUIRED. We also have to register our pagination options & calculations.
			$this->set_pagination_args( array(
				// WE have to calculate the total number of items
				'total_items' => $total_items,
				// WE have to determine how many items to show on a page
				'per_page'	=> $this->items_per_page,
				// WE have to calculate the total number of pages
				'total_pages' => ceil( $total_items/$this->items_per_page )
			) );
		}

	}// NelioABAdminTable

}

