<?php
/**
 * Tests for the maintained dashboard Eventyay sync action (Issue #70).
 *
 * @package Wpfaevent
 */

/**
 * Exercise the public hook, request guards and HTTP client together.
 */
class EventyayAjaxHandlerTest extends WP_Ajax_UnitTestCase {

	/**
	 * Sync must be registered on the maintained admin, without a legacy bridge.
	 */
	public function test_sync_action_is_registered_on_the_admin() {
		global $wp_filter;

		$this->assertArrayHasKey( 'wp_ajax_fossasia_sync_eventyay', $wp_filter );
		foreach ( $wp_filter['wp_ajax_fossasia_sync_eventyay']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$handler = $callback['function'];
				if ( is_array( $handler ) && $handler[0] instanceof Wpfaevent_Admin && 'ajax_sync_eventyay' === $handler[1] ) {
					$this->assertFalse( has_action( 'wp_ajax_nopriv_fossasia_sync_eventyay' ) );
					return;
				}
			}
		}
		$this->fail( 'The sync action must delegate through Wpfaevent_Admin.' );
	}

	/**
	 * Failed requests must terminate at the expected guard or API boundary.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider failure_provider
	 * @param string $scenario          Failure to exercise.
	 * @param int    $expected_code Expected AJAX HTTP status.
	 * @param string $message       Expected response message fragment.
	 * @param int    $request_count Expected outgoing request count.
	 */
	public function test_sync_request_failures( $scenario, $expected_code, $message, $request_count ) {
		$role = 'unauthorized' === $scenario ? 'subscriber' : 'administrator';
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$event_id = self::factory()->post->create( array( 'post_type' => 'wpfa_event' ) );
		$requests = array();
		$status   = null;
		$capture  = static function ( $header, $code ) use ( &$status ) {
			$status = $code;
			return $header;
		};
		$http     = static function ( $pre, $args, $url ) use ( &$requests, $scenario ) {
			$requests[] = array(
				'url'  => $url,
				'args' => $args,
			);
			return array(
				'headers'  => array(),
				'body'     => 'malformed' === $scenario ? '{' : wp_json_encode( array( 'detail' => 'Token rejected' ) ),
				'response' => array( 'code' => 'upstream' === $scenario ? 401 : 200 ),
				'cookies'  => array(),
			);
		};
		$_POST    = array(
			'nonce'            => 'nonce' === $scenario ? 'invalid' : wp_create_nonce( 'fossasia_admin_nonce' ),
			'event_id'         => 'missing event' === $scenario ? 0 : $event_id,
			'eventyay_api_url' => 'missing URL' === $scenario ? '' : 'https://api.eventyay.com/v1/events/example/sessions?filter[state]=confirmed',
		);
		add_filter( 'pre_http_request', $http, 10, 3 );
		add_filter( 'status_header', $capture, 10, 2 );
		$terminated = false;

		try {
			$this->_handleAjax( 'fossasia_sync_eventyay' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$terminated = true;
		} finally {
			remove_filter( 'pre_http_request', $http, 10 );
			remove_filter( 'status_header', $capture, 10 );
			$uploads = wp_upload_dir();
			wp_delete_file( trailingslashit( $uploads['basedir'] ) . 'fossasia-data/site-settings-' . $event_id . '.json' );
			$_POST    = array();
			$_GET     = array();
			$_REQUEST = array();
		}

		$this->assertTrue( $terminated, 'Every failure must terminate the AJAX request.' );
		$this->assertSame( $expected_code, $status );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( $message, $response['data']['message'] );
		$this->assertCount( $request_count, $requests );
		if ( $request_count ) {
			$query = array();
			wp_parse_str( wp_parse_url( $requests[0]['url'], PHP_URL_QUERY ), $query );
			$this->assertSame( 'speakers,track', $query['include'] );
			$this->assertSame( '200', $query['page']['size'] );
			$this->assertSame( 'confirmed', $query['filter']['state'] );
			$this->assertSame( 20, $requests[0]['args']['timeout'] );
			$this->assertSame( 3, $requests[0]['args']['redirection'] );
		}
		if ( 'upstream' === $scenario ) {
			$this->assertSame( 401, $response['data']['http_status'] );
			$this->assertSame( array( 'detail' => 'Token rejected' ), $response['data']['body'] );
		}
	}

	/**
	 * Security guards must run before transport, and API errors must be preserved.
	 *
	 * @return array<string,array{string,int,string,int}> Request failures.
	 */
	public static function failure_provider() {
		return array(
			'invalid nonce'       => array( 'nonce', 403, 'Invalid nonce', 0 ),
			'unauthorized user'   => array( 'unauthorized', 403, 'Unauthorized', 0 ),
			'missing event'       => array( 'missing event', 400, 'Missing event ID', 0 ),
			'missing URL'         => array( 'missing URL', 400, 'Please save an Eventyay API URL', 0 ),
			'upstream failure'    => array( 'upstream', 401, 'Token rejected', 1 ),
			'malformed JSON'      => array( 'malformed', 500, 'malformed JSON', 1 ),
			'invalid API payload' => array( 'invalid payload', 502, 'JSON:API data member', 1 ),
		);
	}
}
