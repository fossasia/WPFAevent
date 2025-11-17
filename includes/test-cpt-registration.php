<?php
/**
 * Tests for CPT registration.
 *
 * @package WPFAevent
 */

/**
 * Test CPT registration.
 *
 * @group data-model
 */
class WPFA_Event_Test_CPT_Registration extends WP_UnitTestCase {

	/**
	 * Test that the event CPT is registered.
	 */
	public function test_event_cpt_exists() {
		$this->assertTrue( post_type_exists( 'wpfaevent_event' ) );
	}

	/**
	 * Test that the speaker CPT is registered.
	 */
	public function test_speaker_cpt_exists() {
		$this->assertTrue( post_type_exists( 'wpfaevent_speaker' ) );
	}
}