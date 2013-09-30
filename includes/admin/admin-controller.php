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

if ( !class_exists( NelioABAdminController ) ) {

	class NelioABAdminController {

		public $message;
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
			$this->message           = NULL;
			$this->validation_errors = array();
			$this->data              = NULL;
			add_filter( 'init', array( $this, 'init' ) );
		}

		public function init() {
			// If the current user is NOT admin, do not show the plugin
			if ( !current_user_can( 'level_8' ) )
				return;

			// Fix $_POST quotes
			foreach( $_POST as $key => $value )
				if ( is_string( $value ) )
					$_POST[$key] = stripslashes( $value );

			// Iconography
			add_action( 'admin_head', array( $this, 'add_custom_styles' ) );

			// Some hooks
			add_action( 'pre_get_posts', array( $this, 'exclude_alternative_pages' ) );
			add_action( 'admin_menu', array( $this, 'create_nelioab_settings_pages' ) );
			add_action( 'admin_menu', array( $this, 'configure_edit_nelioab_alternative' ) );
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			add_action( 'pre_update_option_siteurl', array( NelioABSettings, 'update_registered_sites_if_required' ) );

			// AJAX functions
			add_action( 'wp_ajax_get_html_content', array( $this, 'generate_html_content' ) ) ;

			// TODO: this hook has to be placed inside the proper controller (don't know how, yet)
			add_action( 'admin_enqueue_scripts', array( &$this, 'load_custom_style_and_scripts' ) );
		}

		public function add_custom_styles() {?>
			<style type="text/css">
				/* TODO: recover when using dashboard
				#toplevel_page_nelioab-admin-pages div.wp-menu-image {
					background-image: url("<?php echo NELIOAB_ADMIN_ASSETS_URL . '/images/menu.png?' . NELIOAB_PLUGIN_VERSION; ?>");
					background-position: 0px -32px;
				}

				#toplevel_page_nelioab-admin-pages:hover div.wp-menu-image,
				#toplevel_page_nelioab-admin-pages.wp-has-current-submenu div.wp-menu-image,
				#toplevel_page_nelioab-admin-pages :hover div.wp-menu-image,
				#toplevel_page_nelioab-admin-pages .wp-has-current-submenu div.wp-menu-image,
				#toplevel_page_nelioab-admin-pages .current div.wp-menu-image {
					background-position: 0px 0px;
				}
				*/

				#toplevel_page_nelioab-experiments div.wp-menu-image {
					background-image: url("<?php echo NELIOAB_ADMIN_ASSETS_URL . '/images/menu.png?' . NELIOAB_PLUGIN_VERSION; ?>");
					background-position: 0px -32px;
				}

				#toplevel_page_nelioab-experiments:hover div.wp-menu-image,
				#toplevel_page_nelioab-experiments.wp-has-current-submenu div.wp-menu-image,
				#toplevel_page_nelioab-experiments :hover div.wp-menu-image,
				#toplevel_page_nelioab-experiments .wp-has-current-submenu div.wp-menu-image,
				#toplevel_page_nelioab-experiments .current div.wp-menu-image {
					background-position: 0px 0px;
				}

				#icon-nelioab {
					background-image: url("<?php echo NELIOAB_ADMIN_ASSETS_URL . '/images/icons32.png?' . NELIOAB_PLUGIN_VERSION; ?>");
					background-position: -12px -6px;
				}

				.button-primary-disabled, .button-primary[disabled], .button-primary:disabled {
					cursor: default;
				}

				.button-primary-disabled:hover, .button-primary[disabled]:hover, .button-primary:disabled:hover {
					border: 1px solid #298cba !important;
				}

			</style>
			<?
		}

		public function load_custom_style_and_scripts() {
			// We make sure jQuery is loaded:
			wp_enqueue_style( 'jquery' );

			// Custom CSS for GRAPHICS (conversion experiment progress)
			wp_register_style( 'nelioab_graphics_css',
				NELIOAB_ADMIN_ASSETS_URL . '/css/graphics.css', false, NELIOAB_PLUGIN_VERSION );
			wp_enqueue_style( 'nelioab_graphics_css' );

			// Custom JS for GRAPHICS (conversion experiment progress)
			wp_enqueue_script( 'nelioab_highcharts',
				NELIOAB_ADMIN_ASSETS_URL . '/js/highcharts.js?' . NELIOAB_PLUGIN_VERSION );
			wp_enqueue_script( 'nelioab_exporting',
				NELIOAB_ADMIN_ASSETS_URL . '/js/exporting.js?' . NELIOAB_PLUGIN_VERSION );
			wp_enqueue_script( 'nelioab_graphic_functions',
				NELIOAB_ADMIN_ASSETS_URL . '/js/graphic-functions.js?' . NELIOAB_PLUGIN_VERSION );
		}

		public function exclude_alternative_pages( $query ) {
	
			if ( $query->is_main_query() ) {
				$alt_ids = array();

				remove_action( 'pre_get_posts', array( $this, 'exclude_alternative_pages' ) );

				$args = array(
					'meta_key'    => '_is_nelioab_alternative',
					'post_status' => 'draft',
				);
				$alternative_pages = get_pages( $args );
				foreach ( $alternative_pages as $page )
					array_push( $alt_ids, $page->ID );
				add_action( 'pre_get_posts', array( $this, 'exclude_alternative_pages' ) );

				// WordPress 3.0
				if( get_query_var('post_type') && 'page' == get_query_var('post_type') ) {
					$query->set( 'post__not_in', $alt_ids );
				}
			}
	
			return $query;
		}

		public function generate_html_content() {
			$file = NELIOAB_DIR . $_POST['filename']; 
			$class = $_POST['classname'];
			require_once( $file );
			call_user_func( array ( $class, 'generate_html_content' ) );
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
//				array( NelioABDashboardPageController, 'build' ) );

	
			// Experiments pages (depending on the action, we show one or another)
			// ----------------------------------------------------------------------
			switch ( $_GET['action'] ) {
			case 'edit':
				// When editing an experiment, we have to load the proper controller and view:
				switch ( $_POST['nelioab_edit_exp_type'] ) {
				case 'alt-exp-post':
				case 'alt-exp-page':
					require_once( NELIOAB_ADMIN_DIR . '/alt-exp-edition-page-controller.php' );
					$page_to_build = array( NelioABAltExpEditionPageController, 'build' );
					break;

				default:
					require_once( NELIOAB_ADMIN_DIR . '/select-exp-edition-page-controller.php' );
					$page_to_build = array( NelioABSelectExpEditionPageController, 'build' );
				}
				break;
			case 'progress':
				require_once( NELIOAB_ADMIN_DIR . '/alternatives-experiment-progress-page-controller.php' );
				$page_to_build = array( NelioABAlternativesExperimentProgressPageController, 'build' );
				break;
			default:
				require_once( NELIOAB_ADMIN_DIR . '/experiments-page-controller.php' );
				$page_to_build = array( NelioABExperimentsPageController, 'build' );
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
			switch ( $_GET['experiment-type'] ) {
			case 'alt-exp-page':
			case 'alt-exp-post':
				require_once( NELIOAB_ADMIN_DIR . '/alt-exp-creation-page-controller.php' );
				$page_to_build = array( NelioABAltExpCreationPageController, 'build' );
				break;
			default:
				require_once( NELIOAB_ADMIN_DIR . '/select-exp-creation-page-controller.php' );
				$page_to_build = array( NelioABSelectExpCreationPageController, 'build' );
			}
			require_once( NELIOAB_ADMIN_DIR . '/select-exp-creation-page-controller.php' );
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
				array( NelioABSettingsPageController, 'build' ) );

			// Feedback page
			// ----------------------------------------------------------------------
			require_once( NELIOAB_ADMIN_DIR . '/feedback-page-controller.php' );
			add_submenu_page( $nelioab_menu,
				__( 'Feedback', 'nelioab' ),
				__( 'Feedback', 'nelioab' ),
				'manage_options',
				'nelioab-feedback',
				array( NelioABFeedbackPageController, 'build' ) );
	
		}


		public function configure_edit_nelioab_alternative() {
			// 0. Check whether there is a post_id set. If there is not any,
			// it is a new post, and so we can quit.
			$post_id = $_REQUEST['post'];
			if ( !isset( $post_id ) )
				return;

			// 1. Determine whether the current post is a nelioab_alternative
			// If it is not, quit
			$post    = get_post( $post_id, ARRAY_A );
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

		public function print_alternative_box() {?>
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
						<?php $preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) ); ?>
						<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php _e( 'Preview' ); ?></a>
						<input type="hidden" name="wp-preview" id="wp-preview" value="" />
					</div>
					</div>
				</div>
				<div style="margin:0.8em 0.2em 0.8em 0.2em;">
					<b><?php _e( 'Go back to...', 'nelioab' ); ?></b>
					<?php
					$url        = admin_url() . 'admin.php?page=nelioab-experiments';
					$values     = explode( ',', get_post_meta( $_GET['post'], '_is_nelioab_alternative', true ) );
					$exp_id     = $values[0];
					$exp_status = $values[1];
					?>
					<ul style="margin-left:1.5em;">
						<?php
						require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
						switch( $exp_status ){
						case NelioABExperimentStatus::DRAFT:
						case NelioABExperimentStatus::READY:
					   	?><li><a href="<?php echo $url . '&action=edit&id=' . $exp_id; ?>"><?php _e( 'Editing this experiment', 'nelioab' ); ?></a></li><?php
							break;
						case NelioABExperimentStatus::RUNNING:
						case NelioABExperimentStatus::FINISHED:
					   	?><li><a href="<?php echo $url . '&action=progress&id=' . $exp_id; ?>"><?php _e( 'The results of the related experiment', 'nelioab' ); ?></a></li><?php
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
