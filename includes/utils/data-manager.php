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

/**
 * This interface PHPDOC
 *
 * @since PHPDOC
 */
interface iNelioABDataManager {

	/**
	 * PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function list_elements();

}


/**
 * This class PHPDOC
 *
 * @since PHPDOC
 */
class NelioABArrays {

	/**
	 * PHPDOC
	 *
	 * @param array  $posts      PHPDOC
	 * @param string $comparator PHPDOC
	 *                           Default: `title`.
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public static function sort_posts( &$posts, $comparator = 'title' ) {
		if ( !is_array( $posts ) )
			return;
		switch( $comparator ) {
			case 'title':
			default:
				usort( $posts, array( 'NelioABArrays', 'compare_posts_by_title' ) );
		}
	}


	/**
	 * PHPDOC
	 *
	 * @param WP_Post|array $a PHPDOC
	 * @param WP_Post|array $b PHPDOC
	 *
	 * @return int PHPDOC
	 *
	 * @since PHPDOC
	 */
	private static function compare_posts_by_title( $a, $b ) {
		if ( is_object( $a ) ) $title_a = $a->post_title;
		else $title_a = $a['post_title'];
		if ( is_object( $b ) ) $title_b = $b->post_title;
		else $title_b = $b['post_title'];

		return strcmp( $title_a, $title_b );
	}

}

