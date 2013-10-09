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


if( !class_exists( NelioABExperimentsManager ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives-experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );

	class NelioABExperimentsManager implements iNelioABDataManager {

		private $experiments;
		private $are_experiments_loaded;

		public function __construct() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			$this->experiments            = array();
			$this->are_experiments_loaded = false;
		}

		public function get_experiments() {
			if ( $this->are_experiments_loaded )
				return $this->experiments;

			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$json_data = NelioABBackend::remote_get( sprintf(
				NELIOAB_BACKEND_URL . '/wp/site/%s/exp',
				NelioABSettings::get_site_id()
			) );

			$this->are_experiments_loaded = true;

			$json_data = json_decode( $json_data['body'] );
			if ( $json_data->items ) {
				foreach ( $json_data->items as $json_exp ) {
					$exp = new NelioABAlternativesExperiment( $json_exp->id );
					$exp->set_name( $json_exp->name );
					$exp->set_description( $json_exp->description );
					$exp->set_status( $json_exp->status );
					try { $exp->set_creation_date( $json_exp->creation ); }
					catch ( Exception $exception ) {}
					array_push( $this->experiments, $exp );
				}
			}

			return $this->experiments;
		}

		public function get_experiment_by_id( $id ) {
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/wp/altexp/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABAlternativesExperiment( $json_data->id );
			$exp->set_name( $json_data->name );
			$exp->set_description( $json_data->description );
			$exp->set_original( $json_data->originalPage );
			$exp->set_status( $json_data->status );
			$exp->set_conversion_post( $json_data->conversionPage );

			$alternatives = array();
			if ( isset( $json_data->alternatives ) ) {
				foreach ( $json_data->alternatives as $json_alt ) {
					$alt = new NelioABAlternative( $json_alt->id );
					$alt->set_name( $json_alt->name );
					$alt->set_post_id( $json_alt->page );
					array_push ( $alternatives, $alt );
				}
			}
			$exp->set_appspot_alternatives( $alternatives );

			return $exp;
		}

		public function remove_experiment_by_id( $id ) {
			$exp = $this->get_experiment_by_id( $id );
			$exp->remove();
		}

		public function list_elements() {
			return $this->get_experiments();
		}

		public static function update_running_experiments_cache( $force_update = false ) {
			if ( $force_update )
				update_option( 'nelioab_running_experiments_date', 0 );

			$last_update = get_option( 'nelioab_running_experiments_date', 0 );
			$now = mktime();
			// If the last update was less than an hour ago, it's OK
			if ( $now - $last_update < 3600 )
				return;

			// If we are forcing the update, or the last update is too old, we
			// perform a new update.
			try {
				$result        = NelioABExperimentsManager::get_running_experiments();
				$exps_in_cache = NelioABExperimentsManager::get_running_experiments_from_cache();
				update_option( 'nelioab_running_experiments', json_encode( $result ) );
				if ( count( $result ) == 0 && count( $exps_in_cache ) > 0 )
					update_option( 'nelioab_running_experiments_date', mktime() );
			}
			catch ( Exception $e ) {
				// If we could not retrieve the running experiments, we cannot update
				// the cache...
			}
		}

		public static function get_running_experiments_from_cache() {
			return json_decode( get_option( 'nelioab_running_experiments', '[]' ) );
		}

		private static function get_running_experiments() {
			$mgr = new NelioABExperimentsManager();
			$experiments = $mgr->get_experiments();

			$result = array();
			foreach ( $experiments as $exp_short ) {
				if ( $exp_short->get_status() != NelioABExperimentStatus::RUNNING )
					continue;

				$exp = $mgr->get_experiment_by_id( $exp_short->get_id() );

				$exp_info = array(
					'id'           => $exp->get_id(),
					'original'     => $exp->get_original(),
					'goal'         => $exp->get_conversion_post(),
					'alternatives' => array()
				);

				$alts = array();
				foreach ( $exp->get_alternatives() as $alt )
					array_push( $alts, $alt->get_post_id() );
				$exp_info['alternatives'] = $alts;

				array_push( $result, $exp_info );
			}

			return $result;
		}

	}//NelioABExperimentsManager

}

?>
