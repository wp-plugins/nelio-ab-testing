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

if ( !class_exists( 'NelioABAdminController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	class NelioABAdminController {

		public $error_message;
		public $message;
		public $global_warnings;
		public $validation_errors;
		public $data;

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
			add_filter( 'init', array( $this, 'init' ) );
		}

		protected function process_special_pages() {
			if ( !isset( $_GET['nelioab-page'] ) )
				return;

			switch( $_GET['nelioab-page'] ) {
				case 'heatmap-viewer':
					require_once( NELIOAB_ADMIN_DIR . '/views/content/heatmaps.php' );
					die();
				case 'save-css':
					update_option( 'nelioab_css_' . $_GET['nelioab_preview_css'], $_POST['content'] );
					$url = get_option('home');
					$url = add_query_arg( $_GET, $url );
					header( "Location: $url" ) ;
					die();
			}
		}

		public function init() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// Some relevant global warnings
			// -----------------------------

			// No more quota
			if ( !NelioABSettings::has_quota_left() ) {
				array_push( $this->global_warnings,
					__( '<b>Warning!</b> There is no more quota available.', 'nelioab' ) );
			}

			// -----------------------------


			// If the current user is NOT admin, do not show the plugin
			if ( !current_user_can( 'level_8' ) )
				return;

			$this->process_special_pages();

			// Iconography
			add_action( 'admin_head', array( $this, 'add_custom_styles' ) );

			// Some hooks
			add_action( 'pre_get_posts', array( $this, 'exclude_alternative_posts_and_pages' ) );
			add_action( 'admin_menu', array( $this, 'create_nelioab_settings_pages' ) );
			add_action( 'admin_menu', array( $this, 'configure_edit_nelioab_alternative' ) );
			add_action( 'pre_update_option_siteurl', array( 'NelioABSettings', 'update_registered_sites_if_required' ) );

			// AJAX functions
			add_action( 'wp_ajax_get_html_content', array( $this, 'generate_html_content' ) ) ;

			// TODO: this hook has to be placed inside the proper controller (don't know how, yet)
			add_action( 'admin_enqueue_scripts', array( &$this, 'load_custom_style_and_scripts' ) );
		}

		public function add_css_for_creation_page() {
			wp_register_style( 'nelioab_new_exp_selection_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-new-exp-selection.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_new_exp_selection_css' );
		}

		public function add_css_for_themes() {
			wp_register_style( 'nelioab_theme_exp_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-theme-exp.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_theme_exp_css' );
		}

		public function add_custom_styles() {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			wp_register_style( 'nelioab_generic_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-generic.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_generic_css' );
			if ( NelioABWpHelper::is_at_least_version( 3.8 ) ) {
				wp_register_style( 'nelioab_new_icons_css',
					NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-new-icons.min.css', false, NELIOAB_PLUGIN_VERSION );
				wp_enqueue_style( 'nelioab_new_icons_css' );
			}
		}

		public function add_js_for_dialogs() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css' );
			wp_register_style( 'nelioab_dialog_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-dialog.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_dialog_css' );
		}

		public function load_custom_style_and_scripts() {
			// We make sure jQuery is loaded:
			wp_enqueue_script( 'jquery' );

			// Custom CSS for GRAPHICS and RESULTS (experiment progress)
			wp_register_style( 'nelioab_progress_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/progress.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_progress_css' );

			wp_register_style( 'nelioab_tab_type_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/nelioab-tab-type.min.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_tab_type_css' );

			// Custom JS for GRAPHICS (conversion experiment progress)
			wp_enqueue_script( 'nelioab_highcharts',
				NELIOAB_ADMIN_ASSETS_URL . '/js/highcharts.min.js?' . NELIOAB_PLUGIN_VERSION );
			wp_enqueue_script( 'nelioab_exporting',
				NELIOAB_ADMIN_ASSETS_URL . '/js/exporting.min.js?' . NELIOAB_PLUGIN_VERSION );
			wp_enqueue_script( 'nelioab_graphic_functions',
				NELIOAB_ADMIN_ASSETS_URL . '/js/graphic-functions.min.js?' . NELIOAB_PLUGIN_VERSION );
		}

		public function exclude_alternative_posts_and_pages( $query ) {

			if ( $query->is_main_query() ) {
				$alt_ids = array();

				remove_action( 'pre_get_posts', array( $this, 'exclude_alternative_posts_and_pages' ) );

				// Hiding alternative pages
				$args = array(
					'meta_key'       => '_is_nelioab_alternative',
					'post_status'    => 'draft',
				);
				$alternative_pages = get_pages( $args );
				foreach ( $alternative_pages as $page )
					array_push( $alt_ids, $page->ID );

				// Hiding alternative posts
				$args = array(
					'meta_key'       => '_is_nelioab_alternative',
					'post_status'    => 'draft',
					'posts_per_page' => -1,
				);
				$alternative_pages = get_posts( $args );
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

		/**
		 * TODO
		 *
		 * @package Nelio AB Testing
		 * @subpackage Main Admin Plugin Controller
		 *
		 * @since 0.1
		 */
		public function create_nelioab_settings_pages() {

			// WHEN USING THE DASHBOARD, RECOVER: $nelioab_menu = 'nelioab-admin-pages';
			$nelioab_menu = 'nelioab-experiments';

			// Main menu
			// ----------------------------------------------------------------------
			add_menu_page(
				__( 'Nelio A/B Testing', 'nelioab' ),
				__( 'Nelio A/B Testing', 'nelioab' ),
				'manage_options',
				$nelioab_menu,
				null,
				'div' );


//			// Dashboard page
//			// ----------------------------------------------------------------------
//			require_once( NELIOAB_ADMIN_DIR . '/dashboard-page-controller.php' );
//			add_submenu_page( $nelioab_menu,
//				__( 'Dashboard', 'nelioab' ),
//				__( 'Dashboard', 'nelioab' ),
//				'manage_options',
//				'nelioab-admin-pages',
//				array( 'NelioABDashboardPageController', 'build' ) );


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
					add_action( 'admin_head', array( $this, 'add_js_for_dialogs' ) );
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


			require_once( NELIOAB_ADMIN_DIR . '/settings-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'Settings', 'nelioab' ),
				__( 'Settings', 'nelioab' ),
				'manage_options',
				'nelioab-settings',
				array( 'NelioABSettingsPageController', 'build' ) );

			// Feedback page
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/feedback-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'Share & Comment', 'nelioab' ),
				__( 'Share & Comment', 'nelioab' ),
				'manage_options',
				'nelioab-feedback',
				array( 'NelioABFeedbackPageController', 'build' ) );


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

			// a) Hide the "add new" button TODO: PENDING PROPER IMPLEMENTATION
			// <style type="text/css">
			// 	#favorite-actions, #message,
			// 	.add-new-h2, .tablenav,
			// 	#edit-slug-box {
			// 		display:none;
			// 	}
			// </style>

			// b) Hide some metaboxes whose contents are managed by the plugin
			remove_meta_box( 'submitdiv', 'page', 'side' );        // Publish options
			remove_meta_box( 'commentstatusdiv', 'page', 'side' ); // Comments
			remove_meta_box( 'slugdiv', 'page', 'normal' );        // Comments

			remove_meta_box( 'submitdiv', 'post', 'side' );        // Publish options
			remove_meta_box( 'commentstatusdiv', 'post', 'side' ); // Comments
			remove_meta_box( 'slugdiv', 'post', 'normal' );        // Comments

			// c) Create a custom box for saving the alternative page
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
			<script>
				var nelioab_style_metabox = document.createElement("style");
			   nelioab_style_metabox.setAttribute("type", "text/css");
   			nelioab_style_metabox.innerHTML = "#save_nelioab_alternative_box h3.hndle { " +
					"background:none; " +
					"background-color:#298cba; " +
					"color:white; " +
					"text-shadow:#000 0 1px 0; " +
					<?php echo '"background:#21759B url(' . admin_url() . '/images/button-grad.png ) repeat-x scroll left top; "'; ?>
				"}";
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
					<div style="float:right;margin-top:1em;margin-right:1em;">
					<div id="preview-action">
						<?php $preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true' ) ) ); ?>
						<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php _e( 'Preview' ); ?></a>
						<input type="hidden" name="wp-preview" id="wp-preview" value="" />
					</div>
					</div>
				</div>
				<div style="margin:0.8em 0.2em 0.8em 0.2em;">
					<b><?php _e( 'Go back to...', 'nelioab' ); ?></b>
					<?php
					$the_post_id = 0;
					if ( isset( $_GET['post'] ) )
						$the_post_id = $_GET['post'];

					$url        = admin_url() . 'admin.php?page=nelioab-experiments';
					$values     = explode( ',', get_post_meta( $the_post_id, '_is_nelioab_alternative', true ) );
					$exp_id     = $values[0];
					$exp_status = $values[1];
					?>
					<ul style="margin-left:1.5em;">
						<?php
						switch( $exp_status ){
							case NelioABExperimentStatus::DRAFT:
							case NelioABExperimentStatus::READY:
					   		?><li><a href="<?php echo $url . '&action=edit&id=' . $exp_id .
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
					</ul>
				</div>
			</div><?php
		}

	}//NelioABAdminController
}

if ( is_admin() )
	$nelioab_admin_controller = new NelioABAdminController();

?>
