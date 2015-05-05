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
 * Nelio AB Testing controller for alternative experiments.
 *
 * @since PHPDOC
 * @package \NelioABTesting\Controllers
 */
class NelioABAlternativeExperimentController {

	/**
	 * PHPDOC
	 *
	 * @since PHPDOC
	 * @var boolean
	 */
	private $are_comments_from_original_loaded;


	/**
	 * PHPDOC
	 *
	 * @since PHPDOC
	 * @var WP_Theme|boolean
	 */
	private $actual_theme;


	/**
	 * PHPDOC
	 *
	 * @since PHPDOC
	 * @var WP_Theme|boolean
	 */
	private $original_theme;


	/**
	 * PHPDOC
	 *
	 * @since PHPDOC
	 * @var array
	 */
	private $applied_headlines;


	/**
	 * It creates a new instance of this controller.
	 *
	 * In principle, this class should be used as if it implemented the
	 * `singleton` pattern.
	 *
	 * @return NelioABAlternativeExperimentController a new instance of this class.
	 *
	 * @since PHPDOC
	 */
	public function __construct() {
		$this->actual_theme = false;
		$this->original_theme = false;
		$this->applied_headlines = array();
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function hook_to_wordpress() {
		$this->are_comments_from_original_loaded = false;

		// Page/Post alt exp related
		add_action( 'wp', array( &$this, 'do_late_hooks' ) );

		// Make sure that the title is replaced everywhere
		add_filter( 'wp_title',  array( &$this, 'fix_title_for_landing_page' ), 10, 2 );

		add_filter( 'the_posts',         array( &$this, 'the_posts_intercept' ) );
		add_filter( 'get_post_metadata', array( &$this, 'load_proper_page_template' ), 10, 3 );

		add_action( 'pre_get_comments',    array( &$this, 'load_comments_from_original' ) );
		add_filter( 'comments_array',      array( &$this, 'prepare_comments_form' ) );
		add_filter( 'get_comments_number', array( &$this, 'load_comments_number_from_original' ) );

		add_filter( 'post_link',      array( &$this, 'use_originals_post_link' ) );
		add_filter( 'page_link',      array( &$this, 'use_originals_post_link' ) );
		add_filter( 'post_type_link', array( &$this, 'use_originals_post_link' ) );
		add_filter( 'get_shortlink',  array( &$this, 'use_originals_shortlink' ), 10, 2 );

		add_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
		add_action( 'wp_get_nav_menu_items', array( &$this, 'highlight_current_menu_option' ), 11 );

		add_action( 'nelioab_footer', array( &$this, 'print_list_of_applied_headlines' ) );
		add_filter( 'nelioab_ajax_result', array( &$this, 'add_list_of_applied_headlines' ) );
		add_action( 'wp_footer',      array( &$this, 'include_css_alternative_fragments_if_any' ) );
		add_action( 'wp_footer',      array( &$this, 'do_nelioab_footer' ) );


		/**
		 * Headline Experiments modify TITLE, FEATURED IMAGE and EXCERPT.
		 * Let's add the three filters!
		 */
		add_filter( 'the_title',         array( &$this, 'get_consistent_title' ), 10, 2 );
		add_filter( 'get_the_excerpt',   array( &$this, 'get_consistent_excerpt' ), 10 );
		add_filter( 'the_content',       array( &$this, 'get_consistent_content' ), 10 );
		add_filter( 'get_post_metadata', array( &$this, 'get_consistent_featured_image' ), 10, 4 );

		add_filter( 'the_title',         array( &$this, 'replace_headline_title' ), 10, 2 );
		add_filter( 'get_the_excerpt',   array( &$this, 'replace_headline_excerpt' ), 10 );
		add_filter( 'get_post_metadata', array( &$this, 'fix_headline_featured_image' ), 10, 4 );

		/**
		 * Support with other plugins.
		 * Compatibility tweaks for testing OptimizePress pages.
		 */
		require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
		if ( NelioABOptimizePressSupport::is_plugin_active() ) {
			add_filter( 'template_redirect',
				array( 'NelioABOptimizePressSupport', 'apply_abtesting_to_optimize_press_pages' ) );
		}

		/**
		 * Support with other plugins.
		 * Compatibility tweaks with Member Access.
		 */
		require_once( NELIOAB_UTILS_DIR . '/member-access-support.php' );
		if ( NelioABMemberAccessSupport::is_plugin_active() )
			add_action( 'wp_loaded',
				array( 'NelioABMemberAccessSupport', 'unhook_redirections' ) );

		/**
		 *  Hooks for Gravity Forms and Contact Form 7
		 */
		// Monitoring submissions:
		add_action( 'gform_after_submission', array( &$this, 'track_gf_submission' ),  10, 2 );
		add_action( 'wpcf7_submit',           array( &$this, 'track_cf7_submission' ), 10, 2 );


		/**
		 * Basic WooCommerce support (first version of Custom Post Types support).
		 * @since 4.1.0
		 */
		add_action( 'woocommerce_product_object', array( $this, 'wc_make_alternative_product_purchasable' ) );
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function do_late_hooks() {
		global $nelioab_controller;
		add_filter( 'option_page_on_front', array( &$nelioab_controller, 'fix_page_on_front' ) );
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function do_nelioab_footer() {
		do_action( 'nelioab_footer' );
	}


	/**
	 * PHPDOC
	 *
	 * @param string $title PHPDOC
	 * @param int    $id    PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_consistent_title( $title, $id = NULL ) {
		if ( empty( $id ) )
			return $title;

		// Let's check if the queried $id is a menu...
		$menu_related_post = get_post_meta( $id, '_menu_item_object_id', true );
		if ( $menu_related_post ) {
			// If it is, we have to load the alternative title if the menu
			// itself does not define it's own title.
			$menu = get_post( $id );
			if ( empty( $menu->post_title ) ) {
				$id = $menu_related_post;
			}
			else {
				$post = get_post( $menu_related_post );
				if ( $post->post_title == $menu->post_title )
					$id = $menu_related_post;
			}
		}

		$alt_id = $this->get_post_alternative( $id );
		if ( $alt_id !== $id ) {
			$post = get_post( $alt_id );
			if ( $post ) {
				remove_filter( 'the_title', array( &$this, 'get_consistent_title' ) );
				$title = apply_filters( 'the_title', $post->post_title, $alt_id );
				add_filter( 'the_title', array( &$this, 'get_consistent_title' ), 10, 2 );
				return $title;
			}
		}
		return $title;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $excerpt PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_consistent_excerpt( $excerpt ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $excerpt;
		/** @var NelioABController $nelioab_controller */
		global $nelioab_controller;
		if ( $nelioab_controller->get_queried_post_id() == $post->ID ) {
			return $excerpt;
		}
		$alt_id = $this->get_post_alternative( $post->ID );
		if ( $alt_id !== $post->ID ) {
			$post = get_post( $alt_id );
			if ( $post ) {
				remove_filter( 'get_the_excerpt', array( &$this, 'get_consistent_excerpt' ) );
				$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt );
				add_filter( 'get_the_excerpt', array( &$this, 'get_consistent_excerpt' ), 10 );
				return $excerpt;
			}
		}
		return $excerpt;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $content PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_consistent_content( $content ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $content;
		/** @var NelioABController $nelioab_controller */
		global $nelioab_controller;
		if ( $nelioab_controller->get_queried_post_id() == $post->ID ) {
			return $content;
		}
		$alt_id = $this->get_post_alternative( $post->ID );
		if ( $alt_id !== $post->ID ) {
			$post = get_post( $alt_id );
			if ( $post ) {
				remove_filter( 'the_content', array( &$this, 'get_consistent_content' ) );
				$content = apply_filters( 'the_content', $post->post_content );
				add_filter( 'the_content', array( &$this, 'get_consistent_content' ), 10 );
				return $content;
			}
		}
		return $content;
	}


	/**
	 * PHPDOC
	 *
	 * @param string  $value     Type of object metadata is for (e.g., comment, post, or user)
	 * @param int     $object_id ID of the object metadata is for
	 * @param string  $meta_key  Optional. Metadata key.
	 * @param boolean $single    Optional.
	 *
	 * @return mixed PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_consistent_featured_image( $value, $object_id, $meta_key, $single ) {
		if ( '_thumbnail_id' == $meta_key ) {
			$alt_id = $this->get_post_alternative( $object_id );
			if ( $alt_id !== $object_id ) {
				remove_filter( 'get_post_metadata', array( &$this, 'get_consistent_featured_image' ) );
				$value = get_post_meta( $alt_id, $meta_key, $single );
				add_filter( 'get_post_metadata', array( &$this, 'get_consistent_featured_image' ), 10, 4 );
				return $value;
			}
		}
		return $value;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $value    PHPDOC
	 * @param int    $post_id  PHPDOC
	 * @param string $meta_key Optional. PHPDOC
	 *
	 * @return mixed PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function load_proper_page_template( $value, $post_id = 0, $meta_key = '' ) {
		if ( $meta_key === '_wp_page_template' ) {
			if ( $this->is_post_in_a_post_alt_exp( $post_id ) ) {
				$post_id = $this->get_post_alternative( $post_id );
				remove_filter( 'get_post_metadata', array( &$this, 'load_proper_page_template' ), 10 );
				$value = get_post_meta( $post_id, '_wp_page_template', true );
				add_filter( 'get_post_metadata', array( &$this, 'load_proper_page_template' ), 10, 4 );
				if ( !$value ) $value = null;
			}
		}
		return $value;
	}


	/**
	 * PHPDOC
	 *
	 * @return NelioABGlobalAlternativeExperiment|boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function get_global_alt_exp_running() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CSS_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
					return $exp;
		}
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @return WP_Theme PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function get_actual_theme() {
		if ( !$this->actual_theme ) {
			require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
			remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
			remove_filter( 'template',   array( &$this, 'modify_template' ) );
			$this->actual_theme = NelioABVisitor::get_assigned_theme();
			$this->original_theme = wp_get_theme();
			add_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
			add_filter( 'template',   array( &$this, 'modify_template' ) );
		}
		return $this->actual_theme;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $stylesheet PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function modify_stylesheet( $stylesheet ) {
		// WARNING: I check whether the function 'wp_get_current_user" exists,
		// because the latter depends on this one and, sometimes, this one is
		// not available, generating a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || nelioab_can_user_manage_plugin() )
			return $stylesheet;
		$theme = $this->get_actual_theme();
		return $theme['Stylesheet'];
	}


	/**
	 * PHPDOC
	 *
	 * @param string $template PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function modify_template( $template ) {
		// WARNING: I check whether the function 'wp_get_current_user" exists,
		// because the latter depends on this one and, sometimes, this one is
		// not available, generating a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || nelioab_can_user_manage_plugin() )
			return $template;
		$theme = $this->get_actual_theme();
		return $theme['Template'];
	}


	/**
	 * PHPDOC
	 *
	 * @param string $stylesheet PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function modify_option_stylesheet( $stylesheet ) {
		remove_filter( 'option_stylesheet', array( &$this, 'modify_option_stylesheet' ) );
		$res = $this->modify_stylesheet( $stylesheet );
		add_filter( 'option_stylesheet', array( &$this, 'modify_option_stylesheet' ) );
		return $res;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $name PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function modify_option_current_theme( $name ) {
		// WARNING: I check whether the function 'wp_get_current_user" exists,
		// because the latter depends on this one and, sometimes, this one is
		// not available, generating a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || nelioab_can_user_manage_plugin() )
			return $name;
		$theme = $this->get_actual_theme();
		return $theme['Name'];
	}


	/**
	 * PHPDOC
	 *
	 * @param array  $items An array of menu item post objects.
	 * @param object $menu  The menu object.
	 * @param array  $args  An array of arguments used to retrieve menu item objects.
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function show_the_appropriate_menu( $items, $menu, $args ) {
		remove_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
		$alt_menu = $this->get_menu_alternative( $menu->term_id );
		if ( is_nav_menu( $alt_menu ) )
			$items = wp_get_nav_menu_items( $alt_menu, $args );
		add_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
		return $items;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $all_widgets PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function show_the_appropriate_widgets( $all_widgets ) {
		require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
		/** @var NelioABAlternative $alt */
		$alt = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		if ( $alt )
			return $this->fix_widgets_for_widget_exp( $all_widgets, $alt );

		/** @var NelioABAlternative $alt */
		$alt = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $alt )
			return $this->fix_widgets_for_theme_exp( $all_widgets );

		return $this->filter_original_widgets( $all_widgets );
	}


