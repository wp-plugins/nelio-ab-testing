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


if ( !class_exists( 'NelioABPageAccessedAction' ) ) {

	/**
	 * Class representing a "page accessed" conversion action.
	 *
	 * @package \NelioABTesting\Models\Goals\Actions
	 * @since PHPDOC
	 */
	class NelioABPageAccessedAction extends NelioABAction {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $reference;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $title;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $indirect;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $internal;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param int     $reference the `post_id` or a page URL that uniquely identifies a page.
		 * @param boolean $internal  whether this page is internal to WordPress or external.
		 *                           Default: `true`.
		 *
		 * @return NelioABPageAccessedAction a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $reference, $internal = true ) {
			if ( !$internal )
				parent::__construct( NelioABAction::EXTERNAL_PAGE_ACCESSED );
			else
				parent::__construct( NelioABAction::POST_ACCESSED );
			$this->reference = $reference;
			$this->title     = __( 'Undefined', 'nelioab' );
			$this->indirect  = false;
			$this->internal  = $internal;

			// Fixing type appropriately
			if ( $internal ) {
				$p = get_post( $this->get_reference(), ARRAY_A );
				if ( $p ) {
					if ( $p['post_type'] == 'page' )
						$this->type = NelioABAction::PAGE_ACCESSED;
					else
						$this->type = NelioABAction::POST_ACCESSED;
				}
			}
		}


		/**
		 * Returns this page reference.
		 *
		 * @return string this page reference
		 *
		 * @since PHPDOC
		 */
		public function get_reference() {
			return $this->reference;
		}


		/**
		 * Returns a RegEx string to match an external page or false if internal.
		 *
		 * If the page is external, it returns its reference as a JavaScript
		 * RegEx string, ready to be used as a parameter for the RegExp class
		 * constructor. Otherwise, it returns false.
		 *
		 * @return boolean|string a RegEx string to match an external page or false if internal.
		 *
		 * @since
		 */
		public function get_regex_reference4js() {
			if ( !$this->is_external() )
				return false;
			$url = $this->get_reference();
			$url = str_replace( '"', '', $url );

			// Remove trailing slash
			$url = preg_replace( '/\/+(\*\*\*)?$/', '\1', $url );
			// Remove https
			$url = preg_replace( '/^https?:\/\//', 'http://', $url );

			// Escaping all RegEx chars: \ ^ $ * + ? . ( ) | { } [ ]
			$url = preg_replace( '/([\\\^\$\*\+\?\.\(\)\|\{\}\[\]\/])/', '\\\\$1', $url );

			// Considering starts-with and end-with *** chars
			$uses_starts_with = strpos( $url, '\\*\\*\\*', 1 ) !== false;
			$uses_ends_with = strpos( $url, '\\*\\*\\*' ) === 0;
			$url = str_replace( '\\*\\*\\*', '', $url );
			if ( !$uses_starts_with && !$uses_ends_with )
				$url = '^' . $url . '$';
			if ( $uses_starts_with && !$uses_ends_with )
				$url = '^' . $url;
			if ( !$uses_starts_with && $uses_ends_with )
				$url = $url . '$';

			return $url;
		}


		/**
		 * If the page is external, it returns a clean version of the URL. Otherwise, it returns false.
		 *
		 * If the page is external, it returns a clean version of the URL, which
		 * does not include the "metachar sequence" ***. Otherwise, it returns
		 * false.
		 *
		 * @return boolean|string if the page is external, it returns a clean version of the URL. Otherwise, it returns false.
		 *
		 * @since PHPDOC
		 */
		public function get_clean_reference() {
			if ( !$this->is_external() )
				return false;
			return str_replace( '***', '', $this->get_reference() );
		}


		/**
		 * If the page is external, it returns the regex matching mode for its URL.
		 *
		 * If the page is external, it returns the regex matching mode that has to
		 * be used for its URL. Otherwise, it returns false.
		 *
		 * @return boolean|string if the page is external, it returns the regex matching mode for its URL.
		 *
		 * @since PHPDOC
		 */
		public function get_regex_mode() {
			if ( !$this->is_external() )
				return false;
			$url = $this->get_reference();
			$uses_starts_with = strpos( $url, '***', 1 ) !== false;
			$uses_ends_with = strpos( $url, '***' ) === 0;
			$url_mode = 'exact';
			if ( $uses_starts_with )
				$url_mode = 'starts-with';
			if ( $uses_ends_with )
				$url_mode = 'ends-with';
			if ( $uses_starts_with && $uses_ends_with )
				$url_mode = 'contains';
			return $url_mode;
		}


		/**
		 * Returns the title (or name) of this page
		 *
		 * @return string the title (or name) of this page
		 *
		 * @since PHPDOC
		 */
		public function get_title() {
			return $this->title;
		}


		/**
		 * Sets the title (or name) of this page to $title.
		 *
		 * @param string $title the new title of this page
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_title( $title ) {
			$this->title = $title;
		}


		/**
		 * Returns whether accessing this page is a conversion from any referrer or only if the referrer is the tested element.
		 *
		 * @return boolean whether accessing this page is a conversion from any referrer or only if the referrer is the tested element.
		 *
		 * @since PHPDOC
		 */
		public function accepts_indirect_navigations() {
			return $this->indirect;
		}


		/**
		 * Enables or disables whether conversions should be counted when the page is accessed from any referrer or only if the referrer is the tested element.
		 *
		 * @param boolean $indirect It specifies whether conversion should be counted with any referrer.
		 *                If true, conversions are counted whichever the
		 *                referrer of accessing this page is. If false,
		 *                conversions only occur if the referrer is the
		 *                tested page.
		 *
		 * @since PHPDOC
		 */
		public function set_indirect_navigations_enabled( $indirect = true ) {
			$this->indirect = $indirect;
		}


		/**
		 * Returns whether this page is internal to WordPress or not.
		 *
		 * @return boolean whether this page is internal to WordPress or not.
		 *
		 * @since PHPDOC
		 */
		public function is_internal() {
			return $this->internal;
		}


		/**
		 * Returns whether this page is external to WordPress or not.
		 *
		 * @return boolean whether this page is external to WordPress or not.
		 *
		 * @since PHPDOC
		 */
		public function is_external() {
			return !$this->internal;
		}


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
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action returned by AppEngine.
		 *
		 * @return NelioABPageAccessedAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function decode_from_appengine( $json ) {
			$internal = isset( $json->internal ) && $json->internal;
			$indirect = isset( $json->indirect ) && $json->indirect;
			$ref      = $json->reference;
			$title    = __( 'Undefined', 'nelioab' );
			if ( isset( $json->title ) )
				$title = $json->title;

			$action = new NelioABPageAccessedAction( $ref, $internal );
			$action->set_title( $title );
			$action->set_indirect_navigations_enabled( $indirect );

			if ( isset( $json->key->id ) )
				$action->set_id( $json->key->id );

			return $action;
		}


		// @Implements
		public function json4js() {
			$action = array(
					'is_indirect' => $this->accepts_indirect_navigations(),
				);
			if ( $this->is_internal() ) {
				$p = get_post( $this->get_reference(), ARRAY_A );
				if ( $p ) {
					if ( $p['post_type'] == 'page' )
						$action['type'] = NelioABAction::PAGE_ACCESSED;
					else
						$action['type'] = NelioABAction::POST_ACCESSED;
				}
				else {
					// Referenced page or post was not found
					return false;
				}
				$action['value'] = $this->get_reference();
			}
			else {
				$action['type'] = NelioABAction::EXTERNAL_PAGE_ACCESSED;
				$action['name'] = $this->get_title();
				$action['url'] = $this->get_clean_reference();
				$action['url_mode'] = $this->get_regex_mode();
			}

			return $action;
		}


		/**
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action as used in the admin pages of our plugin.
		 *
		 * @return NelioABPageAccessedAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
		 */
		public static function build_action_using_json4js( $json ) {
			if ( $json->type == NelioABAction::EXTERNAL_PAGE_ACCESSED ) {
				$value = $json->url;
				switch ( $json->url_mode ) {
				case 'exact':
					break;
				case 'starts-with':
					$value = $value . '***';
					break;
				case 'ends-with':
					$value = '***' . $value;
					break;
				case 'contains':
					$value = '***' . $value . '***';
					break;
				}
				$internal = false;
			}
			else {
				$value = $json->value;
				$internal = true;
			}

			$action = new NelioABPageAccessedAction( $value, $internal );
			if ( isset( $json->name ) )
				$action->set_title( $json->name );
			if ( isset( $json->is_indirect ) && $json->is_indirect )
				$action->set_indirect_navigations_enabled();

			return $action;
		}

	}//NelioABPageAccessedAction

}

