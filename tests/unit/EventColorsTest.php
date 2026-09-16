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
		( new Wpfaevent_Admin_Event_Metabox() )->render_event_colors_meta_box( get_post( $this->event_id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Primary Color', $output );
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
	 * Normalizes shorthand hex and rgb to 6-digit hex values for color pickers.
	 */
	public function test_normalize_color_to_hex() {
		$this->assertSame( '#FF6600', Wpfaevent_Meta_Event::normalize_color_to_hex( '#F60', '#D51007' ) );
		$this->assertSame( '#FF6600', Wpfaevent_Meta_Event::normalize_color_to_hex( 'f60', '#D51007' ) );
		$this->assertSame( '#FF6600', Wpfaevent_Meta_Event::normalize_color_to_hex( 'rgb(255, 102, 0)', '#D51007' ) );
		$this->assertSame( '#123456', Wpfaevent_Meta_Event::normalize_color_to_hex( '#123456', '#D51007' ) );
		$this->assertSame( '#D51007', Wpfaevent_Meta_Event::normalize_color_to_hex( 'invalid', '#D51007' ) );
		$this->assertSame( '#D51007', Wpfaevent_Meta_Event::normalize_color_to_hex( '', '#D51007' ) );
	}

	/**
	 * Darkening calculates a darker shade of a hex or rgb color.
	 */
	public function test_darken_color() {
		$this->assertSame( '#D95700', Wpfaevent_Meta_Event::darken_color( '#FF6600', 15 ) );
		$this->assertSame( '#B50E06', Wpfaevent_Meta_Event::darken_color( '#D51007', 15 ) );
		$this->assertSame( '#D95700', Wpfaevent_Meta_Event::darken_color( 'rgb(255, 102, 0)', 15 ) );
		$this->assertSame( '#D95700', Wpfaevent_Meta_Event::darken_color( '#F60', 15 ) );
		$this->assertSame( 'invalid', Wpfaevent_Meta_Event::darken_color( 'invalid' ) );
	}

	/**
	 * Specifying only a primary color auto-derives the hover button color and its contrast.
	 */
	public function test_primary_color_derives_effective_hover_color_and_contrast() {
		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#FF6600' );

		$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $this->event_id );

		$this->assertStringContainsString( '--event-primary: #FF6600', $data['event_style_attr'] );
		$this->assertStringContainsString( '--event-primary-dark: #D95700', $data['event_style_attr'] );
		$this->assertStringContainsString( '--event-primary-contrast: #000000', $data['event_style_attr'] );
		$this->assertStringContainsString( '--event-primary-dark-contrast: #000000', $data['event_style_attr'] );
	}

	/**
	 * Explicit hover button color overrides the auto-derived dark shade.
	 */
	public function test_explicit_hover_color_overrides_derived_dark() {
		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#FF6600' );
		update_post_meta( $this->event_id, 'wpfa_event_hover_button_color', '#993300' );

		$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $this->event_id );

		$this->assertStringContainsString( '--event-primary: #FF6600', $data['event_style_attr'] );
		$this->assertStringContainsString( '--event-primary-dark: #993300', $data['event_style_attr'] );
		$this->assertStringContainsString( '--event-primary-dark-contrast: #FFFFFF', $data['event_style_attr'] );
	}

	/**
	 * The hero background uses the event primary color variable.
	 */
	public function test_event_hero_uses_primary_color_variable() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event-base.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-hero \{.*?var\(--event-hero-bg/s', $stylesheet );
		$this->assertStringContainsString( 'linear-gradient(135deg, #8f0a05 0%, #D51007 52%, #f15b53 100%)', $stylesheet );
	}

	/**
	 * Events without a primary color keep the FOSSASIA red fallback.
	 */
	public function test_event_hero_uses_fossasia_red_when_no_custom_color_is_set() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event-base.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.
		$data       = Wpfaevent_Event_Template_Controller::get_event_template_data( $this->event_id );

		$this->assertNotFalse( $stylesheet );
		$this->assertStringContainsString( '--event-primary: var(--brand, #D51007);', $stylesheet );
		$this->assertStringContainsString( '--event-hero-bg: linear-gradient(135deg, #8f0a05 0%, #D51007 52%, #f15b53 100%);', $stylesheet );
		$this->assertSame( '', $data['event_style_attr'] );
	}

	/**
	 * A light primary color receives dark text for accessible hero contrast.
	 */
	public function test_light_primary_color_uses_dark_contrast_text() {
		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#FDE68A' );

		$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $this->event_id );

		$this->assertSame( '#000000', Wpfaevent_Meta_Event::get_contrast_text_color( '#FDE68A' ) );
		$this->assertStringContainsString( '--event-primary: #FDE68A; --event-primary-contrast: #000000', $data['event_style_attr'] );
	}

	/**
	 * Event colors support only opaque CSS color formats.
	 */
	public function test_event_color_sanitizer_rejects_transparent_and_invalid_colors() {
		$transparent_colors = array(
			'rgba(0, 0, 0, 0)',
			'rgba(0, 0, 0, 0.32)',
			'rgba(255, 255, 255, 0.28)',
			'rgba(119, 119, 119, 0.5)',
		);

		foreach ( $transparent_colors as $color ) {
			$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_color_value( $color ) );
			$this->assertSame( '#FFFFFF', Wpfaevent_Meta_Event::get_contrast_text_color( $color ) );
		}

		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_color_value( 'rgb(256, 0, 0)' ) );
		$this->assertSame( 'rgb(47, 143, 91)', Wpfaevent_Meta_Event::sanitize_color_value( 'rgb(47,143,91)' ) );
	}

	/**
	 * Hero text contrast is chosen against its solid, rendered event color.
	 */
	public function test_event_hero_contrast_uses_solid_primary_color() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event-base.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-hero \{\s*background: var\(--event-hero-bg/s', $stylesheet );
		$this->assertStringNotContainsString( 'linear-gradient(135deg, rgba(0, 0, 0, 0.32)', $stylesheet );

		$this->assertSame( '#000000', Wpfaevent_Meta_Event::get_contrast_text_color( '#777777' ) );
		$this->assertSame( '#FFFFFF', Wpfaevent_Meta_Event::get_contrast_text_color( '#000000' ) );
		$this->assertSame( '#000000', Wpfaevent_Meta_Event::get_contrast_text_color( '#FFFFFF' ) );
		$this->assertSame( '#FFFFFF', Wpfaevent_Meta_Event::get_contrast_text_color( '#D51007' ) );
		$this->assertSame( '#000000', Wpfaevent_Meta_Event::get_contrast_text_color( '#F97316' ) );
	}

	/**
	 * Centralized style attribute generator produces complete variables.
	 */
	public function test_build_event_style_attribute_generates_consistent_variables() {
		$this->assertSame( '', Wpfaevent_Meta_Event::build_event_style_attribute( $this->event_id ) );

		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#0073AA' );
		update_post_meta( $this->event_id, 'wpfa_event_theme_background_color', '#F0F4F8' );
		update_post_meta( $this->event_id, 'wpfa_event_theme_success_color', '#2F8F5B' );
		update_post_meta( $this->event_id, 'wpfa_event_theme_danger_color', '#DC2626' );

		$style_attr = Wpfaevent_Meta_Event::build_event_style_attribute( $this->event_id );
		$this->assertStringContainsString( '--event-primary: #0073AA', $style_attr );
		$this->assertStringContainsString( '--event-primary-dark: #006291', $style_attr );
		$this->assertStringContainsString( '--event-primary-contrast: #FFFFFF', $style_attr );
		$this->assertStringContainsString( '--event-primary-dark-contrast: #FFFFFF', $style_attr );
		$this->assertStringContainsString( '--event-hero-bg: #0073AA', $style_attr );
		$this->assertStringContainsString( '--event-soft: #F0F4F8', $style_attr );
		$this->assertStringContainsString( '--event-success: #2F8F5B', $style_attr );
		$this->assertStringContainsString( '--event-danger: #DC2626', $style_attr );
	}

	/**
	 * Centralized palette generator computes all color variants and fallbacks.
	 */
	public function test_get_effective_event_colors() {
		$empty_palette = Wpfaevent_Meta_Event::get_effective_event_colors( $this->event_id );
		$this->assertSame( '', $empty_palette['primary'] );
		$this->assertSame( '#FFFFFF', $empty_palette['primary_contrast'] );
		$this->assertSame( '', $empty_palette['dark'] );

		update_post_meta( $this->event_id, 'wpfa_event_primary_color', '#FDE68A' );
		$palette = Wpfaevent_Meta_Event::get_effective_event_colors( $this->event_id );
		$this->assertSame( '#FDE68A', $palette['primary'] );
		$this->assertSame( '#000000', $palette['primary_contrast'] );
		$this->assertSame( '#D7C375', $palette['dark'] );
		$this->assertSame( '#000000', $palette['dark_contrast'] );
	}

	/**
	 * Admin color metabox uses external CSS classes and emits no inline scripts.
	 */
	public function test_admin_event_colors_metabox_uses_css_classes_without_inline_script() {
		ob_start();
		( new Wpfaevent_Admin_Event_Metabox() )->render_event_colors_meta_box( get_post( $this->event_id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="wpfaevent-color-field-group"', $output );
		$this->assertStringContainsString( 'class="wpfaevent-color-picker-input"', $output );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringNotContainsString( 'oninput=', $output );
	}

	/**
	 * Hero with header image enforces white text over the dark overlay.
	 */
	public function test_event_hero_with_header_image_preserves_white_text() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-hero\.has-event-header-image.*?color:\s*#fff;/s', $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-hero\.has-event-header-image h1.*?color:\s*#fff;/s', $stylesheet );
	}

	/**
	 * Ticket section background uses the soft background variable.
	 */
	public function test_ticket_section_uses_theme_background_color_variable() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/event.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-event-tickets \{\s*background:\s*var\(--event-soft\);/s', $stylesheet );
	}

	/**
	 * Schedule controls use the primary color variable and avoid hardcoded dark navy.
	 */
	public function test_schedule_controls_use_theme_color_variables() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/schedule.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertStringNotContainsString( '.wpfa-schedule-view-switch a.is-active {\n\tbackground: #17233a;', $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-schedule-view-switch a\.is-active \{\s*background:\s*var\(--event-primary\);/s', $stylesheet );
		$this->assertMatchesRegularExpression( '/\.wpfaevent \.wpfa-schedule-filter-form button \{\s*background:\s*var\(--event-primary\);/s', $stylesheet );
	}

	/**
	 * Speakers template declares theme variables and links brand to event primary.
	 */
	public function test_speakers_template_declares_theme_variables() {
		$stylesheet = file_get_contents( dirname( __DIR__, 2 ) . '/public/css/templates/speakers.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a unit test.

		$this->assertNotFalse( $stylesheet );
		$this->assertStringContainsString( '--event-primary: var(--brand, #D51007);', $stylesheet );
		$this->assertStringContainsString( '--brand: var(--event-primary);', $stylesheet );
	}
}