	/**
	 * PHPDOC
	 *
	 * @param array              $all_widgets PHPDOC
	 * @param NelioABAlternative $alt         PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function fix_widgets_for_widget_exp( $all_widgets, $alt ) {
		/** @var NelioABExperiment $exp */
		$exp = $this->get_global_alt_exp_running();
		if ( $exp->get_type() != NelioABExperiment::WIDGET_ALT_EXP )
			return $all_widgets;

		$alt_id = $alt->get_id();
		$exp_id = $exp->get_id();

		$use_ori_widgets = ( $exp->get_originals_id() == $alt_id );

		$res = array();
		if ( $use_ori_widgets ) {
			$res = $this->filter_original_widgets( $all_widgets );
		}
		else {
			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
			$widgets_in_experiments = NelioABWidgetExpAdminController::get_widgets_in_experiments();
			foreach ( $all_widgets as $sidebar => $widgets ) {
				$res[$sidebar] = array();
				if ( !is_array( $widgets ) )
					continue;
				foreach ( $widgets as $widget ) {
					if ( $this->is_widget_enabled( $widget, $widgets_in_experiments, $exp_id, $alt_id ) )
						array_push( $res[$sidebar], $widget );
				}
			}
		}
		return $res;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $all_widgets PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function filter_original_widgets( $all_widgets ) {
		require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
		$widgets_in_experiments = NelioABWidgetExpAdminController::get_widgets_in_experiments();
		$res = array();
		foreach ( $all_widgets as $sidebar => $widgets ) {
			$res[$sidebar] = array();
			if ( !is_array( $widgets ) )
				continue;
			foreach ( $widgets as $widget ) {
				if ( $this->is_widget_original( $widget, $widgets_in_experiments ) )
					array_push( $res[$sidebar], $widget );
			}
		}
		return $res;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $widget PHPDOC
	 * @param array  $list   PHPDOC
	 *
	 * @return boolean
	 *
	 * @since PHPDOC
	 */
	private function is_widget_original( $widget, $list ) {
		foreach( $list as $w => $aux )
			if ( $w == $widget )
				return false;
		return true;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $widget PHPDOC
	 * @param array  $list   PHPDOC
	 * @param int    $exp_id PHPDOC
	 * @param int    $alt_id PHPDOC
	 *
	 * @return boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function is_widget_enabled( $widget, $list, $exp_id, $alt_id ) {
		foreach( $list as $w => $aux )
			if ( $widget == $w && $aux['exp'] == $exp_id && $aux['alt'] == $alt_id )
				return true;
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $all_widgets PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function fix_widgets_for_theme_exp( $all_widgets ) {
		$actual_theme = $this->get_actual_theme();
		$actual_theme_id = $actual_theme['Stylesheet'];

		if ( !$this->original_theme['Stylesheet'] ||
		     $this->original_theme['Stylesheet'] == $actual_theme_id )
			return $all_widgets;

		$aux = get_option( 'theme_mods_' . $actual_theme_id, array() );
		$sidebars_widgets = $aux['sidebars_widgets']['data'];
		if ( is_array( $sidebars_widgets ) && isset( $sidebars_widgets['array_version'] ) )
			unset( $sidebars_widgets['array_version'] );
		return $sidebars_widgets;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $items PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function highlight_current_menu_option( $items ) {
		foreach ( $items as $item ) {
			$id = $item->object_id;
			$item->object_id = $this->get_post_alternative( $id );
		}
		return $items;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $posts PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function the_posts_intercept( $posts ) {
		/** @var NelioABController $nelioab_controller */
		global $nelioab_controller;
		$post_id = $nelioab_controller->get_queried_post_id();
		remove_filter( 'the_posts', array( &$this, 'the_posts_intercept' ) );
		if ( $post_id > 0 ) {
			$alt = NULL;
			$alt_post_id = $this->get_post_alternative( $post_id );
			if ( $alt_post_id != $post_id )
				$alt = get_post( $this->get_post_alternative( $post_id ) );
			if ( $alt )
				return array( $alt );
		}
		return $posts;
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function include_css_alternative_fragments_if_any() {
		if ( !is_main_query() ) return;

		require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
		/** @var NelioABAlternative $alt */
		$alt = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( !$alt )
			return;

		echo $this->prepare_css_as_js( $alt->get_value() );
	}


	/**
	 * PHPDOC
	 *
	 * @param WP_Query $comments_query PHPDOC
	 *
	 * @return WP_Query PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function load_comments_from_original( $comments_query ) {
		global $post;
		$id = $post->ID;
		if ( !$this->is_post_alternative( $id ) )
			return $comments_query;

		$this->are_comments_from_original_loaded = true;

		// Load the original comments
		$copy_from = $this->get_original_related_to( $id );
		$comments_query->query_vars['post_id'] = $copy_from;

		return $comments_query;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $permalink PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function use_originals_post_link( $permalink ) {
		$perm_id = url_to_postid( $permalink );
		if ( $this->is_post_alternative( $perm_id ) ) {
			$ori_id = $this->get_original_related_to( $perm_id );
			$permalink = get_permalink( $ori_id );
		}
		return $permalink;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $shortlink PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function use_originals_shortlink( $shortlink ) {
		global $post;
		remove_filter( 'get_shortlink', array( &$this, 'use_originals_shortlink' ), 10, 2 );
		if ( $post )
			$shortlink = wp_get_shortlink( $this->get_original_related_to( $post->ID ) );
		add_filter( 'get_shortlink', array( &$this, 'use_originals_shortlink' ), 10, 2 );
		return $shortlink;
	}


	/**
	 * PHPDOC
	 *
	 * @param array $comments PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function prepare_comments_form( $comments ) {
		/**
		 * @var int     $user_ID
		 * @var WP_Post $post
		 * @var wpdb    $wpdb
		 */
		global $user_ID, $post, $wpdb;
		$id = $post->ID;
		if ( !$this->is_post_alternative( $id ) )
			return $comments;

		$ori_id = $this->get_original_related_to( $id );

		// Workaround to fix the fact that wp-includes/comment-template.php does not
		// always use the function GET_COMMENTS
		if ( !$this->are_comments_from_original_loaded ) {
			if ( $user_ID ) {
				$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->comments " .
					"WHERE comment_post_ID = %d AND " .
					"(comment_approved = '1' OR " .
					"   ( user_id = %d AND comment_approved = '0' ) ) ".
					"ORDER BY comment_date_gmt",
					$ori_id, $user_ID ) );
			}
			else {
				$commenter = wp_get_current_commenter();
				$comment_author = $commenter['comment_author'];
				$comment_author_email = $commenter['comment_author_email'];
				$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->comments " .
					"WHERE comment_post_ID = %d AND " .
					"( comment_approved = '1' OR " .
					"   ( comment_author = %s AND " .
					"     comment_author_email = %s AND " .
					"     comment_approved = '0' ) ) " .
					"ORDER BY comment_date_gmt",
					$ori_id, wp_specialchars_decode( $comment_author, ENT_QUOTES ),
					$comment_author_email ) );
			}
		}
		$this->are_comments_from_original_loaded = false;
		?>

		<script type="text/javascript">
		(function($) {
			$(document).ready(function(){
				$("#comment_post_ID").attr( 'value', <?php echo $ori_id; ?> );
			});
		})(jQuery);
		</script><?php

		return $comments;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $comments_number PHPDOC
	 *
	 * @return int PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function load_comments_number_from_original( $comments_number ) {
		global $post;
		$id = $post->ID;
		if ( !$this->is_post_alternative( $id ) )
			return $comments_number;
		$ori_id = $this->get_original_related_to( $post->ID );
		$aux = get_post( $ori_id, ARRAY_A );
		return $aux['comment_count'];
	}


	/**
	 * PHPDOC
	 *
	 * @param boolean $ajax PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function add_list_of_applied_headlines( $ajax = false ) {
		$res = array( 'nelioab' => array() );
		if ( $ajax )
			$res['result'] = $ajax;

		if ( NelioABSettings::get_headlines_quota_mode() == NelioABSettings::HEADLINES_QUOTA_MODE_ON_FRONT_PAGE ) {
			/** @var NelioABController $nelioab_controller */
			global $nelioab_controller;
			$current_post_id = $nelioab_controller->get_queried_post_id();
			$front_page_id = nelioab_get_page_on_front();
			if ( $front_page_id == 0 )
				$front_page_id = NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS;
			if ( $front_page_id != $current_post_id )
				$this->applied_headlines = array();
		}

		$headlines = array();
		foreach ( $this->applied_headlines as $hl )
			array_push( $headlines, implode( ':', $hl ) );
		$headlines = implode( ',', $headlines );
		$res['nelioab']['headlines'] = $headlines;

		return $res;
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function print_list_of_applied_headlines() {
		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
		     ! wp_script_is( 'nelioab_tracking_script', 'done' ) )
			return;
		$aux = $this->add_list_of_applied_headlines();
		$headlines = $aux['nelioab']['headlines']; ?>
		<script type="text/javascript">
		/* <![CDATA[ */
		NelioABParams.sync.headlines = <?php
			echo json_encode( $headlines );
		?>;
		<?php
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			echo "NelioAB.helpers.sendNewHeadlineViews();";
		?>
		/* ]]> */
		</script><?php
	}


