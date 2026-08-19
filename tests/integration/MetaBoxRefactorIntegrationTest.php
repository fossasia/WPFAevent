<?php
/**
 * Class MetaBoxRefactorIntegrationTest
 *
 * @package Wpfaevent
 */

/**
 * Behavioral verification for the meta box handler extraction (issue #92).
 *
 * Exercises Wpfaevent_Admin_Event_Metabox and Wpfaevent_Admin_Speaker_Metabox
 * to confirm the save/nonce/relationship-sync logic moved out of
 * Wpfaevent_Admin still behaves exactly as before.
 */
class MetaBoxRefactorIntegrationTest extends WP_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Test speaker ID.
	 *
	 * @var int
	 */
	private $speaker_id;

	/**
	 * Setup mock objects.
	 */
	public function setUp(): void {
		parent::setUp();

		// Log in as an administrator so current_user_can( 'edit_post' ) passes,
		// matching how these save handlers run for a real logged-in editor.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );

		$this->speaker_id = $this->factory->post->create( array( 'post_type' => 'wpfa_speaker' ) );
	}

	/**
	 * Both new handler classes are loaded and the old ones are gone.
	 */
	public function test_classes_are_wired_correctly() {
		$this->assertTrue( class_exists( 'Wpfaevent_Admin_Event_Metabox' ) );
		$this->assertTrue( class_exists( 'Wpfaevent_Admin_Speaker_Metabox' ) );
		$this->assertFalse( method_exists( 'Wpfaevent_Admin', 'add_meta_boxes' ) );
		$this->assertFalse( method_exists( 'Wpfaevent_Admin', 'save_event_meta' ) );
		$this->assertFalse( method_exists( 'Wpfaevent_Admin', 'save_speaker_meta' ) );
	}

	/**
	 * Registering meta boxes adds the expected boxes to their screens.
	 */
	public function test_register_meta_boxes_adds_expected_boxes() {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		set_current_screen( 'wpfa_event' );
		( new Wpfaevent_Admin_Event_Metabox() )->register_meta_boxes();
		$this->assertArrayHasKey( 'wpfa_event_details', $wp_meta_boxes['wpfa_event']['normal']['high'] );
		$this->assertArrayHasKey( 'wpfa_event_schedule_box', $wp_meta_boxes['wpfa_event']['normal']['default'] );
		$this->assertArrayHasKey( 'wpfa_event_sponsors_box', $wp_meta_boxes['wpfa_event']['normal']['default'] );
		$this->assertArrayHasKey( 'wpfa_event_exhibitors_box', $wp_meta_boxes['wpfa_event']['normal']['default'] );

		set_current_screen( 'wpfa_speaker' );
		( new Wpfaevent_Admin_Speaker_Metabox() )->register_meta_boxes();
		$this->assertArrayHasKey( 'wpfa_speaker_details', $wp_meta_boxes['wpfa_speaker']['normal']['high'] );

		set_current_screen( 'front' );
	}

	/**
	 * Saving event meta persists fields and rejects a missing/invalid nonce.
	 */
	public function test_save_event_meta_persists_fields_and_checks_nonce() {
		$handler = new Wpfaevent_Admin_Event_Metabox();

		// No nonce: nothing should be saved.
		$_POST = array( 'wpfa_event_location' => 'Nowhere' );
		$handler->save_event_meta( $this->event_id );
		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_location', true ) );

		// Valid nonce: fields persist.
		$_POST = array(
			'wpfa_event_meta_nonce' => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_start_date' => '2026-09-01',
			'wpfa_event_end_date'   => '2026-09-01',
			'wpfa_event_all_day'    => '1',
			'wpfa_event_location'   => 'FOSSASIA HQ',
			'wpfa_event_url'        => 'https://example.org/event',
		);
		$handler->save_event_meta( $this->event_id );

		$this->assertSame( '2026-09-01', get_post_meta( $this->event_id, 'wpfa_event_start_date', true ) );
		$this->assertSame( 'FOSSASIA HQ', get_post_meta( $this->event_id, 'wpfa_event_location', true ) );
		$this->assertSame( 'https://example.org/event', get_post_meta( $this->event_id, 'wpfa_event_url', true ) );

		$_POST = array();
	}

	/**
	 * Saving speaker meta persists fields and rejects a missing/invalid nonce.
	 */
	public function test_save_speaker_meta_persists_fields_and_checks_nonce() {
		$handler = new Wpfaevent_Admin_Speaker_Metabox();

		$_POST = array( 'wpfa_speaker_position' => 'Ignored' );
		$handler->save_speaker_meta( $this->speaker_id );
		$this->assertSame( '', get_post_meta( $this->speaker_id, 'wpfa_speaker_position', true ) );

		$_POST = array(
			'wpfa_speaker_meta_nonce'   => wp_create_nonce( 'wpfa_speaker_meta_nonce' ),
			'wpfa_speaker_position'     => 'Keynote Speaker',
			'wpfa_speaker_organization' => 'FOSSASIA',
			'wpfa_speaker_headshot_url' => 'https://example.org/headshot.jpg',
		);
		$handler->save_speaker_meta( $this->speaker_id );

		$this->assertSame( 'Keynote Speaker', get_post_meta( $this->speaker_id, 'wpfa_speaker_position', true ) );
		$this->assertSame( 'FOSSASIA', get_post_meta( $this->speaker_id, 'wpfa_speaker_organization', true ) );

		$_POST = array();
	}

	/**
	 * Assigning a speaker to an event via save_event_meta() syncs the
	 * relationship onto the speaker's own meta (event -> speaker direction).
	 */
	public function test_event_save_syncs_speaker_relationship() {
		$_POST = array(
			'wpfa_event_meta_nonce' => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_speakers'   => array( (string) $this->speaker_id ),
		);
		( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );
		$_POST = array();

		$this->assertContains( $this->speaker_id, (array) get_post_meta( $this->event_id, 'wpfa_event_speakers', true ) );
	}

	/**
	 * Assigning an event to a speaker via save_speaker_meta() syncs the
	 * relationship back onto the event's own meta (speaker -> event
	 * direction), exercising add_speaker_to_event()/remove_speaker_from_event().
	 */
	public function test_speaker_save_syncs_event_relationship_both_ways() {
		// Link speaker -> event.
		$_POST = array(
			'wpfa_speaker_meta_nonce' => wp_create_nonce( 'wpfa_speaker_meta_nonce' ),
			'wpfa_speaker_events'     => array( (string) $this->event_id ),
		);
		( new Wpfaevent_Admin_Speaker_Metabox() )->save_speaker_meta( $this->speaker_id );
		$_POST = array();

		$this->assertContains( $this->event_id, (array) get_post_meta( $this->speaker_id, 'wpfa_speaker_events', true ) );
		$this->assertContains( $this->speaker_id, (array) get_post_meta( $this->event_id, 'wpfa_event_speakers', true ) );

		// Unlink speaker from event -> event-side meta should drop it too.
		$_POST = array(
			'wpfa_speaker_meta_nonce' => wp_create_nonce( 'wpfa_speaker_meta_nonce' ),
			'wpfa_speaker_events'     => array(),
		);
		( new Wpfaevent_Admin_Speaker_Metabox() )->save_speaker_meta( $this->speaker_id );
		$_POST = array();

		$this->assertNotContains( $this->speaker_id, (array) get_post_meta( $this->event_id, 'wpfa_event_speakers', true ) );
	}
}
