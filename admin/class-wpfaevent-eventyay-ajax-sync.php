<?php
/**
 * Eventyay JSON:API dashboard sync and speaker post management.
 *
 * Handles the AJAX-based Eventyay sync path that uses the JSON:API format.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Eventyay JSON:API speaker sync and dashboard file management.
 *
 * Re-architected using Composition. Decoupled from Wpfaevent_Eventyay_Importer
 * to resolve the fragile base class dependency.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Eventyay_Ajax_Sync {

	/**
	 * Parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->parser = new Wpfaevent_JSONAPI_Parser();
	}

	/**
	 * Handle Eventyay JSON:API speaker sync for the admin dashboard.
	 *
	 * @since 1.0.0
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

		$api_url = $this->get_eventyay_sync_url( $event_id );
		if ( empty( $api_url ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please save an Eventyay API URL before syncing.', 'wpfaevent' ),
				),
				400
			);
		}

		$api_url = $this->prepare_eventyay_sync_url( $api_url );
		if ( is_wp_error( $api_url ) ) {
			$this->send_eventyay_ajax_error( $api_url );
		}

		$settings_write = $this->persist_eventyay_sync_url( $event_id, $api_url );
		if ( is_wp_error( $settings_write ) ) {
			$this->send_eventyay_ajax_error( $settings_write );
		}

		$import_settings              = get_option( 'wpfaevent_eventyay_import_settings', array() );
		$api_token                    = ! empty( $import_settings['api_token'] ) ? $this->decrypt_value( $import_settings['api_token'] ) : '';
		$import_settings['api_token'] = $api_token;

		$event_slug = get_post_meta( absint( $event_id ), '_eventyay_event_slug', true );
		$import     = $this->fetch_speakers_with_fallback( $event_id, $event_slug, $import_settings, $api_url );
		if ( is_wp_error( $import ) ) {
			$this->send_eventyay_ajax_error( $import );
		}

		$existing_speakers  = $this->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() );
		$dashboard_speakers = $this->merge_dashboard_speaker_state( $import['speakers'], $existing_speakers );
		$write_result       = $this->write_dashboard_json_file( 'speakers-' . $event_id . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			$this->send_eventyay_ajax_error( $write_result );
		}

		$cpt_result = $this->sync_eventyay_speaker_posts( $import['speakers'], $event_id );

		$schedule_rows = $this->write_eventyay_schedule_table( $event_id, $import['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			$this->send_eventyay_ajax_error( $schedule_rows );
		}

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
				'schedule_rows'    => $schedule_rows,
				'speakers'         => $dashboard_speakers,
				'schedule'         => $this->read_dashboard_json_file( 'schedule-' . $event_id . '.json', new stdClass() ),
				'settings'         => $this->read_dashboard_json_file( 'site-settings-' . $event_id . '.json', new stdClass() ),
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
			$response = array_merge( $response, $error_data );

			if ( isset( $error_data['status'] ) ) {
				$status = absint( $error_data['status'] );
			}
		}

		if ( $status < 400 || $status > 599 ) {
			$status = 500;
		}

		wp_send_json_error( $response, $status );
	}

	/**
	 * Resolve the Eventyay sync URL from POST data or saved dashboard settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function get_eventyay_sync_url( $event_id ) {
		$api_url = '';

		// Nonce is verified in ajax_sync_eventyay() before this helper is called.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['eventyay_api_url'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$api_url = esc_url_raw( wp_unslash( $_POST['eventyay_api_url'] ) );
		}

		if ( empty( $api_url ) ) {
			$settings = $this->read_dashboard_json_file( 'site-settings-' . absint( $event_id ) . '.json', array() );

			if ( is_array( $settings ) && ! empty( $settings['eventyay_api_url'] ) ) {
				$api_url = esc_url_raw( $settings['eventyay_api_url'] );
			}
		}

		// Last resort: build the speakers URL from the event meta and saved import settings.
		if ( empty( $api_url ) && $event_id ) {
			$event_slug      = get_post_meta( absint( $event_id ), '_eventyay_event_slug', true );
			$import_settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
			$base_url        = ! empty( $import_settings['base_url'] ) ? $import_settings['base_url'] : '';
			$organizer_slug  = ! empty( $import_settings['organizer_slug'] ) ? $import_settings['organizer_slug'] : '';

			if ( $event_slug && $base_url && $organizer_slug ) {
				$api_url = trailingslashit( $base_url ) . 'api/v1/organizers/' . rawurlencode( $organizer_slug ) . '/events/' . rawurlencode( $event_slug ) . '/speakers/';
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
	private function persist_eventyay_sync_url( $event_id, $api_url ) {
		$filename = 'site-settings-' . absint( $event_id ) . '.json';
		$settings = $this->read_dashboard_json_file( $filename, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['eventyay_api_url'] = esc_url_raw( $api_url );

		return $this->write_dashboard_json_file( $filename, $settings );
	}

	/**
	 * Validate and complete a dashboard Eventyay API URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Raw API URL.
	 * @return string|WP_Error
	 */
	private function prepare_eventyay_sync_url( $api_url ) {
		$api_url = trim( $api_url );

		if ( empty( $api_url ) || ! $this->parser->is_valid_http_url( $api_url ) ) {
			return new WP_Error(
				'eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is not a valid HTTP(S) URL.', 'wpfaevent' ),
				array( 'status' => 400 )
			);
		}

		$parts  = wp_parse_url( $api_url );
		$scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new WP_Error(
				'eventyay_invalid_url_scheme',
				esc_html__( 'The Eventyay API URL must use HTTP or HTTPS.', 'wpfaevent' ),
				array( 'status' => 400 )
			);
		}

		$path = isset( $parts['path'] ) ? $parts['path'] : '';
		if ( false !== strpos( $path, '/sessions' ) ) {
			$query_args = array();
			if ( ! empty( $parts['query'] ) ) {
				wp_parse_str( $parts['query'], $query_args );
			}

			if ( empty( $query_args['include'] ) ) {
				$api_url = add_query_arg( 'include', 'speakers,track', $api_url );
			}

			if ( empty( $query_args['page']['size'] ) ) {
				$api_url = add_query_arg( 'page[size]', 200, $api_url );
			}
		}

		if ( false !== strpos( $path, '/speakers' ) && false !== strpos( $path, '/organizers/' ) ) {
			$query_args = array();
			if ( ! empty( $parts['query'] ) ) {
				wp_parse_str( $parts['query'], $query_args );
			}

			if ( empty( $query_args['expand'] ) ) {
				$api_url = add_query_arg(
					array(
						'expand'    => 'submissions,submissions.track,submissions.submission_type,submissions.slots.room',
						'lang'      => 'en',
						'page_size' => absint( apply_filters( 'wpfaevent_eventyay_speaker_import_page_size', 50 ) ),
					),
					$api_url
				);
			}
		}

		return $api_url;
	}

	/**
	 * Fetch and decode an Eventyay JSON:API document.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url   Eventyay API URL.
	 * @param string $api_token Optional API token for Authorization header.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_json( $api_url, $api_token = '' ) {
		if ( '' === trim( (string) $api_token ) ) {
			$importer  = new Wpfaevent_Eventyay_Importer();
			$settings  = $importer->get_eventyay_import_settings();
			$api_token = isset( $settings['api_token'] ) ? $settings['api_token'] : '';
		}

		if ( 0 === strpos( (string) $api_token, 'enc::' ) ) {
			$api_token = $this->decrypt_value( $api_token );
		}

		$client  = new Wpfaevent_Eventyay_API_Client();
		$decoded = $client->fetch_eventyay_rest_json( $api_url, $api_token );

		if ( is_wp_error( $decoded ) ) {
			return $decoded;
		}

		// Accept both JSON:API format and Eventyay REST paginated format.
		if ( ! is_array( $decoded ) || ( ! array_key_exists( 'data', $decoded ) && ! array_key_exists( 'results', $decoded ) ) ) {
			return new WP_Error(
				'eventyay_invalid_jsonapi',
				esc_html__( 'Eventyay API response does not contain a JSON:API data member.', 'wpfaevent' ),
				array(
					'status' => 502,
				)
			);
		}

		return $decoded;
	}

	/**
	 * Preserve dashboard-only speaker state across Eventyay syncs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported Eventyay speakers.
	 * @param array $existing Existing dashboard speakers.
	 * @return array
	 */
	private function merge_dashboard_speaker_state( $imported, $existing ) {
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$state = array();
		foreach ( $existing as $speaker ) {
			if ( ! is_array( $speaker ) ) {
				continue;
			}

			foreach ( $this->get_dashboard_speaker_state_keys( $speaker ) as $key ) {
				$state[ $key ] = array(
					'featured'       => ! empty( $speaker['featured'] ),
					'featured_order' => isset( $speaker['featured_order'] ) ? absint( $speaker['featured_order'] ) : null,
					'image'          => isset( $speaker['image'] ) ? esc_url_raw( $speaker['image'] ) : '',
				);
			}
		}

		foreach ( $imported as &$speaker ) {
			foreach ( $this->get_dashboard_speaker_state_keys( $speaker ) as $key ) {
				if ( ! isset( $state[ $key ] ) ) {
					continue;
				}

				$speaker['featured'] = ! empty( $speaker['featured'] ) || $state[ $key ]['featured'];
				if ( null !== $state[ $key ]['featured_order'] ) {
					$speaker['featured_order'] = $state[ $key ]['featured_order'];
				}
				if ( empty( $speaker['image'] ) && ! empty( $state[ $key ]['image'] ) ) {
					$speaker['image'] = $state[ $key ]['image'];
				}
				break;
			}
		}
		unset( $speaker );

		foreach ( $existing as $speaker ) {
			if ( ! is_array( $speaker ) || $this->is_eventyay_dashboard_speaker( $speaker ) ) {
				continue;
			}

			$imported[] = $speaker;
		}

		return array_values( $imported );
	}

	/**
	 * Get matching keys used to preserve dashboard speaker state.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker Speaker data.
	 * @return array
	 */
	private function get_dashboard_speaker_state_keys( $speaker ) {
		$keys = array();

		if ( ! empty( $speaker['eventyay_speaker_id'] ) ) {
			$keys[] = 'eventyay:' . sanitize_text_field( $speaker['eventyay_speaker_id'] );
		}

		if ( ! empty( $speaker['id'] ) ) {
			$keys[] = 'id:' . sanitize_text_field( $speaker['id'] );
		}

		if ( ! empty( $speaker['name'] ) ) {
			$keys[] = 'name:' . sanitize_title( $speaker['name'] );
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Determine whether a dashboard speaker record originated from Eventyay.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker Speaker data.
	 * @return bool
	 */
	private function is_eventyay_dashboard_speaker( $speaker ) {
		if ( isset( $speaker['source'] ) && 'eventyay' === $speaker['source'] ) {
			return true;
		}

		return ! empty( $speaker['id'] ) && 0 === strpos( (string) $speaker['id'], 'eventyay-' );
	}

	/**
	 * Read a dashboard JSON file from the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private function read_dashboard_json_file( $filename, $fallback ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $fallback;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) || ! $filesystem->exists( $path ) ) {
			return $fallback;
		}

		$contents = $filesystem->get_contents( $path );
		if ( false === $contents || '' === trim( $contents ) ) {
			return $fallback;
		}

		$decoded = json_decode( $contents, true );

		return ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : $fallback;
	}

	/**
	 * Write a dashboard JSON file into the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $data     Data to write.
	 * @return true|WP_Error
	 */
	private function write_dashboard_json_file( $filename, $data ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error(
				'eventyay_json_encode_failed',
				esc_html__( 'Could not encode synced Eventyay dashboard data.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		$chmod_file = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		if ( ! $filesystem->put_contents( $path, $json, $chmod_file ) ) {
			return new WP_Error(
				'eventyay_json_write_failed',
				esc_html__( 'Could not write synced Eventyay dashboard data to the dashboard data file.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Get a safe dashboard JSON path under the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @return string|WP_Error
	 */
	private function get_dashboard_json_path( $filename ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error(
				'eventyay_upload_dir_failed',
				esc_html__( 'Could not access the WordPress uploads directory.', 'wpfaevent' ),
				array(
					'status'  => 500,
					'details' => $upload_dir['error'],
				)
			);
		}

		$data_dir = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';
		if ( ! wp_mkdir_p( $data_dir ) ) {
			return new WP_Error(
				'eventyay_data_dir_failed',
				esc_html__( 'Could not create the dashboard data directory.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return trailingslashit( $data_dir ) . sanitize_file_name( $filename );
	}

	/**
	 * Initialize and return the WordPress filesystem API.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Filesystem_Base|WP_Error
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return new WP_Error(
				'eventyay_filesystem_failed',
				esc_html__( 'Could not initialize the WordPress filesystem.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return $wp_filesystem;
	}

	/**
	 * Upsert synced speakers into the maintained speaker CPT path.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speakers Imported speakers.
	 * @param int   $event_id Event post ID.
	 * @return array
	 */
	private function sync_eventyay_speaker_posts( $speakers, $event_id ) {
		$speaker_repo = new Wpfaevent_Speaker_Repository();

		return $speaker_repo->sync_eventyay_speaker_posts( $speakers, $event_id );
	}

	/**
	 * Sync speakers for a given event ID programmatically.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Event post ID.
	 * @param string $event_slug Eventyay event slug.
	 * @param array  $settings   Import settings.
	 * @return array|WP_Error
	 */
	public function sync_speakers_for_event( $event_id, $event_slug, $settings ) {
		$event_id  = absint( $event_id );
		$api_token = ! empty( $settings['api_token'] ) ? $settings['api_token'] : '';

		if ( ! $event_id ) {
			return new WP_Error( 'invalid_event_id', __( 'Invalid event ID.', 'wpfaevent' ) );
		}

		$base_url       = ! empty( $settings['base_url'] ) ? $settings['base_url'] : 'https://api.eventyay.com';
		$organizer_slug = ! empty( $settings['organizer_slug'] ) ? rawurlencode( $settings['organizer_slug'] ) : '';

		// Build the speakers endpoint. The Eventyay REST API requires the organizer in the path:
		// api/v1/organizers/{organizer}/events/{event}/speakers/
		// Fall back to the old Open Event JSON:API path (api.eventyay.com) when no organizer slug.
		if ( $organizer_slug ) {
			$speakers_url = trailingslashit( $base_url ) . 'api/v1/organizers/' . $organizer_slug . '/events/' . rawurlencode( $event_slug ) . '/speakers/';
			$speakers_url = add_query_arg(
				array(
					'expand'    => 'submissions,submissions.track,submissions.submission_type,submissions.slots.room',
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_speaker_import_page_size', 50 ) ),
				),
				$speakers_url
			);
		} else {
			$speakers_url = trailingslashit( $base_url ) . 'v1/events/' . rawurlencode( $event_slug ) . '/speakers?page[size]=200';
			if ( false !== strpos( $base_url, 'eventyay.com' ) && false === strpos( $base_url, 'api.eventyay.com' ) ) {
				$speakers_url = trailingslashit( $base_url ) . 'api/v1/events/' . rawurlencode( $event_slug ) . '/speakers?page[size]=200';
			}
		}

		$this->persist_eventyay_sync_url( $event_id, $speakers_url );

		$import = $this->fetch_speakers_with_fallback( $event_id, $event_slug, $settings, $speakers_url );

		if ( is_wp_error( $import ) ) {
			return $import;
		}

		$existing_speakers  = $this->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() );
		$dashboard_speakers = $this->merge_dashboard_speaker_state( $import['speakers'], $existing_speakers );
		$write_result       = $this->write_dashboard_json_file( 'speakers-' . $event_id . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		$cpt_result = $this->sync_eventyay_speaker_posts( $import['speakers'], $event_id );

		$schedule_rows = $this->write_eventyay_schedule_table( $event_id, $import['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			return $schedule_rows;
		}

		update_post_meta( $event_id, '_wpfa_eventyay_speakers_synced_at', time() );

		return array(
			'speakers'         => count( $import['speakers'] ),
			'created_speakers' => $cpt_result['created'],
			'updated_speakers' => $cpt_result['updated'],
			'sessions'         => isset( $import['session_count'] ) ? $import['session_count'] : count( $import['sessions'] ),
			'schedule_rows'    => $schedule_rows,
		);
	}

	/**
	 * Fetch speakers payload with schedule slots fallback if primary endpoint returns 0.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Event post ID.
	 * @param string $event_slug Event slug.
	 * @param array  $settings   Import settings.
	 * @param string $api_url    Primary speakers API URL.
	 * @return array|WP_Error Normalized import payload.
	 */
	private function fetch_speakers_with_fallback( $event_id, $event_slug, $settings, $api_url ) {
		$api_token = ! empty( $settings['api_token'] ) ? $settings['api_token'] : '';
		$payload   = $this->fetch_eventyay_json( $api_url, $api_token );
		$import    = null;

		if ( ! is_wp_error( $payload ) ) {
			$import = $this->parser->normalize_eventyay_payload( $payload, $settings, $event_slug );
		}

		$client              = new Wpfaevent_Eventyay_API_Client();
		$should_try_fallback = is_wp_error( $payload ) && $client->eventyay_error_has_http_status( $payload, 404 );
		if ( ! $should_try_fallback && ! is_wp_error( $payload ) ) {
			$should_try_fallback = is_wp_error( $import ) || empty( $import['speakers'] );
		}

		if ( $should_try_fallback ) {
			$base_url       = ! empty( $settings['base_url'] ) ? $settings['base_url'] : 'https://api.eventyay.com';
			$organizer_slug = ! empty( $settings['organizer_slug'] ) ? rawurlencode( $settings['organizer_slug'] ) : '';
			$slots_url      = '';

			if ( $organizer_slug ) {
				$slots_url = trailingslashit( $base_url ) . 'api/v1/organizers/' . $organizer_slug . '/events/' . rawurlencode( $event_slug ) . '/slots/';
			} else {
				$slots_url = trailingslashit( $base_url ) . 'v1/events/' . rawurlencode( $event_slug ) . '/slots?page[size]=200';
				if ( false !== strpos( $base_url, 'eventyay.com' ) && false === strpos( $base_url, 'api.eventyay.com' ) ) {
					$slots_url = trailingslashit( $base_url ) . 'api/v1/events/' . rawurlencode( $event_slug ) . '/slots?page[size]=200';
				}
			}

			$slots_url = add_query_arg(
				array(
					'expand'    => 'room,submission,submission.speakers',
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_speaker_import_page_size', 50 ) ),
				),
				$slots_url
			);

			$slots_payload = $this->fetch_eventyay_json( $slots_url, $api_token );
			if ( ! is_wp_error( $slots_payload ) && isset( $slots_payload['results'] ) && is_array( $slots_payload['results'] ) ) {
				$transformed_payload = $this->parser->transform_slots_to_speakers_payload( $slots_payload['results'] );
				$fallback_import     = $this->parser->normalize_eventyay_payload( $transformed_payload, $settings, $event_slug );
				if ( ! is_wp_error( $fallback_import ) && ! empty( $fallback_import['speakers'] ) ) {
					return $fallback_import;
				}
			}
		}

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		if ( is_wp_error( $import ) ) {
			return $import;
		}

		return $import;
	}

	/**
	 * Decrypt a string value using AUTH_KEY.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Encrypted value.
	 * @return string Decrypted value.
	 */
	private function decrypt_value( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}

		if ( 0 !== strpos( $value, 'enc::' ) ) {
			return $value;
		}

		$encrypted_part = substr( $value, 5 );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( $encrypted_part, true );
		if ( false === $raw ) {
			return $value;
		}

		$key       = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'wpfaevent-fallback-key' );
		$method    = 'aes-256-ctr';
		$iv_length = openssl_cipher_iv_length( $method );

		if ( strlen( $raw ) <= $iv_length ) {
			return $value;
		}

		$iv        = substr( $raw, 0, $iv_length );
		$encrypted = substr( $raw, $iv_length );

		$decrypted = openssl_decrypt( $encrypted, $method, $key, 0, $iv );
		if ( false === $decrypted ) {
			return $value;
		}

		return $decrypted;
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
	private function write_eventyay_schedule_table( $event_id, $sessions ) {
		$event_id = absint( $event_id );
		$sessions = is_array( $sessions ) ? $sessions : array();

		if ( ! $event_id ) {
			return 0;
		}

		$filename          = 'schedule-' . $event_id . '.json';
		$existing_schedule = $this->read_dashboard_json_file( $filename, array() );
		if (
			is_array( $existing_schedule )
			&& ! empty( $existing_schedule['name'] )
			&& ( empty( $existing_schedule['source'] ) || 'eventyay' !== $existing_schedule['source'] )
		) {
			return 0;
		}

		$table        = $this->build_eventyay_schedule_table( $sessions );
		$write_result = $this->write_dashboard_json_file( $filename, $table );
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
