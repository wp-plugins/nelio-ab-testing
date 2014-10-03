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
		public static function override( $ori_id, $overriding_id,
				$meta = true, $categories = true, $tags = true ) {

			$ori = get_post( $ori_id, ARRAY_A );
			if ( !$ori )
				return;
			$overriding = get_post( $overriding_id, ARRAY_A );
			if ( !$overriding )
				return;

			require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
			NelioABOptimizePressSupport::make_post_compatible_with_optimizepress(
				$ori_id, $overriding_id );

			$ori['post_title']    = $overriding['post_title'];
			$ori['post_content']  = $overriding['post_content'];
			$ori['post_excerpt']  = $overriding['post_excerpt'];
			$ori['post_parent']   = $overriding['post_parent'];
			wp_update_post( $ori );

			if ( $meta )
				NelioABWpHelper::copy_meta_info( $overriding_id, $ori_id );
			NelioABWpHelper::copy_terms( $overriding_id, $ori_id, $categories, $tags );
		}

		/**
		 * Copy all custom fields (post meta) from one object to another
		 */
		public static function copy_meta_info( $src_id, $dest_id, $copy_hidden = true ) {

			// First of all, we remove all post meta from the destination
			$custom_fields = get_post_meta( $dest_id );
			if ( $custom_fields ) {
				foreach ( $custom_fields as $key => $val) {

					// If the metakey is ours (i.e. nelioab), we do not remove it
					if ( strpos( $key, 'nelioab_' ) !== false )
						continue;

					if ( !$copy_hidden && substr( $key, 0, 1 ) == '_' )
						continue;

					delete_post_meta( $dest_id, $key );
				}
			}

			// And then we transfer the new ones
			$custom_fields = get_post_meta( $src_id );
			if ( $custom_fields ) {
				foreach ( $custom_fields as $key => $val) {

					// If the metakey is ours (i.e. nelioab), we do not duplicate it
					if ( strpos( $key, 'nelioab_' ) !== false )
						continue;

					if ( !$copy_hidden && substr( $key, 0, 1 ) == '_' )
						continue;

					if ( !is_array( $val ) ) {
						if ( is_serialized( $val ) )
							$val = unserialize( $val );
						add_post_meta( $dest_id, $key, $val );
					}
					else {
						foreach ( $val as $aux ) {
							if ( is_serialized( $aux ) )
								$aux = unserialize( $aux );
							add_post_meta( $dest_id, $key, $aux );
						}
					}
				}
			}
		}

		/**
		 * Copy all terms (including categories and tags) from one object to another
		 */
		public static function copy_terms( $src_id, $dest_id, $copy_categories, $copy_tags ) {

			// First of all, we remove all terms from the destination
			wp_delete_object_term_relationships( $dest_id, get_taxonomies() );

			// And then we transfer the new ones
			if ( $copy_tags ) {
				$terms = wp_get_post_terms( $src_id );
				$aux   = array();
				if ( $terms )
					foreach ( $terms as $term )
						array_push( $aux, $term->name );
				wp_set_post_terms( $dest_id, $aux );
			}

			if ( $copy_categories ) {
				$categories = wp_get_post_categories( $src_id );
				if ( $categories )
					wp_set_post_categories( $dest_id, $categories );
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

		/**
		 * Returns whether this WordPress installation is, at least, the queried version
		 */
		public static function is_at_least_version( $version ) {
			return $version <= floatval( get_bloginfo( 'version' ) );
		}

		/**
		 * Returns site_url, but using http instead of (maybe) https.
		 */
		public static function get_unsecured_site_url() {
			$url = site_url();
			$url = preg_replace( '/^https/', 'http', $url );
			return $url;
		}

		/**
		 * This function is an AJAX callback. It returns a list of up to 20 posts (or
		 * pages). It is used by the select2 widget (an item selector that looks more
		 * beautiful than regular the "select>option" combo.
		 *
		 * Accepted POST params are:
		 *   term: {string}
		 *         the (part of the) string used to look for items.
		 *   type: {'post'|'page'|'post-or-page'}
		 *         what type of element are we looking.
		 *   default_id: {int} (optional)
		 *         if set, the item with that ID will be returned. If that item is
		 *         not found, then we'll perform a regular search (as if the param
		 *         had not been set).
		 */
		public static function search_posts() {
			$term = false;
			if ( isset( $_POST['term'] ) )
				$term = $_POST['term'];

			$type = false;
			if ( isset( $_POST['type'] ) )
				$type = $_POST['type'];

			if ( 'page-or-post' == $type )
				$type = array( 'page', 'post' );

			$default_id = false;
			if ( isset( $_POST['default_id'] ) )
				$default_id = $_POST['default_id'];
			if ( $default_id == -1 )
				$default_id = false;

			$default_thumbnail = sprintf(
				'<img src="data:image/gif;%s" class="%s" alt="%s" />',
				'base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
				'attachment-thumbnail wp-post-image nelioab-no-thumbnail',
				__( 'No featured image available', 'nelioab' )
			);

			$args = array(
				's'              => $term,
				'post_type'      => $type,
				'posts_per_page' => 20,
				'meta_key'       => '_is_nelioab_alternative',
				'meta_compare'   => 'NOT EXISTS',
				'post_status'    => 'publish',
			);

			$lp_title = __( 'Your latest posts', 'nelioab' );
			$latest_post_item = false;
			if ( isset( $_POST['include_latest_posts'] ) || $default_id !== false ) {
				if ( !$term || strpos( strtolower( $lp_title ), strtolower( $term ) ) !== false ) {
					$latest_post_item = array(
						'id'        => NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS,
						'type'      => '',
						'title'     => $lp_title,
						'status'    => '',
						'date'      => '',
						'author'    => 'WordPress',
						'thumbnail' => $default_thumbnail,
					);
				}
			}

			// If there's a default_id set, it means that the user is interested
			// in one post only; I'm going to return that post to him
			if ( $default_id !== false ) {
				$id = $default_id;
				$post = false;

				if ( $id == NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS ) {
					header( 'Content-Type: application/json' );
					echo json_encode( array( $latest_post_item ) );
					die();
				}

				if ( $id > 0 )
					$post = get_post( $id );
				if ( $post ) {
					$item = array(
						'id' => $post->ID,
						'title' => $post->post_title,
					);
					header( 'Content-Type: application/json' );
					echo json_encode( array( $item ) );
					die();
				}
			}

			$result = array();

			$my_query = new WP_Query( $args );

			if ( $my_query->have_posts() ) {
				global $post;

				while ( $my_query->have_posts() ) {
					$my_query->the_post();
					$thumbnail = get_the_post_thumbnail( $post->ID, 'thumbnail' );
					if ( $thumbnail === '' )
						$thumbnail = $default_thumbnail;

					$item = array(
						'id'        => $post->ID,
						'type'      => $post->post_type,
						'title'     => $post->post_title,
						'status'    => $post->post_status,
						'date'      => $post->post_date,
						'author'    => get_the_author(),
						'thumbnail' => $thumbnail,
					);
					array_push( $result, $item );
				}
			}

			if ( $latest_post_item )
				array_unshift( $result, $latest_post_item );

			header( 'Content-Type: application/json' );
			echo json_encode( $result );
			die();
		}

		/**
		 * This function is an AJAX callback. It returns a list of up to 20 forms.
		 * The forms can either be Gravity Forms or Contact Form 7 forms. It is
		 * used by the select2 widget (an item selector that looks more beautiful
		 * than regular the "select>option" combo.
		 *
		 * Accepted POST params are:
		 *   term: {string}
		 *         the (part of the) string used to look for items.
		 *   type: {'cf7','gf'}
		 *         we may look for Contact Forms 7 (cf7) or Gravity Forms (gf)
		 *   default_id: {int} (optional)
		 *         if set, the item with that ID will be returned. If that item is
		 *         not found, then we'll perform a regular search (as if the param
		 *         had not been set).
		 */
		public static function search_forms() {
			$term = false;
			if ( isset( $_POST['term'] ) )
				$term = $_POST['term'];

			$type = false;
			if ( isset( $_POST['type'] ) )
				$type = $_POST['type'];

			$default_id = false;
			if ( isset( $_POST['default_id'] ) )
				$default_id = $_POST['default_id'];

			$default_thumbnail = sprintf(
				'<img src="data:image/gif;%s" class="%s" alt="%s" />',
				'base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
				'attachment-thumbnail wp-post-image nelioab-no-thumbnail',
				__( 'No featured image available', 'nelioab' )
			);

			$args = array(
				's'              => $term,
				'posts_per_page' => 20,
				'orderby'        => 'date',
			);

			// If there's a default_id set, it means that the user is interested
			// in one form only; I'm going to return that form to him
			if ( $default_id !== false && $default_id > 0 ) {
				if ( 'cf7' == $type && class_exists( 'WPCF7_ContactForm' ) ) {
					$aux = WPCF7_ContactForm::find( array( 'p' => $default_id ) );
					if ( count( $aux ) > 0 ) {
						$form = $aux[0];
						$item = array(
							'id'        => $form->id(),
							'title'     => $form->title(),
							'type'      => 'Contact Form 7',
							'thumbnail' => $default_thumbnail,
						);
						header( 'Content-Type: application/json' );
						echo json_encode( array( $item ) );
						die();
					}
				}
				elseif ( 'gf' == $type && class_exists( 'GFAPI' ) ) {
					$form = GFAPI::get_form( $default_id );
					if ( $form ) {
						$item = array(
							'id'        => $form['id'],
							'title'     => $form['title'],
							'thumbnail' => $default_thumbnail,
						);
						header( 'Content-Type: application/json' );
						echo json_encode( array( $item ) );
						die();
					}
				}
			}

			$result = array();

			if ( 'cf7' == $type && class_exists( 'WPCF7_ContactForm' ) ) {
				$forms = WPCF7_ContactForm::find( $args );
				foreach ( $forms as $form ) {
					$item = array(
						'id'        => $form->id(),
						'title'     => $form->title(),
						'type'      => 'Contact Form 7',
						'thumbnail' => $default_thumbnail,
					);
					array_push( $result, $item );
				}
			}

			elseif ( 'gf' == $type && class_exists( 'RGFormsModel' ) ) {
				$forms = RGFormsModel::get_forms();
				foreach ( $forms as $form ) {
					if ( $term && strpos( strtolower( $form->title ), strtolower( $term ) ) === false )
						continue;
					$item = array(
						'id'        => $form->id,
						'title'     => $form->title,
						'type'      => 'Gravity Form',
						'thumbnail' => $default_thumbnail,
					);
					array_push( $result, $item );
				}
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $result );
			die();
		}

		public static function sort_post_search_by_title( $i1, $i2 ) {
			return strcasecmp( $i1['title'], $i2['title'] );
		}

	}//NelioABWpHelper

}

