<?php
/**
 * Event logo editing and import regressions.
 *
 * @package Wpfaevent
 */

/**
 * Exercise event logo removal with WordPress post metadata and dashboard data.
 */
class EventLogoTest extends WP_UnitTestCase {
	private const IMPORTED_LOGO = 'https://example.org/imported-logo.png';
	private const LOCAL_LOGO    = 'https://example.org/local-logo.png';
	private const HEADER_IMAGE  = 'https://example.org/independent-header.png';

	/**
	 * Test event ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Site settings file name.
	 *
	 * @var string
	 */
	private $settings_file;

	/**
	 * Dashboard JSON store.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Seed an event that still reads its logo from imported dashboard settings.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->event_id      = $this->factory->post->create(
			array(
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
			)
		);
		$this->settings_file = 'site-settings-' . $this->event_id . '.json';
		$this->store         = new Wpfaevent_Eventyay_Dashboard_Store();
		$this->assertNotWPError(
			$this->store->write_dashboard_json_file(
				$this->settings_file,
				array(
					'event_logo_url'         => self::IMPORTED_LOGO,
					'event_header_image_url' => self::HEADER_IMAGE,
				)
			)
		);
	}

	/**
	 * Remove dashboard data created in each test.
	 */
	public function tearDown(): void {
		$path = $this->store->get_dashboard_json_path( $this->settings_file );
		if ( $path && file_exists( $path ) ) {
			wp_delete_file( $path );
		}
		parent::tearDown();
	}

	/**
	 * An untouched empty metabox field can never override an imported logo.
	 */
	public function test_legacy_logo_survives_an_untouched_blank_metabox_field() {
		$this->save_metabox_logo( '' );
		$this->assertSame( self::IMPORTED_LOGO, $this->template_logo() );

		update_post_meta( $this->event_id, 'wpfa_event_logo_url', '' );
		ob_start();
		( new Wpfaevent_Admin_Event_Metabox() )->render_event_meta_box( get_post( $this->event_id ) );
		$markup = ob_get_clean();
		$this->assertStringContainsString( 'name="wpfa_event_previous_logo_url" value="' . self::IMPORTED_LOGO . '"', $markup );
		$this->assertStringContainsString( 'name="wpfa_event_logo_url" value="' . self::IMPORTED_LOGO . '"', $markup );
		$this->save_metabox_logo( self::IMPORTED_LOGO, self::IMPORTED_LOGO );
		$this->assertSame( '', get_post_meta( $this->event_id, 'wpfa_event_logo_url', true ) );
	}

	/**
	 * An unchanged imported URL keeps its percent-encoded characters.
	 */
	public function test_untouched_encoded_logo_remains_a_dashboard_fallback() {
		$encoded_logo = 'https://example.org/logo%20blue.png?label=Open%2BSource';
		$this->assertNotWPError(
			$this->store->write_dashboard_json_file(
				$this->settings_file,
				array( 'event_logo_url' => $encoded_logo )
			)
		);

		$this->save_metabox_logo( $encoded_logo, $encoded_logo );

		$this->assertFalse( metadata_exists( 'post', $this->event_id, 'wpfa_event_logo_url' ) );
		$this->assertSame( $encoded_logo, $this->template_logo() );
	}

