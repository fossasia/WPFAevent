<?php
/**
 * Eventyay Event Repository.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages event Custom Post Type posts and post metadata.
 */
class Wpfaevent_Event_Repository {

	/**
	 * Parser instance.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Store instance.
	 *
	 * @var Wpfaevent_Partner_Json_Store
	 */
	private $store;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->parser = new Wpfaevent_JSONAPI_Parser();
		$this->store  = new Wpfaevent_Partner_Json_Store();
	}

	/**
	 * Create or update one imported Eventyay event post.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event    Eventyay event resource.
	 * @param array $settings Import settings.
	 * @return array|WP_Error Upsert result.
	 */
	public function upsert_eventyay_event_post( $event, $settings ) {
		$event      = $this->parser->normalize_eventyay_event_resource( $event );
		$event_slug = $this->parser->eventyay_event_slug( $event );
		if ( empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_event_missing_slug',
				esc_html__( 'An Eventyay event was skipped because it did not contain a slug.', 'wpfaevent' )
			);
		}

		$organizer_slug = $settings['organizer_slug'];
		$title          = $this->parser->eventyay_event_title( $event );
		$title          = $title ? $title : $event_slug;
		$description    = $this->parser->eventyay_event_description( $event );
		$existing_id    = $this->find_eventyay_event_post( $organizer_slug, $event_slug );
		$post_status    = in_array( $settings['post_status'], array( 'draft', 'publish', 'pending', 'private' ), true ) ? $settings['post_status'] : 'draft';
		$post_data      = array(
			'post_title'   => sanitize_text_field( $title ),
			'post_type'    => 'wpfa_event',
			'post_status'  => $post_status,
			'post_content' => wp_kses_post( $description ),
		);
		$created        = false;

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$post_data['post_name'] = sanitize_title( $organizer_slug . '-' . $event_slug );
			$saved_id               = wp_insert_post( $post_data, true );
			$created                = true;
		}

		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		$saved_id = absint( $saved_id );
		if ( ! $saved_id ) {
			return new WP_Error(
				'wpfaevent_eventyay_event_save_failed',
				esc_html__( 'Could not save imported Eventyay event.', 'wpfaevent' )
			);
		}

		$start_datetime       = $this->parser->eventyay_event_datetime( $event, 'start' );
		$end_datetime         = $this->parser->eventyay_event_datetime( $event, 'end' );
		$timezone             = $this->parser->eventyay_event_timezone( $event );
		$timezone_object      = $this->parser->eventyay_timezone_object( $timezone );
		$event_is_all_day     = ! $this->parser->eventyay_datetime_has_time( $start_datetime ) && ! $this->parser->eventyay_datetime_has_time( $end_datetime );
		$start_date           = $this->parser->format_eventyay_date( $start_datetime, $timezone_object );
		$end_date             = $this->parser->format_eventyay_date( $end_datetime, $timezone_object );
		$start_time           = $event_is_all_day ? '' : $this->parser->format_eventyay_time( $start_datetime, $timezone_object );
		$end_time             = $event_is_all_day ? '' : $this->parser->format_eventyay_time( $end_datetime, $timezone_object );
		$normalized_starts_at = $this->parser->normalize_eventyay_datetime( $start_datetime );
		$normalized_ends_at   = $this->parser->normalize_eventyay_datetime( $end_datetime );
		$location             = $this->parser->eventyay_event_location( $event );
		$event_url            = $this->parser->eventyay_public_event_url( $event, $settings, $event_slug );
		$languages            = $this->parser->eventyay_event_languages( $event );
		$colors               = $this->parser->eventyay_event_colors( $event );

		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_start_date', $start_date );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_end_date', $end_date );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_start_time', $start_time );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_end_time', $end_time );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_timezone', $timezone );
		update_post_meta( $saved_id, 'wpfa_event_all_day', $event_is_all_day ? '1' : '0' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_starts_at', $normalized_starts_at );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_ends_at', $normalized_ends_at );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_location', $location );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_url', $event_url );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_languages', $languages );

		if ( ! empty( $colors ) || $this->parser->eventyay_event_has_settings_payload( $event ) ) {
			foreach ( Wpfaevent_Meta_Event::get_event_color_meta_fields() as $meta_key => $label ) {
				$this->update_or_delete_post_meta( $saved_id, $meta_key, isset( $colors[ $meta_key ] ) ? $colors[ $meta_key ] : '' );
			}
		}

		// Keep older dashboard/landing metadata in sync with the canonical event meta.
		$this->update_or_delete_post_meta( $saved_id, '_event_date', $start_date );
		$this->update_or_delete_post_meta( $saved_id, '_event_end_date', $end_date );
		$this->update_or_delete_post_meta( $saved_id, '_event_place', $location );
		$this->update_or_delete_post_meta( $saved_id, '_event_registration_link', $event_url );
		$this->update_or_delete_post_meta( $saved_id, '_event_lead_text', wp_strip_all_tags( $description ) );

		update_post_meta( $saved_id, '_wpfa_eventyay_organizer_slug', sanitize_text_field( $organizer_slug ) );
		update_post_meta( $saved_id, '_wpfa_eventyay_event_slug', sanitize_text_field( $event_slug ) );
		update_post_meta( $saved_id, '_wpfa_eventyay_last_imported_at', current_time( 'mysql', true ) );
		$this->store_eventyay_event_lookup( $organizer_slug, $event_slug, $saved_id );

		$source_id = $this->parser->eventyay_event_first_present_raw( $event, array( '_eventyay_source_id', 'id', 'code', 'identifier' ), false );
		if ( is_scalar( $source_id ) && '' !== trim( (string) $source_id ) ) {
			update_post_meta( $saved_id, '_wpfa_eventyay_event_id', sanitize_text_field( (string) $source_id ) );
		}

		return array(
			'id'         => $saved_id,
			'created'    => $created,
			'event_slug' => $event_slug,
		);
	}

	/**
	 * Find an imported Eventyay event by source organizer and event slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $organizer_slug Eventyay organizer slug.
	 * @param string $event_slug     Eventyay event slug.
	 * @return int
	 */
	public function find_eventyay_event_post( $organizer_slug, $event_slug ) {
		$lookup_key = $this->build_eventyay_event_lookup_key( $organizer_slug, $event_slug );
		$lookup_map = $this->get_eventyay_event_lookup_map();

		if ( ! empty( $lookup_map[ $lookup_key ] ) ) {
			$post_id = absint( $lookup_map[ $lookup_key ] );
			if ( $post_id && 'wpfa_event' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		$event_ids = get_posts(
			array(
				'post_type'              => 'wpfa_event',
				'post_status'            => 'any',
				'name'                   => sanitize_title( $organizer_slug . '-' . $event_slug ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $event_ids[0] ) ) {
			$post_id               = absint( $event_ids[0] );
			$stored_organizer_slug = sanitize_text_field( (string) get_post_meta( $post_id, '_wpfa_eventyay_organizer_slug', true ) );
			$stored_event_slug     = sanitize_text_field( (string) get_post_meta( $post_id, '_wpfa_eventyay_event_slug', true ) );

			if ( sanitize_text_field( $organizer_slug ) === $stored_organizer_slug && sanitize_text_field( $event_slug ) === $stored_event_slug ) {
				$this->store_eventyay_event_lookup( $organizer_slug, $event_slug, $post_id );

				return $post_id;
			}
		}

		$this->prime_eventyay_event_lookup_map();
		$lookup_map = $this->get_eventyay_event_lookup_map();

		return ! empty( $lookup_map[ $lookup_key ] ) ? absint( $lookup_map[ $lookup_key ] ) : 0;
	}

	/**
	 * Build a stable lookup key for imported Eventyay events.
	 *
	 * @since 1.0.0
	 *
	 * @param string $organizer_slug Eventyay organizer slug.
	 * @param string $event_slug     Eventyay event slug.
	 * @return string
	 */
	private function build_eventyay_event_lookup_key( $organizer_slug, $event_slug ) {
		return md5( sanitize_text_field( $organizer_slug ) . '|' . sanitize_text_field( $event_slug ) );
	}

	/**
	 * Get the imported Eventyay event lookup map.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	private function get_eventyay_event_lookup_map() {
		$lookup_map = get_option( 'wpfaevent_eventyay_event_lookup', array() );

		return is_array( $lookup_map ) ? $lookup_map : array();
	}

	/**
	 * Store a lookup entry for an imported Eventyay event.
	 *
	 * @since 1.0.0
	 *
	 * @param string $organizer_slug Eventyay organizer slug.
	 * @param string $event_slug     Eventyay event slug.
	 * @param int    $post_id        WordPress post ID.
	 * @return void
	 */
	private function store_eventyay_event_lookup( $organizer_slug, $event_slug, $post_id ) {
		$post_id    = absint( $post_id );
		$lookup_key = $this->build_eventyay_event_lookup_key( $organizer_slug, $event_slug );

		if ( ! $post_id || '' === $lookup_key ) {
			return;
		}

		$lookup_map                = $this->get_eventyay_event_lookup_map();
		$lookup_map[ $lookup_key ] = $post_id;
		update_option( 'wpfaevent_eventyay_event_lookup', $lookup_map, false );
	}

	/**
	 * Prime the imported Eventyay event lookup map from existing posts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function prime_eventyay_event_lookup_map() {
		$event_ids = get_posts(
			array(
				'post_type'              => 'wpfa_event',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $event_ids ) ) {
			return;
		}

		$lookup_map = $this->get_eventyay_event_lookup_map();

		foreach ( $event_ids as $event_id ) {
			$event_id              = absint( $event_id );
			$stored_organizer_slug = sanitize_text_field( (string) get_post_meta( $event_id, '_wpfa_eventyay_organizer_slug', true ) );
			$stored_event_slug     = sanitize_text_field( (string) get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ) );

			if ( '' === $stored_organizer_slug || '' === $stored_event_slug ) {
				continue;
			}

			$lookup_map[ $this->build_eventyay_event_lookup_key( $stored_organizer_slug, $stored_event_slug ) ] = $event_id;
		}

		update_option( 'wpfaevent_eventyay_event_lookup', $lookup_map, false );
	}

	/**
	 * Update or delete a post meta value.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	public function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Write imported Eventyay sessions into the dashboard schedule table.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param array $sessions Normalized Eventyay sessions.
	 * @return array
	 */
	public function build_eventyay_schedule_table( $sessions ) {
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

	/**
	 * Resolve the Eventyay sync URL from POST data or saved dashboard settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public function get_eventyay_sync_url( $event_id ) {
		$api_url = '';

		// Nonce is verified in ajax_sync_eventyay() before this helper is called.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['eventyay_api_url'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$api_url = esc_url_raw( wp_unslash( $_POST['eventyay_api_url'] ) );
		}

		if ( empty( $api_url ) ) {
			$settings = $this->store->read_dashboard_json_file( 'site-settings-' . absint( $event_id ) . '.json', array() );

			if ( is_array( $settings ) && ! empty( $settings['eventyay_api_url'] ) ) {
				$api_url = esc_url_raw( $settings['eventyay_api_url'] );
			}
		}

		/**
		 * Filters the Eventyay Open API URL used by dashboard sync.
		 *
		 * @since 1.0.0
		 *
		 * @param string $api_url  Eventyay API URL.
		 * @param int    $event_id Event post ID.
		 */
		return apply_filters( 'wpfaevent_eventyay_sync_url', $api_url, absint( $event_id ) );
	}

	/**
	 * Persist the Eventyay sync URL into the dashboard settings JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $api_url  Eventyay API URL.
	 * @return true|WP_Error
	 */
	public function persist_eventyay_sync_url( $event_id, $api_url ) {
		$filename = 'site-settings-' . absint( $event_id ) . '.json';
		$settings = $this->store->read_dashboard_json_file( $filename, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['eventyay_api_url'] = esc_url_raw( $api_url );

		return $this->store->write_dashboard_json_file( $filename, $settings );
	}
}
