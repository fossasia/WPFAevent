<?php
/**
 * PHPUnit bootstrap file for wpfaevent plugin.
 *
 * @package Wpfaevent
 */

// 1. Load Composer autoloader for polyfills and dependencies
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

// 2. Determine the WP test framework directory dynamically
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$wp_develop_dir = getenv( 'WP_DEVELOP_DIR' );
	if ( $wp_develop_dir && file_exists( $wp_develop_dir . '/tests/phpunit' ) ) {
		$_tests_dir = rtrim( $wp_develop_dir, '/\\' ) . '/tests/phpunit';
	} else {
		// Fallback to cross-platform temp directory.
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}
}

// 3. Verify files exist before loading
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	printf( 'WP test suite not found at %s' . PHP_EOL, $_tests_dir );
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

// 4. Load the plugin via an anonymous function closure
tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/wpfaevent.php';
	}
);

// 5. Fire up the engine
require $_tests_dir . '/includes/bootstrap.php';
