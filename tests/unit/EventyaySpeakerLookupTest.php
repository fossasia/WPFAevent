<?php
/**
 * Unit tests for Eventyay speaker ID reconciliation lookups.
 *
 * @package Wpfaevent
 */

/**
 * Eventyay speaker lookup tests.
 */
class EventyaySpeakerLookupTest extends WP_UnitTestCase {

	/**
	 * Create a speaker post with an optional Eventyay speaker ID.
	 *
	 * @param string $title               Speaker title.
	 * @param string $eventyay_speaker_id Eventyay speaker ID.
	 * @return int
	 */
	private function create_speaker( $title, $eventyay_speaker_id ) {
		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => $title,
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		if ( '' !== $eventyay_speaker_id ) {
			update_post_meta( $speaker_id, '_wpfa_eventyay_speaker_id', $eventyay_speaker_id );
		}

		return $speaker_id;
	}

	/**
	 * Current/plain speaker IDs should match exactly.
	 */
	public function test_finds_exact_current_eventyay_speaker_id() {
		$speaker_id = $this->create_speaker( 'Roneel Kumar', '9AEJD7' );

		$this->assertSame(
			array( $speaker_id ),
			Wpfaevent_Event_Speaker_Relation_Manager::find_eventyay_speaker_post_ids( '9AEJD7', 'Roneel Kumar' )
		);
	}

	/**
	 * Legacy-prefixed Eventyay speaker IDs should match exactly.
	 */
	public function test_finds_exact_legacy_eventyay_speaker_id() {
		$speaker_id = $this->create_speaker( 'Roneel Kumar', 'fossasia:fosdem:9AEJD7' );

		$this->assertSame(
			array( $speaker_id ),
			Wpfaevent_Event_Speaker_Relation_Manager::find_eventyay_speaker_post_ids( 'fossasia:fosdem:9AEJD7', 'Roneel Kumar' )
		);
	}

	/**
	 * Plain and legacy IDs for the same speaker should reconcile to both posts.
	 */
	public function test_finds_plain_and_legacy_ids_for_the_same_speaker() {
		$current_id = $this->create_speaker( 'Roneel Kumar', '9AEJD7' );
		$legacy_id  = $this->create_speaker( 'Roneel Kumar', 'fossasia:fosdem:9AEJD7' );

		$this->assertEqualsCanonicalizing(
			array( $current_id, $legacy_id ),
			Wpfaevent_Event_Speaker_Relation_Manager::find_eventyay_speaker_post_ids( '9AEJD7', 'Roneel Kumar' )
		);
	}

	/**
	 * Similar IDs from different speakers must not be matched accidentally.
	 */
	public function test_does_not_match_similar_eventyay_speaker_ids() {
		$expected_id = $this->create_speaker( 'Roneel Kumar', '9AEJD7' );
		$this->create_speaker( 'Peter Membrey', '9AEJD78' );
		$this->create_speaker( 'Mitch Altman', 'fossasia:fosdem:9AEJD78' );

		$this->assertSame(
			array( $expected_id ),
			Wpfaevent_Event_Speaker_Relation_Manager::find_eventyay_speaker_post_ids( '9AEJD7', 'Roneel Kumar' )
		);
	}

	/**
	 * Name verification should prevent duplicate reconciliation from crossing speakers.
	 */
	public function test_name_verification_limits_legacy_reconciliation() {
		$this->create_speaker( 'Peter Membrey', 'fossasia:fosdem:9AEJD7' );

		$this->assertSame(
			array(),
			Wpfaevent_Event_Speaker_Relation_Manager::find_eventyay_speaker_post_ids( '9AEJD7', 'Roneel Kumar' )
		);
	}
}
