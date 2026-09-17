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
	 * Parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Event Repository.
	 *
	 * @var Wpfaevent_Event_Repository
	 */
	private $event_repo;

	/**
	 * Speaker Repository.
	 *
	 * @var Wpfaevent_Speaker_Repository
	 */
	private $speaker_repo;

	/**
	 * Partner Store.
	 *
	 * @var Wpfaevent_Partner_Json_Store
	 */
	private $store;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client       = new Wpfaevent_Eventyay_API_Client();
		$this->parser       = new Wpfaevent_JSONAPI_Parser();
		$this->event_repo   = new Wpfaevent_Event_Repository();
		$this->speaker_repo = new Wpfaevent_Speaker_Repository();
		$this->store        = new Wpfaevent_Partner_Json_Store();
	}

	/**
	 * Handle Eventyay JSON:API speaker sync for the admin dashboard.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_sync_eventyay() {
		if ( ! check_ajax_referer( 'fossasia_admin_nonce', 'nonce', false ) ) {
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

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( ! $event_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Missing event ID.', 'wpfaevent' ),
				),
				400
			);
		}

		$api_url = $this->event_repo->get_eventyay_sync_url( $event_id );
		if ( empty( $api_url ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please save an Eventyay API URL before syncing.', 'wpfaevent' ),
				),
				400
			);
		}

		$api_url = $this->client->prepare_eventyay_sync_url( $api_url );
		if ( is_wp_error( $api_url ) ) {
			$this->send_eventyay_ajax_error( $api_url );
		}

		$settings_write = $this->event_repo->persist_eventyay_sync_url( $event_id, $api_url );
		if ( is_wp_error( $settings_write ) ) {
			$this->send_eventyay_ajax_error( $settings_write );
		}

		$payload = $this->client->fetch_eventyay_json( $api_url );
		if ( is_wp_error( $payload ) ) {
			$this->send_eventyay_ajax_error( $payload );
		}

		$import = $this->parser->normalize_eventyay_payload( $payload );
		if ( is_wp_error( $import ) ) {
			$this->send_eventyay_ajax_error( $import );
		}

		$existing_speakers  = $this->store->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() );
		$dashboard_speakers = $this->parser->merge_dashboard_speaker_state( $import['speakers'], $existing_speakers );
		$write_result       = $this->store->write_dashboard_json_file( 'speakers-' . $event_id . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			$this->send_eventyay_ajax_error( $write_result );
		}

		$cpt_result = $this->speaker_repo->sync_eventyay_speaker_posts( $import['speakers'], $event_id );

		wp_send_json_success(
			array(
				'message'          => sprintf(
					/* translators: 1: speaker count, 2: session count. */
					esc_html__( 'Synced %1$d speaker(s) from %2$d Eventyay session(s).', 'wpfaevent' ),
					count( $import['speakers'] ),
					$import['session_count']
				),
				'speaker_count'    => count( $import['speakers'] ),
				'session_count'    => $import['session_count'],
				'created_speakers' => $cpt_result['created'],
				'updated_speakers' => $cpt_result['updated'],
			)
		);
	}
	
	/**
	 * Send a structured Eventyay sync failure response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error Error object.
	 * @return void
	 */
	private function send_eventyay_ajax_error( $error ) {
		$error_data = $error->get_error_data();
		$status     = 500;
		$response   = array(
			'message' => $error->get_error_message(),
			'code'    => $error->get_error_code(),
		);

		if ( is_array( $error_data ) ) {
			if ( isset( $error_data['status'] ) ) {
				$status = absint( $error_data['status'] );
			}
			$response = array_merge( $response, $error_data );
		}

		wp_send_json_error( $response, $status );
	}
}
