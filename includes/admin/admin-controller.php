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

$nelioab_admin_controller = false;

/**
 * Nelio AB Testing admin controller
 *
 * @package Nelio AB Testing
 * @subpackage Experiment
 * @since 0.1
 */
if ( !class_exists( 'NelioABAdminController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	class NelioABAdminController {

		public $error_message;
		public $message;
		public $global_warnings;
		public $validation_errors;
		public $data;

		private $widget_exp_controller;
		private $menu_exp_controller;

		/**
		 * The class constructor
		 *
		 * @package Nelio AB Testing
		 * @subpackage Main Admin Plugin Controller
		 *
		 * @since 0.1
		 */
		public function __construct() {
			$this->error_message     = NULL;
			$this->message           = NULL;
			$this->global_warnings   = array();
			$this->validation_errors = array();
			$this->data              = NULL;

			if ( isset( $_GET['action'] ) && 'clean-and-deactivate' == $_GET['action'] &&
			     isset( $_GET['plugin'] ) && 'nelio-ab-testing' == $_GET['plugin'] ) {
				add_filter( 'admin_init', array( $this, 'deactivate_plugin' ) );
			}
			else {
				add_filter( 'init', array( $this, 'init' ) );
			}

			NelioABAccountSettings::sync_plugin_version();

			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/widget-experiment-controller.php' );
			$this->widget_exp_controller = new NelioABWidgetExpAdminController();

			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/menu-experiment-controller.php' );
			$this->menu_exp_controller = new NelioABMenuExpAdminController();

		}

		protected function process_special_pages() {
			global $pagenow;
			if ( 'admin.php' !== $pagenow || !isset( $_GET['nelioab-page'] ) )
				return;

			switch( $_GET['nelioab-page'] ) {

				case 'save-css':
					update_option( 'nelioab_css_' . $_GET['nelioab_preview_css'], $_POST['content'] );
					$url = get_option('home');
					$url = esc_url( add_query_arg( $_GET, $url ) );
					header( "Location: $url" );
					die();

				case 'heatmaps':
					require_once( NELIOAB_ADMIN_DIR . '/views/content/heatmaps.php' );
					die();

			}

		}

		public function init() {
			// If the user has been disabled... get out of here
			try {
				$aux = NelioABAccountSettings::check_user_settings();
			}
			catch ( Exception $e ) {
				// We do nothing here (if the user is deactivated, proper "ERROR" pages will be shown).
				// However, it is important we add the check here: if the user was deactivated, but it
				// no longer is, then it's important his settings are checked from the admin area.
			}

			// Some relevant global warnings
			// -----------------------------

			// If the current user is NOT admin, do not show the plugin
			if ( !nelioab_can_user_manage_plugin() )
				return;

			$this->process_special_pages();

			// Iconography
			add_action( 'admin_head', array( $this, 'add_custom_styles' ) );

			// Some hooks
			add_action( 'pre_get_posts', array( $this, 'exclude_alternative_posts_and_pages' ) );
			add_filter( 'plugin_action_links',  array( $this, 'add_plugin_action_links') , 10, 2);

			// (Super)Settings for multisite
			add_action( 'network_admin_menu', array( $this, 'create_nelioab_site_settings_page' ) );

			// Regular settings
			add_action( 'admin_menu', array( $this, 'create_nelioab_admin_pages' ) );
				require_once( NELIOAB_ADMIN_DIR . '/views/settings-page.php' );
				add_action( 'admin_init', array( 'NelioABSettingsPage', 'register_settings' ) );

			add_action( 'admin_menu', array( $this, 'configure_edit_nelioab_alternative' ) );

			// AJAX functions
			add_action( 'wp_ajax_nelioab_get_html_content', array( $this, 'generate_html_content' ) ) ;

			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			add_action( 'wp_ajax_nelioab_post_searcher',
				array( 'NelioABWpHelper', 'search_posts' ) );
			add_action( 'wp_ajax_nelioab_form_searcher',
				array( 'NelioABWpHelper', 'search_forms' ) );

			// TODO: this hook has to be placed inside the proper controller (don't know how, yet)
			add_action( 'admin_enqueue_scripts', array( &$this, 'load_custom_style_and_scripts' ) );
		}

		public function deactivate_plugin() {
			// Clean and Deactivate Nelio A/B Testing
				deactivate_plugins( NELIOAB_PLUGIN_ID, false, is_network_admin() );
		}

		public function add_css_for_creation_page() {
			wp_register_style( 'nelioab_new_exp_selection_css',
				nelioab_admin_asset_link( '/css/nelioab-new-exp-selection.min.css' ) );
			wp_enqueue_style( 'nelioab_new_exp_selection_css' );
		}

		public function add_css_for_themes() {
			wp_register_style( 'nelioab_theme_exp_css',
				nelioab_admin_asset_link( '/css/nelioab-theme-exp.min.css' ) );
			wp_enqueue_style( 'nelioab_theme_exp_css' );
		}

		public function add_custom_styles() {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			wp_register_style( 'nelioab_generic_css',
				nelioab_admin_asset_link( '/css/nelioab-generic.min.css' ) );
			wp_enqueue_style( 'nelioab_generic_css' );
			if ( NelioABWpHelper::is_at_least_version( 3.8 ) ) {
				wp_register_style( 'nelioab_new_icons_css',
					nelioab_admin_asset_link( '/css/nelioab-new-icons.min.css' ) );
				wp_enqueue_style( 'nelioab_new_icons_css' );
			}
		}

		public function load_custom_style_and_scripts() {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
				return;

			if ( isset( $_POST['nelioab_save_exp_post'] ) )
				return;

			if ( $this->is_page( array( 'add-headline-exp', 'edit-headline-exp' ) ) )
					wp_enqueue_media();

			// We make sure jQuery is loaded:
			wp_enqueue_script( 'jquery' );

			// Custom CSS for GRAPHICS and RESULTS (experiment progress)
			if ( $this->is_page( array( 'exp-progress', 'nelioab-dashboard' ) ) ) {
				wp_register_style( 'nelioab_progress_css',
					nelioab_admin_asset_link( '/css/progress.min.css' ) );
				wp_enqueue_style( 'nelioab_progress_css' );
			}

			wp_register_style( 'nelioab_tab_type_css',
				nelioab_admin_asset_link( '/css/nelioab-tab-type.min.css' ) );
			wp_enqueue_style( 'nelioab_tab_type_css' );

			// Custom JS for GRAPHICS (conversion experiment progress)
			wp_enqueue_script( 'nelioab_highcharts',
				nelioab_admin_asset_link( '/js/highcharts.min.js' ) );
			wp_enqueue_script( 'nelioab_exporting',
				nelioab_admin_asset_link( '/js/exporting.min.js' ) );
			wp_enqueue_script( 'nelioab_graphic_functions',
				nelioab_admin_asset_link( '/js/graphic-functions.min.js' ) );

			wp_register_style( 'font_awesome_css',
				nelioab_admin_asset_link( '/css/font-awesome.min.css' ) );
			wp_enqueue_style( 'font_awesome_css' );

			wp_enqueue_script( 'd3',
				nelioab_admin_asset_link( '/js/d3.v3.min.js' ) );

			if ( $this->is_page( 'nelioab-dashboard' ) ) {
				wp_register_style( 'cal_heatmap_css',
					nelioab_admin_asset_link( '/css/cal-heatmap.min.css' ) );
				wp_enqueue_style( 'cal_heatmap_css' );
				wp_enqueue_script( 'cal_heatmap',
					nelioab_admin_asset_link( '/js/cal-heatmap.min.js' ) );
			}

			// Post Searcher
			if ( $this->is_page( array( 'nelioab-add-experiment', 'edit-exp', 'nelioab-css-edit' ) ) ) {
				wp_enqueue_style( 'nelioab_select2_css',
					nelioab_admin_asset_link( '/lib/select2-3.5.0/select2.min.css' ) );
				wp_enqueue_script( 'nelioab_select2',
					nelioab_admin_asset_link( '/lib/select2-3.5.0/select2.min.js' ) );
				wp_enqueue_style( 'nelioab_post_searcher_css',
					nelioab_admin_asset_link( '/css/post-searcher.css' ) );
				wp_enqueue_script( 'nelioab_post_searcher',
					nelioab_admin_asset_link( '/js/post-searcher.min.js' ) );
				wp_enqueue_script( 'nelioab_form_searcher',
					nelioab_admin_asset_link( '/js/form-searcher.min.js' ) );
			}

			// Dialog for all nelio pages
			global $pagenow;
			if ( isset( $_GET['page'] ) && strpos( 'nelioab-', $_GET['page'] ) == 0 ) {
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}
			else if ( 'plugins.php' == $pagenow ) {
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}
		}

		protected function is_page( $pages ) {
			if ( !is_array( $pages ) )
				$pages = array( $pages );
			foreach ( $pages as $page )
				if ( $this->do_check_is_page( $page ) )
					return true;
			return false;
		}

		private function do_check_is_page( $page ) {
			if ( !isset( $_GET['page'] ) )
				return false;

			if ( strpos( $page, 'nelioab' ) === 0 && $page == $_GET['page'] )
				return true;

			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

			switch( $page ) {

				case 'edit-exp':
					if ( 'nelioab-experiments' == $_GET['page'] &&
					     isset( $_GET['action'] ) &&
					     'edit' == $_GET['action'] )
						return true;
					break;

				case 'exp-progress':
					if ( 'nelioab-experiments' == $_GET['page'] &&
					     isset( $_GET['action'] ) &&
					     'progress' == $_GET['action'] )
						return true;
					break;

				case 'add-headline-exp':
					if ( 'nelioab-add-experiment' == $_GET['page'] &&
						isset( $_GET['experiment-type'] ) &&
						NelioABExperiment::HEADLINE_ALT_EXP == $_GET['experiment-type'] )
						return true;
					break;

				case 'edit-headline-exp':
					if ( 'nelioab-experiments' == $_GET['page'] &&
						isset( $_GET['action'] ) && 'edit' == $_GET['action'] &&
						isset( $_GET['exp_type'] ) && NelioABExperiment::HEADLINE_ALT_EXP == $_GET['exp_type'] )
						return true;
					break;

			}

			return false;
		}

		public function exclude_alternative_posts_and_pages( $query ) {

			if ( $query->is_main_query() ||
			     ( isset( $_POST['action'] ) && 'menu-quick-search' == $_POST['action'] ) ) {
				$alt_ids = array();

				remove_action( 'pre_get_posts', array( $this, 'exclude_alternative_posts_and_pages' ) );

				// Hiding alternative pages
				$args = array(
					'meta_key'       => '_is_nelioab_alternative',
					'post_status'    => 'draft',
				);
				$alternative_pages = get_pages( $args );
				if ( is_array( $alternative_pages ) )
					foreach ( $alternative_pages as $page )
						array_push( $alt_ids, $page->ID );

				// Hiding alternative posts
				$args = array(
					'meta_key'       => '_is_nelioab_alternative',
					'post_status'    => 'draft',
					'posts_per_page' => -1,
				);
				$alternative_pages = get_posts( $args );
				if ( is_array( $alternative_pages ) )
					foreach ( $alternative_pages as $page )
						array_push( $alt_ids, $page->ID );

				add_action( 'pre_get_posts', array( $this, 'exclude_alternative_posts_and_pages' ) );

				// WordPress 3.0
				if ( 'page' === get_query_var('post_type') || 'post' === get_query_var('post_type') ) {
					$query->set( 'post__not_in', $alt_ids );
				}
			}

			return $query;
		}

		public function generate_html_content() {
			if ( isset( $_POST['filename'] ) && isset( $_POST['classname'] ) ) {
				$file  = $_POST['filename'];
				$class = $_POST['classname'];
				require_once( $file );
				call_user_func( array ( $class, 'generate_html_content' ) );
			}
		}

		public function add_plugin_action_links( $links, $file ) {
			if ( $file == plugin_basename( NELIOAB_PLUGIN_ID ) && is_plugin_active( $file ) ) {
				$dashboard_link  = '<a id="nelioab-clean-button" href="%4$s" style="cursor:hand;cursor:pointer;">%1$s</a>';
				$dashboard_link .= '<div title="%2$s" id="nelioab-clean-dialog" style="display:none;">%3$s</div>';
				$dashboard_link .= '<script type="text/javascript" style="display:none;">';
				$dashboard_link .= 'jQuery(document).ready(function() {';
				$dashboard_link .= '  var $aux = jQuery("#nelioab-clean-dialog").dialog({';
				$dashboard_link .= '    dialogClass   : "wp-dialog",';
				$dashboard_link .= '    modal         : true,';
				$dashboard_link .= '    autoOpen      : false,';
				$dashboard_link .= '    closeOnEscape : true,';
				$dashboard_link .= '    buttons: [';
				$dashboard_link .= '      {';
				$dashboard_link .= '        text: "' . __( 'Cancel', 'nelioab' ) .'",';
				$dashboard_link .= '        click: function() {';
				$dashboard_link .= '          jQuery(this).dialog("close");';
				$dashboard_link .= '        }';
				$dashboard_link .= '      },';
				$dashboard_link .= '      {';
				$dashboard_link .= '        text: "' . __( 'Clean', 'nelioab' ) . '",';
				$dashboard_link .= '        "class": "button-primary",';
				$dashboard_link .= '        click: function() {';
				$dashboard_link .= '          jQuery(this).dialog("close");';
				$dashboard_link .= '          window.location.href = jQuery("#nelioab-clean-button").attr("href");';
				$dashboard_link .= '        }';
				$dashboard_link .= '      }';
				$dashboard_link .= '    ]';
				$dashboard_link .= '  });';
				$dashboard_link .= '  jQuery("#nelioab-clean-button").click(function(e){e.preventDefault();$aux.dialog("open");});';
				$dashboard_link .= '});';
				$dashboard_link .= '</script>';

				$dashboard_link = sprintf( $dashboard_link,
						__( 'Clean and Deactivate', 'nelioab' ),
						__( 'Warning!', 'nelioab' ),
						__( 'You are about to clean all your A/B testing data. This operation cannot be undone. Are you sure you want to continue?', 'nelioab' ),
						wp_nonce_url(
							admin_url( 'plugins.php?action=clean-and-deactivate&plugin=nelio-ab-testing' ),
							'clean-and-deactivate-plugin_' . NELIOAB_PLUGIN_ID )
					);
				array_unshift( $links, $dashboard_link );
			}
			return $links;
		}


		public function create_nelioab_site_settings_page() {
			require_once( NELIOAB_ADMIN_DIR . '/multisite-settings-page-controller.php' );
			add_submenu_page( 'settings.php',
				__( 'Nelio A/B Testing', 'nelioab' ),
				__( 'Nelio A/B Testing', 'nelioab' ),
				'manage_options',
				'nelioab-multisite-settings',
				array( 'NelioABMultisiteSettingsPageController', 'build' )
			);
		}


		/**
		 * This function creates all the relevant pages for our plugin.
		 * These pages appear in the Dashboard.
		 *
		 * @package Nelio AB Testing
		 * @subpackage Main Admin Plugin Controller
		 *
		 * @since 0.1
		 */
		public function create_nelioab_admin_pages() {

			$nelioab_menu = 'nelioab-dashboard';

			// Main menu
			// ----------------------------------------------------------------------
			add_menu_page(
				__( 'Nelio A/B Testing', 'nelioab' ),
				__( 'Nelio A/B Testing', 'nelioab' ),
				'manage_options',
				$nelioab_menu,
				null,
				null,
				NelioABSettings::get_menu_location() . '.000023510' // 2N.3E.5L.1I.0O
			);


			// Dashboard page
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/dashboard-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'Dashboard', 'nelioab' ),
				__( 'Dashboard', 'nelioab' ),
				'manage_options',
				'nelioab-dashboard',
				array( 'NelioABDashboardPageController', 'build' ) );


			// Experiments pages (depending on the action, we show one or another)
			// ----------------------------------------------------------------------
			$the_action = NULL;
			if ( isset( $_GET['action'] ) )
				$the_action = $_GET['action'];

			switch ( $the_action ) {
				case 'edit':
					require_once( NELIOAB_ADMIN_DIR . '/select-exp-edition-page-controller.php' );
					$page_to_build = array( 'NelioABSelectExpEditionPageController', 'build' );
					break;

				case 'progress':
					require_once( NELIOAB_ADMIN_DIR . '/select-exp-progress-page-controller.php' );
					$page_to_build = array( 'NelioABSelectExpProgressPageController', 'build' );
					break;

				default:
					require_once( NELIOAB_ADMIN_DIR . '/experiments-page-controller.php' );
					$page_to_build = array( 'NelioABExperimentsPageController', 'build' );
					break;
			}
			add_submenu_page( $nelioab_menu,
				__( 'Experiments', 'nelioab' ),
				__( 'Experiments', 'nelioab' ),
				'manage_options',
				'nelioab-experiments',
				$page_to_build );


			// Creating Experiment; (depending on the type, we show one form or another)
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/select-exp-creation-page-controller.php' );
			add_action( 'admin_head', array( $this, 'add_css_for_creation_page' ) );
			add_action( 'admin_head', array( $this, 'add_css_for_themes' ) );
			$page_to_build = array( 'NelioABSelectExpCreationPageController', 'build' );
			add_submenu_page( $nelioab_menu,
				__( 'Add Experiment', 'nelioab' ),
				__( 'Add Experiment', 'nelioab' ),
				'manage_options',
				'nelioab-add-experiment',
				$page_to_build );


			require_once( NELIOAB_ADMIN_DIR . '/account-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'My Account', 'nelioab' ),
				__( 'My Account', 'nelioab' ),
				'manage_options',
				'nelioab-account',
				array( 'NelioABAccountPageController', 'build' ) );

			// Settings page
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/settings-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'Settings', 'nelioab' ),
				__( 'Settings', 'nelioab' ),
				'manage_options',
				'nelioab-settings',
				array( 'NelioABSettingsPageController', 'build' ) );


			// Help
			// ----------------------------------------------------------------------
			add_submenu_page( $nelioab_menu,
				__( 'Help', 'nelioab' ),
				__( 'Help', 'nelioab' ),
				'manage_options',
				'nelioab-help' );
			global $submenu;
			for ( $i = 0; $i < count( $submenu['nelioab-dashboard'] ); ++$i ) {
				if ( 'nelioab-help' == $submenu['nelioab-dashboard'][$i][2] ) {
					$submenu['nelioab-dashboard'][$i][2] = 'http://support.nelioabtesting.com/support/home';
					break;
				}
			}


			// OTHER PAGES (not included in the menu)

			// CSS Editing
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/views/content/css-edit.php' );
			add_submenu_page( NULL,
				__( 'CSS Edit', 'nelioab' ),
				__( 'CSS Edit', 'nelioab' ),
				'manage_options',
				'nelioab-css-edit',
				array( 'NelioABCssEditPage', 'build' ) );

		}


		public function configure_edit_nelioab_alternative() {
			// 0. Check whether there is a post_id set. If there is not any,
			// it is a new post, and so we can quit.
			if ( !isset( $_REQUEST['post'] ) )
				return;
			$post_id = $_REQUEST['post'];

			// 1. Determine whether the current post is a nelioab_alternative
			// If it is not, quit
			$post = get_post( $post_id, ARRAY_A );
			if ( isset( $post ) && count( get_post_meta( $post_id, '_is_nelioab_alternative' ) ) == 0 )
				return;

			// ... but if it is ...

			// a) Hide some metaboxes whose contents are managed by the plugin
			remove_meta_box( 'submitdiv', 'page', 'side' );        // Publish options
			remove_meta_box( 'commentstatusdiv', 'page', 'side' ); // Comments
			remove_meta_box( 'slugdiv', 'page', 'normal' );        // Comments

			remove_meta_box( 'submitdiv', 'post', 'side' );        // Publish options
			remove_meta_box( 'commentstatusdiv', 'post', 'side' ); // Comments
			remove_meta_box( 'slugdiv', 'post', 'normal' );        // Comments

			// b) Create a custom box for saving the alternative page
			add_meta_box(
				'save_nelioab_alternative_box',      // HTML identifier
				__( 'Edition of Alternative\'s Content', 'nelioab' ), // Box title
				array( $this, 'print_alternative_box' ),
				'page',
				'side',
				'high' );

			add_meta_box(
				'save_nelioab_alternative_box',      // HTML identifier
				__( 'Edition of Alternative\'s Content', 'nelioab' ), // Box title
				array( $this, 'print_alternative_box' ),
				'post',
				'side',
				'high' );

		}

		public function print_alternative_box() { ?>
			<div id="submitdiv"><?php
				require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				$colorscheme = NelioABWpHelper::get_current_colorscheme();
				?>
				<script>
					var nelioab_style_metabox = document.createElement("style");
					nelioab_style_metabox.setAttribute("type", "text/css");
					nelioab_style_metabox.innerHTML = "#save_nelioab_alternative_box h3.hndle { " +
						"color:<?php echo $colorscheme['foreground']; ?>; " +
						"background: <?php echo $colorscheme['focus']; ?>;" +
						"border: 1px solid <?php echo $colorscheme['focus']; ?>;" +
					"}" +
					"#favorite-actions, #message, .add-new-h2, .tablenav, #edit-slug-box { display:none; }" +
					"#save_nelioab_alternative_box .handlediv," +
					"#save_nelioab_alternative_box .handlediv:hover { visibility:visible; color:white; }";
					document.getElementsByTagName('head')[0].appendChild(nelioab_style_metabox);
				</script>
				<div class="submitbox" id="submitpost">
					<div class="misc-pub-section" style="min-height:4em;">
						<div style="float:right;margin-top:1em;">
							<input name="original_publish" type="hidden" id="original_publish" value="Update">
							<input name="save" type="submit"
								class="button-primary" id="publish"
								tabindex="5"
								value="<?php _e( 'Update' ); ?>" />
						</div>
						<?php
						$the_post_id = 0;
						if ( isset( $_GET['post'] ) )
							$the_post_id = $_GET['post'];
						$url        = admin_url( 'admin.php?page=nelioab-experiments' );
						$values     = explode( ',', get_post_meta( $the_post_id, '_is_nelioab_alternative', true ) );
						$exp_id     = $values[0];
						$exp_status = $values[1];
						?>
						<div style="float:right;margin-top:1em;margin-right:1em;">
							<div id="preview-action">
								<?php
									$type = 'post';
									$ori_id = get_post_meta( $_GET['post'], '_nelioab_original_id', true );
									if ( NelioABExperimentStatus::RUNNING == $exp_status || NelioABExperimentStatus::FINISHED == $exp_status ) {
										$preview_button = __( 'Preview Changes' );
									}
									else {
										$preview_button = __( 'Preview' );
									}
									$preview_link = get_permalink( $_GET['post'] );
									$preview_link = esc_url( add_query_arg( 'preview', 'true', $preview_link ) );
									?>
								<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview-<?php echo $_GET['post']; ?>" id="post-preview"><?php echo $preview_button; ?></a>
								<input type="hidden" name="wp-preview" id="wp-preview" value="" />
							</div>
						</div>
					</div>
					<div style="margin:0.8em 0.2em 0.8em 0.2em;">
						<b><?php _e( 'Go back to...', 'nelioab' ); ?></b>
						<ul style="margin-left:1.5em;">
							<?php
							switch( $exp_status ){
								case NelioABExperimentStatus::DRAFT:
								case NelioABExperimentStatus::READY:
									?><li><a href="<?php echo $url . '&action=edit&ctab=tab-alts&id=' . $exp_id .
										'&exp_type=' . NelioABExperiment::PAGE_OR_POST_ALT_EXP; ?>"><?php
											_e( 'Editing this experiment', 'nelioab' ); ?></a></li><?php
									break;
								case NelioABExperimentStatus::RUNNING:
								case NelioABExperimentStatus::FINISHED:
									?><li><a href="<?php echo $url . '&action=progress&id=' . $exp_id .
									'&exp_type=' . NelioABExperiment::PAGE_OR_POST_ALT_EXP; ?>"><?php
										_e( 'The results of the related experiment', 'nelioab' ); ?></a></li><?php
									break;
								case NelioABExperimentStatus::TRASH:
								case NelioABExperimentStatus::PAUSED:
								default:
									// Nothing here
							}
							?>
							<li><a href="<?php echo $url; ?>"><?php _e( 'My list of experiments', 'nelioab' ); ?></a></li>
							<?php if( $exp_status == NelioABExperimentStatus::RUNNING ) { ?>
								<li><a href="<?php echo admin_url( 'admin.php?page=nelioab-dashboard' ); ?>"><?php _e( 'The Dashboard', 'nelioab' ); ?></a></li>
							<?php } ?>
						</ul>
					</div>
				</div>
			</div><?php
		}

	}//NelioABAdminController

	if ( is_admin() )
		$nelioab_admin_controller = new NelioABAdminController();

}

