<?php
/**
 * FOSSASIA Landing Page Uninstaller
 *
 * Handles the cleanup of pages, data files, and options.
 *
 * @package FOSSASIA-Landing-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FOSSASIA_Uninstaller.
 */
class FOSSASIA_Uninstaller {

	/**
	 * Main entry point for cleanup.
	 *
	 * This static method is called to perform all cleanup operations.
	 */
	public static function uninstall() {
		self::delete_plugin_pages();
		self::delete_data_directory();
		flush_rewrite_rules();
	}

	/**
	 * Deletes all pages created by the plugin.
	 * This includes static pages and dynamic event pages.
	 */
	private static function delete_plugin_pages() {
		// Slugs for the static pages created on activation.
		$static_pages_slugs = array(
			'fossasia-summit',
			'speakers',
			'full-schedule',
			'admin-dashboard',
			'events',
			'past-events',
			'code-of-conduct',
		);

		foreach ( $static_pages_slugs as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $page ) {
				wp_delete_post( $page->ID, true ); // true to force delete and bypass trash.
			}
		}

		// Query for and delete all dynamic event pages identified by the _event_date meta key.
		$event_pages_query = new WP_Query(
			array(
				'post_type'      => 'page',
				'posts_per_page' => -1,
				'meta_key'       => '_wp_page_template',
				'compare'        => 'EXISTS',
				'fields'         => 'ids', // Only get post IDs for efficiency.
			)
		);

		if ( $event_pages_query->have_posts() ) {
			foreach ( $event_pages_query->posts as $page_id ) {
				wp_delete_post( $page_id, true );
			}
		}
	}

	/**
	 * Deletes the plugin's data directory using the WP_Filesystem API.
	 */
	private static function delete_data_directory() {
		global $wp_filesystem;

		// Ensure the filesystem is initialized.
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
}
