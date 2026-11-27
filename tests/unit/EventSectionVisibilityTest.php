<?php
/**
 * Unit tests for empty event section visibility.
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
	 * Overview precedes Tickets unless the saved custom menu puts Tickets first.
	 *
	 * @dataProvider event_section_order_provider
	 * @param array $custom_navigation Saved custom menu items.
	 * @param bool  $tickets_first     Whether Tickets should precede Overview.
	 */
	public function test_event_page_renders_overview_and_tickets_in_order( $custom_navigation, $tickets_first ) {
		wp_update_post(
			array(
				'ID'           => $this->event_id,
				'post_content' => 'Event overview content.',
			)
		);
		update_post_meta( $this->event_id, 'wpfa_event_ticket_widget_url', 'https://eventyay.com/e/test-event' );
		if ( ! empty( $custom_navigation ) ) {
			update_post_meta( $this->event_id, 'wpfa_event_custom_navigation', Wpfaevent_Meta_Event::sanitize_custom_navigation( $custom_navigation ) );
		}

		$output          = $this->render_event_template();
		$overview_offset = strpos( $output, '<section id="about"' );
		$tickets_offset  = strpos( $output, '<section id="tickets"' );

		$this->assertNotFalse( $overview_offset );
		$this->assertNotFalse( $tickets_offset );
		$this->assertSame( $tickets_first, $tickets_offset < $overview_offset );
	}

	/**
	 * Cases for the default and saved custom section order.
	 *
	 * @return array Test cases.
	 */
	public function event_section_order_provider() {
		$overview = array(
			'text' => 'Overview',
			'href' => '#about',
		);
		$tickets  = array(
			'text' => 'Tickets',
			'href' => '#tickets',
		);
		$dropdown = array(
			'text'  => 'Event details',
			'type'  => 'dropdown',
			'items' => array( $tickets, $overview ),
		);

		return array(
			'default'                => array( array(), false ),
			'saved overview first'   => array( array( $overview, $tickets ), false ),
			'saved tickets first'    => array( array( $tickets, $overview ), true ),
			'saved dropdown order'   => array( array( $dropdown ), true ),
			'no saved overview link' => array( array( $tickets ), false ),
		);
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
	 * A dashboard speaker name that sanitizes away is not a speaker.
	 */
	public function test_event_page_hides_speakers_section_when_dashboard_speaker_names_are_blank() {
		$this->write_dashboard_json(
			'speakers',
			array(
				array( 'name' => '   ' ),
			)
		);

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-event-speakers-title"', $output );
	}

	/**
	 * An unpublished linked speaker must not shadow renderable dashboard speakers.
	 */
	public function test_event_page_renders_dashboard_speakers_despite_unpublished_linked_speaker() {
		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Draft Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'draft',
			)
		);

		update_post_meta( $this->event_id, 'wpfa_event_speakers', array( $speaker_id ) );

		$this->write_dashboard_json(
			'speakers',
			array(
				array( 'name' => 'Dashboard Speaker' ),
			)
		);

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-event-speakers-title"', $output );
		$this->assertStringContainsString( 'Dashboard Speaker', $output );
	}

	/**
	 * Published speakers belonging to no event must not leak into an unrelated event.
	 */
	public function test_event_page_ignores_speakers_that_are_not_linked_to_the_event() {
		$this->factory->post->create(
			array(
				'post_title'  => 'Unrelated Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-event-speakers-title"', $output );
		$this->assertStringNotContainsString( 'Unrelated Speaker', $output );
	}

	/**
	 * Administrators get the speaker edit modal their card edit buttons open.
	 */
	public function test_event_page_renders_speaker_modal_for_administrators() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-speaker-modal"', $output );
	}

	/**
	 * Visitors never receive the speaker edit modal.
	 */
	public function test_event_page_omits_speaker_modal_for_visitors() {
		wp_set_current_user( 0 );

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-speaker-modal"', $output );
	}

	/**
	 * Capability sets for logged-in users without full dashboard access.
	 *
	 * @return array<string, array<int, array<int, string>>>
	 */
	public function partial_dashboard_capabilities_provider() {
		return array(
			'no plugin capabilities' => array( array() ),
			'edit_events only'       => array( array( 'edit_events' ) ),
			'edit_speakers only'     => array( array( 'edit_speakers' ) ),
		);
	}

	/**
	 * Logged-in users need both edit_events and edit_speakers to receive the speaker edit modal.
	 *
	 * @dataProvider partial_dashboard_capabilities_provider
	 *
	 * @param array<int, string> $capabilities Capabilities granted to the subscriber.
	 */
	public function test_event_page_omits_speaker_modal_without_dashboard_capabilities( $capabilities ) {
		$this->set_subscriber_with_capabilities( $capabilities );

		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="wpfa-speaker-modal"', $output );
	}

	/**
	 * Non-administrators with both dashboard capabilities receive the speaker edit modal.
	 */
	public function test_event_page_renders_speaker_modal_for_dashboard_capabilities_without_admin() {
		$this->set_subscriber_with_capabilities( array( 'edit_events', 'edit_speakers' ) );

		$this->assertFalse( current_user_can( 'manage_options' ) );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="wpfa-speaker-modal"', $output );
	}

	/**
	 * Log in a new subscriber that holds only the given extra capabilities.
	 *
	 * @param array<int, string> $capabilities Capabilities to grant.
	 */
	private function set_subscriber_with_capabilities( $capabilities ) {
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		foreach ( $capabilities as $capability ) {
			$user->add_cap( $capability );
		}

		wp_set_current_user( $user->ID );
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