	/**
	 * Clearing a displayed logo removes only the logo from the event and listings.
	 */
	public function test_metabox_removal_hides_logo_from_both_listings_and_preserves_header() {
		$event_id = $this->event_id;
		update_post_meta( $event_id, 'wpfa_event_end_date', '2024-03-01' );

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/events/event-card.php';
		$card_before = ob_get_clean();
		$this->assertStringContainsString( self::IMPORTED_LOGO, $card_before );

		$this->save_metabox_logo( '', self::IMPORTED_LOGO );
		$this->assertSame( '', $this->template_logo() );
		$dashboard_data = ( new Wpfaevent_Event_Dashboard_Data() )->get_event_dashboard_data( $event_id );
		$this->assertSame( '', $dashboard_data['settings']['event_logo_url'] );

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/events/event-card.php';
		$card_after = ob_get_clean();
		$this->assertStringNotContainsString( self::IMPORTED_LOGO, $card_after );
		$this->assertStringContainsString( self::HEADER_IMAGE, $card_after );

		ob_start();
		include WPFAEVENT_PATH . 'public/templates/page-past-events.php';
		$archive_after = ob_get_clean();
		$this->assertStringNotContainsString( self::IMPORTED_LOGO, $archive_after );
		$this->assertStringContainsString( self::HEADER_IMAGE, $archive_after );

		$this->save_metabox_logo( self::LOCAL_LOGO );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );
	}

	/**
	 * Targeted dashboard editing can remove and replace the public logo.
	 */
	public function test_dashboard_edit_clears_and_replaces_the_logo() {
		$dashboard = new Wpfaevent_Event_Dashboard_Page();
		$update    = new ReflectionMethod( $dashboard, 'update_dashboard_field' );
		$update->setAccessible( true );

		$this->assertNotWPError( $update->invoke( $dashboard, $this->event_id, 'wpfa_event_logo_url', '' ) );
		$this->assertSame( '', $this->template_logo() );

		$this->assertNotWPError( $update->invoke( $dashboard, $this->event_id, 'wpfa_event_logo_url', self::LOCAL_LOGO ) );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );

		$this->assertWPError( $update->invoke( $dashboard, $this->event_id, 'wpfa_event_logo_url', 'javascript:invalid' ) );
		$this->assertWPError( $update->invoke( $dashboard, $this->event_id, 'wpfa_event_logo_url', null ) );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );
	}

	/**
	 * Post and JSON re-import respect a clear; an explicit logo restore reverses it.
	 */
	public function test_reimport_keeps_a_logo_cleared_until_an_explicit_restore() {
		$event    = array(
			'slug'             => 'fixture-event',
			'name'             => 'Fixture Event',
			'logo_url'         => self::IMPORTED_LOGO,
			'header_image_url' => self::HEADER_IMAGE,
		);
		$settings = array(
			'organizer_slug' => 'fixture-org',
			'event_slug'     => 'fixture-event',
			'base_url'       => 'https://example.org',
			'post_status'    => 'publish',
			'api_token'      => '',
		);
		update_post_meta( $this->event_id, '_wpfa_eventyay_organizer_slug', $settings['organizer_slug'] );
		update_post_meta( $this->event_id, '_wpfa_eventyay_event_slug', $event['slug'] );

		$this->save_metabox_logo( '', self::IMPORTED_LOGO );
		$repository = new Wpfaevent_Event_Repository();
		$result     = $repository->upsert_eventyay_event_post( $event, $settings );
		$this->assertNotWPError( $result );
		$this->assertSame( $this->event_id, $result['id'] );
		$this->assertSame( '', $this->template_logo() );

		$importer = new Wpfaevent_Eventyay_Importer();
		$sync     = new ReflectionMethod( $importer, 'sync_eventyay_event_dashboard_data' );
		$sync->setAccessible( true );
		$this->assertNotWPError( $sync->invoke( $importer, $this->event_id, $event, $settings, $event['slug'] ) );
		$dashboard_settings = $this->store->read_dashboard_json_file( $this->settings_file, array() );
		$this->assertEmpty( $dashboard_settings['event_logo_url'] ?? '' );
		$this->assertSame( self::HEADER_IMAGE, $dashboard_settings['event_header_image_url'] );
		$this->assertSame( '', $this->template_logo() );

		// The REST-exposed URL can be set directly without losing local intent on import.
		update_post_meta( $this->event_id, 'wpfa_event_logo_url', self::LOCAL_LOGO );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );
		$this->assertNotWPError( $repository->upsert_eventyay_event_post( $event, $settings ) );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );

		$dashboard_sync = new Wpfaevent_Event_Dashboard_Sync_Service();
		$sync_media     = new ReflectionMethod( $dashboard_sync, 'update_dashboard_media_settings' );
		$sync_media->setAccessible( true );
		$restored = $sync_media->invoke( $dashboard_sync, $this->event_id, $event, $settings, true );
		$this->assertNotWPError( $restored );
		$this->assertTrue( $restored['logo_overwritten'] );
		$this->assertSame( self::IMPORTED_LOGO, $this->template_logo() );

		$event['logo_url'] = self::LOCAL_LOGO;
		$this->assertNotWPError( $sync_media->invoke( $dashboard_sync, $this->event_id, $event, $settings, true ) );
		$this->assertSame( self::LOCAL_LOGO, $this->template_logo() );
	}

	/**
	 * Submit the normal metabox save with the logo field.
	 *
	 * @param string $url      Submitted logo field value.
	 * @param string $previous_url Logo displayed by the metabox before the edit.
	 */
	private function save_metabox_logo( $url, $previous_url = '' ) {
		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Restore the test request after exercising the real nonce-checked save.
		$_POST         = array(
			'wpfa_event_meta_nonce'        => wp_create_nonce( 'wpfa_event_meta_nonce' ),
			'wpfa_event_logo_url'          => $url,
			'wpfa_event_previous_logo_url' => $previous_url,
		);
		try {
			( new Wpfaevent_Admin_Event_Metabox() )->save_event_meta( $this->event_id );
		} finally {
			$_POST = $previous_post;
		}
	}

	/**
	 * Get the logo URL resolved for the event template.
	 *
	 * @return string
	 */
	private function template_logo() {
		$data = Wpfaevent_Event_Template_Controller::get_event_template_data( $this->event_id );
		return $data['event_logo_url'];
	}
}
