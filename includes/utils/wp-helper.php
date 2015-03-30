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

if ( !class_exists( 'NelioABWpHelper' ) ) {

	abstract class NelioABWpHelper {

		/**
		 *
		 */
		public static function overwrite( $ori_id, $overwriting_id,
				$meta = true, $categories = true, $tags = true ) {

			$ori = get_post( $ori_id, ARRAY_A );
			if ( !$ori )
				return;
			$overwriting = get_post( $overwriting_id, ARRAY_A );
			if ( !$overwriting )
				return;

			require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
			NelioABOptimizePressSupport::make_post_compatible_with_optimizepress(
				$ori_id, $overwriting_id );

			$ori['post_title']   = $overwriting['post_title'];
			$ori['post_content'] = $overwriting['post_content'];
			$ori['post_excerpt'] = $overwriting['post_excerpt'];
			$ori['post_parent']  = $overwriting['post_parent'];
			wp_update_post( $ori );

			if ( $meta )
				NelioABWpHelper::copy_meta_info( $overwriting_id, $ori_id );
			NelioABWpHelper::copy_terms( $overwriting_id, $ori_id, $categories, $tags );
		}

		/**
		 * Copy all custom fields (post meta) from one object to another
		 */
		public static function copy_meta_info( $src_id, $dest_id, $copy_hidden = true ) {

			// First of all, we remove all post meta from the destination
			$custom_fields = get_post_meta( $dest_id );
			if ( $custom_fields ) {
				foreach ( $custom_fields as $key => $val ) {

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
				foreach ( $custom_fields as $key => $val ) {

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


		public static function add_title_filter_to_wpquery( $where, &$wp_query ) {
			global $wpdb;
			if ( $search_term = $wp_query->get( 'post_title_like' ) ) {
				$search_term = $wpdb->esc_like( $search_term );
				$search_term = ' \'%' . $search_term . '%\'';
				$where .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
			}
			return $where;
		}

		/**
		 * This function is an AJAX callback. It returns a list of up to 20 posts (or
		 * pages). It is used by the select2 widget (an item selector that looks more
		 * beautiful than regular the "select>option" combo.
		 *
		 * Accepted POST params are:
		 *   term: {string}
		 *         the (part of the) string used to look for items.
		 *   type: {array}
		 *         array containing the types of element are we looking.
		 *   default_id: {int} (optional)
		 *         if set, the item with that ID will be returned. If that item is
		 *         not found, then we'll perform a regular search (as if the param
		 *         had not been set).
		 */
		public static function search_posts() {
			$term = false;
			if ( isset( $_POST['term'] ) & !empty( $_POST['term'] ) )
				$term = $_POST['term'];

			$types = array();
			if ( isset( $_POST['type'] ) )
				$types = $_POST['type'];
			if ( !is_array( $types ) )
				$types = array( $types );

			$status = 'publish';
			if ( isset( $_POST['drafts'] ) && 'show-drafts' == $_POST['drafts'] )
				$status = array( 'publish', 'draft' );

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
				'post_title_like' => $term,
				'posts_per_page'  => 20,
				'meta_key'        => '_is_nelioab_alternative',
				'meta_compare'    => 'NOT EXISTS',
				'post_status'     => $status,
			);

			if ( count( $types ) > 0 ) {
				$aux = array();
				foreach ( $types as $type )
					if ( strpos( $type, 'nelioab-' ) === false )
						array_push( $aux, $type );
				foreach ( $types as $type ) {
					if ( 'nelioab-all-post-types' == $type ) {
						array_push( $aux, 'page' );
						array_push( $aux, 'post' );
						foreach ( get_post_types( array( 'public' => true, '_builtin' => false ), 'names' ) as $cpt )
							array_push( $aux, $cpt );
					}
				}
				if ( count( $aux ) > 0 )
					$args['post_type'] = $aux;
			}

			if ( $types && count( $types ) == 1 && 'page' === $types[0] && !$term ) {
				$args['order'] = 'asc';
				$args['orderby'] = 'title';
			}

			$latest_post_item = false;
			if ( in_array( 'nelioab-latest-posts', $types ) || false !== $default_id ) {
				$lp_title = __( 'Your latest posts', 'nelioab' );
				if ( !$term || strpos( strtolower( $lp_title ), strtolower( $term ) ) !== false ) {
					$latest_post_item = array(
						'id'        => NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS,
						'type'      => '<i>latest-posts</i>',
						'title'     => self::fix_title( $lp_title ),
						'status'    => '<i>dynamic</i>',
						'date'      => '',
						'author'    => 'WordPress',
						'thumbnail' => $default_thumbnail,
					);
				}
			}

			$theme_based_landing_page = false;
			if ( ( in_array( 'nelioab-theme-landing-page', $types ) && NelioABSettings::does_theme_use_a_custom_landing_page() ) ||
					NelioABController::FRONT_PAGE__THEME_BASED_LANDING == $default_id ) {
				$lp_title = __( 'Landing Page (Theme-based)', 'nelioab' );
				if ( !$term || strpos( strtolower( $lp_title ), strtolower( $term ) ) !== false ) {
					$theme_based_landing_page = array(
						'id'        => NelioABController::FRONT_PAGE__THEME_BASED_LANDING,
						'type'      => '<i>landing-page</i>',
						'title'     => self::fix_title( $lp_title ),
						'status'    => '<i>dynamic</i>',
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

				if ( $id == NelioABController::FRONT_PAGE__THEME_BASED_LANDING ) {
					header( 'Content-Type: application/json' );
					echo json_encode( array( $theme_based_landing_page ) );
					die();
				}

				if ( $id > 0 )
					$post = get_post( $id );
				if ( $post ) {
					$thumbnail = get_the_post_thumbnail( $post->ID, 'thumbnail' );
					if ( $thumbnail === '' )
						$thumbnail = $default_thumbnail;
					$item = array(
						'id' => $post->ID,
						'title' => self::fix_title( $post->post_title ),
						'thumbnail' => $thumbnail,
						'excerpt' => $post->post_excerpt,
					);
					header( 'Content-Type: application/json' );
					echo json_encode( array( $item ) );
					die();
				}
			}

			$result = array();

			add_filter( 'posts_where', array( 'NelioABWpHelper', 'add_title_filter_to_wpquery' ), 10, 2 );
			$my_query = new WP_Query( $args );
			remove_filter( 'posts_where', array( 'NelioABWpHelper', 'add_title_filter_to_wpquery' ), 10, 2 );

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
						'title'     => self::fix_title( $post->post_title ),
						'status'    => $post->post_status,
						'date'      => $post->post_date,
						'excerpt'   => $post->post_excerpt,
						'author'    => get_the_author(),
						'thumbnail' => $thumbnail,
					);
					array_push( $result, $item );
				}
			}

			if ( $latest_post_item )
				array_unshift( $result, $latest_post_item );

			if ( $theme_based_landing_page )
				array_unshift( $result, $theme_based_landing_page );

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
				'order'          => 'asc',
				'orderby'        => 'title',
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
							'title'     => self::fix_title( $form->title() ),
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
							'title'     => self::fix_title( $form['title'] ),
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
						'title'     => self::fix_title( $form->title() ),
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
						'title'     => self::fix_title( $form->title ),
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

		private static function fix_title( $title ) {
			$title = strip_tags( $title );
			if ( strlen( $title ) == 0 )
				$title = __( '(no title)', 'nelioab' );
			return $title;
		}

		public static function sort_post_search_by_title( $i1, $i2 ) {
			return strcasecmp( $i1['title'], $i2['title'] );
		}

		public static function get_current_colorscheme() {
			$result = array();

			if ( self::is_at_least_version( '3.8' ) ) {
				global $_wp_admin_css_colors;
				$cname = get_user_option( 'admin_color' );
				$colorscheme = $_wp_admin_css_colors[$cname];
				$result['foreground'] = '#fff';
				$result['focus'] = $colorscheme->colors[2];
				$result['primary'] = $colorscheme->colors[1];
				$result['secondary'] = $colorscheme->colors[0];

				// Tweaking some colors that don't look good enough
				switch ( $cname ) {
					// Default theme
					case 'fresh':
						$result['primary'] = '#298cba';
						$result['secondary'] = '#194f68';
						break;
					// Greyish/dark theme
					case 'midnight':
						$result['primary'] = '#6b6b70';
						break;
					// Purple theme
					case 'ectoplasm':
						$result['primary'] = '#705f98';
						$result['secondary'] = '#302145';
						break;
				}
			}
			else {
				$result['foreground'] = '#fff';
				$result['focus'] = '#009bd9';
				$result['primary'] = '#298cba';
				$result['secondary'] = '#194f68';
			}

			return $result;
		}

		/**
		 * date_handler($date_start, $date_end)
		 *
		 * Date Validation
		 * $date_start expects a date formatted as YYYYMMDD or the string 'all' or ''
		 * $date_end expects a date formatted as YYYYMMDD or the string 'all' or ''
		 * If either $date_start or $date_end are set to 'all' or '', set dates to 00010101 and 99990101
		 */
		public static function date_handler( $date_start, $date_end ) {

			// check if either $date_start or $date_end have the value 'all'
			if ( $date_start == 'all' || $date_start == '' || $date_end == 'all' || $date_end == '' ) {
				return array( 'start' => '00010101', 'end' => '99990101' );
			}
			else {
				// check $date_start format and validity
				if ( is_numeric( $date_start ) && ( strlen( $date_start ) == '8' ) ) {

					// check $date_end format and validity
					if ( is_numeric( $date_end ) && ( strlen( $date_end ) == '8' ) && $date_end >= $date_start ) {
						$date_end = date( "Ymd", strtotime( date( "Ymd", strtotime( $date_end ) ) . " +1 day" ) );
						return array( 'start' => $date_start, 'end' => $date_end );
					}
					else { return 'End date is not valid.'; }
				}
				else { return 'Start date is not valid.'; }
			}
		}


	}//NelioABWpHelper

}

