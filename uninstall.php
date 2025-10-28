<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// Get all posts of the custom post types.
$cpt_slugs = array( 'wpfa_event', 'wpfa_speaker' );
foreach ( $cpt_slugs as $cpt_slug ) {
	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => $cpt_slug,
			'post_status' => 'any',
		)
	);
	// Delete each post.
	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}
}
// Note: This simple uninstaller does not remove the `fossasia-data` directory
// from wp-content/uploads, as that was part of the old implementation.
// A more robust uninstaller could be added here if needed.
