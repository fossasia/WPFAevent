<?php
/**
 * Class ScheduleNavigationTest
 *
 * @package Wpfaevent
 */

/**
 * Unit tests for Schedule back navigation helper.
 */
class ScheduleNavigationTest extends WP_UnitTestCase {

	/**
	 * Verify back navigation links to event and shows "Back to Event" for a published event.
	 */
	public function test_back_navigation_with_published_event() {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Published Event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		$back_nav = Wpfaevent_Schedule_Helper::get_back_navigation( $event_id );

		$this->assertSame( get_permalink( $event_id ), $back_nav['url'] );
		$this->assertSame( 'Back to Event', $back_nav['text'] );
		$this->assertSame( get_permalink( $event_id ), $back_nav['event_page_url'] );
		$this->assertSame( 'Back to Event', $back_nav['back_button_text'] );
	}

	/**
	 * Verify back navigation falls back to events destination for unpublished events.
	 */
	public function test_back_navigation_with_unpublished_event() {
		$draft_event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Draft Event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'draft',
			)
		);

		$back_nav = Wpfaevent_Schedule_Helper::get_back_navigation( $draft_event_id );

		$this->assertSame( home_url( '/events/' ), $back_nav['url'] );
		$this->assertSame( 'Back to Events', $back_nav['text'] );
	}

	/**
	 * Verify back navigation falls back to events destination for invalid IDs and non-event post types.
	 */
	public function test_back_navigation_with_invalid_or_non_event_id() {
		$non_event_post_id = $this->factory->post->create(
			array(
				'post_title'  => 'Standard Post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		foreach ( array( 0, -1, 999999, $non_event_post_id ) as $invalid_id ) {
			$back_nav = Wpfaevent_Schedule_Helper::get_back_navigation( $invalid_id );

			$this->assertSame( home_url( '/events/' ), $back_nav['url'] );
			$this->assertSame( 'Back to Events', $back_nav['text'] );
		}
	}

	/**
	 * Verify back navigation respects the configured wpfaevent_events_url filter.
	 */
	public function test_back_navigation_with_configured_events_url() {
		$custom_url = 'https://custom-events.test/all-events/';

		add_filter(
			'wpfaevent_events_url',
			static function () use ( $custom_url ) {
				return $custom_url;
			}
		);

		$back_nav = Wpfaevent_Schedule_Helper::get_back_navigation( 0 );

		$this->assertSame( $custom_url, $back_nav['url'] );
		$this->assertSame( 'Back to Events', $back_nav['text'] );

		remove_all_filters( 'wpfaevent_events_url' );
	}
}
