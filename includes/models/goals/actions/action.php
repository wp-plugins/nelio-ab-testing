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


if( !class_exists( 'NelioABAction' ) ) {

	abstract class NelioABAction {

		// PAGE ACCESSED ACTIONS
		const PAGE_ACCESSED          = 'page';
		const POST_ACCESSED          = 'post';
		const EXTERNAL_PAGE_ACCESSED = 'external-page';

		// FORM ACTIONS
		const FORM_SUBMIT         = 'form-submit';
		const SUBMIT_CF7_FORM     = 'cf7-submit';
		const SUBMIT_GRAVITY_FORM = 'gravity-form-submit';

		protected $type;

		public function __construct( $type ) {
			$this->type = $type;
		}

		public function get_type() {
			return $this->type;
		}

		/**
		 *
		 */
		public abstract function json4js();

		/**
		 *
		 */
		public static function build_action_using_json4js( $json ) {
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/page-accessed-action.php' );
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/form-submission-action.php' );
			switch( $json->type ) {
				case self::PAGE_ACCESSED:
				case self::POST_ACCESSED:
				case self::EXTERNAL_PAGE_ACCESSED:
					return NelioABPageAccessedAction::build_action_using_json4js( $json );
				case self::FORM_SUBMIT:
					return NelioABFormSubmissionAction::build_action_using_json4js( $json );
				default:
					return false;
			}
		}

	}//NelioABAction

}

