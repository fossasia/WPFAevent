<?php
/**
 * Context-specific header navigation regressions.
 *
 * @package Wpfaevent
 */

class EventHeaderNavigationTest extends WP_UnitTestCase {
	/**
	 * Create an event with anchors, a dropdown and a custom information page.
	 *
	 * @return int Event ID.
	 */
	private function create_navigation_event() {
		$event_id = self::factory()->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_status'  => 'publish',
				'post_title'   => 'Navigation event',
				'post_content' => 'Event overview.',
			)
		);
		update_post_meta(
			$event_id,
			'wpfa_event_custom_navigation',
			array(
				array( 'text' => 'Event overview', 'type' => 'link', 'href' => '#about' ),
				array(
					'text'  => 'Event details',
					'type'  => 'dropdown',
					'items' => array(
						array( 'text' => 'Event sessions', 'type' => 'link', 'href' => '#schedule-overview' ),
						array( 'text' => 'Travel', 'type' => 'custom_page', 'slug' => 'travel', 'href' => '?custom_page=travel', 'content' => 'Travel information.' ),
					),
				),
			)
		);
		return $event_id;
	}

	/**
	 * The actual event controller data replaces global links in event headers.
	 */
	public function test_event_header_renders_its_prepared_menu() {
		$event_id = $this->create_navigation_event();
		$this->go_to( get_permalink( $event_id ) );
		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Event overview', $output );
		$this->assertStringContainsString( 'Event sessions', $output );
		$this->assertStringContainsString( 'Travel', $output );
		$this->assertStringNotContainsString( 'Upcoming Events', $output );
		$this->assertStringContainsString( 'href="#about"', $output );
	}

	/**
	 * Custom pages navigate back to event sections rather than missing local IDs.
	 */
	public function test_custom_page_header_qualifies_event_anchors() {
		$event_id = $this->create_navigation_event();
		$this->go_to( add_query_arg( 'custom_page', 'travel', get_permalink( $event_id ) ) );
		$_GET['custom_page'] = 'travel';
		$event_data          = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();
		unset( $_GET['custom_page'] );

		$this->assertStringContainsString( esc_url( get_permalink( $event_id ) . '#about' ), $output );
		$this->assertStringContainsString( esc_url( get_permalink( $event_id ) . '#schedule-overview' ), $output );
		$this->assertStringNotContainsString( 'href="#about"', $output );
		$this->assertStringContainsString( 'nav-dropdown active', $output );
	}

	/**
	 * Existing event variables cannot replace homepage navigation accidentally.
	 */
	public function test_global_header_keeps_all_three_default_links() {
		$event_id = $this->create_navigation_event();
		$this->go_to( home_url( '/' ) );
		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Upcoming Events', $output );
		$this->assertStringContainsString( 'Past Events', $output );
		$this->assertStringContainsString( 'Code of Conduct', $output );
		$this->assertStringNotContainsString( 'Event overview', $output );
	}


	/**
	 * A locked event must not disclose its custom menu to an anonymous visitor.
	 */
	public function test_password_protected_event_keeps_global_header_until_unlocked() {
		$event_id = $this->create_navigation_event();
		wp_update_post( array( 'ID' => $event_id, 'post_password' => 'navigation-secret' ) );
		$this->go_to( get_permalink( $event_id ) );
		$this->assertTrue( post_password_required( $event_id ) );
		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'Event overview', $output );
		$this->assertStringNotContainsString( 'Event sessions', $output );
		$this->assertStringContainsString( 'Upcoming Events', $output );
	}

	/**
	 * A configured menu whose pages are hidden does not expose global links.
	 */
	public function test_event_header_preserves_an_empty_resolved_menu() {
		$event_id = $this->create_navigation_event();
		$private_page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'private' ) );
		update_post_meta( $event_id, 'wpfa_event_custom_navigation', array( array( 'text' => 'Private information', 'type' => 'page', 'page_id' => $private_page ) ) );
		$this->go_to( get_permalink( $event_id ) );
		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
		$this->assertSame( array(), $event_data['wpfa_event_nav_items'] );
		ob_start();
		include WPFAEVENT_PATH . 'public/partials/header.php';
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'Upcoming Events', $output );
		$this->assertStringNotContainsString( 'Event overview', $output );
	}
}
