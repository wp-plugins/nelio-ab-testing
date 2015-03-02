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

$nelioab_controller = false;

/**
 * Nelio AB Testing main controller
 *
 * @package Nelio AB Testing
 * @subpackage Experiment
 * @since 0.1
 */
if ( !class_exists( 'NelioABController' ) ) {

	class NelioABController {

		const FRONT_PAGE__YOUR_LATEST_POSTS      = -100;
		const FRONT_PAGE__THEME_BASED_LANDING    = -104;
		const NAVIGATION_ORIGIN_FROM_THE_OUTSIDE = -101;
		const NAVIGATION_ORIGIN_IS_UNKNOWN       = -102;
		const WRONG_NAVIGATION_DESTINATION       = -103;
		const UNKNOWN_PAGE_ID_FOR_NAVIGATION     = -105;

		private $controllers;

		private $id;
		private $url;

		public $sync_data;
		public $tracking_script_params;

		public function __construct() {
			require_once( NELIOAB_UTILS_DIR . '/dtgtm4wp-support.php' );

			$this->build_current_url();

			$this->controllers = array();
			$this->sync_data = array();
			$this->tracking_script_params = array();

			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/alternative-experiment-controller.php' );
			$this->controllers['alt-exp'] = new NelioABAlternativeExperimentController();

			// Iconography and Menu bar
			add_action( 'admin_bar_init', array( $this, 'add_custom_styles' ), 95 );

			if ( NelioABSettings::is_menu_enabled_for_admin_bar() ) {
				add_action( 'admin_bar_menu',
					array( $this, 'create_nelioab_admin_bar_menu' ), 40 );
				add_action( 'admin_bar_menu',
					array( $this, 'create_nelioab_admin_bar_quickexp_option' ), 999 );
			}

			require_once( NELIOAB_EXP_CONTROLLERS_DIR. '/heatmap-experiment-controller.php' );
			$aux = new NelioABHeatmapExperimentController();
			$this->controllers['hm'] = $aux;

			// Preparing AJAX callbacks
			$this->prepare_ajax_callbacks();

			if ( isset( $_GET['nelioab_preview_css'] ) )
				add_action( 'wp_footer', array( &$this->controllers['alt-exp'], 'preview_css' ) );

			global $pagenow;
			if ( $this->is_alternative_content_loading_required() || ( isset( $pagenow ) && 'widgets.php' == $pagenow ) ) {
				// We'll filter the widgets as we please
			}
			else {
				// We must show the original widgets only
				add_filter( 'sidebars_widgets', array( &$this->controllers['alt-exp'], 'filter_original_widgets' ) );
			}

		}

		public function add_custom_styles() {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			if ( NelioABWpHelper::is_at_least_version( 3.8 ) ) {
				wp_register_style( 'nelioab_new_icons_css',
					nelioab_admin_asset_link( '/css/nelioab-new-icons.min.css' ) );
				wp_enqueue_style( 'nelioab_new_icons_css' );
			}
		}

		private function prepare_ajax_callbacks() {

			add_action( 'wp_ajax_nopriv_nelioab_qc',
				array( &$this, 'check_quota' ) );
			add_action( 'wp_ajax_nelioab_qc',
				array( &$this, 'check_quota' ) );

			add_action( 'wp_ajax_nopriv_nelioab_ure',
				array( &$this, 'update_running_experiments' ) );
			add_action( 'wp_ajax_nelioab_ure',
				array( &$this, 'update_running_experiments' ) );

			add_action( 'wp_ajax_nopriv_nelioab_sync_cookies_and_check',
				array( &$this, 'sync_cookies_and_check' ) );
			add_action( 'wp_ajax_nelioab_sync_cookies_and_check',
				array( &$this, 'sync_cookies_and_check' ) );

		}

		public function check_quota() {
			try {
				$url  = sprintf( NELIOAB_BACKEND_URL . '/customer/%s/check',
					NelioABAccountSettings::get_customer_id() );
				$json = NelioABBackend::remote_get( $url, true );
				$json = json_decode( $json['body'] );
				$quota = $json->quota + $json->quotaExtra;
				NelioABAccountSettings::set_has_quota_left( $quota > 0 );
			} catch ( Exception $e ) {}
			die();
		}

		public function update_running_experiments() {
			$this->compute_results_for_running_experiments();
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			NelioABExperimentsManager::update_current_winner_for_running_experiments( 'force_update' );
		}

		public function sync_cookies_and_check() {
			// We control that cookies correspond to the last version of the plugin
			$this->version_control();

			// We update the cookies
			$cookies = $this->update_cookies();

			// Finally, we check if we need to load an alternative
			$alt_con = $this->controllers['alt-exp'];
			$load_action = $alt_con->check_requires_an_alternative( $_REQUEST['nelioab_current_url'] );
			// If there's no need to load an alternative, we build all relevant sync data
			if ( 'DO_NOT_LOAD_ANYTHING' == $load_action )
				$this->build_relevant_sync_data();

			$mode = NelioABSettings::get_alternative_loading_mode();
			$result = array(
					'cookies' => $cookies,
					'action'  => $load_action,
					'mode'    => $mode,
					'sync'    => $this->sync_data,
				);

			echo json_encode( $result );
			die();
		}

		public function build_relevant_sync_data() {
			// We load all relevant sync data
			$alt_con = $this->controllers['alt-exp'];
			$hm_con  = $this->controllers['hm'];

			// (a) Navigation to the current page
			$current_id  = $this->url_or_front_page_to_postid( $this->get_current_url() );
			$referer_url = ( isset( $_REQUEST['nelioab_referer_url'] ) ) ?  rtrim( $_REQUEST['nelioab_referer_url'] ) : '';
			$nav = $alt_con->prepare_navigation_object( $current_id, $referer_url );
			nelioab_add_sync_data( array( 'nav' => $nav ) );

			// (b) Possible outwards navigations from the current page
			$external_urls = $alt_con->get_external_page_accessed_action_urls();
			nelioab_add_sync_data( array( 'outwardsNavigationUrls' => $external_urls ) );

			// (c) Information about Clickable Elements
			$click_elems = $alt_con->get_list_of_click_element_actions( $nav );
			nelioab_add_sync_data( array( 'clickableElements' => $click_elems ) );

			// (d) Information about Heatmaps
			$heatmaps = $hm_con->track_heatmaps_for_post( $nav['currentActualId'] );
			nelioab_add_sync_data( array( 'heatmaps' => $heatmaps ) );

			// (d) By default, we'll assume that no headlines were replaced
			nelioab_add_sync_data( array( 'headlines' => array( 'list' => '' ) ) );

			// BUT NOT for:
			// - Form Submits, because they are necessarily processed by WP
		}

		private function update_cookies() {
			// We assign the current user an ID (if she does not have any)
			require_once( NELIOAB_MODELS_DIR . '/user.php' );
			$user_id = NelioABUser::get_id();

			// And we prepare the other cookies
			$alt_con = $this->controllers['alt-exp'];
			$cookies = $alt_con->sync_cookies();

			// Clean old cookies
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			foreach ( $cookies as $cookie_name => $cookie_value ) {
				$keep_cookie = false;
				foreach ( $running_exps as $exp ) {
					if ( strpos( $cookie_name, 'altexp_' ) === false ||
					     strpos( $cookie_name, $exp->get_id() ) > 0 ) {
						$keep_cookie = true;
						break;
					}
				}
				if ( !$keep_cookie )
					$cookies[$cookie_name] = '__delete_cookie';
			}

			return $cookies;
		}

		public function init() {
			// If the user has been disabled... get out of here
			try {
				$aux = NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				// It is important we add the check here: if the user was deactivated, but it no
				// longer is, then it's important his settings are rechecked so that we can
				// re-enable it.
				if ( $e->getCode() == NelioABErrCodes::DEACTIVATED_USER )
					return;
			}

			// Trick for proper THEME ALT EXP testing
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			// Theme alt exp related
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$aux = $this->controllers['alt-exp'];
				add_filter( 'stylesheet',       array( &$aux, 'modify_stylesheet' ) );
				add_filter( 'template',         array( &$aux, 'modify_template' ) );
				add_filter( 'sidebars_widgets', array( &$aux, 'show_the_appropriate_widgets' ) );
			}

			add_action( 'init', array( &$this, 'do_init' ) );
			add_action( 'init', array( &$this, 'init_admin_stuff' ) );
		}

		/**
		 * This is a callback for the "option_page_on_front" hook...
		 */
		public function fix_page_on_front( $page_on_front ) {
			if ( isset( $_GET['page_id'] ) )
				$current_id = $_GET['page_id'];
			else
				$current_id  = $this->url_or_front_page_to_postid( $this->get_current_url() );
			$original_id = get_post_meta( $current_id, '_nelioab_original_id', true );
			if ( isset( $_GET['preview'] ) || isset( $_GET['nelioab_show_heatmap'] ) ) {
				if ( $page_on_front == $original_id )
					return $current_id;
				else
					return $page_on_front;
			}
			else {
				$aux = $this->controllers['alt-exp'];
				return $aux->get_post_alternative( $page_on_front );
			}
		}

		public function compute_results_for_running_experiments() {

			// 0. Check if the customer can use this function
			if ( NelioABAccountSettings::get_subscription_plan() >=
			     NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN )
				return;

			// 1. Check if the last check was, at least, 5 minutes ago
			$now = time();
			$last_update = get_option( 'nelioab_last_update_of_results', 0 );
			if ( $now - $last_update < 300 )
				return;
			update_option( 'nelioab_last_update_of_results', $now );

			// 2. Check if we have running experiments
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			if ( count( $running_exps ) == 0 )
				return;

			try {
				// 3. Ask AE to recompute the results
				$url = sprintf(
						NELIOAB_BACKEND_URL . '/site/%s/exp/result/compute',
						NelioABAccountSettings::get_site_id()
					);
				$result = NelioABBackend::remote_get( $url );
			}
			catch ( Exception $e ) {}
		}

		public function get_current_url() {
			return $this->url;
		}

		private function build_current_url() {
			if ( isset( $_REQUEST['nelioab_current_url'] ) ) {
				$this->url = $_REQUEST['nelioab_current_url'];
			}
			else {
				$url = 'http';
				if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" )
					$url .= 's';
				$url .= '://';
				//if ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != '80')
				//	$url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
				//else
				//	$url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				$url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				$this->url = $url;
			}

			$this->id = false;
		}

		public function url_or_front_page_to_postid( $url ) {

			if ( strlen( $url ) === 0 )
				return NelioABController::UNKNOWN_PAGE_ID_FOR_NAVIGATION;

			if ( $url === $this->url && $this->id !== false )
				return $this->id;

			$restore_the_filter = false;
			if ( has_filter( 'option_page_on_front', array( &$this, 'fix_page_on_front' ) ) ) {
				remove_filter( 'option_page_on_front', array( &$this, 'fix_page_on_front' ) );
				$restore_the_filter = true;
			}

			$the_id = url_to_postid( $url );
			if ( 0 === $the_id )
				$the_id = url_to_postid( str_replace( 'https://', 'http://', $url ) );

			// Checking if the source page was the Landing Page
			// This is a special case, because it might be the case that the
			// front page is dynamically built using the last posts info.
			$front_page_url = rtrim( get_bloginfo('url'), '/' );
			$front_page_url = str_replace( 'https://', 'http://', $front_page_url );
			$proper_url     = rtrim( $url, '/' );
			$proper_url     = str_replace( 'https://', 'http://', $proper_url );
			if ( $proper_url == $front_page_url ) {
				$aux = nelioab_get_page_on_front();
				if ( $aux )
					$the_id = $aux;
				if ( !$the_id )
					$the_id = NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS;
			}

			// Check if it's the Latest Post Page (which could have been set dynamically)
			$page_for_posts_id = get_option( 'page_for_posts' );
			if ( $page_for_posts_id ) {
				$page_for_posts_url = rtrim( get_permalink( $page_for_posts_id ), '/' );
				$page_for_posts_url = str_replace( 'https://', 'http://', $page_for_posts_url );
				if ( $proper_url == $page_for_posts_url )
					$the_id = $page_for_posts_id;
			}

			// Custom Permalinks Support: making sure that we get the real ID.
			require_once( NELIOAB_UTILS_DIR . '/custom-permalinks-support.php' );
			if ( NelioABCustomPermalinksSupport::is_plugin_active() ) {
				$custom_permalink_id = NelioABCustomPermalinksSupport::url_to_postid( $url );
				if ( $custom_permalink_id )
					$the_id = $custom_permalink_id;
			}

			if ( 0 === $the_id && function_exists( 'woocommerce_get_page_id' ) ) {
				// I wasn't able to find a post/page whose URL is the provided one.
				// If the user is using WooCommerce, for instance, this happens with the
				// Shop Page (see https://core.trac.wordpress.org/ticket/25136). The
				// reported solution there does not work, so I'll try something new:
				$shop_page_id = woocommerce_get_page_id( 'shop' );
				if ( get_permalink( $shop_page_id ) == $url )
					$the_id = $shop_page_id;
			}

			if ( 0 === $the_id && strpos( $url, '?' ) !== false && strlen( get_option( 'permalink_structure', '' ) ) > 0 ) {
				$url_without_get_params = preg_replace( '/\?.*$/', '', $url );
				$the_id = $this->url_or_front_page_to_postid( $url_without_get_params );
			}

			if ( 0 === $the_id )
				$the_id = NelioABController::UNKNOWN_PAGE_ID_FOR_NAVIGATION;

			if ( 0 !== $the_id && $url === $this->url )
				$this->id = $the_id;

			if ( $restore_the_filter )
				add_filter( 'option_page_on_front', array( &$this, 'fix_page_on_front' ) );

			return $the_id;
		}

		public function url_or_front_page_to_actual_postid_considering_alt_exps( $url ) {
			$post_id = $this->url_or_front_page_to_postid( $url );
			$aux = $this->controllers['alt-exp'];
			if ( $aux->is_post_in_a_post_alt_exp( $post_id ) )
				$post_id = $aux->get_post_alternative( $post_id );
			return $post_id;
		}

		public function init_admin_stuff() {
			if ( !nelioab_can_user_manage_plugin() )
				return;

			// Controller for viewing heatmaps
			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/heatmap-controller.php' );
			$conexp_controller = new NelioABHeatMapController();
		}

		public function do_init() {
			// We do not perform AB Testing for certain visitors:
			if ( !$this->could_visitor_be_in_experiment() ) {
				add_action( 'wp_enqueue_scripts', array( &$this, 'add_js_delayer_script' ), 10 );
				return;
			}

			// Custom Permalinks Support: making sure that we are not redirected while
			// loading an alternative...
			if ( $this->is_alternative_content_loading_required() ) {
				require_once( NELIOAB_UTILS_DIR . '/custom-permalinks-support.php' );
				if ( NelioABCustomPermalinksSupport::is_plugin_active() )
					NelioABCustomPermalinksSupport::prevent_template_redirect();
			}

			// If we're previewing a page alternative, it may be the case that it's an
			// alternative of the landing page. Let's make sure the "page_on_front"
			// option is properly updated:
			if ( isset( $_GET['preview'] ) || isset( $_GET['nelioab_show_heatmap'] ) )
				add_filter( 'option_page_on_front', array( &$this, 'fix_page_on_front' ) );

			add_action( 'wp_enqueue_scripts', array( &$this, 'register_tracking_script' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'load_tracking_script' ), 99 );

			// LOAD ALL CONTROLLERS

			// Controller for changing a page using its alternatives:
			$aux = $this->controllers['alt-exp'];
			$aux->hook_to_wordpress();

			// Controller for managing heatmaps (capturing and sending)
			$aux = $this->controllers['hm'];
			$aux->hook_to_wordpress();

		}

		public function could_visitor_be_in_experiment() {
			if ( $this->is_robot() )
				return false;

			if ( nelioab_can_user_manage_plugin() )
				return false;

			return true;
		}

		public function add_js_delayer_script() {
			?><script type="text/javascript">NelioAB={delay:function(f){f()}}</script><?php
			echo "\n";
		}

		public function is_alternative_content_loading_required() {
			$mode = NelioABSettings::get_alternative_loading_mode();
			switch ( $mode ) {
				case NelioABSettings::POST_ALTERNATIVE_LOADING_MODE:
					if ( isset( $_POST['nelioab_load_alt'] ) )
						return 'nelioab_load_alt';
					if ( isset( $_POST['nelioab_load_consistent_version'] ) )
						return 'nelioab_load_consistent_version';
				case NelioABSettings::GET_ALTERNATIVE_LOADING_MODE:
					if ( isset( $_GET['nelioab_load_alt'] ) )
						return 'nelioab_load_alt';
					if ( isset( $_GET['nelioab_load_consistent_version'] ) )
						return 'nelioab_load_consistent_version';
			}

			return false;
		}

		public function register_tracking_script() {
			wp_register_script( 'nelioab_tracking_script',
				nelioab_asset_link( '/js/tracking.min.js' ),
				array( 'jquery' ) );

			// Custom Permalinks Support: Obtaining the real permalink (which might be
			// masquared by custom permalinks plugin)
			require_once( NELIOAB_UTILS_DIR . '/custom-permalinks-support.php' );
			$url = $this->get_current_url();
			$current_post_id = $this->url_or_front_page_to_postid( $url );
			if ( NelioABCustomPermalinksSupport::is_plugin_active() )
				$permalink = NelioABCustomPermalinksSupport::get_original_permalink( $current_post_id );
			else
				$permalink = get_permalink( $current_post_id );

			// If we were unable to find a permalink...
			if ( empty( $permalink ) ) {
				if ( nelioab_get_page_on_front() == $current_post_id )
					$permalink = home_url();
				else
					$permalink = $url;
			}

			// When the page is returned, should the scripts trigger a "check request" or not?
			if ( $this->is_alternative_content_loading_required() ) {
				nelioab_localize_tracking_script( array( 'nelioabScriptAction' => 'track' ) );
				$this->build_relevant_sync_data();
			}
			else if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				if ( isset( $_POST['gform_submit'] ) )
					nelioab_localize_tracking_script( array( 'nelioabScriptAction' => 'skip' ) );
				else if ( isset( $_POST['_wpcf7'] ) )
					nelioab_localize_tracking_script( array( 'nelioabScriptAction' => 'skip' ) );
				else
					nelioab_localize_tracking_script( array( 'nelioabScriptAction' => 'skip' ) );
			}
			else {
				nelioab_localize_tracking_script( array( 'nelioabScriptAction' => 'sync-and-check' ) );
				// relevant_sync_data will be available as a result of the "check_request"
			}

			$misc = array();

			// QUOTA CONTROL
			$misc['hq'] = ( NelioABAccountSettings::has_quota_left() ) ? 'y' : 'n';
			if ( NelioABAccountSettings::is_quota_check_required() ) {
				// We'll wait for an AJAX request to check whether there's actually quota or not.
				// In the meantime, we'll keep the quota as it is
				NelioABAccountSettings::assume_quota_check_will_occur_shortly();
				$misc['qc'] = 'y';
			}
			else {
				// There's no need to check anything
				$misc['qc'] = 'n';
			}

			// UPDATE INFORMATION ABOUT RUNNING EXPERIMENTS
			$now = time();
			$last_ure_call = get_option( 'nelioab_last_ure_call', 0 );
			if ( $last_ure_call + 300 < $now ) {
				update_option( 'nelioab_last_ure_call', $now );
				$misc['ure'] = 'y';
			}
			else {
				$misc['ure'] = 'n';
			}

			// OUTWARDS NAVIGATIONS USING TARGET="_BLANK"
			$misc['useOutwardsNavigationsBlank'] = NelioABSettings::use_outwards_navigations_blank();

			nelioab_localize_tracking_script( array(
					'ajaxurl'        => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
					'permalink'      => $permalink,
					'customer'       => NelioABAccountSettings::get_customer_id(),
					'site'           => NelioABAccountSettings::get_site_id(),
					'backend'        => array( 'domain'  => NELIOAB_BACKEND_DOMAIN,
					                           'version' => NELIOAB_BACKEND_VERSION ),
					'misc'           => $misc,
				) );

		}

		public function load_tracking_script() {
			if ( $this->is_alternative_content_loading_required() )
				$this->tracking_script_params['sync'] = $this->sync_data;
			else
				$this->tracking_script_params['sync'] = array();
			wp_localize_script( 'nelioab_tracking_script', 'NelioABParams',
				$this->tracking_script_params );
			wp_enqueue_script( 'nelioab_tracking_script' );
		}

		/**
		 * When a user connects to our site, she gets a set of cookies. These
		 * cookies depend on the version of the plugin. If the last time she
		 * connected the site had an older version, we update the information
		 * so that she can get rid of any old cookies (via JS).
		 */
		private function version_control() {
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
		 * This function creates the admin bar menu.
		 *
		 * @package Nelio AB Testing
		 * @subpackage Main Plugin Controller
		 *
		 * @since 3.3
		 */
		public function create_nelioab_admin_bar_menu() {

			// If the current user is NOT admin, do not show the menu
			if ( !nelioab_can_user_manage_plugin() )
				return;

			// If we are in the admin UI, do not show the menu
			if ( is_admin() )
				return;

			global $wp_admin_bar, $post;
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

			// Get Current Element (post or page)
			$current_element = '';
			$is_page = false;
			$is_post = false;

			if ( is_singular() )  {
				if ( is_singular( 'post' ) ) {
					$is_post = true;
					$current_element = '&post-id=' . $post->ID;
				}
				else if ( is_page() ) {
					$is_page = true;
					$current_element = '&page-id=' . $post->ID;
				}
			}

			$nelioab_admin_bar_menu = 'nelioab-admin-bar-menu';

			// Main Admin bar menu
			// ----------------------------------------------------------------------
			$wp_admin_bar->add_node( array(
				'id'    => $nelioab_admin_bar_menu,
				'title' => __( 'Nelio A/B Testing', 'nelioab' ),
				'href'  => admin_url( 'admin.php?page=nelioab-dashboard' ),
			) );

			// Add Experiment page
			// ----------------------------------------------------------------------
			$wp_admin_bar->add_node( array(
				'parent' => $nelioab_admin_bar_menu,
				'id'     => 'nelioab_admin_add_experiment',
				'title'  => __( 'Add Experiment', 'nelioab' ),
				'href'   => admin_url( 'admin.php?page=nelioab-add-experiment' ),
			) );

			if ( $is_post ) {
				// -> New A/B Test for Post Headlines
				// ----------------------------------------------------------------------
				$wp_admin_bar->add_node(array(
					'parent' => 'nelioab_admin_add_experiment',
					'id' => 'nelioab_admin_new_exp_titles',
					'title' => __( 'Create Headline Test for this Post', 'nelioab' ),
					'href' => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::HEADLINE_ALT_EXP . $current_element ),
				));

				// -> New A/B Test for Posts
				// ----------------------------------------------------------------------
				$wp_admin_bar->add_node(array(
					'parent' => 'nelioab_admin_add_experiment',
					'id' => 'nelioab_admin_new_exp_posts',
					'title' => __( 'Create A/B Test for this Post', 'nelioab' ),
					'href' => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::POST_ALT_EXP . $current_element ),
				));

			}

			if ( $is_page ) {
				// -> New A/B Test for Pages
				// ----------------------------------------------------------------------
				$wp_admin_bar->add_node(array(
					'parent' => 'nelioab_admin_add_experiment',
					'id' => 'nelioab_admin_new_exp_pages',
					'title' => __( 'Create A/B Test for this Page', 'nelioab' ),
					'href' => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::PAGE_ALT_EXP . $current_element ),
				));
			}

			if ( $is_post || $is_page ) {
				// -> New Heatmap Experiment for Page or Post
				// ----------------------------------------------------------------------
				$wp_admin_bar->add_node( array(
					'parent' => 'nelioab_admin_add_experiment',
					'id'     => 'nelioab_admin_new_exp_heatmaps',
					'title'  => __( 'Create Heatmap Experiment', 'nelioab' ),
					'href'   => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::HEATMAP_EXP . $current_element ),
				) );
			}

			// Dashboard page
			// ----------------------------------------------------------------------
			$wp_admin_bar->add_node( array(
				'parent' => $nelioab_admin_bar_menu,
				'id'     => 'nelioab_admin_dashboard',
				'title'  => __( 'Dashboard', 'nelioab' ),
				'href'   => admin_url( 'admin.php?page=nelioab-dashboard' ),
			) );

			// Experiments page
			// ----------------------------------------------------------------------
			$wp_admin_bar->add_node( array(
				'parent' => $nelioab_admin_bar_menu,
				'id'     => 'nelioab_admin_experiments',
				'title'  => __( 'Experiments', 'nelioab' ),
				'href'   => admin_url( 'admin.php?page=nelioab-experiments' ),
			) );
		}

		/**
		 * This function creates the admin bar menu.
		 *
		 * @package Nelio AB Testing
		 * @subpackage Main Plugin Controller
		 *
		 * @since 3.3
		 */
		public function create_nelioab_admin_bar_quickexp_option() {
			global $wp_admin_bar, $post;
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

			// Get Current Element (post or page)
			$current_element = '';
			$is_page = false;
			$is_post = false;

			if ( is_singular() )  {
				if ( is_singular( 'post' ) ) {
					$is_post = true;
					$current_element = '&post-id=' . $post->ID;
				}
				else if ( is_page() ) {
					$is_page = true;
					$current_element = '&page-id=' . $post->ID;
				}
			}

			// Quick Experiment menu
			// ----------------------------------------------------------------------
			if ( $is_post ) {
				$wp_admin_bar->add_node(array(
					'id' => 'nelioab_admin_bar_quick_menu',
					'title' => __( 'Create Headline Experiment', 'nelioab' ),
					'href' => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::HEADLINE_ALT_EXP . $current_element ),
				));
			}

			if ( $is_page ) {
				$wp_admin_bar->add_node(array(
					'id' => 'nelioab_admin_bar_quick_menu',
					'title' => __( 'Create A/B Experiment', 'nelioab' ),
					'href' => admin_url( 'admin.php?page=nelioab-add-experiment&experiment-type=' . NelioABExperiment::PAGE_ALT_EXP . $current_element ),
				));
			}
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

	$nelioab_controller = new NelioABController();
	if ( !is_admin() )
		$nelioab_controller->init();

	function nelioab_localize_tracking_script( $new_params ) {
		global $nelioab_controller;
		foreach ( $new_params as $key => $value )
			$nelioab_controller->tracking_script_params[$key] = $value;
	}

	function nelioab_add_sync_data( $new_params ) {
		global $nelioab_controller;
		foreach ( $new_params as $key => $value )
			$nelioab_controller->sync_data[$key] = $value;
	}

}
