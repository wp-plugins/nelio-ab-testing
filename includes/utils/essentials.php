<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This function is called every time the plugins are loaded, and checks
 * whether our plugin is up-to-date or not. If it isn't, the
 * nelioab_activate_plugin is called.
 */
function nelioab_update_plugin_info_if_required() {
	$last_available_version = get_option( 'nelioab_last_version_installed', false );
	if ( $last_available_version !== NELIOAB_PLUGIN_VERSION ) {
		add_filter( 'init', 'nelioab_activate_plugin' );
	}
}

/**
 * This function is called by the "registed_activation_hook". It is the
 * opposite of the nelioab_deactivate_plugin function. Its aim is to make sure
 * that alternatives (draft post/pages with a metatype) are not visible in the
 * admin area, but can be editted and used.
 *
 * We also make sure that it's called after an update.
 */
function nelioab_activate_plugin() {
	global $wpdb;

	// Old Stuff Compa: rename the meta key that identifies post/page alternatives...
	$wpdb->update(
		$wpdb->postmeta,
		array( 'meta_key' => '_is_nelioab_alternative' ),
		array( 'meta_key' => 'is_nelioab_alternative' )
	);

	// We remove all information about "_is_nelioab_alternative" for posts whose
	// IDs are less than 15. In previous versions of the plugin, Title experiments
	// marked those posts as alternatives (negative IDs from -1 to -15 were used
	// and WordPress interpreted them as positive IDs).
	$query = '' .
		'DELETE FROM ' . $wpdb->postmeta . ' WHERE ' .
			'post_id < 15 AND meta_key = \'_is_nelioab_alternative\'';
	$aux = $wpdb->query( $query );

	// Showing previous page and post alternatives
	$query = 'UPDATE ' . $wpdb->posts . ' SET post_type = %s WHERE post_type = %s';
	$aux = $wpdb->query( $wpdb->prepare( $query, 'post', 'nelioab_alt_post' ) );
	$aux = $wpdb->query( $wpdb->prepare( $query, 'page', 'nelioab_alt_page' ) );

	// Recover previous widget alternatives
	require_once( NELIOAB_ADMIN_DIR . '/widget-exp-admin-controller.php' );
	NelioABWidgetExpAdminController::restore_alternative_widget_backup();

	// Update mu-plugin (if installed)
	if ( !NelioABSettings::is_performance_muplugin_up_to_date() )
		NelioABSettings::update_performance_muplugin();

	// Make sure that the cache uses new classes
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	NelioABExperimentsManager::reset_running_experiments_cache();
	NelioABExperimentsManager::update_running_experiments_cache( true );

	// Save the latest version we use for "(re)activating" the plugin
	update_option( 'nelioab_last_version_installed', NELIOAB_PLUGIN_VERSION );
}

/**
 * This function is called by the "registed_deactivation_hook". Alternatives
 * are regular pages or posts (draft status) with a special metaoption that
 * is used to hide them from the admin menu. When the plugin is deactivated,
 * no one hides the alternatives... In order to prevent them from appearing,
 * we change their post_type to a fake type.
 */
