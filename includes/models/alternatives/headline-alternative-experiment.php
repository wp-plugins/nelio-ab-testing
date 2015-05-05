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


if ( !class_exists( 'NelioABHeadlineAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/headline-alternative.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	/**
	 * PHPDOC
	 *
	 * @package \NelioABTesting\Models\Experiments\AB
	 * @since PHPDOC
	 */
	class NelioABHeadlineAlternativeExperiment extends NelioABAlternativeExperiment {

		/**
		 * PHPDOC
		 *
		 * PHPDOC: This should probably be inherited from PostAlternative
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $ori;


		// @Override
		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::HEADLINE_ALT_EXP );
		}


		// @Override
		public function clear() {
			parent::clear();
			$this->ori = new NelioABHeadlineAlternative();
			$this->track_heatmaps( false );
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
		public function set_original( $ori ) {
			$aux = $ori;
			if ( !is_object( $ori ) ) {
				$aux = new NelioABHeadlineAlternative();
				$aux->set_value( $ori );
			}

			if ( !is_array( $aux->get_value() ) ) {
				$id = $aux->get_value();
				$aux->set_value_compat( $id, $id );
			}

			$this->ori = $aux;

			$post = get_post( $this->get_originals_id() );
			if ( $post )
				$aux->set_name( $post->post_title );
		}


		// @Override
		public function get_original() {
			return $this->ori;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_originals_id() {
			/** @var NelioABHeadlineAlternative $ori_alt */
			$ori_alt = $this->get_original();
			$val = $ori_alt->get_value();
			return $val['id'];
		}


		// @Override
		public function get_related_post_id() {
			$this->get_originals_id();
		}


		// @Override
		public function set_winning_alternative_using_id( $id ) {
			$winning_alt = false;
			if ( $this->get_originals_id() == $id ) {
				$winning_alt = $this->get_original();
			}
			else {
				$alts = $this->get_alternatives();
				foreach ( $alts as $aux ) {
					/** @var NelioABHeadlineAlternative $aux */
					$val = $aux->get_value();
					if ( $val['id'] == $id )
						$winning_alt = $aux;
				}
			}
			$this->set_winning_alternative( $winning_alt );
		}


		// @Override
		protected function determine_proper_status() {
			if ( count( $this->get_alternatives() ) <= 0 )
				return NelioABExperiment::STATUS_DRAFT;

			if ( $this->get_originals_id() < 0 )
				return NelioABExperiment::STATUS_DRAFT;

			return NelioABExperiment::STATUS_READY;
		}


		// @Override
		public function add_local_alternative( $alt ) {
			$fake_post_id = -1;
			foreach ( $this->get_alternatives() as $aux ) {
				/** @var NelioABHeadlineAlternative $aux */
				$val = $aux->get_value();
				if ( isset( $val['id'] ) && $val['id'] <= $fake_post_id )
					$fake_post_id = $val['id'] - 1;
			}
			$val = $alt->get_value();
			$val['id'] = $fake_post_id;
			$alt->set_value( $val );
			parent::add_local_alternative( $alt );
		}


		// @Override
		public function load_json4js_alternatives( $json_alts ) {
			$this->appspot_alternatives = array();
			$this->local_alternatives = array();
			foreach ( $json_alts as $json_alt ) {
				if ( isset( $json_alt->isNew ) && $json_alt->isNew &&
				     isset( $json_alt->wasDeleted ) && $json_alt->wasDeleted )
					continue;
				$alt = NelioABHeadlineAlternative::build_alternative_using_json4js( $json_alt );
				if ( $alt->get_id() > 0 )
					$this->add_appspot_alternative( $alt );
				else
					$this->add_local_alternative( $alt );
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param array $headline_info PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function fix_image_id_in_value( $headline_info ) {
			if ( !isset( $headline_info['image_id'] ) || 'inherit' == $headline_info['image_id'] ) {
				$alt = get_post_thumbnail_id( $this->get_originals_id() );
				if ( $alt )
					$headline_info['image_id'] = intval( $alt );
				else
					$headline_info['image_id'] = intval( 0 );
			}
			return $headline_info;

		}


		// @Override
		public function save() {
			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/exp/post',
					NelioABAccountSettings::get_site_id()
				);
			}
			else {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/update',
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
				'originalPost'          => $this->get_originals_id(),
				'status'                => $this->get_status(),
				'kind'                  => $this->get_textual_type(),
				'showHeatmap'           => $this->are_heatmaps_tracked(),
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

			// 1.1 SAVE GOALS
			// -------------------------------------------------------------------------
			$this->make_goals_persistent();


			// 2. UPDATE THE ALTERNATIVES
			// -------------------------------------------------------------------------

			// 2.1. UPDATE CHANGES ON ALREADY EXISTING APPSPOT ALTERNATIVES
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				/** @var NelioABHeadlineAlternative $alt */
				if ( $alt->was_removed() || !$alt->is_dirty() )
					continue;

				$body = array(
					'name' => $alt->get_name(),
					'value' => json_encode( $this->fix_image_id_in_value( $alt->get_value() ) ),
				);
				NelioABBackend::remote_post(
					sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/update', $alt->get_id() ),
					$body );
			}

			// 2.2. REMOVE FROM APPSPOT THE REMOVED ALTERNATIVES
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				if ( !$alt->was_removed() )
					continue;

				$url = sprintf(
					NELIOAB_BACKEND_URL . '/alternative/%s/delete',
					$alt->get_id()
				);

				NelioABBackend::remote_post( $url );
			}


			// 2.3. CREATE LOCAL ALTERNATIVES IN APPSPOT
			foreach ( $this->get_local_alternatives() as $alt ) {
				if ( $alt->was_removed() )
					continue;

				$body = array(
					'name'  => $alt->get_name(),
					'value' => json_encode( $this->fix_image_id_in_value( $alt->get_value() ) ),
					'kind'  => $this->get_textual_type(),
				);

				try {
					/** @var int $result */
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/post/%s/alternative', $exp_id ),
						$body );
					$alt->set_id( $result );
				}
				catch ( Exception $e ) {
				}

			}

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			NelioABExperimentsManager::update_experiment( $this );
		}


		// @Implements
		public function get_exp_kind_url_fragment() {
			return 'post';
		}


		// @Override
		public function remove() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/post/%s/delete',
				$this->get_id()
			);

			NelioABBackend::remote_post( $url );
		}


		// @Override
		public function start() {
			// If the experiment is already running, quit
			if ( $this->get_status() == NelioABExperiment::STATUS_RUNNING )
				return;

			// Checking whether the experiment can be started or not...
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			foreach ( $running_exps as $running_exp ) {
				/** @var NelioABExperiment $running_exp */

				if ( $running_exp->get_type() != NelioABExperiment::PAGE_ALT_EXP &&
				     $running_exp->get_type() != NelioABExperiment::POST_ALT_EXP &&
				     $running_exp->get_type() != NelioABExperiment::CPT_ALT_EXP  &&
				     $running_exp->get_type() != NelioABExperiment::HEADLINE_ALT_EXP )
					continue;

				if ( $running_exp->get_originals_id() == $this->get_originals_id() ) {
					if ( $running_exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is another experiment running that is testing the same page. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
					}
					else if ( $running_exp->get_type() == NelioABExperiment::POST_ALT_EXP ) {
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is another experiment running that is testing the same post. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
					}
					else if ( $running_exp->get_type() == NelioABExperiment::CPT_ALT_EXP ) {
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is another experiment running that is testing the same custom post. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
					}
					else /* if ( $running_exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP ) */ {
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is another experiment that is testing the title of the same page. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
					}
					throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
				}
			}

			// If everything is OK, we can start it!

			// (keep in mind that, if it is a title experiment, we'll create the goal in AE

			// And there we go!
			$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/start',
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
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/stop',
					$this->get_id()
				);
			NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperiment::STATUS_FINISHED );
		}


		// @Override
		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/post/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABHeadlineAlternativeExperiment( $json_data->key->id );
			$exp->set_name( $json_data->name );
			if ( isset( $json_data->description ) )
				$exp->set_description( $json_data->description );
			$exp->set_type_using_text( $json_data->kind );
			$exp->set_original( $json_data->originalPost );
			$exp->set_status( $json_data->status );
			$exp->set_finalization_mode( $json_data->finalizationMode );
			if ( isset( $json_data->finalizationModeValue ) )
				$exp->set_finalization_value( $json_data->finalizationModeValue );
			$exp->track_heatmaps( false );
			if ( isset( $json_data->showHeatmap ) && $json_data->showHeatmap  )
				$exp->track_heatmaps( $json_data->showHeatmap );

			if ( isset( $json_data->goals ) )
				NelioABExperiment::load_goals_from_json( $exp, $json_data->goals );

			$alternatives = array();
			if ( isset( $json_data->alternatives ) ) {
				foreach ( $json_data->alternatives as $json_alt ) {
					$alt = new NelioABHeadlineAlternative( $json_alt->key->id );
					$alt->set_name( $json_alt->name );
					if ( NelioABExperiment::HEADLINE_ALT_EXP_STR == $json_alt->kind )
						$alt->set_value( json_decode( $json_alt->value, true ) );
					else
						// This else part is for compatibility with previous Title exp
						$alt->set_value_compat( $json_alt->value, $json_data->originalPost );
					array_push ( $alternatives, $alt );
				}
			}
			$exp->set_appspot_alternatives( $alternatives );

			return $exp;
		}


		// @Override
		public function discard_changes() {
			// Nothing to be done, here
		}

	}//NelioABHeadlineAlternativeExperiment

}

