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


if ( !class_exists( 'NelioABClickElementAction' ) ) {

	/**
	 * Class representing a "click" conversion action.
	 *
	 * @package \NelioABTesting\Models\Goals\Actions
	 * @since PHPDOC
	 */
	class NelioABClickElementAction extends NelioABAction {

		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const ID_MODE = 'id';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const CSS_MODE = 'css-path';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const TEXT_MODE = 'text-is';


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $mode;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $value;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param string $mode  PHPDOC
		 * @param string $value PHPDOC
		 *
		 * @return NelioABClickElementAction a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $mode, $value ) {
			parent::__construct( self::CLICK_ELEMENT );
			$this->mode  = $mode;
			$this->value = $value;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_mode() {
			return $this->mode;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_value() {
			return $this->value;
		}


		/**
		 * Returns whether clicking the element is tracked in any page or only in the tested page.
		 *
		 * @return boolean whether clicking the element is tracked in any page or only in the tested page.
		 *
		 * @since PHPDOC
		 */
		public function is_click_tracked_in_any_page() {
			return false;
		}


		// @Implements
		public function encode_for_appengine() {
			$action = array(
				'kind'    => $this->get_mode(),
				'value'   => $this->get_value(),
				'anyPage' => false,
			);
			return $action;
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action returned by AppEngine.
		 *
		 * @return NelioABClickElementAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function decode_from_appengine( $json ) {
			$action = new NelioABClickElementAction( $json->kind, $json->value );
			if ( isset( $json->key->id ) )
				$action->set_id( $json->key->id );
			return $action;
		}


		// @Implements
		public function json4js() {
			$action = array(
					'type'  => 'click-element',
					'mode'  => $this->get_mode(),
					'value' => $this->get_value(),
				);
			return $action;
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action as used in the admin pages of our plugin.
		 *
		 * @return NelioABClickElementAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function build_action_using_json4js( $json ) {
			$action = new NelioABClickElementAction( $json->mode, $json->value );
			return $action;
		}

	}//NelioABClickElementAction

}

