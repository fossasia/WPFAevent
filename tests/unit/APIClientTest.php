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
	 * Mocked HTTP responses keyed by a URL fragment.
	 *
	 * @var array<string, array>
	 */
	private $mock_responses = array();

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->request_count  = 0;
		$this->mock_responses = array();

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

		foreach ( $this->mock_responses as $url_fragment => $response ) {
			if ( false !== strpos( $url, $url_fragment ) ) {
				return $response;
			}
		}

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

	/**
	 * Verify featured speaker flags are hydrated from the public speakers page when the organizer API omits them.
	 */
	public function test_fetch_eventyay_event_speaker_program_uses_public_speakers_page_for_featured_state() {
		$this->mock_status_code = 200;
		$this->mock_responses   = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => array(
				'headers'  => array(),
				'body'     => wp_json_encode(
					array(
						'count'    => 2,
						'next'     => null,
						'previous' => null,
						'results'  => array(
							array(
								'code'        => 'spk-1',
								'fullname'    => 'Alice Example',
								'biography'   => 'Alice bio',
								'submissions' => array(),
							),
							array(
								'code'        => 'spk-2',
								'fullname'    => 'Bob Example',
								'biography'   => 'Bob bio',
								'submissions' => array(),
							),
						),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
			'/ev/test-event/speakers/' => array(
				'headers'  => array(),
				'body'     => '<script id="pretalx-schedule-data" type="application/json">' . wp_json_encode(
					array(
						'speakers' => array(
							array(
								'code'              => 'spk-1',
								'name'              => 'Alice Example',
								'is_featured'       => true,
								'featured_position' => 4,
							),
							array(
								'code'              => 'spk-2',
								'name'              => 'Bob Example',
								'is_featured'       => false,
								'featured_position' => 9,
							),
						),
					)
				) . '</script>',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
		);

		$client  = new Wpfaevent_Eventyay_API_Client();
		$program = $client->fetch_eventyay_event_speaker_program(
			array(
				'base_url'       => 'https://eventyay.com',
				'organizer_slug' => 'ev',
				'event_slug'     => 'test-event',
				'api_token'      => '',
			),
			'test-event'
		);

		$this->assertIsArray( $program );
		$this->assertCount( 2, $program['speakers'] );
		$this->assertTrue( $program['speakers'][0]['featured'] );
		$this->assertSame( 4, $program['speakers'][0]['featured_order'] );
		$this->assertFalse( $program['speakers'][1]['featured'] );
		$this->assertSame( 9, $program['speakers'][1]['featured_order'] );
	}

	/**
	 * Verify that if the public speakers page fails, the API client falls back to the main event landing page.
	 */
	public function test_fetch_eventyay_event_speaker_program_falls_back_to_main_landing_page() {
		$this->mock_status_code = 200;
		$this->mock_responses   = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => array(
				'headers'  => array(),
				'body'     => wp_json_encode(
					array(
						'count'    => 1,
						'next'     => null,
						'previous' => null,
						'results'  => array(
							array(
								'code'        => 'spk-99',
								'fullname'    => 'Charlie Fallback',
								'biography'   => 'Charlie bio',
								'submissions' => array(),
							),
						),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
			'/ev/test-event/speakers/' => array(
				'headers'  => array(),
				'body'     => 'Not Found',
				'response' => array(
					'code'    => 404,
					'message' => 'Not Found',
				),
				'cookies'  => array(),
				'filename' => null,
			),
			'/ev/test-event/'          => array(
				'headers'  => array(),
				'body'     => '<html><body><script id="pretalx-schedule-data" type="application/json">' . wp_json_encode(
					array(
						'speakers' => array(
							array(
								'code'        => 'spk-99',
								'name'        => 'Charlie Fallback',
								'is_featured' => true,
							),
						),
					)
				) . '</script></body></html>',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
		);

		$client  = new Wpfaevent_Eventyay_API_Client();
		$program = $client->fetch_eventyay_event_speaker_program(
			array(
				'base_url'       => 'https://eventyay.com',
				'organizer_slug' => 'ev',
				'event_slug'     => 'test-event',
				'api_token'      => '',
			),
			'test-event'
		);

		$this->assertIsArray( $program );
		$this->assertCount( 1, $program['speakers'] );
		$this->assertTrue( $program['speakers'][0]['featured'] );
	}

	/**
	 * Verify that the regex in extract_eventyay_public_speaker_featured_map is robust to attributes/quotes.
	 */
	public function test_extract_eventyay_public_speaker_featured_map_robust_regex() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$json   = wp_json_encode(
			array(
				'speakers' => array(
					array(
						'code'        => 'spk-abc',
						'name'        => 'Abc Speaker',
						'is_featured' => true,
					),
				),
			)
		);

		// Variant 1: standard quotes, type before id.
		$html_1 = '<script type="application/json" id="pretalx-schedule-data">' . $json . '</script>';
		$map_1  = $parser->extract_eventyay_public_speaker_featured_map( $html_1 );
		$this->assertArrayHasKey( 'spk-abc', $map_1 );
		$this->assertTrue( $map_1['spk-abc']['featured'] );

		// Variant 2: single quotes, other attributes.
		$html_2 = '<script data-test="val" id=\'pretalx-schedule-data\' class=\'test-class\'>' . $json . '</script>';
		$map_2  = $parser->extract_eventyay_public_speaker_featured_map( $html_2 );
		$this->assertArrayHasKey( 'spk-abc', $map_2 );
		$this->assertTrue( $map_2['spk-abc']['featured'] );
	}
}
