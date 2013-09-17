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


if ( !class_exists( 'NelioABWpHelper' ) ) {

	abstract class NelioABWpHelper {

		/**
		 *
		 */
		public static function override( $ori_id, $overriding_id ) {
			$ori = get_post( $ori_id, ARRAY_A );
			if ( !$ori )
				return;
			$overriding = get_post( $overriding_id, ARRAY_A );
			if ( !$overriding )
				return;

			$ori['post_title']    = $overriding['post_title'];
			$ori['post_content']  = $overriding['post_content'];
			$ori['post_excerpt']  = $overriding['post_excerpt'];
			wp_update_post( $ori );

			$copy_meta = get_post_meta( $overriding_id, '_is_nelioab_metadata_duplicated', true );
			if ( $copy_meta )
				NelioABWpHelper::copy_meta_info( $overriding_id, $ori_id, false );

		}

		/**
		 * Copy all custom fields (post meta) from one object to another
		 */
		public static function copy_meta_info( $src_id, $dest_id, $copy_hidden = true ) {
			$custom_fields = get_post_meta( $src_id );

			if ( $custom_fields ) {
				foreach ( $custom_fields as $key => $val) {

					// If the metakey is ours (i.e. nelioab), we do not duplicate it
					if ( strpos( $key, 'nelioab_' ) !== false )
						continue;

					if ( !$copy_hidden && substr( $key, 0, 1 ) == '_' )
						continue;

					if ( !is_array( $val ) ) {
						update_post_meta( $dest_id, $key, $val );
					}
					else {
						foreach ( $val as $aux ) {
							update_post_meta( $dest_id, $key, $aux );
						}
					}
				}
			}
		}

		/**
		 * Copy all terms (including categories and tags) from one object to another
		 */
		public static function copy_terms( $src_id, $dest_id ) {
			$querystr = 'SELECT term_taxonomy_id, term_order ' .
				'FROM ' . $wpdb->prefix . 'term_relationships ' .
				'WHERE object_id=' . $src_id;

			$the_terms = $wpdb->get_results( $querystr );
			
			foreach( $the_terms as $term ) {
				$wpdb->insert(
					$wpdb->prefix . "term_relationships",
					array(
						'object_id'        => $dest_id,
						'term_taxonomy_id' => $term->term_taxonomy_id,
						'term_order'       => $term->term_order,
					),
					array(
						'%d',
						'%d',
						'%d',
					)
				);
			}
		}

		/**
		 * Copy all comments from one post to another
		 */ 
		public static function copy_comments( $src_id, $dest_id ) {
			foreach ( get_comments( array( 'post_id' => $src_id ) ) as $comment ) {
				$comment->comment_post_ID = $dest_id;
				wp_insert_comment( (array) $comment );
			}
		}

	}//NelioABWpHelper

}
?>
