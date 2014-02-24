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



/**
 * Nelio AB Testing main controller
 *
 * @package Nelio AB Testing
 * @subpackage Experiment
 * @since 0.1
 */
class NelioABController {

	const FRONT_PAGE__YOUR_LATEST_POSTS = -100;

	private $controllers;

	public function __construct() {
		$this->controllers = array();
		$directory   = NELIOAB_DIR . '/experiment-controllers';

		require_once( $directory . '/alternative-experiment-controller.php' );
		$this->controllers['alt-exp'] = new NelioABAlternativeExperimentController();

		require_once( $directory . '/heatmap-experiment-controller.php' );
		$aux = new NelioABHeatmapExperimentController();
		$this->controllers['hm'] = $aux;

		if ( isset( $_POST['nelioab_send_heatmap_info'] ) )
			add_action( 'plugins_loaded', array( &$aux, 'save_heatmap_info_into_cache' ) );
		if ( isset( $_POST['nelioab_sync_heatmaps'] ) )
			add_action( 'plugins_loaded', array( &$aux, 'send_heatmap_info_if_required' ) );
		if ( isset( $_GET['nelioab_preview_css'] ) )
			add_action( 'the_content',    array( &$this->controllers['alt-exp'], 'preview_css' ) );
	}

	public function init() {
		// If the user has been disabled... get out of here
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		try {
			$aux = NelioABSettings::check_user_settings();
		}
		catch ( Exception $e ) {
			if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER )
				return;
		}

		// Trick for proper THEME ALT EXP testing
		if ( isset( $_POST['nelioab_load_alt'] ) ) {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			// Theme alt exp related
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$aux = $this->controllers['alt-exp'];
				add_filter( 'stylesheet',       array( &$aux, 'modify_stylesheet' ) );
				add_filter( 'template',         array( &$aux, 'modify_template' ) );
				add_filter( 'sidebars_widgets', array( &$aux, 'fix_widgets_for_theme' ) );
			}
		}

		add_action( 'init', array( &$this, 'do_init' ) );
		add_action( 'init', array( &$this, 'init_admin_stuff' ) );
	}

	private function check_parameters() {

		if ( isset( $_POST['nelioab_nav'] ) )
			$this->send_navigation();

		// Check if we are syncing cookies...
		if ( isset( $_POST['nelioab_sync'] ) ) {
			// We control that cookies correspond to the last version of the plugin
			$this->version_control();

			// We assign the current user an ID (if she does not have any)
			require_once( NELIOAB_MODELS_DIR . '/user.php' );
			$user_id = NelioABUser::get_id();
		}

		if ( isset( $_POST['nelioab_sync_and_check'] ) ) {
			$alt_con  = $this->controllers['alt-exp'];
			$cookies  = $alt_con->sync_cookies();
			$load_alt = $alt_con->check_requires_an_alternative( $_SERVER['HTTP_REFERER'] );
			$result   = array(
				'cookies'  => $cookies,
				'load_alt' => $load_alt );
			echo json_encode( $result );
			die();
		}

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

	private function send_navigation_if_required( $dest_id, $referer_url, $is_internal = true ) {
		if ( !NelioABSettings::has_quota_left() && !NelioABSettings::is_quota_check_required() )
			return;

		$alt_exp_con = $this->controllers['alt-exp'];
		$nav = $alt_exp_con->prepare_navigation_object( $dest_id, $referer_url, $is_internal );

		if ( $is_internal && !$this->is_relevant( $nav ) )
			return;

		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );

		$url = sprintf(
			NELIOAB_BACKEND_URL . '/site/%s/nav',
			NelioABSettings::get_site_id()
		);

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

	private function is_relevant( $nav ) {

		$aux = $this->controllers['alt-exp'];
		if ( $aux->is_relevant( $nav ) )
			return true;

		$aux = $this->controllers['hm'];
		if ( $aux->is_relevant( $nav ) )
			return true;

		return false;
	}

	public function get_current_url() {
		$url = 'http';
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" )
			$pageURL .= 's';
		$url .= '://';
		if ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != '80')
			$url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		else
			$url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		return $url;
	}

	public function url_or_front_page_to_postid( $url ) {
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
				$the_id = NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS;
		}

		return $the_id;
	}

	public function url_or_front_page_to_actual_postid_considering_alt_exps( $url ) {
		$post_id = url_to_postid( $url );
		$aux = $this->controllers['alt-exp'];
		if ( $aux->is_post_in_a_post_alt_exp( $post_id ) )
			$post_id = $aux->get_post_alternative( $post_id );
		return $post_id;
	}

	public function init_admin_stuff() {
		if ( !current_user_can( 'level_8' ) )
			return;

		$dir = NELIOAB_DIR . '/experiment-controllers';

		// Controller for viewing heatmaps
		require_once( $dir . '/heatmap-controller.php' );
		$conexp_controller = new NelioABHeatMapController();
	}

	public function do_init() {
		// We do not perform AB Testing if the user accessing the page is...
		// ...a ROBOT...
		if ( $this->is_robot() )
			return;

		// ... or an ADMIN
		if ( current_user_can( 'level_8' ) )
			return;

		$this->check_parameters();

		add_action( 'wp_enqueue_scripts', array( &$this, 'load_jquery' ) );

		// LOAD ALL CONTROLLERS

		// Controller for changing a page using its alternatives:
		$aux = $this->controllers['alt-exp'];
		$aux->hook_to_wordpress();

		// Controller for managing heatmaps (capturing and sending)
		$aux = $this->controllers['hm'];
		$aux->hook_to_wordpress();

	}

	public function load_jquery() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'nelioab_events',
			NELIOAB_ASSETS_URL . '/js/nelioab-events.min.js?' . NELIOAB_PLUGIN_VERSION );
	}

	/**
	 * When a user connects to our site, she gets a set of cookies. These
	 * cookies depend on the version of the plugin. If the last time she
	 * connected the site had an older version, we update the information
	 * so that she can get rid of any old cookies (via JS).
	 */
	private function version_control() {
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		global $NELIOAB_COOKIES;
		$cookie_name  = NelioABSettings::cookie_prefix() . 'version';
		$last_version = 0;
		if ( isset( $NELIOAB_COOKIES[$cookie_name] ) )
			$last_version = $NELIOAB_COOKIES[$cookie_name];

		if ( $last_version == NELIOAB_PLUGIN_VERSION )
			return;

		$aux = array();
		$userid_key = NelioABSettings::cookie_prefix() . 'userid';
		if ( isset( $NELIOAB_COOKIES[$userid_key] ) )
			$aux[$userid_key] = $NELIOAB_COOKIES[$userid_key];

		$NELIOAB_COOKIES = $aux;
		nelioab_setcookie( $cookie_name, NELIOAB_PLUGIN_VERSION, time() + (86400*28) );
		nelioab_setcookie( '__nelioab_new_version', 'true' );
	}

	/**
	 * Quickly detects whether the current user is a bot, based on
	 * User Agent. Keep in mind the function is not very precise.
	 * Do not use for page blocking.
	 *
	 * @return bool true if the user is a bot, false otherwise.
	 */
	private function is_robot() {
		$list = 'bot|crawl|spider|https?:' .
			'|Google|Rambler|Lycos|Y!|Yahoo|accoona|Scooter|AltaVista|yandex' .
			'|ASPSeek|Ask Jeeves|eStyle|Scrubby';

		return preg_match("/$list/i", @$_SERVER['HTTP_USER_AGENT']);
	}

}//NelioABController

if ( !is_admin() ) {
	$nelioab_controller = new NelioABController();
	$nelioab_controller->init();
}

?>
