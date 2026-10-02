<?php
/**
 * Tests for an Eventyay import that returns no sessions and no speakers.
 *
 * @package Wpfaevent
 */

/**
 * Covers keeping the stored program and warning the user when Eventyay hides it.
 */
class EventyayEmptyProgramTest extends WP_UnitTestCase {

	/**
	 * Speakers the mocked Eventyay API returns.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $eventyay_speakers = array();

	/**
	 * Temporary uploads base directory.
	 *
	 * @var string
	 */
	private $upload_basedir = '';

	/**
	 * Run each test as an administrator against a mocked Eventyay API on a reserved address.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->eventyay_speakers = array();
		$this->upload_basedir    = trailingslashit( sys_get_temp_dir() ) . 'wpfaevent-tests-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->upload_basedir );

		update_option(
			'wpfaevent_eventyay_import_settings',
			array(
				'base_url'       => 'https://203.0.113.10',
				'organizer_slug' => 'demo-organizer',
				'event_slug'     => 'demo-event',
			)
		);

		add_filter( 'http_request_host_is_external', '__return_true' );
		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_filter( 'wp_redirect', array( $this, 'stop_at_redirect' ) );
	}

	/**
	 * Clear the request globals and filters the tests fill in.
	 */
	public function tear_down() {
		$_POST    = array();
		$_REQUEST = array();
		remove_filter( 'http_request_host_is_external', '__return_true' );
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		remove_filter( 'wp_redirect', array( $this, 'stop_at_redirect' ) );
		parent::tear_down();
	}

	/**
	 * Answer Eventyay API requests: the event itself, its speakers, and empty lists for the rest.
	 *
	 * @param false|array|WP_Error $pre         Preemptive response.
	 * @param array                $parsed_args Request args.
	 * @param string               $url         Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $parsed_args, $url ) {
		unset( $pre, $parsed_args );

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$body = array(
			'count'   => 0,
			'next'    => null,
			'results' => array(),
		);

		if ( '/events/demo-event/' === substr( $path, -19 ) ) {
			$body = array(
				'name' => array( 'en' => 'Demo Event' ),
				'slug' => 'demo-event',
			);
		} elseif ( '/speakers/' === substr( $path, -10 ) ) {
			$body['count']   = count( $this->eventyay_speakers );
			$body['results'] = $this->eventyay_speakers;
		}

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
	 * Redirect uploads into a test-specific temporary directory.
	 *
	 * @param array $dirs Upload directory data.
	 * @return array
	 */
	public function filter_upload_dir( $dirs ) {
		$dirs['basedir'] = $this->upload_basedir;
		$dirs['path']    = $this->upload_basedir;

		return $dirs;
	}

	/**
	 * Stop a handler at its redirect so the test can inspect what it stored.
	 *
	 * @throws RuntimeException Always.
	 */
	public function stop_at_redirect() {
		throw new RuntimeException( 'redirected' );
	}

	/**
	 * Store a program for an event as an earlier import would have.
	 *
	 * @return array{0: int, 1: array, 2: array} Event ID, stored speakers, stored schedule.
	 */
	private function create_event_with_stored_program() {
		$event_id = self::factory()->post->create(
			array(
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
				'post_title'  => 'Demo Event',
			)
		);
		update_post_meta( $event_id, '_wpfa_eventyay_organizer_slug', 'demo-organizer' );
		update_post_meta( $event_id, '_wpfa_eventyay_event_slug', 'demo-event' );

		$speakers = array(
			array(
				'id'     => 'eventyay-ABCDEF',
				'name'   => 'Asha Rao',
				'source' => 'eventyay',
			),
		);
		$schedule = array(
			'name'   => 'Schedule',
			'source' => 'eventyay',
			'rows'   => 2,
			'cols'   => 1,
			'cells'  => array( array( 'Session' ), array( 'Running LLMs on a Raspberry Pi' ) ),
		);

		$store = new Wpfaevent_Eventyay_Dashboard_Store();
		$store->write_dashboard_json_file( 'speakers-' . $event_id . '.json', $speakers );
		$store->write_dashboard_json_file( 'schedule-' . $event_id . '.json', $schedule );

		return array( $event_id, $speakers, $schedule );
	}

	/**
	 * Run the program import for one event.
	 *
	 * @param int $event_id Event post ID.
	 * @return array|WP_Error
	 */
	private function import_program( $event_id ) {
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'import_eventyay_event_program' );
		$method->setAccessible( true );

