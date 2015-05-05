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


if ( !class_exists( 'NelioABHeatmapExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/backend.php' );

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-statistics.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/gtest.php' );

	/**
	 * Abstract class representing a Heatmap Experiment.
	 *
	 * @package \NelioABTesting\Models\Experiments
	 * @since 2.0.10
	 */
	class NelioABHeatmapExperiment extends NelioABExperiment {

		/**
		 * The ID of the post whose Heatmaps (and Clickmaps) have to be tracked.
		 *
		 * @since 2.0.10
		 * @var int
		 */
		private $post_id;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param int $id the ID of this experiment, as defined in AppEngine.
		 *
		 * @return NelioABHeatmapExperiment a new instance of this class.
		 *
		 * @since 2.0.10
		 */
		public function __construct( $id ) {
			parent::__construct();
			$this->id = $id;
			$this->post_id = false;
			$this->set_type( NelioABExperiment::HEATMAP_EXP );
		}


		/**
		 * Returns the ID of the post for which Heatmaps are to be tracked.
		 *
		 * @return int the ID of the post for which Heatmaps are to be tracked.
		 *
		 * @since 2.0.10
		 */
		public function get_post_id() {
			return $this->post_id;
		}

		// @Override
		public function get_related_post_id() {
			return $this->get_post_id();
		}


		/**
		 * Returns the ID of the post for which Heatmaps are to be tracked.
		 *
		 * @return int the ID of the post for which Heatmaps are to be tracked.
		 *
		 * @since 4.0.0
		 * @Implements
		 */
		public function get_originals_id() {
			return $this->get_post_id();
		}


		/**
		 * Sets the ID of the post for which Heatmaps have to be tracked to the given ID.
		 *
		 * IDs have to be valid, which means they must be positive integers. The
		 * only negative integers allowed are those defined in the
		 * `NelioABController`:
		 *
		 * * `FRONT_PAGE__YOUR_LATEST_POSTS`
		 * * `FRONT_PAGE__THEME_BASED_LANDING`
		 *
		 * @param int $id the new post ID to be used.
		 *
		 * @return void
		 *
		 * @since 2.0.10
		 */
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


		/**
		 * Recovers the experiment from the trash and makes it available again.
		 *
		 * The status in which the experiment will appear depends on the
		 * information it contains.
		 * @see self::determine_proper_status.
		 *
		 * @return void
		 *
		 * @since 2.0.10
		 */
		public function untrash() {
			$this->update_status_and_save( $this->determine_proper_status() );
		}


		/**
		 * Sets the status of this experiment to the given status and saves it to AppEngine.
		 *
		 * @param int $status the new status of this experiment.
		 *
		 * @return void
		 *
		 * @since 2.0.10
		 */
		public function update_status_and_save( $status ) {
			if ( $this->get_id() < 0 )
				$this->save();

			$this->set_status( $status );
			$this->save();
		}


		/**
		 * Determines the proper status of this experiment, depending on its information.
		 *
		 * @return int the status of this experiment. If it has no post related, then it's _DRAFT_. Otherwise, it's _READY_.
		 *
		 * @since 2.0.10
		 */
		protected function determine_proper_status() {
			if ( !$this->post_id )
				return NelioABExperiment::STATUS_DRAFT;

			return NelioABExperiment::STATUS_READY;
		}


		// @Implements
		public function save() {
			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------

			/** @var string $url */
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

			if ( $this->get_status() != NelioABExperiment::STATUS_PAUSED &&
			     $this->get_status() != NelioABExperiment::STATUS_RUNNING &&
			     $this->get_status() != NelioABExperiment::STATUS_FINISHED &&
			     $this->get_status() != NelioABExperiment::STATUS_TRASH )
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


		// @Implements
		public function remove() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/delete',
				$this->get_id()
			);
			NelioABBackend::remote_post( $url );
		}


		// @Implements
		public function start() {

			// Checking whether the experiment can be started or not...
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			foreach ( $running_exps as $running_exp ) {
				/** @var NelioABGlobalAlternativeExperiment $running_exp */
				// $running_exp can actually be anything, but we're focusing
				// on Global Alternative Experiments only.
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
			if ( $this->get_status() == NelioABExperiment::STATUS_RUNNING )
				return;

			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/start',
				$this->get_id()
			);
			NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperiment::STATUS_RUNNING );
		}


		// @Implements
		public function stop() {
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/hm/%s/stop',
				$this->get_id()
			);
			NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperiment::STATUS_FINISHED );
		}


		// @Implements
		public function get_exp_kind_url_fragment() {
			return 'hm';
		}


		// @Implements
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

