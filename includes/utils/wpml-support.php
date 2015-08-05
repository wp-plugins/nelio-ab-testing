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

if ( ! class_exists( 'NelioABWpmlSupport' ) ) {

	/**
	 * This class adds support for the WPML plugin.
	 *
	 * @since 4.2.4
	 * @package \NelioABTesting\Compatibility
	 */
	abstract class NelioABWpmlSupport {

		/**
		 * This function returns the POST_ID of the post associated to the given URL.
		 *
		 * @since 4.2.4
		 */
		public static function hook_to_wordpress() {
			add_filter( 'icl_ls_languages', array( __CLASS__, 'fix_language_selector' ) );
		}


		/**
		 * PHPDOC
		 *
		 * @param array $languages PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since 4.2.4
		 */
		public static function fix_language_selector( $languages ) {

			// if the current post is an alternative...
			global $wp_query;
			if ( ! empty( $wp_query->queried_object_id ) ) {

				// Check if the current page is under test
				global $nelioab_controller;
				$aux = $nelioab_controller->get_controller( 'alt-exp' );
				$original_post_id = $aux->get_original_related_to( $wp_query->queried_object_id );
				if ( $original_post_id == $wp_query->queried_object_id ) {
					return $languages;
				}

				// Something with a cache
				static $_cache_icl_ls_languages;
				if ( ! is_null( $_cache_icl_ls_languages ) ) {
					return $_cache_icl_ls_languages;
				}

				// Let's get original's post selector.
				global $sitepress, $bp, $wp_query;
				$post = get_post( $original_post_id );
				if ( ! empty( $post->ID ) &&
				     method_exists( $sitepress, 'set_wp_query' ) &&
				     method_exists( $sitepress, 'get_ls_languages' ) ) {

					// Clone original $wp_query
					$_wp_query = clone $wp_query;

					// Fix query
					$wp_query->queried_object_id = $original_post_id;
					$wp_query->queried_object = $post;
					$wp_query->post = $post;
					$sitepress->set_wp_query();
					remove_filter( 'icl_ls_languages', array( __CLASS__, 'fix_language_selector' ) );

					// Re-create language switcher data
					$languages = $sitepress->get_ls_languages();
					add_filter( 'icl_ls_languages', array( __CLASS__, 'fix_language_selector' ) );

					// Restore $wp_query
					unset( $wp_query );
					global $wp_query;
					$wp_query = clone $_wp_query;
					unset( $_wp_query );
					$sitepress->set_wp_query();
				}

				// Convert links
				if ( is_array( $languages ) ) {
					foreach ( $languages as $code => &$language ) {
						$translated_post_id = icl_object_id( $post->ID, 'page', false, $code );
						if ( $translated_post_id ) {
							$language['url'] = untrailingslashit( get_permalink( $translated_post_id ) );
						}
					}
				}
			}

			return $languages;
		}

	}// NelioABWpmlSupport

}

