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


if( !class_exists( 'NelioABPageAccessedGoalResult' ) ) {

	include_once( NELIOAB_MODELS_DIR . '/alternatives/gtest.php' );
	include_once( NELIOAB_MODELS_DIR . '/goal-results/goal-result.php' );
	class NelioABPageAccessedGoalResult extends NelioABGoalResult {

		private $summary_status;
		private $total_visitors;
		private $total_conversions;
		private $total_conversion_rate;
		private $alternatives;
		private $gtests;
		private $visitors_history;
		private $conversions_history;

		public function __construct() {
			parent::__construct();
			$this->summary_status        = NelioABGTest::UNKNOWN;
			$this->total_visitors        = 0;
			$this->total_conversions     = 0;
			$this->total_conversion_rate = 0;
			$this->alternatives          = array();
			$this->gtests                = array();
			$this->visitors_history      = array();
			$this->conversions_history   = array();
		}

		public function set_summary_status( $status ) {
			$this->summary_status = $status;
		}

		public function get_summary_status() {
			return $this->summary_status;
		}

		public function add_alternative_results( $alternative_results ) {
			array_push( $this->alternatives, $alternative_results );
		}

		public function get_alternative_results() {
			return $this->alternatives;
		}

		public function set_total_visitors( $total_visitors ) {
			$this->total_visitors = $total_visitors;
		}

		public function get_total_visitors() {
			return $this->total_visitors;
		}

		public function set_total_conversions( $total_conversions ) {
			$this->total_conversions = $total_conversions;
		}

		public function get_total_conversions() {
			return $this->total_conversions;
		}

		public function set_total_conversion_rate( $total_conversion_rate ) {
			$this->total_conversion_rate = $total_conversion_rate;
		}

		public function get_total_conversion_rate() {
			return $this->total_conversion_rate;
		}

		public function add_gtest( $g ) {
			array_push( $this->gtests, $g );
		}

		public function get_gtests() {
			return $this->gtests;
		}

		public function set_visitors_history( $visitors_history ) {
			$this->visitors_history = $visitors_history;
		}

		public function get_visitors_history() {
			return $this->visitors_history;
		}

		public function set_conversions_history( $conversions_history ) {
			$this->conversions_history = $conversions_history;
		}

		public function get_conversions_history() {
			return $this->conversions_history;
		}

	}//NelioABPageAccessedGoalResult

}

?>
