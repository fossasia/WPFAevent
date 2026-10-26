<?php
/**
 * Unit tests for event descriptions stored in post_content.
 *
 * @package Wpfaevent
 */

/**
 * Verify that imports, editors and the event page all use post_content for the description.
 */
class EventDescriptionTest extends WP_UnitTestCase {

	/**
	 * Importer settings shared by the import tests.
	 *
	 * @var array<string, string>
	 */
	private $settings = array(
		'base_url'       => 'https://eventyay.example',
		'organizer_slug' => 'fossasia',
		'post_status'    => 'draft',
	);

	/**
	 * Create an event the way imports stored it before, with the description in both fields.
	 *
	 * @param string $slug        Eventyay event slug.
	 * @param string $description Description saved in post_content and post_excerpt.
	 * @return int Event post ID.
	 */
	private function create_legacy_import( $slug, $description ) {
		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_name'    => 'fossasia-' . $slug,
				'post_content' => $description,
				'post_excerpt' => $description,
			)
		);
		update_post_meta( $event_id, '_wpfa_eventyay_organizer_slug', 'fossasia' );
		update_post_meta( $event_id, '_wpfa_eventyay_event_slug', $slug );

		return $event_id;
	}

	/**
	 * The description is post_content, with the excerpt only as a fallback for older modal events.
	 */
	public function test_get_prefers_post_content() {
		$imported = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => '<p>Edited in the WordPress editor.</p>',
				'post_excerpt' => '<p>Imported earlier.</p>',
			)
		);
		$modal    = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => '',
				'post_excerpt' => 'Created in the Add Event modal.',
			)
		);

		$this->assertSame( '<p>Edited in the WordPress editor.</p>', Wpfaevent_Event_Description::get( $imported ) );
		$this->assertSame( 'Created in the Add Event modal.', Wpfaevent_Event_Description::get( $modal ) );
		$this->assertSame( '', Wpfaevent_Event_Description::get( 0 ) );
	}

	/**
	 * Copies of the description are recognized by their text, not their markup.
	 */
	public function test_is_description_copy() {
		$this->assertTrue( Wpfaevent_Event_Description::is_description_copy( '<p>Same text.</p>', '<p>Same text.</p>' ) );
		$this->assertTrue( Wpfaevent_Event_Description::is_description_copy( "<p>One.</p>\n<p>Two&nbsp;words.</p>", "One.\r\n\r\nTwo words." ) );
		$this->assertTrue( Wpfaevent_Event_Description::is_description_copy( 'Only stored here.', '' ) );
		$this->assertFalse( Wpfaevent_Event_Description::is_description_copy( 'A short summary.', '<p>The full description.</p>' ) );
		$this->assertFalse( Wpfaevent_Event_Description::is_description_copy( '', '<p>The full description.</p>' ) );
	}

	/**
	 * New imports leave the excerpt empty.
	 */
	public function test_import_saves_description_to_post_content_only() {
		$result = ( new Wpfaevent_Event_Repository() )->upsert_eventyay_event_post(
			array(
				'slug'        => 'content-only-event',
				'name'        => 'Content Only Event',
				'description' => '<p>Imported description.</p>',
			),
			$this->settings
		);

		$this->assertIsArray( $result );
		$this->assertSame( '<p>Imported description.</p>', get_post_field( 'post_content', $result['id'] ) );
		$this->assertSame( '', get_post_field( 'post_excerpt', $result['id'] ) );
	}

	/**
	 * Re-importing an older event clears the copied excerpt; a written summary is kept.
	 */
	public function test_reimport_clears_copied_excerpt_and_keeps_summary() {
		$copied  = $this->create_legacy_import( 'copied-excerpt-event', '<p>Old description.</p>' );
		$summary = $this->create_legacy_import( 'summary-excerpt-event', '<p>Old description.</p>' );
		wp_update_post(
			array(
				'ID'           => $summary,
				'post_excerpt' => 'A summary written by an admin.',
			)
		);

		$repository = new Wpfaevent_Event_Repository();
		foreach ( array( 'copied-excerpt-event', 'summary-excerpt-event' ) as $slug ) {
			$repository->upsert_eventyay_event_post(
				array(
					'slug'        => $slug,
					'name'        => 'Reimported Event',
					'description' => '<p>New description.</p>',
				),
				$this->settings
			);
		}

		$this->assertSame( '<p>New description.</p>', get_post_field( 'post_content', $copied ) );
		$this->assertSame( '', get_post_field( 'post_excerpt', $copied ) );
		$this->assertSame( '<p>New description.</p>', get_post_field( 'post_content', $summary ) );
		$this->assertSame( 'A summary written by an admin.', get_post_field( 'post_excerpt', $summary ) );
	}

	/**
	 * The importer used by manual and scheduled syncs also clears copied excerpts.
	 */
	public function test_active_importer_clears_copied_excerpt() {
		$event_id = $this->create_legacy_import( 'active-copied-event', '<p>Old description.</p>' );
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'upsert_eventyay_event_post' );
		$method->setAccessible( true );

		$method->invoke(
			$importer,
			array(
				'slug'        => 'active-copied-event',
				'name'        => 'Active Copied Event',
				'description' => '<p>New description.</p>',
			),
			$this->settings,
			$event_id
		);

		$this->assertSame( '<p>New description.</p>', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( '', get_post_field( 'post_excerpt', $event_id ) );
	}

	/**
	 * An event imported before this change shows WordPress editor changes on the event page.
	 */
	public function test_wordpress_editor_change_shows_on_event_page_for_older_import() {
		$event_id = $this->create_legacy_import( 'editor-change-event', '<p>Imported description.</p>' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => '<p>Changed in the WordPress editor.</p>',
			)
		);

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( '<p>Changed in the WordPress editor.</p>', $template_data['about_content'] );
	}

	/**
	 * Saving from the Edit Event modal clears a copied excerpt but keeps a written summary.
	 */
	public function test_excerpt_to_save() {
		$copied  = $this->create_legacy_import( 'modal-copied-event', '<p>Old description.</p>' );
		$summary = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => '<p>The full description.</p>',
				'post_excerpt' => 'A summary written by an admin.',
			)
		);

		$this->assertSame( '', Wpfaevent_Event_Description::excerpt_to_save( $copied ) );
		$this->assertNull( Wpfaevent_Event_Description::excerpt_to_save( $summary ) );
		$this->assertNull( Wpfaevent_Event_Description::excerpt_to_save( 0 ) );
	}
}
