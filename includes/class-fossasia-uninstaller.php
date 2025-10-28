<?php
/**
 * WPFA Event Uninstaller
 *
 * Handles the cleanup of CPTs, data files, and options.
 *
 * @package WPFA-Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Event_Uninstaller.
 */
class WPFA_Event_Uninstaller {

	/**
	 * Main entry point for cleanup.
	 *
	 * This static method is called to perform all cleanup operations.
	 */
	public static function uninstall() {
		self::delete_plugin_posts();
		self::delete_data_directory();
		flush_rewrite_rules();
	}

	/**
	 * Deletes all CPT posts created by the plugin.
	 */
	private static function delete_plugin_posts() {
		$cpts_to_delete = [
			'wpfa_event',
			'wpfa_speaker',
		];

		foreach ( $cpts_to_delete as $post_type ) {
			$query = new WP_Query( [
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			] );

			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post_id ) {
					wp_delete_post( $post_id, true );
				}
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