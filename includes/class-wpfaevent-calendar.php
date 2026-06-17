<?php
/**
 * Calendar export support for WPFA events.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and serves iCalendar output for event posts.
 */
class Wpfaevent_Calendar {

	/**
	 * REST namespace for calendar routes.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'wpfaevent/v1';

	/**
	 * Register calendar REST routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/events/(?P<id>\d+)/ics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle_event_ics_request' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'description'       => __( 'Event post ID.', 'wpfaevent' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Return a REST URL for an event ICS export.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_ics_url( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return '';
		}

		return rest_url( self::REST_NAMESPACE . '/events/' . $event_id . '/ics' );
	}

	/**
	 * Return a Google Calendar template URL for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_google_calendar_url( $event_id ) {
		$event = self::get_event_calendar_data( $event_id );

		if ( is_wp_error( $event ) ) {
			return '';
		}

		return self::build_google_calendar_url( $event );
	}

	/**
	 * Build a Google Calendar template URL from normalized event data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Normalized event calendar data.
	 * @return string
	 */
	public static function build_google_calendar_url( $event ) {
		$dates = self::format_google_calendar_dates( $event );

		if ( '' === $dates ) {
			return '';
		}

		$details = isset( $event['description'] ) ? trim( (string) $event['description'] ) : '';
		$url     = ! empty( $event['url'] ) ? trim( (string) $event['url'] ) : '';

		if ( '' !== $url && false === strpos( $details, $url ) ) {
			$details = trim( $details . "\n\n" . $url );
		}

		$query = array(
			'action' => 'TEMPLATE',
			'text'   => isset( $event['title'] ) ? (string) $event['title'] : '',
			'dates'  => $dates,
			'trp'    => 'false',
		);

		if ( '' !== $details ) {
			$query['details'] = $details;
		}

		if ( ! empty( $event['location'] ) ) {
			$query['location'] = (string) $event['location'];
		}

		$timezone_string = ! empty( $event['timezone_string'] ) ? (string) $event['timezone_string'] : '';

		if ( '' === $timezone_string && ! empty( $event['timezone'] ) ) {
			$timezone_string = (string) $event['timezone'];
		}

		if ( '' !== $timezone_string ) {
			$query['ctz'] = $timezone_string;
		}

		return 'https://calendar.google.com/calendar/render?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Handle an event ICS export request.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_event_ics_request( $request ) {
		$event_id = absint( $request['id'] );
		$calendar = self::generate_event_ics( $event_id );

		if ( is_wp_error( $calendar ) ) {
			return $calendar;
		}

		$response = new WP_REST_Response(
			array(
				'_wpfaevent_calendar' => true,
				'calendar'            => $calendar,
			),
			200
		);

		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="' . self::get_event_ics_filename( $event_id ) . '"' );
		$response->header( 'X-Content-Type-Options', 'nosniff' );

		return $response;
	}

