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



if( !class_exists( 'NelioABExperimentSummary' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	abstract class NelioABExperimentSummary extends NelioABExperiment {

		public function __construct( $id, $type ) {
			parent::__construct();
			$this->id = $id;
			$this->set_type( $type );
		}

		public abstract function load_json4ae( $json );
		public abstract function has_result_status();

		/*
		 * Implementing abstract methods from my parent class. They're not
		 * used at all, but they're needed...
		 */
		public function get_exp_kind_url_fragment() {}
		public function save() {}
		public function remove() {}
		public function start() {}
		public function stop() {}

	}//NelioABExperimentSummary

}

