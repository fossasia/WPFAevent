<?php
/**
 * Event color regression tests.
 *
 * @package Wpfaevent
 */

/**
 * Verify that event colors can be edited and applied to the event hero.
 */
class EventColorsTest extends WP_UnitTestCase {

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Set up an editable event.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
	}

	/**
	 * The event editor displays every supported color field.
	 */
	public function test_event_editor_displays_color_fields() {
		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#F97316' );

		ob_start();
		( new Wpfaevent_Admin_Event_Metabox() )->render_event_meta_box( get_post( $this->event_id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Event Colors', $output );
		foreach ( Wpfaevent_Meta_Event::get_event_color_meta_fields() as $meta_key => $label ) {
			$this->assertStringContainsString( 'name="' . $meta_key . '"', $output );
		}
		$this->assertStringContainsString( 'value="#F97316"', $output );
	}

	/**
	 * Saving the event sanitizes valid colors and removes invalid values.
	 */
	public function test_event_editor_saves_sanitized_colors() {
		update_post_meta( $this->event_id, 'wpfa_event_hover_button_color', '#B20D06' );

		$_POST = array(
			'wpfa_event_meta_nonce'             => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_primary_color'          => 'f97316',
			'wpfa_event_hover_button_color'     => 'not-a-color',
			'wpfa_event_theme_success_color'    => 'rgb(47, 143, 91)',
			'wpfa_event_theme_danger_color'     => '#dc2626',
			'wpfa_event_theme_background_color' => '#fff7ed',
		);

		( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );
		$_POST = array();

		$this->assertSame( '#F97316', get_post_meta( $this->event_id, 'wpfa_event_primary_color', true ) );
		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_hover_button_color', true ) );
		$this->assertSame( 'rgb(47, 143, 91)', get_post_meta( $this->event_id, 'wpfa_event_theme_success_color', true ) );
		$this->assertSame( '#DC2626', get_post_meta( $this->event_id, 'wpfa_event_theme_danger_color', true ) );
		$this->assertSame( '#FFF7ED', get_post_meta( $this->event_id, 'wpfa_event_theme_background_color', true ) );
	}

	/**
	 * The hero background uses the event primary color variable.
	 */
	public function test_event_hero_uses_primary_color_variable() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event-base.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-hero \{.*?var\(--event-primary\);/s', $stylesheet );
		$this->assertStringNotContainsString( 'linear-gradient(135deg, #8f0a05 0%, #D51007 52%, #f15b53 100%)', $stylesheet );
	}
}
