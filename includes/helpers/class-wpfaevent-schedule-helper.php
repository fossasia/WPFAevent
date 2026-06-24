<?php
/**
 * Shared schedule and timezone helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule formatting and timezone conversion helpers.
 */
class Wpfaevent_Schedule_Helper {

	/**
	 * Resolve the selected schedule timezone from the request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $default_timezone_string Default timezone identifier.
	 * @return string
	 */
	public static function get_selected_timezone_string( $default_timezone_string ) {
		$default_timezone_string = trim( (string) $default_timezone_string );

		if ( '' === $default_timezone_string ) {
			$default_timezone_string = wp_timezone_string();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only timezone converter.
		if ( ! isset( $_GET['schedule_tz'] ) ) {
			return $default_timezone_string;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized before validation.
		$requested_timezone = sanitize_text_field( wp_unslash( $_GET['schedule_tz'] ) );

		if ( '' === $requested_timezone ) {
			return $default_timezone_string;
		}

		try {
			new DateTimeZone( $requested_timezone );

			return $requested_timezone;
		} catch ( Exception $exception ) {
			return $default_timezone_string;
		}
	}

	/**
	 * Build timezone options for a schedule converter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $primary_timezone_string Primary timezone to prioritize.
	 * @return array<int, string>
	 */
	public static function get_timezone_options( $primary_timezone_string = '' ) {
		$site_timezone_string = wp_timezone_string();

		if ( '' === trim( (string) $site_timezone_string ) ) {
			$site_timezone_string = wp_timezone()->getName();
		}

		$primary_timezone_string = trim( (string) $primary_timezone_string );
		if ( '' === $primary_timezone_string ) {
			$primary_timezone_string = $site_timezone_string;
		}

		return array_values(
			array_unique(
				array_merge(
					array(
						$primary_timezone_string,
						$site_timezone_string,
						'UTC',
					),
					DateTimeZone::listIdentifiers()
				)
			)
		);
	}

	/**
	 * Format a timezone label for select options.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone_string         Timezone identifier.
	 * @param string $primary_timezone_string Primary timezone identifier.
	 * @return string
	 */
	public static function format_timezone_label( $timezone_string, $primary_timezone_string = '' ) {
		unset( $primary_timezone_string );

		$label  = trim( (string) $timezone_string );
		$offset = self::format_timezone_offset( $timezone_string );

		return '' !== $offset ? sprintf( '%1$s (UTC%2$s)', $label, $offset ) : $label;
	}

	/**
	 * Format the current UTC offset for a timezone identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone_string Timezone identifier.
	 * @return string
	 */
	public static function format_timezone_offset( $timezone_string ) {
		try {
			$timezone = new DateTimeZone( $timezone_string );
			$datetime = new DateTimeImmutable( 'now', $timezone );
		} catch ( Exception $exception ) {
			return '';
		}

		$offset  = $timezone->getOffset( $datetime );
		$sign    = $offset < 0 ? '-' : '+';
		$offset  = absint( $offset );
		$hours   = (int) floor( $offset / HOUR_IN_SECONDS );
		$minutes = (int) floor( ( $offset % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

		return sprintf( '%s%02d:%02d', $sign, $hours, $minutes );
	}

	/**
	 * Build a DateTimeImmutable for an event start in its source timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return DateTimeImmutable|null
	 */
	public static function get_event_start_datetime( $event_id ) {
		$event_id   = absint( $event_id );
		$start_date = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
		$start_time = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_time', true ) );

		if ( '' === trim( $start_date ) ) {
			return null;
		}

		$timezone_string = class_exists( 'Wpfaevent_Calendar' )
			? Wpfaevent_Calendar::get_event_timezone_string( $event_id )
			: wp_timezone_string();
		$timezone_string = trim( (string) $timezone_string );

		if ( '' === $timezone_string ) {
			$timezone_string = wp_timezone_string();
		}

		try {
			$timezone = new DateTimeZone( $timezone_string );
		} catch ( Exception $exception ) {
			$timezone = wp_timezone();
		}

		$value = trim( $start_date . ( $start_time ? ' ' . $start_time : '' ) );

		try {
			return new DateTimeImmutable( $value, $timezone );
		} catch ( Exception $exception ) {
			try {
				return new DateTimeImmutable( $start_date, $timezone );
			} catch ( Exception $inner_exception ) {
				return null;
			}
		}
	}

	/**
	 * Format an event start date using WordPress settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id        Event post ID.
	 * @param DateTimeZone|null $display_timezone Target timezone.
	 * @return string
	 */
	public static function format_event_start_date( $event_id, $display_timezone = null ) {
		$datetime = self::get_event_start_datetime( $event_id );

		if ( ! $datetime ) {
			return '';
		}

		if ( ! $display_timezone instanceof DateTimeZone ) {
			$display_timezone = wp_timezone();
		}

		return wp_date( get_option( 'date_format' ), $datetime->getTimestamp(), $display_timezone );
	}

	/**
	 * Format an event start time using WordPress settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id        Event post ID.
	 * @param DateTimeZone|null $display_timezone Target timezone.
	 * @return string
	 */
	public static function format_event_start_time( $event_id, $display_timezone = null ) {
		$start_time = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_time', true ) );

		if ( '' === trim( $start_time ) ) {
			return '';
		}

		$datetime = self::get_event_start_datetime( $event_id );

		if ( ! $datetime ) {
			return '';
		}

		if ( ! $display_timezone instanceof DateTimeZone ) {
			$display_timezone = wp_timezone();
		}

		return wp_date( get_option( 'time_format' ), $datetime->getTimestamp(), $display_timezone );
	}

	/**
	 * Get the public schedule page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_schedule_page_url() {
		$page_id = absint( get_option( 'wpfaevent_schedule_page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			return apply_filters( 'wpfaevent_schedule_page_url', get_permalink( $page_id ) );
		}

		$page = get_page_by_path( 'full-schedule' );
		if ( $page instanceof WP_Post ) {
			$url = get_permalink( $page );

			return apply_filters( 'wpfaevent_schedule_page_url', $url );
		}

		return apply_filters( 'wpfaevent_schedule_page_url', home_url( '/full-schedule/' ) );
	}

	/**
	 * Ensure the public full schedule page exists.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $check_capability Whether to require plugin settings access before creating the page.
	 * @return int Page ID, or 0 when the page could not be ensured.
	 */
	public static function ensure_schedule_page( $check_capability = true ) {
		if ( $check_capability && ! Wpfaevent_Roles::current_user_can_manage_settings() ) {
			return 0;
		}

		$page_id = absint( get_option( 'wpfaevent_schedule_page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			self::ensure_schedule_page_template( $page_id );

			return $page_id;
		}

		$page = get_page_by_path( 'full-schedule' );
		if ( $page instanceof WP_Post ) {
			$page_id = absint( $page->ID );
			self::ensure_schedule_page_template( $page_id );
			update_option( 'wpfaevent_schedule_page_id', $page_id, false );

			return $page_id;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'     => __( 'Full Schedule', 'wpfaevent' ),
				'post_name'      => 'full-schedule',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_content'   => '',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return 0;
		}

		$page_id = absint( $page_id );
		update_post_meta( $page_id, '_wp_page_template', 'page-schedule.php' );
		update_post_meta( $page_id, '_wpfaevent_managed_page', 'schedule' );
		update_option( 'wpfaevent_schedule_page_id', $page_id, false );

		return $page_id;
	}

	/**
	 * Ensure the schedule page uses the plugin schedule template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	private static function ensure_schedule_page_template( $page_id ) {
		$page_id = absint( $page_id );

		if ( ! $page_id ) {
			return;
		}

		$template = get_page_template_slug( $page_id );
		if ( 'page-schedule.php' === $template ) {
			return;
		}

		update_post_meta( $page_id, '_wp_page_template', 'page-schedule.php' );
	}

	/**
	 * Build event-specific schedule session items from dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id         Event post ID.
	 * @param DateTimeZone|null $display_timezone Target timezone.
	 * @return array<string, mixed>
	 */
	public static function build_event_session_schedule( $event_id, $display_timezone = null ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array(
				'items'  => array(),
				'groups' => array(),
			);
		}

		if ( ! $display_timezone instanceof DateTimeZone ) {
			$display_timezone = wp_timezone();
		}

		$schedule_table = self::read_dashboard_json_file( 'schedule-' . $event_id . '.json', array() );
		$schedule_rows  = isset( $schedule_table['data'] ) && is_array( $schedule_table['data'] ) ? $schedule_table['data'] : array();
		$schedule_meta  = isset( $schedule_table['sessions'] ) && is_array( $schedule_table['sessions'] ) ? $schedule_table['sessions'] : array();
		$schedule_head  = ! empty( $schedule_rows[0] ) && is_array( $schedule_rows[0] ) ? $schedule_rows[0] : array();
		$schedule_body  = ! empty( $schedule_head ) ? array_slice( $schedule_rows, 1 ) : $schedule_rows;
		$event_timezone = self::get_event_timezone_object( $event_id );
		$items          = array();

		foreach ( $schedule_body as $row_index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_meta       = isset( $schedule_meta[ $row_index ] ) && is_array( $schedule_meta[ $row_index ] ) ? $schedule_meta[ $row_index ] : array();
			$start_datetime = isset( $row_meta['starts_at'] ) ? sanitize_text_field( $row_meta['starts_at'] ) : '';
			$end_datetime   = isset( $row_meta['ends_at'] ) ? sanitize_text_field( $row_meta['ends_at'] ) : '';
			$row_date       = isset( $row[0] ) ? sanitize_text_field( $row[0] ) : '';
			$row_time       = isset( $row[1] ) ? sanitize_text_field( $row[1] ) : '';

			$item = array(
				'date'           => $row_date,
				'date_label'     => self::format_schedule_session_date( $start_datetime, $row_date, $row_time, $display_timezone, $event_timezone ),
				'time'           => $row_time,
				'time_label'     => self::format_schedule_session_time( $start_datetime, $end_datetime, $row_date, $row_time, $display_timezone, $event_timezone ),
				'title'          => ! empty( $row[2] ) ? sanitize_text_field( $row[2] ) : get_the_title( $event_id ),
				'speakers'       => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
				'track'          => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
				'room'           => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
			);

			$item['calendar_url'] = self::build_schedule_session_calendar_url( $event_id, $item, $event_timezone );

			$time_parts         = preg_split( '/\s*-\s*/', $item['time_label'], 2 );
			$item['time_start'] = isset( $time_parts[0] ) ? trim( $time_parts[0] ) : $item['time_label'];
			$item['time_end']   = isset( $time_parts[1] ) ? trim( $time_parts[1] ) : '';

			$items[] = $item;
		}

		$groups = array();

		foreach ( $items as $item ) {
			$day_key = ! empty( $item['date_label'] ) ? $item['date_label'] : __( 'TBD', 'wpfaevent' );

			if ( ! isset( $groups[ $day_key ] ) ) {
				$groups[ $day_key ] = array();
			}

			$groups[ $day_key ][] = $item;
		}

		return array(
			'items'  => $items,
			'groups' => $groups,
		);
	}

	/**
	 * Build a sortable schedule entry for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id        Event post ID.
	 * @param DateTimeZone|null $display_timezone Target timezone.
	 * @return array<string, mixed>|null
	 */
	public static function build_event_schedule_entry( $event_id, $display_timezone = null ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return null;
		}

		if ( ! $display_timezone instanceof DateTimeZone ) {
			$display_timezone = wp_timezone();
		}

		$calendar_data = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_calendar_data( $event_id ) : array();
		$calendar_data = is_wp_error( $calendar_data ) ? array() : $calendar_data;
		$calendar_url  = ! empty( $calendar_data ) && class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::build_google_calendar_url( $calendar_data ) : '';
		$all_day       = ! empty( $calendar_data['all_day'] );
		$datetime      = self::get_event_start_datetime( $event_id );

		if ( $datetime ) {
			$display_datetime = $datetime->setTimezone( $display_timezone );
			$date_label       = $all_day && ! empty( $calendar_data['date_label'] )
				? sanitize_text_field( $calendar_data['date_label'] )
				: wp_date( get_option( 'date_format' ), $display_datetime->getTimestamp(), $display_timezone );
			$group_key        = $all_day && ! empty( $calendar_data['start_date'] )
				? sanitize_text_field( $calendar_data['start_date'] )
				: $display_datetime->format( 'Y-m-d' );
			$time_label       = $all_day ? __( 'All day', 'wpfaevent' ) : '';

			if ( ! $all_day ) {
				$time_label = self::format_event_time_label( $calendar_data, $event_id, $display_timezone );
			}

			return array(
				'id'           => $event_id,
				'sort_key'     => $display_datetime->getTimestamp(),
				'date_label'   => $date_label,
				'time_label'   => $time_label,
				'group_key'    => $group_key,
				'location'     => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) ),
				'title'        => get_the_title( $event_id ),
				'permalink'    => get_permalink( $event_id ),
				'schedule_url' => add_query_arg( 'event', get_post_field( 'post_name', $event_id ), self::get_schedule_page_url() ),
				'calendar_url' => $calendar_url,
			);
		}

		return array(
			'id'           => $event_id,
			'sort_key'     => PHP_INT_MAX,
			'date_label'   => __( 'TBD', 'wpfaevent' ),
			'time_label'   => '',
			'group_key'    => 'tbd',
			'location'     => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) ),
			'title'        => get_the_title( $event_id ),
			'permalink'    => get_permalink( $event_id ),
			'schedule_url' => add_query_arg( 'event', get_post_field( 'post_name', $event_id ), self::get_schedule_page_url() ),
			'calendar_url' => $calendar_url,
		);
	}

	/**
	 * Read plugin-managed dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private static function read_dashboard_json_file( $filename, $fallback ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return $fallback;
		}

		$path = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data/' . sanitize_file_name( $filename );

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return $fallback;
		}

		if ( function_exists( 'wp_json_file_decode' ) ) {
			$decoded = wp_json_file_decode( $path, array( 'associative' => true ) );

			return is_array( $decoded ) ? $decoded : $fallback;
		}

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return $fallback;
		}

		$contents = $wp_filesystem->get_contents( $path );
		if ( false === $contents || '' === trim( $contents ) ) {
			return $fallback;
		}

		$decoded = json_decode( $contents, true );

		return ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : $fallback;
	}

	/**
	 * Get an event timezone object.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return DateTimeZone
	 */
	private static function get_event_timezone_object( $event_id ) {
		$timezone_string = class_exists( 'Wpfaevent_Calendar' )
			? Wpfaevent_Calendar::get_event_timezone_string( $event_id )
			: wp_timezone_string();

		try {
			return new DateTimeZone( $timezone_string );
		} catch ( Exception $exception ) {
			return wp_timezone();
		}
	}

	/**
	 * Parse an imported schedule date-time value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime Date-time value.
	 * @return DateTimeImmutable|null
	 */
	private static function parse_schedule_datetime( $datetime ) {
		$datetime = trim( (string) $datetime );

		if ( '' === $datetime ) {
			return null;
		}

		try {
			return new DateTimeImmutable( $datetime );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Split a human-entered time range.
	 *
	 * @since 1.0.0
	 *
	 * @param string $time Time or time range.
	 * @return array<int, string>
	 */
	private static function split_schedule_time_range( $time ) {
		$parts = preg_split( '/\s*-\s*/', trim( (string) $time ), 2 );

		return array_values( array_filter( array_map( 'trim', is_array( $parts ) ? $parts : array() ) ) );
	}

	/**
	 * Build a schedule fallback date-time from separate date and time values.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $date            Date value.
	 * @param string       $time            Time value.
	 * @param DateTimeZone $source_timezone Source timezone.
	 * @return DateTimeImmutable|null
	 */
	private static function build_schedule_fallback_datetime( $date, $time, $source_timezone ) {
		$date = class_exists( 'Wpfaevent_Meta_Event' )
			? Wpfaevent_Meta_Event::sanitize_date_value( $date )
			: sanitize_text_field( $date );

		$time_parts = self::split_schedule_time_range( $time );
		$time       = isset( $time_parts[0] ) ? $time_parts[0] : '';

		if ( '' === $date || '' === $time ) {
			return null;
		}

		try {
			return new DateTimeImmutable( trim( $date . ' ' . $time ), $source_timezone );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Format an imported session date.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $start_datetime  Imported start date-time.
	 * @param string       $fallback_date   Fallback date.
	 * @param string       $fallback_time   Fallback time.
	 * @param DateTimeZone $display_timezone Display timezone.
	 * @param DateTimeZone $event_timezone   Event timezone.
	 * @return string
	 */
	private static function format_schedule_session_date( $start_datetime, $fallback_date, $fallback_time, $display_timezone, $event_timezone ) {
		$start = self::parse_schedule_datetime( $start_datetime );

		if ( $start ) {
			return wp_date( get_option( 'date_format' ), $start->getTimestamp(), $display_timezone );
		}

		$fallback = self::build_schedule_fallback_datetime( $fallback_date, $fallback_time, $event_timezone );

		return $fallback ? wp_date( get_option( 'date_format' ), $fallback->getTimestamp(), $display_timezone ) : sanitize_text_field( $fallback_date );
	}

	/**
	 * Format an imported session time.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $start_datetime  Imported start date-time.
	 * @param string       $end_datetime    Imported end date-time.
	 * @param string       $fallback_date   Fallback date.
	 * @param string       $fallback_time   Fallback time.
	 * @param DateTimeZone $display_timezone Display timezone.
	 * @param DateTimeZone $event_timezone   Event timezone.
	 * @return string
	 */
	private static function format_schedule_session_time( $start_datetime, $end_datetime, $fallback_date, $fallback_time, $display_timezone, $event_timezone ) {
		$start       = self::parse_schedule_datetime( $start_datetime );
		$end         = self::parse_schedule_datetime( $end_datetime );
		$start_label = $start ? wp_date( get_option( 'time_format' ), $start->getTimestamp(), $display_timezone ) : '';
		$end_label   = $end ? wp_date( get_option( 'time_format' ), $end->getTimestamp(), $display_timezone ) : '';

		if ( ! $start_label ) {
			$time_parts     = self::split_schedule_time_range( $fallback_time );
			$fallback_start = self::build_schedule_fallback_datetime( $fallback_date, isset( $time_parts[0] ) ? $time_parts[0] : '', $event_timezone );
			$fallback_end   = self::build_schedule_fallback_datetime( $fallback_date, isset( $time_parts[1] ) ? $time_parts[1] : '', $event_timezone );
			$start_label    = $fallback_start ? wp_date( get_option( 'time_format' ), $fallback_start->getTimestamp(), $display_timezone ) : '';
			$end_label      = $fallback_end ? wp_date( get_option( 'time_format' ), $fallback_end->getTimestamp(), $display_timezone ) : '';
		}

		if ( $start_label && $end_label && $start_label !== $end_label ) {
			return $start_label . ' - ' . $end_label;
		}

		return $start_label ? $start_label : sanitize_text_field( $fallback_time );
	}

	/**
	 * Build a Google Calendar URL for one imported session.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $event_id       Event post ID.
	 * @param array        $item           Schedule item.
	 * @param DateTimeZone $event_timezone Event timezone.
	 * @return string
	 */
	private static function build_schedule_session_calendar_url( $event_id, $item, $event_timezone ) {
		if ( ! class_exists( 'Wpfaevent_Calendar' ) ) {
			return '';
		}

		$time_parts            = self::split_schedule_time_range( isset( $item['time'] ) ? $item['time'] : '' );
		$start                 = self::parse_schedule_datetime( isset( $item['start_datetime'] ) ? $item['start_datetime'] : '' );
		$end                   = self::parse_schedule_datetime( isset( $item['end_datetime'] ) ? $item['end_datetime'] : '' );
		$event_title           = get_the_title( $event_id );
		$event_url             = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_url', true ) );
		$location              = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
		$event_timezone_string = Wpfaevent_Calendar::get_event_timezone_string( $event_id );
		$all_day               = false;
		$start_date            = '';
		$end_date              = '';

		if ( ! $start ) {
			$start = self::build_schedule_fallback_datetime(
				isset( $item['date'] ) ? $item['date'] : '',
				isset( $time_parts[0] ) ? $time_parts[0] : '',
				$event_timezone
			);
		}

		if ( $start && ! $end && ! empty( $time_parts[1] ) ) {
			$end = self::build_schedule_fallback_datetime( isset( $item['date'] ) ? $item['date'] : '', $time_parts[1], $event_timezone );
		}

		if ( $start && $end && $end <= $start ) {
			$end = $end->modify( '+1 day' );
		}

		if ( ! $start && empty( $time_parts[0] ) ) {
			$start_date = class_exists( 'Wpfaevent_Meta_Event' )
				? Wpfaevent_Meta_Event::sanitize_date_value( isset( $item['date'] ) ? $item['date'] : '' )
				: '';
			$end_date   = $start_date;
			$all_day    = '' !== $start_date;
		}

		if ( ! $start && ! $all_day ) {
			return '';
		}

		$details = array_filter(
			array(
				$event_title ? sprintf(
					/* translators: %s: event title. */
					__( 'Event: %s', 'wpfaevent' ),
					$event_title
				) : '',
				! empty( $item['speakers'] ) ? sprintf(
					/* translators: %s: speaker names. */
					__( 'Speakers: %s', 'wpfaevent' ),
					$item['speakers']
				) : '',
				! empty( $item['track'] ) ? sprintf(
					/* translators: %s: session track. */
					__( 'Track: %s', 'wpfaevent' ),
					$item['track']
				) : '',
				! empty( $item['room'] ) ? sprintf(
					/* translators: %s: session room. */
					__( 'Room: %s', 'wpfaevent' ),
					$item['room']
				) : '',
			)
		);

		return Wpfaevent_Calendar::build_google_calendar_url(
			array(
				'title'           => ! empty( $item['title'] ) ? $item['title'] : $event_title,
				'description'     => implode( "\n", $details ),
				'location'        => ! empty( $item['room'] ) ? $item['room'] : $location,
				'url'             => $event_url,
				'timezone_string' => $event_timezone_string,
				'all_day'         => $all_day,
				'start_date'      => $start_date,
				'end_date'        => $end_date,
				'start_datetime'  => $all_day ? null : $start,
				'end_datetime'    => $all_day ? null : $end,
			)
		);
	}

	/**
	 * Format a timed event's start and end time for schedule display.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $calendar_data     Normalized calendar data.
	 * @param int                  $event_id          Event post ID.
	 * @param DateTimeZone         $display_timezone  Target timezone.
	 * @return string
	 */
	private static function format_event_time_label( $calendar_data, $event_id, $display_timezone ) {
		if ( ! empty( $calendar_data['start_datetime'] ) && $calendar_data['start_datetime'] instanceof DateTimeInterface ) {
			$time_format = get_option( 'time_format' );
			$start_label = wp_date( $time_format, $calendar_data['start_datetime']->getTimestamp(), $display_timezone );
			$end_label   = ! empty( $calendar_data['end_datetime'] ) && $calendar_data['end_datetime'] instanceof DateTimeInterface
				? wp_date( $time_format, $calendar_data['end_datetime']->getTimestamp(), $display_timezone )
				: '';

			return $end_label && $end_label !== $start_label ? $start_label . ' - ' . $end_label : $start_label;
		}

		return self::format_event_start_time( $event_id, $display_timezone );
	}
}
