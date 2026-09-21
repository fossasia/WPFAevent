<?php
/**
 * Unit tests for the repeatable rows in the event meta boxes.
 *
 * @package Wpfaevent
 */

/**
 * Rows added with "Add Session", "Add Sponsor" and "Add Exhibitor" must each
 * get their own array index, otherwise PHP keeps only the last one on save.
 */
class EventMetaBoxRepeaterTest extends WP_UnitTestCase {

	/**
	 * The meta box handler under test.
	 *
	 * @var Wpfaevent_Admin_Event_Metabox
	 */
	private $metabox;

	/**
	 * Test event post.
	 *
	 * @var WP_Post
	 */
	private $event;

	/**
	 * Create the event fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->metabox = new Wpfaevent_Admin_Event_Metabox();
		$this->event   = $this->factory->post->create_and_get( array( 'post_type' => 'wpfa_event' ) );
	}

	/**
	 * Repeater meta boxes and the JavaScript index each new row must carry.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function repeater_provider() {
		return array(
			'sessions'   => array( 'render_event_schedule_meta_box', 'wpfa_schedule_sessions', 'sessionIndex' ),
			'sponsors'   => array( 'render_event_sponsors_meta_box', 'wpfa_sponsors', 'sponsorIndex' ),
			'exhibitors' => array( 'render_event_exhibitors_meta_box', 'wpfa_exhibitors', 'exhibitorIndex' ),
			'navigation' => array( 'render_event_navigation_meta_box', 'wpfa_custom_nav_items', 'navIndex' ),
		);
	}

	/**
	 * The row template must interpolate the index, not emit the placeholder literally.
	 *
	 * @dataProvider repeater_provider
	 *
	 * @param string $method     Render method on the meta box handler.
	 * @param string $field      Form field name prefix for the repeater.
	 * @param string $index_name JavaScript variable holding the next row index.
	 */
	public function test_new_row_template_interpolates_the_index( $method, $field, $index_name ) {
		ob_start();
		$this->metabox->$method( $this->event );
		$output = ob_get_clean();

		$this->assertStringContainsString( $field . '[${' . $index_name . '}]', $output );
		$this->assertStringNotContainsString( $field . '[\${' . $index_name . '}]', $output );
	}
}
