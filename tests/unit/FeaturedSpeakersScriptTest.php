<?php
/**
 * Regression tests for the featured-speaker dashboard script.
 *
 * @package Wpfaevent
 */

/**
 * Featured-speaker dashboard JavaScript regression tests.
 */
class FeaturedSpeakersScriptTest extends WP_UnitTestCase {

	/**
	 * Untrusted speaker values must only reach text and attribute setters.
	 */
	public function test_speaker_markup_and_attribute_breaking_values_use_safe_dom_sinks() {
		$source = $this->get_featured_speakers_script();

		$this->assertStringContainsString( ".text(name)", $source );
		$this->assertStringContainsString( ".text(title)", $source );
		$this->assertStringContainsString( ".attr({ src: imgUrl, alt: name })", $source );
		$this->assertStringContainsString( "['http:', 'https:'].includes(parsedUrl.protocol)", $source );
		$this->assertStringNotContainsString( '${name}', $source );
		$this->assertStringNotContainsString( '${title}', $source );
		$this->assertStringNotContainsString( '${imgUrl}', $source );
		$this->assertStringNotContainsString( '${speakerId}', $source );
		$this->assertStringNotContainsString( '.html(', $source );
	}

	/**
	 * Saves must remain single-flight and replace queued state with the latest order.
	 */
	public function test_rapid_saves_are_serialized_and_only_the_latest_result_is_reported() {
		$source = $this->get_featured_speakers_script();

		$this->assertStringContainsString( 'let saveInProgress = false;', $source );
		$this->assertStringContainsString( 'queuedSpeakerIds = speakerIds;', $source );
		$this->assertStringContainsString( 'if (saveInProgress || queuedSpeakerIds === null)', $source );
		$this->assertStringContainsString( 'const speakerIds = queuedSpeakerIds;', $source );
		$this->assertStringContainsString( 'queuedSpeakerIds = null;', $source );
		$this->assertStringContainsString( 'if (queuedSpeakerIds !== null)', $source );
		$this->assertStringContainsString( 'processFeaturedSpeakersSave();', $source );
		$this->assertStringContainsString( 'saveRevision === latestSaveRevision', $source );
		$this->assertSame( 1, substr_count( $source, '$.ajax({' ) );
	}

	/**
	 * Read only the featured-speaker section of the admin script.
	 *
	 * @return string
	 */
	private function get_featured_speakers_script() {
		$path   = dirname( __DIR__, 2 ) . '/admin/js/wpfaevent-admin.js';
		$source = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $source );

		$start = strpos( $source, '// Featured Speakers Manual Ordering' );
		$end   = strpos( $source, 'function getEventTitle', $start );

		$this->assertNotFalse( $start );
		$this->assertNotFalse( $end );

		return substr( $source, $start, $end - $start );
	}
}
