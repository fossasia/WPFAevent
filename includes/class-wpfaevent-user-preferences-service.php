<?php
/**
 * User Preferences Service.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

/**
 * User Preferences Service class.
 *
 * Handles retrieval and updates of user preference configurations, such as bookmarked events.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_User_Preferences_Service {

	/**
	 * Meta key for bookmarked events.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	private static $bookmark_meta_key = 'wpfa_bookmarked_events';

	/**
	 * Get bookmarked event IDs for a user.
	 *
	 * @since    1.0.0
	 * @param    int $user_id User ID. Defaults to current user.
	 * @return   array<int>   Array of bookmarked event post IDs.
	 */
	public static function get_bookmarked_events( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array();
		}

		$bookmarks = get_user_meta( $user_id, self::$bookmark_meta_key, true );

		return array_values( array_filter( array_map( 'absint', (array) $bookmarks ) ) );
	}

	/**
	 * Check if an event is bookmarked by a user.
	 *
	 * @since    1.0.0
	 * @param    int $event_id Event post ID.
	 * @param    int $user_id  User ID. Defaults to current user.
	 * @return   bool          True if the event is bookmarked.
	 */
	public static function is_event_bookmarked( $event_id, $user_id = 0 ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return false;
		}

		$bookmarked_events = self::get_bookmarked_events( $user_id );

		return in_array( $event_id, $bookmarked_events, true );
	}

	/**
	 * Toggle the bookmark status of an event for a user.
	 *
	 * @since    1.0.0
	 * @param    int $event_id Event post ID.
	 * @param    int $user_id  User ID. Defaults to current user.
	 * @return   bool          True if bookmarked, false if unbookmarked.
	 */
	public static function toggle_bookmark( $event_id, $user_id = 0 ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$bookmarked_events = self::get_bookmarked_events( $user_id );
		$is_bookmarked     = in_array( $event_id, $bookmarked_events, true );

		if ( $is_bookmarked ) {
			$bookmarked_events = array_diff( $bookmarked_events, array( $event_id ) );
			$new_state         = false;
		} else {
			$bookmarked_events[] = $event_id;
			$new_state           = true;
		}

		update_user_meta( $user_id, self::$bookmark_meta_key, array_values( $bookmarked_events ) );

		return $new_state;
	}
}
