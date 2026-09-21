<?php
/**
 * Unit tests for header navigation and nav helper.
 *
 * @package Wpfaevent
 */

/**
 * Test case for Wpfaevent_Main_Navigation_Helper and Wpfaevent_Meta_Event navigation sanitization.
 */
class MainNavigationTest extends WP_UnitTestCase {

	/**
	 * Test that default navigation outputs Upcoming Events, Past Events, and Code of Conduct.
	 */
	public function test_default_navigation_renders_expected_links() {
		ob_start();
		Wpfaevent_Main_Navigation_Helper::render_default_navigation(
			array(
				'events_url'            => 'http://example.com/events/',
				'past_events_url'       => 'http://example.com/events/?filter=past',
				'coc_url'               => 'http://example.com/code-of-conduct/',
				'is_events_active'      => 'active',
				'is_past_events_active' => '',
				'is_coc_active'         => '',
			)
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'http://example.com/events/', $output );
		$this->assertStringContainsString( 'Upcoming Events', $output );
		$this->assertStringContainsString( 'http://example.com/events/?filter=past', $output );
		$this->assertStringContainsString( 'Past Events', $output );
		$this->assertStringContainsString( 'http://example.com/code-of-conduct/', $output );
		$this->assertStringContainsString( 'Code of Conduct', $output );
		$this->assertStringContainsString( 'class="active"', $output );
	}

