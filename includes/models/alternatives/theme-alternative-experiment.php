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

		public function get_original() {
			return $this->original_appspot_theme;
		}

		public function get_originals_id() {
			return $this->get_original()->get_id();
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
						if ( in_array( $this->get_post_id(), $this->get_origins() ) || in_array( -1, $this->get_origins() ) ) {
							$err_str = sprintf(
								__( 'The experiment cannot be started, because there is a CSS experiment running. Please, stop the experiment named «%s» before starting the new one.', 'nelioab' ),
								$running_exp->get_name() );
							throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
						}
					case NelioABExperiment::HEATMAP_EXP:
						$err_str = __( 'The experiment cannot be started, because there is one (or more) heatmap experiments running. Please make sure to stop any running heatmap experiment before starting the new one.', 'nelioab' );
						throw new Exception( $err_str, NelioABErrCodes::EXPERIMENT_CANNOT_BE_STARTED );
				}
			}
			// If everything is OK, we can start it!
			parent::start();
		}

		public function save() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			$exp_id = parent::save();

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

		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/global/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABThemeAlternativeExperiment( $json_data->key->id );
			$exp->set_type_using_text( $json_data->kind );
			$exp->set_name( $json_data->name );
			if ( isset( $json_data->description ) )
				$exp->set_description( $json_data->description );
			$exp->set_status( $json_data->status );
			if ( isset( $json_data->goals ) )
				foreach ( $json_data->goals as $goal )
					NelioABGoalsManager::load_goal_from_json( $exp, $goal );

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

	}//NelioABThemeAlternativeExperiment

}

?>
