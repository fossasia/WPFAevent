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
		$string_meta_fields = array(
			'wpfa_speaker_position'      => array(
				'description'       => __( 'Speaker position/title', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_organization'  => array(
				'description'       => __( 'Speaker organization', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_bio'           => array(
				'description'       => __( 'Speaker biography', 'wpfaevent' ),
				'sanitize_callback' => 'wp_kses_post',
			),
			'wpfa_speaker_headshot_url'  => array(
				'description'       => __( 'Speaker headshot image URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_linkedin'      => array(
				'description'       => __( 'Speaker LinkedIn URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_twitter'       => array(
				'description'       => __( 'Speaker Twitter URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_github'        => array(
				'description'       => __( 'Speaker GitHub URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_website'       => array(
				'description'       => __( 'Speaker website URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_talk_title'    => array(
				'description'       => __( 'Speaker session title', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_date'     => array(
				'description'       => __( 'Speaker session date', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_time'     => array(
				'description'       => __( 'Speaker session start time', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_end_time' => array(
				'description'       => __( 'Speaker session end time', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_abstract' => array(
				'description'       => __( 'Speaker session abstract', 'wpfaevent' ),
				'sanitize_callback' => 'wp_kses_post',
			),
		);

		foreach ( $string_meta_fields as $meta_key => $args ) {
			self::register_string_meta( $meta_key, $args['description'], $args['sanitize_callback'] );
		}

		// Related events for the bidirectional event-speaker relationship.
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
	 * Registers a single speaker string meta field.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $meta_key          Meta key to register.
	 * @param string          $description       REST/API field description.
	 * @param callable|string $sanitize_callback Sanitization callback.
	 * @return void
	 */
	private static function register_string_meta( $meta_key, $description, $sanitize_callback ) {
		register_post_meta(
			self::$post_type,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitize_callback,
				'description'       => $description,
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

		$event_ids = array_map( 'absint', $event_ids );
		$event_ids = array_filter( $event_ids );

		return array_values( array_unique( $event_ids ) );
	}
}
