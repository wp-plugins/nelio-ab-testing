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


if ( !class_exists( 'NelioABGlobalAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	/**
	 * Abstract class representing a global A/B Experiment.
	 *
	 * In order to create an instance of this class, one must use of its
	 * concrete subclasses.
	 *
	 * @package \NelioABTesting\Models\Experiments\AB
	 * @since PHPDOC
	 */
	abstract class NelioABGlobalAlternativeExperiment extends NelioABAlternativeExperiment {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $ori;

		/**
		 * Creates a new instance of this class.
		 *
		 * This constructor might be used by the concrete subclasses. It sets all
		 * attributes to their default values.
		 *
		 * @param int $id PHPDOC
		 *
		 * @return NelioABGlobalAlternativeExperiment a new instance of this class.
		 *
		 * @see self::clear
		 *
		 * @since PHPDOC
		 */
		public function __construct( $id ) {
			parent::__construct( $id );
		}


		// @Overrides
		public function is_global() {
			return true;
		}


		// @Overrides
		public function clear() {
			parent::clear();
			$this->ori = array( -1 );
		}


		// @Overrides
		public function set_winning_alternative_using_id( $id ) {
			if ( $this->get_originals_id() == $id )
				$winning_alt = $this->get_original();
			else
				$winning_alt = $this->get_alternative_by_id( $id );
			$this->set_winning_alternative( $winning_alt );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_origins() {
			return $this->ori;
		}


		/**
		 * PHPDOC
		 *
		 * @param array $ori PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_origins( $ori ) {
			$this->ori = $ori;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $ori PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function add_origin( $ori ) {
			array_push( $this->ori, $ori );
		}


		// @Implements
		protected function determine_proper_status() {
			if ( count( $this->get_goals() ) == 0 )
				return NelioABExperiment::STATUS_DRAFT;

			foreach ( $this->get_goals() as $goal ) {
				/** @var NelioABGoal $goal */
				if ( !$goal->is_ready() )
					return NelioABExperiment::STATUS_DRAFT;
			}

			return NelioABExperiment::STATUS_READY;
		}


		// @Implements
		public function remove() {
			// 1. We remove the experiment itself
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/delete',
				$this->get_id()
			);

			NelioABBackend::remote_post( $url );
		}


		// @Implements
		public function discard_changes() {
			// Nothing to be done, here
		}


		// @Implements
		public function start() {
			// If the experiment is already running, quit
			if ( $this->get_status() == NelioABExperiment::STATUS_RUNNING )
				return;

			// Checking whether the experiment can be started or not...
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$this_exp_origins = $this->get_origins();
			array_push( $this_exp_origins, -1 );

			foreach ( $running_exps as $running_exp ) {
				/** @var NelioABExperiment $running_exp */

				switch ( $running_exp->get_type() ) {

					case NelioABExperiment::THEME_ALT_EXP:
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is a theme experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
						throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );

					case NelioABExperiment::CSS_ALT_EXP:
						/** @var NelioABGlobalAlternativeExperiment $running_exp */
						foreach( $this_exp_origins as $origin_id ) {
							if ( in_array( $origin_id, $running_exp->get_origins() ) ) {
								$err_str = sprintf(
									__( 'The experiment cannot be started, because there is a CSS experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
									$running_exp->get_name() );
								throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
							}
						}
						break;

					case NelioABExperiment::WIDGET_ALT_EXP:
						/** @var NelioABGlobalAlternativeExperiment $running_exp */
						foreach( $this_exp_origins as $origin_id ) {
							if ( in_array( $origin_id, $running_exp->get_origins() ) ) {
								$err_str = sprintf(
									__( 'The experiment cannot be started, because there is a Widget experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
									$running_exp->get_name() );
								throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
							}
						}
						break;

					case NelioABExperiment::MENU_ALT_EXP:
						/** @var NelioABGlobalAlternativeExperiment $running_exp */
						foreach( $this_exp_origins as $origin_id ) {
							if ( in_array( $origin_id, $running_exp->get_origins() ) ) {
								$err_str = sprintf(
									__( 'The experiment cannot be started, because there is a Menu experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
									$running_exp->get_name() );
								throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
							}
						}
						break;

					case NelioABExperiment::HEATMAP_EXP:
						if ( $this->get_type() != NelioABExperiment::WIDGET_ALT_EXP ) {
							$err_str = __( 'The experiment cannot be started, because there is one (or more) heatmap experiments running. Please make sure to stop any running heatmap experiment before starting the new one.', 'nelioab' );
							throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
						}
						break;

				}
			}

			// If everything is OK, we can start it!
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/start',
				$this->get_id()
			);
			try {
				NelioABBackend::remote_post( $url );
				$this->set_status( NelioABExperiment::STATUS_RUNNING );
			}
			catch ( Exception $e ) {
				throw $e;
			}
		}


		// @Implements
		public function stop() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/global/%s/stop',
				$this->get_id()
			);
			NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperiment::STATUS_FINISHED );
		}


		// @Implements
		public function save() {
			// 1. UPDATE OR CREATE THE EXPERIMENT
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

			if ( $this->get_status() != NelioABExperiment::STATUS_PAUSED &&
			     $this->get_status() != NelioABExperiment::STATUS_RUNNING &&
			     $this->get_status() != NelioABExperiment::STATUS_FINISHED &&
			     $this->get_status() != NelioABExperiment::STATUS_TRASH )
				$this->set_status( $this->determine_proper_status() );

			$body = array(
				'name'                  => $this->get_name(),
				'description'           => $this->get_description(),
				'origin'                => $this->get_origins(),
				'status'                => $this->get_status(),
				'kind'                  => $this->get_textual_type(),
				'finalizationMode'      => $this->get_finalization_mode(),
				'finalizationModeValue' => $this->get_finalization_value(),
			);

			/** @var array $result */
			$result = NelioABBackend::remote_post( $url, $body );

			$exp_id = $this->get_id();
			if ( $exp_id < 0 ) {
				if ( is_wp_error( $result ) )
					return 0;
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

