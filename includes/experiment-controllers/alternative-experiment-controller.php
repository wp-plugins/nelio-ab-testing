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


require_once( NELIOAB_MODELS_DIR . '/settings.php' );
class NelioABAlternativeExperimentController {

	private $alternative_post;
	private $are_comments_from_original_loaded;

	private $original_theme;

	public function __construct() {
		$this->original_theme = false;
	}

	public function hook_to_wordpress() {
		$this->alternative_post = null;
		$this->are_comments_from_original_loaded = false;

		if ( isset( $_POST['nelioab_load_alt'] ) ) {
			// If we are accessing a page with the "nelioab_load_alt" POST param set,
			// then we have to load an alternative. This may be because of a THEME ALT EXP
			// or a PAGE/POST ALT EXP. Whichever the case is, we make some hooks to load the
			// alternative content.
			// Please not that for THEME ALT EXPs, the required hooks are place inside the
			// class NelioABController. This is because this construct function is hooked to
			// the INIT filter, and the theme functions must be hooked before.

			// Page/Post alt exp related
			add_filter( 'posts_results',       array( &$this, 'posts_results_intercept' ) );
			add_filter( 'the_posts',           array( &$this, 'the_posts_intercept' ) );
			add_action( 'pre_get_comments',    array( &$this, 'load_comments_from_original' ) );
			add_filter( 'comments_array',      array( &$this, 'prepare_comments_form' ) );
			add_filter( 'get_comments_number', array( &$this, 'load_comments_number_from_original' ) );
			add_filter( 'post_link',           array( &$this, 'use_originals_post_link' ) );
			add_filter( 'page_link',           array( &$this, 'use_originals_post_link' ) );
			add_action( 'wp_enqueue_scripts',  array( &$this, 'load_nelioab_scripts_for_alt' ) );
			add_action( 'wp_enqueue_scripts',  array( &$this, 'include_css_alternative_fragments_if_any' ) );
			add_filter( 'the_content',         array( &$this, 'print_script_for_external_links' ) );
			add_filter( 'get_post_metadata',   array( &$this, 'load_proper_page_template' ), 10, 4 );

			// Support with other plugins
			require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
			if ( NelioABOptimizePressSupport::is_optimize_press_active() )
				add_filter( 'template_include',
					array( 'NelioABOptimizePressSupport', 'op_template_include' ) );
		}
		else {
			// If the "nelioab_load_alt" POST param is not set, we have to return the
			// page with the JS file that is able to load an alternative and do all
			// the required stuff
			add_action( 'wp_enqueue_scripts', array( &$this, 'load_nelioab_scripts' ) );
		}

		// Make the script for managing alternatives available everywhere

		// Make sure that the title is replaced everywhere
		add_filter( 'the_title', array( &$this, 'change_title_on_abtesting' ),  10, 2 );
		add_filter( 'wp_title',  array( &$this, 'fix_title_for_landing_page' ), 10, 2 );
		add_action( 'wp_head',   array( &$this, 'add_js_to_replace_titles' ) );
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

	public function update_current_winner_for_running_experiments() {
		$now = time();
		$last_update = get_option( 'nelioab_last_winners_update', 0 );
		if ( $now - $last_update < 1800 )
			return;
		update_option( 'nelioab_last_winners_update', $now );

		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			$exp->update_winning_alternative_from_appengine();
		NelioABExperimentsManager::update_running_experiments_cache( true, $running_exps );
	}

	public function sync_cookies() {
		global $NELIOAB_COOKIES;

		// Check whether new cookies have to be created:
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			// Prepare COOKIES for PAGE/POST ALT EXPS
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::TITLE_ALT_EXP ) {
				$aux = NelioABUser::get_alternative_for_post_alt_exp( $exp->get_originals_id() );
			}
		}
		// Prepare COOKIES for GLOBAL ALT EXP (THEMES and CSS)
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		$aux = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );

		// Return the new COOKIES
		return $NELIOAB_COOKIES;
	}

	public function check_requires_an_alternative( $url ) {
		if ( $this->is_there_a_running_global_alt_exp() )
			return 'LOAD_ALT';
		global $nelioab_controller;
		$post_id = $nelioab_controller->url_or_front_page_to_postid( $url );
		if ( $this->is_post_in_a_post_alt_exp( $post_id ) )
			return 'LOAD_ALT';
		else
			return 'DO_NOT_LOAD_ALT';
	}

	private function is_there_a_running_global_alt_exp() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CSS_ALT_EXP )
					return true;
		return false;
	}

	private function is_there_a_global_alt_exp_with_origin( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::CSS_ALT_EXP )
					return true;
		return false;
	}

	private function is_there_a_global_alt_exp_with_goal( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() != NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() != NelioABExperiment::CSS_ALT_EXP )
				continue;
			foreach( $exp->get_goals() as $goal ) {
				if ( $goal->get_kind() != NelioABGoal::PAGE_ACCESSED_GOAL )
					continue;
				if ( $goal->includes_internal_page( $post_id ) )
					return true;
			}
		}
		return false;
	}

	public function modify_stylesheet( $stylesheet ) {
		// WARNING: I check whether the function exists, because there are some (random)
		// times in which calling "current_user_can" results in a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || current_user_can( 'delete_users' ) )
			return;
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		remove_filter( 'template',   array( &$this, 'modify_template' ) );
		$theme = NelioABUser::get_assigned_theme();
		add_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		add_filter( 'template',   array( &$this, 'modify_template' ) );
		return $theme['Stylesheet'];
	}

	public function modify_template( $template ) {
		// WARNING: I check whether the function exists, because there are some (random)
		// times in which calling "current_user_can" results in a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || current_user_can( 'delete_users' ) )
			return;
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		remove_filter( 'template',   array( &$this, 'modify_template' ) );
		$theme = NelioABUser::get_assigned_theme();
		if ( !$this->original_theme )
			$this->original_theme = wp_get_theme();
		add_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		add_filter( 'template',   array( &$this, 'modify_template' ) );
		return $theme['Template'];
	}

	public function fix_widgets_for_theme( $all_widgets ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$actual_theme = NelioABUser::get_assigned_theme();
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

	public function posts_results_intercept( $posts ) {
		if ( count( $posts ) != 1 )
			return $posts;

		$post = $posts[0];
		if ( $this->is_post_in_a_post_alt_exp( $post->ID ) ) {
			remove_action( 'posts_results', array( $this, 'posts_results_intercept' ) );
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

	public function load_nelioab_scripts() {
		wp_enqueue_script( 'nelioab_alternatives_script_generic',
			NELIOAB_ASSETS_URL . '/js/nelioab-generic.min.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'nelioab_alternatives_script_check',
			NELIOAB_ASSETS_URL . '/js/nelioab-check.min.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'tapas_script',
			NELIOAB_ASSETS_URL . '/js/tapas.min.js?' . NELIOAB_PLUGIN_VERSION );
	}

	public function load_nelioab_scripts_for_alt() {
		wp_enqueue_script( 'nelioab_alternatives_script_generic',
			NELIOAB_ASSETS_URL . '/js/nelioab-generic.min.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'nelioab_alternatives_script_nav',
			NELIOAB_ASSETS_URL . '/js/nelioab-nav.min.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'tapas_script',
			NELIOAB_ASSETS_URL . '/js/tapas.min.js?' . NELIOAB_PLUGIN_VERSION );
	}

	public function include_css_alternative_fragments_if_any() {
		if ( !is_main_query() ) return;
		require_once( NELIOAB_MODELS_DIR . '/user.php' );

		$alt = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( !$alt )
			return;

		echo $this->prepare_css_as_js( $alt->get_value() );
	}

	public function print_script_for_external_links( $content ) {
		if ( !is_main_query() ) return;
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

		// LOOK FOR A PAGE/POST EXPERIMENT IN WHICH THIS POST IS BEING TESTED OR
		// FOR A GLOBAL EXPERIMENT THAT APPLIES TO THIS PAGE
		global $post;
		$relevant_exps = array();
		$original_id = $this->get_original_related_to( $post->ID );
		$is_there_a_global_alt_exp = $this->is_there_a_global_alt_exp_with_origin( $original_id );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			switch( $exp->get_type() ) {
				case NelioABExperiment::POST_ALT_EXP:
				case NelioABExperiment::PAGE_ALT_EXP:
					if ( $exp->get_originals_id() == $original_id )
						array_push( $relevant_exps, $exp );
					break;
				case NelioABExperiment::THEME_ALT_EXP:
				case NelioABExperiment::CSS_ALT_EXP:
					if ( $is_there_a_global_alt_exp )
						array_push( $relevant_exps, $exp );
					break;
			}
		}

		if ( count( $relevant_exps ) == 0 )
			return $content;

		// Retrieve all the external pages
		$hrefs = array();
		foreach( $relevant_exps as $experiment ) {
			foreach( $experiment->get_goals() as $goal ) {
				if ( $goal->get_kind() != NelioABGoal::PAGE_ACCESSED_GOAL )
					continue;
				foreach( $goal->get_pages() as $page ) {
					if ( $page->is_external() ) {
						$url = $page->get_reference();
						$url = str_replace( '"', '', $url );
						// Remove GET params
						if ( !NelioABSettings::match_exact_url_for_external_goals() )
							$url = preg_replace( '/\?.*$/', '', $url );
						// Remove trailing slash
						$url = preg_replace( '/\/+$/', '', $url );
						// Remove https
						$url = preg_replace( '/^https?:\/\//', 'http://', $url );
						$url = '"' . $url . '"';
						if ( !in_array( $url, $hrefs ) )
							array_push( $hrefs, $url );
					}
				}
			}
		}

		if ( count( $hrefs ) == 0 )
			return $content;

		// Print the script
		$hrefs   = implode( ', ', $hrefs );
		$script  = "\n\n";
		$script .= "<script>\n";
		$script .= "jQuery(document).ready( function() {\n";
		$script .= "   var hrefs = [ $hrefs ];\n";
		$script .= "   jQuery('a').click(function() {\n";
		$script .= "      href = jQuery(this).attr('href');\n";
		// Remove GET params
		if ( !NelioABSettings::match_exact_url_for_external_goals() )
			$script .= "      href = href.replace(/\?.*$/, '');\n";
		// Remove trailing slash
		$script .= "      href = href.replace(/\/+$/, '');\n";
		// Remove https
		$script .= "      href = href.replace(/^https?:\/\//, 'http://');\n";
		$script .= "      for ( i=0; i<hrefs.length; ++i ) {\n";
		$script .= "         if ( hrefs[i] == href ) {\n";
		$script .= "            jQuery(this).attr('target','_blank');\n";
		$script .= "            nelioab_nav_to_external_page(jQuery,href);\n";
		$script .= "            break;\n";
		$script .= "         }\n";
		$script .= "      }\n";
		$script .= "   });\n";
		$script .= "});\n";
		$script .= "</script>\n";

		return $content . $script;
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
		if ( !$this->is_post_alternative( $perm_id ) )
			return $permalink;

		$ori_id = $this->get_original_related_to( $perm_id );
		return get_permalink( $ori_id );
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

	public function add_js_to_replace_titles() {
		require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
		?>

		<script type="text/javascript">
		(function($) {
			$(document).ready(function(){
				var theCookies = document.cookie.split(';');
				var substitutions = [];
				for (var i = 1; i <= theCookies.length; ++i) {
					var cookie = theCookies[i];
					if (cookie == undefined)
						continue;
					var cookieName = jQuery.trim( cookie.substr(0, cookie.indexOf('=')) );
					var cookieVal  = jQuery.trim( cookie.substr(cookie.indexOf('=')+1, cookie.length) );

					if (cookieName.indexOf("<?php echo NelioABSettings::cookie_prefix(); ?>title_") == 0) { try {
						var aux  = cookieVal.split(":");
						var exp  = aux[2];
						var post = nelioab_get_cookie_by_name( "<?php echo NelioABSettings::cookie_prefix(); ?>altexp_" + exp );

						var oriTitle = "\t \t \t" + decodeURIComponent(aux[0]) + "\t \t \t";
						var altTitle = decodeURIComponent(aux[1]);
						var regexp   = new RegExp( oriTitle );
						if ( $("*").replaceText( regexp, altTitle ) )
							substitutions.push( { 'exp':exp, 'actual_post':post } );
					} catch (e) {} }
				}
				if ( substitutions.length > 0 ) {
					jQuery.ajax({
						type:  'POST',
						async: true,
						url:   window.location.href,
						data: {
							referer: document.referrer,
							nelioab_send_alt_titles_info: 'true',
							nelioab_cookies: nelioab_get_local_cookies(),
							replaced_title_exps: JSON.stringify( substitutions ),
						},
					});
				}
			});
		})(jQuery);
		</script>

		<?php
	}

	public function send_alt_titles_info() {
		if ( !isset( $_POST['replaced_title_exps'] ) )
			die();

		require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
		if ( !NelioABAccountSettings::has_quota_left() && !NelioABAccountSettings::is_quota_check_required() )
			return;

		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/user.php' );

		global $nelioab_controller;
		$url = $nelioab_controller->get_current_url();
		$current_post_id = $nelioab_controller->url_or_front_page_to_postid( $url );
		$actualDestination = false;

		$replaced_title_exps = json_decode( stripslashes( $_POST['replaced_title_exps'] ) );
		$relevant_title_exps = array();

		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::TITLE_ALT_EXP ) {
				foreach ( $replaced_title_exps as $rte ) {
					if ( $exp->get_id() == $rte->exp &&
					     $exp->get_originals_id() != $current_post_id )
						array_push( $relevant_title_exps, $rte );
				}
			}
		}

		NelioABAccountSettings::set_has_quota_left( true );
		foreach( $relevant_title_exps as $rte ) {
			try {
				$url = sprintf( NELIOAB_BACKEND_URL . '/site/%s/exp/%s/titleview',
					NelioABAccountSettings::get_site_id(),
					$rte->exp );
				$body = array(
					'user'    => '' . NelioABUser::get_id(),
					'postId'  => '' . $rte->actual_post,
				);
				$result = NelioABBackend::remote_post( $url, $body );
			}
			catch ( Exception $e ) {
				if ( $e->getCode() == NelioABErrCodes::NO_MORE_QUOTA ) {
					NelioABAccountSettings::set_has_quota_left( false );
					break;
				}
				// else: bad luck, because navigation is lost
			}
		}

		// Send a regular navigation (if it was not already sent) to control quota
		$nav = $this->prepare_navigation_object( $current_post_id, '', true );
		if ( !$nelioab_controller->is_relevant( $nav ) )
			$nelioab_controller->send_navigation_object( $nav );

		die();
	}

	public function change_title_on_abtesting( $title, $id = -1 ) {
		require_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
		return "\t \t \t$title\t \t \t";
	}

	public function fix_title_for_landing_page( $title, $sep = false ) {
		global $post;
		if ( $this->is_post_alternative( $post->ID ) ) {
			$front_page_id = get_option( 'page_on_front' );
			$ori_id = $this->get_original_related_to( $post->ID );
			if ( $ori_id == $front_page_id ) {
				$title = get_bloginfo( 'name' ) . " $sep ";
			}
		}
		return "$title";
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

	public function is_post_in_a_title_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::TITLE_ALT_EXP &&
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

	private function get_original_related_to( $alt_id ) {
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
		require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {

				foreach( $exp->get_goals() as $goal ) {
					if ( $goal->get_kind() != NelioABGoal::PAGE_ACCESSED_GOAL )
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

	public function is_relevant( $nav ) {
		$src_id  = $nav['origin'];
		$dest_id = $nav['destination'];

		// Checking whether the visited page is relevant for a theme experiment
		$is_there_a_relevant_global_exp = false;
		if ( $this->is_there_a_global_alt_exp_with_origin( $src_id ) ||
		     $this->is_there_a_global_alt_exp_with_goal( $dest_id ) ) {
			$is_there_a_relevant_global_exp = true;
		}

		// IF DEST_ID does not belong to any experiment AND
		//    there is not a GLOBAL experiment running
		// THEN quit
		if ( ! $this->is_goal_in_some_page_or_post_experiment( $dest_id ) &&
		     ! $this->is_post_in_a_post_alt_exp( $dest_id ) &&
		     ! $this->is_post_in_a_title_alt_exp( $dest_id ) &&
		     ! $is_there_a_relevant_global_exp )
			return false;

		return true;
	}

	public function preview_css( $content ) {
		$css_id = '';
		if ( isset( $_GET['nelioab_preview_css'] ) )
			$css_id = $_GET['nelioab_preview_css'];
		$css = get_option( 'nelioab_css_' . $css_id, false );
		return $this->prepare_css_as_js( $css ) .
			"<script>document.getElementsByTagName('head')[0].appendChild(nelioab_cssExpNode);</script>" .
			$content;
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
		$css .= "  nelioab_cssExpNode = document.createElement('style');\n";
		$css .= "  nelioab_cssExpNode.setAttribute('type', 'text/css');\n";
		$css .= "  nelioab_cssExpNode.innerHTML = \"$code\";\n";
		$css .= "</script>\n";

		return $css;
	}

	public function prepare_navigation_object( $dest_id, $referer_url, $is_internal = true ) {
		// PREPARING DATA
		// ---------------------------------
		$referer_url = rtrim( $referer_url, '/' );
		global $nelioab_controller;
		$src_id = $nelioab_controller->url_or_front_page_to_postid( $referer_url );

		// Checking if the source page was an alternative
		$actual_src_id = $src_id;
		if ( $this->is_post_in_a_post_alt_exp( $src_id ) )
			$actual_src_id = $this->get_post_alternative( $src_id );

		// PREPARING THE REST OF THE DATA IF THE NAVIGATION IS...
		// (A) INTERNAL
		if ( $is_internal ) {
			// Making sure is "the original" (if any)
			$dest_id = $this->get_original_related_to( $dest_id );

			// Checking if the destination page was an alternative
			$actual_dest_id = $dest_id;
			if ( $this->is_post_in_a_post_alt_exp( $dest_id ) ||
			     $this->is_post_in_a_title_alt_exp( $dest_id ) )
				$actual_dest_id = $this->get_post_alternative( $actual_dest_id );
		}
		// (B) EXTERNAL
		else {
			$actual_dest_id = $dest_id;
		}

		require_once( NELIOAB_MODELS_DIR . '/user.php' );

		$nav = array(
			'user'              => '' . NelioABUser::get_id(),
			'referer'           => '' . $referer_url,
			'origin'            => '' . $src_id,
			'actualOrigin'      => '' . $actual_src_id,
			'destination'       => '' . $dest_id,
			'actualDestination' => '' . $actual_dest_id );

		$the_theme = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );
		if ( $the_theme )
			$nav['activeTheme'] = '' . $the_theme->get_id();
		$the_css = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::CSS_ALT_EXP );
		if ( $the_css )
			$nav['activeCSS'] = '' . $the_css->get_id();

		return $nav;
	}

}//NelioABAlternativeExperimentController

?>