		return $method->invoke( $importer, $event_id, $importer->get_eventyay_import_settings(), 'demo-event' );
	}

	/**
	 * An empty answer from Eventyay must not wipe a program imported earlier.
	 */
	public function test_an_empty_program_keeps_the_stored_speakers_and_schedule() {
		list( $event_id, $speakers, $schedule ) = $this->create_event_with_stored_program();

		$result = $this->import_program( $event_id );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['empty'] );
		$this->assertSame( 0, $result['session_count'] );
		$this->assertSame( 0, $result['speaker_count'] );

		$store = new Wpfaevent_Eventyay_Dashboard_Store();
		$this->assertSame( $speakers, $store->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() ) );
		$this->assertSame( $schedule, $store->read_dashboard_json_file( 'schedule-' . $event_id . '.json', array() ) );
	}

	/**
	 * A program with content is still imported and replaces what was stored.
	 */
	public function test_a_program_with_speakers_is_imported() {
		list( $event_id )        = $this->create_event_with_stored_program();
		$this->eventyay_speakers = array(
			array(
				'code'      => 'SAWHJN',
				'fullname'  => 'Liam Chen',
				'biography' => 'Contributor.',
			),
		);

		$result = $this->import_program( $event_id );

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'empty', $result );
		$this->assertSame( 1, $result['speaker_count'] );

		$stored = ( new Wpfaevent_Eventyay_Dashboard_Store() )->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() );
		$this->assertCount( 1, $stored );
		$this->assertSame( 'Liam Chen', $stored[0]['name'] );
	}

	/**
	 * Submit the import screen and return the notice it stores.
	 *
	 * @return array{type: string, message: string}
	 */
	private function run_import_screen() {
		$_POST                = array( 'wpfaevent_eventyay_return_page' => 'wpfaevent-import-events' );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'wpfaevent_import_eventyay_events' );

		try {
			( new Wpfaevent_Eventyay_Importer() )->handle_eventyay_events_import();
			$this->fail( 'The handler should have redirected.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'redirected', $e->getMessage() );
		}

		delete_option( Wpfaevent_Eventyay_Importer::get_import_in_progress_key() );

		return get_transient( 'wpfaevent_eventyay_import_notice_' . get_current_user_id() );
	}

	/**
	 * The import screen reports success for a program, then warns and keeps it once Eventyay hides it.
	 */
	public function test_the_import_screen_warns_about_an_empty_program_and_keeps_the_stored_one() {
		$this->eventyay_speakers = array(
			array(
				'code'     => 'SAWHJN',
				'fullname' => 'Liam Chen',
			),
		);

		$notice = $this->run_import_screen();
		$this->assertSame( 'success', $notice['type'] );
		$this->assertStringNotContainsString( Wpfaevent_Eventyay_Importer::get_empty_program_hint(), $notice['message'] );

		$events = get_posts(
			array(
				'post_type'   => 'wpfa_event',
				'post_status' => 'any',
				'fields'      => 'ids',
			)
		);
		$this->assertCount( 1, $events );

		$store    = new Wpfaevent_Eventyay_Dashboard_Store();
		$filename = 'speakers-' . $events[0] . '.json';
		$imported = $store->read_dashboard_json_file( $filename, array() );
		$this->assertSame( 'Liam Chen', $imported[0]['name'] );

		$this->eventyay_speakers = array();

		$notice = $this->run_import_screen();
		$this->assertSame( 'warning', $notice['type'] );
		$this->assertStringContainsString( 'Imported 0 session(s), 0 speaker(s)', $notice['message'] );
		$this->assertStringContainsString( Wpfaevent_Eventyay_Importer::get_empty_program_hint(), $notice['message'] );
		$this->assertSame( $imported, $store->read_dashboard_json_file( $filename, array() ) );
	}

	/**
	 * The speaker sync used by the event edit screen refuses an empty answer and keeps the stored program.
	 */
	public function test_the_speaker_sync_keeps_the_stored_program_when_eventyay_returns_nothing() {
		list( $event_id, $speakers, $schedule ) = $this->create_event_with_stored_program();

		$importer = new Wpfaevent_Eventyay_Importer();
		$result   = ( new Wpfaevent_Eventyay_Ajax_Sync() )->sync_speakers_for_event( $event_id, 'demo-event', $importer->get_eventyay_import_settings() );

		$this->assertWPError( $result );
		$this->assertSame( 'wpfaevent_eventyay_empty_program', $result->get_error_code() );
		$this->assertSame( Wpfaevent_Eventyay_Importer::get_empty_program_hint(), $result->get_error_message() );

		$store = new Wpfaevent_Eventyay_Dashboard_Store();
		$this->assertSame( $speakers, $store->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() ) );
		$this->assertSame( $schedule, $store->read_dashboard_json_file( 'schedule-' . $event_id . '.json', array() ) );
	}

	/**
	 * Updating one event from its dashboard warns the same way.
	 */
	public function test_the_event_dashboard_warns_about_an_empty_program() {
		list( $event_id, $speakers ) = $this->create_event_with_stored_program();

		$_POST = array(
			'event_id'             => $event_id,
			'wpfaevent_sync_nonce' => wp_create_nonce( 'wpfaevent_sync_event_dashboard_' . $event_id ),
		);

		try {
			( new Wpfaevent_Event_Dashboard_Page() )->handle_sync();
			$this->fail( 'The handler should have redirected.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'redirected', $e->getMessage() );
		}

		$notice = get_transient( 'wpfaevent_dashboard_notice_' . get_current_user_id() );
		$this->assertSame( 'warning', $notice['type'] );
		$this->assertStringContainsString( Wpfaevent_Eventyay_Importer::get_empty_program_hint(), $notice['message'] );
		$this->assertSame(
			$speakers,
			( new Wpfaevent_Eventyay_Dashboard_Store() )->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() )
		);
	}
}
