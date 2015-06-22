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

if ( !class_exists( 'NelioABMemberAccessSupport' ) ) {

	/**
	 * This class adds support for the member-access plugin.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Compatibility
	 */
	abstract class NelioABMemberAccessSupport {

		/**
		 * This function checks whether the member-access plugin is active or not.
		 *
		 * @return boolean whether the member-access plugin is active or not.
		 *
		 * @since PHPDOC
		 */
		public static function is_plugin_active() {
			$plugin = 'member-access/member_access.php';
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
		}


		/**
		 * This function unhooks the redirections added by MemberAccess.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function unhook_redirections() {
			/** @noinspection PhpUndefinedClassInspection */
			$aux = MemberAccess::instance();
			remove_filter( 'the_posts', array( &$aux, 'filterPosts' ) );
		}

	}//NelioABMemberAccessSupport

}

