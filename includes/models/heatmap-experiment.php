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

if ( !class_exists( 'NelioABHeatmapExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-statistics.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/gtest.php' );

	class NelioABHeatmapExperiment extends NelioABExperiment {

		private $post_id;

		public function __construct( $id ) {
			parent::__construct();
			$this->id = $id;
			$this->post_id = false;
			$this->set_type( NelioABExperiment::HEATMAP_EXP );
		}

		public function get_post_id() {
			return $this->post_id;
		}

		public function get_related_post_id() {
			return $this->get_post_id();
		}

		public function get_originals_id() {
			return $this->get_post_id();
		}

		public function set_post_id( $id ) {
			if ( $id > 0 )
				$this->post_id = $id;
			else if ( NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS == $id )
				$this->post_id = $id;
			else if ( NelioABController::FRONT_PAGE__THEME_BASED_LANDING == $id )
				$this->post_id = $id;
			else
				$this->post_id = false;
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

		protected function determine_proper_status() {
			if ( !$this->post_id )
				return NelioABExperimentStatus::DRAFT;

			return NelioABExperimentStatus::READY;
		}

		public function save() {
			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------

			$url = '';
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/exp/hm',
					NelioABAccountSettings::get_site_id()
				);
			}
			else {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/hm/%s/update',
					$this->get_id()
				);
			}

			if ( $this->get_status() != NelioABExperimentStatus::PAUSED &&
			     $this->get_status() != NelioABExperimentStatus::RUNNING &&
			     $this->get_status() != NelioABExperimentStatus::FINISHED &&
			     $this->get_status() != NelioABExperimentStatus::TRASH )
				$this->set_status( $this->determine_proper_status() );

			$body = array(
				'name'                  => $this->get_name(),
				'description'           => $this->get_description(),
				'post'                  => $this->get_post_id(),
				'status'                => $this->get_status(),
				'kind'                  => $this->get_textual_type(),
				'finalizationMode'      => $this->get_finalization_mode(),
				'finalizationModeValue' => $this->get_finalization_value(),
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

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			NelioABExperimentsManager::update_experiment( $this );
		}

		public function remove() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/delete',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
		}

		public function start() {

			// Checking whether the experiment can be started or not...
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			foreach ( $running_exps as $running_exp ) {
				switch ( $running_exp->get_type() ) {
					case NelioABExperiment::THEME_ALT_EXP:
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is a theme experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
						throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
					case NelioABExperiment::CSS_ALT_EXP:
						if ( in_array( $this->get_post_id(), $running_exp->get_origins() ) || in_array( -1, $running_exp->get_origins() ) ) {
							$err_str = sprintf(
								__( 'The experiment cannot be started, because there is a running CSS experiment that may be changing the appearence of the tested page. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
								$running_exp->get_name() );
							throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
						}
				}
			}

			// If everything is OK, we can start it!

			// If the experiment is already running, quit
			if ( $this->get_status() == NelioABExperimentStatus::RUNNING )
				return;

			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/start',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperimentStatus::RUNNING );
		}

		public function stop() {
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/stop',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperimentStatus::FINISHED );
		}

		public function get_exp_kind_url_fragment() {
			return 'hm';
		}

		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/hm/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABHeatmapExperiment( $json_data->key->id );
			$exp->set_type_using_text( $json_data->kind );
			$exp->set_name( $json_data->name );
			$exp->set_post_id( $json_data->post );
			if ( isset( $json_data->description ) )
				$exp->set_description( $json_data->description );
			$exp->set_status( $json_data->status );
			$exp->set_finalization_mode( $json_data->finalizationMode );
			if ( isset( $json_data->finalizationModeValue ) )
				$exp->set_finalization_value( $json_data->finalizationModeValue );

			if ( isset( $json_data->goals ) )
				NelioABExperiment::load_goals_from_json( $exp, $json_data->goals );

			return $exp;
		}
	}//NelioABHeatmapExperiment

}

