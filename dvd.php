<?php

add_filter( 'posts_results', 'dvd_results' );
add_filter( 'the_posts', 'dvd_posts' );
function dvd_results( $posts ) {
	global $dvd;
	if ( count( $posts ) != 1 )
		return $posts;
	if ( $posts[0]->ID == 120 ) {
		$id = 368;
		$dvd = get_post( $id );
	}
	return $posts;
}
function dvd_posts( $posts ) {
	global $dvd;
	if ( ! is_null( $dvd ) ) {
		$result = array( $dvd );
		$dvd = null;
		return $result;
	}
	return $posts;
}

add_filter( 'wp_title',  'fix_title_for_landing_page', 10, 2 );
function fix_title_for_landing_page( $title, $sep ) {
	global $post;
	if ( $post->ID == 368 ) {
		$front_page_id = get_option( 'page_on_front' );
		$ori_id = 120;
		if ( $ori_id == $front_page_id ) {
			$title = get_bloginfo( 'name' ) . " $sep ";
		}
	}
	return "$title";
}


add_filter( 'comments_array', 'nelioab_comments' );
function nelioab_comments( $arr ) {
	global $post;
	$copy_from = 120;
	$arr = get_comments( array( 'post_id' => $copy_from ) );
	echo '<pre>';
	echo 'Current Post:  ' . $post->ID . "\n";
	echo 'Comments from: ' . $copy_from;
	echo '</pre>';
	return $arr;
}

?>
