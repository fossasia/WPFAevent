<?php
/**
 * Regression tests for the event featured-speaker grid.
 *
 * @package Wpfaevent
 */

/**
 * Event featured-speaker grid regression tests.
 */
class FeaturedSpeakersGridTest extends WP_UnitTestCase {

	/**
	 * The event template provides an accessible control for both speaker sources.
	 */
	public function test_event_template_renders_featured_speaker_view_all_controls() {
		$source = $this->read_project_file( 'public/templates/single-wpfa-event.php' );

		$this->assertSame( 2, substr_count( $source, 'class="wpfa-event-featured-speakers wpfa-event-featured-speakers--collapsed"' ) );
		$this->assertSame( 2, substr_count( $source, 'class="wpfa-event-featured-speakers-toggle"' ) );
		$this->assertSame( 2, substr_count( $source, 'aria-controls="wpfa-event-featured-speakers-grid"' ) );
		$this->assertStringContainsString( "esc_html_e( 'View All', 'wpfaevent' )", $source );
		$this->assertStringContainsString( "esc_attr_e( 'Show Less', 'wpfaevent' )", $source );
	}

	/**
	 * CSS keeps eight rows at each responsive column count.
	 */
	public function test_featured_speaker_grid_has_eight_row_breakpoints() {
		$source = $this->read_project_file( 'public/css/templates/event-base.css' );

		$this->assertStringContainsString( '@media (min-width: 769px)', $source );
		$this->assertStringContainsString( '@media (min-width: 1025px)', $source );
		$this->assertStringContainsString( '@media (min-width: 1441px)', $source );
		$this->assertStringContainsString( 'grid-template-columns: repeat(2, minmax(0, 1fr));', $source );
		$this->assertStringContainsString( 'grid-template-columns: repeat(3, minmax(0, 1fr));', $source );
		$this->assertStringContainsString( 'grid-template-columns: repeat(4, minmax(0, 1fr));', $source );
		$this->assertStringContainsString( '.wpfa-speaker-card:nth-child(n + 9)', $source );
		$this->assertStringContainsString( '.wpfa-speaker-card:nth-child(n + 17)', $source );
		$this->assertStringContainsString( '.wpfa-speaker-card:nth-child(n + 25)', $source );
		$this->assertStringContainsString( '.wpfa-speaker-card:nth-child(n + 33)', $source );
		$this->assertSame( 4, substr_count( $source, 'display: none !important;' ) );
		$this->assertSame( 3, substr_count( $source, 'display: flex !important;' ) );
	}

	/**
	 * JavaScript expands the complete grid and recalculates overflow on resize.
	 */
	public function test_featured_speaker_grid_script_handles_toggle_and_resize() {
		$source = $this->read_project_file( 'public/js/wpfaevent-public.js' );

		$this->assertStringContainsString( "'wpfa-event-featured-speakers--collapsed'", $source );
		$this->assertStringContainsString( "attr('aria-expanded', isExpanded ? 'true' : 'false')", $source );
		$this->assertStringContainsString( "$(window).on('resize', refreshGrid);", $source );
	}

	/**
	 * Speaker filtering must not override the responsive grid display rules.
	 */
	public function test_speaker_filter_restores_stylesheet_controlled_display() {
		$source = $this->read_project_file( 'public/js/wpfaevent-speakers.js' );

		$this->assertStringContainsString( "speaker.element.style.removeProperty('display');", $source );
		$this->assertStringNotContainsString( "speaker.element.style.display = 'block';", $source );
	}

	/**
	 * Changed speaker assets must use cache-busting file modification versions.
	 */
	public function test_featured_speaker_assets_use_file_modification_versions() {
		$public_source   = $this->read_project_file( 'public/class-wpfaevent-public.php' );
		$template_source = $this->read_project_file( 'includes/class-wpfaevent-templates.php' );

		$this->assertStringContainsString( '$event_base_version', $public_source );
		$this->assertStringContainsString( '$speakers_script_version', $public_source );
		$this->assertStringContainsString( '$event_base_version', $template_source );
	}

	/**
	 * Clean up environment after each test.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wpfaevent_template_embed'] );

		parent::tearDown();
	}

	/**
	 * Main event page renders only the Featured Speakers section when featured speakers exist.
	 */
	public function test_main_event_page_renders_only_featured_speakers_when_present() {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Event with Featured Speakers',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		$featured_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Featured Speaker One',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		$regular_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Regular Speaker One',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $featured_speaker_id, $regular_speaker_id ) );
		update_post_meta( $featured_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $regular_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers', array( $featured_speaker_id ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers_manual', 'yes' );

		$output = $this->render_event_template( $event_id );

		$this->assertStringContainsString( 'wpfa-event-featured-speakers', $output );
		$this->assertStringContainsString( 'Featured Speaker One', $output );
		$this->assertStringNotContainsString( 'wpfa-event-regular-speakers', $output );
		$this->assertStringNotContainsString( 'Regular Speaker One', $output );
	}

