<?php
/**
 * Unit tests for the speakers page search.
 *
 * @package Wpfaevent
 */

/**
 * Speaker biographies are stored as HTML, so the search must look at their text only.
 */
class SpeakersPageSearchTest extends WP_UnitTestCase {
	/**
	 * Create an event with two published speakers.
	 *
	 * @return void
	 */
	private function create_event_with_speakers() {
		$event_id = self::factory()->post->create(
			array(
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
				'post_title'  => 'Search Event',
				'post_name'   => 'search-event',
			)
		);
		$ids      = array(
			self::factory()->post->create(
				array(
					'post_type'    => 'wpfa_speaker',
					'post_status'  => 'publish',
					'post_title'   => 'Asha Rao',
					'post_content' => '<p><strong>Asha</strong> builds <a href="https://example.org/asha">open tools</a>.</p><ul><li>Maintains two projects</li></ul>',
				)
			),
			self::factory()->post->create(
				array(
					'post_type'    => 'wpfa_speaker',
					'post_status'  => 'publish',
					'post_title'   => 'Liam Chen',
					'post_content' => 'Plain biography about strong networks.',
				)
			),
		);

		update_post_meta( $event_id, 'wpfa_event_speakers', $ids );
	}

	/**
	 * Render the speakers page for a search term.
	 *
	 * @param string $term Search term.
	 * @return string
	 */
	private function render( $term ) {
		$_GET['event'] = 'search-event';
		$_GET['q']     = $term;
		$output        = Wpfaevent_Templates::render_embed( 'speakers' );

		unset( $_GET['event'], $_GET['q'] );

		return $output;
	}

	/**
	 * Count the speaker names shown in the rendered page.
	 *
	 * @param string $output Rendered page.
	 * @return int
	 */
	private function count_shown( $output ) {
		return substr_count( $output, 'Asha Rao' ) + substr_count( $output, 'Liam Chen' );
	}

	/**
	 * Tag and attribute names in the stored HTML are not search hits.
	 */
	public function test_markup_is_not_searchable() {
		$this->create_event_with_speakers();

		$this->assertGreaterThan( 0, $this->count_shown( $this->render( '' ) ), 'Setup must show speakers without a search term.' );

		foreach ( array( 'strong', 'href', 'li', 'ul' ) as $term ) {
			$output = $this->render( $term );

			$this->assertStringNotContainsString( 'Asha Rao', $output, $term );
		}
	}

	/**
	 * The text of the biography is still searchable.
	 */
	public function test_biography_text_is_searchable() {
		$this->create_event_with_speakers();

		$this->assertStringContainsString( 'Asha Rao', $this->render( 'open tools' ) );
		$this->assertStringContainsString( 'Asha Rao', $this->render( 'two projects' ) );
		$this->assertStringContainsString( 'Liam Chen', $this->render( 'strong networks' ) );
		$this->assertStringNotContainsString( 'Asha Rao', $this->render( 'strong networks' ) );
	}
}
