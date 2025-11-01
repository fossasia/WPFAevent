<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Fossasia_Landing
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes all pages created by the plugin.
 * This includes static pages and dynamic event pages.
 */
function wpfa_delete_plugin_pages() {
	// Slugs for the static pages created on activation.
	$static_pages_slugs = [
		'speakers',
		'full-schedule',
		'admin-dashboard',
		'events',
		'past-events',
		'code-of-conduct',
	];

	foreach ( $static_pages_slugs as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page ) {
			wp_delete_post( $page->ID, true ); // true to force delete and bypass trash.
		}
	}
}

/**
 * Deletes the plugin's data directory using the WP_Filesystem API.
 */
function wpfa_delete_data_directory() {
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
}

wpfa_delete_plugin_pages();
wpfa_delete_data_directory();
flush_rewrite_rules();