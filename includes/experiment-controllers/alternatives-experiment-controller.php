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


class NelioABAlternativesExperimentController {

	private $alternative_post;

	public function __construct() {
		$this->alternative_post = null;

		add_filter( 'wp', array( $this, 'check_parameters' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_jquery' ) );

		if ( isset( $_POST['nelioab_load_alt'] ) ) {
			// If we are accessing a page with the "nelioab_load_alt" POST param set,
			// then we have to...TODO
			add_filter( 'posts_results',       array( &$this, 'posts_results_intercept' ) );
			add_filter( 'the_posts',           array( &$this, 'the_posts_intercept' ) );
			add_filter( 'comments_array',      array( &$this, 'load_comments_from_original' ) );
			add_filter( 'get_comments_number', array( &$this, 'load_comments_number_from_original' ) );
			add_action( 'wp_enqueue_scripts',  array( &$this, 'load_nelioab_scripts_for_alt' ) );
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

	public function check_parameters() {

		if ( isset( $_POST['nelioab_sync'] ) )
			$this->sync_cookies();

		if ( isset( $_POST['nelioab_nav'] ) )
			$this->send_navigation();

		if ( isset( $_POST['nelioab_check_alt'] ) )
			$this->check_requires_an_alternative();

	}

	private function sync_cookies() {
		global $NELIOAB_COOKIES;

		// Check whether new cookies have to be created:
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $data )
			$aux = NelioABUser::get_alternative_for_conversion_experiment( $data->original );

		// Return the new COOKIES
		echo json_encode( $NELIOAB_COOKIES );
		die();
	}

	private function send_navigation() {
		$post_id = url_to_postid( $_SERVER['HTTP_REFERER'] );

		// Checking if the source page was the Landing Page
		$front_page_url = rtrim( get_bloginfo('url'), '/' );
		$http_referer   = rtrim( $_SERVER['HTTP_REFERER'], '/' );
		if ( $http_referer == $front_page_url )
			$post_id = get_option( 'page_on_front' );

		if ( $post_id )
			$this->send_navigation_if_required( $post_id, $_POST['referer'] );
		die();
	}

	private function check_requires_an_alternative() {
		$post_id = url_to_postid( $_SERVER['HTTP_REFERER'] );

		// Checking if the source page was the Landing Page
		$front_page_url = rtrim( get_bloginfo('url'), '/' );
		$http_referer   = rtrim( $_SERVER['HTTP_REFERER'], '/' );
		if ( $http_referer == $front_page_url )
			$post_id = get_option( 'page_on_front' );

		if ( $this->has_post_alternative( $post_id ) )
			echo "true";
		else
			echo "false";

		die();
	}

	public function posts_results_intercept( $posts ) {
		if ( count( $posts ) != 1 )
			return $posts;

		$post = $posts[0];
		if ( $this->has_post_alternative( $post->ID ) ) {
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

	public function load_comments_from_original( $comments ) {
		global $post;
		$id = $post->ID;
		if ( !$this->is_post_alternative( $id ) )
			return $comments;

		// Load the original comments
		$copy_from = $this->get_original_related_to( $id );
		$comments  = get_comments( array( 'post_id' => $copy_from ) );

		// And prepare the form to save comments to the original
		?>
		<script type="text/javascript">
		(function($) {
			$(document).ready(function(){
				$("#comment_post_ID").attr( 'value', <?php echo $copy_from; ?> );
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

	public function change_title_on_abtesting( $title, $id ) {
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

	private function has_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp_data )
			if ( $exp_data->original == $post_id )
				return true;
		return false;
	}

	private function is_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp_data )
			foreach ( $exp_data->alternatives as $alt )
				if ( $alt == $post_id )
					return true;
		return false;
	}

	private function get_original_related_to( $alt_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp_data )
			foreach ( $exp_data->alternatives as $alt )
				if ( $alt == $alt_id )
					return $exp_data->original;
		// If it is not an alternative, we return the same ID
		return $alt_id;
	}

	private function is_goal_in_some_experiment( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp_data )
			if ( $exp_data->goal == $post_id )
				return true;
		// If it is not the goal of any experiment, return false
		return false;
	}

	private function get_post_alternative( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		return NelioABUser::get_alternative_for_conversion_experiment( $post_id );
	}

	private function send_navigation_if_required( $dest_id, $referer_url ) {
		// PREPARING DATA
		// ---------------------------------
		$referer_url = rtrim( $referer_url, '/' );
		$src_id      = url_to_postid( $referer_url );
		
		// Checking if the source page was the Landing Page
		$front_page_url = rtrim( get_bloginfo('url'), '/' );
		if ( $referer_url == $front_page_url )
			$src_id = get_option( 'page_on_front' );

		// Checking if the source page was an alternative
		if ( $this->has_post_alternative( $src_id ) )
			$src_id = $this->get_post_alternative( $src_id );

		// Checking if the destination page was an alternative
		if ( $this->has_post_alternative( $dest_id ) )
			$dest_id = $this->get_post_alternative( $dest_id );

		// If DEST_ID does not belong to any experiment, quit
		if ( ! $this->is_goal_in_some_experiment( $dest_id ) &&
		     ! $this->has_post_alternative( $dest_id ) &&
		     ! $this->is_post_alternative( $dest_id ) )
			return;
		
		// SENDING DATA to the backend
		// ---------------------------
		require_once( NELIOAB_MODELS_DIR . '/user.php' );
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );

		$url = sprintf(
			NELIOAB_BACKEND_URL . '/site/%s/nav',
			NelioABSettings::get_site_id()
		);

		$nav = array(
			'user'              => '' . NelioABUser::get_id(),
			'referer'           => '' . $referer_url,
			'origin'            => '' . $src_id,
			'destination'       => '' . $this->get_original_related_to( $dest_id ),
			'actualDestination' => '' . $dest_id );

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
				break;
			}
			catch ( Exception $e ) {
				// If the navigation could not be sent, we have a "small" problem...
			}
		}
	}

}//NelioABAlternativesExperimentController
 
?>
