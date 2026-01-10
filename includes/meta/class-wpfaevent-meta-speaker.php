<?php
/**
 * Registers custom meta fields for Speaker CPT.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/meta
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Meta_Speaker.
 *
 * Registers meta fields for the Speaker CPT.
 */
class Wpfaevent_Meta_Speaker {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	private static $post_type = 'wpfa_speaker';

	/**
	 * Registers all speaker meta fields.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		// Speaker position/title
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_position',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Speaker position/title', 'wpfaevent' ),
			)
		);

		// Speaker organization
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_organization',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Speaker organization', 'wpfaevent' ),
			)
		);

		// Speaker bio
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_bio',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'Speaker biography', 'wpfaevent' ),
			)
		);

		// Speaker headshot URL
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_headshot_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Speaker headshot image URL', 'wpfaevent' ),
			)
		);

		// Related events (optional - for bidirectional relationship)
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_events',
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
				'sanitize_callback' => array( __CLASS__, 'sanitize_event_ids' ),
				'description'       => __( 'Related event post IDs', 'wpfaevent' ),
			)
		);
	}

	/**
	 * Sanitizes an array of event IDs.
	 *
	 * @since 1.0.0
	 * @param array $event_ids Array of event post IDs.
	 * @return array Sanitized array of integers.
	 */
	public static function sanitize_event_ids( $event_ids ) {
		if ( ! is_array( $event_ids ) ) {
			return array();
		}

		return array_map( 'absint', $event_ids );
	}
}
