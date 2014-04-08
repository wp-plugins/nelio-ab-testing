<?php

$realpath = realpath( dirname( __FILE__ ) . '/./' );
$filepath = split( 'wp-content', $realpath );

define( 'WP_USE_THEMES', false );
require( '' . $filepath[0] . '/wp-blog-header.php' );

if ( !is_user_logged_in() || !current_user_can( 'delete_users' ) )
	wp_die( 'You do not have permission to see this page.' );

require_once( 'includes/admin/views/content/heatmaps.php' );

?>
