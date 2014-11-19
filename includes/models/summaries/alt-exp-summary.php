<?php
/**
 * Copyright 2013 Nelio Software S.L.
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
	class NelioABAltExpSummary extends NelioABExperimentSummary {

		private $result_status;
		private $alternative_info;
		private $total_visitors;
		private $total_conversions;

		public function __construct( $id ) {
			parent::__construct( $id, NelioABExperiment::PAGE_OR_POST_ALT_EXP );
			$this->alternative_info = array();
		}

		public function set_result_status( $status, $confidence ) {
			$this->result_status = NelioABGTest::get_result_status_from_str( $status );
			if ( NelioABGTest::WINNER == $this->result_status ) {
				if ( NelioABSettings::get_min_confidence_for_significance() <= $confidence )
					$this->result_status = NelioABGTest::WINNER_WITH_CONFIDENCE;
			}
		}

		public function get_result_status() {
			return $this->result_status;
		}

		public function has_result_status() {
			return true;
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

		public function get_alternative_info() {
			return $this->alternative_info;
		}

		public function get_conversion_rate() {
			$result = 0;
			$conv   = $this->get_total_conversions();
			$visits = $this->get_total_visitors();
			if ( $visits != 0 )
				$result = $conv / $visits;
			return $result * 100;
		}

		public function get_original_conversion_rate() {
			$original_info = $this->alternative_info[0];
			$result = 0;
			if ( $original_info['visitors'] != 0 )
				$result = $original_info['conversions'] / $original_info['visitors'];
			return $result * 100;
		}

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
