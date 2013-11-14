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


if( !class_exists( 'NelioABAltExpResult' ) ) {

	class NelioABAltExpResult {

		private $has_historic_info;
		private $total_visitors;
		private $total_conversions;
		private $total_conversion_rate;
		private $alternatives;
		private $gstats;
		private $visitors_history;
		private $conversions_history;
		private $first_update;
		private $last_update;

		public function __construct() {
			$this->has_historic_info     = false;
			$this->total_visitors        = 0;
			$this->total_conversions     = 0;
			$this->total_conversion_rate = 0;
			$this->alternatives          = array();
			$this->gstats                = array();
			$this->visitors_history      = array();
			$this->conversions_history   = array();
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

		public function add_gstat( $g ) {
			array_push( $this->gstats, $g );
		}

		public function get_gstats() {
			return $this->gstats;
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

		public function set_first_update( $first_update ) {
			$this->first_update = $first_update;
			if ( strtotime( '2013-11-01T00:00:00.000Z' ) < strtotime( $first_update ) )
				$this->has_historic_info = true;
		}

		public function get_first_update() {
			return $this->first_update;
		}

		public function set_last_update( $last_update ) {
			$this->last_update = $last_update;
		}

		public function get_last_update() {
			return $this->last_update;
		}

		public function has_historic_info() {
			return $this->has_historic_info;
		}

	}//NelioABAltExpResult

}

?>
