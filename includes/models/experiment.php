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


/**
 * Nelio AB Experiment model
 *
 * @package Nelio AB Testing
 * @subpackage Experiment
 * @since 0.1
 */
if( !class_exists( 'NelioABExperiment' ) ) {

	/**
	 * the model for foo plugin
	 *
	 * @package Nelio AB Testing
	 * @subpackage Experiment
	 * @since 0.1
	 */
	abstract class NelioABExperiment {

		const UNKNOWN_TYPE   = -1;
		const NO_TYPE_SET    =  0;
		const POST_ALT_EXP   =  1;
		const PAGE_ALT_EXP   =  2;
		const CSS_ALT_EXP    =  3;
		const THEME_ALT_EXP  =  4;
		const WIDGET_ALT_EXP =  5;
		const MENU_ALT_EXP   =  6;

		// Used for returning from editing a post/page content
		const PAGE_OR_POST_ALT_EXP  =  5;

		const TITLE_ALT_EXP = 6;
		const HEATMAP_EXP =  7;

		const UNKNOWN_TYPE_STR  = 'UnknownExperiment';
		// NO_TYPE_SET_STR; Does not make sense
		const POST_ALT_EXP_STR  = 'PostAlternativeExperiment';
		const PAGE_ALT_EXP_STR  = 'PageAlternativeExperiment';
		const CSS_ALT_EXP_STR   = 'CssGlobalAlternativeExperiment';
		const THEME_ALT_EXP_STR = 'ThemeGlobalAlternativeExperiment';
		// PAGE_OR_POST_ALT_EXP_STR; Does not make sense
		const TITLE_ALT_EXP_STR = 'TitleAlternativeExperiment';
		const HEATMAP_EXP_STR   = 'HeatmapExperiment';

		protected $id;
		protected $goals;
		private $name;
		private $descr;
		private $status;
		private $creation_date;
		private $type;

		public function __construct() {
			$this->clear();
		}

		public function get_type() {
			return $this->type;
		}

		public function set_type( $type ) {
			$this->type = $type;
		}

		public function get_goals() {
			if ( is_array( $this->goals ) )
				return $this->goals;
			else
				return array();
		}

		public function add_goal( $goal ) {
			array_push( $this->goals, $goal );
		}

		public function set_type_using_text( $kind ) {
			switch( $kind ) {
				case NelioABExperiment::POST_ALT_EXP_STR:
					$this->set_type( NelioABExperiment::POST_ALT_EXP );
					break;
				case NelioABExperiment::PAGE_ALT_EXP_STR:
					$this->set_type( NelioABExperiment::PAGE_ALT_EXP );
					break;
				case NelioABExperiment::TITLE_ALT_EXP_STR:
					$this->set_type( NelioABExperiment::TITLE_ALT_EXP );
					break;
				case NelioABExperiment::CSS_ALT_EXP_STR:
					$this->set_type( NelioABExperiment::CSS_ALT_EXP );
					break;
				case NelioABExperiment::THEME_ALT_EXP_STR:
					$this->set_type( NelioABExperiment::THEME_ALT_EXP );
					break;
				case NelioABExperiment::HEATMAP_EXP_STR:
					$this->set_type( NelioABExperiment::HEATMAP_EXP );
					break;
				default:
					// This should never happen...
					$this->set_type( NelioABExperiment::UNKNOWN_TYPE );
					break;
			}
		}

		protected function get_textual_type() {
			switch( $this->type ) {
				case NelioABExperiment::POST_ALT_EXP:
					return NelioABExperiment::POST_ALT_EXP_STR;
				case NelioABExperiment::PAGE_ALT_EXP:
					return NelioABExperiment::PAGE_ALT_EXP_STR;
				case NelioABExperiment::TITLE_ALT_EXP:
					return NelioABExperiment::TITLE_ALT_EXP_STR;
				case NelioABExperiment::CSS_ALT_EXP:
					return NelioABExperiment::CSS_ALT_EXP_STR;
				case NelioABExperiment::THEME_ALT_EXP:
					return NelioABExperiment::THEME_ALT_EXP_STR;
				case NelioABExperiment::HEATMAP_EXP:
					return NelioABExperiment::HEATMAP_EXP_STR;
				default:
					// This should not happen...
					return NelioABExperiment::UNKNOWN_TYPE_STR;
			}
		}

		public function get_id() {
			return $this->id;
		}

		public function get_name() {
			return $this->name;
		}

		public function set_name( $name ) {
			$this->name = $name;
		}

		public function get_description() {
			return $this->descr;
		}

		public function set_description( $descr ) {
			$this->descr = $descr;
		}

		public function get_status() {
			return $this->status;
		}

		public function set_status( $status ) {
			$this->status = $status;
		}

		public function get_creation_date() {
			return $this->creation_date;
		}

		public function set_creation_date( $creation_date ) {
			$this->creation_date = $creation_date;
		}

		public function clear() {
			$this->id       = -1;
			$this->name     = '';
			$this->descr    = '';
			$this->status   = NelioABExperimentStatus::DRAFT;
			$this->type     = NelioABExperiment::NO_TYPE_SET;
			$this->goals    = array();
		}

		public function get_url_for_making_goal_persistent( $goal ) {
			$exp_url_fragment = $this->get_exp_kind_url_fragment();
			switch ( $goal->get_kind() ) {
				case NelioABGoal::PAGE_ACCESSED_GOAL:
				default:
				$type = 'page-accessed';
			}
			if ( $goal->get_id() == -1 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/exp/%1$s/%2$s/goal/%3$s',
					$exp_url_fragment, $this->get_id(), $type
				);
			}
			else {
				if ( $goal->has_to_be_deleted() )
					$action = 'delete';
				else
					$action = 'update';
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/goal/%2$s/%1$s/%3$s',
					$goal->get_id(), $type, $action
				);
			}
			return $url;
		}

		public function make_goals_persistent() {
			require_once( NELIOAB_MODELS_DIR . '/goals/goals-manager.php' );
			$remaining_goals = array();
			foreach ( $this->get_goals() as $goal ) {
				$url = $this->get_url_for_making_goal_persistent( $goal );
				if ( $goal->has_to_be_deleted() ) {
					if ( $goal->get_id() != -1 )
						$result = NelioABBackend::remote_post( $url );
				}
				else {
					array_push( $remaining_goals, $goal );
					$encoded = NelioABGoalsManager::encode_goal_for_appengine( $goal );
					$result = NelioABBackend::remote_post( $url, $encoded );
				}
			}
			$this->goals = $remaining_goals;
		}

		public abstract function get_exp_kind_url_fragment();
		public abstract function save();
		public abstract function remove();

		public abstract function start();
		public abstract function stop();

		public static function cmp_obj( $a, $b ) {
			return strcmp( $a->get_name(), $b->get_name() );
		}

	}//NelioABExperiment
}

if ( !class_exists( 'NelioABExperimentStatus' ) ) {

	class NelioABExperimentStatus {
		const DRAFT    = 1;
		const PAUSED   = 2;
		const READY    = 3;
		const RUNNING  = 4;
		const FINISHED = 5;
		const TRASH    = 6;

		public static function to_string( $status ) {
			switch ( $status ) {
				case NelioABExperimentStatus::DRAFT:
					return __( 'Draft', 'nelioab' );
				case NelioABExperimentStatus::PAUSED:
					return __( 'Paused', 'nelioab' );
				case NelioABExperimentStatus::READY:
					return __( 'Prepared', 'nelioab' );
				case NelioABExperimentStatus::FINISHED:
					return __( 'Finished', 'nelioab' );
				case NelioABExperimentStatus::RUNNING:
					return __( 'Running', 'nelioab' );
				case NelioABExperimentStatus::TRASH:
					return __( 'Trash' );
				default:
					return __( 'Unknown Status', 'nelioab' );
			}
		}

	}

}

