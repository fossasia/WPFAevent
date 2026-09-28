<?php
/**
 * Tests for the Eventyay import lock.
 *
 * @package Wpfaevent
 */

/**
 * Covers refusing a second import while one runs, and recovering from a dead one.
 */
class EventyayImportLockTest extends WP_UnitTestCase {
	/**
	 * Run each test as an administrator so the lock key belongs to a real user.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Clear the request globals the handler test fills in.
	 */
	public function tear_down() {
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Only the first request may start an import.
	 */
	public function test_a_second_request_cannot_take_a_held_lock() {
		$this->assertNotFalse( Wpfaevent_Eventyay_Importer::acquire_import_lock() );
		$this->assertTrue( Wpfaevent_Eventyay_Importer::is_import_in_progress() );
		$this->assertFalse( Wpfaevent_Eventyay_Importer::acquire_import_lock() );
	}

	/**
	 * A lock left behind by a run that died is taken over once it expires.
	 */
	public function test_an_expired_lock_is_taken_over() {
		update_option(
			Wpfaevent_Eventyay_Importer::get_import_in_progress_key(),
			time() - Wpfaevent_Eventyay_Importer::IMPORT_IN_PROGRESS_TTL - 1,
			false
		);

		$this->assertFalse( Wpfaevent_Eventyay_Importer::is_import_in_progress() );
		$this->assertNotFalse( Wpfaevent_Eventyay_Importer::acquire_import_lock() );
		$this->assertTrue( Wpfaevent_Eventyay_Importer::is_import_in_progress() );
	}

	/**
	 * A run that outlived the TTL must not release the lock of the run that took over.
	 */
	public function test_a_late_release_keeps_the_newer_lock() {
		$outlived = time() - Wpfaevent_Eventyay_Importer::IMPORT_IN_PROGRESS_TTL - 1;
		update_option( Wpfaevent_Eventyay_Importer::get_import_in_progress_key(), $outlived, false );

		$this->assertNotFalse( Wpfaevent_Eventyay_Importer::acquire_import_lock() );
		Wpfaevent_Eventyay_Importer::release_import_lock( $outlived );

		$this->assertTrue( Wpfaevent_Eventyay_Importer::is_import_in_progress() );
	}

	/**
	 * A second submit is refused with a notice and leaves the saved settings alone.
	 */
	public function test_a_refused_import_changes_nothing() {
		$settings = array(
			'base_url'       => 'https://eventyay.com',
			'organizer_slug' => 'running-organizer',
			'event_slug'     => 'running-event',
		);
		update_option( 'wpfaevent_eventyay_import_settings', $settings );
		Wpfaevent_Eventyay_Importer::acquire_import_lock();

		$_POST = array(
			'wpfaevent_eventyay_return_page'     => 'wpfaevent-import-events',
			'wpfaevent_eventyay_import_settings' => array( 'event_url' => 'https://eventyay.com/other-organizer/other-event/' ),
		);

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'wpfaevent_import_eventyay_events' );

		add_filter(
			'wp_redirect',
			static function () {
				throw new RuntimeException( 'redirected' );
			}
		);

		try {
			( new Wpfaevent_Eventyay_Importer() )->handle_eventyay_events_import();
			$this->fail( 'The handler should have redirected.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'redirected', $e->getMessage() );
		}

		$notice = get_transient( 'wpfaevent_eventyay_import_notice_' . get_current_user_id() );
		$this->assertSame( 'warning', $notice['type'] );
		$this->assertSame( $settings, get_option( 'wpfaevent_eventyay_import_settings' ) );
	}
}
