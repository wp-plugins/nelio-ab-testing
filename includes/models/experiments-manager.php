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

if ( !class_exists( 'NelioABExperimentsManager' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/headline-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/css-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/theme-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/widget-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/menu-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/heatmap-experiment.php' );

	class NelioABExperimentsManager implements iNelioABDataManager {

		const CACHE_ALL_EXPERIMENTS = false;

		private static $running_experiments = false;
		private static $running_headline_alt_exps = false;
		private static $experiments = false;


		public function list_elements() {
			return self::get_experiments();
		}


		public static function get_experiments() {
			require_once( NELIOAB_MODELS_DIR . '/goals/goals-manager.php' );

			// Retrieve the experiments from the current static class
			if ( self::$experiments )
				return self::$experiments;

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				// If they are not yet loaded, retrieve them from the cache
				self::$experiments = get_option( 'nelioab_experiments', false );
				usort( self::$experiments, array( 'NelioABExperiment', 'cmp_obj' ) );
				if ( self::$experiments )
					return self::$experiments;
			}

			// If the cache does not yet contain the experiments, get them from AE
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$json_data = NelioABBackend::remote_get( sprintf(
				NELIOAB_BACKEND_URL . '/site/%s/exp',
				NelioABAccountSettings::get_site_id()
			) );
			$json_data = json_decode( $json_data['body'] );

			self::$experiments = array();
			if ( isset( $json_data->items ) ) {
				foreach ( $json_data->items as $json_exp ) {
					$exp = false;
					$id = $json_exp->key->id;
					$type = NelioABExperiment::kind_to_type( $json_exp->kind );

					switch ( $type ) {
						case NelioABExperiment::POST_ALT_EXP:
						case NelioABExperiment::PAGE_ALT_EXP:
						case NelioABExperiment::PAGE_OR_POST_ALT_EXP:
							$exp = new NelioABPostAlternativeExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$exp->set_original( $json_exp->originalPost );
							break;

						case NelioABExperiment::HEADLINE_ALT_EXP:
							$exp = new NelioABHeadlineAlternativeExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$exp->set_original( $json_exp->originalPost );
							break;

						case NelioABExperiment::THEME_ALT_EXP:
							$exp = new NelioABThemeAlternativeExperiment( $id );
							break;

						case NelioABExperiment::CSS_ALT_EXP:
							$exp = new NelioABCssAlternativeExperiment( $id );
							break;

						case NelioABExperiment::WIDGET_ALT_EXP:
							$exp = new NelioABWidgetAlternativeExperiment( $id );
							break;

						case NelioABExperiment::MENU_ALT_EXP:
							$exp = new NelioABMenuAlternativeExperiment( $id );
							break;

						case NelioABExperiment::HEATMAP_EXP:
							$exp = new NelioABHeatmapExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$exp->set_post_id( $json_exp->originalPost );
							break;
					}

					if ( !$exp )
						continue;

					$exp->set_type( $type );
					$exp->set_name( $json_exp->name );
					$exp->set_status( $json_exp->status );

					if ( self::CACHE_ALL_EXPERIMENTS && $exp->get_status() == NelioABExperimentStatus::RUNNING )
						$exp = self::load_experiment_by_id( $id, $type );

					if ( isset( $json_exp->description ) )
						$exp->set_description( $json_exp->description );

					if ( isset( $json_exp->creation ) ) {
						try { $exp->set_creation_date( $json_exp->creation ); }
						catch ( Exception $exception ) {}
					}
					if ( isset( $json_exp->start ) ) {
						try { $exp->set_start_date( $json_exp->start ); }
						catch ( Exception $exception ) {}
					}
					if ( isset( $json_exp->finalization ) ) {
						try { $exp->set_end_date( $json_exp->finalization ); }
						catch ( Exception $exception ) {}
					}
					if ( isset( $json_exp->daysFinished ) ) {
						try { $exp->set_days_since_finalization( $json_exp->daysFinished ); }
						catch ( Exception $exception ) {}
					}

					array_push( self::$experiments, $exp );
				}
			}

			usort( self::$experiments, array( 'NelioABExperiment', 'cmp_obj' ) );

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				update_option( 'nelioab_experiments', self::$experiments );
			}

			return self::$experiments;
		}


		public static function get_experiment_by_id( $id, $type ) {
			if ( !self::CACHE_ALL_EXPERIMENTS )
				return self::load_experiment_by_id( $id, $type );

			// Let's make sure we have the experiments loaded in the static variable
			$aux = self::get_experiments();

			$result = false;
			$loaded_from_ae = false;

			for ( $i = 0; $i < count( self::$experiments ) && !$result; ++$i ) {
				$exp = self::$experiments[$i];
				if ( $exp->get_id() == $id && $exp->get_type() == $type ) {
					$result = $exp;
					if ( !$result->is_fully_loaded() ) {
						$result = self::load_experiment_by_id( $id, $type );
						self::$experiments[$i] = $result;
						$loaded_from_ae = true;
					}
				}
			}

			// If nothing was found, throw an exception
			if ( !$result ) {
				$err = NelioABErrCodes::EXPERIMENT_ID_NOT_FOUND;
				throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			// If the experiment was found, but we had a summary, we had to load it from AE.
			// Let's save this new version in the cache:
			if ( $loaded_from_ae )
				update_option( 'nelioab_experiments', self::$experiments );

			return $result;
		}


		private static function load_experiment_by_id( $id, $type ) {
			require_once( NELIOAB_MODELS_DIR . '/goals/goals-manager.php' );
			$exp = false;
			switch( $type ) {
				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::PAGE_ALT_EXP:
				case NelioABExperiment::PAGE_OR_POST_ALT_EXP:
					$exp = NelioABPostAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::HEADLINE_ALT_EXP:
					$exp = NelioABHeadlineAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::THEME_ALT_EXP:
					$exp = NelioABThemeAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::CSS_ALT_EXP:
					$exp = NelioABCssAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::WIDGET_ALT_EXP:
					$exp = NelioABWidgetAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::MENU_ALT_EXP:
					$exp = NelioABMenuAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::HEATMAP_EXP:
					$exp = NelioABHeatmapExperiment::load( $id );
					break;

				default:
					$err = NelioABErrCodes::EXPERIMENT_ID_NOT_FOUND;
					throw new Exception( NelioABErrCodes::to_string( $err ), $err );
			}

			$exp->mark_as_fully_loaded();

			return $exp;
		}


		public static function remove_experiment_by_id( $id, $type ) {
			$exp = self::get_experiment_by_id( $id, $type );
			$exp->remove();

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$exps = self::get_experiments();
				for ( $i = 0; count( $exps ); ++$i ) {
					$exp = $exps[$i];
					if ( $exp->get_id() == $id && $exp->get_type() == $type )
						unset( $exps[$i] );
				}
				self::$experiments = array_values( $exps );
				update_option( 'nelioab_experiments', self::$experiments );
			}

		}


		public static function update_running_experiments_cache( $force_update = 'if-required' ) {
			if ( 'now' === $force_update )
				update_option( 'nelioab_running_experiments_date', 0 );

			$last_update = get_option( 'nelioab_running_experiments_date', 0 );
			$now = time();
			// If the last update was less than fifteen minutes ago, it's OK
			if ( $now - $last_update < 900 )
				return;

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				update_option( 'nelioab_running_experiments_date', $now );

				// If we are forcing the update, or the last update is too old, we
				// perform a new update.
				try {

					// 1. Obtain all quick experiments from AE
					require_once( NELIOAB_UTILS_DIR . '/backend.php' );
					$json_data = NelioABBackend::remote_get( sprintf(
						NELIOAB_BACKEND_URL . '/site/%s/exp',
						NelioABAccountSettings::get_site_id()
					) );
					$json_data = json_decode( $json_data['body'] );
					$quick_exps = array();
					if ( isset( $json_data->items ) ) {
						foreach ( $json_data->items as $json_exp ) {
							$exp = array(
								'id'     => $json_exp->key->id,
								'type'   => NelioABExperiment::kind_to_type( $json_exp->kind ),
								'status' => $json_exp->status,
							);
							array_push( $quick_exps, $exp );
						}
					}


					// 2. Stop all experiments in our cache that are no longer running
					$aux = self::get_experiments();
					for ( $i = 0; $i < count( self::$experiments ); ++$i ) {
						$exp = self::$experiments[$i];
						$is_exp_still_running = false;
						if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
							$id = $exp->get_id();
							$type = $exp->get_type();
							foreach ( $quick_exps as $aux ) {
								if ( $aux['status'] == NelioABExperimentStatus::RUNNING && $aux['id'] == $id && $aux['type'] == $type ) {
									$is_exp_still_running = true;
									break;
								}
							}
							if ( !$is_exp_still_running )
								self::$experiments[$i] = self::load_experiment_by_id( $id, $type );
						}
					}


					// 3. Update all running experiments in our cache
					foreach ( $quick_exps as $aux ) {
						if ( $aux['status'] != NelioABExperimentStatus::RUNNING )
							continue;
						for ( $i = 0; $i < count( self::$experiments ); ++$i ) {
							$exp = self::$experiments[$i];
							if ( $exp->get_id() == $aux['id'] && $exp->get_type() == $aux['type'] )
								self::$experiments[$i] = self::load_experiment_by_id( $aux['id'], $aux['type'] );
						}
					}

					update_option( 'nelioab_experiments', self::$experiments );
					update_option( 'nelioab_running_experiments_date', $now );
				}
				catch ( Exception $e ) {
					// If we could not retrieve the running experiments, we cannot update
					// the cache...
					update_option( 'nelioab_running_experiments_date', $last_update );
				}
			}
			else {
				// If we are forcing the update, or the last update is too old, we
				// perform a new update.
				try {
					update_option( 'nelioab_running_experiments_date', time() );
					self::$running_experiments = array();
					$experiments = self::get_experiments();
					foreach ( $experiments as $exp_short ) {
						if ( $exp_short->get_status() != NelioABExperimentStatus::RUNNING )
							continue;
						$exp = self::load_experiment_by_id( $exp_short->get_id(), $exp_short->get_type() );
						array_push( self::$running_experiments, $exp );
					}
					update_option( 'nelioab_running_experiments', self::$running_experiments );
					update_option( 'nelioab_running_experiments_date', time() );
				}
				catch ( Exception $e ) {
					// If we could not retrieve the running experiments, we cannot update
					// the cache...
				}
			}
		}


		public static function update_experiment( $experiment ) {
			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$aux = self::get_experiments();
				$is_in_array = false;
				for ( $i = 0; $i < count( self::$experiments ) && !$is_in_array; ++$i ) {
					$aux = self::$experiments[$i];
					if ( $aux->get_id() == $experiment->get_id() &&
					     $aux->get_type() == $experiment->get_type() ) {
						self::$experiments[$i] = $experiment;
						$is_in_array = true;
					}
				}
				if ( !$is_in_array )
					array_push( self::$experiments, $experiment );
				update_option( 'nelioab_experiments', self::$experiments );
			}
		}


		public static function update_current_winner_for_running_experiments( $force_update = 'dont_force' ) {
			if ( 'force_update' === $force_update )
				update_option( 'nelioab_last_winners_update', 0 );
			$now = time();
			$last_update = get_option( 'nelioab_last_winners_update', 0 );
			if ( $now - $last_update < 1800 )
				return;
			update_option( 'nelioab_last_winners_update', $now );

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$aux = self::get_experiments();
				foreach ( self::$experiments as $exp ) {
					if ( $exp->get_status() == NelioABExperimentStatus::RUNNING &&
					     $exp->get_type() !== NelioABExperiment::HEATMAP_EXP )
						$exp->update_winning_alternative_from_appengine();
				}
				update_option( 'nelioab_experiments', self::$experiments );
			}
			else {
				update_option( 'nelioab_running_experiments_date', time() );
				$running_exps = self::get_running_experiments_from_cache();
				foreach ( $running_exps as $exp ) {
					if ( $exp->get_type() !== NelioABExperiment::HEATMAP_EXP )
						$exp->update_winning_alternative_from_appengine();
				}
				update_option( 'nelioab_running_experiments', $running_exps );
				update_option( 'nelioab_running_experiments_date', time() );
			}
		}


		public static function get_running_experiments_from_cache() {
			require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
			if ( self::$running_experiments )
				return self::$running_experiments;
			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$aux = self::get_experiments();
				self::$running_experiments = array();
				foreach ( $aux as $exp )
					if ( $exp->get_status() == NelioABExperimentStatus::RUNNING )
						array_push( self::$running_experiments, $exp );
				return self::$running_experiments;
			}
			else {
				self::$running_experiments = get_option( 'nelioab_running_experiments', array() );
				return self::$running_experiments;
			}
		}

		public static function get_running_headline_experiments_from_cache() {
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			if ( self::$running_headline_alt_exps )
				return self::$running_headline_alt_exps;
			self::$running_headline_alt_exps = array();
			foreach ( $running_exps as $exp )
				if ( $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP )
					array_push( self::$running_headline_alt_exps, $exp );
			return self::$running_headline_alt_exps;
		}

		public static function get_dashboard_summary() {
			// This function is used in the Dashboard
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );
			$json_data = NelioABBackend::remote_get( sprintf(
				NELIOAB_BACKEND_URL . '/site/%s/exp/summary',
				NelioABAccountSettings::get_site_id()
			) );

			// Including types of experiments...
			require_once( NELIOAB_MODELS_DIR . '/summaries/alt-exp-summary.php' );
			require_once( NELIOAB_MODELS_DIR . '/summaries/heatmap-exp-summary.php' );

			$json_data = json_decode( $json_data['body'] );
			$result = array(
				'exps'  => array(),
				'quota' => array(
					'used'  => 0,
					'total' => 7500,
				),
			);

			try {
				$result['quota']['total'] = $json_data->quotaPerMonth + $json_data->quotaExtra;
			}
			catch ( Exception $e ) {
			}

			try {
				$result['quota']['used'] = max( 0, $result['quota']['total'] - $json_data->quota );
			}
			catch ( Exception $e ) {
			}

			if ( isset( $json_data->items ) ) {
				foreach ( $json_data->items as $item ) {
					$exp = false;
					switch ( $item->kind ) {
						case NelioABExperiment::HEATMAP_EXP_STR:
							$exp = new NelioABHeatmapExpSummary( $item->key->id );
							break;
						default:
							$exp = new NelioABAltExpSummary( $item->key->id );
					}
					if ( $exp ) {
						$exp->load_json4ae( $item );
						array_push( $result['exps'], $exp );
					}
				}
			}
			return $result;
		}

	}//NelioABExperimentsManager

}

