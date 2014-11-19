<?php
/**
 * Plugin Name: Nelio A/B Testing (speed-up AJAX calls)
 * Description: This plugin prevents other plugins from loading when performing certain AJAX calls, making things much faster.
 * Version: 3.3.0
 * Author: Nelio Software
 * Author URI: http://neliosoftware.com
 * Plugin URI: http://wp-abtesting.com
 */

function nelioab_is_ajax_call_relevant() {
	if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX || !isset( $_POST['action'] ) )
		return false;

	if ( 0 === strpos( $_POST['action'], 'nelioab_send_navigation' ) )
		return true;

	if ( 0 === strpos( $_POST['action'], 'nelioab_send_headlines_info' ) )
		return true;

	if ( 0 === strpos( $_POST['action'], 'nelioab_sync_cookies_and_check' ) )
		return true;

	if ( 0 === strpos( $_POST['action'], 'nelioab_send_heatmap_info' ) )
		return true;

	if ( 0 === strpos( $_POST['action'], 'nelioab_sync_heatmaps' ) )
		return true;

	/**
	 * Added on version 1.1.0
	 */
	if ( 0 === strpos( $_POST['action'], 'nelioab_external_page_accessed_action_urls' ) )
		return true;

	/**
	 * Added on version 3.3.0
	 */
	if ( 0 === strpos( $_POST['action'], 'nelioab_track_heatmaps_for_post' ) )
		return true;

	return false;
}


function nelioab_exclude_plugins( $plugins ) {
	if ( nelioab_is_ajax_call_relevant() ) {
		foreach( $plugins as $key => $plugin ) {
			if ( false !== strpos( $plugin, 'nelio-ab-testing' ) ) continue;
			if ( false !== strpos( $plugin, 'custom-permalinks' ) ) continue;
			unset( $plugins[$key] );
		}
	}

	return $plugins;
}
add_filter( 'option_active_plugins', 'nelioab_exclude_plugins' );


function nelioab_fake_theme( $param ) {
	if ( nelioab_is_ajax_call_relevant() )
		return 'nelioab_fake_theme';
	else
		return $param;
}
add_filter( 'stylesheet', 'nelioab_fake_theme' );
add_filter( 'template',   'nelioab_fake_theme' );

