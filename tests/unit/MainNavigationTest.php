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
				'href'    => '?custom_page=alpha-venue',
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
						'href'    => '?custom_page=alpha-tracks',
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
				'href'    => '?custom_page=beta-tickets',
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
						'href'    => '?custom_page=beta-workshops',
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
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Event Alpha',
				'post_content' => 'Overview content for Event Alpha.',
			)
		);
		$event_b_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Event Beta',
				'post_content' => 'Overview content for Event Beta.',
			)
		);

		$speaker_a_id = $this->factory->post->create(
			array(
				'post_type'   => 'wpfa_speaker',
				'post_status' => 'publish',
				'post_title'  => 'Speaker Alpha',
			)
		);
		update_post_meta( $event_a_id, 'wpfa_event_speakers', array( $speaker_a_id ) );

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
				'href'    => '?custom_page=alpha-info',
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
				'href'    => '?custom_page=beta-info',
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

		// 2. Verify Event Alpha falls back to default navigation items via the event template controller.
		$event_a_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_a_id );
		$this->assertArrayHasKey( 'wpfa_event_nav_items', $event_a_data );
		$this->assertNotEmpty( $event_a_data['wpfa_event_nav_items'] );
		$event_a_nav = $event_a_data['wpfa_event_nav_items'];

		$nav_texts = array_column( $event_a_nav, 'text' );
		$this->assertContains( 'Overview', $nav_texts );
		$this->assertContains( 'Speakers', $nav_texts );
		$this->assertNotContains( 'Alpha Custom Link', $nav_texts );
		$this->assertNotContains( 'Alpha Info', $nav_texts );

		// 3. Verify Event Alpha does not have its old custom pages or Event Beta's custom pages.
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'alpha-info' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'alpha-info' ) );
		$this->assertFalse( Wpfaevent_Main_Navigation_Helper::has_custom_page( $event_a_id, 'beta-info' ) );
		$this->assertNull( Wpfaevent_Main_Navigation_Helper::get_custom_page( $event_a_id, 'beta-info' ) );

		// 4. Verify Event Beta is completely unaffected and retains its own custom navigation via the controller.
		$stored_b = get_post_meta( $event_b_id, 'wpfa_event_custom_navigation', true );
		$this->assertSame( $nav_items_b, $stored_b );
		$event_b_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_b_id );
		$this->assertArrayHasKey( 'wpfa_event_nav_items', $event_b_data );
		$this->assertSame( $nav_items_b, $event_b_data['wpfa_event_nav_items'] );
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
		$this->assertStringNotContainsString( 'Alpha Custom Link', $rendered_a );
		$this->assertStringNotContainsString( 'Alpha Info', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Custom Link', $rendered_a );
		$this->assertStringNotContainsString( 'Beta Info', $rendered_a );
	}

	/**
	 * Test that the navigation meta box editor is empty when no custom navigation has been saved,
	 * ensuring default navigation is not unintentionally saved as custom navigation.
	 */
	public function test_navigation_meta_box_is_empty_when_no_custom_navigation_saved() {
		if ( ! class_exists( 'Wpfaevent_Admin_Event_Metabox' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Admin_Event_Metabox class not available.' );
		}

		$event_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Without Custom Nav',
			)
		);

		$metabox = new Wpfaevent_Admin_Event_Metabox();
		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="wpfaevent-nav-items-container"', $output );
		$container_start = strpos( $output, 'id="wpfaevent-nav-items-container"' );
		$this->assertNotFalse( $container_start );
		$container_end = strpos( $output, '</div>', $container_start );
		$this->assertNotFalse( $container_end );
		$container_html = substr( $output, $container_start, $container_end - $container_start );
		$this->assertStringNotContainsString( 'wpfaevent-meta-card', $container_html );

		$this->assertStringContainsString( 'name="wpfa_custom_nav_items_present"', $output );
		$this->assertStringContainsString( 'id="wpfaevent-nav-default-template"', $output );
		$this->assertStringContainsString( 'id="wpfaevent-reset-nav-default"', $output );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringNotContainsString( 'style=', $output );
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

	/**
	 * Test that sanitize_custom_navigation discards dropdowns that have no valid sub-items.
	 */
	public function test_sanitize_custom_navigation_discards_empty_dropdowns() {
		$raw_items = array(
			array(
				'text'  => 'Empty Dropdown 1',
				'type'  => 'dropdown',
				'items' => array(),
			),
			array(
				'text'  => 'Empty Dropdown 2',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => '',
						'href' => 'https://example.com',
					),
				),
			),
			array(
				'text' => 'Valid Link',
				'type' => 'link',
				'href' => 'https://example.com',
			),
			array(
				'text'  => 'Valid Dropdown',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text' => 'Sub item',
						'href' => 'https://example.com/sub',
					),
				),
			),
		);

		$sanitized = Wpfaevent_Meta_Event::sanitize_custom_navigation( $raw_items );

		$this->assertCount( 2, $sanitized );
		$this->assertSame( 'Valid Link', $sanitized[0]['text'] );
		$this->assertSame( 'Valid Dropdown', $sanitized[1]['text'] );
	}

	/**
	 * Test that unsubmitted navigation metabox does not delete existing custom navigation.
	 */
	public function test_save_event_meta_unsubmitted_navigation_preserves_meta() {
		if ( ! class_exists( 'Wpfaevent_Admin_Event_Metabox' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Admin_Event_Metabox class not available.' );
		}

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$event_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event With Nav',
			)
		);

		$initial_nav = array(
			array(
				'text' => 'Speakers',
				'type' => 'link',
				'href' => '#speakers',
			),
		);
		update_post_meta( $event_id, 'wpfa_event_custom_navigation', $initial_nav );

		// Simulate an event save where wpfa_custom_nav_items_present is NOT sent (e.g. metabox hidden).
		$_POST['wpfa_event_meta_nonce'] = wp_create_nonce( 'wpfa_event_meta_nonce' );
		unset( $_POST['wpfa_custom_nav_items_present'], $_POST['wpfa_custom_nav_items'] );

		$metabox = new Wpfaevent_Admin_Event_Metabox();
		$metabox->save_event_meta( $event_id );

		// Navigation meta must be preserved.
		$stored_nav = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
		$this->assertSame( $initial_nav, $stored_nav );

		// Now simulate explicit submission with the presence flag set and empty nav items.
		$_POST['wpfa_custom_nav_items_present'] = '1';
		$_POST['wpfa_custom_nav_items']         = array();
		$metabox->save_event_meta( $event_id );

		// Navigation meta must now be deleted.
		$this->assertEmpty( get_post_meta( $event_id, 'wpfa_event_custom_navigation', true ) );

		unset( $_POST['wpfa_event_meta_nonce'], $_POST['wpfa_custom_nav_items_present'], $_POST['wpfa_custom_nav_items'] );
	}

	/**
	 * Test that custom-page slugs are preserved when the heading/title is edited in the navigation metabox.
	 */
	public function test_custom_page_slug_is_preserved_when_heading_is_edited() {
		if ( ! class_exists( 'Wpfaevent_Admin_Event_Metabox' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Admin_Event_Metabox class not available.' );
		}

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$event_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event With Custom Page',
			)
		);

		// 1. Initial save of a new custom page without a pre-existing slug.
		$_POST['wpfa_event_meta_nonce']         = wp_create_nonce( 'wpfa_event_meta_nonce' );
		$_POST['wpfa_custom_nav_items_present'] = '1';
		$_POST['wpfa_custom_nav_items']         = array(
			array(
				'text'    => 'Travel Grants',
				'type'    => 'custom_page',
				'title'   => 'Travel Grants',
				'slug'    => '',
				'content' => 'Travel grant details.',
			),
			array(
				'text'  => 'Resources',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'    => 'Visa Info',
						'type'    => 'custom_page',
						'title'   => 'Visa Info',
						'slug'    => '',
						'content' => 'Visa details.',
					),
				),
			),
		);

		$metabox = new Wpfaevent_Admin_Event_Metabox();
		$metabox->save_event_meta( $event_id );

		$saved_nav = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
		$this->assertIsArray( $saved_nav );
		$this->assertSame( 'travel-grants', $saved_nav[0]['slug'] );
		$this->assertSame( '?custom_page=travel-grants', $saved_nav[0]['href'] );
		$this->assertSame( 'visa-info', $saved_nav[1]['items'][0]['slug'] );
		$this->assertSame( '?custom_page=visa-info', $saved_nav[1]['items'][0]['href'] );

		// 2. Verify that the metabox markup renders the hidden slug inputs.
		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$metabox_output = ob_get_clean();

		$this->assertStringContainsString( 'name="wpfa_custom_nav_items[0][slug]" value="travel-grants"', $metabox_output );
		$this->assertStringContainsString( 'name="wpfa_custom_nav_items[1][items][0][slug]" value="visa-info"', $metabox_output );

		// 3. Edit the headings to something different, while the hidden slug input keeps the existing slug.
		$_POST['wpfa_custom_nav_items'] = array(
			array(
				'text'    => 'Travel Grant Info',
				'type'    => 'custom_page',
				'title'   => 'Travel Grant Information & Guidelines',
				'slug'    => 'travel-grants', // hidden input value from the form.
				'content' => 'Updated content.',
			),
			array(
				'text'  => 'Resources',
				'type'  => 'dropdown',
				'items' => array(
					array(
						'text'    => 'Visa Assistance',
						'type'    => 'custom_page',
						'title'   => 'Comprehensive Visa Guidance',
						'slug'    => 'visa-info', // hidden input value from the form.
						'content' => 'Updated visa content.',
					),
				),
			),
		);

		$metabox->save_event_meta( $event_id );

		$updated_nav = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
		$this->assertIsArray( $updated_nav );

		// The headings are updated, but the slugs and hrefs MUST stay the same as the original.
		$this->assertSame( 'Travel Grant Information & Guidelines', $updated_nav[0]['title'] );
		$this->assertSame( 'travel-grants', $updated_nav[0]['slug'] );
		$this->assertSame( '?custom_page=travel-grants', $updated_nav[0]['href'] );

		$this->assertSame( 'Comprehensive Visa Guidance', $updated_nav[1]['items'][0]['title'] );
		$this->assertSame( 'visa-info', $updated_nav[1]['items'][0]['slug'] );
		$this->assertSame( '?custom_page=visa-info', $updated_nav[1]['items'][0]['href'] );

		unset( $_POST['wpfa_event_meta_nonce'], $_POST['wpfa_custom_nav_items_present'], $_POST['wpfa_custom_nav_items'] );
	}

	/**
	 * Test that non-scalar custom_page query parameter does not trigger errors.
	 */
	public function test_custom_page_non_scalar_query_param_does_not_error() {
		if ( ! class_exists( 'Wpfaevent_Templates' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Templates class not available.' );
		}

		$event_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event Non Scalar Test',
			)
		);

		$this->go_to( get_permalink( $event_id ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['custom_page'] = array( 'malicious_array' );

		// Template loader must not crash and must select single-wpfa-event.php.
		$resolved_template = Wpfaevent_Templates::load( 'single.php' );
		$this->assertSame( WPFAEVENT_PATH . 'public/templates/single-wpfa-event.php', $resolved_template );

		// Main navigation helper get_current_custom_page must safely return null.
		$current_custom = Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_id );
		$this->assertNull( $current_custom );

		// event-section-nav.php partial must render cleanly without type error.
		$wpfa_event_nav_items = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items();
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		$output = ob_get_clean();
		$this->assertStringContainsString( 'wpfa-event-section-nav', $output );

		unset( $_GET['custom_page'] );
	}

	/**
	 * Test that header.php renders navigation links via Wpfaevent_Main_Navigation_Helper::render_navigation.
	 */
	public function test_header_renders_navigation_links() {
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$header_output = ob_get_clean();

		$this->assertStringContainsString( 'Upcoming Events', $header_output );
		$this->assertStringContainsString( 'Past Events', $header_output );
		$this->assertStringContainsString( 'Code of Conduct', $header_output );
	}

	/**
	 * Test that Reset to Default loads the dynamically built event navigation items,
	 * including tickets and custom tabs, while hiding empty sections.
	 */
	public function test_get_default_event_nav_items_matches_automatic_page_menu() {
		if ( ! class_exists( 'Wpfaevent_Admin_Event_Metabox' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Admin_Event_Metabox class not available.' );
		}

		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Dynamic Event Nav Test',
				'post_content' => 'About content for the event.',
			)
		);

		// Enable tickets and add a custom tab.
		update_post_meta( $event_id, 'wpfa_event_ticket_widget_url', 'https://eventyay.com/e/test-event' );
		update_post_meta(
			$event_id,
			'wpfa_event_custom_tabs',
			array(
				array(
					'slug'    => 'venue-guide',
					'title'   => 'Venue Guide',
					'content' => 'Venue details',
				),
			)
		);

		// No speakers, schedule, sponsors, or exhibitors meta are added.
		$default_nav = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items( $event_id );
		$hrefs       = array_column( $default_nav, 'href' );

		// Overview, Tickets, and custom tab must be present.
		$this->assertContains( '#about', $hrefs );
		$this->assertContains( '#tickets', $hrefs );
		$this->assertContains( '#custom-section-venue-guide', $hrefs );

		// Empty sections (Speakers, Schedule, Sponsors, Exhibitors) must be hidden.
		$this->assertNotContains( '#speakers', $hrefs );
		$this->assertNotContains( '#schedule-overview', $hrefs );
		$this->assertNotContains( '#sponsors', $hrefs );
		$this->assertNotContains( '#exhibitors', $hrefs );

		// In the metabox default template, the dynamic default items must be rendered.
		$metabox = new Wpfaevent_Admin_Event_Metabox();
		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="wpfaevent-nav-default-template"', $output );
		$this->assertStringContainsString( '#tickets', $output );
		$this->assertStringContainsString( '#custom-section-venue-guide', $output );
		$this->assertStringNotContainsString( '#sponsors', $output );
	}

	/**
	 * Test that a custom page belonging to an event resolves and renders navigation
	 * associated with that specific event, avoiding leakage from other events or generic fallbacks.
	 */
	public function test_custom_page_navigation_is_associated_with_correct_event() {
		if ( ! class_exists( 'Wpfaevent_Templates' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Templates class not available.' );
		}

		// 1. Create two distinct events.
		$event_a_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Event Alpha',
				'post_content' => 'Content for Event Alpha.',
			)
		);
		$event_b_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Event Beta',
				'post_content' => 'Content for Event Beta.',
			)
		);

		// 2. Configure event-specific custom pages and navigation for each event.
		$nav_a = array(
			array(
				'text' => 'Alpha Section',
				'type' => 'link',
				'href' => '#alpha-section',
			),
			array(
				'text'    => 'Alpha Custom Page',
				'type'    => 'custom_page',
				'title'   => 'Alpha Details',
				'slug'    => 'alpha-details',
				'content' => 'Alpha custom page content.',
				'href'    => '?custom_page=alpha-details',
			),
		);
		$nav_b = array(
			array(
				'text' => 'Beta Section',
				'type' => 'link',
				'href' => '#beta-section',
			),
			array(
				'text'    => 'Beta Custom Page',
				'type'    => 'custom_page',
				'title'   => 'Beta Details',
				'slug'    => 'beta-details',
				'content' => 'Beta custom page content.',
				'href'    => '?custom_page=beta-details',
			),
		);

		update_post_meta( $event_a_id, 'wpfa_event_custom_navigation', $nav_a );
		update_post_meta( $event_b_id, 'wpfa_event_custom_navigation', $nav_b );

		// 3. Set query context to Event Alpha's custom page.
		$this->go_to( add_query_arg( 'custom_page', 'alpha-details', get_permalink( $event_a_id ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['custom_page'] = 'alpha-details';

		// Verify template loader selects page-event-custom.php for Event Alpha.
		$resolved_template = Wpfaevent_Templates::load( 'single.php' );
		$this->assertSame( WPFAEVENT_PATH . 'public/templates/page-event-custom.php', $resolved_template );

		// Verify template controller resolves navigation belonging to Event Alpha.
		$event_a_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_a_id );
		$this->assertSame( $nav_a, $event_a_data['wpfa_event_nav_items'] );

		// Render the custom page template into an output buffer.
		ob_start();
		include WPFAEVENT_PATH . 'public/templates/page-event-custom.php';
		$rendered_a_custom = ob_get_clean();

		// Verify navigation rendered belongs to Event Alpha and contains its items.
		$this->assertStringContainsString( 'Alpha Section', $rendered_a_custom );
		$this->assertStringContainsString( 'Alpha Custom Page', $rendered_a_custom );

		// Ensure Event Beta's navigation items do not leak into Event Alpha's custom page.
		$this->assertStringNotContainsString( 'Beta Section', $rendered_a_custom );
		$this->assertStringNotContainsString( 'Beta Custom Page', $rendered_a_custom );

		// Verify no duplicate <title> tag is rendered (at most one from wp_head).
		$this->assertLessThanOrEqual( 1, substr_count( $rendered_a_custom, '<title>' ) );

		// Verify document_title_parts filter sets the page title.
		$title_parts = apply_filters( 'document_title_parts', array( 'title' => 'Default' ) );
		$this->assertSame( 'Alpha Details - Event Alpha', $title_parts['title'] );

		// 4. Test the fallback navigation path when custom navigation is not configured.
		delete_post_meta( $event_a_id, 'wpfa_event_custom_navigation' );
		delete_post_meta( $event_b_id, 'wpfa_event_custom_navigation' );

		// Configure distinct dynamic features for each event.
		update_post_meta( $event_a_id, 'wpfa_event_ticket_widget_url', 'https://eventyay.com/e/alpha-tickets' );
		update_post_meta(
			$event_b_id,
			'wpfa_event_custom_tabs',
			array(
				array(
					'slug'    => 'beta-tab',
					'title'   => 'Beta Tab',
					'content' => 'Beta content',
				),
			)
		);

		// Fallback navigation resolved via get_default_event_nav_items( $event_id ) must be event-specific.
		$fallback_a = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items( $event_a_id );
		$fallback_b = Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items( $event_b_id );

		$fallback_a_hrefs = array_column( $fallback_a, 'href' );
		$fallback_b_hrefs = array_column( $fallback_b, 'href' );

		// Event Alpha's fallback must include tickets and not Event Beta's custom tab.
		$this->assertContains( '#tickets', $fallback_a_hrefs );
		$this->assertNotContains( '#custom-section-beta-tab', $fallback_a_hrefs );

		// Event Beta's fallback must include its custom tab and not tickets.
		$this->assertContains( '#custom-section-beta-tab', $fallback_b_hrefs );
		$this->assertNotContains( '#tickets', $fallback_b_hrefs );

		// Clean up global query state.
		unset( $_GET['custom_page'] );
	}

	/**
	 * Create an event whose custom navigation links to a WordPress page, top level and in a dropdown.
	 *
	 * @param int $page_id Linked page ID.
	 * @return int Event post ID.
	 */
	private function create_event_with_existing_page_nav( $page_id ) {
		$event_id = $this->factory->post->create(
			array(
				'post_type'  => 'wpfa_event',
				'post_title' => 'Event With Page Link',
			)
		);

		$nav = Wpfaevent_Meta_Event::sanitize_custom_navigation(
			array(
				array(
					'text' => 'Register',
					'type' => 'link',
					'href' => 'https://eventyay.com/e/register',
				),
				array(
					'text'    => 'Venue',
					'type'    => 'page',
					'page_id' => $page_id,
				),
				array(
					'text'  => 'More',
					'type'  => 'dropdown',
					'items' => array(
						array(
							'text'    => 'Venue Details',
							'type'    => 'page',
							'page_id' => $page_id,
						),
					),
				),
			)
		);
		update_post_meta( $event_id, 'wpfa_event_custom_navigation', $nav );

		return $event_id;
	}

	/**
	 * Render the public event section navigation for an event.
	 *
	 * @param int $event_id Event post ID.
	 * @return string Rendered HTML.
	 */
	private function render_event_section_nav( $event_id ) {
		$event_data           = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		$wpfa_event_nav_items = $event_data['wpfa_event_nav_items'];

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		return (string) ob_get_clean();
	}

	/**
	 * Test that an "Existing Page" item follows the page's current URL without re-saving the event.
	 */
	public function test_existing_page_item_follows_page_url_changes() {
		$this->set_permalink_structure( '/%postname%/' );

		$page_id  = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Venue',
				'post_name'   => 'venue',
			)
		);
		$event_id = $this->create_event_with_existing_page_nav( $page_id );

		$output = $this->render_event_section_nav( $event_id );
		$this->assertStringContainsString( 'href="' . home_url( '/venue/' ) . '"', $output );

		wp_update_post(
			array(
				'ID'        => $page_id,
				'post_name' => 'venue-and-travel',
			)
		);

		$output = $this->render_event_section_nav( $event_id );
		$this->assertStringContainsString( 'href="' . home_url( '/venue-and-travel/' ) . '"', $output );
		$this->assertStringNotContainsString( home_url( '/venue/' ), $output );
	}

	/**
	 * Test that "Existing Page" items are hidden while their page is unavailable and come back when it returns.
	 */
	public function test_existing_page_item_is_hidden_while_page_is_unavailable() {
		$page_id  = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Venue',
			)
		);
		$event_id = $this->create_event_with_existing_page_nav( $page_id );

		$this->assertStringContainsString( '>Venue</a>', $this->render_event_section_nav( $event_id ) );

		foreach ( array( 'draft', 'private' ) as $status ) {
			wp_update_post(
				array(
					'ID'          => $page_id,
					'post_status' => $status,
				)
			);
			$output = $this->render_event_section_nav( $event_id );
			$this->assertStringContainsString( 'Register</a>', $output, "Other items stay visible when the page is {$status}." );
			$this->assertStringNotContainsString( '>Venue</a>', $output, "Item is hidden when the page is {$status}." );
			$this->assertStringNotContainsString( 'Venue Details', $output, "Sub-item is hidden when the page is {$status}." );
			$this->assertStringNotContainsString( 'nav-dropdown', $output, "A dropdown left empty is hidden when the page is {$status}." );
		}

		wp_update_post(
			array(
				'ID'          => $page_id,
				'post_status' => 'publish',
			)
		);
		wp_trash_post( $page_id );
		$output = $this->render_event_section_nav( $event_id );
		$this->assertStringContainsString( 'Register</a>', $output );
		$this->assertStringNotContainsString( '>Venue</a>', $output );

		wp_untrash_post( $page_id );
		wp_update_post(
			array(
				'ID'          => $page_id,
				'post_status' => 'publish',
			)
		);
		$output = $this->render_event_section_nav( $event_id );
		$this->assertStringContainsString( '>Venue</a>', $output, 'Item reappears once the page is restored.' );
		$this->assertStringContainsString( 'Venue Details', $output );

		wp_delete_post( $page_id, true );
		$output = $this->render_event_section_nav( $event_id );
		$this->assertStringContainsString( 'Register</a>', $output );
		$this->assertStringNotContainsString( '>Venue</a>', $output );
	}

	/**
	 * Test that an "Existing Page" item saved without a page is dropped instead of rendering its stored URL.
	 */
	public function test_existing_page_item_without_page_id_is_dropped() {
		$resolved = Wpfaevent_Main_Navigation_Helper::resolve_page_nav_items(
			array(
				array(
					'text' => 'Register',
					'type' => 'link',
					'href' => 'https://eventyay.com/e/register',
				),
				array(
					'text'    => 'Venue',
					'type'    => 'page',
					'page_id' => 0,
					'href'    => 'https://example.com/stale-venue/',
				),
			)
		);

		$this->assertSame( array( 'Register' ), array_column( $resolved, 'text' ) );
	}

	/**
	 * Test that an unavailable page without a title gets a readable label in the navigation editor.
	 */
	public function test_navigation_meta_box_labels_untitled_unavailable_page() {
		$page_id  = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => '',
			)
		);
		$event_id = $this->create_event_with_existing_page_nav( $page_id );
		$metabox  = new Wpfaevent_Admin_Event_Metabox();

		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '(no title #' . $page_id . ') (not published)', $output );

		wp_trash_post( $page_id );

		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( '(no title #' . $page_id . ') (in trash)', $output );
	}

	/**
	 * Test that a custom menu whose only items are unavailable pages is not replaced by the default menu.
	 */
	public function test_custom_navigation_with_only_unavailable_pages_does_not_fall_back_to_defaults() {
		$page_id  = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => 'Venue',
			)
		);
		$event_id = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Event With Only Page Links',
				'post_content' => 'Overview content.',
			)
		);
		update_post_meta(
			$event_id,
			'wpfa_event_custom_navigation',
			array(
				array(
					'text'    => 'Venue',
					'type'    => 'page',
					'page_id' => $page_id,
				),
			)
		);

		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		$this->assertSame( array(), $event_data['wpfa_event_nav_items'] );
		$this->assertNotEmpty( $event_data['default_nav_items'] );

		wp_publish_post( $page_id );

		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		$this->assertSame( array( 'Venue' ), array_column( $event_data['wpfa_event_nav_items'], 'text' ) );
	}

	/**
	 * Test that the navigation editor keeps an unavailable page selected and warns about it.
	 */
	public function test_navigation_meta_box_flags_unavailable_existing_page() {
		if ( ! class_exists( 'Wpfaevent_Admin_Event_Metabox' ) ) {
			$this->markTestSkipped( 'Wpfaevent_Admin_Event_Metabox class not available.' );
		}

		$page_id  = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Venue',
			)
		);
		$event_id = $this->create_event_with_existing_page_nav( $page_id );
		$metabox  = new Wpfaevent_Admin_Event_Metabox();

		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = (string) ob_get_clean();
		$this->assertStringNotContainsString( 'wpfaevent-nav-page-warning', $output );

		foreach ( array( 'draft', 'private' ) as $status ) {
			wp_update_post(
				array(
					'ID'          => $page_id,
					'post_status' => $status,
				)
			);

			ob_start();
			$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
			$output = (string) ob_get_clean();

			$this->assertSame( 2, substr_count( $output, 'class="wpfaevent-nav-page-warning"' ), "Warning shown when the page is {$status}." );
			$this->assertStringContainsString( 'The selected page is no longer published', $output );
			$this->assertMatchesRegularExpression( '/<option value="' . $page_id . '" data-unavailable="1" selected>\s*Venue \(not published\)/', $output );
		}

		wp_update_post(
			array(
				'ID'          => $page_id,
				'post_status' => 'publish',
			)
		);
		wp_trash_post( $page_id );

		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = (string) ob_get_clean();

		$this->assertSame( 2, substr_count( $output, 'class="wpfaevent-nav-page-warning"' ) );
		$this->assertStringContainsString( 'The selected page is in the trash', $output );
		$this->assertMatchesRegularExpression( '/<option value="' . $page_id . '" data-unavailable="1" selected>\s*Venue \(in trash\)/', $output );

		wp_delete_post( $page_id, true );

		ob_start();
		$metabox->render_event_navigation_meta_box( get_post( $event_id ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'The selected page has been deleted', $output );
		$this->assertStringContainsString( 'Deleted page (#' . $page_id . ')', $output );
	}
}
