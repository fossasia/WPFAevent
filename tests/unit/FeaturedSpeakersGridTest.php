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
