<?php
/**
 * Unit tests for empty event section visibility (Issue #273).
 *
 * @package Wpfaevent
 */

/**
 * Event section visibility test class.
 */
class EventSectionVisibilityTest extends WP_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Create the event fixture.
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
	}

	/**
	 * Remove dashboard fixture data created by a test.
	 */
	public function tearDown(): void {
		$store = new Wpfaevent_Eventyay_Dashboard_Store();

		foreach ( array( 'speakers', 'schedule' ) as $dataset ) {
			$path = $store->get_dashboard_json_path( $dataset . '-' . $this->event_id . '.json' );

			if ( $path && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}

		parent::tearDown();
	}

	/**
	 * An event without speakers or schedule renders neither section heading.
	 */
	public function test_event_page_hides_speaker_and_schedule_sections_without_data() {
		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-event-speakers-title"', $output );
		$this->assertStringNotContainsString( 'id="wpfa-event-schedule-title"', $output );
	}

	/**
	 * Linked speaker posts bring the speakers section back.
	 */
	public function test_event_page_renders_speakers_section_when_speakers_are_linked() {
		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Speaker 1',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $this->event_id, 'wpfa_event_speakers', array( $speaker_id ) );
		update_post_meta( $speaker_id, 'wpfa_speaker_events', array( $this->event_id ) );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-event-speakers-title"', $output );
	}

	/**
	 * Imported dashboard speakers bring the speakers section back.
	 */
	public function test_event_page_renders_speakers_section_for_dashboard_speakers() {
		$this->write_dashboard_json(
			'speakers',
			array(
				array(
					'name'  => 'Dashboard Speaker',
					'title' => 'Engineer',
				),
			)
		);

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-event-speakers-title"', $output );
	}

	/**
	 * An imported schedule brings the schedule section back.
	 */
	public function test_event_page_renders_schedule_section_when_schedule_is_imported() {
		$this->write_schedule_fixture();

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-event-schedule-title"', $output );
	}

	/**
	 * A schedule filter that excludes every session keeps the section and explains why.
	 */
	public function test_event_page_keeps_schedule_section_when_filters_exclude_every_session() {
		$this->write_schedule_fixture();

		$output = $this->render_event_template( array( 'day' => 'no-such-day' ) );

		$this->assertStringContainsString( 'id="wpfa-event-schedule-title"', $output );
		$this->assertStringContainsString( 'No sessions match the selected filters.', $output );
	}

	/**
	 * Linked speakers that are no longer published render no cards, so the section stays hidden.
	 */
	public function test_event_page_hides_speakers_section_when_linked_speakers_are_unpublished() {
		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Draft Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'draft',
			)
		);

		update_post_meta( $this->event_id, 'wpfa_event_speakers', array( $speaker_id ) );

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-event-speakers-title"', $output );
	}

	/**
	 * Dashboard speaker rows without a name render no cards, so the section stays hidden.
	 */
	public function test_event_page_hides_speakers_section_when_dashboard_speakers_have_no_name() {
		$this->write_dashboard_json(
			'speakers',
			array(
				array( 'position' => 'Engineer' ),
			)
		);

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-event-speakers-title"', $output );
	}

	/**
	 * Store a single-session dashboard schedule for the event fixture.
	 */
	private function write_schedule_fixture() {
		$this->write_dashboard_json(
			'schedule',
			array(
				'data'     => array(
					array( 'Date', 'Time', 'Session', 'Speakers', 'Track', 'Room' ),
					array( '2026-03-08', '09:00 - 10:00', 'Opening Keynote', 'Speaker 1', 'Main', 'Hall A' ),
				),
				'sessions' => array(
					array(
						'starts_at' => '2026-03-08T09:00:00+00:00',
						'ends_at'   => '2026-03-08T10:00:00+00:00',
					),
				),
			)
		);
	}

	/**
	 * Store dashboard rows for the event fixture.
	 *
	 * @param string $dataset Dashboard dataset name.
	 * @param array  $data    Dashboard payload.
	 */
	private function write_dashboard_json( $dataset, $data ) {
		$store  = new Wpfaevent_Eventyay_Dashboard_Store();
		$result = $store->write_dashboard_json_file( $dataset . '-' . $this->event_id . '.json', $data );

		$this->assertTrue( $result );
	}

	/**
	 * Render the single event template for the event fixture.
	 *
	 * @param array<string, string> $query_args Optional query string arguments.
	 * @return string Rendered markup.
	 */
	private function render_event_template( $query_args = array() ) {
		$url = get_permalink( $this->event_id );

		if ( ! empty( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		$this->go_to( $url );

		$this->assertSame( $this->event_id, get_queried_object_id() );

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php';

		return (string) ob_get_clean();
	}
}
