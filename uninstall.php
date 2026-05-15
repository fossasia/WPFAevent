<?php
/**
 * Uninstall handler for WPFAevent / FOSSASIA Landing plugin.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete uploaded data files in uploads/fossasia-data.
$upload_dir = wp_upload_dir();
$data_dir   = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';

if ( is_dir( $data_dir ) ) {
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$files = glob( $data_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}
	if ( $wp_filesystem && $wp_filesystem->is_dir( $data_dir ) ) {
		$wp_filesystem->rmdir( $data_dir, true );
	}
}

// Remove pages created by the plugin by slug.
$slugs = array( 'fossasia-summit', 'speakers', 'full-schedule', 'admin-dashboard', 'events', 'past-events', 'code-of-conduct' );
foreach ( $slugs as $slug ) {
	$page_object = get_page_by_path( $slug );
	if ( $page_object ) {
		wp_delete_post( $page_object->ID, true );
	}
}
