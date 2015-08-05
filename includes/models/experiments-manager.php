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

if ( !class_exists( 'NelioABExperimentsManager' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/headline-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/css-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/theme-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/widget-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/menu-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/woocommerce/product-summary-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/heatmap-experiment.php' );

	/**
	 * A class containing some useful functions for managing experiments.
	 *
	 * This class was originally designed for loading, saving, and keeping track
	 * of our customers' experiments. With the latest releases, however, we've
	 * shifted some of its logic (such as, for instance, how to parse a JSON
	 * result to build an experiment instance) to specific experiment classes.
	 *
	 * @package \NelioABTesting\Models\Experiments
	 * @since 1.0.10
	 */
	class NelioABExperimentsManager implements iNelioABDataManager {

		/**
		 * It specifies whether a cache of all experiments is kept in WordPress or not.
		 *
		 * @since 3.4.0
		 * @var boolean
		 */
		const CACHE_ALL_EXPERIMENTS = false;


		/**
		 * List of experiments defined within the current site.
		 *
		 * If the variable has not yet been initialized, it's false.
		 *
		 * @since 3.4.0
		 * @var boolean|array
		 */
		private static $experiments = false;


		/**
		 * List of running experiments defined within the current site.
		 *
		 * If the variable has not yet been initialized, it's false.
		 *
		 * @since 1.4.0
		 * @var boolean|array
		 */
		private static $running_experiments = false;


		/**
		 * List of relevant running experiments defined within the current site.
		 *
		 * A relevant running experiment is a running experiment in which the
		 * current visitor participates. Therefore, this only makes sense if the
		 * plugin has been "launched" by a normal request from a regular visitor.
		 *
		 * If the variable has not yet been initialized, it's false.
		 *
		 * @since 4.0.0
		 * @var boolean|array
		 */
		private static $relevant_running_experiments = false;


		// @Implements
		public function list_elements() {
			return self::get_experiments();
		}


		/**
		 * Returns the list of experiments.
		 *
		 * If the experiments are not cached and it's the first time we call the
		 * operation in a specific request, the function accesses AppEngine and
		 * retrieves the list of experiments from there.
		 *
		 * @return array the list of experiments.
		 *
		 * @since 1.0.10
		 */
		public static function get_experiments() {
			require_once( NELIOAB_MODELS_DIR . '/goals/goals-manager.php' );

			// Retrieve the experiments from the current static class
			if ( self::$experiments )
				return self::$experiments;

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				// If they are not yet loaded, retrieve them from the cache
				self::$experiments = get_option( 'nelioab_experiments', false );
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
					/** @var NelioABExperiment $exp */
					$exp = NULL;
					$id = $json_exp->key->id;
					$type = NelioABExperiment::kind_to_type( $json_exp->kind );

					switch ( $type ) {
						case NelioABExperiment::POST_ALT_EXP:
						case NelioABExperiment::PAGE_ALT_EXP:
						case NelioABExperiment::PAGE_OR_POST_ALT_EXP:
						case NelioABExperiment::CPT_ALT_EXP:
							/** @var NelioABPostAlternativeExperiment $aux */
							$aux = new NelioABPostAlternativeExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$aux->set_original( $json_exp->originalPost );
							$exp = $aux;
							break;

						case NelioABExperiment::HEADLINE_ALT_EXP:
							/** @var NelioABHeadlineAlternativeExperiment $aux */
							$aux = new NelioABHeadlineAlternativeExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$aux->set_original( $json_exp->originalPost );
							$exp = $aux;
							break;

						case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
							/** @var NelioABHeadlineAlternativeExperiment $aux */
							$aux = new NelioABProductSummaryAlternativeExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$aux->set_original( $json_exp->originalPost );
							$exp = $aux;
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
							/** @var NelioABHeatmapExperiment $aux */
							$aux = new NelioABHeatmapExperiment( $id );
							if ( isset( $json_exp->originalPost ) )
								$aux->set_post_id( $json_exp->originalPost );
							$exp = $aux;
							break;
					}

					if ( !$exp )
						continue;

					$exp->set_type( $type );
					$exp->set_name( $json_exp->name );
					$exp->set_status( $json_exp->status );

					if ( self::CACHE_ALL_EXPERIMENTS && $exp->get_status() == NelioABExperiment::STATUS_RUNNING )
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

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				update_option( 'nelioab_experiments', self::$experiments );
			}

			return self::$experiments;
		}


		/**
		 * Returns the experiment whose ID and type are the given ones.
		 *
		 * @param int $id   the ID of the experiment we want to retrieve.
		 * @param int $type the type of the experiment we want to retrieve.
		 *
		 * @return NelioABExperiment the experiment whose ID is `$id` and whose type is `$type`.
		 *
		 * @throws Exception `EXPERIMENT_ID_NOT_FOUND`
		 *                   This exception is thrown if the experiment was not
		 *                   found in AppEngine.
		 *
		 * @see self::load_experiment_by_id
		 *
		 * @since 1.0.10
		 */
		public static function get_experiment_by_id( $id, $type ) {
			if ( !self::CACHE_ALL_EXPERIMENTS )
				return self::load_experiment_by_id( $id, $type );

			// Let's make sure we have the experiments loaded in the static variable
			self::get_experiments();

			$result = false;
			$loaded_from_ae = false;

			for ( $i = 0; $i < count( self::$experiments ) && !$result; ++$i ) {
				/** @var NelioABExperiment $exp */
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


		/**
		 * Loads the experiment from AppEngine.
		 *
		 * @param int $id   the ID of the experiment we want to retrieve.
		 * @param int $type the type of the experiment we want to retrieve.
		 *
		 * @return NelioABExperiment the experiment whose ID is `$id` and whose type is `$type`.
		 *                           Note that the result will be an instance of
		 *                           a concrete subclass of `NelioABExperiment`.
		 *
		 * @throws Exception `EXPERIMENT_ID_NOT_FOUND`
		 *                   This exception is thrown if the experiment was not
		 *                   found in AppEngine.
		 *
		 * @see NelioABExperiment::load
		 *
		 * @since 3.4.0
		 */
		private static function load_experiment_by_id( $id, $type ) {
			require_once( NELIOAB_MODELS_DIR . '/goals/goals-manager.php' );
			/** @var NelioABExperiment $exp */
			switch( $type ) {
				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::PAGE_ALT_EXP:
				case NelioABExperiment::PAGE_OR_POST_ALT_EXP:
				case NelioABExperiment::CPT_ALT_EXP:
					$exp = NelioABPostAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::HEADLINE_ALT_EXP:
					$exp = NelioABHeadlineAlternativeExperiment::load( $id );
					break;

				case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
					$exp = NelioABProductSummaryAlternativeExperiment::load( $id );
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


		/**
		 * Removes the experiment from AppEngine, as well as any local information it created.
		 *
		 * @param int $id   the ID of the experiment we want to remove.
		 * @param int $type the type of the experiment we want to remove.
		 *
		 * @throws Exception `EXPERIMENT_ID_NOT_FOUND`
		 *                   This exception is thrown if the experiment was not
		 *                   found in AppEngine.
		 *
		 * @see NelioABExperiment::remove
		 *
		 * @since 1.0.10
		 */
		public static function remove_experiment_by_id( $id, $type ) {
			$exp = self::get_experiment_by_id( $id, $type );
			$exp->remove();

			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$exps = self::get_experiments();
				for ( $i = 0; count( $exps ); ++$i ) {
					/** @var NelioABExperiment $exp */
					$exp = $exps[$i];
					if ( $exp->get_id() == $id && $exp->get_type() == $type )
						unset( $exps[$i] );
				}
				self::$experiments = array_values( $exps );
				update_option( 'nelioab_experiments', self::$experiments );
			}

		}


		/**
		 * Obtains the list of experiments from AppEngine and saves in a local (WordPress) cache those that are running.
		 *
		 * @param string $force_update whether the update has to be performed right now or only if a certain amount of time has passed by.
		 *                             The accepted values are:
		 *                             * `if-required`: a certain amount of time has passed by.
		 *                             * `now`: the update has to be performed right now
		 *
		 * @since 1.0.10
		 */
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
					self::get_experiments();
					for ( $i = 0; $i < count( self::$experiments ); ++$i ) {
						/** @var NelioABExperiment $exp */
						$exp = self::$experiments[$i];
						$is_exp_still_running = false;
						if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
							$id = $exp->get_id();
							$type = $exp->get_type();
							foreach ( $quick_exps as $aux ) {
								if ( $aux['status'] == NelioABExperiment::STATUS_RUNNING && $aux['id'] == $id && $aux['type'] == $type ) {
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
						if ( $aux['status'] != NelioABExperiment::STATUS_RUNNING )
							continue;
						for ( $i = 0; $i < count( self::$experiments ); ++$i ) {
							/** @var NelioABExperiment $exp */
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
						/** @var NelioABExperimentSummary $exp_short */
						if ( $exp_short->get_status() != NelioABExperiment::STATUS_RUNNING )
							continue;
						/** @var NelioABExperiment $exp */
						$exp = self::load_experiment_by_id( $exp_short->get_id(), $exp_short->get_type() );
						array_push( self::$running_experiments, $exp );
					}
					update_option( 'nelioab_running_experiments', self::$running_experiments );
					update_option( 'nelioab_running_experiments_date', time() );
				}
				catch ( Exception $e ) {
					// If we could not retrieve the running experiments, we cannot update
					// the cache...
					update_option( 'nelioab_running_experiments_date', $last_update );
				}
			}
		}


		/**
		 * If caching all experiments, this function updates the cache with the given experiment.
		 *
		 * @param NelioABExperiment $experiment the experiment to be added to or updated from the cache.
		 *
		 * @since 3.4.0
		 */
		public static function update_experiment( $experiment ) {
			if ( self::CACHE_ALL_EXPERIMENTS ) {
				self::get_experiments();
				$is_in_array = false;
				for ( $i = 0; $i < count( self::$experiments ) && !$is_in_array; ++$i ) {
					/** @var NelioABExperiment $aux */
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


		/**
		 * Returns the list of running experiments from the local cache.
		 *
		 * @return array the list of running experiments from the local cache.
		 *
		 * @since 1.0.10
		 */
		public static function get_running_experiments_from_cache() {
			require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
			if ( self::$running_experiments )
				return self::$running_experiments;
			if ( self::CACHE_ALL_EXPERIMENTS ) {
				$aux = self::get_experiments();
				self::$running_experiments = array();
				foreach ( $aux as $exp )
					/** @var NelioABExperiment $exp */
					if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING )
						array_push( self::$running_experiments, $exp );
				return self::$running_experiments;
			}
			else {
				self::$running_experiments = get_option( 'nelioab_running_experiments', array() );
				return self::$running_experiments;
			}
		}


		/**
		 * Returns the list of running experiments for which the current user has one alternative assigned.
		 *
		 * @return array the list of running experiments for which the current user has one alternative assigned.
		 *
		 * @see NelioABVisitor::get_experiment_ids_in_request
		 *
		 * @since 4.0.0
		 */
		public static function get_relevant_running_experiments_from_cache() {
			if ( self::$relevant_running_experiments )
				return self::$relevant_running_experiments;

			$env_ids = NelioABVisitor::get_experiment_ids_in_request();
			$running_experiments = self::get_running_experiments_from_cache();

			$relevant_running_experiments = array();
			foreach ( $running_experiments as $exp ) {
				/** @var NelioABExperiment $exp */
				$is_relevant = false;
				for ( $i=0; $i < count( $env_ids ) && !$is_relevant; ++$i )
					if ( $exp->get_id() == $env_ids[$i] )
						$is_relevant = true;
				if ( $is_relevant ) {
					$already_in_array = false;
					foreach ( $relevant_running_experiments as $relevant_exp )
						/** @var NelioABExperiment $relevant_exp */
						if ( $relevant_exp->get_id() == $exp->get_id() )
							$already_in_array = true;
					if ( !$already_in_array )
						array_push( $relevant_running_experiments, $exp );
				}
			}

			if ( NelioABVisitor::is_fully_loaded() )
				self::$relevant_running_experiments = $relevant_running_experiments ;
			else
				self::$relevant_running_experiments = false;

			return $relevant_running_experiments;
		}


		/**
		 * Returns the most relevant information of Nelio A/B Testing (to be displayed in the dashboard).
		 *
		 * @return array {
		 *     Most relevant information of Nelio A/B Testing (to be displayed in the dashboard).
		 *
		 *     @type array $exps  List of running experiments.
		 *     @type array $quota Two integers: the amount of `used` quota and the `total` quota available.
		 * }
		 *
		 * @since 3.4.4
		 */
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
					'regular' => 5000,
					'monthly' => 5000,
					'extra'   => 0,
				),
			);

			if ( isset( $json_data->quota ) ) {
				$result['quota']['regular'] = $json_data->quota + $json_data->quotaExtra;
			}

			if ( isset( $json_data->quotaPerMonth ) ) {
				$result['quota']['monthly'] = $json_data->quotaPerMonth;
			}

			if ( $result['quota']['regular'] > $result['quota']['monthly'] ) {
				$diff = $result['quota']['regular'] - $result['quota']['monthly'];
				$result['quota']['extra'] += $diff;
				$result['quota']['regular'] = $result['quota']['monthly'];
			}

			if ( isset( $json_data->items ) ) {
				foreach ( $json_data->items as $item ) {
					/** @var NelioABExperimentSummary $exp */
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

