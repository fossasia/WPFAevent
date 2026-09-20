<?php
/**
 * Unit tests for Event Additional Information fields (Issue #238).
 *
 * @package Wpfaevent
 */

/**
 * Additional Information test class.
 */
class EventAdditionalInformationTest extends WP_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Set up test event and admin user.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Test Event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Clean up superglobals.
	 */
	public function tearDown(): void {
		$_POST = array();
		$_GET  = array();

		parent::tearDown();
	}

	/**
	 * Saving the event persists venue, transportation, and hotel information.
	 */
	public function test_event_editor_saves_additional_information_fields() {
		$_POST = array(
			'wpfa_event_meta_nonce'                 => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_venue_information'          => '<p>Main Hall, Level 2</p>',
			'wpfa_event_transportation_information' => '<p>Take MRT to City Hall station.</p>',
			'wpfa_event_hotel_information'          => '<p>Recommended Hotel: Grand Plaza.</p>',
		);

		( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );

		$this->assertSame( '<p>Main Hall, Level 2</p>', get_post_meta( $this->event_id, 'wpfa_event_venue_information', true ) );
		$this->assertSame( '<p>Take MRT to City Hall station.</p>', get_post_meta( $this->event_id, 'wpfa_event_transportation_information', true ) );
		$this->assertSame( '<p>Recommended Hotel: Grand Plaza.</p>', get_post_meta( $this->event_id, 'wpfa_event_hotel_information', true ) );
	}

	/**
	 * Non-string POST values are rejected and not saved.
	 */
	public function test_event_editor_rejects_non_string_additional_information() {
		$_POST = array(
			'wpfa_event_meta_nonce'        => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_venue_information' => array( 'invalid' => 'array' ),
		);

		( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );

		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_venue_information', true ) );
	}

	/**
	 * Emptying an additional information field removes the stored post meta.
	 */
	public function test_event_editor_removes_emptied_additional_information_fields() {
		update_post_meta( $this->event_id, 'wpfa_event_venue_information', '<p>Old venue</p>' );
		update_post_meta( $this->event_id, 'wpfa_event_transportation_information', '<p>Old transportation</p>' );
		update_post_meta( $this->event_id, 'wpfa_event_hotel_information', '<p>Old hotel</p>' );

		$_POST = array(
			'wpfa_event_meta_nonce'                 => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_venue_information'          => '',
			'wpfa_event_transportation_information' => '',
			'wpfa_event_hotel_information'          => '',
		);

		( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );

		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_venue_information', true ) );
		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_transportation_information', true ) );
		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_hotel_information', true ) );
	}

	/**
	 * Additional information is shown on the event page and navigation when venue is populated.
	 */
	public function test_shows_additional_information_when_only_venue_is_populated() {
		update_post_meta( $this->event_id, 'wpfa_event_venue_information', '<p>Venue only</p>' );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="additional-information"', $output );
		$this->assertStringContainsString( 'Venue only', $output );

		$context = array( 'has_additional_information' => true );
		$this->assertTrue( Wpfaevent_Event_Navigation_Helper::section_is_visible( '#additional-information', $context ) );
	}

	/**
	 * Additional information is shown on the event page and navigation when transportation is populated.
	 */
	public function test_shows_additional_information_when_only_transportation_is_populated() {
		update_post_meta( $this->event_id, 'wpfa_event_transportation_information', '<p>Transportation only</p>' );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="additional-information"', $output );
		$this->assertStringContainsString( 'Transportation only', $output );

		$context = array( 'has_additional_information' => true );
		$this->assertTrue( Wpfaevent_Event_Navigation_Helper::section_is_visible( '#additional-information', $context ) );
	}

	/**
	 * Additional information is shown on the event page and navigation when hotel is populated.
	 */
	public function test_shows_additional_information_when_only_hotel_is_populated() {
		update_post_meta( $this->event_id, 'wpfa_event_hotel_information', '<p>Hotel only</p>' );

		$output = $this->render_event_template();

		$this->assertStringContainsString( 'id="additional-information"', $output );
		$this->assertStringContainsString( 'Hotel only', $output );

		$context = array( 'has_additional_information' => true );
		$this->assertTrue( Wpfaevent_Event_Navigation_Helper::section_is_visible( '#additional-information', $context ) );
	}

	/**
	 * When all three fields are empty, the additional information section and navigation item are hidden.
	 */
	public function test_hides_additional_information_and_nav_when_all_fields_are_empty() {
		$output = $this->render_event_template();

		$this->assertStringNotContainsString( 'id="additional-information"', $output );

		$context = array( 'has_additional_information' => false );
		$this->assertFalse( Wpfaevent_Event_Navigation_Helper::section_is_visible( '#additional-information', $context ) );

		$nav_items = Wpfaevent_Event_Navigation_Helper::build_nav_items( $context );
		$hrefs     = array_column( $nav_items, 'href' );
		$this->assertNotContains( '#additional-information', $hrefs );
	}

	/**
	 * Populating only the venue field renders the venue section and omits transportation and hotel.
	 */
	public function test_renders_venue_section_independently() {
		update_post_meta( $this->event_id, 'wpfa_event_venue_information', '<p>Convention Center</p>' );

		$event_output = $this->render_event_template();
		$this->assertStringContainsString( '<h3>Venue</h3>', $event_output );
		$this->assertStringContainsString( 'Convention Center', $event_output );
		$this->assertStringNotContainsString( '<h3>Transportation</h3>', $event_output );
		$this->assertStringNotContainsString( '<h3>Hotel & Accommodation</h3>', $event_output );

		$page_output = $this->render_additional_information_template();
		$this->assertStringContainsString( '<h3>Venue</h3>', $page_output );
		$this->assertStringContainsString( 'Convention Center', $page_output );
		$this->assertStringNotContainsString( '<h3>Transportation</h3>', $page_output );
		$this->assertStringNotContainsString( '<h3>Hotel & Accommodation</h3>', $page_output );
	}

	/**
	 * Populating only transportation renders the transportation section and omits venue and hotel.
	 */
	public function test_renders_transportation_section_independently() {
		update_post_meta( $this->event_id, 'wpfa_event_transportation_information', '<p>Shuttle bus from airport</p>' );

		$event_output = $this->render_event_template();
		$this->assertStringContainsString( '<h3>Transportation</h3>', $event_output );
		$this->assertStringContainsString( 'Shuttle bus from airport', $event_output );
		$this->assertStringNotContainsString( '<h3>Venue</h3>', $event_output );
		$this->assertStringNotContainsString( '<h3>Hotel & Accommodation</h3>', $event_output );

		$page_output = $this->render_additional_information_template();
		$this->assertStringContainsString( '<h3>Transportation</h3>', $page_output );
		$this->assertStringContainsString( 'Shuttle bus from airport', $page_output );
		$this->assertStringNotContainsString( '<h3>Venue</h3>', $page_output );
		$this->assertStringNotContainsString( '<h3>Hotel & Accommodation</h3>', $page_output );
	}

	/**
	 * Populating only hotel renders the hotel section and omits venue and transportation.
	 */
	public function test_renders_hotel_section_independently() {
		update_post_meta( $this->event_id, 'wpfa_event_hotel_information', '<p>Discounted rooms at Marina Bay</p>' );

		$event_output = $this->render_event_template();
		$this->assertStringContainsString( '<h3>Hotel & Accommodation</h3>', $event_output );
		$this->assertStringContainsString( 'Discounted rooms at Marina Bay', $event_output );
		$this->assertStringNotContainsString( '<h3>Venue</h3>', $event_output );
		$this->assertStringNotContainsString( '<h3>Transportation</h3>', $event_output );

		$page_output = $this->render_additional_information_template();
		$this->assertStringContainsString( '<h3>Hotel & Accommodation</h3>', $page_output );
		$this->assertStringContainsString( 'Discounted rooms at Marina Bay', $page_output );
		$this->assertStringNotContainsString( '<h3>Venue</h3>', $page_output );
		$this->assertStringNotContainsString( '<h3>Transportation</h3>', $page_output );
	}

	/**
	 * Render the single event template for the event fixture.
	 *
	 * @return string Rendered markup.
	 */
	private function render_event_template() {
		$url = get_permalink( $this->event_id );
		$this->go_to( $url );

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php';

		return (string) ob_get_clean();
	}

	/**
	 * Render the additional information page template for the event fixture.
	 *
	 * @return string Rendered markup.
	 */
	private function render_additional_information_template() {
		$_GET['event'] = get_post_field( 'post_name', $this->event_id );

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/page-additional-information.php';

		return (string) ob_get_clean();
	}
}
