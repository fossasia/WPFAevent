<?php
/**
 * Unit tests for Featured Speakers manual ordering (Issue #249).
 *
 * @package Wpfaevent
 */

/**
 * Featured Speakers manual ordering test class.
 */
class FeaturedSpeakersTest extends WP_Ajax_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Test speaker IDs.
	 *
	 * @var array<int>
	 */
	private $speaker_ids;

	/**
	 * Setup event and speaker fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test event.
		$this->event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test Event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		// Create test speakers.
		$this->speaker_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->speaker_ids[] = $this->factory->post->create(
				array(
					'post_title'  => 'Speaker ' . $i,
					'post_type'   => 'wpfa_speaker',
					'post_status' => 'publish',
				)
			);
		}

		// Link speakers to event.
		update_post_meta( $this->event_id, 'wpfa_event_speakers', $this->speaker_ids );
		foreach ( $this->speaker_ids as $sid ) {
			update_post_meta( $sid, 'wpfa_speaker_events', array( $this->event_id ) );
		}
	}

	/**
	 * Test marking and unmarking speakers as featured.
	 */
	public function test_resolve_featured_speakers_with_manual_mode() {
		// Before manual mode is set, legacy auto fallback is used.
		$dashboard_speakers = array(
			array(
				'name'     => 'Speaker 2',
				'featured' => true,
			),
		);
		$resolved           = Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $this->event_id, $this->speaker_ids, $dashboard_speakers );
		$this->assertSame( array( $this->speaker_ids[1] ), $resolved );

		// Set manual mode and feature only Speaker 2 and Speaker 3.
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers_manual', 'yes' );
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers', array( $this->speaker_ids[1], $this->speaker_ids[2] ) );

		$resolved = Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $this->event_id, $this->speaker_ids, $dashboard_speakers );
		$this->assertEqualsCanonicalizing( array( $this->speaker_ids[1], $this->speaker_ids[2] ), $resolved );

		// Unmark/unfeature Speaker 2 (leave only Speaker 3 featured).
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers', array( $this->speaker_ids[2] ) );
		$resolved = Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $this->event_id, $this->speaker_ids, $dashboard_speakers );
		$this->assertSame( array( $this->speaker_ids[2] ), $resolved );
	}

	/**
	 * Test saving and restoring the exact selected order.
	 */
	public function test_featured_speakers_order_preservation() {
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers_manual', 'yes' );

		// Order: Speaker 3 then Speaker 1.
		$expected_order = array( $this->speaker_ids[2], $this->speaker_ids[0] );
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers', $expected_order );

		$resolved = Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $this->event_id, $this->speaker_ids );
		$this->assertSame( $expected_order, $resolved );
	}

	/**
	 * Test handling of missing/deleted speakers.
	 */
	public function test_gracefully_handles_deleted_speakers() {
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers_manual', 'yes' );
		update_post_meta( $this->event_id, 'wpfa_event_featured_speakers', array( $this->speaker_ids[0], 99999, $this->speaker_ids[1] ) ); // 99999 is non-existent.

		$resolved = Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $this->event_id, $this->speaker_ids );
		$this->assertSame( array( $this->speaker_ids[0], $this->speaker_ids[1] ), $resolved );
	}

	/**
	 * Test authorization, nonce validation and write behavior of AJAX handler.
	 */
	public function test_ajax_handler_security_checks() {
		$page = new Wpfaevent_Event_Dashboard_Page();

		// 1. Logged in user with no capabilities (subscriber)
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$_POST['event_id']    = $this->event_id;
		$_POST['nonce']       = wp_create_nonce( 'wpfaevent_save_featured_speakers_' . $this->event_id );
		$_POST['speaker_ids'] = array( $this->speaker_ids[0] );

		$res = $this->execute_ajax( $page );
		$this->assertFalse( $res['success'] );
		$this->assertSame( 'You are not allowed to edit this event.', $res['data']['message'] );

		// 2. Logged in with correct capability, but invalid nonce
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$_POST['nonce'] = 'invalid_nonce';

		$res = $this->execute_ajax( $page );
		$this->assertFalse( $res['success'] );
		$this->assertSame( 'Invalid request or session expired.', $res['data']['message'] );

		// 3. Correct credentials, correct nonce, successful write
		$_POST['nonce'] = wp_create_nonce( 'wpfaevent_save_featured_speakers_' . $this->event_id );

		$res = $this->execute_ajax( $page );
		$this->assertTrue( $res['success'] );
		$this->assertSame( 'Featured speakers saved successfully.', $res['data']['message'] );

		// Verify write in database.
		$saved_ids = get_post_meta( $this->event_id, 'wpfa_event_featured_speakers', true );
		$this->assertEqualsCanonicalizing( array( $this->speaker_ids[0] ), $saved_ids );
		$this->assertSame( 'yes', get_post_meta( $this->event_id, 'wpfa_event_featured_speakers_manual', true ) );
	}

	/**
	 * Helper to execute AJAX controller action under output buffer and catch response.
	 *
	 * @param Wpfaevent_Event_Dashboard_Page $page Dashboard page controller.
	 * @return array
	 */
	private function execute_ajax( $page ) {
		unset( $page ); // Wpfaevent_Event_Dashboard_Page is called via hook registration.
		$_POST['action']      = 'wpfaevent_save_featured_speakers';
		$this->_last_response = '';
		try {
			$this->_handleAjax( 'wpfaevent_save_featured_speakers' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->_last_response = $e->getMessage();
		} catch ( WPAjaxDieContinueException $e ) {
			if ( empty( $this->_last_response ) ) {
				$this->_last_response = $e->getMessage();
			}
		} catch ( Throwable $e ) {
			unset( $e );
		}
		return json_decode( $this->_last_response, true );
	}
}
