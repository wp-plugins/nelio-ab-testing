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


if ( !class_exists( 'NelioABVisitor' ) ) {

	/**
	 * This class contains the alternatives a certain visitor is supposed to see.
	 *
	 * Before version 4.1.0, this class was known as `NelioABVisitor`.
	 *
	 * @package \NelioABTesting\Models
	 * @since 4.1.0
	 */
	class NelioABVisitor {

		/**
		 * List of experiment IDs for which this visitor has an alternative.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $ids = array();


		/**
		 * A tuple with the running CSS experiment and one of its alternatives (the one this visitor has to see).
		 *
		 * @since 4.0.0
		 * @var boolean|array{
		 *      @type NelioABCssAlternativeExperiment $exp The running CSS experiment.
		 *      @type NelioABAlternative              $alt The alternative that belongs to _$exp_ that this visitor is supposed to see.
		 * }
		 */
		private static $css_info = false;


		/**
		 * A tuple with the running Menu experiment and one of its alternatives (the one this visitor has to see).
		 *
		 * @since 4.0.0
		 * @var boolean|array{
		 *      @type NelioABMenuAlternativeExperiment $exp The running Menu experiment.
		 *      @type NelioABAlternative               $alt The alternative that belongs to _$exp_ that this visitor is supposed to see.
		 * }
		 */
		private static $menu_info = false;


		/**
		 * A tuple with the running Theme experiment and one of its alternatives (the one this visitor has to see).
		 *
		 * @since 4.0.0
		 * @var boolean|array{
		 *      @type NelioABThemeAlternativeExperiment $exp The running Theme experiment.
		 *      @type NelioABAlternative                $alt The alternative that belongs to _$exp_ that this visitor is supposed to see.
		 * }
		 */
		private static $theme_info = false;


		/**
		 * A tuple with the running Widget experiment and one of its alternatives (the one this visitor has to see).
		 *
		 * @since 4.0.0
		 * @var boolean|array{
		 *      @type NelioABWidgetAlternativeExperiment $exp The running Widget experiment.
		 *      @type NelioABAlternative                 $alt The alternative that belongs to _$exp_ that this visitor is supposed to see.
		 * }
		 */
		private static $widget_info = false;


		/**
		 * Hashmap of experiments (key) and the alternatives (value) this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $exps = array();


		/**
		 * Hashmap of headline experiments (key) and the alternatives (value) this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $headline_exps = array();


		/**
		 * Hashmap of woocommerce product summary experiments (key) and the alternatives (value) this visitor is supposed to see.
		 *
		 * TODO: this should be refactored and extensible...
		 *
		 * @since 4.2.0
		 * @var array
		 */
		private static $wc_product_summary_exps = array();


		/**
		 * Specifies whether the environment of this visitor has been fully loaded or not.
		 *
		 * @since 4.0.0
		 * @var boolean
		 */
		private static $is_fully_loaded = false;


		/**
		 * Loads the environment of this visitor.
		 *
		 * In order to load the environment, this function hooks to two WordPress
		 * action: `setup_theme` and `the_posts`. The former is used for
		 * determining almost all the alternatives the visitor has assigned.The
		 * latter is used for discovering which alternative is assigned for the
		 * currently requested post (the one specified by the `nab` GET parameter.
		 * We need to use two hooks because the sooner we have the alternatives,
		 * the better, but the ID of the currently requested post is not available
		 * before the `the_posts` action.
		 *
		 * @see self::do_first_load
		 * @see self::do_late_load
		 *
		 * @since 4.0.0
		 */
		public static function load() {
			add_action( 'setup_theme', array( 'NelioABVisitor', 'do_first_load' ) );
			add_action( 'the_posts',   array( 'NelioABVisitor', 'do_late_load' ) );
		}


		/**
		 * Callback for the `setup_theme` action, which loads the environment of this visitor.
		 *
		 * This function is able to determine almost all the alternatives this
		 * visitor is supposed to see. The only alternative that cannot be
		 * determined at this point is the one related to the currently requested
		 * post.
		 *
		 * @see self::do_late_load
		 *
		 * @since 4.0.0
		 */
		public static function do_first_load() {
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$exp_and_alt_list = array();

			if ( isset( $_POST['nelioab_env'] ) ) {
				$exp_and_alt_list = $_POST['nelioab_env'];
			}
			elseif ( isset( $_POST['nelioab_form_env'] ) ) {
				$exp_and_alt_list = json_decode( urldecode( $_POST['nelioab_form_env'] ) );
			}
			else if ( !is_admin() ) {
				foreach ( $_GET as $key => $val ) {
					switch ( $key ) {
						case 'nabe':
							try {
								$aux = explode( ',', $val );
								foreach ( $aux as $pair ) {
									try {
										$pair = explode( ':', $pair );
										$exp_and_alt_list[$pair[0]] = $pair[1];
									}
									catch ( Exception $e ) {}
								}
							}
							catch ( Exception $e ) {}
							break;
						case 'nabc':
							foreach ( $running_exps as $exp ) {
								/** @var NelioABExperiment $exp */
								if ( $exp->get_type() == NelioABExperiment::CSS_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							}
							break;
						case 'nabm':
							foreach ( $running_exps as $exp ) {
								/** @var NelioABExperiment $exp */
								if ( $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							}
							break;
						case 'nabt':
							foreach ( $running_exps as $exp ) {
								/** @var NelioABExperiment $exp */
								if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							}
							break;
						case 'nabw':
							foreach ( $running_exps as $exp ) {
								/** @var NelioABExperiment $exp */
								if ( $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							}
							break;
					}
				}
			}
			NelioABVisitor::prepareEnvironment( $exp_and_alt_list );
		}


		/**
		 * Callback for the `the_posts` action, which loads the remaining experiment (if the currently requested page is under test) from the environment.
		 *
		 * @param array $posts The array of retrieved posts.
		 *
		 * @return array The array of retrieved posts.
		 *
		 * @since 4.0.0
		 */
		public static function do_late_load( $posts ) {
			/** @var NelioABController $nelioab_controller */
			global $nelioab_controller;
			$current_id = $nelioab_controller->get_queried_post_id();

			// If I don't know which is the queried post, I can't complete the
			// environment yet.
			if ( !$current_id )
				return $posts;

			if ( !is_admin() && isset( $_GET['nab'] ) ) {

				/** @var int $val */
				$val = $_GET['nab'];

				/** @var NelioABPostAlternativeExperiment $exp */
				$exp = NULL;

				require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
				$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
				for ( $i = 0; $i < count( $running_exps ) && !$exp; ++$i ) {
					$aux = $running_exps[$i];
					if ( $aux instanceof NelioABAlternativeExperiment &&
					     $aux->get_originals_id() == $current_id )
						$exp = $aux;
				}
				if ( $exp )
					NelioABVisitor::completeEnvironment( $exp, $val );
			}
			self::$is_fully_loaded = true;
			remove_action( 'the_posts',   array( 'NelioABVisitor', 'do_late_load' ) );

			return $posts;
		}


		/**
		 * Initializes the properties of this class.
		 *
		 * This function iterates over all the experiment and assigned alternative
		 * in which this visitor participates and initializes the relevant
		 * properties of the class. In particular, it sets the properties
		 * `$css_info`, `$menu_info,` `$theme_info`, and `$widget_info` for global
		 * experiments and the arrays `$exps` and `$headline_exps`.
		 *
		 *
		 * @param array $env a hashmap of experiments (key) and the alternatives (value) this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 */
		private static function prepareEnvironment( $env ) {
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();

			self::$ids = array();

			self::$css_info    = false;
			self::$menu_info   = false;
			self::$theme_info  = false;
			self::$widget_info = false;

			self::$exps = array();
			self::$headline_exps = array();
			self::$wc_product_summary_exps = array();

			foreach ( $env as $exp_id => $alt ) {
				/** @var NelioABExperiment $aux */
				$exp = NULL;
				foreach ( $running_exps as $aux )
					if ( $aux->get_id() == $exp_id )
						$exp = $aux;
				if ( $exp ) {
					switch ( $exp->get_type() ) {
						case NelioABExperiment::PAGE_ALT_EXP:
						case NelioABExperiment::POST_ALT_EXP:
						case NelioABExperiment::CPT_ALT_EXP:
							/** @var NelioABPostAlternativeExperiment $exp */
							self::add_regular_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
						case NelioABExperiment::HEADLINE_ALT_EXP:
							/** @var NelioABHeadlineAlternativeExperiment $exp */
							self::add_headline_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
						case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
							/** @var NelioABHeadlineAlternativeExperiment $exp */
							self::add_wc_product_summary_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
						case NelioABExperiment::CSS_ALT_EXP:
						case NelioABExperiment::MENU_ALT_EXP:
						case NelioABExperiment::THEME_ALT_EXP:
						case NelioABExperiment::WIDGET_ALT_EXP:
							/** @var NelioABGlobalAlternativeExperiment $exp */
							self::add_global_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
					}
				}
			}
		}


		/**
		 * Adds the experiment related to the currently requested post to the setup.
		 *
		 * @param NelioABPostAlternativeExperiment $exp       The experiment that tests the currently requested post.
		 * @param int                              $alt_index The alternative (of `$exp`) this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 */
		private static function completeEnvironment( $exp, $alt_index ) {
			switch ( $exp->get_type() ) {
				case NelioABExperiment::PAGE_ALT_EXP:
				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::CPT_ALT_EXP:
					self::add_regular_exp( $exp, $alt_index );
					array_push( self::$ids, $exp->get_id() );
					break;
				case NelioABExperiment::HEADLINE_ALT_EXP:
					/** @var NelioABHeadlineAlternativeExperiment $exp */
					self::add_headline_exp( $exp, $alt_index );
					array_push( self::$ids, $exp->get_id() );
					break;
				case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
					/** @var NelioABHeadlineAlternativeExperiment $exp */
					self::add_wc_product_summary_exp( $exp, $alt_index );
					array_push( self::$ids, $exp->get_id() );
					break;
			}
		}


		/**
		 * Returns whether the current environment is fully loaded or not.
		 *
		 * The environment is fully loaded if `self::do_late_load` has been
		 * executed.
		 *
		 * @return boolean whether the current environment is fully loaded or not.
		 *
		 * @see self::do_late_load
		 *
		 * @since 4.0.0
		 */
		public static function is_fully_loaded() {
			return self::$is_fully_loaded;
		}


		/**
		 * Returns a list with the experiment IDs for which this visitor has an alternative.
		 *
		 * @return array a list with the experiment IDs for which this visitor has an alternative.
		 *
		 * @since 4.0.0
		 */
		public static function get_experiment_ids_in_request() {
			return self::$ids;
		}


		/**
		 * Adds a non-global, non-headline Alternative Experiment in self::$exps.
		 *
		 * @param NelioABPostAlternativeExperiment $exp The Alternative Experiment to be added.
		 * @param int $alt_index The index of the alternative that this visitor is supposed to see.
		 *            If 0, the original version is added; if 1, the first
		 *            alternative; if 2, the second; and so on.
		 *
		 * @since 4.0.0
		 */
		private static function add_regular_exp( $exp, $alt_index ) {
			$alt = $exp->get_originals_id();
			if ( $alt_index > 0 ) {
				--$alt_index;
				$alts = $exp->get_alternatives();
				if ( $alt_index < count( $alts ) ) {
					/** @var NelioABAlternative $aux */
					$aux = $alts[$alt_index];
					$alt = $aux->get_value();
				}
			}
			if ( $alt )
				self::$exps[$exp->get_originals_id()] = $alt;
		}


		/**
		 * Returns the alternative post ID (if any) this visitor is supposed to see.
		 *
		 * @param int $post_id A post ID for which we have to return the alternative.
		 *
		 * @return int the alternative post ID (if any) this visitor is supposed to see.
		 *             If `$post_id` is under test, the function returns the post ID
		 *             of the alternative the visitor is supposed to see. Otherwise,
		 *             `$post_id` is returned.
		 *
		 * @since 1.2.0
		 */
		public static function get_alternative_for_post_alt_exp( $post_id ) {
			if ( isset( self::$exps[$post_id] ) )
				return self::$exps[$post_id];
			else
				return $post_id;
		}


		/**
		 * Adds a headline alternative experiment in self::$headline_exps.
		 *
		 * @param NelioABHeadlineAlternativeExperiment $exp The Headline Alternative Experiment to be added.
		 * @param int $alt_index The index of the alternative that this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 */
		private static function add_headline_exp( $exp, $alt_index ) {
			$alt = $exp->get_original();
			if ( $alt_index > 0 ) {
				--$alt_index;
				$alts = $exp->get_alternatives();
				if ( $alt_index < count( $alts ) )
					$alt = $alts[$alt_index];
			}
			self::$headline_exps[$exp->get_originals_id()] = array(
				'exp' => $exp, 'alt' => $alt );
		}


		/**
		 * Adds a woocommerce product summary alternative experiment in self::$wc_product_summary_exps.
		 *
		 * @param NelioABProductSummaryAlternativeExperiment $exp The Headline Alternative Experiment to be added.
		 * @param int $alt_index The index of the alternative that this visitor is supposed to see.
		 *
		 * @since 4.0.0
		 */
		private static function add_wc_product_summary_exp( $exp, $alt_index ) {
			$alt = $exp->get_original();
			if ( $alt_index > 0 ) {
				--$alt_index;
				$alts = $exp->get_alternatives();
				if ( $alt_index < count( $alts ) )
					$alt = $alts[$alt_index];
			}
			self::$wc_product_summary_exps[$exp->get_originals_id()] = array(
				'exp' => $exp, 'alt' => $alt );
		}


		/**
		 * Returns the headline alternative (if any) this visitor is supposed to see.
		 *
		 * @param int $post_id A post ID for which we have to return the headline alternative.
		 *
		 * @return boolean|NelioABAlternative the headline alternative (if any) this visitor is supposed to see.
		 *                 If `$post_id` is under test and it's relevant for this
		 *                 visitor, it returns the alternative object she's
		 *                 supposed to see. False otherwise.
		 *
		 * @since 4.0.0
		 */
		public static function get_alternative_for_headline_alt_exp( $post_id ) {
			if ( isset( self::$headline_exps[$post_id] ) )
				return self::$headline_exps[$post_id];
			else
				return false;
		}


		/**
		 * Returns the woocommerce product summary alternative (if any) this visitor is supposed to see.
		 *
		 * @param int $post_id A post ID for which we have to return the woocommerce product summary alternative.
		 *
		 * @return boolean|NelioABAlternative the woocommerce product summary alternative (if any) this visitor is supposed to see.
		 *                 If `$post_id` is under test and it's relevant for this
		 *                 visitor, it returns the alternative object she's
		 *                 supposed to see. False otherwise.
		 *
		 * @since 4.0.0
		 */
		public static function get_alternative_for_wc_product_summary_alt_exp( $post_id ) {
			if ( isset( self::$wc_product_summary_exps[$post_id] ) )
				return self::$wc_product_summary_exps[$post_id];
			else
				return false;
		}


		/**
		 * Adds a Global Alternative Experiment in the appropriate property.
		 *
		 * @param NelioABGlobalAlternativeExperiment $exp The Alternative Experiment to be added.
		 * @param int $alt_index The index of the alternative that this visitor is supposed to see.
		 *            If 0, the original version is added; if 1, the first
		 *            alternative; if 2, the second; and so on.
		 *
		 * @since 4.0.0
		 */
		private static function add_global_exp( $exp, $alt_index ) {
			$alt = $exp->get_original();
			if ( $alt_index > 0 ) {
				--$alt_index;
				$alts = $exp->get_alternatives();
				if ( $alt_index < count( $alts ) )
					$alt = $alts[$alt_index];
			}
			switch ( $exp->get_type() ) {
				case NelioABExperiment::CSS_ALT_EXP:
					self::$css_info = array( 'exp' => $exp, 'alt' => $alt );
					break;
				case NelioABExperiment::MENU_ALT_EXP:
					self::$menu_info = array( 'exp' => $exp, 'alt' => $alt );
					break;
				case NelioABExperiment::THEME_ALT_EXP:
					self::$theme_info = array( 'exp' => $exp, 'alt' => $alt );
					break;
				case NelioABExperiment::WIDGET_ALT_EXP:
					self::$widget_info = array( 'exp' => $exp, 'alt' => $alt );
					break;
			}
		}


		/**
		 * Returns the global alternative (if any) this visitor is supposed to see.
		 *
		 * @param int $type The global type of experiment for which we want to retrieve the assigned alternative.
		 *
		 * @return boolean|NelioABAlternative the global alternative (if any) this visitor is supposed to see.
		 *                 If there's an experiment of type `$type` running and
		 *                 it's relevant to this visitor, it returns the
		 *                 alternative object this visitor is supposed to see.
		 *                 False otherwise.
		 *
		 * @since 4.0.0
		 */
		public static function get_alternative_for_global_alt_exp( $type ) {
			switch ( $type ) {
				case NelioABExperiment::CSS_ALT_EXP:
					if ( self::$css_info )
						return self::$css_info['alt'];
					break;
				case NelioABExperiment::MENU_ALT_EXP:
					if ( self::$menu_info )
						return self::$menu_info['alt'];
					break;
				case NelioABExperiment::THEME_ALT_EXP:
					if ( self::$theme_info )
						return self::$theme_info['alt'];
					break;
				case NelioABExperiment::WIDGET_ALT_EXP:
					if ( self::$widget_info )
						return self::$widget_info['alt'];
					break;
			}
			return false;
		}


		/**
		 * Returns the global menu alternative (if any) this visitor is supposed to see.
		 *
		 * @param int $menu_id The ID of the menu for which we want to load an alternative.
		 *
		 * @return boolean|NelioABAlternative the global menu alternative (if any) this visitor is supposed to see.
		 *                 If there's a menu experiment running that's testing the
		 *                 menu `$menu_id`, it returns the appropriate
		 *                 `NelioABAlternative`. Otherwise, it reutrns false.
		 *
		 * @since 4.0.0
		 */
		public static function get_alternative_for_menu_alt_exp( $menu_id ) {
			if ( !self::$menu_info )
				return false;
			/** @var NelioABMenuAlternativeExperiment $aux */
			$aux = self::$menu_info['exp'];
			/** @var NelioABAlternative $original */
			$original = $aux->get_original();
			if ( $original->get_value() != $menu_id )
				return false;
			return self::$menu_info['alt'];
		}


		/**
		 * Returns the theme this visitor has assigned.
		 *
		 * If there's a Theme experiment running that is relevant to this visitor,
		 * it returns the alternative theme this visitor is supposed to use. If
		 * there are no theme experiments running, the currently active theme is
		 * returned instead.
		 *
		 * @return WP_Theme the theme this visitor has assigned.
		 *
		 * @since 1.2.0
		 */
		public static function get_assigned_theme() {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			$alt = self::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
			if ( $alt ) {
				$themes = wp_get_themes();
				foreach ( $themes as $theme )
					if ( $theme['Stylesheet'] == $alt->get_value() )
						return $theme;
			}
			return wp_get_theme();
		}

	}//NelioABVisitor

}

