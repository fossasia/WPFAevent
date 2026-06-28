<?php
/**
 * WP-Cron scheduled auto-sync for Eventyay events and speakers.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the WP-Cron scheduled event that periodically re-imports
 * events and speakers from the configured Eventyay API endpoint.
 *
 * @since      1.0.0
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Cron_Scheduler {

	const HOOK               = 'wpfaevent_auto_sync';
	const LAST_RESULT_OPTION = 'wpfaevent_auto_sync_last_result';

	/**
	 * Schedule (or unschedule) the recurring sync based on saved settings.
	 *
	 * Call this whenever the import settings change, and on plugin activation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Saved Eventyay import settings (plain-text, not encrypted).
	 * @return void
	 */
	public static function schedule( array $settings ) {
		self::clear();

		$enabled  = ! empty( $settings['auto_sync_enabled'] );
		$has_slug = ! empty( $settings['organizer_slug'] );

		if ( ! $enabled || ! $has_slug ) {
			return;
		}

		$interval = isset( $settings['auto_sync_interval'] ) ? $settings['auto_sync_interval'] : 'daily';
		if ( ! in_array( $interval, array( 'hourly', 'twicedaily', 'daily' ), true ) ) {
			$interval = 'daily';
		}

		wp_schedule_event( time(), $interval, self::HOOK );
	}

	/**
	 * Remove any existing scheduled sync event.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function clear() {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	/**
	 * Cron callback: run the full Eventyay import and record the result.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function run() {
		$importer = new Wpfaevent_Eventyay_Importer();
		$result   = $importer->import_eventyay_events_from_settings();

		$record = array( 'time' => time() );

		if ( is_wp_error( $result ) ) {
			$record['type']    = 'error';
			$record['message'] = $result->get_error_message();
		} else {
			$record['type']    = 'success';
			$record['message'] = sprintf(
				/* translators: 1: fetched count, 2: created count, 3: updated count, 4: skipped count */
				__( 'Fetched %1$d event(s). Created %2$d, updated %3$d, skipped %4$d.', 'wpfaevent' ),
				absint( $result['fetched'] ),
				absint( $result['created'] ),
				absint( $result['updated'] ),
				absint( $result['skipped'] )
			);
		}

		update_option( self::LAST_RESULT_OPTION, $record, false );
	}

	/**
	 * Re-schedule when saved settings change.
	 *
	 * Hooked onto `update_option_wpfaevent_eventyay_import_settings`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $old_value Previous option value (unused).
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public static function handle_settings_update( $old_value, $new_value ) {
		self::schedule( is_array( $new_value ) ? $new_value : array() );
	}

	/**
	 * Return the Unix timestamp of the next scheduled sync, or false if not scheduled.
	 *
	 * @since 1.0.0
	 *
	 * @return int|false
	 */
	public static function get_next_scheduled() {
		return wp_next_scheduled( self::HOOK );
	}

	/**
	 * Return the stored result of the last cron run, or null if it has never run.
	 *
	 * @since 1.0.0
	 *
	 * @return array{time:int,type:string,message:string}|null
	 */
	public static function get_last_result() {
		$result = get_option( self::LAST_RESULT_OPTION, null );
		return is_array( $result ) ? $result : null;
	}
}
