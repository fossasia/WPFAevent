<?php
/**
 * Regression tests for Eventyay sync HTTP error responses (Issue #70).
 *
 * @package Wpfaevent
 */

/**
 * Both sync controllers must preserve API failures in the response status.
 */
class EventyayAjaxErrorTest extends WP_Ajax_UnitTestCase {

	/**
	 * Check the real WordPress JSON serializer for both sync implementations.
	 *
	 * @dataProvider error_status_provider
	 * @param string            $controller    Sync controller class.
	 * @param array<string,int> $data          Error details from the API client.
	 * @param int               $expected_code Expected HTTP response code.
	 */
	public function test_error_status( $controller, $data, $expected_code ) {
		$reflection = new ReflectionClass( $controller );
		$instance   = $reflection->newInstanceWithoutConstructor();
		$method     = $reflection->getMethod( 'send_eventyay_ajax_error' );
		$method->setAccessible( true );

		$status   = null;
		$capture  = static function ( $header, $code ) use ( &$status ) {
			$status = $code;
			return $header;
		};
		$error    = new WP_Error( 'upstream_failure', 'The upstream request failed.', $data );
		$ob_level = ob_get_level();
		add_filter( 'status_header', $capture, 10, 2 );
		$this->_last_response = '';
		ob_start();

		try {
			$method->invoke( $instance, $error );
			$this->fail( 'The error response must terminate the AJAX request.' );
		} catch ( WPAjaxDieContinueException $exception ) {
			// WordPress captured the actual JSON response before terminating.
		} finally {
			remove_filter( 'status_header', $capture, 10 );
			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}
		}

		$this->assertSame( $expected_code, $status );
		$this->assertSame(
			array(
				'success' => false,
				'data'    => array_merge(
					array(
						'message' => 'The upstream request failed.',
						'code'    => 'upstream_failure',
					),
					$data
				),
			),
			json_decode( $this->_last_response, true )
		);
	}

	/**
	 * Cover upstream failures, local errors, precedence and invalid statuses.
	 *
	 * @return array<string,array{string,array<string,int>,int}> Test cases.
	 */
	public static function error_status_provider() {
		$cases = array(
			'unauthenticated' => array( array( 'http_status' => 401 ), 401 ),
			'forbidden'       => array( array( 'http_status' => 403 ), 403 ),
			'rate limited'    => array( array( 'http_status' => 429 ), 429 ),
			'unavailable'     => array( array( 'http_status' => 503 ), 503 ),
			'lower boundary'  => array( array( 'http_status' => 400 ), 400 ),
			'upper boundary'  => array( array( 'http_status' => 599 ), 599 ),
			'non error'       => array( array( 'http_status' => 399 ), 500 ),
			'out of range'    => array( array( 'http_status' => 600 ), 500 ),
			'missing status'  => array( array(), 500 ),
			'local error'     => array( array( 'status' => 400 ), 400 ),
			'local priority'  => array( array( 'status' => 403, 'http_status' => 503 ), 403 ),
		);
		$tests = array();
		foreach ( array( 'Wpfaevent_Eventyay_Ajax_Sync', 'Wpfaevent_AJAX_Controller' ) as $controller ) {
			foreach ( $cases as $name => $case ) {
				$tests[ $controller . ': ' . $name ] = array( $controller, $case[0], $case[1] );
			}
		}
		return $tests;
	}
}
