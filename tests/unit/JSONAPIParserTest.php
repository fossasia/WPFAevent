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
}
