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


if( !class_exists( 'NelioABThemeAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/global-alternative-experiment.php' );

	class NelioABThemeAlternativeExperiment extends NelioABGlobalAlternativeExperiment {

		private $original_appspot_theme;
		private $original_local_theme;

		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::THEME_ALT_EXP );
			$this->original_appspot_theme = false;
			$this->original_local_theme   = json_decode( json_encode(
				array( 'name' => '', 'value' => -1 )
			)	);
		}

		public function get_original_theme() {
			return $this->original_appspot_theme;
		}

		public function get_originals_id() {
			return $this->get_original_theme()->get_id();
		}

		protected function determine_proper_status() {
			if ( count( $this->get_local_alternatives() ) <= 0 )
				return NelioABExperimentStatus::DRAFT;
			return parent::determine_proper_status();
		}

		public function set_appspot_alternatives( $alts ) {
			$aux = array();
			if ( count( $alts ) > 0 ) {
				$this->original_appspot_theme = $alts[0];
				for ( $i = 1; $i < count( $alts ); $i++ )
					array_push( $aux, $alts[$i] );
			}
			parent::set_appspot_alternatives( $aux );
		}

		public function set_current_default_theme( $id, $name ) {
			$this->original_local_theme->value = $id;
			$this->original_local_theme->name  = $name;
		}

		public function add_selected_theme( $id, $name ) {
			$this->add_local_alternative(
				json_decode( json_encode(
					array( 'name' => $name, 'value' => $id )
				) ) );
		}

		public function encode_appspot_alternatives() {
			$aux = array();
			$ori = $this->original_appspot_theme;
			if ( $ori )
				array_push( $aux, $ori->json() );
			foreach ( $this->get_appspot_alternatives() as $alt )
				array_push( $aux, $alt->json() );
			return base64_encode( json_encode( $aux ) );
		}

		public function encode_local_alternatives() {
			return base64_encode( json_encode( $this->get_local_alternatives() ) );
		}

		public function load_encoded_local_alternatives( $input ) {
			$aux = json_decode( base64_decode( $input ) );
			foreach( $aux as $alt )
				$this->add_local_alternative( $alt );
		}

		private function is_new( $theme_id ) {
			foreach( $this->get_appspot_alternatives() as $alt )
				if ( $alt->get_value() == $theme_id )
					return false;
			return true;
		}

		private function was_removed( $alt ) {
			foreach( $this->get_local_alternatives() as $local )
				if ( $local->value == $alt->get_value() )
					return false;
			return true;
		}

		public function is_theme_selected_locally( $theme_id ) {
			foreach( $this->get_local_alternatives() as $alt )
				if ( $alt->value == $theme_id )
					return true;
			return false;
		}

		public function save() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// 1. UPDATE OR CREATE THE EXPERIMENT
			$url = '';
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/exp/global',
					NelioABSettings::get_site_id()
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


			// 2. UPDATE THE ALTERNATIVES
			// -------------------------------------------------------------------------

			// 2.1. CREATE OR DELETE ORIGINAL THEME
			$ori_local   = $this->original_local_theme;
			$ori_appspot = $this->original_appspot_theme;

			// Create...
			if ( !$ori_appspot ) {
				if ( $ori_local->value !== -1 ) {
					$body = array(
						'name'  => $ori_local->name,
						'value' => $ori_local->value,
						'kind'  => NelioABExperiment::THEME_ALT_EXP_STR,
					);
					try {
						$result = NelioABBackend::remote_post(
							sprintf( NELIOAB_BACKEND_URL . '/exp/global/%s/alternative', $exp_id ),
							$body );
					}
					catch ( Exception $e ) {
					}
				}
			}
			// Edit...
			else {
				if ( $ori_local->value != $ori_appspot->get_value() ) {
					$body = array(
						'name'  => $ori_local->name,
						'value' => $ori_local->value,
					);
					$url = sprintf(
						NELIOAB_BACKEND_URL . '/alternative/%s/update',
						$ori_appspot->get_id()
					);
					$result = NelioABBackend::remote_post( $url, $body );
				}
			}

			// 2.2. REMOVE FROM APPSPOT THE ALTERNATIVES THAT ARE NOT SELECTED LOCALLY
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				if ( !$this->was_removed( $alt ) )
					continue;

				$url = sprintf(
					NELIOAB_BACKEND_URL . '/alternative/%s/delete',
					$alt->get_id()
				);

				$result = NelioABBackend::remote_post( $url );
			}


			// 2.3. CREATE "NEW" LOCAL ALTERNATIVES IN APPSPOT
			foreach ( $this->get_local_alternatives() as $alt ) {
				if ( !$this->is_new( $alt->value ) )
					continue;

				$body = array(
					'name'  => $alt->name,
					'value' => $alt->value,
					'kind' => NelioABExperiment::THEME_ALT_EXP_STR,
				);

				try {
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/global/%s/alternative', $exp_id ),
						$body );
				}
				catch ( Exception $e ) {
				}

			}

		}

		public function get_url_for_making_goal_persistent( $goal, $goal_type_url ) {
			if ( $goal->get_id() == -1 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/global/%1$s/goal/%2$s',
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

	}//NelioABThemeAlternativeExperiment

}

?>
