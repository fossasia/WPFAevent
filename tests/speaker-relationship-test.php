<?php
// phpcs:ignoreFile -- Standalone CLI test defines minimal WordPress stubs and executable assertions.
/**
 * Lightweight speaker/event relationship checks.
 *
 * Run with: php tests/speaker-relationship-test.php
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$GLOBALS['wpfa_relationship_test_meta']       = array();
$GLOBALS['wpfa_relationship_test_post_types'] = array();
$GLOBALS['wpfa_relationship_test_statuses']   = array();
$GLOBALS['wpfa_relationship_test_can_edit']   = false;

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

if ( ! function_exists( 'get_post_type' ) ) {
	/**
	 * Read a stubbed post type.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function get_post_type( $post_id ) {
		return isset( $GLOBALS['wpfa_relationship_test_post_types'][ $post_id ] ) ? $GLOBALS['wpfa_relationship_test_post_types'][ $post_id ] : '';
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Read stubbed post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed
	 */
	function get_post_meta( $post_id, $key, $single = false ) {
		unset( $single );

		return isset( $GLOBALS['wpfa_relationship_test_meta'][ $post_id ][ $key ] ) ? $GLOBALS['wpfa_relationship_test_meta'][ $post_id ][ $key ] : '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * Write stubbed post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['wpfa_relationship_test_meta'][ $post_id ][ $key ] = $value;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	/**
	 * Delete stubbed post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return void
	 */
	function delete_post_meta( $post_id, $key ) {
		unset( $GLOBALS['wpfa_relationship_test_meta'][ $post_id ][ $key ] );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Return the configured capability result.
	 *
	 * @return bool
	 */
	function current_user_can() {
		return (bool) $GLOBALS['wpfa_relationship_test_can_edit'];
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	/**
	 * Query the in-memory post/meta fixtures used by this test.
	 *
	 * @param array $args Query args.
	 * @return array<int>
	 */
	function get_posts( $args ) {
		$post_type   = isset( $args['post_type'] ) ? $args['post_type'] : '';
		$post_status = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';
		$ids         = isset( $args['post__in'] ) ? wpfa_relationship_test_normalize_ids( $args['post__in'] ) : array_keys( $GLOBALS['wpfa_relationship_test_post_types'] );

		$results = array();

		foreach ( $ids as $post_id ) {
			if ( $post_type && get_post_type( $post_id ) !== $post_type ) {
				continue;
			}

			if ( ! wpfa_relationship_test_status_matches( $post_id, $post_status ) ) {
				continue;
			}

			if ( ! empty( $args['meta_query'] ) && ! wpfa_relationship_test_meta_matches( $post_id, $args['meta_query'] ) ) {
				continue;
			}

			$results[] = $post_id;
		}

		return $results;
	}
}

/**
 * Normalize a fixture ID list.
 *
 * @param mixed $ids Raw IDs.
 * @return array<int>
 */
function wpfa_relationship_test_normalize_ids( $ids ) {
	if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
		return Wpfaevent_Meta_Event::sanitize_post_id_list( $ids );
	}

	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}

	$ids = array_map( 'absint', $ids );
	$ids = array_filter( $ids );

	return array_values( array_unique( $ids ) );
}

/**
 * Check a fixture post status against a query status.
 *
 * @param int          $post_id     Post ID.
 * @param string|array $post_status Query post status.
 * @return bool
 */
function wpfa_relationship_test_status_matches( $post_id, $post_status ) {
	if ( 'any' === $post_status ) {
		return true;
	}

	$status = isset( $GLOBALS['wpfa_relationship_test_statuses'][ $post_id ] ) ? $GLOBALS['wpfa_relationship_test_statuses'][ $post_id ] : 'publish';

	if ( is_array( $post_status ) ) {
		return in_array( $status, $post_status, true );
	}

	return $status === $post_status;
}

/**
 * Check whether a fixture post matches the relationship meta query.
 *
 * @param int   $post_id    Post ID.
 * @param array $meta_query Meta query.
 * @return bool
 */
