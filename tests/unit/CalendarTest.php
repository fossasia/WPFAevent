<?php
/**
 * Unit tests for calendar exports.
 *
 * @package Wpfaevent
 */

/**
 * Calendar export unit tests.
 */
class CalendarTest extends WP_UnitTestCase {

	/**
	 * Build normalized calendar data for an all-day event.
	 *
	 * @return array
	 */
	private function all_day_event() {
		return array(
			'uid'             => 'wpfaevent-event-69@example.test',
			'dtstamp'         => new DateTimeImmutable( '2026-06-05T00:00:00Z' ),
			'title'           => "FOSS, Asia; Event \\ Test\nNext",
			'description'     => "Line one\nLine two, with; chars \\ here",
			'location'        => 'Singapore, Hall A',
			'url'             => 'https://example.test/events/fossasia?x=1,2',
			'timezone_string' => 'Asia/Colombo',
			'all_day'         => true,
			'start_date'      => '2026-06-05',
			'end_date'        => '2026-06-06',
		);
	}

	/**
	 * Build normalized calendar data for a timed event.
	 *
	 * @return array
	 */
	private function timed_event() {
		$timezone = new DateTimeZone( 'Asia/Colombo' );

		return array(
			'uid'             => 'wpfaevent-event-70@example.test',
			'dtstamp'         => new DateTimeImmutable( '2026-06-05T00:00:00Z' ),
			'title'           => 'Timed Event',
			'timezone_string' => 'Asia/Colombo',
			'all_day'         => false,
			'start_datetime'  => new DateTimeImmutable( '2026-06-05 10:00:00', $timezone ),
			'end_datetime'    => new DateTimeImmutable( '2026-06-05 12:15:00', $timezone ),
		);
	}

	/**
	 * Return the query parameters of a Google Calendar URL.
	 *
	 * @param string $url Google Calendar URL.
	 * @return array
	 */
	private function google_calendar_params( $url ) {
		$params = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $params );

		return $params;
	}

	/**
	 * All-day exports should use date-only values with an exclusive end date.
	 */
	public function test_all_day_ics_uses_exclusive_date_only_range() {
		$ics = Wpfaevent_Calendar::build_event_ics_content( $this->all_day_event() );

		$this->assertStringContainsString( 'BEGIN:VCALENDAR', $ics );
		$this->assertStringContainsString( 'DTSTART;VALUE=DATE:20260605', $ics );
		$this->assertStringContainsString( 'DTEND;VALUE=DATE:20260607', $ics );
	}

	/**
	 * ICS text properties should escape commas, semicolons, backslashes, and newlines.
	 */
	public function test_all_day_ics_escapes_text_properties() {
		$ics = Wpfaevent_Calendar::build_event_ics_content( $this->all_day_event() );

		$this->assertStringContainsString( 'SUMMARY:FOSS\, Asia\; Event \\\\ Test\nNext', $ics );
		$this->assertStringContainsString( 'DESCRIPTION:Line one\nLine two\, with\; chars \\\\ here', $ics );
		$this->assertStringContainsString( 'LOCATION:Singapore\, Hall A', $ics );
		$this->assertStringContainsString( 'URL:https://example.test/events/fossasia?x=1\,2', $ics );
	}

	/**
	 * All-day Google Calendar URLs should template the event with an exclusive end date.
	 */
	public function test_all_day_google_calendar_url_templates_the_event() {
		$event  = $this->all_day_event();
		$url    = Wpfaevent_Calendar::build_google_calendar_url( $event );
		$params = $this->google_calendar_params( $url );

		$this->assertStringContainsString( 'https://calendar.google.com/calendar/render?', $url );
		$this->assertSame( 'TEMPLATE', $params['action'] ?? '' );
		$this->assertSame( '20260605/20260607', $params['dates'] ?? '' );
		$this->assertSame( $event['title'], $params['text'] ?? '' );
		$this->assertSame( $event['description'] . "\n\n" . $event['url'], $params['details'] ?? '' );
		$this->assertSame( 'Asia/Colombo', $params['ctz'] ?? '' );
	}

	/**
	 * Timezone formatting should expose hours, minutes, and a readable label.
	 */
	public function test_timezone_formatting_includes_the_utc_offset() {
		$this->assertSame( '+06:00', Wpfaevent_Schedule_Helper::format_timezone_offset( 'Asia/Dhaka' ) );
		$this->assertSame( 'Asia/Dhaka (UTC+06:00)', Wpfaevent_Schedule_Helper::format_timezone_label( 'Asia/Dhaka' ) );
	}

	/**
	 * Timed exports should convert event timezone boundaries to UTC.
	 */
	public function test_timed_ics_converts_event_timezone_to_utc() {
		$ics = Wpfaevent_Calendar::build_event_ics_content( $this->timed_event() );

		$this->assertStringContainsString( 'DTSTART:20260605T043000Z', $ics );
		$this->assertStringContainsString( 'DTEND:20260605T064500Z', $ics );
	}

	/**
	 * Timed Google Calendar ranges should use UTC date-times.
	 */
	public function test_timed_google_calendar_dates_use_a_utc_range() {
		$this->assertSame(
			'20260605T043000Z/20260605T064500Z',
			Wpfaevent_Calendar::format_google_calendar_dates( $this->timed_event() )
		);
	}

	/**
	 * Exporting an event that does not exist should fail with a safe 404.
	 */
	public function test_missing_event_ics_returns_a_not_found_error() {
		$error = Wpfaevent_Calendar::generate_event_ics( 999999 );

		$this->assertWPError( $error );
		$this->assertSame( 'wpfaevent_calendar_event_not_found', $error->get_error_code() );
		$this->assertSame( array( 'status' => 404 ), $error->get_error_data() );
	}

	/**
	 * Posts of another type should not leak through the event export.
	 */
	public function test_non_event_post_ics_returns_a_not_found_error() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );

		$error = Wpfaevent_Calendar::generate_event_ics( $post_id );

		$this->assertWPError( $error );
		$this->assertSame( 'wpfaevent_calendar_event_not_found', $error->get_error_code() );
	}

	/**
	 * Invalid event IDs should not produce an ICS URL.
	 */
	public function test_invalid_event_id_has_no_ics_url() {
		$this->assertSame( '', Wpfaevent_Calendar::get_event_ics_url( 0 ) );
	}

	/**
	 * Valid event IDs should resolve to the REST ICS route.
	 */
	public function test_valid_event_id_resolves_to_the_rest_ics_route() {
		$event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );

		$this->assertSame(
			rest_url( 'wpfaevent/v1/events/' . $event_id . '/ics' ),
			Wpfaevent_Calendar::get_event_ics_url( $event_id )
		);
	}
}
