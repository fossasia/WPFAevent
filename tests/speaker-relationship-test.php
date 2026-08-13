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
$GLOBALS['wpfa_relationship_test_titles']     = array();
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

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Minimal sanitize_text_field() fallback for standalone CLI tests.
	 *
	 * @param mixed $text Raw text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return is_scalar( $text ) ? trim( (string) $text ) : '';
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Minimal sanitize_key() fallback for standalone CLI tests.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( sanitize_text_field( $key ) ) );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Minimal sanitize_title() fallback for standalone CLI tests.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	function sanitize_title( $title ) {
		$title = strtolower( sanitize_text_field( $title ) );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );

		return trim( (string) $title, '-' );
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	/**
	 * Read a stubbed post title.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function get_the_title( $post_id ) {
		return isset( $GLOBALS['wpfa_relationship_test_titles'][ $post_id ] ) ? $GLOBALS['wpfa_relationship_test_titles'][ $post_id ] : '';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Minimal apply_filters() fallback for standalone CLI tests.
	 *
	 * @return mixed
	 */
	function apply_filters() {
		$args = func_get_args();

		return isset( $args[1] ) ? $args[1] : null;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Minimal esc_attr() fallback for standalone CLI tests.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function esc_attr( $text ) {
		return sanitize_text_field( $text );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Minimal esc_url() fallback for standalone CLI tests.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	function esc_url( $url ) {
		return sanitize_text_field( $url );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Minimal esc_html() fallback for standalone CLI tests.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function esc_html( $text ) {
		return sanitize_text_field( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	/**
	 * Minimal esc_attr_e() fallback for standalone CLI tests.
	 *
	 * @param string $text Text.
	 * @return void
	 */
	function esc_attr_e( $text ) {
		echo sanitize_text_field( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test stub.
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * Minimal esc_html_e() fallback for standalone CLI tests.
	 *
	 * @param string $text Text.
	 * @return void
	 */
	function esc_html_e( $text ) {
		echo sanitize_text_field( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test stub.
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * Minimal get_permalink() fallback for standalone CLI tests.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function get_permalink( $post_id ) {
		return 'https://example.test/speaker/' . absint( $post_id );
	}
}

if ( ! function_exists( 'taxonomy_exists' ) ) {
	/**
	 * Minimal taxonomy_exists() fallback for standalone CLI tests.
	 *
	 * @return bool
	 */
	function taxonomy_exists() {
		return false;
	}
}

if ( ! function_exists( 'wp_get_post_terms' ) ) {
	/**
	 * Minimal wp_get_post_terms() fallback for standalone CLI tests.
	 *
	 * @return array
	 */
	function wp_get_post_terms() {
		return array();
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Minimal wp_kses_post() fallback for standalone CLI tests.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function wp_kses_post( $text ) {
		return is_scalar( $text ) ? (string) $text : '';
	}
}

if ( ! function_exists( 'wpautop' ) ) {
	/**
	 * Minimal wpautop() fallback for standalone CLI tests.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function wpautop( $text ) {
		return '<p>' . str_replace( "\n", "</p><p>", trim( (string) $text ) ) . '</p>';
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
	$GLOBALS['wpfa_relationship_test_titles']     = array(
		10  => 'Roneel Kumar',
		11  => 'Peter Membrey',
		12  => 'Mitch Altman',
		100 => 'Event 100',
		200 => 'Event 200',
		300 => 'Event 300',
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

require_once dirname( __DIR__ ) . '/includes/class-wpfaevent-event-speaker-relation-manager.php';
require_once dirname( __DIR__ ) . '/includes/meta/class-wpfaevent-meta-event.php';
require_once dirname( __DIR__ ) . '/includes/meta/class-wpfaevent-meta-speaker.php';

if ( ! defined( 'WPFAEVENT_PATH' ) ) {
	define( 'WPFAEVENT_PATH', dirname( __DIR__ ) . '/' );
}

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

wpfa_relationship_test_reset();
$GLOBALS['wpfa_relationship_test_meta'][100]['wpfa_event_speakers']          = array( 10, 11, 12 );
$GLOBALS['wpfa_relationship_test_meta'][100]['wpfa_event_featured_speakers'] = array( 11 );
$GLOBALS['wpfa_relationship_test_meta'][10]['_wpfa_eventyay_speaker_id']     = 'lspk8633';
$GLOBALS['wpfa_relationship_test_meta'][11]['_wpfa_eventyay_speaker_id']     = '9AEJD7';
$GLOBALS['wpfa_relationship_test_meta'][12]['_wpfa_eventyay_speaker_id']     = 'lspk5150';

$dashboard_featured = array(
	array(
		'eventyay_speaker_id' => 'lspk5150',
		'featured'            => true,
	),
	array(
		'name'     => 'Roneel Kumar',
		'featured' => false,
	),
);

wpfa_relationship_test_assert_same(
	array( 11, 12 ),
	Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( 100, array( 10, 11, 12 ), $dashboard_featured ),
	'Featured speaker resolution should use explicit event meta and dashboard featured flags only.'
);

wpfa_relationship_test_assert_same(
	array( 11, 12 ),
	Wpfaevent_Event_Speaker_Relation_Manager::resolve_event_featured_speaker_ids( 100, array( 10, 11, 12 ), $dashboard_featured ),
	'Featured speaker relation manager should mirror explicit featured speaker resolution.'
);

wpfa_relationship_test_reset();
$GLOBALS['wpfa_relationship_test_meta'][100]['wpfa_event_speakers']      = array( 10, 11, 12 );
$GLOBALS['wpfa_relationship_test_meta'][10]['_wpfa_eventyay_speaker_id'] = 'lspk8633';
$GLOBALS['wpfa_relationship_test_meta'][11]['_wpfa_eventyay_speaker_id'] = '9AEJD7';
$GLOBALS['wpfa_relationship_test_meta'][12]['_wpfa_eventyay_speaker_id'] = 'lspk5150';

wpfa_relationship_test_assert_same(
	array(),
	Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( 100, array( 10, 11, 12 ), array() ),
	'Featured speaker resolution should not auto-select a fallback speaker when no explicit featured data exists.'
);

wpfa_relationship_test_reset();
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_position']      = 'Chief Scientist';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_organization']  = 'Cornfield Electronics';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_bio']           = 'Compact bio';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_talk_title']    = 'Open Hardware Keynote';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_talk_date']     = '2026-03-09';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_talk_time']     = '10:00';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_talk_end_time'] = '10:45';
$GLOBALS['wpfa_relationship_test_meta'][12]['wpfa_speaker_talk_abstract'] = 'This full abstract should be hidden for compact featured cards.';

$sid                       = 12;
$wpfa_speaker_card_variant = 'compact';
ob_start();
include dirname( __DIR__ ) . '/public/partials/speakers/speaker-card.php';
$compact_card_markup = ob_get_clean();

wpfa_relationship_test_assert_same(
	false,
	false !== strpos( $compact_card_markup, 'wpfa-talk-abstract' ),
	'Compact featured speaker cards should omit the session abstract markup.'
);

wpfa_relationship_test_assert_same(
	true,
	false !== strpos( $compact_card_markup, 'Open Hardware Keynote' ),
	'Compact featured speaker cards should still render concise session details.'
);

echo 'Speaker relationship tests passed.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test output.
