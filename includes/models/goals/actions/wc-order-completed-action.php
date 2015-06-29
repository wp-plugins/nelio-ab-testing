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


if ( !class_exists( 'NelioABWooCommerceOrderCompletedAction' ) ) {

	/**
	 * Class representing a "page accessed" conversion action.
	 *
	 * @package \NelioABTesting\Models\Goals\Actions
	 * @since PHPDOC
	 */
	class NelioABWooCommerceOrderCompletedAction extends NelioABAction {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $product_id;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param int $product_id the `product_id` that identifies a product.
		 *
		 * @return NelioABWooCommerceOrderCompletedAction a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $product_id ) {
			parent::__construct( NelioABAction::WC_ORDER_COMPLETED );
			$this->product_id = $product_id;
		}


		/**
		 * Returns the product ID that has to be purchased.
		 *
		 * @return string the product ID that has to be purchased.
		 *
		 * @since PHPDOC
		 */
		public function get_product_id() {
			return $this->product_id;
		}


		// @Implements
		public function encode_for_appengine() {
			$page = array( 'product' => $this->get_product_id() );
			return $page;
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action returned by AppEngine.
		 *
		 * @return NelioABWooCommerceOrderCompletedAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function decode_from_appengine( $json ) {
			$product_id = false;
			if ( isset( $json->product ) )
				$product_id = $json->product;

			$action = new NelioABWooCommerceOrderCompletedAction( $product_id );

			if ( isset( $json->key->id ) )
				$action->set_id( $json->key->id );

			return $action;
		}


		// @Implements
		public function json4js() {
			$action = array(
				'type'  => NelioABAction::WC_ORDER_COMPLETED,
				'value' => $this->get_producT_id()
			);
			return $action;
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action as used in the admin pages of our plugin.
		 *
		 * @return NelioABWooCommerceOrderCompletedAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function build_action_using_json4js( $json ) {
			$product_id = $json->value;
			$action = new NelioABWooCommerceOrderCompletedAction( $product_id );
			return $action;
		}

	}//NelioABWooCommerceOrderCompletedAction

}