function wpfa_relationship_test_meta_matches( $post_id, $meta_query ) {
	foreach ( $meta_query as $clause ) {
		if ( ! is_array( $clause ) || empty( $clause['key'] ) || ! array_key_exists( 'value', $clause ) ) {
			continue;
		}

		$needle = absint( preg_replace( '/\D+/', '', (string) $clause['value'] ) );
		$values = wpfa_relationship_test_normalize_ids( get_post_meta( $post_id, $clause['key'], true ) );

		if ( $needle && in_array( $needle, $values, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Reset in-memory fixtures.
 *
 * @return void
 */
function wpfa_relationship_test_reset() {
	$GLOBALS['wpfa_relationship_test_meta']       = array();
	$GLOBALS['wpfa_relationship_test_post_types'] = array(
		10  => 'wpfa_speaker',
		11  => 'wpfa_speaker',
		12  => 'wpfa_speaker',
		100 => 'wpfa_event',
		200 => 'wpfa_event',
		300 => 'wpfa_event',
	);
	$GLOBALS['wpfa_relationship_test_statuses']   = array(
		10  => 'publish',
		11  => 'publish',
		12  => 'publish',
		100 => 'publish',
		200 => 'publish',
		300 => 'draft',
	);
	$GLOBALS['wpfa_relationship_test_can_edit']   = false;
}

/**
 * Assert strict equality.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Failure message.
 * @return void
 */
function wpfa_relationship_test_assert_same( $expected, $actual, $message ) {
	if ( $expected === $actual ) {
		return;
	}

	fwrite( STDERR, $message . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	fwrite( STDERR, 'Actual: ' . var_export( $actual, true ) . PHP_EOL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export,WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI test output.
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/includes/meta/class-wpfaevent-meta-event.php';
require_once dirname( __DIR__ ) . '/includes/meta/class-wpfaevent-meta-speaker.php';

wpfa_relationship_test_reset();
$GLOBALS['wpfa_relationship_test_meta'][10]['wpfa_speaker_events'] = array( 100 );
$GLOBALS['wpfa_relationship_test_meta'][200]['wpfa_event_speakers'] = array( 10 );
$GLOBALS['wpfa_relationship_test_meta'][300]['wpfa_event_speakers'] = array( 10 );

wpfa_relationship_test_assert_same(
	array( 100, 200 ),
	Wpfaevent_Meta_Speaker::get_events_linked_to_speaker( 10, 'publish' ),
	'Speaker profiles should merge speaker-side links with published event-side references.'
);

wpfa_relationship_test_reset();
Wpfaevent_Meta_Speaker::add_event_to_speaker( 11, 200 );
wpfa_relationship_test_assert_same(
	'',
	get_post_meta( 11, 'wpfa_speaker_events', true ),
	'Public add helper should keep the speaker capability guard by default.'
);

Wpfaevent_Meta_Speaker::add_event_to_speaker( 11, 200, false );
wpfa_relationship_test_assert_same(
	array( 200 ),
	get_post_meta( 11, 'wpfa_speaker_events', true ),
	'Internal sync should be able to add reverse links after event edit permission has passed.'
);

wpfa_relationship_test_reset();
$GLOBALS['wpfa_relationship_test_meta'][10]['wpfa_speaker_events'] = array( 100 );
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_events'] = array( 100 );

$sync_method = new ReflectionMethod( 'Wpfaevent_Meta_Event', 'sync_event_speaker_relationships' );
if ( PHP_VERSION_ID < 80100 ) {
	$sync_method->setAccessible( true );
}
$sync_method->invoke( null, 100, array( 10 ), array( 11 ) );

wpfa_relationship_test_assert_same(
	'',
	get_post_meta( 10, 'wpfa_speaker_events', true ),
	'Event sync should remove old event-side speaker reverse links.'
);

wpfa_relationship_test_assert_same(
	'',
	get_post_meta( 12, 'wpfa_speaker_events', true ),
	'Event sync should remove stale reverse-only speaker links.'
);

wpfa_relationship_test_assert_same(
	array( 100 ),
	get_post_meta( 11, 'wpfa_speaker_events', true ),
	'Event sync should add reverse links for current speakers.'
);

wpfa_relationship_test_assert_same(
	array( 101, 102 ),
	Wpfaevent_Meta_Speaker::sanitize_event_ids( '101, 102, invalid, 101' ),
	'Speaker event meta sanitization should accept scalar ID lists consistently.'
);

echo 'Speaker relationship tests passed.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test output.
