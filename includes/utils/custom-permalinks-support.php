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

if ( !class_exists( 'NelioABCustomPermalinksSupport' ) ) {

	/**
	 * This class adds support for the custom-permalinks plugin.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Compatibility
	 */
	abstract class NelioABCustomPermalinksSupport {

		/**
		 * This function checks whether the custom-permalinks plugin is active or not.
		 *
		 * @return boolean whether the custom-permalinks plugin is active or not.
		 *
		 * @since PHPDOC
		 */
		public static function is_plugin_active() {
			$plugin = 'custom-permalinks/custom-permalinks.php';
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
		}


		/**
		 * This function returns the POST_ID of the post associated to the given URL.
		 *
		 * We assume that this URL is using a custom permalink (from the
		 * custom-permalinks plugin). If it isn't, the function returns `false`.
		 *
		 * @param string $url A URL.
		 *
		 * @return int the POST_ID of the post associated to the given URL.
		 *
		 * @since PHPDOC
		 */
		public static function url_to_postid( $url ) {
			/** @var wpdb $wpdb */
			global $wpdb;
			$the_id = false;
			$custom_permalink = ltrim( str_replace( home_url(), '', $url ), '/' );
			if ( strlen( $custom_permalink ) ) {
				$query = "" .
					" SELECT $wpdb->postmeta.post_id FROM $wpdb->postmeta WHERE " .
					" $wpdb->postmeta.meta_key = 'custom_permalink' AND " .
					" $wpdb->postmeta.meta_value = %s";
				$query = $wpdb->prepare( $query, $custom_permalink );
				$posts = $wpdb->get_results( $query );
				if ( $posts )
					$the_id = $posts[0]->post_id;
			}
			return $the_id;
		}


		/**
		 * This function returns the original permalink of the specified post.
		 *
		 * @param int $post_id a post ID.
		 *
		 * @return string the original permalink of the specified post.
		 *
		 * @since PHPDOC
		 */
		public static function get_original_permalink( $post_id ) {
			remove_filter( 'post_link', 'custom_permalinks_post_link', 10, 2 );
			remove_filter( 'post_type_link', 'custom_permalinks_post_link', 10, 2 );
			remove_filter( 'page_link', 'custom_permalinks_page_link', 10, 2 );
			remove_filter( 'user_trailingslashit', 'custom_permalinks_trailingslash', 10, 2 );
			$permalink = get_permalink( $post_id );
			add_filter( 'post_link', 'custom_permalinks_post_link', 10, 2 );
			add_filter( 'post_type_link', 'custom_permalinks_post_link', 10, 2 );
			add_filter( 'user_trailingslashit', 'custom_permalinks_trailingslash', 10, 2 );
			add_filter( 'page_link', 'custom_permalinks_page_link', 10, 2 );
			return $permalink;
		}


		/**
		 * This function should only be used when loading an alternative. It prevents custom permalinks redirects.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function prevent_template_redirect() {
			remove_action( 'template_redirect', 'custom_permalinks_redirect', 5 );
		}


		/**
		 * This function removes (if any) the custom permalink.
		 *
		 * This is important when creating an alternative based on an already
		 * existing page/post, because the original custom permalink (if any) is
		 * inherited.
		 *
		 * @param int $post_id a post ID.
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function remove_custom_permalink( $post_id ) {
			delete_post_meta( $post_id, 'custom_permalink' );
		}

	}//NelioABCustomPermalinksSupport

}

