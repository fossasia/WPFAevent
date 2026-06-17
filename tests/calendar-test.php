<?php
// phpcs:ignoreFile -- Standalone CLI test defines minimal WordPress stubs and executable assertions.
/**
 * Lightweight calendar export checks.
 *
 * Run with: php tests/calendar-test.php
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}


if ( ! function_exists( 'absint' ) ) {
	/**
	 * Minimal absint() fallback for standalone CLI tests.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Minimal translation fallback for standalone CLI tests.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html__( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Simulate a missing post for invalid event ID tests.
	 *
	 * @param int $post_id Post ID.
	 * @return null
	 */
	function get_post( $post_id ) {
		unset( $post_id );

		return null;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Minimal is_wp_error() fallback for standalone CLI tests.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error fallback for standalone CLI tests.
	 */
	class WP_Error {

		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get the error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

require_once dirname( __DIR__ ) . '/includes/helpers/class-wpfaevent-schedule-helper.php';
require_once dirname( __DIR__ ) . '/includes/class-wpfaevent-calendar.php';

/**
 * Assert that a string contains an expected fragment.
 *
 * @param string $needle  Expected fragment.
 * @param string $haystack Full string.
 * @param string $message Failure message.
 * @return void
 */
function wpfaevent_calendar_test_assert_contains( $needle, $haystack, $message ) {
	if ( false !== strpos( $haystack, $needle ) ) {
		return;
	}

	fwrite( STDERR, $message . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	fwrite( STDERR, 'Missing: ' . $needle . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	exit( 1 );
}

/**
 * Assert strict equality.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Failure message.
 * @return void
 */
function wpfaevent_calendar_test_assert_same( $expected, $actual, $message ) {
	if ( $expected === $actual ) {
		return;
	}

	fwrite( STDERR, $message . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	fwrite( STDERR, 'Expected: ' . $expected . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	fwrite( STDERR, 'Actual: ' . $actual . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	exit( 1 );
}

$all_day_event = array(
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

$all_day_ics = Wpfaevent_Calendar::build_event_ics_content( $all_day_event );

wpfaevent_calendar_test_assert_contains( 'BEGIN:VCALENDAR', $all_day_ics, 'All-day export should start a calendar.' );
wpfaevent_calendar_test_assert_contains( 'DTSTART;VALUE=DATE:20260605', $all_day_ics, 'All-day export should use date-only DTSTART.' );
wpfaevent_calendar_test_assert_contains( 'DTEND;VALUE=DATE:20260607', $all_day_ics, 'All-day export should use exclusive date-only DTEND.' );
wpfaevent_calendar_test_assert_contains( 'SUMMARY:FOSS\, Asia\; Event \\\\ Test\nNext', $all_day_ics, 'Summary should escape ICS text characters.' );
wpfaevent_calendar_test_assert_contains( 'DESCRIPTION:Line one\nLine two\, with\; chars \\\\ here', $all_day_ics, 'Description should escape commas, semicolons, backslashes, and newlines.' );
wpfaevent_calendar_test_assert_contains( 'LOCATION:Singapore\, Hall A', $all_day_ics, 'Location should escape commas.' );
wpfaevent_calendar_test_assert_contains( 'URL:https://example.test/events/fossasia?x=1\,2', $all_day_ics, 'URL should escape commas.' );

$all_day_google_url    = Wpfaevent_Calendar::build_google_calendar_url( $all_day_event );
$all_day_google_query  = parse_url( $all_day_google_url, PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Standalone CLI test does not load WordPress helpers.
$all_day_google_params = array();
parse_str( $all_day_google_query, $all_day_google_params );

wpfaevent_calendar_test_assert_contains( 'https://calendar.google.com/calendar/render?', $all_day_google_url, 'Google Calendar URL should use the calendar render endpoint.' );
wpfaevent_calendar_test_assert_same( 'TEMPLATE', $all_day_google_params['action'] ?? '', 'Google Calendar URL should create an event template.' );
wpfaevent_calendar_test_assert_same( '20260605/20260607', $all_day_google_params['dates'] ?? '', 'Google all-day dates should use an exclusive end date.' );
wpfaevent_calendar_test_assert_same( $all_day_event['title'], $all_day_google_params['text'] ?? '', 'Google Calendar URL should keep the event title.' );
wpfaevent_calendar_test_assert_same( $all_day_event['description'] . "\n\n" . $all_day_event['url'], $all_day_google_params['details'] ?? '', 'Google Calendar URL should include the event description and URL.' );
wpfaevent_calendar_test_assert_same( 'Asia/Colombo', $all_day_google_params['ctz'] ?? '', 'Google Calendar URL should include the event timezone.' );
wpfaevent_calendar_test_assert_same( '+06:00', Wpfaevent_Schedule_Helper::format_timezone_offset( 'Asia/Dhaka' ), 'Timezone offsets should include hours and minutes.' );
wpfaevent_calendar_test_assert_same( 'Asia/Dhaka (UTC+06:00)', Wpfaevent_Schedule_Helper::format_timezone_label( 'Asia/Dhaka' ), 'Timezone labels should append the UTC offset.' );

$timezone    = new DateTimeZone( 'Asia/Colombo' );
$timed_event = array(
	'uid'             => 'wpfaevent-event-70@example.test',
	'dtstamp'         => new DateTimeImmutable( '2026-06-05T00:00:00Z' ),
	'title'           => 'Timed Event',
	'timezone_string' => 'Asia/Colombo',
	'all_day'         => false,
	'start_datetime'  => new DateTimeImmutable( '2026-06-05 10:00:00', $timezone ),
	'end_datetime'    => new DateTimeImmutable( '2026-06-05 12:15:00', $timezone ),
);
$timed_ics   = Wpfaevent_Calendar::build_event_ics_content( $timed_event );

wpfaevent_calendar_test_assert_contains( 'DTSTART:20260605T043000Z', $timed_ics, 'Timed export should convert event timezone start to UTC.' );
wpfaevent_calendar_test_assert_contains( 'DTEND:20260605T064500Z', $timed_ics, 'Timed export should convert event timezone end to UTC.' );
wpfaevent_calendar_test_assert_same( '20260605T043000Z/20260605T064500Z', Wpfaevent_Calendar::format_google_calendar_dates( $timed_event ), 'Google timed dates should use UTC date-time ranges.' );

$missing_event = Wpfaevent_Calendar::generate_event_ics( 999999 );

wpfaevent_calendar_test_assert_same( true, $missing_event instanceof WP_Error, 'Missing event export should return a WP_Error.' );
wpfaevent_calendar_test_assert_same( 'wpfaevent_calendar_event_not_found', $missing_event->get_error_code(), 'Missing event export should use the safe not-found error code.' );
wpfaevent_calendar_test_assert_same( array( 'status' => 404 ), $missing_event->get_error_data(), 'Missing event export should return a 404 status.' );
wpfaevent_calendar_test_assert_same( '', Wpfaevent_Calendar::get_event_ics_url( 0 ), 'Invalid event IDs should not produce an ICS URL.' );

fwrite( STDOUT, 'Calendar tests passed.' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
