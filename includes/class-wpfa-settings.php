<?php
/**
 * Handles plugin settings registration.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Settings.
 */
class WPFA_Settings {

	/**
	 * Register settings, sections, and fields.
	 */
	public static function register_settings() {
		register_setting(
			'wpfa_settings_group', // Option group
			'wpfa_settings',       // Option name
			[ 'sanitize_callback' => [ self::class, 'sanitize' ] ] // Sanitize callback
		);

		add_settings_section(
			'wpfa_general_settings_section', // ID
			'General Settings',              // Title
			null,                            // Callback
			'wpfa_settings'                  // Page
		);

		// Add fields here in the future.
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$new_input = [];
		// Example for a future field:
		// if ( isset( $input['some_field'] ) ) {
		//  $new_input['some_field'] = sanitize_text_field( $input['some_field'] );
		// }
		return $input; // Return sanitized input.
	}
}