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
 * This function is called by the "registed_activation_hook". It is the
 * opposite of the nelioab_deactivate_plugin function. Its aim is to make sure
 * that alternatives (draft post/pages with a metatype) are not visible in the
 * admin area, but can be editted and used.
 */
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

/**
 * This function is called by the "registed_deactivation_hook". Alternatives
 * are regular pages or posts (draft status) with a special metaoption that
 * is used to hide them from the admin menu. When the plugin is deactivated,
 * no one hides the alternatives... In order to prevent them from appearing,
 * we change their post_type to a fake type.
 */
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

