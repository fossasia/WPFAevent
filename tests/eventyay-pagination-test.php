<?php
// phpcs:ignoreFile -- Standalone CLI test defines minimal WordPress stubs and executable assertions.
/**
 * Eventyay pagination URL normalization checks.
 *
 * Run with: php tests/eventyay-pagination-test.php
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPFAEVENT_PATH' ) ) {
	define( 'WPFAEVENT_PATH', dirname( __DIR__ ) . '/' );
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

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Minimal translation fallback for standalone CLI tests.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html__( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Minimal esc_url_raw() fallback for standalone CLI tests.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL ) ? $url : '';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	/**
	 * Minimal untrailingslashit() fallback for standalone CLI tests.
	 *
	 * @param string $string String value.
	 * @return string
	 */
	function untrailingslashit( $string ) {
		return rtrim( $string, '/\\' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Minimal trailingslashit() fallback for standalone CLI tests.
	 *
	 * @param string $string String value.
	 * @return string
	 */
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_http_validate_url' ) ) {
	/**
	 * Minimal wp_http_validate_url() fallback for standalone CLI tests.
	 *
	 * @param string $url Raw URL.
	 * @return string|false
	 */
	function wp_http_validate_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : false;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Minimal wp_parse_url() fallback for standalone CLI tests.
	 *
	 * @param string $url       Raw URL.
	 * @param int    $component Optional URL component.
	 * @return array|string|false
	 */
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for standalone CLI tests.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code, $message ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}
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
function wpfaevent_pagination_test_assert_same( $expected, $actual, $message ) {
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
function wpfaevent_pagination_test_invoke( Wpfaevent_Eventyay_Importer $importer, $method, array $args ) {
	$reflection = new ReflectionClass( $importer );
	$callable   = $reflection->getMethod( $method );

	return $callable->invokeArgs( $importer, $args );
}

$importer      = new Wpfaevent_Eventyay_Importer();
$reference_url = 'https://eventyay.com/api/v1/organizers/test/events/?page_size=100';

$query_only = wpfaevent_pagination_test_invoke(
	$importer,
	'normalize_eventyay_next_url',
	array( '?page=2&page_size=100', $reference_url )
);
wpfaevent_pagination_test_assert_same(
	'https://eventyay.com/api/v1/organizers/test/events/?page=2&page_size=100',
	$query_only,
	'Query-only next links should keep the current endpoint path.'
);

$absolute_path = wpfaevent_pagination_test_invoke(
	$importer,
	'normalize_eventyay_next_url',
	array( '/api/v1/organizers/test/events/?page=2', $reference_url )
);
wpfaevent_pagination_test_assert_same(
	'https://eventyay.com/api/v1/organizers/test/events/?page=2',
	$absolute_path,
	'Absolute-path next links should resolve against the current endpoint origin.'
);

$full_url = wpfaevent_pagination_test_invoke(
	$importer,
	'normalize_eventyay_next_url',
	array(
		'https://eventyay.com/api/v1/organizers/test/events/?page=2',
		$reference_url,
	)
);
wpfaevent_pagination_test_assert_same(
	'https://eventyay.com/api/v1/organizers/test/events/?page=2',
	$full_url,
	'Full next URLs on the same host should pass through unchanged.'
);

$empty_next = wpfaevent_pagination_test_invoke(
	$importer,
	'normalize_eventyay_next_url',
	array( '', $reference_url )
);
wpfaevent_pagination_test_assert_same( '', $empty_next, 'Empty next links should normalize to an empty string.' );

$untrusted_host = wpfaevent_pagination_test_invoke(
	$importer,
	'normalize_eventyay_next_url',
	array( 'https://evil.example/events/?page=2', $reference_url )
);
wpfaevent_pagination_test_assert_same(
	true,
	$untrusted_host instanceof WP_Error,
	'Next URLs on a different host should be rejected.'
);
wpfaevent_pagination_test_assert_same(
	'wpfaevent_eventyay_untrusted_next_url',
	$untrusted_host->get_error_code(),
	'Untrusted next URLs should use the expected error code.'
);

fwrite( STDOUT, 'Eventyay pagination tests passed.' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
