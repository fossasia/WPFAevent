<?php
/**
 * Tests for meta registration.
 *
 * @package WPFAevent
 */

/**
 * Test meta registration.
 *
 * @group data-model
 */
class WPFA_Event_Test_Meta_Registration extends WP_UnitTestCase {

	/**
	 * Test that the speaker bio meta key is registered.
	 */
	public function test_speaker_bio_meta_exists() {
		$this->assertTrue(
			registered_meta_key_exists( 'post', 'bio', 'wpfaevent_speaker' )
		);
	}

	/**
	 * Test that the speaker position meta key is registered.
	 */
	public function test_speaker_position_meta_exists() {
		$this->assertTrue(
			registered_meta_key_exists( 'post', 'position', 'wpfaevent_speaker' )
		);
	}

	/**
	 * Test that the event talk_title meta key is registered.
	 */
	public function test_event_talk_title_meta_exists() {
		$this->assertTrue(
			registered_meta_key_exists( 'post', 'talk_title', 'wpfaevent_event' )
		);
	}
}