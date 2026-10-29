<?php
/**
 * Unit tests for event modal description validation.
 *
 * @package Wpfaevent
 */

/**
 * Verify that descriptions from the visual editor are checked by their visible text.
 */
class EventHandlerDescriptionTest extends WP_UnitTestCase {

	/**
	 * Call the private blank description check.
	 *
	 * @param string $description Description HTML.
	 * @return bool
	 */
	private function is_blank( $description ) {
		$handler = new Wpfaevent_Event_Handler();
		$method  = new ReflectionMethod( $handler, 'is_blank_description' );
		$method->setAccessible( true );

		return $method->invoke( $handler, $description );
	}

	/**
	 * Markup the editor sends for a field that looks empty counts as blank.
	 */
	public function test_empty_editor_markup_is_blank() {
		$this->assertTrue( $this->is_blank( '' ) );
		$this->assertTrue( $this->is_blank( "  \n " ) );
		$this->assertTrue( $this->is_blank( '<p>&nbsp;</p>' ) );
		$this->assertTrue( $this->is_blank( "<p>\xC2\xA0</p>\n<p><br></p>" ) );
		$this->assertTrue( $this->is_blank( '<ul><li> </li></ul>' ) );
	}

	/**
	 * Call the private request description reader.
	 *
	 * @param string|null $content Sanitized content field value.
	 * @param string|null $excerpt Sanitized legacy excerpt field value.
	 * @return string
	 */
	private function request_description( $content, $excerpt ) {
		$handler = new Wpfaevent_Event_Handler();
		$method  = new ReflectionMethod( $handler, 'request_description' );
		$method->setAccessible( true );

		return $method->invoke( $handler, $content, $excerpt );
	}

	/**
	 * The description is read from the canonical content field (#369).
	 */
	public function test_description_comes_from_content_field() {
		$this->assertSame( '<p>From content.</p>', $this->request_description( '<p>From content.</p>', '<p>From excerpt.</p>' ) );
	}

	/**
	 * The legacy excerpt field is still accepted as a fallback.
	 */
	public function test_legacy_excerpt_field_is_still_accepted() {
		$this->assertSame( '<p>Legacy description.</p>', $this->request_description( null, '<p>Legacy description.</p>' ) );
	}

	/**
	 * No submitted description resolves to an empty string.
	 */
	public function test_description_defaults_to_empty_string() {
		$this->assertSame( '', $this->request_description( null, null ) );
	}

	/**
	 * Any visible text counts as a description.
	 */
	public function test_visible_text_is_not_blank() {
		$this->assertFalse( $this->is_blank( 'Plain text description.' ) );
		$this->assertFalse( $this->is_blank( '<p>Join the <strong>FOSSASIA Summit</strong>.</p>' ) );
		$this->assertFalse( $this->is_blank( '<p>&nbsp;</p><ul><li>Workshops</li></ul>' ) );
		$this->assertFalse( $this->is_blank( '<p>&amp;</p>' ) );
	}
}
