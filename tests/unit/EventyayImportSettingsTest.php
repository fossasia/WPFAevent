<?php
/**
 * Tests for Eventyay import settings precedence.
 *
 * @package Wpfaevent
 */

/**
 * Covers switching events without retaining stale manual slugs.
 */
class EventyayImportSettingsTest extends WP_UnitTestCase {
	/**
	 * A submitted event URL must override saved settings merged into the form input.
	 *
	 * @dataProvider event_url_provider
	 * @param string $base_url Eventyay instance URL.
	 */
	public function test_event_url_overrides_saved_slugs( $base_url ) {
		$saved = array(
			'base_url'           => 'https://eventyay.com',
			'organizer_slug'     => 'previous-organizer',
			'event_slug'         => 'previous-event',
			'auto_sync_interval' => 'hourly',
		);
		update_option( 'wpfaevent_eventyay_import_settings', $saved );

		$importer = new Wpfaevent_Eventyay_Importer();
		$input    = array_merge( $importer->get_eventyay_import_settings(), array( 'event_url' => $base_url . '/next-organizer/next-event/' ) );
		$settings = $importer->sanitize_eventyay_import_settings( $input );
		$client   = new Wpfaevent_Eventyay_API_Client();

		$this->assertSame( $base_url, $settings['base_url'] );
		$this->assertSame( 'next-organizer', $settings['organizer_slug'] );
		$this->assertSame( 'next-event', $settings['event_slug'] );
		$this->assertSame(
			$base_url . '/api/v1/organizers/next-organizer/events/next-event/?lang=en',
			$client->build_eventyay_events_endpoint( $settings )
		);
	}

	/**
	 * Public instances accepted by the event URL parser.
	 *
	 * @return array<string, array<string>>
	 */
	public function event_url_provider() {
		return array(
			'production'  => array( 'https://eventyay.com' ),
			'development' => array( 'https://dev.eventyay.com' ),
		);
	}

	/**
	 * Saving manual settings without an event URL keeps the legacy path available.
	 */
	public function test_manual_slugs_remain_supported_without_event_url() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$settings = $importer->sanitize_eventyay_import_settings(
			array(
				'base_url'           => 'https://api.eventyay.com/v1',
				'event_slug'         => 'legacy-event',
				'auto_sync_interval' => 'hourly',
			)
		);

		$this->assertSame( 'https://api.eventyay.com/v1', $settings['base_url'] );
		$this->assertSame( '', $settings['organizer_slug'] );
		$this->assertSame( 'legacy-event', $settings['event_slug'] );
	}
}
