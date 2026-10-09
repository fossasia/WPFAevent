<?php
/**
 * Unit tests for Eventyay JSON:API sponsor normalization.
 *
 * @package Wpfaevent
 */

/**
 * Covers sponsor tier extraction for Eventyay imports.
 */
class JSONAPIParserTest extends WP_UnitTestCase {
	/**
	 * Legacy organizer speakers should inherit featured flags from JSON:API data.
	 */
	public function test_merge_supplemental_speakers_marks_matching_name_as_featured() {
		$parser       = new Wpfaevent_JSONAPI_Parser();
		$speakers     = array(
			array(
				'name'           => 'Tarus Balog',
				'title'          => 'Principal Open Source Strategist',
				'image'          => 'https://example.com/tarus.jpg',
				'featured'       => false,
				'featured_order' => 0,
			),
		);
		$supplemental = array(
			array(
				'name'           => 'Tarus Balog',
				'title'          => 'Principal Open Source Strategist',
				'image'          => 'https://example.com/tarus.jpg',
				'featured'       => true,
				'featured_order' => 15,
			),
		);

		$merged = $parser->merge_supplemental_speakers( $speakers, $supplemental );

		$this->assertTrue( $merged[0]['featured'] );
		$this->assertSame( 15, $merged[0]['featured_order'] );
	}

	/**
	 * Ambiguous name matches should resolve via image when possible.
	 */
	public function test_merge_supplemental_speakers_uses_image_to_break_name_ties() {
		$parser       = new Wpfaevent_JSONAPI_Parser();
		$speakers     = array(
			array(
				'name'           => 'Alex Kim',
				'title'          => 'Engineer',
				'image'          => 'https://example.com/alex-2.jpg',
				'featured'       => false,
				'featured_order' => 0,
			),
		);
		$supplemental = array(
			array(
				'name'           => 'Alex Kim',
				'title'          => 'Engineer',
				'image'          => 'https://example.com/alex-1.jpg',
				'featured'       => true,
				'featured_order' => 10,
			),
			array(
				'name'           => 'Alex Kim',
				'title'          => 'Engineer',
				'image'          => 'https://example.com/alex-2.jpg',
				'featured'       => true,
				'featured_order' => 4,
			),
		);

		$merged = $parser->merge_supplemental_speakers( $speakers, $supplemental );

		$this->assertTrue( $merged[0]['featured'] );
		$this->assertSame( 4, $merged[0]['featured_order'] );
	}

	/**
	 * Missing speakers from the legacy organizer feed should be appended.
	 */
	public function test_merge_supplemental_speakers_appends_missing_records() {
		$parser       = new Wpfaevent_JSONAPI_Parser();
		$speakers     = array(
			array(
				'name'     => 'Tarus Balog',
				'title'    => 'Principal Open Source Strategist',
				'image'    => 'https://example.com/tarus.jpg',
				'featured' => false,
			),
		);
		$supplemental = array(
			array(
				'name'     => 'Tarus Balog',
				'title'    => 'Principal Open Source Strategist',
				'image'    => 'https://example.com/tarus.jpg',
				'featured' => true,
			),
			array(
				'name'         => 'Italo Vignoli',
				'title'        => 'Marketing Lead',
				'image'        => 'https://example.com/italo.jpg',
				'organization' => 'The Document Foundation',
				'featured'     => true,
			),
		);

		$merged = $parser->merge_supplemental_speakers( $speakers, $supplemental );

		$this->assertCount( 2, $merged );
		$this->assertSame( 'Italo Vignoli', $merged[1]['name'] );
		$this->assertTrue( $merged[1]['featured'] );
	}

	/**
	 * The importer uses a dedicated Eventyay headline when one is available.
	 */
	public function test_event_lead_text_prefers_headline() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$lead_text = $parser->eventyay_event_lead_text(
			array(
				'headline'    => 'A concise event headline',
				'description' => 'The longer event description. More details follow.',
			)
		);

