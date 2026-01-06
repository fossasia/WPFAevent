<?php
/**
 * Uninstall handler for WPFAevent / FOSSASIA Landing plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete uploaded data files in uploads/fossasia-data
$upload_dir = wp_upload_dir();
$data_dir   = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';

if ( is_dir( $data_dir ) ) {
	$files = glob( $data_dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
	@rmdir( $data_dir );
}

// Remove pages created by the plugin (by slug)
$slugs = array( 'fossasia-summit', 'speakers', 'full-schedule', 'admin-dashboard', 'events', 'past-events', 'code-of-conduct' );
foreach ( $slugs as $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		wp_delete_post( $page->ID, true );
	}
}
