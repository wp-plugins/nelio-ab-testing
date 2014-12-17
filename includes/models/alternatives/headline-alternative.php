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


if( !class_exists( 'NelioABHeadlineAlternative' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative.php' );
	class NelioABHeadlineAlternative extends NelioABAlternative {

		public function __construct( $id = -1 ) {
			parent::__construct( $id );
			$this->value = array( 'id' => -1 );
		}

		public function set_value_compat( $alt_id, $original_post_id ) {
			$image_id = get_post_thumbnail_id( $original_post_id );
			if ( !$image_id )
				$image_id = 0;

			$excerpt = '';
			$post = get_post( $original_post_id );
			if ( $post )
				$excerpt = $post->post_excerpt;

			$info = array(
				'id'       => $alt_id,
				'image_id' => $image_id,
				'excerpt'  => $excerpt,
			);

			$this->set_value( $info );
		}

		public function applies_to_post_id( $post_id ) {
			$val = $this->get_value();
			return $val['id'] == $post_id;
		}

		public function get_identifiable_value() {
			$val = $this->get_value();
			return $val['id'];
		}


		public function json4js() {
			$image_id = isset( $this->value['image_id'] ) ? $this->value['image_id'] : 'inherit';
			$image_src = false;
			if ( is_int( $image_id ) )
				$image_src = wp_get_attachment_image_src( $image_id, 'thumbnail' );
			if ( !$image_src ) {
				$image_id = 'inherit';
				$image_src = nelioab_admin_asset_link( '/images/feat-image-placeholder.png' );
			}
			else {
				$image_src = $image_src[0];
			}
			return array(
				'id'         => $this->id,
				'name'       => $this->name,
				'fakePostId' => isset( $this->value['id'] ) ? $this->value['id'] : -1,
				'excerpt'    => isset( $this->value['excerpt'] ) ? $this->value['excerpt'] : '',
				'imageId'    => $image_id,
				'imageSrc'   => $image_src,
				'wasDeleted' => $this->was_removed,
				'isDirty'    => $this->is_dirty,
			);
		}

		public static function build_alternative_using_json4js( $json_alt ) {
			$alt = new NelioABHeadlineAlternative();
			$alt->id    = $json_alt->id;
			$alt->name  = $json_alt->name;
			$alt->value = array(
				'id'        => isset( $json_alt->fakePostId ) ? $json_alt->fakePostId : -1,
				'excerpt'   => isset( $json_alt->excerpt ) ? $json_alt->excerpt : '',
				'image_id'  => isset( $json_alt->imageId ) ? $json_alt->imageId : 'inherit',
			);
			$alt->was_removed = isset( $json_alt->wasDeleted ) && $json_alt->wasDeleted;
			$alt->is_dirty = isset( $json_alt->isDirty ) && $json_alt->isDirty;
			return $alt;
		}

	}//NelioABHeadlineAlternative

}

