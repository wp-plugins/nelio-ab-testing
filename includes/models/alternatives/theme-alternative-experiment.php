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

		/**
		 * We'll use a different approach, here. I'll store the list of appsport IDs, and
		 * overwrite them as necessary. If I have less selected themes than appspot ids,
		 * I'll remove the ones that are no longer necessary. If there are less appspot ids
		 * than selected themes, I'll create the new ones.
		 */
		private $appspot_ids;

		private $selected_themes;

		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::THEME_ALT_EXP );
			$this->original_appspot_theme = false;
			$this->selected_themes = array();
			$this->appspot_ids = array();
		}

		public function get_original() {
			return $this->original_appspot_theme;
		}

		public function get_originals_id() {
			return $this->get_original()->get_id();
		}

		protected function determine_proper_status() {
			if ( count( $this->selected_themes ) <= 0 )
				return NelioABExperimentStatus::DRAFT;
			return parent::determine_proper_status();
		}

		public function set_appspot_alternatives( $alts ) {
			$aux = array();
			foreach ( $alts as $alt )
				array_push( $this->appspot_ids, $alt->get_id() );
			if ( count( $alts ) > 0 ) {
				$this->original_appspot_theme = $alts[0];
				for ( $i = 1; $i < count( $alts ); $i++ )
					array_push( $aux, $alts[$i] );
			}
			parent::set_appspot_alternatives( $aux );
		}

		public function set_appspot_ids( $ids ) {
			$this->appspot_ids = $ids;
		}

		public function get_appspot_ids() {
			return $this->appspot_ids;
		}

		public function add_selected_theme( $id, $name ) {
			foreach ( $this->selected_themes as $theme )
				if ( $theme->value === $id )
					return;
			if ( strlen( $id ) === 0 )
				return;
			array_push( $this->selected_themes,
				json_decode( json_encode(
					array( 'name' => $name, 'value' => $id, 'isSelected' => true )
				) ) );
		}

		public function get_selected_themes() {
			return $this->selected_themes;
		}

		public function is_theme_selected( $theme_id ) {
			foreach( $this->selected_themes as $selected_theme )
				if ( $selected_theme->value == $theme_id )
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
			$exp_id = parent::save();

			// 2. UPDATE THE ALTERNATIVES
			// -------------------------------------------------------------------------

			// 2.1 REUSE ALL APPSPOT ALTERNATIVES
			$i = 0;
			while ( $i < count( $this->appspot_ids ) && $i < count( $this->selected_themes ) ) {
				$theme = $this->selected_themes[$i];
				$body = array(
					'name'  => $theme->name,
					'value' => $theme->value,
				);
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/alternative/%s/update',
					$this->appspot_ids[$i]
				);
				$result = NelioABBackend::remote_post( $url, $body );
				$i++;
			}

			// 2.2 CREATE NEW APPSPOT ALTERNATIVES (IF REQUIRED)
			while ( $i < count( $this->selected_themes ) ) {
				$theme = $this->selected_themes[$i];
				$body = array(
					'name'  => $theme->name,
					'value' => $theme->value,
					'kind' => NelioABExperiment::THEME_ALT_EXP_STR,
				);
				try {
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/global/%s/alternative', $exp_id ),
						$body );
					array_push( $this->appspot_ids, $result );
				}
				catch ( Exception $e ) {
				}
				$i++;
			}

			// 2.3 REMOVE UNUSED APPSPOT ALTERNATIVES (IF REQUIRED)
			$last_valid = $i;
			while ( $i < count( $this->appspot_ids ) ) {
				$id = $this->appspot_ids[$i];
				$url = sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/delete', $id );
				$result = NelioABBackend::remote_post( $url );
				$i++;
			}

			$aux = $this->appspot_ids;
			$this->appspot_ids = array();
			for ( $i = 0; $i < $last_valid; ++$i )
				array_push( $this->appspot_ids, $aux[$i] );
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
			$exp->set_finalization_mode( $json_data->finalizationMode );
			if ( isset( $json_data->finalizationModeValue ) )
				$exp->set_finalization_value( $json_data->finalizationModeValue );

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

	}//NelioABThemeAlternativeExperiment

}