	/**
	 * Serve calendar REST responses as raw text/calendar instead of JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param bool             $served  Whether the request has already been served.
	 * @param WP_HTTP_Response $result  Result to send to the client.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @param WP_REST_Server   $server  Server instance.
	 * @return bool
	 */
	public static function serve_rest_calendar( $served, $result, $request, $server ) {
		unset( $server );

		if ( $served || ! $request || ! preg_match( '#^/' . preg_quote( self::REST_NAMESPACE, '#' ) . '/events/\d+/ics$#', $request->get_route() ) ) {
			return $served;
		}

		$response = rest_ensure_response( $result );
		$data     = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['_wpfaevent_calendar'] ) || ! isset( $data['calendar'] ) ) {
			return $served;
		}

		status_header( $response->get_status() );
		header( 'Content-Type: text/calendar; charset=utf-8', true );

		foreach ( $response->get_headers() as $header => $value ) {
			if ( 'Content-Type' === $header ) {
				continue;
			}

			header( $header . ': ' . $value, true );
		}

		echo (string) $data['calendar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iCalendar content is escaped by the builder.

		return true;
	}

	/**
	 * Generate iCalendar output for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string|WP_Error
	 */
	public static function generate_event_ics( $event_id ) {
		$event = self::get_event_calendar_data( $event_id );

		if ( is_wp_error( $event ) ) {
			return $event;
		}

		return self::build_event_ics_content( $event );
	}

	/**
	 * Build iCalendar output from normalized event data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Normalized event calendar data.
	 * @return string
	 */
	public static function build_event_ics_content( $event ) {
		$dtstamp = isset( $event['dtstamp'] ) && $event['dtstamp'] instanceof DateTimeInterface
			? $event['dtstamp']
			: new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//FOSSASIA//WPFAevent//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
		);

		if ( ! empty( $event['title'] ) ) {
			$lines[] = self::text_property( 'X-WR-CALNAME', $event['title'] );
		}

		if ( ! empty( $event['timezone_string'] ) ) {
			$lines[] = self::text_property( 'X-WR-TIMEZONE', $event['timezone_string'] );
		}

		$lines[] = 'BEGIN:VEVENT';
		$lines[] = 'UID:' . self::sanitize_ical_token( isset( $event['uid'] ) ? $event['uid'] : '' );
		$lines[] = 'DTSTAMP:' . self::format_ics_utc( $dtstamp );

		if ( ! empty( $event['all_day'] ) ) {
			$start_date = isset( $event['start_date'] ) ? self::sanitize_ics_date_value( $event['start_date'] ) : '';
			$end_date   = isset( $event['end_date'] ) ? self::sanitize_ics_date_value( $event['end_date'] ) : '';

			if ( '' !== $start_date ) {
				$lines[] = 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $start_date );
				$lines[] = 'DTEND;VALUE=DATE:' . self::format_ics_exclusive_end_date( $end_date ? $end_date : $start_date );
			}
		} else {
			if ( ! empty( $event['start_datetime'] ) && $event['start_datetime'] instanceof DateTimeInterface ) {
				$lines[] = 'DTSTART:' . self::format_ics_utc( $event['start_datetime'] );
			}

			if ( ! empty( $event['end_datetime'] ) && $event['end_datetime'] instanceof DateTimeInterface ) {
				$lines[] = 'DTEND:' . self::format_ics_utc( $event['end_datetime'] );
			}
		}

		$lines[] = self::text_property( 'SUMMARY', isset( $event['title'] ) ? $event['title'] : '' );

		if ( ! empty( $event['description'] ) ) {
			$lines[] = self::text_property( 'DESCRIPTION', $event['description'] );
		}

		if ( ! empty( $event['location'] ) ) {
			$lines[] = self::text_property( 'LOCATION', $event['location'] );
		}

		if ( ! empty( $event['url'] ) ) {
			$lines[] = self::text_property( 'URL', $event['url'] );
		}

		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';

		return self::render_ics_lines( $lines );
	}

	/**
	 * Get normalized calendar data for an event post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array|WP_Error
	 */
	public static function get_event_calendar_data( $event_id ) {
		$event_id = absint( $event_id );
		$post     = get_post( $event_id );

		if ( ! $post || 'wpfa_event' !== get_post_type( $post ) ) {
			return new WP_Error(
				'wpfaevent_calendar_event_not_found',
				esc_html__( 'The requested event could not be found.', 'wpfaevent' ),
				array( 'status' => 404 )
			);
		}

		if ( ! self::current_user_can_read_event( $post ) ) {
			return new WP_Error(
				'wpfaevent_calendar_event_not_found',
				esc_html__( 'The requested event could not be found.', 'wpfaevent' ),
				array( 'status' => 404 )
			);
		}

		$timezone_string = self::get_event_timezone_string( $event_id );
		$timezone        = self::get_timezone_object( $timezone_string );
		$all_day         = self::get_event_all_day( $event_id );
		$start_date      = self::sanitize_ics_date_value( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
		$end_date        = self::sanitize_ics_date_value( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
		$start_time      = self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_start_time', true ) );
		$end_time        = self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_end_time', true ) );
		$starts_at       = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_starts_at', true ) );
		$ends_at         = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_ends_at', true ) );
		$start_datetime  = null;
		$end_datetime    = null;

		if ( ! $all_day ) {
			$start_datetime = self::build_event_datetime( $starts_at, $start_date, $start_time, $timezone );
			$end_datetime   = self::build_event_datetime( $ends_at, $end_date ? $end_date : $start_date, $end_time, $timezone );

			if ( $start_datetime && $end_datetime && $end_datetime <= $start_datetime && '' === $end_date && '' !== $end_time ) {
				$end_datetime = $end_datetime->modify( '+1 day' );
			}

			if ( ! $start_datetime ) {
				$all_day = true;
			}
		}

		if ( $all_day && '' === $start_date && $start_datetime instanceof DateTimeInterface ) {
			$start_date = $start_datetime->setTimezone( $timezone )->format( 'Y-m-d' );
		}

		if ( $all_day && '' === $end_date && $end_datetime instanceof DateTimeInterface ) {
			$end_date = $end_datetime->setTimezone( $timezone )->format( 'Y-m-d' );
		}

		if ( '' === $start_date && ! $start_datetime ) {
			return new WP_Error(
				'wpfaevent_calendar_event_missing_start',
				esc_html__( 'The event does not have a valid start date.', 'wpfaevent' ),
				array( 'status' => 400 )
			);
		}

		$title        = get_the_title( $event_id );
		$external_url = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_url', true ) );
		$url          = $external_url ? $external_url : get_permalink( $event_id );
		$description  = self::get_event_description( $event_id, $post );
		$location     = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );

		return array(
			'id'              => $event_id,
			'uid'             => self::get_event_uid( $event_id ),
			'dtstamp'         => new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ),
			'title'           => $title,
			'description'     => $description,
			'location'        => $location,
			'url'             => $url,
			'timezone'        => $timezone_string,
			'timezone_string' => $timezone_string,
			'timezone_label'  => self::format_timezone_label( $timezone_string ),
			'all_day'         => $all_day,
			'start_date'      => $all_day ? $start_date : '',
			'end_date'        => $all_day ? ( $end_date ? $end_date : $start_date ) : '',
			'start_datetime'  => $all_day ? null : $start_datetime,
			'end_datetime'    => $all_day ? null : $end_datetime,
			'start_content'   => $all_day ? $start_date : ( $start_datetime ? $start_datetime->format( DATE_ATOM ) : '' ),
			'end_content'     => $all_day ? ( $end_date ? $end_date : $start_date ) : ( $end_datetime ? $end_datetime->format( DATE_ATOM ) : '' ),
			'date_label'      => self::format_date_label( $all_day, $start_date, $end_date, $start_datetime, $end_datetime, $timezone ),
			'time_label'      => $all_day ? __( 'All day', 'wpfaevent' ) : self::format_time_label( $start_datetime, $end_datetime, $timezone ),
		);
	}

	/**
	 * Get an event timezone string with site fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_timezone_string( $event_id ) {
		if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
			return Wpfaevent_Meta_Event::get_event_timezone( $event_id );
		}

		$timezone = wp_timezone_string();

		if ( '' !== trim( (string) $timezone ) ) {
			return $timezone;
		}

		return wp_timezone()->getName();
	}

	/**
	 * Escape an iCalendar text value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function escape_ical_text( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
		$value = str_replace( '\\', '\\\\', $value );
		$value = str_replace( ';', '\;', $value );
		$value = str_replace( ',', '\,', $value );
		$value = preg_replace( "/\r\n|\r|\n/", '\\n', $value );

		return null === $value ? '' : $value;
	}

	/**
	 * Fold an iCalendar line to the recommended length.
	 *
	 * @since 1.0.0
	 *
	 * @param string $line Unfolded line.
	 * @return string
	 */
	public static function fold_ical_line( $line ) {
		$line = (string) $line;

		if ( strlen( $line ) <= 75 ) {
			return $line;
		}

		$chars = preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY );

		if ( ! is_array( $chars ) || empty( $chars ) ) {
			return wordwrap( $line, 75, "\r\n ", true );
		}

		$lines   = array();
		$current = '';

		foreach ( $chars as $char ) {
			if ( '' !== $current && strlen( $current . $char ) > 75 ) {
				$lines[] = $current;
				$current = ' ' . $char;
				continue;
			}

			$current .= $char;
		}

		if ( '' !== $current ) {
			$lines[] = $current;
		}

		return implode( "\r\n", $lines );
	}

	/**
	 * Format a datetime in iCalendar UTC form.
	 *
	 * @since 1.0.0
	 *
	 * @param DateTimeInterface $datetime Datetime.
	 * @return string
	 */
	public static function format_ics_utc( $datetime ) {
		$utc = new DateTimeZone( 'UTC' );

		if ( $datetime instanceof DateTimeImmutable ) {
			$datetime = $datetime->setTimezone( $utc );
		} else {
			$datetime = ( new DateTimeImmutable( $datetime->format( DATE_ATOM ) ) )->setTimezone( $utc );
		}

		return $datetime->format( 'Ymd\THis\Z' );
	}

	/**
	 * Format a Google Calendar dates parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Normalized event calendar data.
	 * @return string
	 */
	public static function format_google_calendar_dates( $event ) {
		if ( ! empty( $event['all_day'] ) ) {
			$start_date = isset( $event['start_date'] ) ? self::sanitize_ics_date_value( $event['start_date'] ) : '';
			$end_date   = isset( $event['end_date'] ) ? self::sanitize_ics_date_value( $event['end_date'] ) : '';

			if ( '' === $start_date ) {
				return '';
			}

			$end_date = self::format_ics_exclusive_end_date( '' !== $end_date ? $end_date : $start_date );

			return str_replace( '-', '', $start_date ) . '/' . $end_date;
		}

		if ( empty( $event['start_datetime'] ) || ! $event['start_datetime'] instanceof DateTimeInterface ) {
			return '';
		}

		$start_datetime = $event['start_datetime'];
		$end_datetime   = ! empty( $event['end_datetime'] ) && $event['end_datetime'] instanceof DateTimeInterface
			? $event['end_datetime']
			: ( new DateTimeImmutable( $start_datetime->format( DATE_ATOM ) ) )->modify( '+1 hour' );

		return self::format_ics_utc( $start_datetime ) . '/' . self::format_ics_utc( $end_datetime );
	}

	/**
	 * Create a folded text property line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 * @return string
	 */
	private static function text_property( $name, $value ) {
		return self::fold_ical_line( $name . ':' . self::escape_ical_text( $value ) );
	}

	/**
	 * Render iCalendar lines with CRLF line endings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lines Lines.
	 * @return string
	 */
	private static function render_ics_lines( $lines ) {
		$lines = array_filter(
			array_map(
				static function ( $line ) {
					return trim( (string) $line );
				},
				$lines
			),
			static function ( $line ) {
				return '' !== $line;
			}
		);

		return implode( "\r\n", $lines ) . "\r\n";
	}

	/**
	 * Build a datetime from normalized meta or date/time fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $stored_datetime Stored ISO datetime.
	 * @param string       $date            Date.
	 * @param string       $time            Time.
	 * @param DateTimeZone $timezone        Event timezone.
	 * @return DateTimeImmutable|null
	 */
	private static function build_event_datetime( $stored_datetime, $date, $time, $timezone ) {
		$stored_datetime = trim( (string) $stored_datetime );

		if ( '' !== $stored_datetime ) {
			try {
				return ( new DateTimeImmutable( $stored_datetime ) )->setTimezone( $timezone );
			} catch ( Exception $exception ) {
				unset( $exception );
				$stored_datetime = '';
			}
		}

		$date = self::sanitize_ics_date_value( $date );
		$time = self::sanitize_time_value( $time );

		if ( '' === $date || '' === $time ) {
			return null;
		}

		try {
			return new DateTimeImmutable( $date . ' ' . $time, $timezone );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Sanitize a date string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $date Date.
	 * @return string
	 */
	private static function sanitize_ics_date_value( $date ) {
		if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
			return Wpfaevent_Meta_Event::sanitize_date_value( $date );
		}

		$date = is_scalar( $date ) ? trim( (string) $date ) : '';

		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
	}

	/**
	 * Sanitize a time string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $time Time.
	 * @return string
	 */
	private static function sanitize_time_value( $time ) {
		if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
			return Wpfaevent_Meta_Event::sanitize_time_value( $time );
		}

		$time = is_scalar( $time ) ? trim( (string) $time ) : '';

		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $time ) ) {
			return '';
		}

		return substr( $time, 0, 5 );
	}

	/**
	 * Determine if an event is all-day.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	private static function get_event_all_day( $event_id ) {
		if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
			return Wpfaevent_Meta_Event::get_event_all_day( $event_id );
		}

		return true;
	}

	/**
	 * Build an exclusive all-day DTEND value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return string
	 */
	private static function format_ics_exclusive_end_date( $date ) {
		$date = self::sanitize_ics_date_value( $date );

		if ( '' === $date ) {
			return '';
		}

		try {
			return ( new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( 'UTC' ) ) )->modify( '+1 day' )->format( 'Ymd' );
		} catch ( Exception $exception ) {
			return '';
		}
	}

	/**
	 * Get a timezone object with site fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone_string Timezone string.
	 * @return DateTimeZone
	 */
	private static function get_timezone_object( $timezone_string ) {
		try {
			return new DateTimeZone( $timezone_string );
		} catch ( Exception $exception ) {
			return wp_timezone();
		}
	}

	/**
	 * Determine whether the current request can read an event.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Event post.
	 * @return bool
	 */
	private static function current_user_can_read_event( $post ) {
		if ( function_exists( 'is_post_publicly_viewable' ) && is_post_publicly_viewable( $post ) ) {
			return true;
		}

		return current_user_can( 'read_post', $post->ID );
	}

	/**
	 * Get a stable event UID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function get_event_uid( $event_id ) {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( empty( $host ) ) {
			$host = 'wpfaevent.local';
		}

		return 'wpfaevent-event-' . absint( $event_id ) . '@' . $host;
	}

	/**
	 * Get a download filename for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private static function get_event_ics_filename( $event_id ) {
		$title = sanitize_title( get_the_title( $event_id ) );

		if ( '' === $title ) {
			$title = 'event-' . absint( $event_id );
		}

		return sanitize_file_name( $title . '.ics' );
	}

	/**
	 * Strip content down to plain text for calendar fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw content.
	 * @return string
	 */
	private static function plain_text( $content ) {
		return trim( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );
	}

	/**
	 * Resolve the best attendee-facing event description for calendar exports.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $event_id Event post ID.
	 * @param WP_Post $post     Event post.
	 * @return string
	 */
	private static function get_event_description( $event_id, $post ) {
		$sources = array(
			$post->post_excerpt,
			get_post_meta( $event_id, 'wpfa_event_lead_text', true ),
			$post->post_content,
		);

		foreach ( $sources as $source ) {
			$description = self::plain_text( $source );

			if ( '' !== $description ) {
				return $description;
			}
		}

		return '';
	}

	/**
	 * Format a display timezone label.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone_string Timezone string.
	 * @return string
	 */
	private static function format_timezone_label( $timezone_string ) {
		if ( class_exists( 'Wpfaevent_Schedule_Helper' ) ) {
			return Wpfaevent_Schedule_Helper::format_timezone_label( $timezone_string );
		}

		return $timezone_string;
	}

	/**
	 * Format an event date label.
	 *
	 * @since 1.0.0
	 *
	 * @param bool                   $all_day        Whether event is all-day.
	 * @param string                 $start_date     Start date.
	 * @param string                 $end_date       End date.
	 * @param DateTimeInterface|null $start_datetime Start datetime.
	 * @param DateTimeInterface|null $end_datetime   End datetime.
	 * @param DateTimeZone           $timezone       Event timezone.
	 * @return string
	 */
	private static function format_date_label( $all_day, $start_date, $end_date, $start_datetime, $end_datetime, $timezone ) {
		$date_format = get_option( 'date_format' );

		if ( ! $all_day && $start_datetime instanceof DateTimeInterface ) {
			$start_label = wp_date( $date_format, $start_datetime->getTimestamp(), $timezone );
			$end_label   = $end_datetime instanceof DateTimeInterface ? wp_date( $date_format, $end_datetime->getTimestamp(), $timezone ) : '';

			return $end_label && $end_label !== $start_label ? $start_label . ' - ' . $end_label : $start_label;
		}

		$start_label = self::format_date_string( $start_date, $timezone );
		$end_label   = $end_date && $end_date !== $start_date ? self::format_date_string( $end_date, $timezone ) : '';

		return $end_label ? $start_label . ' - ' . $end_label : $start_label;
	}

	/**
	 * Format an event time label.
	 *
	 * @since 1.0.0
	 *
	 * @param DateTimeInterface|null $start_datetime Start datetime.
	 * @param DateTimeInterface|null $end_datetime   End datetime.
	 * @param DateTimeZone           $timezone       Event timezone.
	 * @return string
	 */
	private static function format_time_label( $start_datetime, $end_datetime, $timezone ) {
		if ( ! $start_datetime instanceof DateTimeInterface ) {
			return '';
		}

		$time_format = get_option( 'time_format' );
		$start_label = wp_date( $time_format, $start_datetime->getTimestamp(), $timezone );
		$end_label   = $end_datetime instanceof DateTimeInterface ? wp_date( $time_format, $end_datetime->getTimestamp(), $timezone ) : '';

		return $end_label && $end_label !== $start_label ? $start_label . ' - ' . $end_label : $start_label;
	}

	/**
	 * Format a date-only value.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $date     Date in Y-m-d format.
	 * @param DateTimeZone $timezone Timezone.
	 * @return string
	 */
	private static function format_date_string( $date, $timezone ) {
		$date = self::sanitize_ics_date_value( $date );

		if ( '' === $date ) {
			return '';
		}

		try {
			$datetime = new DateTimeImmutable( $date . ' 00:00:00', $timezone );
		} catch ( Exception $exception ) {
			return $date;
		}

		return wp_date( get_option( 'date_format' ), $datetime->getTimestamp(), $timezone );
	}

	/**
	 * Remove newlines from token-like ICS values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Token value.
	 * @return string
	 */
	private static function sanitize_ical_token( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		return str_replace( array( "\r", "\n" ), '', $value );
	}
}
