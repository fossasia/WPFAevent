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
	 * Test that render_navigation renders default navigation links.
	 */
	public function test_render_navigation() {
		ob_start();
		Wpfaevent_Main_Navigation_Helper::render_navigation();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Upcoming Events', $output );
		$this->assertStringContainsString( 'Past Events', $output );
		$this->assertStringContainsString( 'Code of Conduct', $output );
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

		$_GET['custom_page'] = 'nested-info';
		$current             = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_id );
		$this->assertIsArray( $current );
		$this->assertSame( 'Nested Title', $current['title'] );
		unset( $_GET['custom_page'] );
	}

	/**
	 * Test that duplicate custom page titles or slugs get unique numeric suffixes.
	 */
	public function test_sanitize_custom_navigation_resolves_slug_collisions() {
		$raw_items = array(
			array(
				'text'  => 'First Page',
				'type'  => 'custom_page',
				'title' => 'Fund Info',
			),
			array(
				'text'  => 'Second Page',
				'type'  => 'custom_page',
				'title' => 'Fund Info',
			),
			array(
				'text'  => 'Dropdown',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'  => 'Third Page',
						'type'  => 'custom_page',
						'title' => 'Fund Info',
					),
				),
			),
		);

		$sanitized = Wpfaevent_Meta_Event::sanitize_custom_navigation( $raw_items );

		$this->assertSame( 'fund-info', $sanitized[0]['slug'] );
		$this->assertSame( '?custom_page=fund-info', $sanitized[0]['href'] );
		$this->assertSame( 'fund-info-2', $sanitized[1]['slug'] );
		$this->assertSame( '?custom_page=fund-info-2', $sanitized[1]['href'] );
		$this->assertSame( 'fund-info-3', $sanitized[2]['items'][0]['slug'] );
		$this->assertSame( '?custom_page=fund-info-3', $sanitized[2]['items'][0]['href'] );
	}

	/**
	 * Test that default event navigation items contain Overview, Speakers, Schedule, Sponsors, and Exhibitors.
	 */
	public function test_get_default_event_nav_items() {
		$items = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items();

		$this->assertCount( 5, $items );
		$this->assertSame( 'Overview', $items[0]['text'] );
		$this->assertSame( '#about', $items[0]['href'] );
		$this->assertSame( 'Speakers', $items[1]['text'] );
		$this->assertSame( '#speakers', $items[1]['href'] );
		$this->assertSame( 'Schedule', $items[2]['text'] );
		$this->assertSame( '#schedule-overview', $items[2]['href'] );
		$this->assertSame( 'Sponsors', $items[3]['text'] );
		$this->assertSame( '#sponsors', $items[3]['href'] );
		$this->assertSame( 'Exhibitors', $items[4]['text'] );
		$this->assertSame( '#exhibitors', $items[4]['href'] );
	}

	/**
	 * Test event section navigation partial renders items, dropdowns, and custom page links.
	 */
	public function test_event_section_nav_partial() {
		$event_id             = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
		$wpfa_event_nav_items = array(
			array(
				'text' => 'Overview',
				'type' => 'link',
				'href' => '#about',
			),
			array(
				'text'  => 'More Info',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => 'Fund Info',
						'type' => 'custom_page',
						'slug' => 'fund-info',
						'href' => '?custom_page=fund-info',
					),
				),
			),
		);

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'wpfa-event-section-nav', $output );
		$this->assertStringContainsString( 'Overview', $output );
		$this->assertStringContainsString( '#about', $output );
		$this->assertStringContainsString( 'More Info', $output );
		$this->assertStringContainsString( 'Fund Info', $output );
		$this->assertStringContainsString( 'custom_page=fund-info', $output );
	}

	/**
	 * Test event section navigation qualifies anchors and activates current page on custom page.
	 */
	public function test_event_section_nav_on_custom_page() {
		$event_id             = $this->factory->post->create(
			array(
				'post_type' => 'wpfa_event',
				'post_name' => 'summit-2026',
			)
		);
		$wpfa_event_nav_items = array(
			array(
				'text' => 'Overview',
				'type' => 'link',
				'href' => '#about',
			),
			array(
				'text'  => 'More Info',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => 'Fund Info',
						'type' => 'custom_page',
						'slug' => 'fund-info',
						'href' => '?custom_page=fund-info',
					),
				),
			),
		);

		$_GET['custom_page'] = 'fund-info';

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$output = ob_get_clean();

		unset( $_GET['custom_page'] );

		// Anchors should be prefixed with the event permalink when on a custom page.
		$permalink = get_permalink( $event_id );
		$this->assertStringContainsString( esc_url( $permalink . '#about' ), $output );
		// The dropdown and custom page item should be marked active.
		$this->assertStringContainsString( 'nav-dropdown active', $output );
		$this->assertStringContainsString( 'nav-dropdown-toggle active', $output );
	}

	/**
	 * Test that header.php renders the 3 static uncustomizable navigation links.
	 */
	public function test_header_renders_static_navigation_links() {
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Upcoming Events', $output );
		$this->assertStringContainsString( 'Past Events', $output );
		$this->assertStringContainsString( 'Code of Conduct', $output );
	}

	/**
	 * Regression test for event isolation: verify that multiple events with different
	 * custom navigations only return their own navigation and do not leak across events.
	 */
	public function test_event_navigation_isolation_between_events() {
		$event_a_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Alpha',
			)
		);
		$event_b_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Beta',
			)
		);
		$event_c_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Gamma',
			)
		);

		$nav_items_a = array(
			array(
				'text' => 'Alpha Overview',
				'type' => 'link',
				'href' => '#alpha-about',
			),
			array(
				'text'    => 'Alpha Venue',
				'type'    => 'custom_page',
				'title'   => 'Alpha Venue Information',
				'slug'    => 'alpha-venue',
				'content' => 'Venue details for Alpha.',
			),
			array(
				'text'  => 'Alpha Dropdown',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'    => 'Alpha Tracks',
						'type'    => 'custom_page',
						'title'   => 'Alpha Track Details',
						'slug'    => 'alpha-tracks',
						'content' => 'Tracks list for Alpha.',
					),
				),
			),
		);

		$nav_items_b = array(
			array(
				'text' => 'Beta Overview',
				'type' => 'link',
				'href' => '#beta-about',
			),
			array(
				'text'    => 'Beta Tickets',
				'type'    => 'custom_page',
				'title'   => 'Beta Ticket Information',
				'slug'    => 'beta-tickets',
				'content' => 'Ticket details for Beta.',
			),
			array(
				'text'  => 'Beta Dropdown',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'    => 'Beta Workshops',
						'type'    => 'custom_page',
						'title'   => 'Beta Workshop Details',
						'slug'    => 'beta-workshops',
						'content' => 'Workshops list for Beta.',
					),
				),
			),
		);

		update_post_meta( $event_a_id, 'wpfa_event_custom_navigation', $nav_items_a );
		update_post_meta( $event_b_id, 'wpfa_event_custom_navigation', $nav_items_b );

		// 1. Verify stored meta isolation between events.
		$stored_a = get_post_meta( $event_a_id, 'wpfa_event_custom_navigation', true );
		$stored_b = get_post_meta( $event_b_id, 'wpfa_event_custom_navigation', true );
		$stored_c = get_post_meta( $event_c_id, 'wpfa_event_custom_navigation', true );

		$this->assertSame( $nav_items_a, $stored_a );
		$this->assertSame( $nav_items_b, $stored_b );
		$this->assertEmpty( $stored_c );
		$this->assertNotSame( $stored_a, $stored_b );

		// 2. Verify has_custom_page does not cross-contaminate between events.
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'alpha-venue' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'alpha-tracks' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'beta-tickets' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'beta-workshops' ) );

		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'beta-tickets' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'beta-workshops' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'alpha-venue' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'alpha-tracks' ) );

		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_c_id, 'alpha-venue' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_c_id, 'beta-tickets' ) );

		// 3. Verify get_custom_page returns only the queried event's custom page data.
		$page_a = Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'alpha-venue' );
		$this->assertIsArray( $page_a );
		$this->assertSame( 'Alpha Venue Information', $page_a['title'] );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_b_id, 'alpha-venue' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_c_id, 'alpha-venue' ) );

		$page_b = Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_b_id, 'beta-tickets' );
		$this->assertIsArray( $page_b );
		$this->assertSame( 'Beta Ticket Information', $page_b['title'] );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'beta-tickets' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_c_id, 'beta-tickets' ) );

		// 4. Verify get_current_custom_page isolates by event ID when query param is present.
		$_GET['custom_page'] = 'alpha-venue';
		$current_a           = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_a_id );
		$current_b           = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_b_id );
		$this->assertIsArray( $current_a );
		$this->assertSame( 'Alpha Venue Information', $current_a['title'] );
		$this->assertNull( $current_b );

		$_GET['custom_page'] = 'beta-tickets';
		$current_a           = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_a_id );
		$current_b           = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_b_id );
		$this->assertNull( $current_a );
		$this->assertIsArray( $current_b );
		$this->assertSame( 'Beta Ticket Information', $current_b['title'] );
		unset( $_GET['custom_page'] );

		// 5. Verify partial rendering isolates navigation items for each event.
		$wpfa_event_nav_items = $stored_a;
		$event_id             = $event_a_id;
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$rendered_a = ob_get_clean();

		$this->assertStringContainsString( 'Alpha Overview', $rendered_a );
		$this->assertStringContainsString( 'Alpha Venue', $rendered_a );
		$this->assertStringContainsString( 'Alpha Tracks', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Overview', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Tickets', $rendered_a );

		$wpfa_event_nav_items = $stored_b;
		$event_id             = $event_b_id;
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$rendered_b = ob_get_clean();

		$this->assertStringContainsString( 'Beta Overview', $rendered_b );
		$this->assertStringContainsString( 'Beta Tickets', $rendered_b );
		$this->assertStringContainsString( 'Beta Workshops', $rendered_b );
		$this->assertStringNotContainsString( 'Alpha Overview', $rendered_b );
		$this->assertStringNotContainsString( 'Alpha Venue', $rendered_b );
	}

	/**
	 * Test that clearing an event's custom navigation deletes its post meta, falls back to default navigation,
	 * and does not retain or use navigation from another event.
	 */
	public function test_clearing_custom_navigation_falls_back_to_default_navigation() {
		$event_a_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Alpha',
			)
		);
		$event_b_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Beta',
			)
		);

		$nav_items_a = array(
			array(
				'text' => 'Alpha Custom Link',
				'type' => 'link',
				'href' => '#alpha-custom',
			),
			array(
				'text'    => 'Alpha Info',
				'type'    => 'custom_page',
				'title'   => 'Alpha Information',
				'slug'    => 'alpha-info',
				'content' => 'Alpha content.',
			),
		);

		$nav_items_b = array(
			array(
				'text' => 'Beta Custom Link',
				'type' => 'link',
				'href' => '#beta-custom',
			),
			array(
				'text'    => 'Beta Info',
				'type'    => 'custom_page',
				'title'   => 'Beta Information',
				'slug'    => 'beta-info',
				'content' => 'Beta content.',
			),
		);

		update_post_meta( $event_a_id, 'wpfa_event_custom_navigation', $nav_items_a );
		update_post_meta( $event_b_id, 'wpfa_event_custom_navigation', $nav_items_b );

		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'alpha-info' ) );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'beta-info' ) );

		// Clear Event Alpha's custom navigation (as done on save when no custom nav items are posted).
		delete_post_meta( $event_a_id, 'wpfa_event_custom_navigation' );

		// 1. Verify Event Alpha's post meta is completely removed.
		$stored_a = get_post_meta( $event_a_id, 'wpfa_event_custom_navigation', true );
		$this->assertEmpty( $stored_a );

		// 2. Verify Event Alpha falls back to default navigation items.
		$default_items = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items();
		$event_a_nav   = ( is_array( $stored_a ) && ! empty( $stored_a ) )
			? $stored_a
			: $default_items;

		$this->assertSame( $default_items, $event_a_nav );
		$this->assertSame( 'Overview', $event_a_nav[0]['text'] );
		$this->assertSame( 'Speakers', $event_a_nav[1]['text'] );
		$this->assertSame( 'Schedule', $event_a_nav[2]['text'] );
		$this->assertSame( 'Sponsors', $event_a_nav[3]['text'] );
		$this->assertSame( 'Exhibitors', $event_a_nav[4]['text'] );

		// 3. Verify Event Alpha does not have its old custom pages or Event Beta's custom pages.
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'alpha-info' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'alpha-info' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'beta-info' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'beta-info' ) );

		// 4. Verify Event Beta is completely unaffected and retains its own navigation.
		$stored_b = get_post_meta( $event_b_id, 'wpfa_event_custom_navigation', true );
		$this->assertSame( $nav_items_b, $stored_b );
		$this->assertTrue( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_b_id, 'beta-info' ) );
		$this->assertIsArray( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_b_id, 'beta-info' ) );

		// 5. Verify partial rendering of Event Alpha uses default links and does not leak Event Beta's links.
		$wpfa_event_nav_items = $event_a_nav;
		$event_id             = $event_a_id;
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$rendered_a = ob_get_clean();

		$this->assertStringContainsString( 'Overview', $rendered_a );
		$this->assertStringContainsString( 'Speakers', $rendered_a );
		$this->assertStringContainsString( 'Schedule', $rendered_a );
		$this->assertStringContainsString( 'Sponsors', $rendered_a );
		$this->assertStringContainsString( 'Exhibitors', $rendered_a );
		$this->assertStringNotContainsString( 'Alpha Custom Link', $rendered_a );
		$this->assertStringNotContainsString( 'Alpha Info', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Custom Link', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Info', $rendered_a );
	}

	/**
	 * Test custom page ownership: ensure that a custom_page belonging to one event
	 * cannot be resolved as a custom page for another event during template loading.
	 */
	public function test_custom_page_ownership_cannot_be_resolved_by_another_event() {
		if ( ! class_exists( 'Wpfaevent_Templates' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Templates class not available.' );
		}

		$event_a_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Alpha',
			)
		);
		$event_b_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Beta',
			)
		);

		update_post_meta(
			$event_a_id,
			'wpfa_event_custom_navigation',
			array(
				array(
					'text'    => 'Alpha Guide',
					'type'    => 'custom_page',
					'title'   => 'Alpha Guide Title',
					'slug'    => 'alpha-guide',
					'content' => 'Alpha guide content.',
				),
			)
		);

		// 1. Requesting Event B with Event A's custom page slug must NOT resolve to page-event-custom.php.
		$this->go_to( add_query_arg( 'custom_page', 'alpha-guide', get_permalink( $event_b_id ) ) );
		$_GET['custom_page'] = 'alpha-guide';
		$resolved_template   = Wpfaevent_Templates::load( 'single.php' );
		$this->assertSame( WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php', $resolved_template );

		// 2. Requesting Event A with its own custom page slug MUST resolve to page-event-custom.php.
		$this->go_to( add_query_arg( 'custom_page', 'alpha-guide', get_permalink( $event_a_id ) ) );
		$_GET['custom_page'] = 'alpha-guide';
		$resolved_template   = Wpfaevent_Templates::load( 'single.php' );
		$this->assertSame( WPFAEVENT_PATH . 'public/templates/page-event-custom.php', $resolved_template );

		unset( $_GET['custom_page'] );
	}
}
