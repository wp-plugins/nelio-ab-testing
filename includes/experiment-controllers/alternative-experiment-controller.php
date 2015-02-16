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


class NelioABAlternativeExperimentController {

	private $alternative_post;
	private $are_comments_from_original_loaded;

	private $actual_theme;
	private $original_theme;

	private $applied_headlines;

	public function __construct() {
		$this->actual_theme = false;
		$this->original_theme = false;
		$this->applied_headlines = array();
	}

	public function hook_to_wordpress() {
		$this->alternative_post = null;
		$this->are_comments_from_original_loaded = false;

		global $nelioab_controller;
		if ( 'nelioab_load_alt' == $nelioab_controller->is_alternative_content_loading_required() ) {
			// If we are accessing a page with the "nelioab_load_alt" POST param set,
			// then we have to load an alternative. This may be because of a THEME ALT EXP
			// or a PAGE/POST ALT EXP. Whichever the case is, we make some hooks to load the
			// alternative content.
			// Please not that for THEME ALT EXPs, the required hooks are place inside the
			// class NelioABController. This is because this construct function is hooked to
			// the INIT filter, and the theme functions must be hooked before.

			// Page/Post alt exp related
			add_filter( 'wp', array( &$this, 'do_late_hooks' ) );

			add_filter( 'posts_results',     array( &$this, 'posts_results_intercept' ) );
			add_filter( 'the_posts',         array( &$this, 'the_posts_intercept' ) );
			add_filter( 'get_post_metadata', array( &$this, 'load_proper_page_template' ), 10, 4 );

			add_action( 'pre_get_comments',    array( &$this, 'load_comments_from_original' ) );
			add_filter( 'comments_array',      array( &$this, 'prepare_comments_form' ) );
			add_filter( 'get_comments_number', array( &$this, 'load_comments_number_from_original' ) );

			add_filter( 'post_link', array( &$this, 'use_originals_post_link' ) );
			add_filter( 'page_link', array( &$this, 'use_originals_post_link' ) );

			add_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
			add_action( 'wp_get_nav_menu_items', array( &$this, 'highlight_current_menu_option' ), 11 );

			add_action( 'wp_footer', array( &$this, 'add_list_of_applied_headlines' ) );
			add_action( 'wp_footer', array( &$this, 'include_css_alternative_fragments_if_any' ) );

			/**
			 * Headline Experiments modify TITLE, FEATURED IMAGE and EXCERPT.
			 * Let's add the three filters!
			 */
			if ( NelioABSettings::make_site_consistent() ) {
				add_filter( 'the_title',         array( &$this, 'get_consistent_title' ), 10, 2 );
				add_filter( 'get_the_excerpt',   array( &$this, 'get_consistent_excerpt' ), 10 );
				add_filter( 'the_content',       array( &$this, 'get_consistent_content' ), 10 );
				add_filter( 'get_post_metadata', array( &$this, 'get_consistent_featured_image' ), 10, 4 );
			}
			else {
				add_filter( 'the_title',         array( &$this, 'replace_headline_title' ), 10, 2 );
				add_filter( 'get_the_excerpt',   array( &$this, 'replace_headline_excerpt' ), 10 );
				add_filter( 'get_post_metadata', array( &$this, 'fix_headline_featured_image' ), 10, 4 );
			}

			/**
			 * Support with other plugins.
			 * Compatibility tweaks for testing OptimizePress pages.
			 */
			require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
			if ( NelioABOptimizePressSupport::is_optimize_press_active() ) {
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
		}
		// If we need to load an alternative to have a consistent site
		elseif ( 'nelioab_load_consistent_version' == $nelioab_controller->is_alternative_content_loading_required() ) {
			add_filter( 'the_title',         array( &$this, 'get_consistent_title' ), 10, 2 );
			add_filter( 'get_the_excerpt',   array( &$this, 'get_consistent_excerpt' ), 10 );
			add_filter( 'the_content',       array( &$this, 'get_consistent_content' ), 10 );
			add_filter( 'get_post_metadata', array( &$this, 'get_consistent_featured_image' ), 10, 4 );
			add_action( 'wp_footer',         array( &$this, 'add_list_of_applied_headlines' ) );
		}
		else {
			/**
			 * Support with other plugins.
			 * Compatibility tweaks for preventing autotriggering of Google Tag Manager
			 */
			require_once( NELIOAB_UTILS_DIR . '/dtgtm4wp-support.php' );
			NelioABDtGtm4WpSupport::nelioab_tweak_dtgtm4wp();
		}

		// Make sure that the title is replaced everywhere
		add_filter( 'wp_title',  array( &$this, 'fix_title_for_landing_page' ), 10, 2 );

		/**
		 *  Hooks for Gravity Forms and Contact Form 7
		 */
		// Monitoring submissions:
		add_action( 'gform_after_submission', array( &$this, 'track_gf_submission' ),  10, 2 );
		add_action( 'wpcf7_submit',           array( &$this, 'track_cf7_submission' ), 10, 2 );
	}

	public function do_late_hooks( $wp = false ) {
		global $nelioab_controller;
		add_filter( 'option_page_on_front', array( &$nelioab_controller, 'fix_page_on_front' ) );
	}

	public function get_consistent_title( $title, $id = NULL ) {
		if ( empty( $id ) )
			return $title;
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
		return $this->replace_headline_title( $title, $id );
	}

	public function get_consistent_excerpt( $excerpt ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $excerpt;
		global $nelioab_controller;
		if ( $nelioab_controller->url_or_front_page_to_postid(
		     $nelioab_controller->get_current_url() ) == $post->ID ) {
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
		return $this->replace_headline_excerpt( $excerpt );
	}

	public function get_consistent_content( $content ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $excerpt;
		global $nelioab_controller;
		if ( $nelioab_controller->url_or_front_page_to_postid(
		     $nelioab_controller->get_current_url() ) == $post->ID ) {
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
		return $this->fix_headline_featured_image( $value, $object_id, $meta_key, $single );
	}

	public function load_proper_page_template( $value, $post_id = 0, $meta_key = '', $single = false ) {
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

	public function sync_cookies() {
		global $NELIOAB_COOKIES;

		// Check whether new cookies have to be created:
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			// Prepare COOKIES for PAGE/POST ALT EXPS
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				$aux = $this->get_post_alternative( $exp->get_originals_id() );
			}
			elseif ( $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP ) {
				$aux = $this->get_headline_experiment_and_alternative( $exp->get_originals_id() );
			}
		}
		// Prepare COOKIES for GLOBAL ALT EXP (THEMES and CSS)
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::MENU_ALT_EXP );
		foreach ( wp_get_nav_menus() as $menu )
			$aux = NelioABUser::get_alternative_for_menu_alt_exp( $menu->term_id );

		// Return the new COOKIES
		return $NELIOAB_COOKIES;
	}

	public function check_requires_an_alternative( $url ) {
		if ( $this->get_global_alt_exp_running() !== false )
			return 'LOAD_ALTERNATIVE';
		if ( $this->is_there_a_running_headline_experiment() )
			return 'LOAD_ALTERNATIVE';
		global $nelioab_controller;
		$post_id = $nelioab_controller->url_or_front_page_to_postid( $url );
		if ( $this->is_post_in_a_post_alt_exp( $post_id ) )
			return 'LOAD_ALTERNATIVE';
		if ( NelioABSettings::make_site_consistent() )
			if ( $this->are_there_ab_experiments_running() )
				return 'LOAD_CONSISTENT_VERSION';
		return 'DO_NOT_LOAD_ANYTHING';
	}

	private function are_there_ab_experiments_running() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() != NelioABExperiment::HEATMAP_EXP )
				return true;
		return false;
	}

	private function get_global_alt_exp_running() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CSS_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
					return $exp;
		return false;
	}

	private function is_there_a_global_alt_exp_with_origin( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CSS_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
					return true;
		return false;
	}

	private function is_there_a_global_alt_exp_with_goal( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() != NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() != NelioABExperiment::CSS_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::WIDGET_ALT_EXP ||
					 $exp->get_type() == NelioABExperiment::MENU_ALT_EXP )
				continue;
			foreach( $exp->get_goals() as $goal ) {
				if ( $goal->get_kind() != NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL )
					continue;
				if ( $goal->includes_internal_page( $post_id ) )
					return true;
			}
		}
		return false;
	}

	private function get_actual_theme() {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		if ( !$this->actual_theme ) {
			remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
			remove_filter( 'template',   array( &$this, 'modify_template' ) );
			$this->actual_theme = NelioABUser::get_assigned_theme();
			$this->original_theme = wp_get_theme();
			add_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
			add_filter( 'template',   array( &$this, 'modify_template' ) );
		}
		return $this->actual_theme;
	}

	public function modify_stylesheet( $stylesheet ) {
		// If I'm not loading an alternative, I hooked for no reason...
		global $nelioab_controller;
		if ( !$nelioab_controller->is_alternative_content_loading_required() ) {
			remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
			return $stylesheet;
		}

		// WARNING: I check whether the function 'wp_get_current_user" exists,
		// because the latter depends on this one and, sometimes, this one is
		// not available, generating a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || nelioab_can_user_manage_plugin() )
			return $stylesheet;
		$theme = $this->get_actual_theme();
		return $theme['Stylesheet'];
	}

	public function modify_template( $template ) {
		// If I'm not loading an alternative, I hooked for no reason...
		global $nelioab_controller;
		if ( !$nelioab_controller->is_alternative_content_loading_required() ) {
			remove_filter( 'stylesheet', array( &$this, 'modify_template' ) );
			return $template;
		}

		// WARNING: I check whether the function 'wp_get_current_user" exists,
		// because the latter depends on this one and, sometimes, this one is
		// not available, generating a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || nelioab_can_user_manage_plugin() )
			return $template;
		$theme = $this->get_actual_theme();
		return $theme['Template'];
	}

	public function show_the_appropriate_menu( $items, $menu, $args ) {
		remove_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
		$alt_menu = $this->get_menu_alternative( $menu->term_id );
		if ( is_nav_menu( $alt_menu ) )
			$items = wp_get_nav_menu_items( $alt_menu, $args );
		add_action( 'wp_get_nav_menu_items', array( &$this, 'show_the_appropriate_menu' ), 10, 3 );
		return $items;
	}

	public function show_the_appropriate_widgets( $all_widgets ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
		$alt = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		if ( $alt )
			return $this->fix_widgets_for_widget_exp( $all_widgets, $alt );

		$alt = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $alt )
			return $this->fix_widgets_for_theme_exp( $all_widgets );

		return $this->filter_original_widgets( $all_widgets );
	}

	private function fix_widgets_for_widget_exp( $all_widgets, $alt ) {
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

	private function is_widget_original( $widget, $list ) {
		foreach( $list as $w => $aux )
			if ( $w == $widget )
				return false;
		return true;
	}

	private function is_widget_enabled( $widget, $list, $exp, $alt ) {
		foreach( $list as $w => $aux )
			if ( $widget == $w && $aux['exp'] == $exp && $aux['alt'] == $alt )
				return true;
		return false;
	}

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

	public function highlight_current_menu_option( $items ) {
		foreach ( $items as $item ) {
			$id = $item->object_id;
			$item->object_id = $this->get_post_alternative( $id );
		}
		return $items;
	}

	public function posts_results_intercept( $posts ) {
		if ( count( $posts ) != 1 )
			return $posts;

		$post = $posts[0];
		if ( $this->is_post_in_a_post_alt_exp( $post->ID ) ) {
			remove_filter( 'posts_results', array( $this, 'posts_results_intercept' ) );
			$alt = get_post( $this->get_post_alternative( $post->ID ) );
			if ( $alt )
				$this->alternative_post = $alt;
		}

		return $posts;
	}

	public function the_posts_intercept( $posts ) {
		if ( ! is_null( $this->alternative_post ) ) {
			$result = array( $this->alternative_post );
			$this->alternative_post = null;
			return $result;
		}
		else {
			$this->alternative_post = null;
			return $posts;
		}
	}

	public function include_css_alternative_fragments_if_any() {
		if ( !is_main_query() ) return;

		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$alt = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( !$alt )
			return;

		echo $this->prepare_css_as_js( $alt->get_value() );
	}

	public function get_external_page_accessed_action_urls() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

		$result = array( 'ae_hrefs' => array(), 'regex_hrefs' => array() );

		// LOOK FOR A PAGE/POST EXPERIMENT IN WHICH THIS POST IS BEING TESTED OR
		// FOR A GLOBAL EXPERIMENT THAT APPLIES TO THIS PAGE
		global $post;
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			foreach( $exp->get_goals() as $goal ) {
				if ( $goal->get_kind() != NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL )
					continue;
				foreach( $goal->get_actions() as $action ) {
					$type = $action->get_type();
					if ( $type == NelioABAction::PAGE_ACCESSED ||
					     $type == NelioABAction::POST_ACCESSED ||
					     $type == NelioABAction::EXTERNAL_PAGE_ACCESSED ) {
						if ( $action->is_external() ) {
							$ae_url = $action->get_reference();
							$ae_url = preg_replace( '/^https:\/\//', 'http://', $ae_url );
							$ae_url = preg_replace( '/\/$/', '', $ae_url );
							$regex_url = $action->get_regex_reference4js();
							if ( !in_array( $ae_url, $result['ae_hrefs'] ) ) {
								array_push( $result['ae_hrefs'], $ae_url );
								array_push( $result['regex_hrefs'], $regex_url );
							}
						}
					}
				}
			}
		}

		return $result;
	}

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

	public function use_originals_post_link( $permalink, $id = 0, $leavename = false ) {
		$perm_id = url_to_postid( $permalink );
		if ( $this->is_post_alternative( $perm_id ) ) {
			$ori_id = $this->get_original_related_to( $perm_id );
			$permalink = get_permalink( $ori_id );
		}
		return $permalink;
	}

	public function prepare_comments_form( $comments ) {
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

	public function load_comments_number_from_original( $comments_number ) {
		global $post;
		$id = $post->ID;
		if ( !$this->is_post_alternative( $id ) )
			return $comments_number;
		$ori_id = $this->get_original_related_to( $post->ID );
		$aux    = get_post( $ori_id, ARRAY_A );
		return $aux['comment_count'];
	}

	public function get_list_of_click_element_actions( $nav ) {
		$result = array();
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		$relevant_exps = array();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP )
				continue;
			if ( $exp->is_global() ) {
				if ( in_array( $nav['currentId'], $exp->get_origins() ) ||
				     in_array( -1, $exp->get_origins() ) )
					array_push( $relevant_exps, $exp );
			}
			else {
				if ( $exp->get_originals_id() == $nav['currentId'] )
					array_push( $relevant_exps, $exp );
			}
		}
		foreach ( $relevant_exps as $exp ) {
			foreach ( $exp->get_goals() as $goal ) {
				foreach ( $goal->get_actions() as $action ) {
					if ( $action->get_type() == NelioABAction::CLICK_ELEMENT ) {
						$sec = NelioABAccountSettings::get_customer_id() .
							NelioABAccountSettings::get_site_id() .
							$action->get_id() .
							$nav['currentId'] .
							$nav['currentActualId'] .
							$nav['activeCss'] .
							$nav['activeTheme'] .
							$nav['activeWidget'] .
							NelioABAccountSettings::get_reg_num();
						$sec = strtolower( md5( $sec ) );
						$ce = array(
								'id'    => $action->get_id(),
								'mode'  => $action->get_mode(),
								'value' => strtolower( $action->get_value() ),
								'sec'   => $sec,
							);
						array_push( $result, $ce );
					}
				}
			}
		}
		return $result;
	}



	public function add_list_of_applied_headlines() {
		if ( NelioABSettings::get_headlines_quota_mode() == NelioABSettings::HEADLINES_QUOTA_MODE_ON_FRONT_PAGE ) {
			global $nelioab_controller;
			$url = $nelioab_controller->get_current_url();
			$current_post_id = $nelioab_controller->url_or_front_page_to_postid( $url );
			$front_page_id = nelioab_get_page_on_front();
			if ( $front_page_id == 0 )
				$front_page_id = NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS;
			if ( $front_page_id != $current_post_id )
				$this->applied_headlines = array();
		}

		$headlines = array();
		foreach ( $this->applied_headlines as $hl )
			array_push( $headlines, implode( ':', $hl ) );
		$headlines = implode( ',', $headlines ); ?>
		<script type="text/javascript">
		/* <![CDATA[ */
		NelioABParams.sync.headlines = {"list":<?php
			echo json_encode( $headlines );
			if ( count( $this->applied_headlines ) > 0 ) {
				$aux = NelioABAccountSettings::get_customer_id() .
					NelioABAccountSettings::get_site_id() .
					$headlines .
					NelioABAccountSettings::get_reg_num();
				$sec = strtolower( md5( $aux ) );
				?>,"sec":<?php echo json_encode( $sec );
			} ?>};
		/* ]]> */
		</script><?php
	}

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

	public function is_post_in_a_post_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				if ( $exp->get_originals_id() == $post_id )
					return true;
			}
		}
		return false;
	}

	public function is_post_in_a_headline_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP &&
			     $exp->get_originals_id() == $post_id )
				return true;
		}
		return false;
	}

	private function is_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				foreach ( $exp->get_alternatives() as $alt )
					if ( $alt->get_value() == $post_id )
						return true;
			}
		}
		return false;
	}

	public function get_original_related_to( $alt_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				foreach ( $exp->get_alternatives() as $alt )
					if ( $alt->get_value() == $alt_id )
						return $exp->get_originals_id();
			}
		}
		// If it is not an alternative, we return the same ID
		return $alt_id;
	}

	private function is_goal_in_some_page_or_post_experiment( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {

				foreach( $exp->get_goals() as $goal ) {
					if ( $goal->get_kind() != NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL )
						continue;
					if ( $goal->includes_internal_page( $post_id ) )
						return true;
				}
			}
		}
		// If it is not the goal of any experiment, return false
		return false;
	}

	public function get_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		return NelioABUser::get_alternative_for_post_alt_exp( $post_id );
	}

	public function get_menu_alternative( $menu_id ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$alt = NelioABUser::get_alternative_for_menu_alt_exp( $menu_id );
		if ( $alt )
			return $alt->get_value();
		else
			return false;
	}

	public function preview_css() {
		$css_id = '';
		if ( isset( $_GET['nelioab_preview_css'] ) )
			$css_id = $_GET['nelioab_preview_css'];
		$css = get_option( 'nelioab_css_' . $css_id, false );
		echo $this->prepare_css_as_js( $css );
	}

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

	public function is_there_a_running_headline_experiment() {
		$aux = NelioABExperimentsManager::get_running_headline_experiments_from_cache();
		return count( $aux ) > 0;
	}

	public function replace_headline_title( $title, $id = NULL ) {
		if ( empty( $id ) )
			return $title;
		$headline_data = $this->get_headline_experiment_and_alternative( $id );
		if ( $headline_data ) {
			$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
			$alt = $headline_data['alt'];
			return $alt->get_name();
		}
		return $title;
	}

	public function replace_headline_excerpt( $excerpt ) {
		// This function can be tricky, because the global variable
		// post might not be properly set by some plugins...
		global $post;
		if ( !$post )
			return $excerpt;
		global $nelioab_controller;
		if ( $nelioab_controller->url_or_front_page_to_postid(
		     $nelioab_controller->get_current_url() ) == $post->ID ) {
			return $excerpt;
		}
		$headline_data = $this->get_headline_experiment_and_alternative( $post->ID );
		if ( $headline_data ) {
			$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
			$alt = $headline_data['alt'];
			$value = $alt->get_value();
			// This first IF is a safeguard...
			if ( is_array( $value ) && isset( $value['excerpt'] ) && !empty( $value['excerpt'] ) ) {
				return $value['excerpt'];
			}
		}
		return $excerpt;
	}

	public function fix_headline_featured_image( $value, $object_id, $meta_key, $single ) {
		if ( '_thumbnail_id' == $meta_key ) {
			$headline_data = $this->get_headline_experiment_and_alternative( $object_id );
			if ( $headline_data ) {
				$this->add_active_headline_experiment( $headline_data['exp'], $headline_data['alt'] );
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

	public function get_headline_experiment_and_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		return NelioABUser::get_alternative_for_headline_alt_exp( $post_id );
	}

	public function prepare_navigation_object( $current_id, $referer_url ) {
		// PREPARING DATA
		// ---------------------------------
		global $nelioab_controller;
		$referer_id = $nelioab_controller->url_or_front_page_to_postid( $referer_url );

		// Checking if the source page was an alternative
		$referer_actual_id = $referer_id;
		if ( NelioABController::UNKNOWN_PAGE_ID_FOR_NAVIGATION == $referer_actual_id ) {
			if ( strlen( $referer_url ) === 0 ) {
				$referer_id = NelioABController::NAVIGATION_ORIGIN_IS_UNKNOWN;
				$referer_actual_id = NelioABController::NAVIGATION_ORIGIN_IS_UNKNOWN;
			}
			else {
				$referer_id = NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE;
				$referer_actual_id = NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE;
			}
		}
		elseif ( $this->is_post_in_a_post_alt_exp( $referer_id ) ) {
			$referer_actual_id = $this->get_post_alternative( $referer_id );
		}
		elseif ( $this->is_post_in_a_headline_alt_exp( $referer_id ) ) {
			$headline_data = $this->get_headline_experiment_and_alternative( $referer_id );
			$val = $headline_data['alt']->get_value();
			$referer_actual_id = $val['id'];
		}

		// PREPARING THE REST OF THE DATA
		// Making sure is "the original" (if any)
		$current_id = $this->get_original_related_to( $current_id );

		// Checking if the destination page was an alternative
		$current_actual_id = $current_id;
		if ( $this->is_post_in_a_post_alt_exp( $current_id ) ) {
			$current_actual_id = $this->get_post_alternative( $current_id );
		}
		elseif ( $this->is_post_in_a_headline_alt_exp( $current_id ) ) {
			$headline_data = $this->get_headline_experiment_and_alternative( $current_id );
			$val = $headline_data['alt']->get_value();
			$current_actual_id = $val['id'];
		}

		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$nav = array(
			'referer'           => '' . $referer_url,
			'refererId'         => '' . $referer_id,
			'refererActualId'   => '' . $referer_actual_id,
			'currentId'         => '' . $current_id,
			'currentActualId'   => '' . $current_actual_id,
			'isRelevant'        => $this->is_relevant( $current_id )
		);

		$the_theme = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $the_theme )
			$nav['activeTheme'] = '' . $the_theme->get_id();
		else
			$nav['activeTheme'] = '';

		$the_css = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( $the_css )
			$nav['activeCss'] = '' . $the_css->get_id();
		else
			$nav['activeCss'] = '';

		$the_widget = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		if ( $the_widget )
			$nav['activeWidget'] = '' . $the_widget->get_id();
		else
			$nav['activeWidget'] = '';

		$the_menu = '';
		foreach ( wp_get_nav_menus() as $aux ) {
			$alt = NelioABUser::get_alternative_for_menu_alt_exp( $aux->term_id );
			if ( $alt ) $the_menu = $alt->get_id();
			if ( $the_menu ) break;
		}
		$nav['activeMenu'] = '' . $the_menu;

		if ( NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE == $nav['currentId'] )
			$nav['currentId'] = NelioABController::WRONG_NAVIGATION_DESTINATION;
		if ( NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE == $nav['currentActualId'] )
			$nav['currentActualId'] = NelioABController::WRONG_NAVIGATION_DESTINATION;

		// Preparing security codes
		$pre = NelioABAccountSettings::get_customer_id() . NelioABAccountSettings::get_site_id();
		$post = $nav['activeCss'] . $nav['activeMenu'] . $nav['activeTheme'] . $nav['activeWidget'] .
			NelioABAccountSettings::get_reg_num();

		$nav['rsec'] = strtolower( md5(
			$pre . $referer_id . $referer_actual_id . $current_id . $current_actual_id . $post ) );
		$nav['osec'] = strtolower( md5(
			$pre . $current_id . $current_actual_id . $post ) );

		return $nav;
	}

	private function is_relevant( $id ) {
		$is_relevant = false;
		if ( $this->is_there_a_global_alt_exp_with_origin( $id ) ||
		     $this->is_there_a_global_alt_exp_with_goal( $id ) ||
		     $this->is_goal_in_some_page_or_post_experiment( $id ) ||
		     $this->is_post_in_a_post_alt_exp( $id ) ||
		     $this->is_post_in_a_headline_alt_exp( $id ) )
			$is_relevant = true;
		return $is_relevant;
	}


	public function track_gf_submission( $entry, $form ) {
		$type = NelioABAction::SUBMIT_GRAVITY_FORM;
		$this->send_form_action_if_required( $type, $form['id'] );
	}


	public function track_cf7_submission( $form, $result ) {
		$status = $result['status'];
		$type = NelioABAction::SUBMIT_CF7_FORM;
		if ( $status == 'mail_sent' || $status == 'demo_mode' )
			$this->send_form_action_if_required( $type, $form->id() );
	}


	private function send_form_action_if_required( $type, $form_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/actions/form-submission-action.php' );
		require_once( NELIOAB_MODELS_DIR . '/goals/actions/action.php' );
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );

		$kap = NelioABFormSubmissionAction::type_to_kind_and_plugin( $type );
		if ( !$kap )
			return;

		if ( !isset( $_POST['nelioab_form_current_url'] ) )
			return;

		if ( strlen( trim( $_POST['nelioab_form_current_url'] ) ) === 0 )
			return;

		// Constructing FORM EVENT object:
		$page_url = rtrim( json_decode( urldecode( $_POST['nelioab_form_current_url'] ) ), '/' );
		global $nelioab_controller;
		$page = $nelioab_controller->url_or_front_page_to_postid( $page_url );
		$actual_page = $this->get_post_alternative( $page );

		$ev = array(
			'kind'       => $kap['kind'],
			'form'       => $form_id,
			'plugin'     => $kap['plugin'],
			'page'       => $page,
			'actualPage' => $actual_page,
			'user'       => NelioABUser::get_id(),
		);

		$the_theme = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $the_theme )
			$ev['activeTheme'] = $the_theme->get_id();

		$the_css = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( $the_css )
			$ev['activeCSS'] = $the_css->get_id();

		$the_widget = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::WIDGET_ALT_EXP );
		if ( $the_widget )
			$ev['activeWidget'] = '' . $the_widget->get_id();

		$the_menu = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::MENU_ALT_EXP );
		if ( $the_menu )
			$ev['activeMenu'] = $the_menu->get_id();


		// Check if there's one experiment at least with a form submission action,
		// which corresponds to the given form, then send the event. Otherwise,
		// get out!
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$send_form_event = false;
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			foreach ( $exp->get_goals() as $goal ) {
				foreach ( $goal->get_actions() as $action ) {
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
				$result = NelioABBackend::remote_post_raw( $url, $data );
				NelioABAccountSettings::set_has_quota_left( true );
				break;
			}
			catch ( Exception $e ) {
				// If the form submission event could not be sent, it may be that's
				// because there is no more quota available
				if ( $e->getCode() == NelioABErrCodes::NO_MORE_QUOTA ) {
					NelioABAccountSettings::set_has_quota_left( false );
					break;
				}
				// If there was another error... we just keep trying (attemp) up to 5
				// times.
			}
		}

	}

}//NelioABAlternativeExperimentController

