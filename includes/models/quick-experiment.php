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


if( !class_exists( 'NelioABQuickExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	final class NelioABQuickExperiment extends NelioABExperiment {

		private $type;

		public function __construct( $id ) {
			parent::__construct();
			$this->id   = $id;
			$this->type = -1;
		}

		public function get_type() {
			return $this->type;
		}

		public function set_type( $type ) {
			$this->type = $type;
		}

		public function save() {
			// Nothing to be done here
		}

		public function remove() {
			// Nothing to be done here
		}

		public function start() {
			// Nothing to be done here
		}

		public function stop() {
			// Nothing to be done here
		}

		public function get_url_for_making_goal_persistent( $goal, $type ) {
			// Nothing to be done here
		}

	}//NelioABPostAlternativeExperiment

}

?>
