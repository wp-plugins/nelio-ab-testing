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
	require_once( NELIOAB_MODELS_DIR . '/alternatives/gtest.php' );

	abstract class NelioABAlternativeExperiment extends NelioABExperiment {

		private $appspot_alternatives;
		private $local_alternatives;

		private $winning_alternative;

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
			$this->winning_alternative = false;
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

		public function get_json4js_alternatives() {
			$result = array();

			foreach ( $this->appspot_alternatives as $alt ) {
				if ( $alt->was_removed() )
					continue;
				$json_alt = $alt->json4js();
				array_push( $result, $json_alt );
			}

			foreach ( $this->local_alternatives as $alt ) {
				if ( $alt->was_removed() )
					continue;
				$json_alt = $alt->json4js();
				$json_alt['isNew'] = true;
				array_push( $result, $json_alt );
			}

			return $result;
		}

		public function get_alternative_by_id( $id ) {
			foreach ( $this->get_alternatives() as $alt )
				if ( $alt->get_id() == $id )
					return $alt;
			return false;
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

		public function remove_alternative_by_id( $id ) {
			foreach ( $this->get_alternatives() as $alt ) {
				if ( $alt->get_id() == $id ) {
					$alt->mark_as_removed();
					return;
				}
			}
		}

		public function load_json4js_alternatives( $json_alts ) {
			foreach ( $json_alts as $json_alt ) {
				if ( isset( $json_alt->isNew ) && $json_alt->isNew &&
				     isset( $json_alt->wasDeleted ) && $json_alt->wasDeleted )
					continue;
				$alt = NelioABAlternative::build_alternative_using_json4js( $json_alt );
				if ( $alt->get_id() > 0 )
					$this->add_appspot_alternative( $alt );
				else
					$this->add_local_alternative( $alt );
			}
		}

		public function update_winning_alternative_from_appengine() {
			$this->winning_alternative = false;
			try {
				require_once( NELIOAB_UTILS_DIR . '/backend.php' );
				$json_data = NelioABBackend::remote_get( sprintf(
					NELIOAB_BACKEND_URL . '/exp/%s/%s/winner',
					$this->get_exp_kind_url_fragment(), $this->get_id()
				) );
				$json_data = json_decode( $json_data['body'] );
				if ( isset( $json_data->winner ) && $json_data->winner != 'NO_WINNER' )
					$this->set_winning_alternative_using_id( $json_data->winner );
			}
			catch ( Exception $e ) {
			}
		}

		protected function set_winning_alternative( $alt ) {
			$this->winning_alternative = $alt;
		}

		public function get_winning_alternative() {
			return $this->winning_alternative;
		}

		public abstract function discard_changes();
		public abstract function get_original();
		public abstract function get_originals_id();
		public abstract function set_winning_alternative_using_id( $id );
		protected abstract function determine_proper_status();

	}//NelioABAlternativeExperiment

}

