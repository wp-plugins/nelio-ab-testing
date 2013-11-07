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


if( !class_exists( 'NelioABAlternative' ) ) {

	class NelioABAlternative {
		private $id;
		private $name;
		private $value;
		private $was_removed;
		private $is_dirty;

		public function __construct( $id = -1 ) {
			$this->id          = $id;
			$this->name        = '';
			$this->value       = -1;
			$this->was_removed = false;
			$this->is_dirty    = false;
		}

		public function set_id( $id ) {
			$this->id = $id;
		}

		public function get_id() {
			return $this->id;
		}

		public function set_name( $name ) {
			$this->name = $name;
		}

		public function get_name() {
			return $this->name;
		}

		public function set_value( $value ) {
			$this->value = $value;
		}

		public function get_value() {
			return $this->value;
		}

		public function mark_as_removed() {
			$this->was_removed = true;
		}

		public function was_removed() {
			return $this->was_removed;
		}

		public function mark_as_dirty() {
			$this->is_dirty = true;
		}

		public function is_dirty() {
			return $this->is_dirty;
		}

		public function json() {
			return array(
				'id'            => $this->id,
				'name'          => $this->name,
				'post_id'       => $this->value,
				'was_removed'   => $this->was_removed,
				'is_dirty'      => $this->is_dirty,
			);
		}

		public function load_json( $json ) {
			$this->name          = $json->name;
			$this->value         = $json->post_id;
			$this->id            = $json->id;
			$this->was_removed   = $json->was_removed;
			$this->is_dirty      = $json->is_dirty;
		}

	}//NelioABAlternative

}

?>
