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

		$cpt_result     = $this->sync_eventyay_speaker_posts( $import['speakers'], $event_id );
		$partner_result = $this->sync_eventyay_partner_data( $event_id, $event_slug, $import_settings );

		$schedule_rows = $this->write_eventyay_schedule_table( $event_id, $import['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			$this->send_eventyay_ajax_error( $schedule_rows );
		}

		wp_send_json_success(
			array(
				'message'          => sprintf(
					/* translators: 1: speaker count, 2: sponsor count, 3: exhibitor count, 4: session count. */
					esc_html__( 'Synced %1$d speaker(s), %2$d sponsor(s), and %3$d exhibitor(s) from %4$d Eventyay session(s).', 'wpfaevent' ),
					count( $import['speakers'] ),
					isset( $partner_result['sponsors'] ) ? $partner_result['sponsors'] : 0,
					isset( $partner_result['exhibitors'] ) ? $partner_result['exhibitors'] : 0,
					$import['session_count']
				),
				'speaker_count'    => count( $import['speakers'] ),
				'session_count'    => $import['session_count'],
				'created_speakers' => $cpt_result['created'],
				'updated_speakers' => $cpt_result['updated'],
				'sponsors'         => isset( $partner_result['sponsors'] ) ? $partner_result['sponsors'] : 0,
				'exhibitors'       => isset( $partner_result['exhibitors'] ) ? $partner_result['exhibitors'] : 0,
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
		$result         = array(
			'created'      => 0,
			'updated'      => 0,
			'ids'          => array(),
			'featured_ids' => array(),
		);
		$featured_posts = array();

		$event_status   = $event_id ? get_post_status( $event_id ) : 'draft';
		$speaker_status = 'publish' === $event_status ? 'publish' : 'draft';

		foreach ( $speakers as $speaker ) {
			$upsert = $this->upsert_eventyay_speaker_post( $speaker, $speaker_status );

			if ( is_wp_error( $upsert ) || empty( $upsert['id'] ) ) {
				continue;
			}

			$result['ids'][] = absint( $upsert['id'] );
			if ( ! empty( $speaker['featured'] ) ) {
				$featured_posts[] = array(
					'id'    => absint( $upsert['id'] ),
					'order' => isset( $speaker['featured_order'] ) ? absint( $speaker['featured_order'] ) : 0,
					'name'  => isset( $speaker['name'] ) ? sanitize_text_field( $speaker['name'] ) : '',
				);
			}

			if ( ! empty( $upsert['created'] ) ) {
				++$result['created'];
			} else {
				++$result['updated'];
			}
		}

		$result['ids'] = $this->sanitize_eventyay_post_id_list( $result['ids'] );
		usort(
			$featured_posts,
			static function ( $speaker_a, $speaker_b ) {
				if ( $speaker_a['order'] !== $speaker_b['order'] ) {
					if ( ! $speaker_a['order'] ) {
						return 1;
					}

					if ( ! $speaker_b['order'] ) {
						return -1;
					}

					return $speaker_a['order'] < $speaker_b['order'] ? -1 : 1;
				}

				return strcasecmp( $speaker_a['name'], $speaker_b['name'] );
			}
		);
		$result['featured_ids'] = $this->sanitize_eventyay_post_id_list( wp_list_pluck( $featured_posts, 'id' ) );

		if ( $event_id && 'wpfa_event' === get_post_type( $event_id ) ) {
			$previous_speakers = $this->get_eventyay_event_speaker_ids( $event_id );
			$previous_featured = $this->get_eventyay_event_featured_speaker_ids( $event_id );
			$manual_speakers   = array_values(
				array_filter(
					$previous_speakers,
					function ( $speaker_id ) {
						return ! $this->is_eventyay_speaker_post( $speaker_id );
					}
				)
			);
			$current_speakers  = $this->sanitize_eventyay_post_id_list( array_merge( $manual_speakers, $result['ids'] ) );
			$manual_featured   = array_values(
				array_filter(
					$previous_featured,
					function ( $speaker_id ) {
						return ! $this->is_eventyay_speaker_post( $speaker_id );
					}
				)
			);
			$current_featured  = $this->sanitize_eventyay_post_id_list( array_merge( $manual_featured, $result['featured_ids'] ) );
			$current_featured  = array_values( array_intersect( $current_featured, $current_speakers ) );

			if ( empty( $current_speakers ) ) {
				delete_post_meta( $event_id, 'wpfa_event_speakers' );
			} else {
				update_post_meta( $event_id, 'wpfa_event_speakers', $current_speakers );
			}

			if ( empty( $current_featured ) ) {
				delete_post_meta( $event_id, 'wpfa_event_featured_speakers' );
			} else {
				update_post_meta( $event_id, 'wpfa_event_featured_speakers', $current_featured );
			}
			$this->sync_eventyay_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers );
		}

		return $result;
	}

	/**
	 * Create or update one Eventyay speaker post.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker     Speaker data.
	 * @param string $post_status WordPress post status for the speaker.
	 * @return array|WP_Error
	 */
	private function upsert_eventyay_speaker_post( $speaker, $post_status = 'draft' ) {
		if ( empty( $speaker['eventyay_speaker_id'] ) || empty( $speaker['name'] ) ) {
			return new WP_Error(
				'eventyay_speaker_missing_id',
				esc_html__( 'Eventyay speaker is missing an ID or name.', 'wpfaevent' )
			);
		}

		$allowed_statuses = array( 'draft', 'publish', 'pending', 'private' );
		if ( ! in_array( $post_status, $allowed_statuses, true ) ) {
			$post_status = 'draft';
		}

		$speaker_id = $this->find_eventyay_speaker_post( $speaker['eventyay_speaker_id'] );
		$post_data  = array(
			'post_title'   => sanitize_text_field( $speaker['name'] ),
			'post_type'    => 'wpfa_speaker',
			'post_status'  => $post_status,
			'post_content' => wp_kses_post( $speaker['bio'] ),
		);
		$created    = false;

		if ( $speaker_id ) {
			$post_data['ID'] = $speaker_id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$saved_id = wp_insert_post( $post_data, true );
			$created  = true;
		}

		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		$saved_id = absint( $saved_id );
		if ( ! $saved_id ) {
			return new WP_Error(
				'eventyay_speaker_save_failed',
				esc_html__( 'Could not save Eventyay speaker.', 'wpfaevent' )
			);
		}

		$session = ! empty( $speaker['sessions'][0] ) && is_array( $speaker['sessions'][0] ) ? $speaker['sessions'][0] : array();
		$social  = ! empty( $speaker['social'] ) && is_array( $speaker['social'] ) ? $speaker['social'] : array();

		update_post_meta( $saved_id, '_wpfa_eventyay_speaker_id', sanitize_text_field( $speaker['eventyay_speaker_id'] ) );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_position', $speaker['position'] );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_organization', $speaker['organization'] );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_bio', $speaker['bio'] );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_headshot_url', $speaker['image'] );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_linkedin', isset( $social['linkedin'] ) ? $social['linkedin'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_twitter', isset( $social['twitter'] ) ? $social['twitter'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_github', isset( $social['github'] ) ? $social['github'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_website', isset( $social['website'] ) ? $social['website'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_talk_title', isset( $session['title'] ) ? $session['title'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_talk_date', isset( $session['date'] ) ? $session['date'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_talk_time', isset( $session['time'] ) ? $session['time'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_talk_end_time', isset( $session['end_time'] ) ? $session['end_time'] : '' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_speaker_talk_abstract', isset( $session['abstract'] ) ? $session['abstract'] : '' );

		if ( ! empty( $speaker['category'] ) && taxonomy_exists( 'wpfa_speaker_category' ) ) {
			wp_set_object_terms( $saved_id, sanitize_text_field( $speaker['category'] ), 'wpfa_speaker_category' );
		}

		return array(
			'id'      => $saved_id,
			'created' => $created,
		);
	}

	/**
	 * Find an existing speaker post by Eventyay speaker ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $eventyay_speaker_id Eventyay speaker ID.
	 * @return int
	 */
	private function find_eventyay_speaker_post( $eventyay_speaker_id ) {
		$speaker_ids = get_posts(
			array(
				'post_type'      => 'wpfa_speaker',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Eventyay IDs are stored in speaker post meta for sync idempotency.
				'meta_key'       => '_wpfa_eventyay_speaker_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Eventyay IDs are stored in speaker post meta for sync idempotency.
				'meta_value'     => sanitize_text_field( $eventyay_speaker_id ),
			)
		);

		return ! empty( $speaker_ids[0] ) ? absint( $speaker_ids[0] ) : 0;
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
	private function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Determine whether a speaker post is managed by Eventyay import.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return bool
	 */
	private function is_eventyay_speaker_post( $speaker_id ) {
		$speaker_id = absint( $speaker_id );

		return $speaker_id && '' !== trim( (string) get_post_meta( $speaker_id, '_wpfa_eventyay_speaker_id', true ) );
	}

	/**
	 * Get normalized speaker IDs assigned to an event for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	private function get_eventyay_event_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_speakers', true );

		return $this->sanitize_eventyay_post_id_list( $speaker_ids );
	}

	/**
	 * Get normalized featured speaker IDs assigned to an event for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	private function get_eventyay_event_featured_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_featured_speakers', true );

		return $this->sanitize_eventyay_post_id_list( $speaker_ids );
	}

	/**
	 * Get normalized event IDs assigned to a speaker for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int>
	 */
	private function get_eventyay_speaker_event_ids( $speaker_id ) {
		$event_ids = get_post_meta( $speaker_id, 'wpfa_speaker_events', true );

		return $this->sanitize_eventyay_post_id_list( $event_ids );
	}

	/**
	 * Sanitize, deduplicate, and reindex post IDs for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Post IDs.
	 * @return array<int>
	 */
	private function sanitize_eventyay_post_id_list( $post_ids ) {
		if ( ! is_array( $post_ids ) ) {
			return array();
		}

		$post_ids = array_map( 'absint', $post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Sync speaker-side event relationship meta after Eventyay import.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $event_id          Event post ID.
	 * @param array<int> $previous_speakers Speaker IDs before sync.
	 * @param array<int> $current_speakers  Speaker IDs after sync.
	 * @return void
	 */
	private function sync_eventyay_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
		$event_id          = absint( $event_id );
		$previous_speakers = $this->sanitize_eventyay_post_id_list( $previous_speakers );
		$current_speakers  = $this->sanitize_eventyay_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		foreach ( array_diff( $previous_speakers, $current_speakers ) as $speaker_id ) {
			$this->remove_eventyay_event_from_speaker( $speaker_id, $event_id );
		}

		foreach ( $current_speakers as $speaker_id ) {
			$this->add_eventyay_event_to_speaker( $speaker_id, $event_id );
		}
	}

	/**
	 * Add an event ID to a speaker's related events for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id   Event post ID.
	 * @return void
	 */
	private function add_eventyay_event_to_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$event_ids   = $this->get_eventyay_speaker_event_ids( $speaker_id );
		$event_ids[] = $event_id;

		update_post_meta( $speaker_id, 'wpfa_speaker_events', $this->sanitize_eventyay_post_id_list( $event_ids ) );
	}

	/**
	 * Remove an event ID from a speaker's related events for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id   Event post ID.
	 * @return void
	 */
	private function remove_eventyay_event_from_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id ) {
			return;
		}

		$event_ids = array_diff( $this->get_eventyay_speaker_event_ids( $speaker_id ), array( $event_id ) );
		$event_ids = $this->sanitize_eventyay_post_id_list( $event_ids );

		if ( empty( $event_ids ) ) {
			delete_post_meta( $speaker_id, 'wpfa_speaker_events' );
			return;
		}

		update_post_meta( $speaker_id, 'wpfa_speaker_events', $event_ids );
	}

	/**
	 * Sync imported sponsor and exhibitor data into dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Event post ID.
	 * @param string $event_slug Eventyay event slug.
	 * @param array  $settings   Import settings.
	 * @return array<string, int>
	 */
	private function sync_eventyay_partner_data( $event_id, $event_slug, $settings ) {
		$event_id  = absint( $event_id );
		$event_slug = sanitize_title( (string) $event_slug );
		$settings  = is_array( $settings ) ? $settings : array();

		$result = array(
			'sponsors'      => 0,
			'exhibitors'    => 0,
			'partner_skipped' => 0,
		);

		if ( ! $event_id || '' === $event_slug ) {
			return $result;
		}

		$sponsors = $this->fetch_eventyay_partner_collection( $settings, $event_slug, 'sponsors' );
		if ( is_wp_error( $sponsors ) ) {
			++$result['partner_skipped'];
		} else {
			$normalized_sponsors = $this->normalize_eventyay_sponsor_resources( $sponsors['resources'], $settings );
			$existing_sponsors   = $this->read_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', array() );
			$sponsor_groups      = $this->merge_eventyay_sponsor_groups( $normalized_sponsors, $existing_sponsors );
			$write_result        = $this->write_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', $sponsor_groups );

			if ( is_wp_error( $write_result ) ) {
				++$result['partner_skipped'];
			} else {
				$result['sponsors'] = count( $normalized_sponsors );
			}
		}

		$exhibitors = $this->fetch_eventyay_partner_collection( $settings, $event_slug, 'exhibitors' );
		if ( is_wp_error( $exhibitors ) ) {
			++$result['partner_skipped'];
		} else {
			$normalized_exhibitors = $this->normalize_eventyay_exhibitor_resources( $exhibitors['resources'], $settings );
			$existing_exhibitors   = $this->read_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', array() );
			$merged_exhibitors     = $this->merge_eventyay_flat_records( $normalized_exhibitors, $existing_exhibitors );
			$write_result          = $this->write_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', $merged_exhibitors );

			if ( is_wp_error( $write_result ) ) {
				++$result['partner_skipped'];
			} else {
				$result['exhibitors'] = count( $normalized_exhibitors );
			}
		}

		return $result;
	}

	/**
	 * Build candidate sponsor/exhibitor endpoints for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @param string $resource_type Resource type, sponsors or exhibitors.
	 * @return array<int, string>
	 */
	private function build_eventyay_partner_endpoint_candidates( $settings, $event_slug, $resource_type ) {
		$resource_type = sanitize_key( $resource_type );
		if ( ! in_array( $resource_type, array( 'sponsors', 'exhibitors' ), true ) ) {
			return array();
		}

		$settings = wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			$this->get_eventyay_partner_import_defaults()
		);
		$base_url = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = sanitize_title( (string) $event_slug );
		$endpoints = array();

		if ( ! empty( $settings['organizer_slug'] ) && ! empty( $event_slug ) ) {
			$modern_endpoint = $this->build_eventyay_modern_partner_endpoint( $settings, $event_slug, $resource_type );
			if ( ! is_wp_error( $modern_endpoint ) ) {
				$endpoints[] = $modern_endpoint;
			}
		}

		if ( empty( $base_url ) || ! $this->parser->is_valid_http_url( $base_url ) || empty( $event_slug ) ) {
			return array_values( array_unique( array_filter( $endpoints ) ) );
		}

		$legacy_paths = array(
			'v1/events/' . rawurlencode( $event_slug ) . '/' . $resource_type,
		);

		if ( false !== strpos( $base_url, 'eventyay.com' ) && false === strpos( $base_url, 'api.eventyay.com' ) ) {
			$legacy_paths[] = 'api/v1/events/' . rawurlencode( $event_slug ) . '/' . $resource_type;
		}

		foreach ( $legacy_paths as $path ) {
			$endpoints[] = add_query_arg(
				array(
					'page[size]' => absint( apply_filters( 'wpfaevent_eventyay_partner_import_page_size', 100, $resource_type ) ),
					'sort'       => 'sponsors' === $resource_type ? 'level' : 'position',
					'filter'     => '[]',
				),
				trailingslashit( $base_url ) . ltrim( $path, '/' )
			);
		}

		return array_values( array_unique( array_filter( $endpoints ) ) );
	}

	/**
	 * Build the newer organizer/event partner endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @param string $resource_type Resource type.
	 * @return string|WP_Error
	 */
	private function build_eventyay_modern_partner_endpoint( $settings, $event_slug, $resource_type ) {
		$settings = wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			$this->get_eventyay_partner_import_defaults()
		);
		$base_url = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = sanitize_title( (string) $event_slug );
		$resource_type = sanitize_key( $resource_type );

		if ( empty( $base_url ) || ! $this->parser->is_valid_http_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_partner_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for sponsor/exhibitor import.', 'wpfaevent' )
			);
		}

		$path = sprintf(
			'api/v1/organizers/%s/events/%s/%s/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug ),
			$resource_type
		);

		$url = trailingslashit( $base_url ) . $path;

		return esc_url_raw(
			add_query_arg(
				array(
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_partner_import_page_size', 50, $resource_type ) ),
					'sort'      => 'sponsors' === $resource_type ? 'level' : 'position',
				),
				$url
			)
		);
	}

	/**
	 * Fetch sponsor or exhibitor records from the first available endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Resource type.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_partner_collection( $settings, $event_slug, $resource_type ) {
		$endpoints = $this->build_eventyay_partner_endpoint_candidates( $settings, $event_slug, $resource_type );
		if ( empty( $endpoints ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_partner_endpoint',
				esc_html__( 'Could not build an Eventyay sponsors/exhibitors endpoint.', 'wpfaevent' )
			);
		}

		$not_found_errors = array();

		foreach ( $endpoints as $endpoint ) {
			$fetched = $this->fetch_eventyay_partner_resources( $endpoint, $settings, $resource_type );
			if ( ! is_wp_error( $fetched ) ) {
				return $fetched;
			}

			if ( ! $this->client->eventyay_error_has_http_status( $fetched, 404 ) ) {
				return $fetched;
			}

			$not_found_errors[] = $fetched;
		}

		return new WP_Error(
			'wpfaevent_eventyay_partner_endpoint_not_found',
			sprintf(
				/* translators: %s: partner resource type. */
				esc_html__( 'Eventyay did not expose a %s endpoint for this event.', 'wpfaevent' ),
				sanitize_text_field( $resource_type )
			),
			array(
				'http_status' => 404,
				'errors'      => array_map(
					static function ( $error ) {
						return is_wp_error( $error ) ? $error->get_error_message() : '';
					},
					$not_found_errors
				),
			)
		);
	}

	/**
	 * Fetch one sponsor/exhibitor endpoint, following pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint     Endpoint URL.
	 * @param array  $settings     Import settings.
	 * @param string $resource_type Resource type.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_partner_resources( $endpoint, $settings, $resource_type ) {
		$resources = array();
		$next_url   = $endpoint;
		$page       = 0;
		$seen_urls  = array();
		$max_pages  = absint( apply_filters( 'wpfaevent_eventyay_partner_import_max_pages', 20, $resource_type ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_partner_pagination_loop',
					esc_html__( 'Eventyay sponsor/exhibitor pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_partner_page_limit',
					esc_html__( 'Eventyay sponsor/exhibitor import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$payload = $this->fetch_eventyay_json( $next_url, isset( $settings['api_token'] ) ? $settings['api_token'] : '' );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $resource ) {
					if ( is_array( $resource ) ) {
						$resources[] = $resource;
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_partner_next_url( $payload['next'], $endpoint ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
				foreach ( $this->parser->eventyay_list_value( $payload['data'] ) as $resource ) {
					if ( is_array( $resource ) ) {
						$resources[] = $resource;
					}
				}

				$next_url = ! empty( $payload['links']['next'] ) ? $this->normalize_eventyay_partner_next_url( $payload['links']['next'], $endpoint ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			$resources[] = $payload;
			$next_url    = '';
		}

		return array(
			'resources' => $resources,
			'pages'     => $page,
			'endpoint'  => esc_url_raw( $endpoint ),
		);
	}

	/**
	 * Normalize sponsor resources for dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Sponsor resources.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_sponsor_resources( $resources, $settings ) {
		$sponsors = array();

		foreach ( $resources as $resource ) {
			$sponsor = $this->normalize_eventyay_sponsor_resource( $resource, $settings );
			if ( ! empty( $sponsor['name'] ) ) {
				$sponsors[] = $sponsor;
			}
		}

		usort(
			$sponsors,
			static function ( $sponsor_a, $sponsor_b ) {
				$level_a = isset( $sponsor_a['level'] ) ? absint( $sponsor_a['level'] ) : 0;
				$level_b = isset( $sponsor_b['level'] ) ? absint( $sponsor_b['level'] ) : 0;

				if ( $level_a !== $level_b ) {
					if ( ! $level_a ) {
						return 1;
					}

					if ( ! $level_b ) {
						return -1;
					}

					return $level_a <=> $level_b;
				}

				return strcasecmp( $sponsor_a['name'], $sponsor_b['name'] );
			}
		);

		return $sponsors;
	}

	/**
	 * Normalize one sponsor resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sponsor_resource Sponsor resource.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_sponsor_resource( $sponsor_resource, $settings ) {
		$sponsor_resource = $this->parser->normalize_eventyay_api_resource( $sponsor_resource );
		$source_id        = $this->parser->eventyay_resource_identifier( $sponsor_resource );
		$name             = $this->parser->eventyay_first_present_text( $sponsor_resource, array( 'name', 'title', 'label' ) );
		$type             = $this->parser->eventyay_first_present_text( $sponsor_resource, array( 'type', 'level_name', 'level-name', 'tier', 'category' ) );
		$level            = $this->parser->eventyay_first_present_raw( $sponsor_resource, array( 'level', 'position', 'order', 'sort_order', 'sort-order' ) );

		return array(
			'id'          => $source_id ? 'eventyay-sponsor-' . sanitize_key( $source_id ) : 'eventyay-sponsor-' . sanitize_title( $name ),
			'source'      => 'eventyay',
			'eventyay_id' => sanitize_text_field( $source_id ),
			'name'        => sanitize_text_field( $name ),
			'description' => $this->parser->eventyay_first_present_rich_text( $sponsor_resource, array( 'description', 'subtitle', 'summary' ) ),
			'link'        => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $sponsor_resource, array( 'url', 'link', 'website', 'website-url', 'website_url' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'image'       => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $sponsor_resource, array( 'logo-url', 'logo_url', 'logo', 'image', 'image-url', 'image_url' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'type'        => sanitize_text_field( $type ),
			'level'       => is_numeric( $level ) ? absint( $level ) : 0,
		);
	}

	/**
	 * Merge imported sponsor groups with manually maintained groups.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported sponsors.
	 * @param array $existing Existing sponsor groups.
	 * @return array
	 */
	private function merge_eventyay_sponsor_groups( $imported, $existing ) {
		$existing = is_array( $existing ) ? $existing : array();
		$groups   = array();

		foreach ( $existing as $group ) {
			if ( ! is_array( $group ) || $this->is_eventyay_sponsor_group( $group ) ) {
				continue;
			}

			$groups[] = $group;
		}

		foreach ( $this->group_eventyay_sponsors( $imported ) as $group ) {
			$groups[] = $group;
		}

		return array_values( $groups );
	}

	/**
	 * Group imported sponsors by type or level.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sponsors Imported sponsors.
	 * @return array
	 */
	private function group_eventyay_sponsors( $sponsors ) {
		$groups = array();

		foreach ( $sponsors as $sponsor ) {
			$group_name = ! empty( $sponsor['type'] ) ? $sponsor['type'] : '';
			if ( '' === trim( $group_name ) && ! empty( $sponsor['level'] ) ) {
				$group_name = sprintf(
					/* translators: %d: Sponsor level number. */
					__( 'Level %d Sponsors', 'wpfaevent' ),
					absint( $sponsor['level'] )
				);
			}

			if ( '' === trim( $group_name ) ) {
				$group_name = __( 'Sponsors', 'wpfaevent' );
			}

			$key = sanitize_key( $group_name );
			if ( empty( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'group_name'         => sanitize_text_field( $group_name ),
					'source'             => 'eventyay',
					'eventyay_group_key' => $key,
					'centered'           => false,
					'logo_size'          => 160,
					'sponsors'           => array(),
				);
			}

			$groups[ $key ]['sponsors'][] = $sponsor;
		}

		return array_values( $groups );
	}

	/**
	 * Determine whether a sponsor group came from Eventyay import.
	 *
	 * @since 1.0.0
	 *
	 * @param array $group Sponsor group.
	 * @return bool
	 */
	private function is_eventyay_sponsor_group( $group ) {
		if ( ! empty( $group['source'] ) && 'eventyay' === $group['source'] ) {
			return true;
		}

		if ( empty( $group['sponsors'] ) || ! is_array( $group['sponsors'] ) ) {
			return false;
		}

		foreach ( $group['sponsors'] as $sponsor ) {
			if ( is_array( $sponsor ) && ! empty( $sponsor['source'] ) && 'eventyay' === $sponsor['source'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize exhibitor resources for dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Exhibitor resources.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_exhibitor_resources( $resources, $settings ) {
		$exhibitors = array();

		foreach ( $resources as $resource ) {
			$exhibitor = $this->normalize_eventyay_exhibitor_resource( $resource, $settings );
			if ( ! empty( $exhibitor['name'] ) ) {
				$exhibitors[] = $exhibitor;
			}
		}

		usort(
			$exhibitors,
			static function ( $exhibitor_a, $exhibitor_b ) {
				$position_a = isset( $exhibitor_a['position'] ) ? absint( $exhibitor_a['position'] ) : 0;
				$position_b = isset( $exhibitor_b['position'] ) ? absint( $exhibitor_b['position'] ) : 0;

				if ( $position_a !== $position_b ) {
					if ( ! $position_a ) {
						return 1;
					}

					if ( ! $position_b ) {
						return -1;
					}

					return $position_a <=> $position_b;
				}

				return strcasecmp( $exhibitor_a['name'], $exhibitor_b['name'] );
			}
		);

		return $exhibitors;
	}

	/**
	 * Normalize one exhibitor resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $exhibitor_resource Exhibitor resource.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_exhibitor_resource( $exhibitor_resource, $settings ) {
		$exhibitor_resource = $this->parser->normalize_eventyay_api_resource( $exhibitor_resource );
		$source_id          = $this->parser->eventyay_resource_identifier( $exhibitor_resource );
		$name               = $this->parser->eventyay_first_present_text( $exhibitor_resource, array( 'name', 'title', 'label' ) );
		$position           = $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'position', 'order', 'sort_order', 'sort-order' ) );

		return array(
			'id'            => $source_id ? 'eventyay-exhibitor-' . sanitize_key( $source_id ) : 'eventyay-exhibitor-' . sanitize_title( $name ),
			'source'        => 'eventyay',
			'eventyay_id'   => sanitize_text_field( $source_id ),
			'name'          => sanitize_text_field( $name ),
			'description'   => $this->parser->eventyay_first_present_rich_text( $exhibitor_resource, array( 'description', 'subtitle', 'summary' ) ),
			'link'          => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'url', 'link', 'website', 'website-url', 'website_url' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'logo'          => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'logo-url', 'logo_url', 'logo', 'image', 'image-url', 'image_url' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'banner'        => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'banner-url', 'banner_url', 'banner' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'video'         => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'video-url', 'video_url', 'video' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'slides'        => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'slides-url', 'slides_url', 'slides' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'contact_email' => sanitize_email( $this->parser->eventyay_first_present_text( $exhibitor_resource, array( 'contact-email', 'contact_email', 'email' ) ) ),
			'contact_link'  => $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $exhibitor_resource, array( 'contact-link', 'contact_link' ) ), isset( $settings['base_url'] ) ? $settings['base_url'] : '' ),
			'position'      => is_numeric( $position ) ? absint( $position ) : 0,
		);
	}

	/**
	 * Merge imported Eventyay flat partner records with manual records.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported records.
	 * @param array $existing Existing records.
	 * @return array
	 */
	private function merge_eventyay_flat_records( $imported, $existing ) {
		$existing = is_array( $existing ) ? $existing : array();
		$records  = array();

		foreach ( $existing as $record ) {
			if ( ! is_array( $record ) || ( ! empty( $record['source'] ) && 'eventyay' === $record['source'] ) ) {
				continue;
			}

			$records[] = $record;
		}

		foreach ( $imported as $record ) {
			$records[] = $record;
		}

		return array_values( $records );
	}

	/**
	 * Get default Eventyay import settings used by partner sync helpers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	private function get_eventyay_partner_import_defaults() {
		return array(
			'base_url'       => 'https://api.eventyay.com',
			'organizer_slug' => '',
			'api_token'      => '',
		);
	}

	/**
	 * Normalize a paginated partner next URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $next_url  Raw next URL.
	 * @param string $base_url  Reference endpoint URL.
	 * @return string|WP_Error
	 */
	private function normalize_eventyay_partner_next_url( $next_url, $base_url ) {
		$next_url = trim( (string) $next_url );

		if ( '' === $next_url ) {
			return '';
		}

		$base_url = untrailingslashit( esc_url_raw( $base_url ) );
		if ( empty( $base_url ) || ! $this->parser->is_valid_http_url( $base_url ) ) {
			return '';
		}

		$parts = wp_parse_url( $next_url );
		if ( ! empty( $parts['scheme'] ) || ! empty( $parts['host'] ) ) {
			if ( ! $this->eventyay_urls_share_origin( $next_url, $base_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_untrusted_next_url',
					esc_html__( 'Eventyay pagination returned a next URL outside the configured Eventyay host.', 'wpfaevent' )
				);
			}

			if ( ! wp_http_validate_url( $next_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_invalid_next_url',
					esc_html__( 'Eventyay pagination returned an invalid next URL.', 'wpfaevent' )
				);
			}

			return esc_url_raw( $next_url );
		}

		if ( 0 === strpos( $next_url, '?' ) ) {
			$base_path = preg_replace( '/[?#].*$/', '', $base_url );
			$next_url  = $base_path . $next_url;

			if ( ! wp_http_validate_url( $next_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_invalid_next_url',
					esc_html__( 'Eventyay pagination returned an invalid next URL.', 'wpfaevent' )
				);
			}

			return esc_url_raw( $next_url );
		}

		return esc_url_raw( trailingslashit( $base_url ) . ltrim( $next_url, '/' ) );
	}

	/**
	 * Check whether two URLs share the same scheme, host, and port.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url      URL to test.
	 * @param string $base_url Base URL.
	 * @return bool
	 */
	private function eventyay_urls_share_origin( $url, $base_url ) {
		$url_parts  = wp_parse_url( $url );
		$base_parts = wp_parse_url( $base_url );

		if ( empty( $url_parts['scheme'] ) || empty( $url_parts['host'] ) || empty( $base_parts['scheme'] ) || empty( $base_parts['host'] ) ) {
			return false;
		}

		$url_scheme  = strtolower( $url_parts['scheme'] );
		$base_scheme = strtolower( $base_parts['scheme'] );
		$url_port    = isset( $url_parts['port'] ) ? absint( $url_parts['port'] ) : $this->default_port_for_scheme( $url_scheme );
		$base_port   = isset( $base_parts['port'] ) ? absint( $base_parts['port'] ) : $this->default_port_for_scheme( $base_scheme );

		return $url_scheme === $base_scheme
			&& strtolower( $url_parts['host'] ) === strtolower( $base_parts['host'] )
			&& $url_port === $base_port;
	}

	/**
	 * Get the default network port for a URL scheme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scheme URL scheme.
	 * @return int|null
	 */
	private function default_port_for_scheme( $scheme ) {
		if ( 'https' === strtolower( $scheme ) ) {
			return 443;
		}

		if ( 'http' === strtolower( $scheme ) ) {
			return 80;
		}

		return null;
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

		$cpt_result     = $this->sync_eventyay_speaker_posts( $import['speakers'], $event_id );
		$partner_result = $this->sync_eventyay_partner_data( $event_id, $event_slug, $settings );

		$schedule_rows = $this->write_eventyay_schedule_table( $event_id, $import['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			return $schedule_rows;
		}

		update_post_meta( $event_id, '_wpfa_eventyay_speakers_synced_at', time() );

		return array(
			'speakers'         => count( $import['speakers'] ),
			'created_speakers' => $cpt_result['created'],
			'updated_speakers' => $cpt_result['updated'],
			'sponsors'         => isset( $partner_result['sponsors'] ) ? $partner_result['sponsors'] : 0,
			'exhibitors'       => isset( $partner_result['exhibitors'] ) ? $partner_result['exhibitors'] : 0,
			'partner_skipped'   => isset( $partner_result['partner_skipped'] ) ? $partner_result['partner_skipped'] : 0,
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
