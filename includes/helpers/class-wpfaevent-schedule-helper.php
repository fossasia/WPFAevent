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
		$label = str_replace( '_', ' ', $timezone_string );

		if ( $timezone_string === $primary_timezone_string ) {
			return sprintf(
				/* translators: %s: primary timezone. */
				__( 'Event timezone (%s)', 'wpfaevent' ),
				$label
			);
		}

		$site_timezone_string = wp_timezone_string();
		if ( '' === trim( (string) $site_timezone_string ) ) {
			$site_timezone_string = wp_timezone()->getName();
		}

		if ( $timezone_string === $site_timezone_string ) {
			return sprintf(
				/* translators: %s: WordPress site timezone. */
				__( 'Site timezone (%s)', 'wpfaevent' ),
				$label
			);
		}

		return $label;
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

		$datetime = self::get_event_start_datetime( $event_id );

		if ( $datetime ) {
			$display_datetime = $datetime->setTimezone( $display_timezone );

			return array(
				'id'           => $event_id,
				'sort_key'     => $display_datetime->getTimestamp(),
				'date_label'   => wp_date( get_option( 'date_format' ), $display_datetime->getTimestamp(), $display_timezone ),
				'time_label'   => self::format_event_start_time( $event_id, $display_timezone ),
				'group_key'    => $display_datetime->format( 'Y-m-d' ),
				'location'     => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) ),
				'title'        => get_the_title( $event_id ),
				'permalink'    => get_permalink( $event_id ),
				'calendar_url' => class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_google_calendar_url( $event_id ) : '',
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
			'calendar_url' => class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_google_calendar_url( $event_id ) : '',
		);
	}
}
