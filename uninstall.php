<?php
/**
 * FOSSASIA Event Plugin Uninstaller
 *
 * Handles the cleanup of CPTs, options, and data files.
 *
 * @package FOSSASIA-Event-Plugin
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Delete Custom Post Type posts.
 *
 * @param string $post_type The post type to delete.
 */
function wpfa_delete_cpt_posts( $post_type ) {
	$query = new WP_Query( [
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
			wp_delete_post( $post_id, true ); // true to force delete.
		}
	}
}

// Delete all posts of our CPTs.
wpfa_delete_cpt_posts( 'wpfa_event' );
wpfa_delete_cpt_posts( 'wpfa_speaker' );

// Delete plugin options.
delete_option( 'wpfa_settings' );

// Delete the old data directory from the previous plugin version.
global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}
$upload_dir = wp_upload_dir();
$data_dir   = $upload_dir['basedir'] . '/fossasia-data';
if ( $wp_filesystem->is_dir( $data_dir ) ) {
	$wp_filesystem->rmdir( $data_dir, true );
}