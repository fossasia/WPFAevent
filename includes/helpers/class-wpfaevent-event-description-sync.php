<?php
/**
 * Keeps WordPress edits to imported event descriptions across Eventyay syncs.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks whether an imported event description was edited on this site.
 *
 * Each import that writes the description stores a fingerprint of the saved
 * content and excerpt. When the post no longer matches that fingerprint, the
 * description was edited in WordPress (modal, WP admin, dashboard or REST) and
 * later syncs leave it alone until it is restored from Eventyay.
 */
class Wpfaevent_Event_Description_Sync {

	/**
	 * Latest description received from Eventyay.
	 */
	const REMOTE_META = '_wpfa_eventyay_description';

	/**
	 * Eventyay description that was last written into the post.
	 */
	const APPLIED_META = '_wpfa_eventyay_description_applied';

	/**
	 * Fingerprint of the post content and excerpt right after that write.
	 */
	const HASH_META = '_wpfa_eventyay_description_hash';

	/**
	 * Admin-post action that restores the Eventyay description.
	 */
	const RESTORE_ACTION = 'wpfaevent_restore_eventyay_description';

	/**
	 * Whether the description was edited in WordPress since the last import wrote it.
	 *
	 * Events imported before this tracking existed have no fingerprint and keep
	 * following Eventyay.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 * @return bool
	 */
	public static function has_local_edit( $post_id ) {
		$hash = (string) get_post_meta( absint( $post_id ), self::HASH_META, true );

		return '' !== $hash && self::fingerprint( $post_id ) !== $hash;
	}

	/**
	 * Whether Eventyay has a different description than the one the local edit replaced.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 * @return bool
	 */
	public static function has_newer_remote( $post_id ) {
		$post_id = absint( $post_id );
		$remote  = self::normalize( get_post_meta( $post_id, self::REMOTE_META, true ) );
		$applied = self::normalize( get_post_meta( $post_id, self::APPLIED_META, true ) );

		return $remote !== $applied;
	}

	/**
	 * Record the description an import received, and whether it was written to the post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id     Event post ID.
	 * @param string $description Description from Eventyay.
	 * @param bool   $applied     Whether the import wrote it into the post.
	 * @return void
	 */
	public static function remember_import( $post_id, $description, $applied ) {
		$post_id     = absint( $post_id );
		$description = wp_kses_post( (string) $description );

		update_post_meta( $post_id, self::REMOTE_META, $description );

		if ( $applied ) {
			update_post_meta( $post_id, self::APPLIED_META, $description );
			update_post_meta( $post_id, self::HASH_META, self::fingerprint( $post_id ) );
		}
	}

	/**
	 * Replace the local description with the latest one from Eventyay.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 * @return bool
	 */
	public static function restore( $post_id ) {
		$post_id     = absint( $post_id );
		$description = wp_kses_post( (string) get_post_meta( $post_id, self::REMOTE_META, true ) );
		$result      = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $description,
				'post_excerpt' => $description,
			),
			true
		);

		if ( is_wp_error( $result ) || ! $result ) {
			return false;
		}

		self::remember_import( $post_id, $description, true );

		return true;
	}

	/**
	 * Get the editor note for a locally edited imported description.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 * @return string Empty when the event follows Eventyay.
	 */
	public static function get_note( $post_id ) {
		if ( ! self::has_local_edit( $post_id ) ) {
			return '';
		}

		if ( self::has_newer_remote( $post_id ) ) {
			return __( 'Description edited on this site. Eventyay has a newer description, which syncs will not apply.', 'wpfaevent' );
		}

		return __( 'Description edited on this site. Eventyay syncs will not change it.', 'wpfaevent' );
	}

	/**
	 * Build the nonce-protected "Restore from Eventyay" URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 * @return string
	 */
	public static function get_restore_url( $post_id ) {
		$post_id = absint( $post_id );

		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => self::RESTORE_ACTION,
					'post_id' => $post_id,
				),
				admin_url( 'admin-post.php' )
			),
			self::RESTORE_ACTION . '_' . $post_id
		);
	}

	/**
	 * Handle the "Restore from Eventyay" admin-post request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_restore_request() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		check_admin_referer( self::RESTORE_ACTION . '_' . $post_id );

		if ( ! $post_id || 'wpfa_event' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to edit this event.', 'wpfaevent' ), 403 );
		}

		if ( ! self::restore( $post_id ) ) {
			wp_die( esc_html__( 'Could not restore the Eventyay description.', 'wpfaevent' ) );
		}

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : (string) get_edit_post_link( $post_id, 'url' ) );
		exit;
	}

	/**
	 * Fingerprint the description fields currently stored on the post.
	 *
	 * @param int $post_id Event post ID.
	 * @return string
	 */
	private static function fingerprint( $post_id ) {
		$post_id = absint( $post_id );

		return md5( self::normalize( get_post_field( 'post_content', $post_id ) ) . "\0" . self::normalize( get_post_field( 'post_excerpt', $post_id ) ) );
	}

	/**
	 * Normalize text so form round-trips (CRLF line endings, outer whitespace) do not count as edits.
	 *
	 * @param mixed $text Raw text.
	 * @return string
	 */
	private static function normalize( $text ) {
		return trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $text ) );
	}
}
