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


if( !class_exists( 'NelioABGlobalAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	abstract class NelioABGlobalAlternativeExperiment extends NelioABAlternativeExperiment {

		private $ori;

		public function __construct( $id ) {
			parent::__construct( $id );
		}

		public function clear() {
			parent::clear();
			$this->ori = array( -1 );
		}

		public function set_winning_alternative_using_id( $id ) {
			$winning_alt = false;
			if ( $this->get_originals_id() == $id )
				$winning_alt = $this->get_original();
			else
				$winning_alt = $this->get_alternative_by_id( $id );
			$this->set_winning_alternative( $winning_alt );
		}

		public function get_origins() {
			return $this->ori;
		}

		public function set_origins( $ori ) {
			$this->ori = $ori;
		}

		public function add_origin( $ori ) {
			array_push( $this->ori, $ori );
		}

		protected function determine_proper_status() {
			if ( count( $this->get_goals() ) == 0 )
				return NelioABExperimentStatus::DRAFT;

			foreach ( $this->get_goals() as $goal )
				if ( !$goal->is_ready() )
					return NelioABExperimentStatus::DRAFT;

			return NelioABExperimentStatus::READY;
		}

		public function remove() {
			// 1. We remove the experiment itself
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/delete',
				$this->get_id()
			);

			$result = NelioABBackend::remote_post( $url );
		}

		public function discard_changes() {
			// Nothing to be done, here
 		}

		public function start() {
			// If the experiment is already running, quit
			if ( $this->get_status() == NelioABExperimentStatus::RUNNING )
				return;

			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/start',
				$this->get_id()
			);
			try {
				$result = NelioABBackend::remote_post( $url );
				$this->set_status( NelioABExperimentStatus::RUNNING );
			}
			catch ( Exception $e ) {
				throw $e;
			}
		}

		public function stop() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/stop',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperimentStatus::FINISHED );
		}

		public function save() {
			require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );

			// 1. UPDATE OR CREATE THE EXPERIMENT
			$url = '';
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/exp/global',
					NelioABAccountSettings::get_site_id()
				);
			}
			else {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/global/%s/update',
					$this->get_id()
				);
			}

			if ( $this->get_status() != NelioABExperimentStatus::PAUSED &&
			     $this->get_status() != NelioABExperimentStatus::RUNNING &&
			     $this->get_status() != NelioABExperimentStatus::FINISHED &&
			     $this->get_status() != NelioABExperimentStatus::TRASH )
				$this->set_status( $this->determine_proper_status() );

			$body = array(
				'name'           => $this->get_name(),
				'description'    => $this->get_description(),
				'origin'         => $this->get_origins(),
				'status'         => $this->get_status(),
				'kind'           => $this->get_textual_type(),
			);

			$result = NelioABBackend::remote_post( $url, $body );

			$exp_id = $this->get_id();
			if ( $exp_id < 0 ) {
				if ( is_wp_error( $result ) )
					return;
				$json = json_decode( $result['body'] );
				$exp_id = $json->key->id;
				$this->id = $exp_id;
			}

			// 1.1 SAVE GOALS
			// -------------------------------------------------------------------------
			$this->make_goals_persistent();

			return $this->get_id();
		}

		public function get_exp_kind_url_fragment() {
			return 'global';
		}

	}//NelioABGlobalAlternativeExperiment

}

?>
