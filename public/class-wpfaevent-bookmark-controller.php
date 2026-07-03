<?php
/**
 * The AJAX controller for event bookmarking.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public
 */

/**
 * AJAX Bookmark Controller class.
 *
 * Handles AJAX requests for toggling event bookmarks.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Bookmark_Controller {

	/**
	 * Toggle bookmark/favorite status of an event for the current logged-in user.
	 *
	 * @since    1.0.0
	 */
	public function ajax_toggle_bookmark() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'wpfa_bookmark_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Verify user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You must be logged in to bookmark events.', 'wpfaevent' ),
				),
				401
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid event ID.', 'wpfaevent' ),
				)
			);
		}

		$event = get_post( $event_id );

		if ( ! $event || 'wpfa_event' !== $event->post_type ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Event not found.', 'wpfaevent' ),
				)
			);
		}

		// Call the User Preferences Service to toggle bookmark state.
		$bookmarked = Wpfaevent_User_Preferences_Service::toggle_bookmark( $event_id );

		if ( $bookmarked ) {
			$message = esc_html__( 'Event bookmarked successfully.', 'wpfaevent' );
		} else {
			$message = esc_html__( 'Event removed from bookmarks.', 'wpfaevent' );
		}

		wp_send_json_success(
			array(
				'bookmarked' => $bookmarked,
				'message'    => $message,
			)
		);
	}
}
