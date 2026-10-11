<?php
/**
 * Unit tests for keeping locally edited Eventyay event descriptions.
 *
 * @package Wpfaevent
 */

/**
 * Covers Wpfaevent_Event_Description_Sync and its use in event imports.
 */
class EventDescriptionSyncTest extends WP_UnitTestCase {

	/**
	 * Import settings shared by the tests.
	 *
	 * @var array<string, string>
	 */
	private $settings = array(
		'base_url'       => 'https://eventyay.example',
		'organizer_slug' => 'fossasia',
		'post_status'    => 'draft',
	);

	/**
	 * Import an event through the repository.
	 *
	 * @param string $description Eventyay description.
	 * @param string $title       Eventyay title.
	 * @return int Event post ID.
	 */
	private function import( $description, $title = 'Description Event' ) {
		$result = ( new Wpfaevent_Event_Repository() )->upsert_eventyay_event_post(
			array(
				'slug'        => 'description-event',
				'name'        => $title,
				'description' => $description,
			),
			$this->settings
		);

		$this->assertIsArray( $result );

		return $result['id'];
	}

	/**
	 * Events that were never edited keep following Eventyay.
	 */
	public function test_unedited_description_follows_eventyay() {
		$event_id = $this->import( 'Original description.' );
		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( '', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
	}

	/**
	 * A description edited in the Edit Event modal survives a sync; other fields still sync.
	 */
	public function test_locally_edited_excerpt_is_kept_on_sync() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Written for the website.',
			)
		);

		$this->import( 'Original description.', 'Renamed on Eventyay' );

		$this->assertSame( 'Written for the website.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( 'Renamed on Eventyay', get_the_title( $event_id ) );
		$this->assertSame( 'Description edited on this site. Eventyay syncs will not change it.', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
	}

	/**
	 * A description edited in WP admin or the dashboard (post content) survives a sync.
	 */
	public function test_locally_edited_content_is_kept_on_sync() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => '<p>Longer website copy.</p>',
			)
		);

		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( '<p>Longer website copy.</p>', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( 'Original description.', get_post_field( 'post_excerpt', $event_id ) );
	}

	/**
	 * Saving the modal without changing the text (textarea CRLF line endings) is not an edit.
	 */
	public function test_line_ending_round_trip_is_not_an_edit() {
		$event_id = $this->import( "First line.\nSecond line." );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => "First line.\r\nSecond line.",
			)
		);

		$this->assertFalse( Wpfaevent_Event_Description_Sync::has_local_edit( $event_id ) );
	}

	/**
	 * The note mentions a newer Eventyay description after a local edit.
	 */
	public function test_note_mentions_newer_eventyay_description() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Written for the website.',
			)
		);

		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( 'Written for the website.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertTrue( Wpfaevent_Event_Description_Sync::has_newer_remote( $event_id ) );
		$this->assertStringContainsString( 'Eventyay has a newer description', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
	}

	/**
	 * Restoring brings back the current Eventyay description and follows Eventyay again.
	 */
	public function test_restore_follows_eventyay_again() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Written for the website.',
			)
		);
		$this->import( 'Updated on Eventyay.' );

		$this->assertTrue( Wpfaevent_Event_Description_Sync::restore( $event_id ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( '', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );

		$this->import( 'Changed again on Eventyay.' );
		$this->assertSame( 'Changed again on Eventyay.', get_post_field( 'post_excerpt', $event_id ) );
	}

	/**
	 * Backslashes in Eventyay descriptions survive import, tracking and restore.
	 */
	public function test_backslashes_survive_import_and_restore() {
		$event_id = $this->import( 'Files live in C:\events\2027.' );
		$this->assertSame( 'Files live in C:\events\2027.', get_post_field( 'post_content', $event_id ) );
		$this->assertFalse( Wpfaevent_Event_Description_Sync::has_local_edit( $event_id ) );

		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Written for the website.',
			)
		);
		$this->import( 'Moved to D:\events\2027.' );
		$this->assertSame( 'Moved to D:\events\2027.', get_post_meta( $event_id, Wpfaevent_Event_Description_Sync::REMOTE_META, true ) );

		$this->assertTrue( Wpfaevent_Event_Description_Sync::restore( $event_id ) );
		$this->assertSame( 'Moved to D:\events\2027.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertFalse( Wpfaevent_Event_Description_Sync::has_local_edit( $event_id ) );
	}

	/**
	 * Turn an event into one imported before description tracking existed.
	 *
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private function make_legacy( $event_id ) {
		delete_post_meta( $event_id, Wpfaevent_Event_Description_Sync::HASH_META );
		delete_post_meta( $event_id, Wpfaevent_Event_Description_Sync::APPLIED_META );
		delete_post_meta( $event_id, Wpfaevent_Event_Description_Sync::REMOTE_META );
	}

	/**
	 * A legacy event edited in the modal keeps its description, and the note reports
	 * that Eventyay's description changed.
	 */
	public function test_legacy_event_edited_locally_is_kept() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Edited before the upgrade.',
			)
		);
		$this->make_legacy( $event_id );

		$this->import( 'Updated on Eventyay.', 'Renamed on Eventyay' );

		$this->assertSame( 'Edited before the upgrade.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( 'Renamed on Eventyay', get_the_title( $event_id ) );
		$this->assertTrue( Wpfaevent_Event_Description_Sync::has_newer_remote( $event_id ) );
		$this->assertStringContainsString( 'Eventyay has a newer description', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );

		// Later syncs keep it too, and restore brings back Eventyay's text.
		$this->import( 'Changed again on Eventyay.' );
		$this->assertSame( 'Edited before the upgrade.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertTrue( Wpfaevent_Event_Description_Sync::restore( $event_id ) );
		$this->assertSame( 'Changed again on Eventyay.', get_post_field( 'post_excerpt', $event_id ) );
	}

	/**
	 * A legacy event edited in WP admin keeps its description; with Eventyay unchanged
	 * the note does not claim a newer description.
	 */
	public function test_legacy_content_edit_with_unchanged_eventyay_is_kept() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => 'Longer website copy.',
			)
		);
		$this->make_legacy( $event_id );

		$this->import( 'Original description.' );

		$this->assertSame( 'Longer website copy.', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( 'Description edited on this site. Eventyay syncs will not change it.', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
	}

	/**
	 * A title-only change on a legacy event does not lock its description.
	 */
	public function test_legacy_title_change_does_not_keep_old_description() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'         => $event_id,
				'post_title' => 'Renamed on this site',
			)
		);
		$this->make_legacy( $event_id );

		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_content', $event_id ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( '', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
	}

	/**
	 * A content-only legacy import that still matches Eventyay follows it and starts tracking.
	 */
	public function test_legacy_content_only_import_in_sync_follows_eventyay() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => '',
			)
		);
		$this->make_legacy( $event_id );

		$this->import( 'Original description.' );
		$this->assertSame( 'Original description.', get_post_field( 'post_excerpt', $event_id ) );
		$this->assertSame( '', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );

		$this->import( 'Updated on Eventyay.' );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_content', $event_id ) );
	}

	/**
	 * A content-only legacy import that differs from Eventyay is ambiguous, so it is kept.
	 */
	public function test_legacy_content_only_import_with_changed_text_is_kept() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => 'Edited in WP admin before the upgrade.',
				'post_excerpt' => '',
			)
		);
		$this->make_legacy( $event_id );

		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( 'Edited in WP admin before the upgrade.', get_post_field( 'post_content', $event_id ) );
		$this->assertStringContainsString( 'Eventyay has a newer description', Wpfaevent_Event_Description_Sync::get_note( $event_id ) );
		$this->assertTrue( Wpfaevent_Event_Description_Sync::restore( $event_id ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_field( 'post_content', $event_id ) );
	}

	/**
	 * A legacy event that matches Eventyay starts tracking, so later edits are kept.
	 */
	public function test_legacy_event_in_sync_starts_tracking() {
		$event_id = $this->import( 'Original description.' );
		$this->make_legacy( $event_id );

		$this->import( 'Original description.' );
		$this->assertNotSame( '', get_post_meta( $event_id, Wpfaevent_Event_Description_Sync::HASH_META, true ) );

		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_excerpt' => 'Written for the website.',
			)
		);
		$this->import( 'Updated on Eventyay.' );

		$this->assertSame( 'Written for the website.', get_post_field( 'post_excerpt', $event_id ) );
	}

	/**
	 * The active importer used by manual and scheduled syncs keeps local edits too.
	 */
	public function test_active_importer_keeps_local_edit() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'upsert_eventyay_event_post' );
		$method->setAccessible( true );
		$event = array(
			'slug'        => 'active-description-event',
			'name'        => 'Active Description Event',
			'description' => 'Original description.',
		);

		$result = $method->invoke( $importer, $event, $this->settings );
		$this->assertIsArray( $result );
		wp_update_post(
			array(
				'ID'           => $result['id'],
				'post_excerpt' => 'Written for the website.',
			)
		);

		$event['description'] = 'Updated on Eventyay.';
		$method->invoke( $importer, $event, $this->settings );

		$this->assertSame( 'Written for the website.', get_post_field( 'post_excerpt', $result['id'] ) );
		$this->assertSame( 'Updated on Eventyay.', get_post_meta( $result['id'], Wpfaevent_Event_Description_Sync::REMOTE_META, true ) );
	}
}
