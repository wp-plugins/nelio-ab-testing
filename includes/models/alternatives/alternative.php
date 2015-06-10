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


if ( !class_exists( 'NelioABAlternative' ) ) {

	/**
	 * This class represents an alternative within an A/B experiment.
	 *
	 * @package \NelioABTesting\Models\Experiments\AB
	 * @since PHPDOC
	 */
	class NelioABAlternative {

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
		protected $name;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var mixed
		 */
		protected $value;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		protected $based_on;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		protected $was_removed;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		protected $is_dirty;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param int $id PHPDOC
		 *            Default: -1.
		 *
		 * @return NelioABAlternative a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $id = -1 ) {
			$this->id          = $id;
			$this->name        = '';
			$this->value       = -1;
			$this->was_removed = false;
			$this->is_dirty    = false;
			$this->based_on    = false;
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
		 * PHPDOC
		 *
		 * @param string $name PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_name( $name ) {
			$this->name = $name;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_name() {
			return $this->name;
		}


		/**
		 * PHPDOC
		 *
		 * @param mixed $value PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_value( $value ) {
			$this->value = $value;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return mixed PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_value() {
			return $this->value;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_identifiable_value() {
			// TODO What?
			return $this->value;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @param int $post_id PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function applies_to_post_id( $post_id ) {
			return $this->value == $post_id;
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function mark_as_removed() {
			$this->was_removed = true;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function was_removed() {
			return $this->was_removed;
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function mark_as_dirty() {
			$this->is_dirty = true;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function is_dirty() {
			return $this->is_dirty;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function is_based_on_another_element() {
			if ( $this->based_on && $this->based_on > 0 )
				return true;
			else
				return false;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $pid PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function set_base_element( $pid ) {
			$this->based_on = $pid;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_base_element() {
			return $this->based_on;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function json4js() {
			return array(
				'id'         => $this->id,
				'name'       => $this->name,
				'value'      => $this->value,
				'base'       => $this->based_on,
				'wasDeleted' => $this->was_removed,
				'isDirty'    => $this->is_dirty,
			);
		}


		/**
		 * Returns PHPDOC
		 *
		 * @param object $json_alt PHPDOC
		 *
		 * @return NelioABAlternative PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function build_alternative_using_json4js( $json_alt ) {
			$alt = new NelioABAlternative();
			$alt->id            = $json_alt->id;
			$alt->name          = $json_alt->name;
			$alt->value         = isset( $json_alt->value ) ? $json_alt->value : -1;
			$alt->based_on      = isset( $json_alt->base ) ? $json_alt->base : false;
			$alt->was_removed   = isset( $json_alt->wasDeleted ) && $json_alt->wasDeleted;
			$alt->is_dirty      = isset( $json_alt->isDirty ) && $json_alt->isDirty;
			return $alt;
		}

	}//NelioABAlternative

}

