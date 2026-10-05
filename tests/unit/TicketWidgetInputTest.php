<?php
/**
 * Class TicketWidgetInputTest
 *
 * @package Wpfaevent
 */

/**
 * Unit tests for Eventyay ticket widget embed code and URL sanitization.
 */
class TicketWidgetInputTest extends WP_UnitTestCase {

	/**
	 * Test sanitization of full HTML embed snippet copied from Eventyay widget settings.
	 */
	public function test_sanitize_html_embed_full_snippet() {
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Test fixture string for widget HTML snippet.
		$snippet = '<link rel="stylesheet" type="text/css" href="https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.css">' . "\n"
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Test fixture string for widget HTML snippet.
			. '<script type="text/javascript" src="https://dev.eventyay.com/widget/v1.en.js" async></script>' . "\n"
			. '<eventyay-widget event="https://dev.eventyay.com/fossasia/7xrpkx/"></eventyay-widget>' . "\n"
			. '<noscript>' . "\n"
			. '   <div class="pretix-widget">' . "\n"
			. '        <div class="pretix-widget-info-message">' . "\n"
			. '            JavaScript is disabled in your browser. To access our ticket shop without JavaScript, please <a target="_blank" rel="noopener" href="https://dev.eventyay.com/fossasia/7xrpkx/">click here</a>.' . "\n"
			. '        </div>' . "\n"
			. '    </div>' . "\n"
			. '</noscript>';

		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of an individual eventyay-widget tag.
	 */
	public function test_sanitize_html_eventyay_widget_tag_only() {
		$snippet   = '<eventyay-widget event="https://dev.eventyay.com/fossasia/7xrpkx/"></eventyay-widget>';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of pretix-widget-compat div element.
	 */
	public function test_sanitize_html_pretix_widget_compat_div() {
		$snippet   = '<div class="pretix-widget-compat" event="https://dev.eventyay.com/fossasia/7xrpkx/"></div>';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of pretix-widget tag.
	 */
	public function test_sanitize_html_pretix_widget_tag() {
		$snippet   = '<pretix-widget event="https://eventyay.com/e/c897bfda/"></pretix-widget>';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://eventyay.com/e/c897bfda/', $sanitized );
	}

	/**
	 * Test sanitization when only the stylesheet link tag is provided.
	 */
	public function test_sanitize_html_link_tag_only() {
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Test fixture string for widget HTML link tag.
		$snippet   = '<link rel="stylesheet" type="text/css" href="https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.css">';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of Markdown link syntax.
	 */
	public function test_sanitize_markdown_link() {
		$snippet   = '[Tickets](https://dev.eventyay.com/fossasia/7xrpkx/)';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of Markdown image link syntax.
	 */
	public function test_sanitize_markdown_image_link() {
		$snippet   = '[![Buy Tickets](https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.png)](https://dev.eventyay.com/fossasia/7xrpkx/)';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of Markdown autolink syntax.
	 */
	public function test_sanitize_markdown_autolink() {
		$snippet   = '<https://dev.eventyay.com/fossasia/7xrpkx/>';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $snippet );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of direct URLs with and without trailing slashes.
	 */
	public function test_sanitize_direct_url_with_and_without_trailing_slash() {
		$url_without_slash = 'https://dev.eventyay.com/fossasia/7xrpkx';
		$sanitized         = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $url_without_slash );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );

		$url_with_slash = 'https://dev.eventyay.com/fossasia/7xrpkx/';
		$sanitized      = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $url_with_slash );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of direct asset URL stripping asset path.
	 */
	public function test_sanitize_direct_asset_url() {
		$css_url   = 'https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.css';
		$sanitized = Wpfaevent_Meta_Event::sanitize_ticket_widget_input( $css_url );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $sanitized );
	}

	/**
	 * Test sanitization of invalid and empty inputs.
	 */
	public function test_sanitize_invalid_and_empty_inputs() {
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( '' ) );
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( '   ' ) );
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( 'plain text with no link' ) );
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( array( 'not-a-string' ) ) );
		// Insecure HTTP should be rejected.
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( 'http://dev.eventyay.com/fossasia/7xrpkx/' ) );
		// Unapproved origins should be rejected.
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( 'https://malicious.example.com/fossasia/7xrpkx/' ) );
		// Script-only embed without an event URL should be rejected.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Test fixture string for widget HTML snippet.
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( '<script type="text/javascript" src="https://dev.eventyay.com/widget/v1.en.js" async></script>' ) );
		// Root domain without an event path should be rejected.
		$this->assertSame( '', Wpfaevent_Meta_Event::sanitize_ticket_widget_input( 'https://dev.eventyay.com/' ) );
	}

	/**
	 * Test that registering wpfa_event_ticket_widget_url post meta runs the sanitizer.
	 */
	public function test_post_meta_sanitization_callback() {
		$post_id = $this->factory->post->create(
			array(
				'post_title' => 'Test Ticket Widget Event',
				'post_type'  => 'wpfa_event',
			)
		);

		$embed_code = '<eventyay-widget event="https://dev.eventyay.com/fossasia/7xrpkx/"></eventyay-widget>';
		update_post_meta( $post_id, 'wpfa_event_ticket_widget_url', $embed_code );

		$saved_value = get_post_meta( $post_id, 'wpfa_event_ticket_widget_url', true );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $saved_value );
	}

	/**
	 * Test template controller resolves widget assets when post meta has full HTML embed snippet.
	 */
	public function test_template_controller_resolves_widget_assets_from_html_snippet() {
		$post_id = $this->factory->post->create(
			array(
				'post_title' => 'Test Full HTML Widget Event',
				'post_type'  => 'wpfa_event',
			)
		);

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Test fixture string for widget HTML snippet.
		$snippet = '<link rel="stylesheet" type="text/css" href="https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.css">' . "\n"
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Test fixture string for widget HTML snippet.
			. '<script type="text/javascript" src="https://dev.eventyay.com/widget/v1.en.js" async></script>' . "\n"
			. '<eventyay-widget event="https://dev.eventyay.com/fossasia/7xrpkx/"></eventyay-widget>';

		update_post_meta( $post_id, 'wpfa_event_ticket_widget_url', $snippet );

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $post_id );

		$this->assertTrue( $template_data['show_ticket_section'] );
		$this->assertTrue( $template_data['show_ticket_widget'] );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/', $template_data['ticket_widget_assets']['event_url'] );
		$this->assertSame( 'https://dev.eventyay.com/fossasia/7xrpkx/widget/v1.css', $template_data['ticket_widget_assets']['css_url'] );
		$this->assertSame( 'https://dev.eventyay.com/widget/v1.en.js', $template_data['ticket_widget_assets']['script_url'] );
	}

	/**
	 * Test that clearing ticket widget meta does not restore stale site-settings JSON.
	 */
	public function test_template_controller_does_not_restore_stale_site_settings_when_meta_cleared() {
		$post_id = $this->factory->post->create(
			array(
				'post_title' => 'Test Cleared Widget Event',
				'post_type'  => 'wpfa_event',
			)
		);

		update_post_meta( $post_id, 'wpfa_event_ticket_widget_url', '' );

		$template_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $post_id );
		$this->assertFalse( $template_data['show_ticket_widget'] );
		$this->assertEmpty( $template_data['ticket_widget_assets'] );
	}
}
