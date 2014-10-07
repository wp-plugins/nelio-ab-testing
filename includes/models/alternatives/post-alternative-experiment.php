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


if( !class_exists( 'NelioABPostAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	class NelioABPostAlternativeExperiment extends NelioABAlternativeExperiment {

		private $ori;

		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::NO_TYPE_SET );
		}

		public function clear() {
			parent::clear();
			$this->ori = new NelioABAlternative();
			$this->track_heatmaps( true );
		}

		public function set_type( $type ) {
			parent::set_type( $type );
			if ( $type == NelioABExperiment::TITLE_ALT_EXP )
				$this->track_heatmaps( false );
			else
				$this->track_heatmaps( true );
		}

		public function get_original() {
			if ( !is_object( $this->ori ) ) {
				$aux = new NelioABAlternative();
				$aux->set_value( $this->ori );
				$this->ori = $aux;
			}
			return $this->ori;
		}

		public function get_originals_id() {
			$ori_alt = $this->get_original();
			return $ori_alt->get_value();
		}

		public function set_original( $ori ) {
			$ori_alt = $this->ori;
			$ori_alt->set_value( $ori );

			// Setting type
			$post = get_post( $ori, ARRAY_A );
			if ( isset( $post ) &&
			     ( $this->get_type() == NelioABExperiment::NO_TYPE_SET ||
			       $this->get_type() == NelioABExperiment::UNKNOWN_TYPE )
			   ) {
				if ( $post['post_type'] == 'page' )
					$this->set_type( NelioABExperiment::PAGE_ALT_EXP );
				else
					$this->set_type( NelioABExperiment::POST_ALT_EXP );
			}
		}

		public function set_winning_alternative_using_id( $id ) {
			$winning_alt = false;
			if ( $this->get_originals_id() == $id ) {
				$winning_alt = $this->get_original();
			}
			else {
				$alts = $this->get_alternatives();
				foreach ( $alts as $aux )
					if ( $aux->get_value() == $id )
						$winning_alt = $aux;
			}
			$this->set_winning_alternative( $winning_alt );
		}

		public function create_empty_alternative( $name, $post_type ) {
			switch ( $post_type ) {
				case NelioABExperiment::PAGE_ALT_EXP:
					$post_type = 'page';
					break;
				case NelioABExperiment::POST_ALT_EXP:
					$post_type = 'post';
					break;
				default:
					return false;
			}

			$post = array(
				'post_type'    => $post_type,
				'post_title'   => $name,
				'post_content' => '',
				'post_excerpt' => '',
				'post_status'  => 'draft',
				'post_name'    => 'nelioab_' . rand(1, 10),
			);

			// Retrieve original post
			$ori_post = get_post( $this->get_originals_id(), ARRAY_A );
			if ( $ori_post )
				$post['post_author'] = $ori_post['post_author'];

			$post_id = wp_insert_post( $post, true );
			if ( is_wp_error( $post_id ) )
				return false;

			$aux = get_post( $post_id, ARRAY_A );

			// Update the post_name
			$aux['post_name'] = 'nelioab_' . $post_id;
			wp_update_post( $aux );

			// Prepare custom metadata
			add_post_meta( $post_id, '_is_nelioab_alternative', 'true' );

			return $post_id;
		}

		public function create_alternative_copying_content( $name, $src_post_id ) {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );

			// Retrieve original post
			$src_post = get_post( $src_post_id, ARRAY_A );
			if ( !$src_post )
				return false;

			// Create new empty post
			$post_data = array(
				'post_author'  => $src_post['post_author'],
				'post_type'    => $src_post['post_type'],
				'post_title'   => $src_post['post_title'],
				'post_content' => $src_post['post_content'],
				'post_excerpt' => $src_post['post_excerpt'],
				'post_status'  => 'draft',
				'post_name'    => 'nelioab_' . rand(1, 10),
			);
			$new_post_id = wp_insert_post( $post_data, true );
			if ( is_wp_error( $new_post_id ) )
				return false;
			$new_post = get_post( $new_post_id, ARRAY_A );

			// Update the post_name
			$new_post['post_name'] = 'nelioab_' . $new_post_id;
			wp_update_post( $new_post );

			// Prepare custom metadata
			add_post_meta( $new_post_id, '_is_nelioab_alternative', 'true' );

			// Override all information
			NelioABWpHelper::overwrite( $new_post_id, $src_post_id );

			// Custom Permalinks compatibility
			require_once( NELIOAB_UTILS_DIR . '/custom-permalinks-support.php' );
			if ( NelioABCustomPermalinksSupport::is_plugin_active() )
				NelioABCustomPermalinksSupport::remove_custom_permalink( $new_post_id );

			return $new_post_id;
		}

		protected function determine_proper_status() {
			if ( count( $this->get_alternatives() ) <= 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( $this->get_originals_id() < 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( $this->get_type() != NelioABExperiment::TITLE_ALT_EXP ) {
				if ( count( $this->get_goals() ) == 0 )
					return NelioABExperimentStatus::DRAFT;
				foreach ( $this->get_goals() as $goal )
					if ( !$goal->is_ready() )
						return NelioABExperimentStatus::DRAFT;
			}

			return NelioABExperimentStatus::READY;
		}

		public function add_local_alternative( $alt ) {
			if ( $this->get_type() == NelioABExperiment::TITLE_ALT_EXP ) {
				$fake_post_id = -1;
				foreach ( $this->get_alternatives() as $aux )
					if ( $aux->get_value() <= $fake_post_id )
						$fake_post_id = $aux->get_value() - 1;
				$alt->set_value( $fake_post_id );
			}
			parent::add_local_alternative( $alt );
		}

		public function save() {
			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------
			$url = '';
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

			if ( $this->get_status() != NelioABExperimentStatus::PAUSED &&
			     $this->get_status() != NelioABExperimentStatus::RUNNING &&
			     $this->get_status() != NelioABExperimentStatus::FINISHED &&
			     $this->get_status() != NelioABExperimentStatus::TRASH )
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
				if ( $alt->was_removed() || !$alt->is_dirty() )
					continue;

				$body = array( 'name' => $alt->get_name() );
				$result = NelioABBackend::remote_post(
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

				$result = NelioABBackend::remote_post( $url );
			}


			// 2.3. CREATE LOCAL ALTERNATIVES IN APPSPOT
			foreach ( $this->get_local_alternatives() as $alt ) {
				if ( $alt->was_removed() )
					continue;

				if ( $this->get_type() != NelioABExperiment::TITLE_ALT_EXP ) {
					if ( $alt->is_based_on_a_post() ) {
						$new_id = $this->create_alternative_copying_content( $alt->get_name(), $alt->get_base_post() );
						if ( $new_id )
							$alt->set_value( $new_id );
						else
							continue;
					}
					else {
						$new_id = $this->create_empty_alternative( $alt->get_name(), $this->get_type() );
						if ( $new_id )
							$alt->set_value( $new_id );
						else
							continue;
					}
				}

				$body = array(
					'name'  => $alt->get_name(),
					'value' => $alt->get_value(),
					'kind'  => NelioABExperiment::get_textual_type(),
				);

				try {
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/post/%s/alternative', $exp_id ),
						$body );
					$alt->set_id( $result );
				}
				catch ( Exception $e ) {
					// If I could not add an alternative... remove the associated page
					wp_delete_post( $alt->get_value() );
				}

			}


			// 2.3 REMOVE THE PAGES THAT BELONGED TO AN ALTERNATIVE THAT HAS BEEN DELETED
			$all_alternatives = array_merge(
					$this->get_appspot_alternatives(),
					$this->get_local_alternatives()
				);
			foreach ( $all_alternatives as $alt ) {
				if ( $alt->was_removed() ) {
					// Delete permanently (skipping Trash)
					if ( $alt->get_value() )
						wp_delete_post( $alt->get_value(), true );
				}
			}

			// 2.4 SET META "_is_nelioab_alternative" WITH THE ID OF THE EXPERIMENT
			foreach ( $this->get_alternatives() as $alt ) {
				$value = $this->get_id() . ',' . $this->get_status();
				update_post_meta( $alt->get_value(), "_is_nelioab_alternative", $value );
			}
		}

		public function get_exp_kind_url_fragment() {
			return 'post';
		}

		public function remove() {
			// 1. For each alternative, we first remove its associated page
			foreach ( $this->get_alternatives() as $alt )
				wp_delete_post( $alt->get_value(), true );

			// 2. We remove the experiment itself
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/exp/post/%s/delete',
				$this->get_id()
			);

			$result = NelioABBackend::remote_post( $url );
		}

		public function discard_changes() {
			foreach ( $this->get_local_alternatives() as $alt ) {
				// Delete permanently (skipping Trash)
				wp_delete_post( $alt->get_value(), true );
			}
		}

		public function start() {
			// If the experiment is already running, quit
			if ( $this->get_status() == NelioABExperimentStatus::RUNNING )
				return;

			// Checking whether the experiment can be started or not...
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			foreach ( $running_exps as $running_exp ) {

				if ( $running_exp->get_type() != NelioABExperiment::PAGE_ALT_EXP &&
				     $running_exp->get_type() != NelioABExperiment::POST_ALT_EXP &&
				     $running_exp->get_type() != NelioABExperiment::TITLE_ALT_EXP )
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
					else /* if ( $running_exp->get_type() == NelioABExperiment::TITLE_ALT_EXP ) */ {
						$err_str = sprintf(
							__( 'The experiment cannot be started, because there is another experiment that is testing the title of the same page. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
							$running_exp->get_name() );
					}
					throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
				}
			}

			// If everything is OK, we can start it!

			// ...but before, if it is a title experiment, we have to create the goal
			if ( $this->get_type() == NelioABExperiment::TITLE_ALT_EXP ) {
				require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
				$goal = new NelioABAltExpGoal( $this );
				$page = new NelioABPageAccessedAction( $this->get_originals_id() );
				$page->set_indirect_navigations_enabled( true );
				$goal->add_action( $page );
				$this->add_goal( $goal );
				$this->make_goals_persistent();
			}

			// And there we go!
			$ori_post = get_post( $this->get_originals_id() );
			if ( $ori_post && $this->get_type() != NelioABExperiment::TITLE_ALT_EXP ) {
				foreach ( $this->get_alternatives() as $alt ) {
					$alt_post = get_post( $alt->get_value() );
					if ( $alt_post ) {
						$alt_post->comment_status = $ori_post->comment_status;
						wp_update_post( $alt_post );
					}
				}
			}
			$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/start',
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
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/stop',
					$this->get_id()
				);
			$result = NelioABBackend::remote_post( $url );
			$this->set_status( NelioABExperimentStatus::FINISHED );
		}

		public function set_status( $status ) {
			parent::set_status( $status );
			foreach ( $this->get_alternatives() as $alt ) {
				$value = $this->get_id() . ',' . $this->get_status();
				update_post_meta( $alt->get_value(), "_is_nelioab_alternative", $value );
			}
		}

		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/post/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABPostAlternativeExperiment( $json_data->key->id );
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
					$alt = new NelioABAlternative( $json_alt->key->id );
					$alt->set_name( $json_alt->name );
					$alt->set_value( $json_alt->value );
					array_push ( $alternatives, $alt );
				}
			}
			$exp->set_appspot_alternatives( $alternatives );

			return $exp;
		}

	}//NelioABPostAlternativeExperiment

}

