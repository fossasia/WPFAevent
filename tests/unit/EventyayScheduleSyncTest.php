<?php
/**
 * Unit tests for Eventyay schedule preservation on JSON:API sync responses (Issue #299).
 *
 * @package Wpfaevent
 */

/**
 * Eventyay schedule sync regression test class.
 */
class EventyayScheduleSyncTest extends WP_Ajax_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Eventyay event slug used by the fixtures.
	 *
	 * @var string
	 */
	private $event_slug = 'test-event';

	/**
	 * Mocked HTTP responses keyed by a URL fragment.
	 *
	 * @var array<string, array>
	 */
	private $mock_responses = array();

	/**
	 * Dashboard JSON store used to read back what the plugin wrote.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Setup event fixture and HTTP stubbing.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test Event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $this->event_id, '_eventyay_event_slug', $this->event_slug );

		$this->mock_responses = array();
		$this->store          = new Wpfaevent_Eventyay_Dashboard_Store();

		add_filter( 'pre_http_request', array( $this, 'mock_api_request' ), 10, 3 );
	}

	/**
	 * Teardown fixtures created by this test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_api_request' ), 10 );

		foreach ( array( 'speakers-', 'schedule-', 'site-settings-' ) as $prefix ) {
			$path = $this->store->get_dashboard_json_path( $prefix . $this->event_id . '.json' );
			if ( $path && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}

		$_POST = array();

		parent::tearDown();
	}

	/**
	 * Mock HTTP requests to return canned responses by URL fragment.
	 *
	 * @param false|array|WP_Error $pre         A preemptive return value of the request.
	 * @param array                $parsed_args The HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @return array Mocked response array.
	 */
	public function mock_api_request( $pre, $parsed_args, $url ) {
		unset( $pre, $parsed_args );

		foreach ( $this->mock_responses as $url_fragment => $response ) {
			if ( false !== strpos( $url, $url_fragment ) ) {
				return $response;
			}
		}

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'detail' => 'Not Found' ) ),
			'response' => array(
				'code'    => 404,
				'message' => 'Not Found',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Build a mocked HTTP 200 JSON response array.
	 *
	 * @param array $body Decoded response body.
	 * @return array
	 */
	private function json_response( $body ) {
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Build an Eventyay REST speakers "results" payload with one scheduled session per title.
	 *
	 * @param array $session_titles Titles for the sessions to attach to the single speaker.
	 * @return array
	 */
	private function rest_speakers_payload( $session_titles ) {
		$submissions = array();

		foreach ( $session_titles as $index => $title ) {
			$submissions[] = array(
				'code'  => 'sess-' . ( $index + 1 ),
				'title' => $title,
				'slots' => array(
					array(
						'start' => '2026-01-0' . ( $index + 1 ) . 'T10:00:00Z',
						'end'   => '2026-01-0' . ( $index + 1 ) . 'T11:00:00Z',
					),
				),
			);
		}

		return array(
			'count'    => 1,
			'next'     => null,
			'previous' => null,
			'results'  => array(
				array(
					'code'        => 'spk-1',
					'fullname'    => 'Alice Example',
					'biography'   => 'Alice bio',
					'submissions' => $submissions,
				),
			),
		);
	}

	/**
	 * Build a JSON:API speakers document that carries no session/schedule information.
	 *
	 * @return array
	 */
	private function jsonapi_speakers_payload() {
		return array(
			'data' => array(
				array(
					'type'       => 'speaker',
					'id'         => 'spk-2',
					'attributes' => array(
						'name'      => 'Speaker JsonApi',
						'biography' => 'Bio from JSON:API',
					),
				),
			),
		);
	}

	/**
	 * Build a JSON:API sessions document (with an included speaker) that carries no 'sessions' key
	 * once normalized, reproducing the payload shape returned by an Eventyay sessions endpoint.
	 *
	 * @return array
	 */
	private function jsonapi_sessions_payload() {
		return array(
			'data'     => array(
				array(
					'type'          => 'session',
					'id'            => 'sess-jsonapi-1',
					'attributes'    => array(
						'title'     => 'JSON:API Session',
						'starts-at' => '2026-02-01T10:00:00Z',
						'ends-at'   => '2026-02-01T11:00:00Z',
					),
					'relationships' => array(
						'speakers' => array(
							'data' => array(
								array(
									'type' => 'speaker',
									'id'   => 'spk-2',
								),
							),
						),
					),
				),
			),
			'included' => array(
				array(
					'type'       => 'speaker',
					'id'         => 'spk-2',
					'attributes' => array(
						'name'      => 'Speaker JsonApi',
						'biography' => 'Bio from JSON:API',
					),
				),
			),
		);
	}

	/**
	 * Read back the dashboard schedule sessions written for the fixture event.
	 *
	 * @return array
	 */
	private function read_schedule_sessions() {
		$schedule = $this->store->read_dashboard_json_file( 'schedule-' . $this->event_id . '.json', array() );

		return is_array( $schedule ) && ! empty( $schedule['sessions'] ) && is_array( $schedule['sessions'] )
			? $schedule['sessions']
			: array();
	}

	/**
	 * REST sync (organizer slug present) writes the Eventyay schedule from its sessions, and a
	 * second REST sync with fewer sessions shrinks it. Green control for ruling 2.
	 */
	public function test_sync_speakers_for_event_rest_sync_writes_then_shrinks_schedule() {
		$settings = array(
			'base_url'       => 'https://api.eventyay.com',
			'organizer_slug' => 'ev',
			'api_token'      => '',
		);

		$this->mock_responses = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => $this->json_response(
				$this->rest_speakers_payload( array( 'Session One', 'Session Two' ) )
			),
		);

		$sync   = new Wpfaevent_Eventyay_Ajax_Sync();
		$result = $sync->sync_speakers_for_event( $this->event_id, $this->event_slug, $settings );

		$this->assertIsArray( $result );
		$this->assertSame( 2, $result['sessions'] );
		$this->assertCount( 2, $this->read_schedule_sessions() );

		// Second REST sync with only one session should shrink the schedule.
		$this->mock_responses = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => $this->json_response(
				$this->rest_speakers_payload( array( 'Session One' ) )
			),
		);

		$result = $sync->sync_speakers_for_event( $this->event_id, $this->event_slug, $settings );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['sessions'] );
		$this->assertCount( 1, $this->read_schedule_sessions() );
	}

	/**
	 * A REST response whose speakers have no sessions still produces an empty Eventyay schedule
	 * (mirror semantics). Green control for ruling 2.
	 */
	public function test_sync_speakers_for_event_rest_sync_without_sessions_produces_empty_schedule() {
		$settings = array(
			'base_url'       => 'https://api.eventyay.com',
			'organizer_slug' => 'ev',
			'api_token'      => '',
		);

		$this->mock_responses = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => $this->json_response(
				array(
					'count'    => 1,
					'next'     => null,
					'previous' => null,
					'results'  => array(
						array(
							'code'        => 'spk-1',
							'fullname'    => 'Alice Example',
							'biography'   => 'Alice bio',
							'submissions' => array(),
						),
					),
				)
			),
		);

		$sync   = new Wpfaevent_Eventyay_Ajax_Sync();
		$result = $sync->sync_speakers_for_event( $this->event_id, $this->event_slug, $settings );

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['sessions'] );
		$this->assertSame( array(), $this->read_schedule_sessions() );
	}

	/**
	 * A follow-up sync_speakers_for_event() call whose response is a JSON:API document (no
	 * organizer slug, legacy JSON:API speakers URL) must leave the existing Eventyay-imported
	 * schedule untouched instead of emptying it.
	 */
	public function test_sync_speakers_for_event_json_api_response_preserves_existing_schedule() {
		$rest_settings = array(
			'base_url'       => 'https://api.eventyay.com',
			'organizer_slug' => 'ev',
			'api_token'      => '',
		);

		$this->mock_responses = array(
			'/api/v1/organizers/ev/events/test-event/speakers/' => $this->json_response(
				$this->rest_speakers_payload( array( 'Session One', 'Session Two' ) )
			),
		);

		$sync = new Wpfaevent_Eventyay_Ajax_Sync();
		$sync->sync_speakers_for_event( $this->event_id, $this->event_slug, $rest_settings );

		$this->assertCount( 2, $this->read_schedule_sessions() );

		// Legacy JSON:API sync: empty organizer slug makes the plugin build the legacy
		// api/v1/events/{slug}/speakers path against a non-api.eventyay.com base URL.
		$jsonapi_settings = array(
			'base_url'       => 'https://eventyay.com',
			'organizer_slug' => '',
			'api_token'      => '',
		);

		$this->mock_responses = array(
			'/api/v1/events/test-event/speakers' => $this->json_response( $this->jsonapi_speakers_payload() ),
		);

		$result = $sync->sync_speakers_for_event( $this->event_id, $this->event_slug, $jsonapi_settings );

		$this->assertIsArray( $result );
		$this->assertSame(
			array( 'Session One', 'Session Two' ),
			wp_list_pluck( $this->read_schedule_sessions(), 'title' )
		);
	}

	/**
	 * A follow-up fossasia_sync_eventyay AJAX sync whose response is a JSON:API document
	 * (a sessions URL, expanded with include=speakers,track by the handler) must leave the
	 * existing Eventyay-imported schedule untouched instead of emptying it.
	 */
	public function test_ajax_sync_eventyay_json_api_response_preserves_existing_schedule() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user     = new WP_User( $admin_id );
		$user->add_cap( Wpfaevent_Roles::CAP_IMPORT_EVENTYAY );
		wp_set_current_user( $admin_id );

		$rest_url = 'https://api.eventyay.com/api/v1/organizers/ev/events/test-event/speakers';

		$this->mock_responses = array(
			$rest_url => $this->json_response(
				$this->rest_speakers_payload( array( 'Session One', 'Session Two' ) )
			),
		);

		$response = $this->execute_ajax_sync(
			array(
				'event_id'         => $this->event_id,
				'eventyay_api_url' => $rest_url,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertCount( 2, $this->read_schedule_sessions() );

		$sessions_url = 'https://api.eventyay.com/v1/events/test-event/sessions';

		$this->mock_responses = array(
			$sessions_url => $this->json_response( $this->jsonapi_sessions_payload() ),
		);

		$response = $this->execute_ajax_sync(
			array(
				'event_id'         => $this->event_id,
				'eventyay_api_url' => $sessions_url,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame(
			array( 'Session One', 'Session Two' ),
			wp_list_pluck( $this->read_schedule_sessions(), 'title' )
		);
	}

	/**
	 * Post to the fossasia_sync_eventyay AJAX action and decode the JSON response.
	 *
	 * @param array $post_fields Extra $_POST fields for the request.
	 * @return array
	 */
	private function execute_ajax_sync( $post_fields ) {
		$_POST                = $post_fields;
		$_POST['action']      = 'fossasia_sync_eventyay';
		$_POST['nonce']       = wp_create_nonce( 'fossasia_admin_nonce' );
		$this->_last_response = '';

		try {
			$this->_handleAjax( 'fossasia_sync_eventyay' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->_last_response = $e->getMessage();
		} catch ( WPAjaxDieContinueException $e ) {
			if ( empty( $this->_last_response ) ) {
				$this->_last_response = $e->getMessage();
			}
		}

		return json_decode( $this->_last_response, true );
	}
}
