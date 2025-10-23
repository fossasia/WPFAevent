<?php
<<<<<<< Updated upstream

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
=======
/**
 * Fired when the plugin is uninstalled.
>>>>>>> Stashed changes
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
<<<<<<< Updated upstream
=======
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
>>>>>>> Stashed changes
