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


if ( !class_exists( 'NelioABGoalResult' ) ) {

	/**
	 * Abstract class representing the results of a goal.
	 *
	 * In order to create an instance of this class, one must use of its
	 * concrete subclasses.
	 *
	 * @package \NelioABTesting\Models\Goals
	 * @since PHPDOC
	 */
	abstract class NelioABGoalResult {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $first_update;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $last_update;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $has_historic_info;


		/**
		 * PHPDOC
		 *
		 * @return NelioABGoalResult PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function __construct() {
			$this->has_historic_info = false;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $first_update PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_first_update( $first_update ) {
			$this->first_update = $first_update;
			if ( strtotime( '2013-11-01T00:00:00.000Z' ) < strtotime( $first_update ) )
				$this->has_historic_info = true;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_first_update() {
			return $this->first_update;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $last_update
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_last_update( $last_update ) {
			$this->last_update = $last_update;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_last_update() {
			return $this->last_update;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function has_historic_info() {
			return $this->has_historic_info;
		}

	}//NelioABGoalResult

}

