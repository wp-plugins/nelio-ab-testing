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


if ( !class_exists( 'NelioABOptimizePressSupport' ) ) {

	abstract class NelioABOptimizePressSupport {

		public static function is_optimize_press_active() {
			$plugin = 'optimizePressPlugin/optimizepress.php';
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
		}

		/**
		 * If src post was created with OptimizePress, make new post compatible with it
		 */
		public static function make_post_compatible_with_optimizepress(
				$new_post_id, $old_post_id ) {

			global $wpdb;

			$table_name = $wpdb->prefix . 'optimizepress_post_layouts';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name )
				return;

			$old_post_entry = $wpdb->get_var(
				'SELECT COUNT(*) ' .
				'FROM ' . $wpdb->prefix . 'optimizepress_post_layouts ' .
				'WHERE post_id = ' . $old_post_id . ' AND status = \'publish\'' );
			if ( $old_post_entry == 0 )
				return;

			$row = $wpdb->get_row(
				'SELECT * ' .
				'FROM ' . $wpdb->prefix . 'optimizepress_post_layouts ' .
				'WHERE post_id = ' . $old_post_id . ' AND status = \'publish\'',
				ARRAY_A );

			$new_post_entry = $wpdb->get_var(
				'SELECT COUNT(*) ' .
				'FROM ' . $wpdb->prefix . 'optimizepress_post_layouts ' .
				'WHERE post_id = ' . $new_post_id );
			if ( $new_post_entry == 0 ) {
				$wpdb->insert(
					$wpdb->prefix . 'optimizepress_post_layouts',
					array ( 'post_id' => $new_post_id ),
					array ( '%d' ) );
			}

			$wpdb->update(
				$wpdb->prefix . 'optimizepress_post_layouts',
					array (
						'layout'  => $row['layout'],
						'type'    => $row['type']
					),
					array ( 'post_id' => $new_post_id ),
					array (
						'%s',
						'%s'
					),
					array( '%d' )
				);

		}

		public static function op_template_include( $template, $use_template = true ) {
			if ( $use_template ) {
				if ( $id = get_queried_object_id() ) {
					$aux = get_post_meta( $id, '_is_nelioab_alternative' );
					if ( !$aux )
						return $template;

					$status = get_post_status( $id );
					if ( 'draft' == $status || 'publish' == $status ||
					     ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) )
					   ) {
						if ( get_post_meta( $id, '_' . OP_SN . '_pagebuilder', true ) == 'Y' ) {
							op_init_page( $id );
							if ( op_page_option( 'launch_funnel', 'enabled' ) == 'Y' &&
							     $launch_info = op_page_option( 'launch_suite_info' ) )
								require_once( OP_FUNC . 'launch.php' );
							$theme = op_page_option('theme');
							$file  = OP_PAGES . $theme['type'] . '/' . $theme['dir'] . '/template.php';
							if ( file_exists( $file ) )
								return apply_filters( 'op_check_page_availability', $file );
						}
					}
				}
			}
		   return $template;
		}

	}//NelioABOptimizePressSupport

}

?>
