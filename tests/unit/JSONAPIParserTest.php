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
			)
		);
		update_post_meta( $event_id, 'wpfa_event_lead_text', 'A short event lead.' );

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( 'A short event lead.', $template_data['event_lead_text'] );
		$this->assertStringContainsString( 'complete event description', $template_data['about_content'] );
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
	}
}
