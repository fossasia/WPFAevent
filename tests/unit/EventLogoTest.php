<?php
/**
 * Tests the event logo's local and imported settings.
 *
 * @package Wpfaevent
 */

/**
 * Exercises logo resolution with real WordPress post metadata.
 */
class EventLogoTest extends WP_UnitTestCase {

	/**
	 * Explicit removals take precedence over the legacy dashboard fallback.
	 */
	public function test_cleared_logo_stays_empty_when_imported_logo_exists() {
		$event_id      = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );
		$store         = new Wpfaevent_Eventyay_Dashboard_Store();
		$settings_file = 'site-settings-' . $event_id . '.json';
		$imported_logo = 'https://example.org/imported-logo.png';
		$local_logo    = 'https://example.org/local-logo.png';
		$result        = $store->write_dashboard_json_file( $settings_file, array( 'event_logo_url' => $imported_logo ) );
		$this->assertNotWPError( $result );

		try {
			$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
			$this->assertSame( $imported_logo, $data['event_logo_url'] );

			update_post_meta( $event_id, 'wpfa_event_logo_url', $local_logo );
			$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
			$this->assertSame( $local_logo, $data['event_logo_url'] );

			update_post_meta( $event_id, 'wpfa_event_logo_url', '' );
			$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
			$this->assertSame( '', $data['event_logo_url'] );
		} finally {
			$path = $store->get_dashboard_json_path( $settings_file );
			if ( $path && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}
}
