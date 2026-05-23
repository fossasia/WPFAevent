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

if ( ! defined( 'WPFAEVENT_VERSION' ) ) {
	define( 'WPFAEVENT_VERSION', '1.0.0' );
}

if ( ! defined( 'WPFAEVENT_PATH' ) ) {
	define( 'WPFAEVENT_PATH', __DIR__ . '/' );
}

if ( ! defined( 'WPFAEVENT_URL' ) ) {
	define( 'WPFAEVENT_URL', 'https://example.com/wp-content/plugins/wpfaevent/' );
}