	/**
	 * Main event page renders regular speakers when no featured speakers exist.
	 */
	public function test_main_event_page_renders_regular_speakers_when_no_featured_exist() {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Event without Featured Speakers',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		$speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Regular Speaker Only',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $speaker_id ) );
		update_post_meta( $speaker_id, 'wpfa_speaker_events', array( $event_id ) );

		$output = $this->render_event_template( $event_id );

		$this->assertStringNotContainsString( 'wpfa-event-featured-speakers', $output );
		$this->assertStringContainsString( 'wpfa-event-regular-speakers', $output );
		$this->assertStringContainsString( 'Regular Speaker Only', $output );
	}

	/**
	 * Stale or unpublished featured speaker IDs do not prevent regular speakers from displaying.
	 */
	public function test_invalid_featured_speakers_do_not_block_regular_speakers() {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'Event with Stale Featured Speakers',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		$draft_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Draft Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'draft',
			)
		);

		$regular_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Active Regular Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $draft_speaker_id, $regular_speaker_id ) );
		update_post_meta( $draft_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $regular_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers', array( $draft_speaker_id, 999999 ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers_manual', 'yes' );

		$output = $this->render_event_template( $event_id );

		$this->assertStringNotContainsString( 'wpfa-event-featured-speakers', $output );
		$this->assertStringContainsString( 'wpfa-event-regular-speakers', $output );
		$this->assertStringContainsString( 'Active Regular Speaker', $output );
		$this->assertStringNotContainsString( 'Draft Speaker', $output );
	}

	/**
	 * All Speakers page renders all speakers in a single unified grid without separate groups.
	 */
	public function test_all_speakers_page_renders_unified_list_without_separate_featured_section() {
		$event_id = $this->factory->post->create(
			array(
				'post_title'  => 'All Speakers Test Event',
				'post_name'   => 'all-speakers-event',
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);

		$featured_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Alpha Featured Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		$regular_speaker_id = $this->factory->post->create(
			array(
				'post_title'  => 'Beta Regular Speaker',
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', array( $featured_speaker_id, $regular_speaker_id ) );
		update_post_meta( $featured_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $regular_speaker_id, 'wpfa_speaker_events', array( $event_id ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers', array( $featured_speaker_id ) );
		update_post_meta( $event_id, 'wpfa_event_featured_speakers_manual', 'yes' );

		$output = $this->render_speakers_template( 'all-speakers-event' );

		$this->assertStringContainsString( 'id="wpfa-speakers-grid"', $output );
		$this->assertStringContainsString( 'Alpha Featured Speaker', $output );
		$this->assertStringContainsString( 'Beta Regular Speaker', $output );
		$this->assertStringNotContainsString( 'wpfa-featured-speaker-group', $output );
		$this->assertStringNotContainsString( 'wpfa-regular-speaker-group', $output );
		$this->assertStringNotContainsString( 'id="wpfa-featured-speakers-title"', $output );
	}

	/**
	 * Render the single event template for a given event ID.
	 *
	 * @param int $event_id Event ID.
	 * @return string Rendered HTML.
	 */
	private function render_event_template( $event_id ) {
		$url = get_permalink( $event_id );
		$this->go_to( $url );

		$this->assertSame( $event_id, get_queried_object_id() );

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php';

		return (string) ob_get_clean();
	}

	/**
	 * Render the speakers archive template for a given event slug.
	 *
	 * @param string $event_slug Optional event slug.
	 * @return string Rendered HTML.
	 */
	private function render_speakers_template( $event_slug = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Unit test simulates query args.
		$old_get = $_GET;
		$_GET    = array();

		if ( '' !== $event_slug ) {
			$_GET['event'] = $event_slug;
		}

		$GLOBALS['wpfaevent_template_embed'] = true;

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/page-speakers.php';
		$output = (string) ob_get_clean();

		$_GET = $old_get;
		unset( $GLOBALS['wpfaevent_template_embed'] );

		return $output;
	}

	/**
	 * Read a repository file fixture.
	 *
	 * @param string $relative_path File path relative to the plugin root.
	 * @return string
	 */
	private function read_project_file( $relative_path ) {
		$path   = dirname( __DIR__, 2 ) . '/' . $relative_path;
		$source = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $source );

		return $source;
	}
}