		$this->assertSame( 'A concise event headline', $lead_text );
	}

	/**
	 * Dedicated lead fields take precedence over frontpage description content.
	 */
	public function test_event_lead_text_prefers_short_field_over_frontpage_text() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'Meet the open-source community',
			$parser->eventyay_event_lead_text(
				array(
					'short_description' => 'Meet the open-source community',
					'frontpage_text'    => 'A longer frontpage description. More details follow.',
				)
			)
		);
	}

	/**
	 * A description supplies its first sentence when no dedicated lead exists.
	 */
	public function test_event_lead_text_falls_back_to_first_description_sentence() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$lead_text = $parser->eventyay_event_lead_text(
			array(
				'description' => '<p>First sentence for the hero.</p> More event details follow.',
			)
		);

		$this->assertSame( 'First sentence for the hero.', $lead_text );
	}

	/**
	 * HTML descriptions are converted to text before the first sentence is extracted.
	 */
	public function test_event_lead_text_uses_description_html() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'First HTML sentence.',
			$parser->eventyay_event_lead_text(
				array( 'description_html' => '<p>First HTML sentence.</p><p>Second sentence.</p>' )
			)
		);
	}

	/**
	 * Adjacent HTML paragraphs retain a whitespace boundary when stripped.
	 */
	public function test_event_lead_text_preserves_adjacent_html_boundaries() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'First sentence.',
			$parser->eventyay_event_lead_text(
				array( 'description_html' => '<p>First sentence.</p><p>Second sentence.</p>' )
			)
		);
	}

	/**
	 * A dedicated short description remains the lead when no description exists.
	 */
	public function test_event_lead_text_uses_short_description_without_description() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( 'Meet the open-source community', $parser->eventyay_event_lead_text( array( 'short_description' => 'Meet the open-source community' ) ) );
	}

	/**
	 * A standalone summary remains a lead when no separate description exists.
	 */
	public function test_event_lead_text_uses_standalone_summary() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( 'Meet the open-source community', $parser->eventyay_event_lead_text( array( 'summary' => 'Meet the open-source community' ) ) );
	}

	/**
	 * A summary duplicated from frontpage content is reduced to its first sentence.
	 */
	public function test_event_lead_text_reduces_duplicate_summary() {
		$parser      = new Wpfaevent_JSONAPI_Parser();
		$description = 'First sentence. The remaining event description is much longer.';

		$this->assertSame(
			'First sentence.',
			$parser->eventyay_event_lead_text(
				array(
					'summary'        => $description,
					'frontpage_text' => $description,
				)
			)
		);
	}

	/**
	 * Frontpage content aliases can supply the description fallback.
	 */
	public function test_event_lead_text_uses_frontpage_content_alias() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'First frontpage sentence.',
			$parser->eventyay_event_lead_text( array( 'frontpage-content' => 'First frontpage sentence. More details follow.' ) )
		);
	}

	/**
	 * Frontpage content aliases supply the full imported event description.
	 */
	public function test_event_description_uses_frontpage_content_alias() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'Full frontpage description.',
			$parser->eventyay_event_description( array( 'frontpage_content' => 'Full frontpage description.' ) )
		);
		$this->assertSame(
			'Full hyphenated frontpage description.',
			$parser->eventyay_event_description( array( 'frontpage-content' => 'Full hyphenated frontpage description.' ) )
		);
	}

	/**
	 * No usable source leaves the lead empty.
	 */
	public function test_event_lead_text_is_empty_without_sources() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( '', $parser->eventyay_event_lead_text( array( 'name' => 'Event without description' ) ) );
	}

	/**
	 * The event template keeps the canonical lead separate from the full about content.
	 */
	public function test_event_template_uses_canonical_lead_text() {
		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => 'The complete event description remains available here.',
				'post_excerpt' => '',
			)
		);
		update_post_meta( $event_id, 'wpfa_event_lead_text', 'A short event lead.' );
		update_post_meta( $event_id, '_event_lead_text', 'A legacy event lead.' );

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( 'A short event lead.', $template_data['event_lead_text'] );
		$this->assertStringContainsString( 'complete event description', $template_data['about_content'] );
	}

	/**
	 * Older imported events still expose their legacy lead text in the template data.
	 */
	public function test_event_template_falls_back_to_legacy_lead_text() {
		$event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
		update_post_meta( $event_id, '_event_lead_text', 'A legacy event lead.' );

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( 'A legacy event lead.', $template_data['event_lead_text'] );
	}

	/**
	 * Events without lead metadata retain the existing post-content fallback.
	 */
	public function test_event_template_keeps_existing_fallback_without_lead_meta() {
		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => 'The existing event description fallback.',
				'post_excerpt' => '',
			)
		);

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( 'The existing event description fallback.', $template_data['event_lead_text'] );
		$this->assertStringContainsString( 'existing event description fallback', $template_data['about_content'] );
	}

	/**
	 * Frontend-edited descriptions use the post excerpt on the event page.
	 */
	public function test_event_template_uses_post_excerpt_before_post_content() {
		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => 'The old event description.',
				'post_excerpt' => 'The updated event description.',
			)
		);

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( 'The updated event description.', $template_data['about_content'] );
	}

	/**
	 * A frontend-edited description takes precedence over stale dashboard content.
	 */
	public function test_event_template_uses_edited_excerpt_before_stale_about_content() {
		$event_id   = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_content' => 'The newly edited full description.',
				'post_excerpt' => 'The newly edited full description.',
			)
		);
		$upload_dir = wp_upload_dir();
		$data_dir   = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';
		$file_path  = trailingslashit( $data_dir ) . 'site-settings-' . $event_id . '.json';

		wp_mkdir_p( $data_dir );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents(
			$file_path,
			wp_json_encode( array( 'about_section_content' => 'The stale imported description.' ) )
		);

		try {
			$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $file_path );
		}

		$this->assertSame( 'The newly edited full description.', $template_data['about_content'] );
	}

	/**
	 * A full description is reduced to a sentence instead of becoming the lead.
	 */
	public function test_event_lead_text_does_not_store_full_description() {
		$parser      = new Wpfaevent_JSONAPI_Parser();
		$description = 'First sentence. The remaining event description is much longer.';

		$this->assertSame(
			'First sentence.',
			$parser->eventyay_event_lead_text(
				array(
					'summary'     => $description,
					'description' => $description,
				)
			)
		);
	}

	/**
	 * A description without a sentence boundary does not become the full lead.
	 */
	public function test_event_lead_text_is_empty_without_sentence_boundary() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame( '', $parser->eventyay_event_lead_text( array( 'description' => 'A description without a sentence boundary' ) ) );
	}

	/**
	 * Derived hero leads are limited without cutting a word in half.
	 */
	public function test_event_lead_text_is_limited_to_a_word_boundary() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$lead   = $parser->eventyay_event_lead_text(
			array(
				'headline' => str_repeat( 'A long headline ', 20 ),
			)
		);

		$this->assertLessThanOrEqual( 160, strlen( $lead ) );
		$this->assertNotSame( ' ', substr( $lead, -1 ) );
	}

	/**
	 * Sentence extraction does not stop at an abbreviation before a number.
	 */
	public function test_event_lead_text_ignores_abbreviation_sentence_boundary() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertSame(
			'Join us on Mar. 14 for the event.',
			$parser->eventyay_event_lead_text( array( 'description' => 'Join us on Mar. 14 for the event. More details follow.' ) )
		);
	}

	/**
	 * Imported lead text is stored in the canonical event meta field.
	 */
	public function test_event_import_stores_lead_text_in_canonical_meta() {
		$event_id = ( new Wpfaevent_Event_Repository() )->upsert_eventyay_event_post(
			array(
				'slug'        => 'lead-text-event',
				'name'        => 'Lead Text Event',
				'description' => 'The first sentence is the hero lead. The rest is event detail.',
			),
			array(
				'base_url'       => 'https://eventyay.example',
				'organizer_slug' => 'fossasia',
				'post_status'    => 'draft',
			)
		);

		$this->assertIsArray( $event_id );
		$this->assertSame( 'The first sentence is the hero lead.', get_post_meta( $event_id['id'], 'wpfa_event_lead_text', true ) );
		$this->assertSame( '', get_post_meta( $event_id['id'], '_event_lead_text', true ) );
		$this->assertSame( 'The first sentence is the hero lead. The rest is event detail.', get_post_field( 'post_content', $event_id['id'] ) );
		$this->assertSame( 'The first sentence is the hero lead. The rest is event detail.', get_post_field( 'post_excerpt', $event_id['id'] ) );
	}

	/**
	 * Reimports do not overwrite an existing canonical lead with an empty lead.
	 */
	public function test_event_import_preserves_existing_lead_when_new_lead_is_empty() {
		$repository = new Wpfaevent_Event_Repository();
		$settings   = array(
			'base_url'       => 'https://eventyay.example',
			'organizer_slug' => 'fossasia',
			'post_status'    => 'draft',
		);
		$event      = array(
			'slug'        => 'legacy-lead-event',
			'name'        => 'Legacy Lead Event',
			'description' => 'The initial event description.',
		);
		$event_id   = $repository->upsert_eventyay_event_post( $event, $settings );

		$this->assertIsArray( $event_id );
		update_post_meta( $event_id['id'], '_event_lead_text', 'Stale legacy lead.' );

		$event['description'] = 'Updated description without a sentence boundary';
		$repository->upsert_eventyay_event_post( $event, $settings );

		$this->assertSame( 'The initial event description.', get_post_meta( $event_id['id'], 'wpfa_event_lead_text', true ) );
		$this->assertSame( 'Stale legacy lead.', get_post_meta( $event_id['id'], '_event_lead_text', true ) );
	}

	/**
	 * Reimports do not overwrite a manually edited canonical lead.
	 */
	public function test_event_import_preserves_manually_edited_lead() {
		$repository = new Wpfaevent_Event_Repository();
		$settings   = array(
			'base_url'       => 'https://eventyay.example',
			'organizer_slug' => 'fossasia',
			'post_status'    => 'draft',
		);
		$event      = array(
			'slug'        => 'manual-lead-event',
			'name'        => 'Manual Lead Event',
			'description' => 'The imported lead. More event details follow.',
		);
		$event_id   = $repository->upsert_eventyay_event_post( $event, $settings );

		$this->assertIsArray( $event_id );
		update_post_meta( $event_id['id'], 'wpfa_event_lead_text', 'The organizer written lead.' );

		$event['description'] = 'A changed imported lead. More event details follow.';
		$repository->upsert_eventyay_event_post( $event, $settings );

		$this->assertSame( 'The organizer written lead.', get_post_meta( $event_id['id'], 'wpfa_event_lead_text', true ) );
	}

	/**
	 * The active importer delegates lead extraction to its parser.
	 */
	public function test_active_event_importer_stores_lead_text() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'upsert_eventyay_event_post' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$importer,
			array(
				'slug'        => 'active-lead-text-event',
				'name'        => 'Active Lead Text Event',
				'description' => 'The active importer uses the parser. More details follow.',
			),
			array(
				'base_url'       => 'https://eventyay.example',
				'organizer_slug' => 'fossasia',
				'post_status'    => 'draft',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'The active importer uses the parser.', get_post_meta( $result['id'], 'wpfa_event_lead_text', true ) );
		$this->assertSame( 'The active importer uses the parser. More details follow.', get_post_field( 'post_content', $result['id'] ) );
		$this->assertSame( 'The active importer uses the parser. More details follow.', get_post_field( 'post_excerpt', $result['id'] ) );
	}

	/**
	 * Verify that is_eventyay_confirmed_session only accepts confirmed state or scheduled sessions.
	 */
	public function test_is_eventyay_confirmed_session() {
		$parser = new Wpfaevent_JSONAPI_Parser();

		$this->assertTrue(
			$parser->is_eventyay_confirmed_session(
				array(
					'title' => 'Confirmed Talk',
					'state' => 'confirmed',
				)
			)
		);

		$this->assertFalse(
			$parser->is_eventyay_confirmed_session(
				array(
					'title' => 'Accepted Talk',
					'state' => 'accepted',
				)
			)
		);

		$this->assertFalse(
			$parser->is_eventyay_confirmed_session(
				array(
					'title' => 'Draft Talk',
					'state' => 'draft',
				)
			)
		);

		$this->assertFalse(
			$parser->is_eventyay_confirmed_session(
				array(
					'title' => 'Unscheduled Talk',
				)
			)
		);

		$this->assertTrue(
			$parser->is_eventyay_confirmed_session(
				array(
					'title'     => 'Scheduled Talk without state',
					'starts_at' => '2026-03-15T10:00:00Z',
				)
			)
		);
	}

	/**
	 * Submissions payload normalization should only import confirmed sessions.
	 */
	public function test_normalize_eventyay_submissions_payload_filters_unconfirmed_sessions() {
		$parser      = new Wpfaevent_JSONAPI_Parser();
		$submissions = array(
			array(
				'id'        => '101',
				'title'     => 'Confirmed Session',
				'state'     => 'confirmed',
				'starts_at' => '2026-03-15T10:00:00Z',
				'ends_at'   => '2026-03-15T10:30:00Z',
				'speakers'  => array(
					array(
						'name' => 'Jane Doe',
					),
				),
			),
			array(
				'id'       => '102',
				'title'    => 'Accepted Unconfirmed Session',
				'state'    => 'accepted',
				'speakers' => array(
					array(
						'name' => 'John Smith',
					),
				),
			),
		);

		$result = $parser->normalize_eventyay_submissions_payload( $submissions, array(), 'test-event' );

		$this->assertSame( 1, $result['session_count'] );
		$this->assertCount( 1, $result['sessions'] );
		$this->assertSame( 'Confirmed Session', $result['sessions'][0]['title'] );
	}
}
