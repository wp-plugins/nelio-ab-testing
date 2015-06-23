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


if ( !class_exists( 'NelioABAltExpGoalResult' ) ) {

	include_once( NELIOAB_MODELS_DIR . '/alternatives/gtest.php' );
	include_once( NELIOAB_MODELS_DIR . '/goal-results/goal-result.php' );

	/**
	 * Class representing the results of an alternative experiment goal.
	 *
	 * This class contains information about visitors, conversions, and
	 * conversion rates (both globally and per-alternative) related to a certain
	 * goal in an A/B experiment. It also contains information about the GTests
	 * that were used for computing the results.
	 *
	 * @package \NelioABTesting\Models\Goals
	 * @since PHPDOC
	 */
	class NelioABAltExpGoalResult extends NelioABGoalResult {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $summary_status;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $total_visitors;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $total_conversions;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var float
		 */
		private $total_conversion_rate;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $alternatives;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $gtests;


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
		 * PHPDOC
		 *
		 * @return NelioABAltExpGoalResult PHPDOC
		 *
		 * @since PHPDOC
		 */
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


		public function set_summary_status( $status, $confidence ) {
			$this->summary_status = $status;
			if ( NelioABGTest::WINNER == $this->summary_status ) {
				if ( NelioABSettings::get_min_confidence_for_significance() <= $confidence )
					$this->summary_status = NelioABGTest::WINNER_WITH_CONFIDENCE;
			}
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_summary_status() {
			return $this->summary_status;
		}


		/**
		 * PHPDOC
		 *
		 * @param NelioABAltStats $alternative_results PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function add_alternative_results( $alternative_results ) {
			array_push( $this->alternatives, $alternative_results );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_alternative_results() {
			return $this->alternatives;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $total_visitors PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_total_visitors( $total_visitors ) {
			$this->total_visitors = $total_visitors;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_total_visitors() {
			return $this->total_visitors;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $total_conversions PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_total_conversions( $total_conversions ) {
			$this->total_conversions = $total_conversions;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_total_conversions() {
			return $this->total_conversions;
		}


		/**
		 * PHPDOC
		 *
		 * @param float $total_conversion_rate PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_total_conversion_rate( $total_conversion_rate ) {
			$this->total_conversion_rate = $total_conversion_rate;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return float PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_total_conversion_rate() {
			return $this->total_conversion_rate;
		}


		/**
		 * PHPDOC
		 *
		 * @param NelioABGTest $g PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function add_gtest( $g ) {
			array_push( $this->gtests, $g );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_gtests() {
			return $this->gtests;
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

	}//NelioABAltExpGoalResult

}

