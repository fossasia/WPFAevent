<?php
/**
 * Unit tests for WPFA template shortcodes.
 *
 * @package Wpfaevent
 */

/**
 * Shortcode registration and rendering must behave the same after being
 * moved out of Wpfaevent_Templates.
 */
class TemplateShortcodesTest extends WP_UnitTestCase {

	/**
	 * Every template shortcode and the generic shortcode are registered.
	 */
	public function test_registers_all_template_shortcodes() {
		Wpfaevent_Template_Shortcodes::register();

		foreach ( Wpfaevent_Templates::get_templates() as $template ) {
			$this->assertTrue( shortcode_exists( $template['shortcode'] ), "{$template['shortcode']} should be registered" );
		}

		$this->assertTrue( shortcode_exists( 'wpfaevent_template' ) );
	}

	/**
	 * A template shortcode renders the same markup as the embed renderer.
	 */
	public function test_template_shortcode_renders_embed() {
		Wpfaevent_Template_Shortcodes::register();

		$this->assertSame(
			Wpfaevent_Templates::render_embed( 'code_of_conduct' ),
			do_shortcode( '[wpfaevent_code_of_conduct]' )
		);
	}

	/**
	 * The generic shortcode resolves its template attribute.
	 */
	public function test_generic_shortcode_resolves_template_attribute() {
		Wpfaevent_Template_Shortcodes::register();

		$this->assertSame(
			Wpfaevent_Templates::render_embed( 'code_of_conduct' ),
			do_shortcode( '[wpfaevent_template template="code-of-conduct"]' )
		);
	}

	/**
	 * Without a template attribute the generic shortcode renders the events template.
	 */
	public function test_generic_shortcode_defaults_to_events_template() {
		Wpfaevent_Template_Shortcodes::register();

		$output = do_shortcode( '[wpfaevent_template]' );

		$this->assertStringContainsString( 'wpfa-events', $output );
		$this->assertSame( Wpfaevent_Templates::render_embed( 'events' ), $output );
	}

	/**
	 * The default events template keeps its block class when aligned.
	 */
	public function test_generic_shortcode_default_template_wraps_aligned_output() {
		Wpfaevent_Template_Shortcodes::register();

		$this->assertStringStartsWith(
			'<div class="wp-block-wpfaevent-events alignwide">',
			do_shortcode( '[wpfaevent_template align="wide"]' )
		);
	}

	/**
	 * Wide and full alignment wrap the output in an aligned block container.
	 */
	public function test_align_attribute_wraps_output() {
		Wpfaevent_Template_Shortcodes::register();

		$output = do_shortcode( '[wpfaevent_code_of_conduct align="full"]' );

		$this->assertStringStartsWith( '<div class="wp-block-wpfaevent-code-of-conduct alignfull">', $output );
	}

	/**
	 * Unsupported alignment values are ignored.
	 */
	public function test_unsupported_align_is_ignored() {
		Wpfaevent_Template_Shortcodes::register();

		$this->assertSame(
			Wpfaevent_Templates::render_embed( 'code_of_conduct' ),
			do_shortcode( '[wpfaevent_code_of_conduct align="left"]' )
		);
	}

	/**
	 * An unknown template renders nothing.
	 */
	public function test_unknown_generic_template_renders_empty() {
		Wpfaevent_Template_Shortcodes::register();

		$this->assertSame( '', do_shortcode( '[wpfaevent_template template="does-not-exist"]' ) );
	}

	/**
	 * Shortcodes stay hooked through the legacy callback, so integrations can
	 * still unhook them with remove_action().
	 */
	public function test_init_hook_uses_legacy_callback() {
		$this->assertNotFalse( has_action( 'init', array( 'Wpfaevent_Templates', 'register_shortcodes' ) ) );
	}

	/**
	 * Removing the legacy init callback stops shortcode registration, because
	 * no other init callback registers the shortcodes.
	 */
	public function test_removing_legacy_init_callback_unhooks_shortcodes() {
		$legacy_callback = array( 'Wpfaevent_Templates', 'register_shortcodes' );

		$this->assertTrue( remove_action( 'init', $legacy_callback ) );
		$this->assertFalse( has_action( 'init', $legacy_callback ) );
		$this->assertFalse( has_action( 'init', array( 'Wpfaevent_Template_Shortcodes', 'register' ) ) );
	}

	/**
	 * The legacy Wpfaevent_Templates::render_shortcode() entry point still works.
	 */
	public function test_legacy_render_shortcode_delegates() {
		$this->assertSame(
			Wpfaevent_Template_Shortcodes::render( array(), '', 'wpfaevent_code_of_conduct' ),
			Wpfaevent_Templates::render_shortcode( array(), '', 'wpfaevent_code_of_conduct' )
		);
	}
}