	/**
	 * PHPDOC
	 *
	 * @param string         $title PHPDOC
	 * @param string|boolean $sep   PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function fix_title_for_landing_page( $title, $sep = false ) {
		global $post;
		if ( is_object( $post ) && $this->is_post_alternative( $post->ID ) ) {
			$front_page_id = nelioab_get_page_on_front();
			$ori_id = $this->get_original_related_to( $post->ID );
			if ( $ori_id == $front_page_id ) {
				$title = get_bloginfo( 'name' ) . " $sep ";
			}
		}
		return $title;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $post_id PHPDOC
	 *
	 * @return boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function is_post_in_a_post_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
				 $exp->get_type() == NelioABExperiment::CPT_ALT_EXP ) {
				if ( $exp->get_originals_id() == $post_id )
					return true;
			}
		}
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $post_id PHPDOC
	 *
	 * @return boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function is_post_in_a_headline_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP &&
			     $exp->get_originals_id() == $post_id )
				return true;
		}
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $post_id PHPDOC
	 *
	 * @return boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function is_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CPT_ALT_EXP ) {
				/** @var NelioABPostAlternativeExperiment $exp */
				foreach ( $exp->get_alternatives() as $alt ) {
					/** @var NelioABAlternative $alt */
					if ( $alt->get_value() == $post_id )
						return true;
				}
			}
		}
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $alt_id PHPDOC
	 *
	 * @return int PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_original_related_to( $alt_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CPT_ALT_EXP) {
				/** @var NelioABPostAlternativeExperiment $exp */
				foreach ( $exp->get_alternatives() as $alt ) {
					/** @var NelioABAlternative $alt */
					if ( $alt->get_value() == $alt_id )
						return $exp->get_originals_id();
				}
			}
		}
		// If it is not an alternative, we return the same ID
		return $alt_id;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $post_id PHPDOC
	 *
	 * @return int PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function get_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
		return NelioABVisitor::get_alternative_for_post_alt_exp( $post_id );
	}

	/**
	 * PHPDOC
	 *
	 * @param int $menu_id PHPDOC
	 *
	 * @return int|boolean PHPDOC
	 *
	 * @since PHPDOC
	 */

	public function get_menu_alternative( $menu_id ) {
		require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
		/** @var NelioABAlternative $alt */
		$alt = NelioABVisitor::get_alternative_for_menu_alt_exp( $menu_id );
		if ( $alt )
			return $alt->get_value();
		else
			return false;
	}


	/**
	 * PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function preview_css() {
		$css_id = '';
		if ( isset( $_GET['nelioab_preview_css'] ) )
			$css_id = $_GET['nelioab_preview_css'];
		$css = get_option( 'nelioab_css_' . $css_id, false );
		echo $this->prepare_css_as_js( $css );
	}


	/**
	 * PHPDOC
	 *
	 * @param string $code PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function prepare_css_as_js( $code ) {
		$code = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $code );
		$code = preg_replace( '/\s+/', ' ', $code );
		$code = preg_replace( '/ *([{};]) */', '\1', $code );
		$code = str_replace( ': ', ':', $code );
		$code = str_replace( '\\', '\\\\', $code );
		$code = str_replace( '"', '\\"', $code );
		$code = str_replace( array( "\r\n", "\r", "\n" ), '', $code );

		$css  = "<script type='text/javascript'>\n";
		$css .= "  if ( typeof NelioABParams == 'undefined' ) NelioABParams = {};\n";
		$css .= "  if ( typeof NelioABParams.css == 'undefined' ) NelioABParams.css = {};\n";
		$css .= "  NelioABParams.css.value = document.createElement('style');\n";
		$css .= "  NelioABParams.css.value.setAttribute('type', 'text/css');\n";
		$css .= "  NelioABParams.css.value.innerHTML = \"$code\";\n";
		$css .= "  document.getElementsByTagName('head')[0].appendChild(NelioABParams.css.value);\n";
		$css .= "</script>\n";

		return $css;
	}


	/**
	 * PHPDOC
	 *
	 * @param NelioABHeadlineAlternativeExperiment $exp PHPDOC
	 * @param NelioABHeadlineAlternative           $alt PHPDOC
	 *
	 * @return array PHPDOC
	 *
	 * @since PHPDOC
	 */
	private function add_active_headline_experiment( $exp, $alt ) {
		$exp_id = $exp->get_id();
		$aux = $alt->get_value();
		$alt_id = $aux['id'];
		foreach ( $this->applied_headlines as $info )
			if ( $info['exp'] == $exp_id )
				return;
		array_push( $this->applied_headlines,
			array(
				'exp' => $exp_id,
				'alt' => $alt_id,
			)
		);
	}


	/**
	 * PHPDOC
	 *
	 * @return boolean PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function is_there_a_running_headline_experiment() {
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			if ( $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP )
				return true;
		}
		return false;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $title PHPDOC
	 * @param int    $id    PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function replace_headline_title( $title, $id = NULL ) {
		if ( empty( $id ) )
			return $title;
		$headline_data = $this->get_headline_experiment_and_alternative( $id );
		if ( $headline_data ) {
			$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
			/** @var NelioABAlternative $alt */
			$alt = $headline_data['alt'];
			return $alt->get_name();
		}
		return $title;
	}


	/**
	 * PHPDOC
	 *
	 * @param string $excerpt PHPDOC
	 *
	 * @return string PHPDOC
	 *
	 * @since PHPDOC
	 */
	public function replace_headline_excerpt( $excerpt ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $excerpt;
		/** @var NelioABController $nelioab_controller */
		global $nelioab_controller;
		if ( $nelioab_controller->get_queried_post_id() == $post->ID ) {
			return $excerpt;
		}
		$headline_data = $this->get_headline_experiment_and_alternative( $post->ID );
		if ( $headline_data ) {
			$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
			/** @var NelioABAlternative $alt */
			$alt = $headline_data['alt'];
			$value = $alt->get_value();
			// This first IF is a safeguard...
			if ( is_array( $value ) && isset( $value['excerpt'] ) && !empty( $value['excerpt'] ) ) {
				return $value['excerpt'];
			}
		}
		return $excerpt;
	}


	/**
	 * PHPDOC
	 *
	 * @param string  $value     Type of object metadata is for (e.g., comment, post, or user)
	 * @param int     $object_id ID of the object metadata is for
	 * @param string  $meta_key  Optional. Metadata key.
	 * @param boolean $single    Optional.
	 *
	 * @return mixed Single metadata value, or array of values
	 *
	 * @since PHPDOC
	 */
	public function fix_headline_featured_image( $value, $object_id, $meta_key, $single ) {
		if ( '_thumbnail_id' == $meta_key ) {
			$headline_data = $this->get_headline_experiment_and_alternative( $object_id );
			if ( $headline_data ) {
				$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
				/** @var NelioABAlternative $alt */
				$alt = $headline_data['alt'];
				$value = $alt->get_value();
				// This first IF is a safeguard...
				if ( is_array( $value ) && isset( $value['image_id'] ) ) {
					if ( $single )
						return $value['image_id'];
					else
						return $value['image_id'];
				}
			}
		}
		return $value;
	}


	/**
	 * PHPDOC
	 *
	 * @param int $post_id PHPDOC
	 *
	 * @return boolean|NelioABAlternative the headline alternative (if any) this visitor is supposed to see.
	 *
	 * @see NelioABVisitor::get_alternative_for_headline_alt_exp
	 *
	 * @since PHPDOC
	 */
	public function get_headline_experiment_and_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
		return NelioABVisitor::get_alternative_for_headline_alt_exp( $post_id );
	}


	/**
	 * PHPDOC
	 *
	 * @param array $entry PHPDOC
	 * @param array $form  PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function track_gf_submission( $entry, $form ) {
		$type = NelioABAction::SUBMIT_GRAVITY_FORM;
		$this->send_form_action_if_required( $type, $form['id'] );
	}


	/**
	 * PHPDOC
	 *
	 * @param WPCF7_ContactForm $form   PHPDOC
	 * @param array             $result PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	public function track_cf7_submission( $form, $result ) {
		$status = $result['status'];
		$type = NelioABAction::SUBMIT_CF7_FORM;
		if ( $status == 'mail_sent' || $status == 'demo_mode' )
			$this->send_form_action_if_required( $type, $form->id() );
	}


	/**
	 * PHPDOC
	 *
	 * @param string $type    PHPDOC
	 * @param int    $form_id PHPDOC
	 *
	 * @return void
	 *
	 * @since PHPDOC
	 */
	private function send_form_action_if_required( $type, $form_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/actions/form-submission-action.php' );
		require_once( NELIOAB_MODELS_DIR . '/goals/actions/action.php' );
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );

		$kap = NelioABFormSubmissionAction::type_to_kind_and_plugin( $type );
		if ( !$kap )
			return;

		$f = 'nelioab_current_id';
		if ( !isset( $_POST[$f] ) )
			return;

		$f = 'nelioab_current_actual_id';
		if ( !isset( $_POST[$f] ) )
			return;

		$f = 'nelioab_userid';
		if ( !isset( $_POST[$f] ) || strlen( trim( $_POST[$f] ) ) === 0 )
			return;

		// Constructing FORM EVENT object:
		$page = $_POST['nelioab_current_id'];
		$actual_page = $_POST['nelioab_current_actual_id'];

		$ev = array(
			'kind'       => $kap['kind'],
			'form'       => $form_id,
			'plugin'     => $kap['plugin'],
			'page'       => $page,
			'actualPage' => $actual_page,
			'user'       => $_POST['nelioab_userid']
		);

		$the_theme = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $the_theme )
			$ev['activeTheme'] = $the_theme->get_id();

		$the_css = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( $the_css )
			$ev['activeCSS'] = $the_css->get_id();

		$the_widget = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		if ( $the_widget )
			$ev['activeWidget'] = '' . $the_widget->get_id();

		$the_menu = NelioABVisitor::get_alternative_for_global_alt_exp( NelioABExperiment::MENU_ALT_EXP );
		if ( $the_menu )
			$ev['activeMenu'] = $the_menu->get_id();


		// Check if there's one experiment at least with a form submission action,
		// which corresponds to the given form, then send the event. Otherwise,
		// get out!
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$send_form_event = false;
		$running_exps = NelioABExperimentsManager::get_relevant_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			/** @var NelioABExperiment $exp */
			foreach ( $exp->get_goals() as $goal ) {
				/** @var NelioABAltExpGoal $goal */
				foreach ( $goal->get_actions() as $action ) {
					/** @var NelioABFormSubmissionAction $action */
					if ( $action->get_type() == $type &&
					     $action->get_form_id() == $ev['form'] ) {
						// If this action uses the form, then we have to check whether
						// it accepts submissions from anywhere or only from the tested
						// page.
						if ( $action->accepts_submissions_from_any_page() ) {
							$send_form_event = true;
							break;
						}
					  if ( $exp->get_originals_id() == $ev['page'] ) {
							$send_form_event = true;
							break;
						}
					}
				}
				if ( $send_form_event )
					break;
			}
			if ( $send_form_event )
				break;
		}

		if ( !$send_form_event )
			return;

		$url = sprintf(
			NELIOAB_BACKEND_URL . '/site/%s/form',
			NelioABAccountSettings::get_site_id()
		);

		$data = NelioABBackend::build_json_object_with_credentials( $ev );
		$data['timeout'] = 50;

		for ( $attemp=0; $attemp < 5; ++$attemp ) {
			try {
				NelioABBackend::remote_post_raw( $url, $data );
				break;
			}
			catch ( Exception $e ) {
				// If the form submission event could not be sent, it may be that's
				// because there is no more quota available
				if ( $e->getCode() == NelioABErrCodes::NO_MORE_QUOTA ) {
					// If that was the case, simply leave
					break;
				}
				// If there was another error... we just keep trying (attemp) up to 5
				// times.
			}
		}

	}


	/**
	 * PHPDOC
	 *
	 * @param WP_Post $product PHPDOC
	 *
	 * @return WP_Post PHPDOC
	 *
	 * @since PHPDOC
	 */
	function wc_make_alternative_product_purchasable( $product ) {
		if ( get_post_meta( $product->ID, '_is_nelioab_alternative' ) )
			$product->post_status = 'publish';
		return $product;
	}

}//NelioABAlternativeExperimentController

