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

	public function __construct() {
		$this->actual_theme = false;
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
			add_filter( 'get_post_metadata',   array( &$this, 'load_proper_page_template' ), 10, 4 );

			/**
			 * Support with other plugins.
			 * Compatibility tweaks for testing OptimizePress pages.
			 */
			require_once( NELIOAB_UTILS_DIR . '/optimize-press-support.php' );
			if ( NelioABOptimizePressSupport::is_optimize_press_active() )
				add_filter( 'template_include',
					array( 'NelioABOptimizePressSupport', 'op_template_include' ) );

			/**
			 * Support with other plugins.
			 * Compatibility tweaks with Member Access.
			 */
			require_once( NELIOAB_UTILS_DIR . '/member-access-support.php' );
			if ( NelioABMemberAccessSupport::is_plugin_active() )
				add_action( 'wp_loaded',
					array( 'NelioABMemberAccessSupport', 'unhook_redirections' ) );
		}
		else {
			if ( !NelioABSettings::use_php_cookies() ) {
				// If the "nelioab_load_alt" POST param is not set, we have to return the
				// page with the JS file that is able to load an alternative and do all
				// the required stuff
				add_action( 'wp_enqueue_scripts', array( &$this, 'load_nelioab_check_scripts' ) );
			}
		}

		// Make sure that the title is replaced everywhere
		add_filter( 'the_title', array( &$this, 'change_title_on_abtesting' ),  10, 2 );
		add_filter( 'wp_title',  array( &$this, 'fix_title_for_landing_page' ), 10, 2 );
		add_action( 'wp_head',   array( &$this, 'add_js_to_replace_titles' ) );

		/**
		 *  Hooks for Gravity Forms and Contact Form 7
		 */
		// Adding hidden fields:
		add_filter( 'gform_pre_render',    array( &$this, 'add_hidden_fields_to_gf' ),  10, 1 );
		add_filter( 'wpcf7_form_elements', array( &$this, 'add_hidden_fields_to_cf7' ), 10, 1 );

		// Monitoring submissions:
		add_action( 'gform_after_submission', array( &$this, 'track_gf_submission' ),  10, 2 );
		add_action( 'wpcf7_submit',           array( &$this, 'track_cf7_submission' ), 10, 2 );
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

	public function update_current_winner_for_running_experiments( $force_update = 'dont_force' ) {
		if ( 'force_update' === $force_update )
			update_option( 'nelioab_last_winners_update', 0 );
		$now = time();
		$last_update = get_option( 'nelioab_last_winners_update', 0 );
		if ( $now - $last_update < 1800 )
			return;
		update_option( 'nelioab_last_winners_update', $now );

		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() !== NelioABExperiment::HEATMAP_EXP )
				$exp->update_winning_alternative_from_appengine();
		}
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
		require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() != NelioABExperiment::THEME_ALT_EXP ||
			     $exp->get_type() != NelioABExperiment::CSS_ALT_EXP )
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
		if ( !$this->actual_theme ) {
			require_once( NELIOAB_MODELS_DIR . '/user.php' );
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
		if ( !isset( $_POST['nelioab_load_alt'] ) )
			remove_filter( 'stylesheet', array( &$this, 'modify_stylesheet' ) );

		// WARNING: I check whether the function exists, because there are some (random)
		// times in which calling "current_user_can" results in a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || current_user_can( 'delete_users' ) )
			return $stylesheet;
		$theme = $this->get_actual_theme();
		return $theme['Stylesheet'];
	}

	public function modify_template( $template ) {
		// If I'm not loading an alternative, I hooked for no reason...
		if ( !isset( $_POST['nelioab_load_alt'] ) )
			remove_filter( 'stylesheet', array( &$this, 'modify_template' ) );

		// WARNING: I check whether the function exists, because there are some (random)
		// times in which calling "current_user_can" results in a fatal error :-S
		if ( !function_exists( 'wp_get_current_user' ) || current_user_can( 'delete_users' ) )
			return $template;
		$theme = $this->get_actual_theme();
		return $theme['Template'];
	}

	public function fix_widgets_for_theme( $all_widgets ) {
		// If I'm not loading an alternative, I hooked for no reason...
		if ( !isset( $_POST['nelioab_load_alt'] ) )
			remove_filter( 'stylesheet', array( &$this, 'fix_widgets_for_theme' ) );

		require_once( NELIOAB_MODELS_DIR . '/user.php' );
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

	public function load_nelioab_check_scripts() {
		// Custom Permalinks Support: Obtaining the real permalink (which might be
		// masquared by custom permalinks plugin)
		require_once( NELIOAB_UTILS_DIR . '/custom-permalinks-support.php' );
		global $nelioab_controller;
		$url = $nelioab_controller->get_current_url();
		$current_post_id = $nelioab_controller->url_or_front_page_to_postid( $url );
		if ( NelioABCustomPermalinksSupport::is_plugin_active() )
			$permalink = NelioABCustomPermalinksSupport::get_original_permalink( $current_post_id );
		else
			$permalink = get_permalink( $current_post_id );

		wp_enqueue_script( 'nelioab_alternatives_script_check',
			nelioab_asset_link( '/js/nelioab-check.min.js' ) );
		wp_localize_script( 'nelioab_alternatives_script_check',
			'NelioABChecker', array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'permalink' => $permalink,
			) );
	}

	public function load_nelioab_scripts_for_alt() {
		wp_enqueue_script( 'nelioab_alternatives_script_alt',
			nelioab_asset_link( '/js/nelioab-alt.min.js' ) );
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

		header( 'Content-Type: application/json' );
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

		echo json_encode( $result );
		die();
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

	public function add_js_to_replace_titles() { ?>
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
						url:   '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						data: {
							action: 'nelioab_send_alt_titles_info',
							current_url: document.URL,
							referer: document.referrer,
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

		// Send a regular navigation (if it was not already sent, which is controlled
		// by the "is_relevant" function) to control quota
		$nav = $this->prepare_navigation_object( $current_post_id, '', true );
		if ( $this->is_post_in_a_title_alt_exp( $current_post_id ) && !$nelioab_controller->is_relevant( $nav ) )
			$nelioab_controller->send_navigation_object( $nav );

		die();
	}

	public function change_title_on_abtesting( $title, $id = -1 ) {
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
		if ( NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE == $actual_src_id &&
		     strlen( $referer_url ) === 0 )
			$actual_src_id = NelioABController::NAVIGATION_ORIGIN_IS_UNKNOWN;
		elseif ( $this->is_post_in_a_post_alt_exp( $src_id ) )
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

		//$the_menu = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::MENU_ALT_EXP );
		//if ( $the_menu )
		//	$nav['activeMenu'] = '' . $the_menu->get_id();

		if ( NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE == $nav['destination'] )
			$nav['destination'] = NelioABController::WRONG_NAVIGATION_DESTINATION;
		if ( NelioABController::NAVIGATION_ORIGIN_FROM_THE_OUTSIDE == $nav['actualDestination'] )
			$nav['actualDestination'] = NelioABController::WRONG_NAVIGATION_DESTINATION;

		return $nav;
	}

	private function get_form_hidden_field_names() {
		return array( 'nelioab_form_cookies', 'nelioab_form_current_url' );
	}

	public function add_hidden_fields_to_gf( $form ) {
		$field_names = $this->get_form_hidden_field_names();
		foreach ( $field_names as $field_name )
			$this->add_hidden_field_to_gf( $form, $field_name );
		return $form;
	}


	private function add_hidden_field_to_gf( &$form, $name ) {
		// Counting number of fields (for GF internal field counter)
		$max = 0;
		foreach( $form['fields'] as $field )
			if( floatval($field['id'] ) > $max )
				$max = floatval( $field['id'] );

		// Adding field
		$form['fields'][] = array(
			'type'   => 'adminonly_hidden',
			'id'     => $max + 1,
			'allowsPrepopulate' => true,
			'inputs' => array(
				array(
					'id'    => $name,
					'label' => $name,
				),
			),
		);
	}


	public function add_hidden_fields_to_cf7( $fields ) {
		global $post;
		$original_id = $this->get_original_related_to( $post->ID );
		$hf_template = '<input type="hidden" name="%1$s" value="%2$s" />';

		$field_names = $this->get_form_hidden_field_names();
		$hidden_fields = '';
		foreach ( $field_names as $field_name )
			$hidden_fields .= sprintf( $hf_template, $field_name, '' );

		return $hidden_fields . $fields;
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

		//$the_menu = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::MENU_ALT_EXP );
		//if ( $the_menu )
		//	$ev['activeMenu'] = $the_menu->get_id();


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

