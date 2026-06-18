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

		register_post_meta(
			self::$post_type,
			'wpfa_event_languages',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_language_list' ),
				'description'       => __( 'Event languages', 'wpfaevent' ),
			)
		);

		foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) {
			register_post_meta(
				self::$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_color_value' ),
					'description'       => $label,
				)
			);
		}

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

		$speaker_ids = array_map( 'absint', $speaker_ids );
		$speaker_ids = array_filter( $speaker_ids );

		return array_values( array_unique( $speaker_ids ) );
	}

	/**
	 * Sync speaker-side event relationship meta after an event is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $event_id          Event post ID.
	 * @param array<int> $previous_speakers Speaker IDs before save.
	 * @param array<int> $current_speakers  Speaker IDs after save.
	 */
	public static function sync_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
		$event_id          = absint( $event_id );
		$previous_speakers = self::sanitize_post_id_list(
			array_merge(
				self::sanitize_post_id_list( $previous_speakers ),
				Wpfaevent_Meta_Speaker::get_speakers_linked_to_event( $event_id )
			)
		);
		$current_speakers  = self::sanitize_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		foreach ( array_diff( $previous_speakers, $current_speakers ) as $speaker_id ) {
			Wpfaevent_Meta_Speaker::remove_event_from_speaker( $speaker_id, $event_id, false );
		}

		foreach ( $current_speakers as $speaker_id ) {
			Wpfaevent_Meta_Speaker::add_event_to_speaker( $speaker_id, $event_id, false );
		}
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int>
	 */
	public static function sanitize_post_id_list( $post_ids ) {
		if ( is_array( $post_ids ) ) {
			$normalized_post_ids = $post_ids;
		} elseif ( is_scalar( $post_ids ) ) {
			if ( is_string( $post_ids ) ) {
				$post_ids = trim( $post_ids );
			}

			if ( '' === $post_ids ) {
				return array();
			}

			$decoded_post_ids = is_string( $post_ids ) ? json_decode( $post_ids, true ) : null;

			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded_post_ids ) ) {
				$normalized_post_ids = $decoded_post_ids;
			} elseif ( JSON_ERROR_NONE === json_last_error() && is_scalar( $decoded_post_ids ) ) {
				$normalized_post_ids = array( $decoded_post_ids );
			} elseif ( is_string( $post_ids ) && false !== strpos( $post_ids, ',' ) ) {
				$normalized_post_ids = array_map( 'trim', explode( ',', $post_ids ) );
			} else {
				$normalized_post_ids = array( $post_ids );
			}
		} else {
			return array();
		}

		$post_ids = array_map( 'absint', $normalized_post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
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

		return '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_start_time', true ) )
			&& '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_end_time', true ) )
			&& '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_time', true ) );
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
	 * Event color meta fields imported from Eventyay settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function get_event_color_meta_fields() {
		return array(
			'wpfa_event_primary_color'          => __( 'Primary color', 'wpfaevent' ),
			'wpfa_event_hover_button_color'     => __( 'Button hover color', 'wpfaevent' ),
			'wpfa_event_theme_background_color' => __( 'Theme background color', 'wpfaevent' ),
			'wpfa_event_theme_success_color'    => __( 'Theme success color', 'wpfaevent' ),
			'wpfa_event_theme_danger_color'     => __( 'Theme danger color', 'wpfaevent' ),
		);
	}

	/**
	 * Get all color meta values for a specific event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, string> Color meta values mapped by key.
	 */
	public static function get_event_colors( $event_id ) {
		$event_id = absint( $event_id );
		$colors   = array();

		if ( ! $event_id ) {
			return $colors;
		}

		foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) {
			$color               = get_post_meta( $event_id, $meta_key, true );
			$colors[ $meta_key ] = self::sanitize_color_value( $color );
		}

		return $colors;
	}

	/**
	 * Sanitize event language values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $languages Raw language list.
	 * @return array<string>
	 */
	public static function sanitize_language_list( $languages ) {
		if ( is_string( $languages ) ) {
			$decoded = json_decode( $languages, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				$languages = $decoded;
			} else {
				$languages = preg_split( '/[,|]/', $languages );
			}
		}

		if ( is_scalar( $languages ) ) {
			$languages = array( $languages );
		}

		if ( ! is_array( $languages ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $languages as $language ) {
			if ( is_array( $language ) ) {
				foreach ( array( 'name', 'label', 'title', 'code', 'locale', 'language' ) as $key ) {
					if ( ! empty( $language[ $key ] ) && is_scalar( $language[ $key ] ) ) {
						$language = $language[ $key ];
						break;
					}
				}
			}

			if ( ! is_scalar( $language ) ) {
				continue;
			}

			$language = sanitize_text_field( (string) $language );
			$language = trim( $language );

			if ( '' !== $language ) {
				$normalized[] = $language;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Sanitize an imported event color value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $color Raw color.
	 * @return string
	 */
	public static function sanitize_color_value( $color ) {
		if ( is_array( $color ) ) {
			foreach ( array( 'value', 'color', 'hex', 'default' ) as $key ) {
				if ( isset( $color[ $key ] ) ) {
					return self::sanitize_color_value( $color[ $key ] );
				}
			}

			return '';
		}

		if ( ! is_scalar( $color ) ) {
			return '';
		}

		$color = trim( sanitize_text_field( (string) $color ) );
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $color ) ) {
			return strtoupper( $color );
		}

		if ( preg_match( '/^[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $color ) ) {
			return '#' . strtoupper( $color );
		}

		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
			return $color;
		}

		return '';
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
