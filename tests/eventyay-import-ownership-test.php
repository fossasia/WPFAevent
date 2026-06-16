<?php
// phpcs:ignoreFile -- Standalone CLI test defines minimal WordPress stubs and executable assertions.
/**
 * Eventyay import ownership and merge checks.
 *
 * Run with: php tests/eventyay-import-ownership-test.php
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPFAEVENT_PATH' ) ) {
	define( 'WPFAEVENT_PATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Minimal translation fallback for standalone CLI tests.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Minimal absint() fallback for standalone CLI tests.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Minimal sanitize_text_field() fallback for standalone CLI tests.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return trim( (string) $value );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Minimal sanitize_key() fallback for standalone CLI tests.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_key( $value ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) );
	}
}

require_once WPFAEVENT_PATH . 'admin/class-wpfaevent-eventyay-importer.php';

/**
 * Assert two values are identical.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Failure message.
 * @return void
 */
function wpfaevent_ownership_test_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, 'Assertion failed: ' . $message . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.PHP.DevelopmentFunctions.error_log_var_export
		fwrite( STDERR, 'Actual: ' . var_export( $actual, true ) . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.PHP.DevelopmentFunctions.error_log_var_export
		exit( 1 );
	}
}

/**
 * Call a private importer method through reflection.
 *
 * @param Wpfaevent_Eventyay_Importer $importer Importer instance.
 * @param string                      $method   Method name.
 * @param array                       $args     Method arguments.
 * @return mixed
 */
function wpfaevent_ownership_test_invoke( Wpfaevent_Eventyay_Importer $importer, $method, array $args ) {
	$reflection = new ReflectionClass( $importer );
	$callable   = $reflection->getMethod( $method );

	return $callable->invokeArgs( $importer, $args );
}

$importer = new Wpfaevent_Eventyay_Importer();

$manual_group = array(
	'group_name' => 'Community Partners',
	'sponsors'   => array(
		array(
			'id'   => 'manual-partner-1',
			'name' => 'Manual Partner',
		),
	),
);
$existing_eventyay_group = array(
	'group_name'         => 'Gold Sponsors',
	'source'             => 'eventyay',
	'eventyay_group_key' => 'gold-sponsors',
	'sponsors'           => array(
		array(
			'id'     => 'eventyay-sponsor-old',
			'name'   => 'Old Eventyay Sponsor',
			'source' => 'eventyay',
		),
	),
);
$imported_sponsors = array(
	array(
		'id'     => 'eventyay-sponsor-new',
		'name'   => 'New Eventyay Sponsor',
		'source' => 'eventyay',
		'type'   => 'Gold Sponsors',
	),
);

$merged_groups = wpfaevent_ownership_test_invoke(
	$importer,
	'merge_eventyay_sponsor_groups',
	array( $imported_sponsors, array( $manual_group, $existing_eventyay_group ) )
);

wpfaevent_ownership_test_assert_same( 2, count( $merged_groups ), 'Reimport should keep manual groups and replace Eventyay-owned groups.' );
wpfaevent_ownership_test_assert_same( 'Community Partners', $merged_groups[0]['group_name'], 'Manual sponsor groups should survive reimport.' );
wpfaevent_ownership_test_assert_same( 'eventyay', $merged_groups[1]['source'], 'Imported sponsor groups should remain Eventyay-owned.' );
wpfaevent_ownership_test_assert_same( 'New Eventyay Sponsor', $merged_groups[1]['sponsors'][0]['name'], 'Eventyay sponsors should be replaced on reimport.' );

$manual_exhibitor = array(
	'id'   => 'manual-exhibitor-1',
	'name' => 'Manual Booth',
);
$existing_eventyay_exhibitor = array(
	'id'     => 'eventyay-exhibitor-old',
	'name'   => 'Old Eventyay Booth',
	'source' => 'eventyay',
);
$imported_exhibitors = array(
	array(
		'id'     => 'eventyay-exhibitor-new',
		'name'   => 'New Eventyay Booth',
		'source' => 'eventyay',
	),
);

$merged_exhibitors = wpfaevent_ownership_test_invoke(
	$importer,
	'merge_eventyay_flat_records',
	array( $imported_exhibitors, array( $manual_exhibitor, $existing_eventyay_exhibitor ) )
);

wpfaevent_ownership_test_assert_same( 2, count( $merged_exhibitors ), 'Reimport should keep manual exhibitors and replace Eventyay-owned exhibitors.' );
wpfaevent_ownership_test_assert_same( 'Manual Booth', $merged_exhibitors[0]['name'], 'Manual exhibitors should survive reimport.' );
wpfaevent_ownership_test_assert_same( 'New Eventyay Booth', $merged_exhibitors[1]['name'], 'Eventyay exhibitors should be replaced on reimport.' );

fwrite( STDOUT, 'Eventyay import ownership tests passed.' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
