<?php
/**
 * Class APIClientTest
 *
 * @package Wpfaevent
 */

/**
 * Unit tests for Eventyay API Client authentication graceful exit.
 */
class APIClientTest extends WP_UnitTestCase {

	/**
	 * Request counter to keep track of how many times the API is requested.
	 *
	 * @var int
	 */
	private $request_count = 0;

	/**
	 * Mock HTTP status code to return.
	 *
	 * @var int
	 */
	private $mock_status_code = 401;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->request_count = 0;

		// Intercept HTTP requests.
		add_filter( 'pre_http_request', array( $this, 'mock_api_request' ), 10, 3 );
	}

	/**
	 * Teardown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'pre_http_request', array( $this, 'mock_api_request' ), 10 );
	}

	/**
	 * Mock API requests to return specific HTTP status codes.
	 *
	 * @param false|array|WP_Error $pre         A preemptive return value of the request.
	 * @param array                $parsed_args The HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @return array Mocked response array.
	 */
	public function mock_api_request( $pre, $parsed_args, $url ) {
		++$this->request_count;

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'detail' => 'Mock Error Detail' ) ),
			'response' => array(
				'code'    => $this->mock_status_code,
				'message' => 'Mock Error Message',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Verify that fetch_eventyay_rest_json exits immediately (only 1 request) on 401 error.
	 */
	public function test_api_client_exits_early_on_401() {
		$this->mock_status_code = 401;

		$client = new Wpfaevent_Eventyay_API_Client();
		$result = $client->fetch_eventyay_rest_json( 'https://api.eventyay.com/v1/events/test', 'dummy_token' );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertEquals( 401, $result->get_error_data()['http_status'] );
		// It should only make exactly 1 request because it exits early on 401.
		$this->assertEquals( 1, $this->request_count );
	}

	/**
	 * Verify that fetch_eventyay_rest_json exits immediately (only 1 request) on 403 error.
	 */
	public function test_api_client_exits_early_on_403() {
		$this->mock_status_code = 403;

		$client = new Wpfaevent_Eventyay_API_Client();
		$result = $client->fetch_eventyay_rest_json( 'https://api.eventyay.com/v1/events/test', 'dummy_token' );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertEquals( 403, $result->get_error_data()['http_status'] );
		// It should only make exactly 1 request because it exits early on 403.
		$this->assertEquals( 1, $this->request_count );
	}
}
