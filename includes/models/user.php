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


if ( !class_exists( 'NelioABUser' ) ) {

	class NelioABUser {

		private static $ids = array();

		private static $css_info    = false;
		private static $menu_info   = false;
		private static $theme_info  = false;
		private static $widget_info = false;

		private static $exps = array();
		private static $headline_exps = array();

		private static $is_fully_loaded = false;


		public static function load() {
			add_action( 'setup_theme', array( 'NelioABUser', 'do_first_load' ) );
			add_action( 'the_posts',   array( 'NelioABUser', 'do_late_load' ) );
		}


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
							foreach ( $running_exps as $exp )
								if ( $exp->get_type() == NelioABExperiment::CSS_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							break;
						case 'nabm':
							foreach ( $running_exps as $exp )
								if ( $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							break;
						case 'nabt':
							foreach ( $running_exps as $exp )
								if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							break;
						case 'nabw':
							foreach ( $running_exps as $exp )
								if ( $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP )
									$exp_and_alt_list[$exp->get_id()] = $val;
							break;
					}
				}
			}
			NelioABUser::prepareEnvironment( $exp_and_alt_list );
		}


		public static function do_late_load( $posts ) {
			if ( !is_admin() && isset( $_GET['nab'] ) ) {
				/** @var NelioABController $nelioab_controller */
				global $nelioab_controller;
				$current_id = $nelioab_controller->get_queried_post_id();

				$val = $_GET['nab'];
				$exp = false;

				require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
				$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
				for ( $i = 0; $i < count( $running_exps ) && !$exp; ++$i ) {
					$aux = $running_exps[$i];
					if ( $aux->get_originals_id() == $current_id )
						$exp = $aux;
				}
				if ( $exp )
					NelioABUser::completeEnvironment( $exp, $val );
			}
			self::$is_fully_loaded = true;

			return $posts;
		}


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

			foreach ( $env as $exp_id => $alt ) {
				$exp = false;
				foreach ( $running_exps as $aux )
					if ( $aux->get_id() == $exp_id )
						$exp = $aux;
				if ( $exp ) {
					switch ( $exp->get_type() ) {
						case NelioABExperiment::PAGE_ALT_EXP:
						case NelioABExperiment::POST_ALT_EXP:
							self::add_regular_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
						case NelioABExperiment::HEADLINE_ALT_EXP:
							self::add_headline_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
						case NelioABExperiment::CSS_ALT_EXP:
						case NelioABExperiment::MENU_ALT_EXP:
						case NelioABExperiment::THEME_ALT_EXP:
						case NelioABExperiment::WIDGET_ALT_EXP:
							self::add_global_exp( $exp, $alt );
							array_push( self::$ids, $exp->get_id() );
							break;
					}
				}
			}
		}


		private static function completeEnvironment( $exp, $alt ) {
			switch ( $exp->get_type() ) {
				case NelioABExperiment::PAGE_ALT_EXP:
				case NelioABExperiment::POST_ALT_EXP:
					self::add_regular_exp( $exp, $alt );
					array_push( self::$ids, $exp->get_id() );
					break;
				case NelioABExperiment::HEADLINE_ALT_EXP:
					self::add_headline_exp( $exp, $alt );
					array_push( self::$ids, $exp->get_id() );
					break;
			}
		}


		public static function is_fully_loaded() {
			return self::$is_fully_loaded;
		}


		public static function get_experiment_ids_in_request() {
			return self::$ids;
		}


		private static function add_regular_exp( $exp, $alt_index ) {
			$alt = $exp->get_originals_id();
			if ( $alt_index > 0 ) {
				--$alt_index;
				$alts = $exp->get_alternatives();
				if ( $alt_index < count( $alts ) )
					$alt = $alts[$alt_index]->get_value();
			}
			if ( $alt )
				self::$exps[$exp->get_originals_id()] = $alt;
		}


		public static function get_alternative_for_post_alt_exp( $post_id ) {
			if ( isset( self::$exps[$post_id] ) )
				return self::$exps[$post_id];
			else
				return $post_id;
		}


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


		public static function get_alternative_for_headline_alt_exp( $post_id ) {
			if ( isset( self::$headline_exps[$post_id] ) )
				return self::$headline_exps[$post_id];
			else
				return false;
		}


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


		public static function get_alternative_for_menu_alt_exp( $menu_id ) {
			if ( !self::$menu_info )
				return false;
			if ( self::$menu_info['exp']->get_original()->get_value() != $menu_id )
				return false;
			return self::$menu_info['alt'];
		}


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

	}//NelioABUser

}

