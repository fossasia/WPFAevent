<?php
/**
 * Class EventNavigationHelperTest
 *
 * @package Wpfaevent
 */

/**
 * Unit tests for Event Navigation Helper and filter handling.
 */
class EventNavigationHelperTest extends WP_UnitTestCase {

	/**
	 * Verify that default filter is 'all'.
	 */
	public function test_default_filter_is_all() {
		$this->assertSame( 'all', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( '' ) );
		$this->assertSame( 'all', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( 'invalid' ) );
	}

	/**
	 * Verify that valid filters 'upcoming' and 'past' are accepted.
	 */
	public function test_valid_filters_accepted() {
		$this->assertSame( 'upcoming', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( 'upcoming' ) );
		$this->assertSame( 'past', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( 'past' ) );
	}

	/**
	 * Verify that 'bookmarked' requires a logged-in user.
	 */
	public function test_bookmarked_filter_requires_logged_in_user() {
		wp_set_current_user( 0 );
		$this->assertSame( 'all', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( 'bookmarked' ) );

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$this->assertSame( 'bookmarked', Wpfaevent_Event_Navigation_Helper::get_initial_events_filter( 'bookmarked' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Verify that wpfaevent_events_url filter backward compatibility works for the logo link.
	 */
	public function test_events_url_filter_applies_to_logo_hub_url() {
		add_filter(
			'wpfaevent_events_url',
			static function () {
				return 'https://custom-events.test/listing/';
			}
		);

		$hub_url = apply_filters(
			'wpfaevent_hub_url',
			apply_filters( 'wpfaevent_events_url', home_url( '/events/' ) )
		);

		$this->assertSame( 'https://custom-events.test/listing/', $hub_url );

		remove_all_filters( 'wpfaevent_events_url' );
	}
}
