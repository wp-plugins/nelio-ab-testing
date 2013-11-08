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


if( !class_exists( 'NelioABAltStats' ) ) {

	class NelioABAltStats {

		private $name;
		private $post_id;
		private $num_of_visitors;
		private $num_of_conversions;
		private $conversion_rate;
		private $improvement_factor;
		private $is_original;
		private $visitors_history;
		private $conversions_history;

		public function __construct( $is_original = 0 ) {
			$this->is_original         = $is_original;
			$this->visitors_history    = array();
			$this->conversions_history = array();
		}

		public function set_name( $name ) {
			$this->name = $name;
		}

		public function get_name() {
			return $this->name;
		}

		public function set_post_id( $post_id ) {
			$this->post_id = $post_id;;
		}

		public function get_post_id() {
			return $this->post_id;
		}

		public function set_num_of_visitors( $num_of_visitors ) {
			$this->num_of_visitors = $num_of_visitors;
		}

		public function get_num_of_visitors() {
			return $this->num_of_visitors;
		}

		public function set_num_of_conversions( $num_of_conversions ) {
			$this->num_of_conversions = $num_of_conversions;
		}

		public function get_num_of_conversions() {
			return $this->num_of_conversions;
		}

		public function set_conversion_rate( $conversion_rate ) {
			$this->conversion_rate = $conversion_rate;
		}

		public function get_conversion_rate() {
			return number_format( floatval( $this->conversion_rate ), 2 );
		}

		public function set_improvement_factor( $improvement_factor ) {
			$this->improvement_factor = $improvement_factor;
		}

		public function get_improvement_factor() {
			if ( $this->is_original() )
				return '-';
			return number_format( floatval( $this->improvement_factor ), 2 );
		}

		public function get_conversion_rate_text() {
			return  $this->get_conversion_rate() . ' %';
		}

		public function get_improvement_factor_text() {
			if ( $this->is_original() )
				return '-';
			return  $this->get_improvement_factor() . ' %';
		}

		public function is_original() {
			return $this->is_original;
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

	}//NelioABAltStats

}

?>
