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



if( !class_exists( 'NelioABHeatmapExpSummary' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/summaries/experiment-summary.php' );
	class NelioABHeatmapExpSummary extends NelioABExperimentSummary {

		private $heatmap_info;
		private $clickmap_info;

		public function __construct( $id ) {
			parent::__construct( $id, NelioABExperiment::HEATMAP_EXP );
			$this->heatmap_info = array(
					'phone'   => 0,
					'tablet'  => 0,
					'desktop' => 0,
					'hd'      => 0,
				);
			$this->clickmap_info = array(
					'phone'   => 0,
					'tablet'  => 0,
					'desktop' => 0,
					'hd'      => 0,
				);
		}

		public function has_result_status() {
			return false;
		}

		public function get_heatmap_info() {
			return $this->heatmap_info;
		}

		public function get_clickmap_info() {
			return $this->clickmap_info;
		}

		public function load_json4ae( $json ) {
			$this->set_name( $json->name );
			$this->set_creation_date( $json->creation );
			foreach ( $json->heatmapParticipants as $key => $val )
				$this->heatmap_info[$key] = $val;
			foreach ( $json->clickmapParticipants as $key => $val )
				$this->clickmap_info[$key] = $val;
		}

	}//NelioABHeatmapExpSummary

}
