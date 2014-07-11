<?php

$realpath = realpath( dirname( __FILE__ ) . '/./' );
$filepath = explode( 'wp-content', $realpath );

require_once( '' . $filepath[0] . 'wp-config.php' );
$wp->init();
$wp->parse_request();
$wp->query_posts();
$wp->register_globals();
$wp->send_headers();

if ( !is_user_logged_in() || !current_user_can( 'delete_users' ) )
	wp_die( 'You do not have permission to see this page.' );

require_once( 'includes/admin/views/content/heatmaps.php' );

