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


if ( !class_exists( 'NelioABAction' ) ) {

	/**
	 * Abstract class representing a conversion action.
	 *
	 * In order to create an instance of this class, one must use of its
	 * concrete subclasses.
	 *
	 * @package \NelioABTesting\Models\Goals\Actions
	 * @since PHPDOC
	 */
	abstract class NelioABAction {

		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const PAGE_ACCESSED = 'page';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const POST_ACCESSED = 'post';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const EXTERNAL_PAGE_ACCESSED = 'external-page';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const FORM_SUBMIT = 'form-submit';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const SUBMIT_CF7_FORM = 'cf7-submit';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const SUBMIT_GRAVITY_FORM = 'gravity-form-submit';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const CLICK_ELEMENT = 'click-element';


		/**
		 * Constant PHPDOC
		 *
		 * @since 4.2.0
		 * @var string
		 */
		const WC_ORDER_COMPLETED = 'wc-order-completed';


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		protected $id;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		protected $type;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param string $type PHPDOC
		 *
		 * @return NelioABAction a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $type ) {
			$this->type = $type;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_id( $id ) {
			$this->id = $id;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_id() {
			return $this->id;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_type() {
			return $this->type;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public abstract function json4js();


		/**
		 * Returns an array with all the relevant information for saving this action in AppEngine.
		 *
		 * @return array all the relevant information for saving this action in AppEngine.
		 *
		 * @since 4.1.0
		 */
		public abstract function encode_for_appengine();


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * The particular type of the resulting action will depend on
		 * `$action->type`. Note this class is abstract and there are
		 * several concrete classes extending this one.
		 *
		 * @param object $json a JSON action as used in the admin pages of our plugin.
		 *
		 * @return boolean|NelioABAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 */
		public static function build_action_using_json4js( $json ) {
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/page-accessed-action.php' );
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/form-submission-action.php' );
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/click-element-action.php' );
			require_once( NELIOAB_MODELS_DIR . '/goals/actions/wc-order-completed-action.php' );
			switch( $json->type ) {
				case self::PAGE_ACCESSED:
				case self::POST_ACCESSED:
				case self::EXTERNAL_PAGE_ACCESSED:
					return NelioABPageAccessedAction::build_action_using_json4js( $json );
				case self::FORM_SUBMIT:
					return NelioABFormSubmissionAction::build_action_using_json4js( $json );
				case self::CLICK_ELEMENT:
					return NelioABClickElementAction::build_action_using_json4js( $json );
				case self::WC_ORDER_COMPLETED:
					return NelioABWooCommerceOrderCompletedAction::build_action_using_json4js( $json );
				default:
					return false;
			}
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * The particular type of the resulting action will depend on
		 * `$action->type`. Note this class is abstract and there are
		 * several concrete classes extending this one.
		 *
		 * @param object $action a JSON action returned by AppEngine.
		 *
		 * @return NelioABAction the new action containing all the information in `$action`.
		 *
		 * @since 4.1.0
		 *
		 * @abstract
		 */
		public static function decode_from_appengine( /** @noinspection PhpUnusedParameterInspection */ $action ) {
			// TODO: should this be refactored like build_action_using_json4js
			throw new RuntimeException( 'Not Implemented Method' );
		}

	}//NelioABAction

}

