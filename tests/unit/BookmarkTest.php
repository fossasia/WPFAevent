<?php
/**
 * Class BookmarkTest
 *
 * @package Wpfaevent
 */

/**
 * Unit tests for Event Bookmarking preferences and service.
 */
class BookmarkTest extends WP_UnitTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Setup mock objects.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test user.
		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		// Create a test event post.
		$this->event_id = $this->factory->post->create(
			array(
				'post_title' => 'Test Event',
				'post_type'  => 'wpfa_event',
			)
		);
	}

	/**
	 * Verify that Wpfaevent_User_Preferences_Service is registered.
	 */
	public function test_service_class_exists() {
		$this->assertTrue( class_exists( 'Wpfaevent_User_Preferences_Service' ) );
	}

	/**
	 * Verify retrieving bookmarked events on empty state.
	 */
	public function test_get_bookmarked_events_empty() {
		$bookmarks = Wpfaevent_User_Preferences_Service::get_bookmarked_events( $this->user_id );
		$this->assertIsArray( $bookmarks );
		$this->assertEmpty( $bookmarks );
	}

	/**
	 * Verify bookmark toggling adds and removes correctly.
	 */
	public function test_toggle_bookmark() {
		// Toggle ON.
		$result_on = Wpfaevent_User_Preferences_Service::toggle_bookmark( $this->event_id, $this->user_id );
		$this->assertTrue( $result_on );

		// Check is_event_bookmarked.
		$is_bookmarked = Wpfaevent_User_Preferences_Service::is_event_bookmarked( $this->event_id, $this->user_id );
		$this->assertTrue( $is_bookmarked );

		// Check get_bookmarked_events.
		$bookmarks = Wpfaevent_User_Preferences_Service::get_bookmarked_events( $this->user_id );
		$this->assertContains( $this->event_id, $bookmarks );

		// Toggle OFF.
		$result_off = Wpfaevent_User_Preferences_Service::toggle_bookmark( $this->event_id, $this->user_id );
		$this->assertFalse( $result_off );

		// Check is_event_bookmarked again.
		$is_bookmarked_after = Wpfaevent_User_Preferences_Service::is_event_bookmarked( $this->event_id, $this->user_id );
		$this->assertFalse( $is_bookmarked_after );
	}
}
