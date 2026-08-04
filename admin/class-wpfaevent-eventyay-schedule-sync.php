<?php
/**
 * Eventyay dashboard schedule sync service.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes Eventyay sessions into the dashboard schedule table JSON.
 */
class Wpfaevent_Eventyay_Schedule_Sync {

	/**
	 * Dashboard JSON store.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Eventyay_Dashboard_Store|null $store Dashboard store.
	 */
	public function __construct( $store = null ) {
		$this->store = $store ? $store : new Wpfaevent_Eventyay_Dashboard_Store();
	}

	/**
	 * Write imported Eventyay sessions into the dashboard schedule table.
	 *
	 * @param int   $event_id Imported WordPress event post ID.
	 * @param array $sessions Normalized Eventyay sessions.
	 * @return int|WP_Error Number of imported schedule data rows.
	 */
	public function write_eventyay_schedule_table( $event_id, $sessions ) {
		$event_id = absint( $event_id );
		$sessions = is_array( $sessions ) ? $sessions : array();

		if ( ! $event_id ) {
			return 0;
		}

		$filename          = 'schedule-' . $event_id . '.json';
		$existing_schedule = $this->store->read_dashboard_json_file( $filename, array() );
		if (
			is_array( $existing_schedule )
			&& ! empty( $existing_schedule['name'] )
			&& ( empty( $existing_schedule['source'] ) || 'eventyay' !== $existing_schedule['source'] )
		) {
			return 0;
		}

		$table        = $this->build_eventyay_schedule_table( $sessions );
		$write_result = $this->store->write_dashboard_json_file( $filename, $table );
		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		return max( 0, absint( $table['rows'] ) - 1 );
	}

	/**
	 * Build the dashboard schedule table payload from Eventyay sessions.
	 *
	 * @param array $sessions Normalized Eventyay sessions.
	 * @return array
	 */
	private function build_eventyay_schedule_table( $sessions ) {
		usort(
			$sessions,
			static function ( $session_a, $session_b ) {
				$a_time = ! empty( $session_a['starts_at'] ) ? $session_a['starts_at'] : trim( (string) ( isset( $session_a['date'] ) ? $session_a['date'] : '' ) . ' ' . ( isset( $session_a['time'] ) ? $session_a['time'] : '' ) );
				$b_time = ! empty( $session_b['starts_at'] ) ? $session_b['starts_at'] : trim( (string) ( isset( $session_b['date'] ) ? $session_b['date'] : '' ) . ' ' . ( isset( $session_b['time'] ) ? $session_b['time'] : '' ) );

				return strcmp( $a_time, $b_time );
			}
		);

		$rows              = array(
			array(
				__( 'Date', 'wpfaevent' ),
				__( 'Time', 'wpfaevent' ),
				__( 'Session', 'wpfaevent' ),
				__( 'Speaker(s)', 'wpfaevent' ),
				__( 'Track', 'wpfaevent' ),
				__( 'Room', 'wpfaevent' ),
			),
		);
		$schedule_sessions = array();

		foreach ( $sessions as $session ) {
			if ( ! is_array( $session ) ) {
				continue;
			}

			$starts_at = isset( $session['starts_at'] ) ? sanitize_text_field( $session['starts_at'] ) : '';
			$ends_at   = isset( $session['ends_at'] ) ? sanitize_text_field( $session['ends_at'] ) : '';
			$date      = isset( $session['date'] ) ? sanitize_text_field( $session['date'] ) : '';
			$time      = isset( $session['time'] ) ? sanitize_text_field( $session['time'] ) : '';

			if ( ! empty( $session['end_time'] ) ) {
				$end_time = sanitize_text_field( $session['end_time'] );
				$time    .= $time ? ' - ' . $end_time : $end_time;
			}

			$speakers = '';
			if ( ! empty( $session['speakers'] ) && is_array( $session['speakers'] ) ) {
				$speakers = implode( ', ', array_map( 'sanitize_text_field', $session['speakers'] ) );
			}

			$title = isset( $session['title'] ) ? sanitize_text_field( $session['title'] ) : '';
			$track = isset( $session['track'] ) ? sanitize_text_field( $session['track'] ) : '';
			$room  = isset( $session['room'] ) ? sanitize_text_field( $session['room'] ) : '';

			$rows[] = array(
				$date,
				sanitize_text_field( $time ),
				$title,
				$speakers,
				$track,
				$room,
			);

			$schedule_sessions[] = array(
				'title'     => $title,
				'date'      => $date,
				'time'      => sanitize_text_field( $time ),
				'speakers'  => $speakers,
				'track'     => $track,
				'room'      => $room,
				'starts_at' => $starts_at,
				'ends_at'   => $ends_at,
			);
		}

		return array(
			'name'     => __( 'Eventyay Schedule', 'wpfaevent' ),
			'rows'     => count( $rows ),
			'cols'     => 6,
			'data'     => $rows,
			'sessions' => $schedule_sessions,
			'source'   => 'eventyay',
		);
	}
}
