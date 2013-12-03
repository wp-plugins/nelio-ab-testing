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
		private $tests_title_only;

		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::NO_TYPE_SET );
			$this->tests_title_only = false;
		}

		public function clear() {
			parent::clear();
			$this->ori = -1;
		}

		public function get_original() {
			return $this->ori;
		}

		public function get_originals_id() {
			return $this->get_original();
		}

		public function set_to_test_title_only( $only ) {
			$this->tests_title_only = $only;
		}

		public function tests_title_only() {
			return $this->tests_title_only;
		}

		public function set_original( $ori ) {
			$this->ori = $ori;

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

		public function create_title_alternative( $name ) {
			$alts         = $this->get_alternatives();
			$fake_post_id = -1;
			foreach ( $alts as $aux )
				if ( $aux->get_value() <= $fake_post_id )
					$fake_post_id = $aux->get_value() - 1;

			$alternative = new NelioABAlternative();
			$alternative->set_name( $name );
			$alternative->set_value( $fake_post_id );
			$this->add_local_alternative( $alternative );
		}

		public function create_empty_alternative( $name, $post_type ) {
			if ( $post_type == NelioABExperiment::PAGE_ALT_EXP )
				$post_type = 'page';
			else
				$post_type = 'post';
			$post = array(
				'post_type'    => $post_type,
				'post_title'   => $name,
				'post_content' => '',
				'post_excerpt' => '',
				'post_status'  => 'draft',
				'post_name'    => 'nelioab_' . rand(1, 10),
			);

			$post_id = wp_insert_post( $post, true );
			$aux = get_post( $post_id, ARRAY_A );
			$aux['post_name'] = 'nelioab_' . $post_id;
			wp_update_post( $aux );

			add_post_meta( $post_id, '_is_nelioab_alternative', 'true' );

			$alt = new NelioABAlternative();
			$alt->set_name( $name );
			$alt->set_value( $post_id );

			$this->add_local_alternative( $alt );
		}

		public function create_alternative_copying_content( $name, $src_post_id ) {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// Retrieve original post
			$src_post = get_post( $src_post_id, ARRAY_A );
			if ( !$src_post )
				return;

			// Create new empty post
			$post_data = array(
				'post_type'    => $src_post['post_type'],
				'post_title'   => $src_post['post_title'],
				'post_content' => $src_post['post_content'],
				'post_excerpt' => $src_post['post_excerpt'],
				'post_status'  => 'draft',
				'post_name'    => 'nelioab_' . rand(1, 10),
			);
			$new_post_id = wp_insert_post( $post_data, true );
			if ( is_wp_error( $new_post_id ) )
				return;
			$new_post = get_post( $new_post_id, ARRAY_A );

			// Override all information
			NelioABWpHelper::override( $new_post_id, $src_post_id );

			// Update the post_name
			$new_post['post_name'] = 'nelioab_' . $new_post_id;
			wp_update_post( $new_post );

			// Prepare custom metadata
			add_post_meta( $new_post_id, '_is_nelioab_alternative', 'true' );
			NelioABWpHelper::override( $new_post_id, $src_post_id );

			$alt = new NelioABAlternative();
			$alt->set_name( $name );
			$alt->set_value( $new_post_id );

			$this->add_local_alternative( $alt );
		}

		protected function determine_proper_status() {
			if ( count( $this->get_alternatives() ) <= 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( $this->get_original() < 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( count( $this->get_goals() ) == 0 )
				return NelioABExperimentStatus::DRAFT;

			foreach ( $this->get_goals() as $goal )
				if ( !$goal->is_ready() )
					return NelioABExperimentStatus::DRAFT;

			return NelioABExperimentStatus::READY;
		}

		public function save() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------

			$url = '';
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/exp/post',
					NelioABSettings::get_site_id()
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
				'name'           => $this->get_name(),
				'description'    => $this->get_description(),
				'originalPost'   => $this->get_original(),
				'status'         => $this->get_status(),
				'kind'           => $this->get_textual_type(),
				'testsTitleOnly' => $this->tests_title_only(),
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

				$body = array(
					'name'  => $alt->get_name(),
					'value' => $alt->get_value(),
					'kind'  => NelioABExperiment::get_textual_type(),
				);

				try {
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/post/%s/alternative', $exp_id ),
						$body );
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
					wp_delete_post( $alt->get_value(), true );
				}
			}

			// 2.4 SET META "_is_nelioab_alternative" WITH THE ID OF THE EXPERIMENT
			foreach ( $this->get_alternatives() as $alt ) {
				$value = $this->get_id() . ',' . $this->get_status();
				update_post_meta( $alt->get_value(), "_is_nelioab_alternative", $value );
			}
		}

		public function get_url_for_making_goal_persistent( $goal, $goal_type_url ) {
			if ( $goal->get_id() == -1 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%1$s/goal/%2$s',
					$this->get_id(), $goal_type_url
				);
			}
			else {
				if ( $goal->has_to_be_deleted() )
					$action = 'delete';
				else
					$action = 'update';
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/goal/%2$s/%1$s/%3$s',
					$goal->get_id(), $goal_type_url, $action
				);
			}
			return $url;
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

			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$ori = get_post( $this->get_original() );
			if ( $ori ) {
				foreach ( $this->get_alternatives() as $alt ) {
					$alt_post = get_post( $alt->get_value() );
					if ( $alt_post ) {
						$alt_post->comment_status = $ori->comment_status;
						wp_update_post( $alt_post );
					}
				}
			}
			$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/post/%s/start',
					$this->get_id()
				);
			try {
				$this->split_page_accessed_goal_if_any();
				$result = NelioABBackend::remote_post( $url );
				$this->set_status( NelioABExperimentStatus::RUNNING );
			}
			catch ( Exception $e ) {
				$this->unsplit_page_accessed_goal_if_any();
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

	}//NelioABPostAlternativeExperiment

}

?>