	/**
	 * Test sanitization of custom navigation data structure.
	 */
	public function test_sanitize_custom_navigation() {
		$raw_items = array(
			array(
				'text' => '<b>Register</b>',
				'type' => 'link',
				'href' => 'https://eventyay.com/e/12345',
			),
			array(
				'text'  => 'About',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => 'Venue & Travel',
						'href' => 'https://example.com/venue',
					),
					array(
						'text' => 'Code of Conduct',
						'href' => '/code-of-conduct/',
					),
					array(
						'text'    => 'Fund Info',
						'type'    => 'custom_page',
						'title'   => 'Fund Information',
						'content' => "- Travel Grants\n- Stipends",
					),
					array(
						'text'    => 'About Page',
						'type'    => 'page',
						'page_id' => 999,
					),
					array(
						'text' => '',
						'href' => '',
					),
				),
			),
			array(
				'text' => '',
				'type' => 'link',
				'href' => '',
			),
		);

		$sanitized = Wpfaevent_Meta_Event::sanitize_custom_navigation( $raw_items );

		$this->assertCount( 2, $sanitized );
		$this->assertSame( 'Register', $sanitized[0]['text'] );
		$this->assertSame( 'link', $sanitized[0]['type'] );
		$this->assertSame( 'https://eventyay.com/e/12345', $sanitized[0]['href'] );

		$this->assertSame( 'About', $sanitized[1]['text'] );
		$this->assertSame( 'dropdown', $sanitized[1]['type'] );
		$this->assertCount( 4, $sanitized[1]['items'] );
		$this->assertSame( 'Venue & Travel', $sanitized[1]['items'][0]['text'] );
		$this->assertSame( 'https://example.com/venue', $sanitized[1]['items'][0]['href'] );
		$this->assertSame( 'Code of Conduct', $sanitized[1]['items'][1]['text'] );
		$this->assertSame( '/code-of-conduct/', $sanitized[1]['items'][1]['href'] );
		$this->assertSame( 'Fund Info', $sanitized[1]['items'][2]['text'] );
		$this->assertSame( 'custom_page', $sanitized[1]['items'][2]['type'] );
		$this->assertSame( 'fund-information', $sanitized[1]['items'][2]['slug'] );
		$this->assertSame( '?custom_page=fund-information', $sanitized[1]['items'][2]['href'] );
		$this->assertSame( 'About Page', $sanitized[1]['items'][3]['text'] );
		$this->assertSame( 'page', $sanitized[1]['items'][3]['type'] );
	}

	/**
	 * Test that custom navigation renders links and dropdown sub-items.
	 */
	public function test_render_custom_nav_items_with_dropdown() {
		$items = array(
			array(
				'text' => 'Register',
				'type' => 'link',
				'href' => 'https://eventyay.com/e/test',
			),
			array(
				'text'  => 'About',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => 'Venue',
						'href' => 'https://example.com/venue',
					),
					array(
						'text' => 'Code of Conduct',
						'href' => 'https://example.com/coc',
					),
				),
			),
		);

		ob_start();
		Wpfaevent_Main_Navigation_Helper::render_custom_nav_items( $items );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<a href="https://eventyay.com/e/test"', $output );
		$this->assertStringContainsString( 'Register</a>', $output );
		$this->assertStringContainsString( '<div class="nav-dropdown">', $output );
		$this->assertStringContainsString( '<button type="button" class="nav-dropdown-toggle"', $output );
		$this->assertStringContainsString( 'About', $output );
		$this->assertStringContainsString( '<div class="nav-dropdown-content">', $output );
		$this->assertStringContainsString( '<a href="https://example.com/venue"', $output );
		$this->assertStringContainsString( 'Venue', $output );
		$this->assertStringContainsString( '<a href="https://example.com/coc"', $output );
		$this->assertStringContainsString( 'Code of Conduct', $output );
	}

	/**
	 * Test is_url_active URL matching.
	 */
	public function test_is_url_active() {
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::is_url_active( 'http://example.com/events/', '/events/' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::is_url_active( '/events/?filter=past', '/events/?filter=past' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::is_url_active( '/events/?foo=1&bar=2', '/events/?bar=2&foo=1' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::is_url_active( '/events/', '/events/?filter=past' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::is_url_active( '', '/events/' ) );
	}

	/**
	 * Test that get_default_nav_items returns the 3 expected items.
	 */
	public function test_get_default_nav_items() {
		$defaults = Wpfaevent_Main_Navigation_Helper::get_default_nav_items();

		$this->assertCount( 3, $defaults );
		$this->assertSame( 'Upcoming Events', $defaults[0]['text'] );
		$this->assertSame( 'link', $defaults[0]['type'] );
		$this->assertStringContainsString( '/events/', $defaults[0]['href'] );

		$this->assertSame( 'Past Events', $defaults[1]['text'] );
		$this->assertSame( 'link', $defaults[1]['type'] );
		$this->assertStringContainsString( 'filter=past', $defaults[1]['href'] );

		$this->assertSame( 'Code of Conduct', $defaults[2]['text'] );
		$this->assertSame( 'link', $defaults[2]['type'] );
		$this->assertStringContainsString( '/code-of-conduct/', $defaults[2]['href'] );
	}

	/**
	 * Test that render_navigation falls back to global option when event nav is not set.
	 */
	public function test_render_navigation_uses_global_option() {
		update_option(
			'wpfaevent_header_navigation',
			array(
				array(
					'text' => 'Custom Global Link',
					'type' => 'link',
					'href' => 'https://example.com/custom',
				),
			)
		);

		ob_start();
		Wpfaevent_Main_Navigation_Helper::render_navigation();
		$output = ob_get_clean();

		delete_option( 'wpfaevent_header_navigation' );

		$this->assertStringContainsString( 'Custom Global Link', $output );
		$this->assertStringContainsString( 'https://example.com/custom', $output );
	}

	/**
	 * Test that get_latest_custom_navigation retrieves the most recent custom event navigation.
	 */
	public function test_get_latest_custom_navigation() {
		$event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
		$nav_data = array(
			array(
				'text' => 'Event Specific Link',
				'type' => 'link',
				'href' => 'https://example.com/event-link',
			),
		);

		update_post_meta( $event_id, 'wpfa_event_custom_navigation', $nav_data );

		$latest = Wpfaevent_Main_Navigation_Helper::get_latest_custom_navigation();
		$this->assertIsArray( $latest );
		$this->assertSame( 'Event Specific Link', $latest[0]['text'] );
	}

	/**
	 * Test has_custom_page and get_custom_page resolution.
	 */
	public function test_get_and_has_custom_page() {
		$event_id = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
		$nav_data = array(
			array(
				'text'    => 'Top Level Info',
				'type'    => 'custom_page',
				'title'   => 'Top Level Title',
				'slug'    => 'top-level-info',
				'content' => 'Top content',
			),
			array(
				'text'  => 'Menu',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'    => 'Nested Info',
						'type'    => 'custom_page',
						'title'   => 'Nested Title',
						'slug'    => 'nested-info',
						'content' => 'Nested content',
					),
				),
			),
		);

		update_post_meta( $event_id, 'wpfa_event_custom_navigation', $nav_data );

		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_id, 'top-level-info' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_id, 'nested-info' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_id, 'non-existent' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( 0, 'top-level-info' ) );

		$top = Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_id, 'top-level-info' );
		$this->assertIsArray( $top );
		$this->assertSame( 'Top Level Title', $top['title'] );

		$nested = Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_id, 'nested-info' );
		$this->assertIsArray( $nested );
		$this->assertSame( 'Nested Title', $nested['title'] );

		$missing = Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_id, 'unknown-slug' );
		$this->assertNull( $missing );
	}
}