function nelioab_deactivate_plugin() {
	global $wpdb;
	require_once( NELIOAB_ADMIN_DIR . '/widget-exp-admin-controller.php' );

	if ( isset( $_GET['action'] ) && 'clean-and-deactivate' == $_GET['action'] ) {
		// Remove all alternative widgets
		NelioABWidgetExpAdminController::clean_all_alternative_widgets();

		// Remove all alternative pages and posts
		$query = '' .
			'DELETE FROM ' . $wpdb->posts . ' WHERE ' .
				'id IN (' .
					'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE ' .
						'meta_key = \'_is_nelioab_alternative\' ' .
				')';
		$aux = $wpdb->query( $query );

		// Clean all experiments in AE
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );
		for ( $i = 0; $i < 5; ++$i ) {
			try {
				NelioABBackend::remote_get( sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/clean',
					NelioABAccountSettings::get_site_id()
				) );
				break;
			}
			catch ( Exception $e ) {}
		}

		// Remove all Nelio options
		$query = 'DELETE FROM ' . $wpdb->postmeta . ' WHERE meta_key LIKE \'%nelioab%\'';
		$aux = $wpdb->query( $query );
		$query = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'%nelioab%\'';
		$aux = $wpdb->query( $query );
	}
	else {
		// Hiding alternative pages
		$query = '' .
			'UPDATE ' . $wpdb->posts . ' SET post_type = %s WHERE ' .
				'id IN (' .
					'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE ' .
						'meta_key = \'_is_nelioab_alternative\' ' .
				') AND ' .
				'post_type = %s';
		$aux = $wpdb->query( $wpdb->prepare( $query, 'nelioab_alt_post', 'post' ) );
		$aux = $wpdb->query( $wpdb->prepare( $query, 'nelioab_alt_page', 'page' ) );

		// Hiding widget alternatives
		NelioABWidgetExpAdminController::backup_alternative_widgets();
	}
}

/**
 * Remind users that they have to clean their cache after an update
 */
if ( get_option( 'nelioab_cache_notice', false ) !== NELIOAB_PLUGIN_VERSION  )
	add_action( 'admin_notices', 'nelioab_add_cache_notice' );
function nelioab_add_cache_notice() {
	global $pagenow;
	if ( 'plugins.php' == $pagenow || 'update.php' == $pagenow )
		return;
	$message = sprintf(
			__( 'You\'ve recently upgraded to <strong>Nelio A/B Testing %s</strong>. <strong>If you\'re running a cache system</strong> (such as <em>W3 Total Cache</em> or <em>WP Super Cache</em>) <strong>or if your server is behind a CDN</strong>, please <strong>clean all your caches</strong>. Otherwise, you may serve old versions of our tracking scripts and, therefore, the plugin may not work properly.', 'nelioab' ),
			NELIOAB_PLUGIN_VERSION
		);
	?>
	<div class="updated">
		<p>
			<?php echo $message; ?>
			<a id="dismiss-nelioab-cache-notice" style="font-size:80%;" href="#"><?php _e( 'Dismiss' ); ?></a>
		</p>
		<script style="display:none;" type="text/javascript">
		(function($) {
			$('a#dismiss-nelioab-cache-notice').on('click', function() {
				$.post( ajaxurl, {action:'nelioab_dismiss_cache_notice'} );
				$(this).parent().parent().fadeOut();
			});
		})(jQuery);
		</script>
	</div>
	<?php
}
add_action( 'wp_ajax_nelioab_dismiss_cache_notice', 'nelioab_dismiss_cache_notice' );
function nelioab_dismiss_cache_notice() {
	update_option( 'nelioab_cache_notice', NELIOAB_PLUGIN_VERSION );
	die();
}

/**
 * This function returns the URL of the given resource, appending the current
 * version of the plugin. The resource has to be a file in NELIOAB_ASSETS_DIR
 */
function nelioab_asset_link( $resource ) {
	$link = NELIOAB_ASSETS_URL . $resource;
	$link = add_query_arg( array( 'version' => NELIOAB_PLUGIN_VERSION ), $link );
	return $link;
}

/**
 * This function returns the URL of the given resource, appending the current
 * version of the plugin. The resource has to be a file in NELIOAB_ASSETS_DIR
 */
function nelioab_admin_asset_link( $resource ) {
	return nelioab_asset_link( '/admin' . $resource );
}

/**
 * This function always returns the REAL page on front.
 */
function nelioab_get_page_on_front() {
	global $nelioab_controller;
	$hook = has_filter( 'option_page_on_front', array( $nelioab_controller, 'fix_page_on_front' ) );
	if ( false !== $hook )
		remove_filter( 'option_page_on_front', array( $nelioab_controller, 'fix_page_on_front' ) );
	$res = get_option( 'page_on_front', 0 );
	if ( false !== $hook )
		add_filter( 'option_page_on_front', array( $nelioab_controller, 'fix_page_on_front' ) );
	return $res;
}

