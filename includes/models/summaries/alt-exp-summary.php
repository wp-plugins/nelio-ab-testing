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


if ( !class_exists( 'NelioABAltExpSummary' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/summaries/experiment-summary.php' );

	/**
	 * This class summarizes any A/B experiment.
	 *
	 * This class can be used in Nelio's _Dashboard_ or in the
	 * _Experiment List_. It contains the basic, essential information that is
	 * shown in those pages.
	 *
	 * @package \NelioABTesting\Models\Experiments\Summaries
	 * @since 3.0.0
	 */
	class NelioABAltExpSummary extends NelioABExperimentSummary {

		/**
		 * The status of this experiment's results.
		 *
		 * @see NelioABGTest
		 *
		 * @since 3.0.0
		 * @var int
		 */
		private $result_status;


		/**
		 * A list of tuples, one for each alternative, with some relevant information.
		 *
		 * The information contained for each alternative is:
		 *
		 * * **id**: the ID of the alternative
		 * * **name**: the name of the alternative
		 * * **conversions**: the number of conversions of the alternative
		 * * **page views**: the number of page views of the alternative
		 *
		 * @since 3.0.0
		 * @var array
		 */
		private $alternative_info;


		/**
		 * The total number of page views of this experiment.
		 *
		 * @since 3.0.0
		 * @var int
		 */
		private $total_visitors;


		/**
		 * The total number of conversions of this experiment.
		 *
		 * @since 3.0.0
		 * @var int
		 */
		private $total_conversions;


		// @Override
		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::PAGE_OR_POST_ALT_EXP );
			$this->alternative_info = array();
		}


		/**
		 * Sets the result status of this experiment to the given status and confidence.
		 *
		 * @param int $status     the new status of the experiment.
		 * @param int $confidence the confidence (from 0 to 100) we have in the status of the current results.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public function set_result_status( $status, $confidence ) {
			$this->result_status = NelioABGTest::get_result_status_from_str( $status );
			if ( NelioABGTest::WINNER == $this->result_status ) {
				if ( NelioABSettings::get_min_confidence_for_significance() <= $confidence )
					$this->result_status = NelioABGTest::WINNER_WITH_CONFIDENCE;
			}
		}


		/**
		 * Returns the result status of this experiment.
		 *
		 * @return int the result status of this experiment.
		 *
		 * @since 3.0.0
		 */
		public function get_result_status() {
			return $this->result_status;
		}


		// @Implements
		public function has_result_status() {
			return true;
		}


		/**
		 * Sets the number of page views of this experiment to the given value.
		 *
		 * @param int $total_visitors the new number of total page views of the experiment.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public function set_total_visitors( $total_visitors ) {
			$this->total_visitors = $total_visitors;
		}


		/**
		 * Returns the number of page views of this experiment.
		 *
		 * @return int the number of page views of this experiment.
		 *
		 * @since 3.0.0
		 */
		public function get_total_visitors() {
			return $this->total_visitors;
		}


		/**
		 * Sets the number of conversions of this experiment to the given value.
		 *
		 * @param int $total_conversions the new number of total conversions of the experiment.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public function set_total_conversions( $total_conversions ) {
			$this->total_conversions = $total_conversions;
		}


		/**
		 * Returns the number of conversions of this experiment.
		 *
		 * @return int the number of conversions of this experiment.
		 *
		 * @since 3.0.0
		 */
		public function get_total_conversions() {
			return $this->total_conversions;
		}


		/**
		 * Adds a new tuple containing information about an alternative.
		 *
		 * In the summary of an experiment, we have information about the
		 * performance of all alternatives. This information is stored in an array
		 * of tuples.
		 *
		 * The name of each alternative is generated automatically by this method. The
		 * first alternative that is added to the summary will be named "Original".
		 * From that one on, each one will be named "Alt x", where _x_ is the xth
		 * alternative.
		 *
		 * @param int $id          the ID of the alternative.
		 * @param int $visitors    the number of page views of the alternative.
		 * @param int $conversions the number of conversions of the alternative.
		 *
		 * @return void
		 *
		 * @see self::alternative_info
		 *
		 * @since 3.0.0
		 */
		public function add_alternative_info( $id, $visitors, $conversions ) {
			$name = sprintf( __( 'Alt %s', 'nelioab' ), count( $this->alternative_info ) );
			if ( count( $this->alternative_info ) == 0 )
				$name = __( 'Original', 'nelioab' );
			array_push( $this->alternative_info,
				array(
					'id' => $id,
					'name' => $name,
					'visitors' => $visitors,
					'conversions' => $conversions,
				)	);
		}


		/**
		 * Returns an array with information about all the alternatives of this experiment.
		 *
		 * @return array an array with information about all the alternatives of this experiment.
		 *
		 * @see self::alternative_info
		 *
		 * @since 3.0.0
		 */
		public function get_alternative_info() {
			return $this->alternative_info;
		}


		/**
		 * Returns the average conversion rate of this experiment.
		 *
		 * @return int the average conversion rate of this experiment.
		 *
		 * @since 3.0.0
		 */
		public function get_conversion_rate() {
			$result = 0;
			$conv   = $this->get_total_conversions();
			$visits = $this->get_total_visitors();
			if ( $visits != 0 )
				$result = $conv / $visits;
			return $result * 100;
		}


		/**
		 * Returns the conversion rate of the original alternative.
		 *
		 * @return int the conversion rate of the original alternative.
		 *
		 * @since 3.0.0
		 */
		public function get_original_conversion_rate() {
			$result = 0;
			if ( count( $this->alternative_info ) == 0 )
				return $result;
			$original_info = $this->alternative_info[0];
			if ( $original_info['visitors'] != 0 )
				$result = $original_info['conversions'] / $original_info['visitors'];
			return $result * 100;
		}


		/**
		 * Returns the conversion rate of the best alternative (excluding the original version).
		 *
		 * @return int the conversion rate of the best alternative (excluding the original version).
		 *
		 * @since 3.0.0
		 */
		public function get_best_alternative_conversion_rate() {
			$winning_cr = 0;
			for ( $i = 1; $i < count( $this->alternative_info ); ++$i ) {
				$info = $this->alternative_info[$i];
				if ( $info['visitors'] == 0 )
					continue;
				$aux = $info['conversions'] / $info['visitors'];
				if ( $aux > $winning_cr )
					$winning_cr = $aux;
			}
			return $winning_cr * 100;
		}


		/**
		 * Returns the best conversion rate available, taking into account **all** alternatives.
		 *
		 * @return int the best conversion rate available, taking into account **all** alternatives.
		 *
		 * @since 3.0.0
		 */
		public function get_winning_conversion_rate() {
			$winning_cr = 0;
			foreach ( $this->get_alternative_info() as $info ) {
				if ( $info['visitors'] == 0 )
					continue;
				$aux = $info['conversions'] / $info['visitors'];
				if ( $aux > $winning_cr )
					$winning_cr = $aux;
			}
			return $winning_cr * 100;
		}


		// @Implements
		public function load_json4ae( $json ) {
			$this->set_name( $json->name );
			$this->set_type_using_text( $json->kind );
			$this->set_creation_date( $json->creation );
			$this->set_total_visitors( $json->visitors );
			$this->set_total_conversions( $json->conversions );

			$confidence = 0;
			if ( isset( $json->confidenceInResultStatus ) )
				$confidence = $json->confidenceInResultStatus;
			if ( isset( $json->resultStatus ) )
				$this->set_result_status( $json->resultStatus, $confidence );

			if ( isset( $json->altVisitors ) ) {
				for ( $i = 0; $i < count( $json->altVisitors ); ++$i ) {
					$id = $json->altVisitors[$i]->first;
					$v = $json->altVisitors[$i]->second;
					$c = $json->altConversions[$i]->second;
					$this->add_alternative_info( $id, $v, $c );
				}
			}
			else {
				for ( $id = 0; $id < $json->alternatives; ++$id )
					$this->add_alternative_info( -$id, 0, 0 );
			}

		}

	}//NelioABAltExpSummary

}

