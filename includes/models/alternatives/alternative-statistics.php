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


if ( !class_exists( 'NelioABAltStats' ) ) {

	/**
	 * Abstract class representing an Experiment in Nelio A/B Testing.
	 *
	 * In order to create an instance of this class, one must use of its
	 * concrete subclasses.
	 *
	 * @package \NelioABTesting\Models\Results
	 * @since PHPDOC
	 */
	class NelioABAltStats {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $name;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $alt_id;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $num_of_visitors;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $num_of_conversions;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var float
		 */
		private $conversion_rate;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var float
		 */
		private $improvement_factor;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $is_original;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $visitors_history;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $conversions_history;


		/**
		 * Creates a new instance of this class.
		 *
		 * This constructor might be used by the concrete subclasses. It sets all
		 * attributes to their default values.
		 *
		 * @param boolean $is_original PHPDOC
		 *                Default: `false`.
		 *
		 * @return NelioABAltStats a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $is_original = false ) {
			$this->is_original         = $is_original;
			$this->visitors_history    = array();
			$this->conversions_history = array();
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_name( $name ) {
			$this->name = $name;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_name() {
			return $this->name;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $alt_id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_alt_id( $alt_id ) {
			$this->alt_id = $alt_id;;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_alt_id() {
			return $this->alt_id;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $num_of_visitors PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_num_of_visitors( $num_of_visitors ) {
			$this->num_of_visitors = $num_of_visitors;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_num_of_visitors() {
			return $this->num_of_visitors;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $num_of_conversions PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_num_of_conversions( $num_of_conversions ) {
			$this->num_of_conversions = $num_of_conversions;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_num_of_conversions() {
			return $this->num_of_conversions;
		}


		/**
		 * PHPDOC
		 *
		 * @param float $conversion_rate PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_conversion_rate( $conversion_rate ) {
			$this->conversion_rate = $conversion_rate;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return float PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_conversion_rate() {
			return floatval( $this->conversion_rate );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_conversion_rate_text() {
			return number_format_i18n( floatval( $this->conversion_rate ), 2 ) . ' %';
		}


		/**
		 * PHPDOC
		 *
		 * @param float $improvement_factor PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_improvement_factor( $improvement_factor ) {
			$this->improvement_factor = $improvement_factor;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return float PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_improvement_factor() {
			if ( $this->is_original() )
				return '-';
			return floatval( $this->improvement_factor );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_improvement_factor_text() {
			if ( $this->is_original() )
				return '-';
			return number_format_i18n( floatval( $this->improvement_factor ), 2 ) . ' %';
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function is_original() {
			return $this->is_original;
		}


		/**
		 * PHPDOC
		 *
		 * @param array $visitors_history PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_visitors_history( $visitors_history ) {
			$this->visitors_history = $visitors_history;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_visitors_history() {
			return $this->visitors_history;
		}


		/**
		 * PHPDOC
		 *
		 * @param array $conversions_history PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_conversions_history( $conversions_history ) {
			$this->conversions_history = $conversions_history;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_conversions_history() {
			return $this->conversions_history;
		}

	}//NelioABAltStats

}

