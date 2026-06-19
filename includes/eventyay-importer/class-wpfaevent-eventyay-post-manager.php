<?php
/**
 * Eventyay Importer Post Ingestion Manager.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service class for inserting, updating, and syncing event posts and metadata in WordPress database.
 *
 * @since 1.0.0
 */
class Wpfaevent_Eventyay_Post_Manager {

	/**
	 * Insert or update a WordPress Custom Post Type record.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_data Arguments passed to wp_insert_post/wp_update_post.
	 * @param int   $post_id   Existing post ID to update, or 0 to insert.
	 * @return int|WP_Error New or updated post ID, or WP_Error on database error.
	 */
	public function save_event_post( $post_data, $post_id = 0 ) {
		if ( $post_id ) {
			$post_data['ID'] = absint( $post_id );
			$result_id       = wp_update_post( $post_data, true );
		} else {
			$result_id = wp_insert_post( $post_data, true );
		}

		return $result_id;
	}

	/**
	 * Synchronize a post's metadata using a key-value map.
	 *
	 * Empty values are deleted to keep the database tidy.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id  Target post ID.
	 * @param array $metadata Key-value pairs of metadata fields.
	 * @return void
	 */
	public function sync_event_metadata( $post_id, $metadata ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || empty( $metadata ) || ! is_array( $metadata ) ) {
			return;
		}

		foreach ( $metadata as $key => $value ) {
			$key = sanitize_key( $key );

			if ( '' === $value || null === $value ) {
				delete_post_meta( $post_id, $key );
			} elseif ( is_array( $value ) ) {
				update_post_meta( $post_id, $key, $value );
			} else {
				update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
			}
		}
	}
}
