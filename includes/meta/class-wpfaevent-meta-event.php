<?php
/**
 * Registers custom meta fields for Event CPT.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/meta
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Meta_Event.
 *
 * Registers meta fields for the Event CPT.
 */
class Wpfaevent_Meta_Event {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	private static $post_type = 'wpfa_event';

	/**
	 * Registers all event meta fields.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		// Event date fields.
		register_post_meta(
			self::$post_type,
			'wpfa_event_start_date',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date_value' ),
				'description'       => __( 'Event start date', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_end_date',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date_value' ),
				'description'       => __( 'Event end date', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_start_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event start time', 'wpfaevent' ),
			)
		);

		// Legacy single event time used by the front-end event modal.
		register_post_meta(
			self::$post_type,
			'wpfa_event_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_end_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event end time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_timezone',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_timezone' ),
				'description'       => __( 'Event timezone', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_all_day',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_boolean_value' ),
				'description'       => __( 'Whether the event is an all-day event', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_starts_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Normalized event start date-time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_ends_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Normalized event end date-time', 'wpfaevent' ),
			)
		);

		// Event location.
		register_post_meta(
			self::$post_type,
			'wpfa_event_location',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event venue/location', 'wpfaevent' ),
			)
		);

		// Event external URL.
		register_post_meta(
			self::$post_type,
			'wpfa_event_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'External event link (Eventyay, etc.)', 'wpfaevent' ),
			)
		);

		// Event hero section lead text.
		register_post_meta(
			self::$post_type,
			'wpfa_event_lead_text',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event hero lead text', 'wpfaevent' ),
			)
		);

		// Event registration link.
		register_post_meta(
			self::$post_type,
			'wpfa_event_registration_link',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Event registration link', 'wpfaevent' ),
			)
		);

		// Call for speakers link.
		register_post_meta(
			self::$post_type,
			'wpfa_event_cfs_link',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Call for speakers link', 'wpfaevent' ),
			)
		);

		// Event speakers as an array of speaker IDs.
		register_post_meta(
			self::$post_type,
			'wpfa_event_speakers',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_speaker_ids' ),
				'description'       => __( 'Related speaker post IDs', 'wpfaevent' ),
			)
		);
	}

	/**
	 * Sanitizes an array of speaker IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker_ids Array of speaker post IDs.
	 * @return array Sanitized array of integers.
	 */
	public static function sanitize_speaker_ids( $speaker_ids ) {
		if ( ! is_array( $speaker_ids ) ) {
			return array();
		}

		return array_map( 'absint', $speaker_ids );
	}

	/**
	 * Sanitize an event date value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $date Raw date.
	 * @return string
	 */
	public static function sanitize_date_value( $date ) {
		if ( ! is_scalar( $date ) ) {
			return '';
		}

		$date = trim( sanitize_text_field( (string) $date ) );

		if ( '' === $date ) {
			return '';
		}

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) ) {
			return '';
		}

		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return '';
		}

		return $date;
	}

	/**
	 * Sanitize an event time value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $time Raw time.
	 * @return string
	 */
	public static function sanitize_time_value( $time ) {
		if ( ! is_scalar( $time ) ) {
			return '';
		}

		$time = trim( sanitize_text_field( (string) $time ) );

		if ( '' === $time ) {
			return '';
		}

		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $time ) ) {
			return '';
		}

		return substr( $time, 0, 5 );
	}

	/**
	 * Sanitize a timezone identifier or UTC offset.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $timezone Raw timezone.
	 * @return string
	 */
	public static function sanitize_timezone( $timezone ) {
		if ( ! is_scalar( $timezone ) ) {
			return '';
		}

		$timezone = trim( sanitize_text_field( (string) $timezone ) );

		if ( '' === $timezone ) {
			return '';
		}

		$timezone = self::normalize_utc_offset_timezone( $timezone );

		try {
			new DateTimeZone( $timezone );
			return $timezone;
		} catch ( Exception $exception ) {
			return '';
		}
	}

	/**
	 * Sanitize a boolean-like meta value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_boolean_value( $value ) {
		return rest_sanitize_boolean( $value );
	}

	/**
	 * Get an event timezone, falling back to the WordPress site timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_timezone( $event_id ) {
		$timezone = self::sanitize_timezone( get_post_meta( $event_id, 'wpfa_event_timezone', true ) );

		if ( '' !== $timezone ) {
			return $timezone;
		}

		$site_timezone = self::sanitize_timezone( wp_timezone_string() );

		if ( '' !== $site_timezone ) {
			return $site_timezone;
		}

		return wp_timezone()->getName();
	}

	/**
	 * Determine whether an event should be treated as all-day.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function get_event_all_day( $event_id ) {
		$value = get_post_meta( $event_id, 'wpfa_event_all_day', true );

		if ( '' !== $value ) {
			return rest_sanitize_boolean( $value );
		}

		return false;
	}

	/**
	 * Build an ISO 8601 datetime for timed manual events.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date     Date in Y-m-d format.
	 * @param string $time     Time in H:i format.
	 * @param string $timezone Timezone identifier.
	 * @return string
	 */
	public static function build_datetime_value( $date, $time, $timezone ) {
		$date     = self::sanitize_date_value( $date );
		$time     = self::sanitize_time_value( $time );
		$timezone = self::sanitize_timezone( $timezone );

		if ( '' === $date || '' === $time ) {
			return '';
		}

		if ( '' === $timezone ) {
			$timezone = self::get_event_timezone( 0 );
		}

		try {
			$datetime = new DateTimeImmutable( $date . ' ' . $time, new DateTimeZone( $timezone ) );
		} catch ( Exception $exception ) {
			return '';
		}

		return $datetime->format( DATE_ATOM );
	}

	/**
	 * Normalize old WordPress UTC offset labels into DateTimeZone-compatible offsets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone Timezone string.
	 * @return string
	 */
	private static function normalize_utc_offset_timezone( $timezone ) {
		if ( ! preg_match( '/^UTC([+-])(\d{1,2})(?:\.(5|50))?$/', $timezone, $matches ) ) {
			return $timezone;
		}

		$hours   = absint( $matches[2] );
		$minutes = empty( $matches[3] ) ? 0 : 30;

		if ( $hours > 14 ) {
			return $timezone;
		}

		return sprintf( '%s%02d:%02d', $matches[1], $hours, $minutes );
	}
}
