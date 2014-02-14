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


if( !class_exists( 'NelioABAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-statistics.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/gstats.php' );

	abstract class NelioABAlternativeExperiment extends NelioABExperiment {

		private $appspot_alternatives;
		private $local_alternatives;

		private $track_heatmaps;

		public function __construct( $id ) {
			parent::__construct();
			$this->id = $id;
		}

		public function clear() {
			parent::clear();
			$this->appspot_alternatives = array();
			$this->local_alternatives = array();
			$this->track_heatmaps = false;
		}

		public function track_heatmaps( $do_track ) {
			$this->track_heatmaps = $do_track;
		}

		public function are_heatmaps_tracked() {
			return $this->track_heatmaps;
		}

		public function get_appspot_alternatives() {
			return $this->appspot_alternatives;
		}

		public function set_appspot_alternatives( $alts ) {
			$this->appspot_alternatives = $alts;
		}

		public function get_alternatives() {
			$result = array();

			foreach ( $this->appspot_alternatives as $alt )
				if ( !$alt->was_removed() )
					array_push( $result, $alt );

			foreach ( $this->local_alternatives as $alt )
				if ( !$alt->was_removed() )
					array_push( $result, $alt );

			return $result;
		}

		public function untrash() {
			$this->update_status_and_save( $this->determine_proper_status() );
		}

		public function update_status_and_save( $status ) {
			if ( $this->get_id() < 0 )
				$this->save();

			$this->set_status( $status );
			$this->save();
		}

		public function get_local_alternatives() {
			return $this->local_alternatives;
		}

		public function add_appspot_alternative( $alt ) {
			array_push( $this->appspot_alternatives, $alt );
		}

		public function add_local_alternative( $alt ) {
			if ( $alt instanceof NelioABAlternative ) {
				$new_id = count( $this->local_alternatives ) + 1;
				$alt->set_id( -$new_id );
			}
			array_push( $this->local_alternatives, $alt );
		}

		public function encode_appspot_alternatives() {
			$aux = array();
			foreach ( $this->get_appspot_alternatives() as $alt )
				array_push( $aux, $alt->json() );
			return base64_encode( json_encode( $aux ) );
		}

		public function load_encoded_appspot_alternatives( $input ) {
			$data = json_decode( base64_decode( $input ) );
			$aux  = array();
			foreach( $data as $json_alt ) {
				$alt = new NelioABAlternative();
				$alt->load_json( $json_alt );
				array_push( $aux, $alt );
			}
			$this->set_appspot_alternatives( $aux );
		}

		public function encode_local_alternatives() {
			$aux = array();
			foreach ( $this->get_local_alternatives() as $alt )
				array_push( $aux, $alt->json() );
			return base64_encode( json_encode( $aux ) );
		}

		public function load_encoded_local_alternatives( $input ) {
			$data = json_decode( base64_decode( $input ) );
			foreach( $data as $json_alt ) {
				$alt = new NelioABAlternative();
				$alt->load_json( $json_alt );
				array_push( $this->local_alternatives, $alt );
			}
		}

		public function remove_alternative_by_id( $id ) {
			foreach ( $this->get_alternatives() as $alt ) {
				if ( $alt->get_id() == $id ) {
					$alt->mark_as_removed();
					return;
				}
			}
		}

		public abstract function discard_changes();
		public abstract function get_original();
		public abstract function get_originals_id();
		protected abstract function determine_proper_status();

	}//NelioABAlternativeExperiment

}

?>
