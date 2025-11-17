<?php
/**
 * Tests for taxonomy registration.
 *
 * @package WPFAevent
 */

/**
 * Test taxonomy registration.
 *
 * @group data-model
 */
class WPFA_Event_Test_Tax_Registration extends WP_UnitTestCase {

	/**
	 * Test that the track taxonomy is registered.
 */
	public function test_track_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'wpfaevent_track' ) );
	}

	/**
	 * Test that the tag taxonomy is registered.
 */
	public function test_tag_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'wpfaevent_tag' ) );
	}
}