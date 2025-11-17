<?php
/**
 * Registers custom meta fields for the plugin.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WPFA_Event_Meta.
 *
 * Registers meta fields for CPTs.
 */
class WPFA_Event_Meta {

	/**
	 * Registers all meta fields.
	 */
	public static function register_meta_fields() {
		self::register_speaker_meta();
		self::register_event_meta();
	}

	/**
	 * Registers meta fields for the Speaker CPT.
	 */
	private static function register_speaker_meta() {
		$speaker_post_type = 'wpfaevent_speaker';

		register_post_meta(
			$speaker_post_type,
			'position',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			$speaker_post_type,
			'organization',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			$speaker_post_type,
			'bio',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		register_post_meta(
			$speaker_post_type,
			'headshot_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
	}

	/**
	 * Registers meta fields for the Event CPT.
	 */
	private static function register_event_meta() {
		$event_post_type = 'wpfaevent_event';

		$meta_fields = array(
			'talk_title'         => 'sanitize_text_field',
			'schedule_reference' => 'sanitize_text_field',
		);

		foreach ( $meta_fields as $meta_key => $sanitizer ) {
			register_post_meta(
				$event_post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitizer,
				)
			);
		}
	}
}