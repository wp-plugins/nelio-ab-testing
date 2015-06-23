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


if ( !class_exists( 'NelioABExperimentSummary' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	/**
	 * Abstract class representing the summary of an AB Experiment.
	 *
	 * In order to create an instance of this class, one must use of its
	 * concrete subclasses.
	 *
	 * @package \NelioABTesting\Models\Experiments\Summaries
	 * @since 3.0.0
	 */
	abstract class NelioABExperimentSummary extends NelioABExperiment {

		/**
		 * It creates a new instance of Experiment Summary.
		 *
		 * @param int $id the ID of the experiment summary.
		 *
		 * @return NelioABExperimentSummary a new instance of this class.
		 *
		 * @since 4.1.0
		 */
		public function __construct( $id ) {
			parent::__construct();
			$this->id = $id;
		}


		/**
		 * Extracts all the information from the JSON object and saves it in this experiment summary.
		 *
		 * @param Object $json the JSON object as retrieved from AppEngine.
		 *                     It contains all the summarized information about
		 *                     this experiment. The method will extract it and
		 *                     initialize this experiment summary instance.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public abstract function load_json4ae( $json );


		/**
		 * Specifies whether this experiment has a metric that acknowledges the status of the experiment.
		 *
		 * Alternative Experiments have a progress status, which depends on the
		 * performance of each alternative. If an alternative is clearly better
		 * than the others, we have a winner. That's the result status.
		 *
		 * Heatmap experiments, on the other hand, do not have such a metric.
		 *
		 * @return boolean whether this experiment has a metric that acknowledges the status of the experiment.
		 *
		 * @since 3.0.0
		 */
		public abstract function has_result_status();


		// @Implements
		public function get_originals_id() {
			return false;
		}


		// @Implements
		public function get_exp_kind_url_fragment() {}


		// @Implements
		public function save() {}


		// @Implements
		public function remove() {}


		// @Implements
		public function start() {}


		// @Implements
		public function stop() {}

	}//NelioABExperimentSummary

}

