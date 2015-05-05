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

if ( !class_exists( 'NelioABOptimizePressSupport' ) ) {

	/**
	 * This class adds support for the custom-permalinks plugin.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Compatibility
	 */
	abstract class NelioABOptimizePressSupport {

		/**
		 * This function checks whether the custom-permalinks plugin is active or not.
		 *
		 * @return boolean whether the custom-permalinks plugin is active or not.
		 *
		 * @since PHPDOC
		 */
		public static function is_plugin_active() {
			$mode = self::get_optimize_press_current_mode();
			return in_array( $mode, array( 'theme', 'plugin' ) );
		}


		/**
		 * PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		private static function get_optimize_press_current_mode() {
			$theme = wp_get_theme();
			$plugin = 'optimizePressPlugin/optimizepress.php';
			if ( 'optimizePressTheme' == $theme->template )
				return 'theme';
			else if ( in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) )
				return 'plugin';
			else
				return 'none';
		}


		/**
		 * This funtion makes a post originally created with OptimizePress compatible with OP.
		 *
		 * @param int $new_post_id PHPDOC
		 * @param int $old_post_id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function make_post_compatible_with_optimizepress(
				$new_post_id, $old_post_id ) {

			/** @var wpdb $wpdb */
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


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function apply_abtesting_to_optimize_press_pages() {
			// Remove default hook
			remove_filter( 'template_include', 'op_template_include' );
			// And add the proper hook
			switch ( self::get_optimize_press_current_mode() ) {
				case 'plugin':
					add_filter( 'template_include',
						array( 'NelioABOptimizePressSupport', 'op_plugin_template_include' ), 10, 2 );
					break;
				case 'theme':
					add_filter( 'template_include',
						array( 'NelioABOptimizePressSupport', 'op_theme_template_include' ), 10, 2 );
					break;
			}

			// OptimizePress uses single_post_title for showing post titles.
			// Let's make sure the proper title is shown
			add_filter( 'single_post_title',
				array( 'NelioABOptimizePressSupport', 'fix_the_single_title' ), 10, 2 );
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $template     PHPDOC
		 * @param boolean $use_template PHPDOC
		 *                              Default: true.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function op_plugin_template_include( $template, $use_template = true ) {
			if($use_template){
				if($id = get_queried_object_id()){
					$status = get_post_status($id);
					/* Nelio */ require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
					/* Nelio */ $id = NelioABVisitor::get_alternative_for_post_alt_exp( $id );
					/* Nelio */ if ( get_post_meta( $id, '_is_nelioab_alternative' ) ) $status = 'publish';
					if ( $status == 'publish' || (current_user_can('edit_posts') || current_user_can('edit_pages')) ){
						if(get_post_meta($id,'_'.OP_SN.'_pagebuilder',true) == 'Y'){
							op_init_page($id);
							if(op_page_option('launch_funnel','enabled') == 'Y' && $launch_info = op_page_option('launch_suite_info')){
								require_once OP_FUNC.'launch.php';
							}
							$theme = op_page_option('theme');
							$file = OP_PAGES.$theme['type'].'/'.$theme['dir'].'/template.php';
							if(file_exists($file)){
								return apply_filters('op_check_page_availability', $file);
							}
						}
					}
				}
			}
			return $template;
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $template     PHPDOC
		 * @param boolean $use_template PHPDOC
		 *                              Default: true.
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function op_theme_template_include( $template, $use_template = true ) {
			/*
			 * Assuring that we don't run this method twice (once on the template_include and once on the index_template hook)
			 */
			static $passed;
			if (isset($passed) && true === $passed && !empty($template)) {
				return $template;
			}
			$passed = true;

			if(op_get_option('blog_enabled') != 'Y' || op_get_option('installed') != 'Y'){
				global $post;
				if (!empty($post) && 'page' != $post->post_type) {
					return OP_DIR.'index.php';
				}
			}
			if($use_template){
				if($id = get_queried_object_id()){
					$status = get_post_status($id);
					/* Nelio */ require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
					/* Nelio */ $id = NelioABVisitor::get_alternative_for_post_alt_exp( $id );
					/* Nelio */ if ( get_post_meta( $id, '_is_nelioab_alternative' ) ) $status = 'publish';
					if ( $status == 'publish' || (current_user_can('edit_posts') || current_user_can('edit_pages')) ){
						if(get_post_meta($id,'_'.OP_SN.'_pagebuilder',true) == 'Y'){
							op_init_page($id);
							if(op_page_option('launch_funnel','enabled') == 'Y' && $launch_info = op_page_option('launch_suite_info')){
								require_once OP_FUNC.'launch.php';
							}
								$theme = op_page_option('theme');
							$file = OP_PAGES.$theme['type'].'/'.$theme['dir'].'/template.php';
							if(file_exists($file)){
									return apply_filters('op_check_page_availability', $file);
							}
						}
						else {
							op_init_theme();
							if($tpl = get_post_meta($id,'_op_page_template',true)){
								if(file_exists(OP_THEME_DIR.$tpl.'.php')){
									return OP_THEME_DIR.$tpl.'.php';
								}
							}
						}
					}
					else {
						op_init_theme();
					}
				}
				else {
					op_init_theme();
				}
			}
			$checks = array(
					'is_404' => '404',
					'is_search' => 'search',
					'is_front_page' => 'front_page',
					'is_home' => 'home',
					'is_single' => 'single',
					'is_page' => 'page',
					'is_category' => 'category',
					'is_tag' => 'tag',
					'is_author' => 'author',
					'is_archive' => 'archive',
					'is_paged' => 'paged'
				);
			$checks = apply_filters('op_template_include_checks',$checks);
			foreach ($checks as $check => $type) {
				if ($check()) {
					$files = apply_filters('op_template_include-' . $type,array($type));
					foreach ($files as $file) {
						if (file_exists(OP_THEME_DIR . $file . '.php')) {
							return OP_THEME_DIR . $file . '.php';
						}
					}
				}
			}

			if (defined('OP_THEME_DIR')) {
				return OP_THEME_DIR . 'index.php';
			}
			else {
				return OP_DIR . 'index.php';
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param string          $single_title PHPDOC
		 * @param WP_Post|boolean $post         PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public static function fix_the_single_title( $single_title, $post = false) {
			require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
			if ( $post ) {
				$alt_id = NelioABVisitor::get_alternative_for_post_alt_exp( $post->ID );
				$alt_post = get_post( $alt_id );
				if ( $alt_post )
					return $alt_post->post_title;
			}
			return $single_title;
		}

	}//NelioABOptimizePressSupport

}

