<?php
/**
 * PHPStan Bootstrap File
 *
 * This file defines global constants and sets up a mock environment
 * specifically for PHPStan static analysis runs so that template paths
 * and dependencies resolve properly without loading core WordPress.
 *
 * @package Wpfaevent
 * @since   1.0.0
 */

// Prevents PHPStan from treating guarded code as unreachable/dead code.
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes/' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Dynamically extract the plugin version from the main file to prevent configuration drift.
if ( ! defined( 'WPFAEVENT_VERSION' ) ) {
	define(
		'WPFAEVENT_VERSION',
		( static function () {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$wpfa_raw_data = file_get_contents( __DIR__ . '/wpfaevent.php' );
			$wpfa_string   = is_string( $wpfa_raw_data ) ? $wpfa_raw_data : '';

			preg_match( '/Version:\s*(\d+\.\d+\.\d+)/i', $wpfa_string, $wpfa_matches );
			return $wpfa_matches[1] ?? '1.0.0';
		} )()
	);
}

if ( ! defined( 'WPFAEVENT_PATH' ) ) {
	define( 'WPFAEVENT_PATH', __DIR__ . '/' );
}

// Neutral placeholder URL — strictly for static analysis only, never used in runtime code.
// Uses the RFC 2606 reserved '.test' TLD which can never resolve to a real site.
if ( ! defined( 'WPFAEVENT_URL' ) ) {
	define( 'WPFAEVENT_URL', 'https://example.test/wp-content/plugins/wpfaevent/' );
}
