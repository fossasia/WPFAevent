<?php
/**
 * Eventyay AJAX Controller.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles incoming AJAX calls for Eventyay importer sync operations.
 */
class Wpfaevent_AJAX_Controller {

	/**
	 * API Client.
	 *
	 * @var Wpfaevent_Eventyay_API_Client
	 */
	private $client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client = new Wpfaevent_Eventyay_API_Client();
	}

	/**
	 * Get all events for chunked imports.
	 *
	 * @since 1.0.0
	 */
	public function ajax_import_get_events() {
		if ( ! check_ajax_referer( 'wpfaevent_import_eventyay_events', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		$importer = new Wpfaevent_Eventyay_Importer();
		$settings = $importer->get_eventyay_import_settings();

		if ( empty( $settings['organizer_slug'] ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please save an Eventyay organizer slug before importing.', 'wpfaevent' ),
				),
				400
			);
		}

		$fetched = $this->client->fetch_eventyay_event_resources( $settings );
		if ( is_wp_error( $fetched ) ) {
			wp_send_json_error(
				array(
					'message' => $fetched->get_error_message(),
				),
				400
			);
		}

		$events = isset( $fetched['events'] ) && is_array( $fetched['events'] ) ? $fetched['events'] : array();
		if ( empty( $events ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'No Eventyay events were returned by the configured endpoint.', 'wpfaevent' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'events' => $events,
			)
		);
	}

	/**
	 * Import a single event for chunked imports.
	 *
	 * @since 1.0.0
	 */
	public function ajax_import_single_event() {
		if ( ! check_ajax_referer( 'wpfaevent_import_eventyay_events', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		$event_str = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON expected, sanitized internally.
		$event     = json_decode( $event_str, true );

		if ( ! is_array( $event ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Missing or invalid event data.', 'wpfaevent' ),
				),
				400
			);
		}

		$importer = new Wpfaevent_Eventyay_Importer();
		$settings = $importer->get_eventyay_import_settings();

		$result = $importer->import_single_eventyay_event( $event, $settings );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$parser     = new Wpfaevent_JSONAPI_Parser();
		$event_slug = $parser->eventyay_event_slug( $event );
		if ( $event_slug && ! empty( $result['post_id'] ) ) {
			$sync_service  = new Wpfaevent_Eventyay_Ajax_Sync();
			$speaker_stats = $sync_service->sync_speakers_for_event( $result['post_id'], $event_slug, $settings );
			if ( ! is_wp_error( $speaker_stats ) && is_array( $speaker_stats ) ) {
				$result = array_merge( $result, $speaker_stats );
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Save the final import summary transient for notices.
	 *
	 * @since 1.0.0
	 */
	public function ajax_import_save_summary() {
		if ( ! check_ajax_referer( 'wpfaevent_import_eventyay_events', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		$notice_key = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();
		$message    = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		$type       = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'success';

		set_transient(
			$notice_key,
			array(
				'type'    => $type,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);

		wp_send_json_success();
	}
}
