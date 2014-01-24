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

	const FRONT_PAGE__YOUR_LATEST_POSTS = -1;

	private $alternative_post;
	private $are_comments_from_original_loaded;

	private $original_theme;

	public function __construct() {
		$this->check_parameters();
		$this->original_theme = false;
	}

	public function hook_to_wordpress() {
		$this->alternative_post = null;
		$this->are_comments_from_original_loaded = false;

		add_action( 'wp_enqueue_scripts', array( &$this, 'load_jquery' ) );

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
			add_filter( 'the_content',         array( &$this, 'print_script_for_external_links' ) );

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

	private function check_parameters() {

		if ( isset( $_POST['nelioab_nav'] ) )
			$this->send_navigation();

		if ( isset( $_POST['nelioab_sync_and_check'] ) ) {
			$cookies  = $this->sync_cookies();
			$load_alt = $this->check_requires_an_alternative();
			$result   = array(
				'cookies'  => $cookies,
				'load_alt' => $load_alt );
			echo json_encode( $result );
			die();
		}

	}

	private function sync_cookies() {
		global $NELIOAB_COOKIES;

		// Check whether new cookies have to be created:
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {

			// Prepare COOKIES for PAGE/POST ALT EXPS
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				$aux = NelioABUser::get_alternative_for_post_alt_exp( $exp->get_original() );
			}

			// Prepare COOKIES for THEME ALT EXPS
			else if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP ) {
				$aux = NelioABUser::get_alternative_for_theme_alt_exp();
			}
		}

		// Return the new COOKIES
		return $NELIOAB_COOKIES;
	}

	private function send_navigation() {
		$dest_post_id = $this->url_or_front_page_to_postid( $_SERVER['HTTP_REFERER'] );
		$referer = '';
		if ( isset( $_POST['referer'] ) )
			$referer = $_POST['referer'];

		if ( isset( $_POST['nelioab_nav_to_external_page'] ) )
			$this->send_navigation_if_required( $_POST['nelioab_nav_to_external_page'], $referer, false );
		else if ( $dest_post_id )
			$this->send_navigation_if_required( $dest_post_id, $referer );

		die();
	}

	private function check_requires_an_alternative() {
		$post_id = $this->url_or_front_page_to_postid( $_SERVER['HTTP_REFERER'] );

		if ( $this->is_there_a_running_theme_alt_exp() )
			return 'LOAD_ALT';

		if ( $this->is_post_in_a_post_alt_exp( $post_id ) )
			return 'LOAD_ALT';
		else
			return 'DO_NOT_LOAD_ALT';
	}

	private function is_there_a_running_theme_alt_exp() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP )
					return true;
		return false;
	}

	private function is_there_a_theme_alt_exp_with_origin( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP )
					return true;
		return false;
	}

	private function is_there_a_theme_alt_exp_with_goal( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() != NelioABExperiment::THEME_ALT_EXP )
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
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		remove_filter( 'template',   array( &$this, 'modify_template' ) );
		$theme = NelioABUser::get_assigned_theme();
		add_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );
		add_filter( 'template',   array( &$this, 'modify_template' ) );
		return $theme['Stylesheet'];
	}

	public function modify_template( $template ) {
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

	public function load_jquery() {
		wp_enqueue_script( 'jquery' );
	}

	public function load_nelioab_scripts() {
		wp_enqueue_script( 'nelioab_alternatives_script_generic',
			NELIOAB_ASSETS_URL . '/js/nelioab-generic.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'nelioab_alternatives_script_check',
			NELIOAB_ASSETS_URL . '/js/nelioab-check.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'tapas_script',
			NELIOAB_ASSETS_URL . '/js/tapas.js?' . NELIOAB_PLUGIN_VERSION );
	}

	public function load_nelioab_scripts_for_alt() {
		wp_enqueue_script( 'nelioab_alternatives_script_generic',
			NELIOAB_ASSETS_URL . '/js/nelioab-generic.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'nelioab_alternatives_script_nav',
			NELIOAB_ASSETS_URL . '/js/nelioab-nav.js?' . NELIOAB_PLUGIN_VERSION );
		wp_enqueue_script( 'tapas_script',
			NELIOAB_ASSETS_URL . '/js/tapas.js?' . NELIOAB_PLUGIN_VERSION );
	}

	public function print_script_for_external_links( $content ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

		// Retrieve the experiment for which this page is an alternative
		global $post;
		$original_id = $this->get_original_related_to( $post->ID );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		$experiment   = NUll;
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				if ( $exp->get_original() == $original_id ) {
					$experiment = $exp;
					break;
				}
			}
		}
		if ( !$experiment )
			return $content;

		// Retrieve all the external pages
		$hrefs = array();
		foreach( $experiment->get_goals() as $goal ) {
			if ( $goal->get_kind() == NelioABGoal::PAGE_ACCESSED_GOAL ) {
				foreach( $goal->get_pages() as $page ) {
					if ( $page->is_external() ) {
						$url = $page->get_reference();
						$url = str_replace( '"', '', $url );
						array_push( $hrefs, '"' . $url . '"' );
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
		$script .= "jQuery(document).ready(function() {\n";
		$script .= "   var hrefs = [ $hrefs ];\n";
		$script .= "   for ( i=0; i<hrefs.length; ++i ) {\n";
		$script .= "      nelioab_prepare_outlinks(jQuery, hrefs[i]);\n";
		$script .= "   }\n";
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
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		?>

		<script type="text/javascript">
		(function($) {
			$(document).ready(function(){
				var theCookies = document.cookie.split(';');
				for (var i = 1; i <= theCookies.length; ++i) {
					var cookie = theCookies[i];
					if (cookie == undefined)
						continue;
					var cookieName = cookie.substr(0, cookie.indexOf('=')).trim();
					var cookieVal  = cookie.substr(cookie.indexOf('=')+1, cookie.length).trim();

					if (cookieName.indexOf("<?php echo NelioABSettings::cookie_prefix(); ?>title_") == 0) { try {
						aux      = cookieVal.split(":");
						oriTitle = "\t \t \t" + decodeURIComponent(aux[0]) + "\t \t \t";
						altTitle = decodeURIComponent(aux[1]);
						$("*").replaceText(oriTitle, altTitle);
					} catch (e) {} }
				}
			});
		})(jQuery);
		</script>

		<?php
	}

	public function change_title_on_abtesting( $title, $id = -1 ) {
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		return "\t \t \t$title\t \t \t";
	}

	public function fix_title_for_landing_page( $title, $sep ) {
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

	private function is_post_in_a_post_alt_exp( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
				if ( $exp->get_original() == $post_id )
					return true;
			}
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
						return $exp->get_original();
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

	private function get_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		return NelioABUser::get_alternative_for_post_alt_exp( $post_id );
	}

	private function url_or_front_page_to_postid( $url ) {
		$the_id = url_to_postid( $url );

		// Checking if the source page was the Landing Page
		// This is a special case, because it might be the case that the
		// front page is dynamically built using the last posts info.
		$front_page_url = rtrim( get_bloginfo('url'), '/' );
		$proper_url     = rtrim( $url, '/' );
		if ( $proper_url == $front_page_url ) {
			$aux = get_option( 'page_on_front' );
			if ( $aux )
				$the_id = $aux;
			if ( !$the_id )
				$the_id = NelioABAlternativeExperimentController::FRONT_PAGE__YOUR_LATEST_POSTS;
		}

		return $the_id;
	}

	private function send_navigation_if_required( $dest_id, $referer_url, $is_internal = true ) {
		// PREPARING DATA
		// ---------------------------------
		$referer_url = rtrim( $referer_url, '/' );
		$src_id      = $this->url_or_front_page_to_postid( $referer_url );

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
			if ( $this->is_post_in_a_post_alt_exp( $dest_id ) )
				$actual_dest_id = $this->get_post_alternative( $actual_dest_id );

			// Checking whether the visited page is relevant for a theme experiment
			$is_there_a_relevant_theme_exp = false;
			if ( $this->is_there_a_theme_alt_exp_with_origin( $src_id ) ||
			     $this->is_there_a_theme_alt_exp_with_goal( $dest_id ) ) {
				$is_there_a_relevant_theme_exp = true;
			}

			// IF DEST_ID does not belong to any experiment AND
			//    there is not a GLOBAL experiment running
			// THEN quit
			if ( ! $this->is_goal_in_some_page_or_post_experiment( $dest_id ) &&
			     ! $this->is_post_in_a_post_alt_exp( $dest_id ) &&
			     ! $is_there_a_relevant_theme_exp )
				return;
		}
		// (B) EXTERNAL
		else {
			$actual_dest_id = $dest_id;
			$is_there_a_relevant_theme_exp = false;
		}

		// SENDING DATA to the backend
		// ---------------------------
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );

		if ( !NelioABSettings::has_quota_left() && !NelioABSettings::is_quota_check_required() )
			return;

		$url = sprintf(
			NELIOAB_BACKEND_URL . '/site/%s/nav',
			NelioABSettings::get_site_id()
		);

		$nav = array(
			'user'              => '' . NelioABUser::get_id(),
			'referer'           => '' . $referer_url,
			'origin'            => '' . $src_id,
			'actualOrigin'      => '' . $actual_src_id,
			'destination'       => '' . $dest_id,
			'actualDestination' => '' . $actual_dest_id );

		if ( $is_there_a_relevant_theme_exp )
			$nav['activeTheme'] = '' . NelioABUser::get_alternative_for_theme_alt_exp()->get_id();

		$wrapped_params = array();
		$credential     = NelioABBackend::make_credential();

		$wrapped_params['object']     = $nav;
		$wrapped_params['credential'] = $credential;

		$data = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => json_encode( $wrapped_params ),
			'timeout' => 50,
		);

		for ( $attemp=0; $attemp < 5; ++$attemp ) {
			try {
				$result = NelioABBackend::remote_post_raw( $url, $data );
				NelioABSettings::set_has_quota_left( true );
				break;
			}
			catch ( Exception $e ) {
				// If the navigation could not be sent, it may be the case because
				// there is no more quota available
				if ( $e->getCode() == NelioABErrCodes::NO_MORE_QUOTA ) {
					NelioABSettings::set_has_quota_left( false );
					break;
				}
				// If there was another error... we just keep trying (attemp) up to 5
				// times.
			}
		}
	}

}//NelioABAlternativeExperimentController

?>
