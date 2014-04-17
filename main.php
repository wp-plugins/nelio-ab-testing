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



/*
 * Plugin Name: Nelio A/B Testing
 * Description: Optimize your site based on data, not opinions. With this plugin, you will be able to perform A/B testing (and more) on your wordpress site.
 * Version: 2.0.13
 * Author: Nelio Software
 * Plugin URI: http://wp-abtesting.com
 * Text Domain: nelioab
 */

// PLUGIN VERSION
define( 'NELIOAB_PLUGIN_VERSION', '2.0.13' );

// Plugin dir name...
define( 'NELIOAB_PLUGIN_NAME', 'Nelio A/B Testing' );
define( 'NELIOAB_PLUGIN_DIR_NAME', basename( dirname( __FILE__ ) ) );

// Defining a few important directories
define( 'NELIOAB_ROOT_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'NELIOAB_DIR', NELIOAB_ROOT_DIR . '/includes' );

define( 'NELIOAB_ADMIN_DIR', NELIOAB_DIR . '/admin' );
define( 'NELIOAB_UTILS_DIR', NELIOAB_DIR . '/utils' );
define( 'NELIOAB_MODELS_DIR', NELIOAB_DIR . '/models' );

// Some URLs...
define( 'NELIOAB_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'NELIOAB_BACKEND_URL', 'https://nelioabtesting.appspot.com/_ah/api/nelioab/v4');
define( 'NELIOAB_FEEDBACK_URL', 'https://neliofeedback.appspot.com/_ah/api/feedback/v1');
define( 'NELIOAB_ASSETS_URL', plugins_url() . '/' . NELIOAB_PLUGIN_DIR_NAME . '/assets' );
define( 'NELIOAB_ADMIN_ASSETS_URL', NELIOAB_ASSETS_URL . '/admin' );

function nelioab_i18n() {
	load_plugin_textdomain( 'nelioab', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'nelioab_i18n' );

// Two debug functions pretty useful...
function nelioab_p( $obj, $title = false) {
	if ( $title ) nelioab_e( $title, true );
	echo '<pre style="text-align:left;">'; print_r( $obj ); echo '</pre>';
}
function nelioab_e( $str, $title = false ) {
	if ( $title ) echo '<pre> </pre><pre> </pre>';
	echo '<pre>' . $str . '</pre>';
	if ( $title ) echo '<pre>=========================================</pre>';
}

require_once( NELIOAB_UTILS_DIR . '/cookies.php' );
require_once( NELIOAB_DIR . '/controller.php' );
require_once( NELIOAB_ADMIN_DIR . '/admin-controller.php' );

// Clean old stuff when activating the plugin
require_once( NELIOAB_UTILS_DIR . '/cleaner.php' );
register_activation_hook( __FILE__, 'nelioab_clean' );

// Making sure all alternatives are hidden when the plugin is deactivated
register_activation_hook( __FILE__, 'nelioab_activate_plugin' );
register_deactivation_hook( __FILE__, 'nelioab_deactivate_plugin' );

function nelioab_activate_plugin() {
	// Showing previous page alternatives
	$args = array(
		'post_status'    => 'draft',
		'post_type'      => 'nelioab_alt_page',
		'posts_per_page' => -1,
	);
	$alternative_pages = get_posts( $args );
	foreach ( $alternative_pages as $page ) {
		$page->post_type = 'page';
		wp_update_post( $page );
	}

	// Showing previous page alternatives
	$args = array(
		'post_status'    => 'draft',
		'post_type'      => 'nelioab_alt_post',
		'posts_per_page' => -1,
	);
	$alternative_posts = get_posts( $args );
	foreach ( $alternative_posts as $post ) {
		$post->post_type = 'post';
		wp_update_post( $post );
	}
}

function nelioab_deactivate_plugin() {
	// Hiding alternative pages
	$args = array(
		'meta_key'       => '_is_nelioab_alternative',
		'post_status'    => 'draft',
	);
	$alternative_pages = get_pages( $args );
	foreach ( $alternative_pages as $page ) {
		$page->post_type = 'nelioab_alt_page';
		wp_update_post( $page );
	}

	// Hiding alternative posts
	$args = array(
		'meta_key'       => '_is_nelioab_alternative',
		'post_status'    => 'draft',
		'posts_per_page' => -1,
	);
	$alternative_posts = get_posts( $args );
	foreach ( $alternative_posts as $post ) {
		$post->post_type = 'nelioab_alt_post';
		wp_update_post( $post );
	}
}

add_action( 'wp_ajax_dismiss_upgrade_notice', 'dismiss_upgrade_notice_callback' );
function dismiss_upgrade_notice_callback() {
	require_once( NELIOAB_MODELS_DIR . '/settings.php' );
	NelioABSettings::hide_upgrade_message();
	echo '0';
	die();
}

?>
