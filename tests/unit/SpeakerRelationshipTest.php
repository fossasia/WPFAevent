<?php
/**
 * Unit tests for speaker and event relationship helpers.
 *
 * @package Wpfaevent
 */

/**
 * Speaker relationship unit tests.
 */
class SpeakerRelationshipTest extends WP_UnitTestCase {

	/**
	 * Reset the current user before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 0 );
	}

	/**
	 * Create a published speaker post with optional meta.
	 *
	 * @param array $meta Speaker meta values.
	 * @return int
	 */
	private function create_speaker( $meta = array() ) {
		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => isset( $meta['post_title'] ) ? $meta['post_title'] : 'Test Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => isset( $meta['post_status'] ) ? $meta['post_status'] : 'publish',
			)
		);

		unset( $meta['post_title'], $meta['post_status'] );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $speaker_id, $key, $value );
		}

		return $speaker_id;
	}

	/**
	 * Create an event post with optional meta.
	 *
	 * @param string $post_status Event status.
	 * @param array  $meta        Event meta values.
	 * @return int
	 */
	private function create_event( $post_status = 'publish', $meta = array() ) {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test Event',
				'post_type'   => 'wpfa_event',
				'post_status' => $post_status,
			)
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $event_id, $key, $value );
		}

		return $event_id;
	}

	/**
	 * Speaker profiles should merge speaker-side links with published event-side references.
	 */
	public function test_get_events_linked_to_speaker_merges_published_relationships() {
		$speaker_id       = $this->create_speaker();
		$published_event  = $this->create_event( 'publish' );
		$referenced_event = $this->create_event( 'publish', array( 'wpfa_event_speakers' => array( $speaker_id ) ) );
		$this->create_event( 'draft', array( 'wpfa_event_speakers' => array( $speaker_id ) ) );

		update_post_meta( $speaker_id, 'wpfa_speaker_events', array( $published_event ) );

		$this->assertSame(
			array( $published_event, $referenced_event ),
			Wpfaevent_Meta_Speaker::get_events_linked_to_speaker( $speaker_id, 'publish' )
		);
	}

	/**
	 * Public add helper should keep the speaker capability guard by default.
	 */
	public function test_add_event_to_speaker_requires_capability_by_default() {
		$speaker_id = $this->create_speaker();
		$event_id   = $this->create_event();

		Wpfaevent_Meta_Speaker::add_event_to_speaker( $speaker_id, $event_id );

		$this->assertSame( array(), Wpfaevent_Meta_Speaker::get_speaker_event_ids( $speaker_id ) );
	}

	/**
	 * Internal sync should be able to add reverse links after event edit permission has passed.
	 */
	public function test_add_event_to_speaker_can_bypass_capability_check() {
		$speaker_id = $this->create_speaker();
		$event_id   = $this->create_event();

		Wpfaevent_Meta_Speaker::add_event_to_speaker( $speaker_id, $event_id, false );

		$this->assertSame( array( $event_id ), Wpfaevent_Meta_Speaker::get_speaker_event_ids( $speaker_id ) );
	}

	/**
	 * Event sync should remove stale reverse links and add current ones.
	 */
	public function test_sync_event_speaker_relationships_updates_reverse_links() {
		$existing_speaker = $this->create_speaker( array( 'wpfa_speaker_events' => array() ) );
		$stale_speaker    = $this->create_speaker( array( 'wpfa_speaker_events' => array() ) );
		$new_speaker      = $this->create_speaker();
		$event_id         = $this->create_event();

		update_post_meta( $existing_speaker, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $stale_speaker, 'wpfa_speaker_events', array( $event_id ) );

		Wpfaevent_Meta_Event::sync_event_speaker_relationships( $event_id, array( $existing_speaker ), array( $new_speaker ) );

		$this->assertSame( array(), Wpfaevent_Meta_Speaker::get_speaker_event_ids( $existing_speaker ) );
		$this->assertSame( array(), Wpfaevent_Meta_Speaker::get_speaker_event_ids( $stale_speaker ) );
		$this->assertSame( array( $event_id ), Wpfaevent_Meta_Speaker::get_speaker_event_ids( $new_speaker ) );
	}

	/**
	 * Speaker event meta sanitization should accept scalar ID lists consistently.
	 */
	public function test_sanitize_event_ids_accepts_scalar_lists() {
		$this->assertSame( array( 101, 102 ), Wpfaevent_Meta_Speaker::sanitize_event_ids( '101, 102, invalid, 101' ) );
	}

	/**
	 * Featured speaker resolution should use explicit event meta and dashboard featured flags only.
	 */
	public function test_featured_speaker_resolution_uses_explicit_data_only() {
		$event_id      = $this->create_event();
		$speaker_one   = $this->create_speaker(
			array(
				'post_title'                => 'Roneel Kumar',
				'_wpfa_eventyay_speaker_id' => 'lspk8633',
			)
		);
		$speaker_two   = $this->create_speaker(
			array(
				'post_title'                => 'Peter Membrey',
				'_wpfa_eventyay_speaker_id' => '9AEJD7',
			)
		);
		$speaker_three = $this->create_speaker(
			array(
				'post_title'                => 'Mitch Altman',
				'_wpfa_eventyay_speaker_id' => 'lspk5150',
			)
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $speaker_one, $speaker_two, $speaker_three ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers', array( $speaker_two ) );

		$dashboard_featured = array(
			array(
				'eventyay_speaker_id' => 'lspk5150',
				'featured'            => true,
			),
			array(
				'name'     => 'Roneel Kumar',
				'featured' => false,
			),
		);

		$this->assertSame(
			array( $speaker_two, $speaker_three ),
			Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $event_id, array( $speaker_one, $speaker_two, $speaker_three ), $dashboard_featured )
		);

		$this->assertSame(
			array( $speaker_two, $speaker_three ),
			Wpfaevent_Event_Speaker_Relation_Manager::resolve_event_featured_speaker_ids( $event_id, array( $speaker_one, $speaker_two, $speaker_three ), $dashboard_featured )
		);
	}

	/**
	 * Featured speaker resolution should not auto-select a fallback speaker when no explicit featured data exists.
	 */
	public function test_featured_speaker_resolution_does_not_auto_select_a_fallback() {
		$event_id      = $this->create_event();
		$speaker_one   = $this->create_speaker( array( '_wpfa_eventyay_speaker_id' => 'lspk8633' ) );
		$speaker_two   = $this->create_speaker( array( '_wpfa_eventyay_speaker_id' => '9AEJD7' ) );
		$speaker_three = $this->create_speaker( array( '_wpfa_eventyay_speaker_id' => 'lspk5150' ) );

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $speaker_one, $speaker_two, $speaker_three ) );

		$this->assertSame(
			array(),
			Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $event_id, array( $speaker_one, $speaker_two, $speaker_three ), array() )
		);
	}

	/**
	 * Compact featured speaker cards should omit the abstract while keeping session details.
	 */
	public function test_compact_speaker_cards_hide_the_session_abstract() {
		$speaker_id = $this->create_speaker(
			array(
				'post_title'                 => 'Mitch Altman',
				'wpfa_speaker_position'      => 'Chief Scientist',
				'wpfa_speaker_organization'  => 'Cornfield Electronics',
				'wpfa_speaker_bio'           => 'Compact bio',
				'wpfa_speaker_talk_title'    => 'Open Hardware Keynote',
				'wpfa_speaker_talk_date'     => '2026-03-09',
				'wpfa_speaker_talk_time'     => '10:00',
				'wpfa_speaker_talk_end_time' => '10:45',
				'wpfa_speaker_talk_abstract' => 'This full abstract should be hidden for compact featured cards.',
			)
		);

		$sid                       = $speaker_id;
		$wpfa_speaker_card_variant = 'compact';

		ob_start();
		include dirname( __DIR__, 2 ) . '/public/partials/speakers/speaker-card.php';
		$compact_card_markup = ob_get_clean();

		$this->assertStringNotContainsString( 'wpfa-talk-abstract', $compact_card_markup );
		$this->assertStringContainsString( 'Open Hardware Keynote', $compact_card_markup );
	}
}
