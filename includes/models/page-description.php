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


if( !class_exists( 'NelioABPageDescription' ) ) {

	class NelioABPageDescription {

		private $reference;
		private $title;
		private $indirect;
		private $internal;

		/**
		 * Constructor of this class. By default, a Page does not accept
		 * indirect navigations, is internal to WordPress, and its ID is -1.
		 * 
		 * @param reference the POST_ID or the PAGE_URL that uniquely
		 *        identifies a page.
		 * @param internal (default=true) whether this page is internal to
		 *        WordPress or external
		 */
		public function __construct( $reference, $internal = true ) {
			$this->reference = $reference;
			$this->title     = __( 'Undefined', 'neliob' );
			$this->indirect  = false;
			$this->internal  = $internal;
		}

		/**
		 * @returns this page reference
		 */
		public function get_reference() {
			return $this->reference;
		}

		/**
		 * Returns the title (or name) of this page
		 *
		 * @return the title (or name) of this page
		 */
		public function get_title() {
			return $this->title;
		}

		/**
		 * Sets the title (or name) of this page to $title
		 *
		 * @param title the new title of this page
		 */
		public function set_title( $title ) {
			$this->title = $title;
		}

		/**
		 * Returns whether this page is to be counted as a conversion
		 * when not accessed directly from the experiment that is being
		 * tested
		 * 
		 * @return whether this page is to be counted as a conversion
		 *         when not accessed directly from the experiment that
		 *         is being tested
		 */
		public function accepts_indirect_navigations() {
			return $this->indirect;
		}

		/**
		 * This function is used to enable or disable whether an
		 * indirect access to this page has to be counted as a conversion
		 * or not
		 *
		 * @param indirect if false, then indirect navigations are not counted
		 *        as conversions. Otherwise, indirect navigations are counted
		 *        as conversions.
		 */
		public function set_indirect_navigations_enabled( $indirect = true ) {
			$this->indirect = $indirect;
		}

		/**
		 * Returns whether this page is internal to WordPress or not
		 *
		 * @return whether this page is internal to WordPress or not
		 */
		public function is_internal() {
			return $this->internal;
		}

		/**
		 * Returns whether this page is external to WordPress or not
		 *
		 * @return whether this page is external to WordPress or not
		 */
		public function is_external() {
			return !$this->internal;
		}

		/**
		 * Returns an array of values, ready to be JSON-codified and
		 * prepared for AppEngine
		 *
		 * @return the JSON array for AppEngine
		 */
		public function encode_for_appengine() {
			$page = array(
				'reference' => $this->get_reference(),
				'title'     => $this->get_title(),
				'indirect'  => $this->accepts_indirect_navigations(),
				'internal'  => $this->is_internal(),
			);
			return $page;
		}

		/**
		 * Creates a Page Description using the information obtained from
		 * the JSON parameter.
		 *
		 * @param json the JSON array from AppEngine
		 *
		 * @return a Page Description with the values obtained from the
		 *         json
		 */
		public static function decode_from_appengine( $json ) {
			$internal = isset( $json->internal ) && $json->internal;
			$indirect = isset( $json->indirect ) && $json->indirect;
			$ref      = $json->reference;
			$title    = __( 'Undefined', 'nelioab' );
			if ( isset( $json->title ) )
				$title = $json->title;

			$page = new NelioABPageDescription( $ref, $internal );
			$page->set_title( $title );
			$page->set_indirect_navigations_enabled( $indirect );

			return $page;
		}

	}//NelioABPageDescription

}

