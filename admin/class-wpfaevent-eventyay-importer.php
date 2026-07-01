<?php
/**
 * Eventyay import and dashboard sync functionality.
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
 * Handles Eventyay settings, imports, and dashboard syncs.
 *
 * Acts as the orchestrator/facade for the decoupled modular classes.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Eventyay_Importer {

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
	 * Settings Renderer.
	 *
	 * @var Wpfaevent_Admin_Settings_Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client       = new Wpfaevent_Eventyay_API_Client();
		$this->parser       = new Wpfaevent_JSONAPI_Parser();
		$this->event_repo   = new Wpfaevent_Event_Repository();
		$this->speaker_repo = new Wpfaevent_Speaker_Repository();
		$this->store        = new Wpfaevent_Partner_Json_Store();
		$this->renderer     = new Wpfaevent_Admin_Settings_Renderer( $this );
	}

	/**
	 * Get the API client instance.
	 *
	 * @return Wpfaevent_Eventyay_API_Client
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Get the JSONAPI parser instance.
	 *
	 * @return Wpfaevent_JSONAPI_Parser
	 */
	public function get_parser() {
		return $this->parser;
	}

	/**
	 * Get the event repository instance.
	 *
	 * @return Wpfaevent_Event_Repository
	 */
	public function get_event_repository() {
		return $this->event_repo;
	}

	/**
	 * Get the speaker repository instance.
	 *
	 * @return Wpfaevent_Speaker_Repository
	 */
	public function get_speaker_repository() {
		return $this->speaker_repo;
	}

	/**
	 * Get the partner JSON store instance.
	 *
	 * @return Wpfaevent_Partner_Json_Store
	 */
	public function get_partner_store() {
		return $this->store;
	}

	/**
	 * Sanitize Eventyay import options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input Raw option input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_eventyay_import_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->get_eventyay_import_default_settings();
		$current  = $this->get_eventyay_import_settings();
		$settings = $defaults;

		$base_url = isset( $input['base_url'] ) ? trim( (string) wp_unslash( $input['base_url'] ) ) : '';
		$base_url = $base_url ? esc_url_raw( $base_url ) : $defaults['base_url'];

		if ( ! wp_http_validate_url( $base_url ) ) {
			add_settings_error(
				'wpfaevent_eventyay_import',
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL must be a valid HTTP(S) URL.', 'wpfaevent' ),
				'error'
			);
			$base_url = $current['base_url'];
		}

		$settings['organizer_slug'] = isset( $input['organizer_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['organizer_slug'] ) : '';
		$settings['event_slug']     = isset( $input['event_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['event_slug'] ) : '';
		$parsed_event_url           = $this->parse_eventyay_public_event_url( $base_url );

		if ( $parsed_event_url ) {
			$base_url = $parsed_event_url['base_url'];

			if ( empty( $settings['organizer_slug'] ) ) {
				$settings['organizer_slug'] = $parsed_event_url['organizer_slug'];
			}

			if ( empty( $settings['event_slug'] ) ) {
				$settings['event_slug'] = $parsed_event_url['event_slug'];
			}
		}

		$settings['base_url'] = untrailingslashit( $base_url );

		if ( ! empty( $input['clear_api_token'] ) ) {
			$settings['api_token'] = '';
		} elseif ( isset( $input['api_token'] ) && '' !== trim( (string) wp_unslash( $input['api_token'] ) ) ) {
			$settings['api_token'] = sanitize_text_field( wp_unslash( $input['api_token'] ) );
		} else {
			$settings['api_token'] = isset( $current['api_token'] ) ? $current['api_token'] : '';
		}

		$post_status = isset( $input['post_status'] ) ? sanitize_key( wp_unslash( $input['post_status'] ) ) : $defaults['post_status'];
		if ( ! in_array( $post_status, array( 'draft', 'publish', 'pending', 'private' ), true ) ) {
			$post_status = $defaults['post_status'];
		}
		$settings['post_status'] = $post_status;

		return $settings;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		$this->renderer->render_settings_page();
	}

	/**
	 * Render the Eventyay update page.
	 *
	 * @since 1.0.0
	 */
	public function render_update_events_page() {
		$this->renderer->render_update_events_page();
	}

	/**
	 * Import Eventyay events using the saved newer REST API settings.
	 *
	 * @since 1.0.0
	 */
	public function handle_eventyay_events_import() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to import Eventyay events.', 'wpfaevent' ) );
		}

		check_admin_referer( 'wpfaevent_import_eventyay_events' );

		$return_page = 'wpfaevent-import-events';
		if ( isset( $_POST['wpfaevent_eventyay_return_page'] ) ) {
			$return_page = sanitize_key( wp_unslash( $_POST['wpfaevent_eventyay_return_page'] ) );
		}

		if ( ! in_array( $return_page, array( 'wpfaevent-import-events', 'wpfaevent-update-events' ), true ) ) {
			$return_page = 'wpfaevent-import-events';
		}

		$result     = $this->import_eventyay_events_from_settings();
		$notice_key = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();

		if ( is_wp_error( $result ) ) {
			set_transient(
				$notice_key,
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				),
				MINUTE_IN_SECONDS
			);
		} else {
			set_transient(
				$notice_key,
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: fetched events, 2: created events, 3: updated events, 4: skipped events, 5: sessions, 6: speakers, 7: sponsors, 8: exhibitors, 9: schedule rows, 10: about updates, 11: skipped program imports, 12: skipped sponsor/exhibitor imports. */
						esc_html__( 'Fetched %1$d Eventyay event(s). Created %2$d, updated %3$d, skipped %4$d. Imported %5$d session(s), %6$d speaker(s), %7$d sponsor(s), %8$d exhibitor(s), %9$d schedule row(s), and updated %10$d about section(s); skipped program import for %11$d event(s) and sponsor/exhibitor import for %12$d event(s).', 'wpfaevent' ),
						absint( $result['fetched'] ),
						absint( $result['created'] ),
						absint( $result['updated'] ),
						absint( $result['skipped'] ),
						absint( $result['sessions'] ),
						absint( $result['speakers'] ),
						absint( $result['sponsors'] ),
						absint( $result['exhibitors'] ),
						absint( $result['schedule_rows'] ),
						absint( $result['about_updates'] ),
						absint( $result['program_skipped'] ),
						absint( $result['partner_skipped'] )
					),
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=wpfa_event&page=' . $return_page ) );
		exit;
	}

	/**
	 * Get default Eventyay import settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_default_settings() {
		return array(
			'base_url'       => 'https://eventyay.com',
			'organizer_slug' => '',
			'event_slug'     => '',
			'api_token'      => '',
			'post_status'    => 'draft',
		);
	}

	/**
	 * Get Eventyay import settings with defaults applied.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_settings() {
		$settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
	}

	/**
	 * Sanitize a path segment used by Eventyay organizer and event slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw path segment.
	 * @return string
	 */
	public function sanitize_eventyay_path_segment( $value ) {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );

		return preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
	}

	/**
	 * Parse a public Eventyay event URL into root URL and path slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Public Eventyay URL.
	 * @return array<string, string>
	 */
	public function parse_eventyay_public_event_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
			return array();
		}

		$path = trim( $parts['path'], '/' );
		if ( '' === $path || 0 === strpos( $path, 'api/' ) || 0 === strpos( $path, 'v1/' ) ) {
			return array();
		}

		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( count( $segments ) < 2 ) {
			return array();
		}

		$organizer_slug = $this->sanitize_eventyay_path_segment( $segments[0] );
		$event_slug     = $this->sanitize_eventyay_path_segment( $segments[1] );

		if ( empty( $organizer_slug ) || empty( $event_slug ) ) {
			return array();
		}

		$base_url = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$base_url .= ':' . absint( $parts['port'] );
		}

		return array(
			'base_url'       => esc_url_raw( $base_url ),
			'organizer_slug' => $organizer_slug,
			'event_slug'     => $event_slug,
		);
	}

	/**
	 * Import Eventyay event resources from saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Import result.
	 */
	public function import_eventyay_events_from_settings() {
		$settings = $this->get_eventyay_import_settings();

		if ( empty( $settings['organizer_slug'] ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'Please save an Eventyay organizer slug before importing.', 'wpfaevent' )
			);
		}

		$fetched = $this->client->fetch_eventyay_event_resources( $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		$events = isset( $fetched['events'] ) && is_array( $fetched['events'] ) ? $fetched['events'] : array();
		if ( empty( $events ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_no_events',
				esc_html__( 'No Eventyay events were returned by the configured endpoint.', 'wpfaevent' )
			);
		}

		$result = array(
			'fetched'          => count( $events ),
			'created'          => 0,
			'updated'          => 0,
			'skipped'          => 0,
			'sessions'         => 0,
			'speakers'         => 0,
			'sponsors'         => 0,
			'exhibitors'       => 0,
			'created_speakers' => 0,
			'updated_speakers' => 0,
			'about_updates'    => 0,
			'schedule_rows'    => 0,
			'program_skipped'  => 0,
			'partner_skipped'  => 0,
		);

		foreach ( $events as $event ) {
			$event_res = $this->import_single_eventyay_event( $event, $settings );
			if ( is_wp_error( $event_res ) ) {
				++$result['skipped'];
				continue;
			}

			$result['created']          += absint( $event_res['created'] );
			$result['updated']          += absint( $event_res['updated'] );
			$result['skipped']          += absint( $event_res['skipped'] );
			$result['sessions']         += absint( $event_res['sessions'] );
			$result['speakers']         += absint( $event_res['speakers'] );
			$result['sponsors']         += absint( $event_res['sponsors'] );
			$result['exhibitors']       += absint( $event_res['exhibitors'] );
			$result['created_speakers'] += absint( $event_res['created_speakers'] );
			$result['updated_speakers'] += absint( $event_res['updated_speakers'] );
			$result['about_updates']    += absint( $event_res['about_updates'] );
			$result['schedule_rows']    += absint( $event_res['schedule_rows'] );
			$result['program_skipped']  += absint( $event_res['program_skipped'] );
			$result['partner_skipped']  += absint( $event_res['partner_skipped'] );
		}

		return $result;
	}

	/**
	 * Import a single Eventyay event's details, partner data, and program.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event    Event data array.
	 * @param array $settings Import settings.
	 * @return array|WP_Error Import results.
	 */
	public function import_single_eventyay_event( $event, $settings ) {
		$result = array(
			'created'          => 0,
			'updated'          => 0,
			'skipped'          => 0,
			'sessions'         => 0,
			'speakers'         => 0,
			'sponsors'         => 0,
			'exhibitors'       => 0,
			'created_speakers' => 0,
			'updated_speakers' => 0,
			'about_updates'    => 0,
			'schedule_rows'    => 0,
			'program_skipped'  => 0,
			'partner_skipped'  => 0,
		);

		$upsert = $this->event_repo->upsert_eventyay_event_post( $event, $settings );

		if ( is_wp_error( $upsert ) ) {
			$result['skipped'] = 1;
			return $upsert;
		}

		if ( ! empty( $upsert['created'] ) ) {
			$result['created'] = 1;
		} else {
			$result['updated'] = 1;
		}

		$dashboard = $this->sync_eventyay_event_dashboard_data( $upsert['id'], $event, $settings, $upsert['event_slug'] );
		if ( is_wp_error( $dashboard ) ) {
			$result['program_skipped'] = 1;
		} else {
			$result['about_updates'] += absint( $dashboard['about_updated'] );
		}

		$partners = $this->import_eventyay_event_partner_data( $upsert['id'], $event, $settings, $upsert['event_slug'] );
		if ( is_wp_error( $partners ) ) {
			$result['partner_skipped'] = 1;
		} else {
			$result['sponsors']        += absint( $partners['sponsor_count'] );
			$result['exhibitors']      += absint( $partners['exhibitor_count'] );
			$result['partner_skipped'] += absint( $partners['skipped'] );
		}

		$program = $this->import_eventyay_event_program( $upsert['id'], $settings, $upsert['event_slug'] );
		if ( is_wp_error( $program ) ) {
			$result['program_skipped'] = 1;
		} else {
			$result['sessions']         += absint( $program['session_count'] );
			$result['speakers']         += absint( $program['speaker_count'] );
			$result['created_speakers'] += absint( $program['created_speakers'] );
			$result['updated_speakers'] += absint( $program['updated_speakers'] );
			$result['schedule_rows']    += absint( $program['schedule_rows'] );
		}

		return $result;
	}

	/**
	 * Fetch Eventyay event resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array|WP_Error Event resources and metadata.
	 */
	private function fetch_eventyay_event_resources( $settings ) {
		$endpoints = $this->build_eventyay_event_endpoint_candidates( $settings );
		if ( is_wp_error( $endpoints ) ) {
			return $endpoints;
		}

		$not_found_errors = array();

		foreach ( $endpoints as $endpoint ) {
			$fetched = $this->fetch_eventyay_event_resources_from_endpoint( $endpoint, $settings );
			if ( ! is_wp_error( $fetched ) ) {
				return $fetched;
			}

			if ( ! $this->eventyay_error_has_http_status( $fetched, 404 ) ) {
				return $fetched;
			}

			$not_found_errors[] = $fetched;
		}

		return $this->eventyay_event_not_found_error( $endpoints, $not_found_errors );
	}

	/**
	 * Fetch Eventyay events from one endpoint, following paginated responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Event endpoint URL.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Event resources and metadata.
	 */
	private function fetch_eventyay_event_resources_from_endpoint( $endpoint, $settings ) {
		if ( empty( $endpoint ) || ! wp_http_validate_url( $endpoint ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$events    = array();
		$next_url  = $endpoint;
		$page      = 0;
		$seen_urls = array();
		$max_pages = absint( apply_filters( 'wpfaevent_eventyay_import_max_pages', 20 ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_pagination_loop',
					esc_html__( 'Eventyay pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_page_limit',
					esc_html__( 'Eventyay import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $event ) {
					if ( is_array( $event ) ) {
						$events[] = $this->hydrate_eventyay_event_resource( $event, $settings, true );
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			foreach ( $this->extract_eventyay_event_resources( $payload ) as $event ) {
				$events[] = $this->hydrate_eventyay_event_resource( $event, $settings, false );
			}
			$next_url = '';
		}

		return array(
			'events'   => $events,
			'pages'    => $page,
			'endpoint' => $endpoint,
		);
	}

	/**
	 * Build Eventyay event endpoints to try for the saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array|WP_Error Endpoint URLs.
	 */
	private function build_eventyay_event_endpoint_candidates( $settings ) {
		$primary_endpoint = $this->build_eventyay_events_endpoint( $settings );
		if ( is_wp_error( $primary_endpoint ) ) {
			return $primary_endpoint;
		}

		$endpoints = array( $primary_endpoint );

		if ( ! empty( $settings['event_slug'] ) ) {
			$endpoints = array_merge(
				$endpoints,
				$this->build_eventyay_legacy_event_endpoint_candidates( $settings )
			);
		}

		return array_values( array_unique( array_filter( $endpoints ) ) );
	}

	/**
	 * Build legacy Open Event API endpoints for event-slug imports.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array Endpoint URLs.
	 */
	private function build_eventyay_legacy_event_endpoint_candidates( $settings ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $settings['event_slug'] );

		if ( empty( $event_slug ) || empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return array();
		}

		$api_bases   = array( $base_url );
		$api_bases[] = apply_filters( 'wpfaevent_eventyay_legacy_api_base_url', 'https://api.eventyay.com', $settings );
		$endpoints   = array();

		foreach ( array_unique( array_filter( $api_bases ) ) as $api_base ) {
			$api_base = untrailingslashit( esc_url_raw( $api_base ) );
			if ( empty( $api_base ) || ! wp_http_validate_url( $api_base ) ) {
				continue;
			}

			$endpoints[] = esc_url_raw(
				trailingslashit( $this->trim_eventyay_legacy_api_version_path( $api_base ) ) .
				'v1/events/' .
				rawurlencode( $event_slug )
			);
		}

		return $endpoints;
	}

	/**
	 * Trim a trailing /v1 path before building legacy Open Event API URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_base API base URL.
	 * @return string Base URL without a trailing /v1 path.
	 */
	private function trim_eventyay_legacy_api_version_path( $api_base ) {
		return preg_replace( '#/v1/?$#', '', untrailingslashit( $api_base ) );
	}

	/**
	 * Check whether a WP_Error represents a given HTTP status.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error       Error object.
	 * @param int      $http_status HTTP status code.
	 * @return bool
	 */
	private function eventyay_error_has_http_status( $error, $http_status ) {
		if ( ! is_wp_error( $error ) ) {
			return false;
		}

		$data = $error->get_error_data();

		return is_array( $data )
			&& isset( $data['http_status'] )
			&& absint( $data['http_status'] ) === absint( $http_status );
	}

	/**
	 * Build a clear event-not-found error after all candidate endpoints fail.
	 *
	 * @since 1.0.0
	 *
	 * @param array $endpoints        Endpoint URLs that were tried.
	 * @param array $not_found_errors 404 errors returned by the endpoints.
	 * @return WP_Error
	 */
	private function eventyay_event_not_found_error( $endpoints, $not_found_errors ) {
		$tried_endpoints = implode( ', ', array_map( 'esc_url_raw', $endpoints ) );

		return new WP_Error(
			'wpfaevent_eventyay_event_not_found',
			sprintf(
				/* translators: %s: comma-separated Eventyay API endpoint URLs. */
				esc_html__( 'Could not find the Eventyay event. Tried: %s. Please confirm the event exists, the event is visible to your API token, and the API base URL, organizer slug, and event slug are correct.', 'wpfaevent' ),
				$tried_endpoints
			),
			array(
				'http_status' => 404,
				'endpoints'   => array_map( 'esc_url_raw', $endpoints ),
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
	 * Build an Eventyay events endpoint from saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return string|WP_Error Endpoint URL.
	 */
	private function build_eventyay_events_endpoint( $settings ) {
		$settings = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url = untrailingslashit( esc_url_raw( $settings['base_url'] ) );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'The Eventyay organizer slug is missing.', 'wpfaevent' )
			);
		}

		$path = sprintf(
			'api/v1/organizers/%s/events/',
			rawurlencode( $settings['organizer_slug'] )
		);

		if ( ! empty( $settings['event_slug'] ) ) {
			$path .= rawurlencode( $settings['event_slug'] ) . '/';
		}

			$url        = trailingslashit( $base_url ) . $path;
			$query_args = array(
				'lang' => 'en',
			);

			if ( empty( $settings['event_slug'] ) ) {
				$query_args['page_size'] = absint( apply_filters( 'wpfaevent_eventyay_import_page_size', 100 ) );
			}

			return esc_url_raw( add_query_arg( $query_args, $url ) );
	}

		/**
		 * Build the newer Eventyay event detail endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_event_endpoint( $settings, $event_slug ) {
		$settings               = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$settings['event_slug'] = $this->sanitize_eventyay_path_segment( $event_slug );

		return $this->build_eventyay_events_endpoint( $settings );
	}

		/**
		 * Build the newer Eventyay event settings endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_event_settings_endpoint( $settings, $event_slug ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $event_slug );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_event_settings_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for settings import.', 'wpfaevent' )
			);
		}

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/settings/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug )
		);

		return esc_url_raw( add_query_arg( 'lang', 'en', $url ) );
	}

		/**
		 * Normalize and enrich an Eventyay event returned from the list endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event        Eventyay event resource.
		 * @param array $settings     Import settings.
		 * @param bool  $fetch_detail Whether to fetch the detail endpoint if the list item is sparse.
		 * @return array
		 */
	private function hydrate_eventyay_event_resource( $event, $settings, $fetch_detail ) {
		$event      = $this->normalize_eventyay_event_resource( $event );
		$event_slug = $this->eventyay_event_slug( $event );

		if ( $fetch_detail && $event_slug && $this->eventyay_event_resource_needs_detail( $event ) ) {
			$detail_endpoint = $this->build_eventyay_event_endpoint( $settings, $event_slug );

			if ( ! is_wp_error( $detail_endpoint ) ) {
				$detail_payload = $this->fetch_eventyay_rest_json( $detail_endpoint, $settings['api_token'] );

				if ( ! is_wp_error( $detail_payload ) ) {
					foreach ( $this->extract_eventyay_event_resources( $detail_payload ) as $detail_event ) {
						$event = $this->merge_eventyay_event_resource( $event, $detail_event );
						break;
					}
				}
			}
		}

		if ( $event_slug && apply_filters( 'wpfaevent_eventyay_import_fetch_settings', true, $event, $settings ) ) {
			$settings_payload = $this->fetch_eventyay_event_settings_resource( $settings, $event_slug );

			if ( ! is_wp_error( $settings_payload ) && is_array( $settings_payload ) && ! empty( $settings_payload ) ) {
				$event['_eventyay_settings'] = $settings_payload;
			}
		}

		return $event;
	}

		/**
		 * Extract Eventyay event resources from REST or JSON:API-shaped payloads.
		 *
		 * @since 1.0.0
		 *
		 * @param array $payload API payload.
		 * @return array
		 */
	private function extract_eventyay_event_resources( $payload ) {
		$resources = array();

		if ( ! is_array( $payload ) ) {
			return $resources;
		}

		if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
			foreach ( $payload['results'] as $event ) {
				if ( is_array( $event ) ) {
					$resources[] = $this->normalize_eventyay_event_resource( $event );
				}
			}

			return $resources;
		}

		if ( array_key_exists( 'data', $payload ) && is_array( $payload['data'] ) ) {
			if ( $this->is_jsonapi_resource( $payload['data'] ) ) {
				return array( $this->normalize_eventyay_event_resource( $payload['data'] ) );
			}

			foreach ( $payload['data'] as $event ) {
				if ( is_array( $event ) ) {
					$resources[] = $this->normalize_eventyay_event_resource( $event );
				}
			}

			return $resources;
		}

		return array( $this->normalize_eventyay_event_resource( $payload ) );
	}

		/**
		 * Normalize an Eventyay event resource into a flat field map.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return array
		 */
	private function normalize_eventyay_event_resource( $event ) {
		if ( ! is_array( $event ) ) {
			return array();
		}

		if ( array_key_exists( 'data', $event ) && is_array( $event['data'] ) && $this->is_jsonapi_resource( $event['data'] ) ) {
			$event = $event['data'];
		}

		$normalized = $event;

		if ( isset( $event['attributes'] ) && is_array( $event['attributes'] ) ) {
			$normalized = array_merge( $event['attributes'], $normalized );
		}

		if ( isset( $event['id'] ) && is_scalar( $event['id'] ) ) {
			$normalized['_eventyay_source_id'] = sanitize_text_field( (string) $event['id'] );
		}

		if ( ! empty( $event['links']['self'] ) && is_scalar( $event['links']['self'] ) ) {
			$normalized['_eventyay_api_url'] = esc_url_raw( (string) $event['links']['self'] );
		}

		return $normalized;
	}

		/**
		 * Determine whether a list event should be hydrated through its detail endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Normalized Eventyay event resource.
		 * @return bool
		 */
	private function eventyay_event_resource_needs_detail( $event ) {
		$has_start    = '' !== trim( $this->eventyay_event_datetime( $event, 'start' ) );
		$has_location = '' !== trim( $this->eventyay_event_location( $event ) );

		return ! ( $has_start && $has_location );
	}

		/**
		 * Merge a sparse Eventyay event with a hydrated detail event.
		 *
		 * @since 1.0.0
		 *
		 * @param array $base   Existing event fields.
		 * @param array $detail Detail event fields.
		 * @return array
		 */
	private function merge_eventyay_event_resource( $base, $detail ) {
		$base   = $this->normalize_eventyay_event_resource( $base );
		$detail = $this->normalize_eventyay_event_resource( $detail );

		foreach ( $detail as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = $this->merge_eventyay_event_resource( $base[ $key ], $value );
				continue;
			}

			if ( $this->eventyay_value_is_non_empty( $value ) || ! array_key_exists( $key, $base ) ) {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

		/**
		 * Fetch event-level settings such as frontpage/about text when available.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return array|WP_Error
		 */
	private function fetch_eventyay_event_settings_resource( $settings, $event_slug ) {
		$settings_endpoint = $this->build_eventyay_event_settings_endpoint( $settings, $event_slug );
		if ( is_wp_error( $settings_endpoint ) ) {
			return $settings_endpoint;
		}

		return $this->fetch_eventyay_rest_json( $settings_endpoint, $settings['api_token'] );
	}

		/**
		 * Determine whether an Eventyay value is meaningfully populated.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $value Raw Eventyay value.
		 * @return bool
		 */
	private function eventyay_value_is_non_empty( $value ) {
		if ( is_scalar( $value ) ) {
			return '' !== trim( (string) $value );
		}

		return is_array( $value ) && ! empty( $value );
	}

		/**
		 * Build the newer Eventyay submissions endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_submissions_endpoint( $settings, $event_slug ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $event_slug );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_program_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for speaker import.', 'wpfaevent' )
			);
		}

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/submissions/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug )
		);

		return esc_url_raw(
			add_query_arg(
				array(
					'expand'    => 'speakers,track,submission_type,slots.room',
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_program_import_page_size', 50 ) ),
				),
				$url
			)
		);
	}

		/**
		 * Build the newer Eventyay speakers endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_speakers_endpoint( $settings, $event_slug ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $event_slug );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_speakers_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for speaker import.', 'wpfaevent' )
			);
		}

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/speakers/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug )
		);

		return esc_url_raw(
			add_query_arg(
				array(
					'expand'    => 'submissions,submissions.track,submissions.submission_type,submissions.slots.room',
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_speaker_import_page_size', 50 ) ),
				),
				$url
			)
		);
	}

	/**
	 * Build the newer Eventyay schedule slots endpoint for an imported event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return string|WP_Error Endpoint URL.
	 */
	private function build_eventyay_slots_endpoint( $settings, $event_slug ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $event_slug );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_slots_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for schedule import.', 'wpfaevent' )
			);
		}

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/slots/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug )
		);

		return esc_url_raw(
			add_query_arg(
				array(
					'expand'    => 'room,submission,submission.speakers,submission.track,submission.submission_type',
					'lang'      => 'en',
					'page_size' => absint( apply_filters( 'wpfaevent_eventyay_slot_import_page_size', 50 ) ),
				),
				$url
			)
		);
	}

	/**
	 * Build candidate Eventyay partner resource endpoints for an imported event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param array  $event         Eventyay event resource.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Partner resource type. Accepts sponsors or exhibitors.
	 * @return array Endpoint URLs.
	 */
	private function build_eventyay_partner_endpoint_candidates( $settings, $event, $event_slug, $resource_type ) {
		$resource_type = sanitize_key( $resource_type );
		if ( ! in_array( $resource_type, array( 'sponsors', 'exhibitors' ), true ) ) {
			return array();
		}

		$endpoints       = array();
		$modern_endpoint = $this->build_eventyay_modern_partner_endpoint( $settings, $event_slug, $resource_type );
		if ( ! is_wp_error( $modern_endpoint ) ) {
			$endpoints[] = $modern_endpoint;
		}

		$endpoints = array_merge(
			$endpoints,
			$this->build_eventyay_legacy_partner_endpoints( $settings, $event, $event_slug, $resource_type )
		);

		return array_values( array_unique( array_filter( $endpoints ) ) );
	}

	/**
	 * Build the newer organizer/event Eventyay partner endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Partner resource type.
	 * @return string|WP_Error Endpoint URL.
	 */
	private function build_eventyay_modern_partner_endpoint( $settings, $event_slug, $resource_type ) {
		$settings      = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url      = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug    = $this->sanitize_eventyay_path_segment( $event_slug );
		$resource_type = sanitize_key( $resource_type );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
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

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/%s/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug ),
			rawurlencode( $resource_type )
		);

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
	 * Build legacy Eventyay partner endpoints from the Open Event API shape.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param array  $event         Eventyay event resource.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Partner resource type.
	 * @return array Endpoint URLs.
	 */
	private function build_eventyay_legacy_partner_endpoints( $settings, $event, $event_slug, $resource_type ) {
		$settings      = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url      = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug    = $this->sanitize_eventyay_path_segment( $event_slug );
		$resource_type = sanitize_key( $resource_type );
		$source_id     = $this->eventyay_event_first_present_raw( $event, array( '_eventyay_source_id', 'id', 'code', 'identifier' ), false );
		$identifiers   = array();

		if ( is_scalar( $source_id ) && '' !== trim( (string) $source_id ) ) {
			$identifiers[] = sanitize_text_field( (string) $source_id );
		}

		if ( $event_slug ) {
			$identifiers[] = $event_slug;
		}

		if ( empty( $identifiers ) ) {
			return array();
		}

		$api_bases   = array( $base_url );
		$api_bases[] = apply_filters( 'wpfaevent_eventyay_legacy_api_base_url', 'https://api.eventyay.com', $settings );
		$endpoints   = array();

		foreach ( array_unique( array_filter( $api_bases ) ) as $api_base ) {
			$api_base = untrailingslashit( esc_url_raw( $api_base ) );
			if ( empty( $api_base ) || ! wp_http_validate_url( $api_base ) ) {
				continue;
			}

			foreach ( array_unique( array_filter( $identifiers ) ) as $identifier ) {
				$url = trailingslashit( $this->trim_eventyay_legacy_api_version_path( $api_base ) ) .
					'v1/events/' .
					rawurlencode( $identifier ) .
					'/' .
					rawurlencode( $resource_type );

				$endpoints[] = esc_url_raw(
					add_query_arg(
						array(
							'page[size]' => absint( apply_filters( 'wpfaevent_eventyay_partner_import_page_size', 100, $resource_type ) ),
							'sort'       => 'sponsors' === $resource_type ? 'level' : 'position',
							'filter'     => '[]',
						),
						$url
					)
				);
			}
		}

		return $endpoints;
	}

	/**
	 * Fetch Eventyay partner resources from the first available endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param array  $event         Eventyay event resource.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Partner resource type.
	 * @return array|WP_Error Partner resources and metadata.
	 */
	private function fetch_eventyay_partner_collection( $settings, $event, $event_slug, $resource_type ) {
		$endpoints = $this->build_eventyay_partner_endpoint_candidates( $settings, $event, $event_slug, $resource_type );
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

			if ( ! $this->eventyay_error_has_http_status( $fetched, 404 ) ) {
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
				'errors'      => $not_found_errors,
			)
		);
	}

	/**
	 * Fetch one Eventyay partner endpoint, following pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint      API endpoint URL.
	 * @param array  $settings      Import settings.
	 * @param string $resource_type Partner resource type.
	 * @return array|WP_Error Partner resources and metadata.
	 */
	private function fetch_eventyay_partner_resources( $endpoint, $settings, $resource_type ) {
		$resources = array();
		$next_url  = $endpoint;
		$page      = 0;
		$seen_urls = array();
		$max_pages = absint( apply_filters( 'wpfaevent_eventyay_partner_import_max_pages', 20, $resource_type ) );

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

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $resource ) {
					if ( is_array( $resource ) ) {
						$resources[] = $resource;
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
				foreach ( $this->eventyay_list_value( $payload['data'] ) as $resource ) {
					if ( is_array( $resource ) ) {
						$resources[] = $resource;
					}
				}

				$next_url = ! empty( $payload['links']['next'] ) ? $this->normalize_eventyay_next_url( $payload['links']['next'], $current_url ) : '';
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
	 * Normalize Eventyay sponsor resources into dashboard sponsor records.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Eventyay sponsor resources.
	 * @param array $settings  Import settings.
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
	 * Normalize one Eventyay sponsor resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sponsor_resource Eventyay sponsor resource.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_sponsor_resource( $sponsor_resource, $settings ) {
		$sponsor_resource = $this->normalize_eventyay_api_resource( $sponsor_resource );
		$source_id        = $this->eventyay_resource_identifier( $sponsor_resource );
		$name             = $this->eventyay_first_present_text( $sponsor_resource, array( 'name', 'title', 'label' ) );
		$type             = $this->eventyay_first_present_text( $sponsor_resource, array( 'type', 'level_name', 'level-name', 'tier', 'category' ) );
		$level            = $this->eventyay_first_present_raw( $sponsor_resource, array( 'level', 'position', 'order', 'sort_order', 'sort-order' ) );

		return array(
			'id'          => $source_id ? 'eventyay-sponsor-' . sanitize_key( $source_id ) : 'eventyay-sponsor-' . sanitize_title( $name ),
			'source'      => 'eventyay',
			'eventyay_id' => sanitize_text_field( $source_id ),
			'name'        => sanitize_text_field( $name ),
			'description' => $this->eventyay_first_present_rich_text( $sponsor_resource, array( 'description', 'subtitle', 'summary' ) ),
			'link'        => $this->eventyay_url_value( $this->eventyay_first_present_raw( $sponsor_resource, array( 'url', 'link', 'website', 'website-url', 'website_url' ) ), $settings['base_url'] ),
			'image'       => $this->eventyay_url_value( $this->eventyay_first_present_raw( $sponsor_resource, array( 'logo-url', 'logo_url', 'logo', 'image', 'image-url', 'image_url' ) ), $settings['base_url'] ),
			'type'        => sanitize_text_field( $type ),
			'level'       => is_numeric( $level ) ? absint( $level ) : 0,
		);
	}

	/**
	 * Merge imported Eventyay sponsors with manually maintained dashboard groups.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported sponsors.
	 * @param array $existing Existing dashboard sponsor groups.
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

			return $groups;
	}

	/**
	 * Group imported sponsors by Eventyay type or level.
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
	 * Determine whether a sponsor group is owned by Eventyay import.
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
	 * Normalize Eventyay exhibitor resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Eventyay exhibitor resources.
	 * @param array $settings  Import settings.
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
	 * Normalize one Eventyay exhibitor resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $exhibitor_resource Eventyay exhibitor resource.
	 * @param array $settings Import settings.
	 * @return array
	 */
	private function normalize_eventyay_exhibitor_resource( $exhibitor_resource, $settings ) {
		$exhibitor_resource = $this->normalize_eventyay_api_resource( $exhibitor_resource );
		$source_id          = $this->eventyay_resource_identifier( $exhibitor_resource );
		$name               = $this->eventyay_first_present_text( $exhibitor_resource, array( 'name', 'title', 'label' ) );
		$position           = $this->eventyay_first_present_raw( $exhibitor_resource, array( 'position', 'order', 'sort_order', 'sort-order' ) );

		return array(
			'id'            => $source_id ? 'eventyay-exhibitor-' . sanitize_key( $source_id ) : 'eventyay-exhibitor-' . sanitize_title( $name ),
			'source'        => 'eventyay',
			'eventyay_id'   => sanitize_text_field( $source_id ),
			'name'          => sanitize_text_field( $name ),
			'description'   => $this->eventyay_first_present_rich_text( $exhibitor_resource, array( 'description', 'subtitle', 'summary' ) ),
			'link'          => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'url', 'link', 'website', 'website-url', 'website_url' ) ), $settings['base_url'] ),
			'logo'          => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'logo-url', 'logo_url', 'logo', 'image', 'image-url', 'image_url' ) ), $settings['base_url'] ),
			'banner'        => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'banner-url', 'banner_url', 'banner' ) ), $settings['base_url'] ),
			'video'         => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'video-url', 'video_url', 'video' ) ), $settings['base_url'] ),
			'slides'        => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'slides-url', 'slides_url', 'slides' ) ), $settings['base_url'] ),
			'contact_email' => sanitize_email( $this->eventyay_first_present_text( $exhibitor_resource, array( 'contact-email', 'contact_email', 'email' ) ) ),
			'contact_link'  => $this->eventyay_url_value( $this->eventyay_first_present_raw( $exhibitor_resource, array( 'contact-link', 'contact_link' ) ), $settings['base_url'] ),
			'position'      => is_numeric( $position ) ? absint( $position ) : 0,
		);
	}

	/**
	 * Merge imported Eventyay flat records with manually maintained records.
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

			return $records;
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

	/**
	 * Fetch Eventyay submission resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Submission endpoint.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Submission resources and metadata.
	 */
	private function fetch_eventyay_program_resources( $endpoint, $settings ) {
		$submissions = array();
		$next_url    = $endpoint;
		$page        = 0;
		$seen_urls   = array();
		$max_pages   = absint( apply_filters( 'wpfaevent_eventyay_program_import_max_pages', 20 ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_program_pagination_loop',
					esc_html__( 'Eventyay program pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_program_page_limit',
					esc_html__( 'Eventyay program import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $submission ) {
					if ( is_array( $submission ) ) {
						$submissions[] = $submission;
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			$submissions[] = $payload;
			$next_url      = '';
		}

		return array(
			'submissions' => $submissions,
			'pages'       => $page,
		);
	}

	/**
	 * Fetch and normalize speaker profiles for an Eventyay event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Normalized speaker program payload.
	 */
	private function fetch_eventyay_event_speaker_program( $settings, $event_slug ) {
		$endpoint = $this->build_eventyay_speakers_endpoint( $settings, $event_slug );
		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$fetched = $this->fetch_eventyay_speaker_resources( $endpoint, $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		return $this->normalize_eventyay_speakers_payload( $fetched['speakers'], $settings, $event_slug );
	}

	/**
	 * Fetch Eventyay speaker resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Speaker endpoint.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Speaker resources and metadata.
	 */
	private function fetch_eventyay_speaker_resources( $endpoint, $settings ) {
		$speakers  = array();
		$next_url  = $endpoint;
		$page      = 0;
		$seen_urls = array();
		$max_pages = absint( apply_filters( 'wpfaevent_eventyay_speaker_import_max_pages', 20 ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_speaker_pagination_loop',
					esc_html__( 'Eventyay speaker pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_speaker_page_limit',
					esc_html__( 'Eventyay speaker import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $speaker ) {
					if ( is_array( $speaker ) ) {
						$speakers[] = $speaker;
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
				foreach ( $this->eventyay_list_value( $payload['data'] ) as $speaker ) {
					if ( is_array( $speaker ) ) {
						$speakers[] = $speaker;
					}
				}

				$next_url = ! empty( $payload['links']['next'] ) ? $this->normalize_eventyay_next_url( $payload['links']['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			$speakers[] = $payload;
			$next_url   = '';
		}

		return array(
			'speakers' => $speakers,
			'pages'    => $page,
		);
	}

	/**
	 * Fetch and normalize scheduled slots for an Eventyay event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Normalized slot program payload.
	 */
	private function fetch_eventyay_event_slot_program( $settings, $event_slug ) {
		$endpoint = $this->build_eventyay_slots_endpoint( $settings, $event_slug );
		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$fetched = $this->fetch_eventyay_slot_resources( $endpoint, $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		return $this->normalize_eventyay_slots_payload( $fetched['slots'], $settings, $event_slug );
	}

	/**
	 * Fetch Eventyay slot resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Slot endpoint.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Slot resources and metadata.
	 */
	private function fetch_eventyay_slot_resources( $endpoint, $settings ) {
		$slots     = array();
		$next_url  = $endpoint;
		$page      = 0;
		$seen_urls = array();
		$max_pages = absint( apply_filters( 'wpfaevent_eventyay_slot_import_max_pages', 20 ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_slot_pagination_loop',
					esc_html__( 'Eventyay slot pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_slot_page_limit',
					esc_html__( 'Eventyay slot import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $slot ) {
					if ( is_array( $slot ) ) {
						$slots[] = $slot;
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
				foreach ( $this->eventyay_list_value( $payload['data'] ) as $slot ) {
					if ( is_array( $slot ) ) {
						$slots[] = $slot;
					}
				}

				$next_url = ! empty( $payload['links']['next'] ) ? $this->normalize_eventyay_next_url( $payload['links']['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			$slots[]  = $payload;
			$next_url = '';
		}

		return array(
			'slots' => $slots,
			'pages' => $page,
		);
	}

	/**
	 * Normalize a paginated Eventyay next URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $next_url Raw next URL.
	 * @param string $reference_url Current request URL used to resolve relative next links.
	 * @return string|WP_Error
	 */
	private function normalize_eventyay_next_url( $next_url, $reference_url ) {
		$next_url = trim( (string) $next_url );

		if ( empty( $next_url ) ) {
			return '';
		}

		$reference_url = untrailingslashit( esc_url_raw( $reference_url ) );
		if ( empty( $reference_url ) || ! wp_http_validate_url( $reference_url ) ) {
			return '';
		}

		$next_parts = wp_parse_url( $next_url );
		if ( ! empty( $next_parts['scheme'] ) || ! empty( $next_parts['host'] ) ) {
			if ( ! $this->eventyay_urls_share_origin( $next_url, $reference_url ) ) {
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
			$base_path = preg_replace( '/[?#].*$/', '', $reference_url );
			$next_url  = $base_path . $next_url;

			if ( ! wp_http_validate_url( $next_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_invalid_next_url',
					esc_html__( 'Eventyay pagination returned an invalid next URL.', 'wpfaevent' )
				);
			}

			return esc_url_raw( $next_url );
		}

		$base_origin = $this->eventyay_url_origin( $reference_url );
		if ( empty( $base_origin ) ) {
			return '';
		}

		return esc_url_raw( trailingslashit( $base_origin ) . ltrim( $next_url, '/' ) );
	}

	/**
	 * Check whether two URLs share scheme, host, and port.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url      Candidate URL.
	 * @param string $base_url Configured base URL.
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
	 * Get the scheme/host/port origin from a URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL or endpoint.
	 * @return string
	 */
	private function eventyay_url_origin( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$origin = strtolower( $parts['scheme'] ) . '://' . strtolower( $parts['host'] );
		if ( isset( $parts['port'] ) ) {
			$origin .= ':' . absint( $parts['port'] );
		}

		return wp_http_validate_url( $origin ) ? esc_url_raw( $origin ) : '';
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
		if ( 'https' === $scheme ) {
			return 443;
		}

		if ( 'http' === $scheme ) {
			return 80;
		}

		return null;
	}

	/**
	 * Fetch and decode an Eventyay REST API response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url   API endpoint URL.
	 * @param string $api_token Optional API token.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_rest_json( $api_url, $api_token = '' ) {
		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$auth_schemes = $this->get_eventyay_authorization_schemes( $api_token );
		$last_error   = null;

		foreach ( $auth_schemes as $auth_scheme ) {
			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
					'headers'     => $this->build_eventyay_rest_headers( $api_token, $auth_scheme ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_request_failed',
					esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
					array( 'details' => $response->get_error_message() )
				);
			}

			$decoded = $this->decode_eventyay_rest_response( $response, $api_url );
			if ( ! is_wp_error( $decoded ) ) {
				return $decoded;
			}

			$last_error = $decoded;
			if (
				! $this->eventyay_error_has_http_status( $decoded, 401 )
				&& ! $this->eventyay_error_has_http_status( $decoded, 403 )
			) {
				return $decoded;
			}
		}

		return is_wp_error( $last_error ) ? $last_error : new WP_Error(
			'wpfaevent_eventyay_request_failed',
			esc_html__( 'Eventyay request failed.', 'wpfaevent' )
		);
	}

	/**
	 * Decode an Eventyay REST response into an array or structured error.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $response WordPress HTTP response.
	 * @param string $api_url  API endpoint URL.
	 * @return array|WP_Error
	 */
	private function decode_eventyay_rest_response( $response, $api_url ) {
		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'wpfaevent_eventyay_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: Eventyay API URL. */
					esc_html__( 'Eventyay API returned HTTP %1$d for %2$s.', 'wpfaevent' ),
					$status,
					esc_url_raw( $api_url )
				),
				array(
					'http_status' => $status,
					'url'         => esc_url_raw( $api_url ),
					'body'        => $this->decode_eventyay_error_body( $body ),
				)
			);
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_malformed_json',
				esc_html__( 'Eventyay API returned malformed JSON.', 'wpfaevent' ),
				array(
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->truncate_string( $body ),
				)
			);
		}

		return $decoded;
	}

	/**
	 * Build request headers for Eventyay REST calls.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token   Optional API token.
	 * @param string $auth_scheme Authorization scheme.
	 * @return array<string, string>
	 */
	private function build_eventyay_rest_headers( $api_token, $auth_scheme ) {
		$headers = array(
			'Accept' => 'application/json, application/vnd.api+json, text/javascript',
		);

		$authorization = $this->format_eventyay_authorization_header( $api_token, $auth_scheme );
		if ( $authorization ) {
			$headers['Authorization'] = $authorization;
		}

		return $headers;
	}

	/**
	 * Get authorization schemes to try for Eventyay requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token Optional API token.
	 * @return array<int, string>
	 */
	private function get_eventyay_authorization_schemes( $api_token ) {
		$api_token = trim( (string) $api_token );

		if ( '' === $api_token ) {
			return array( '' );
		}

		if ( preg_match( '/^(Token|JWT|Bearer)\s+/i', $api_token ) ) {
			return array( '' );
		}

		$schemes = apply_filters(
			'wpfaevent_eventyay_import_auth_schemes',
			array( 'Token', 'JWT' ),
			$api_token
		);

		if ( ! is_array( $schemes ) ) {
			$schemes = array( 'Token', 'JWT' );
		}

		$schemes = array_filter(
			array_map(
				static function ( $scheme ) {
					return sanitize_text_field( (string) $scheme );
				},
				$schemes
			)
		);

		return ! empty( $schemes ) ? array_values( array_unique( $schemes ) ) : array( 'Token', 'JWT' );
	}

	/**
	 * Format an Authorization header value for Eventyay requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token   Optional API token.
	 * @param string $auth_scheme Authorization scheme.
	 * @return string
	 */
	private function format_eventyay_authorization_header( $api_token, $auth_scheme ) {
		$api_token = trim( sanitize_text_field( (string) $api_token ) );

		if ( '' === $api_token ) {
			return '';
		}

		if ( preg_match( '/^(Token|JWT|Bearer)\s+/i', $api_token ) ) {
			return $api_token;
		}

		$auth_scheme = trim( sanitize_text_field( (string) $auth_scheme ) );
		if ( '' === $auth_scheme ) {
			$auth_scheme = 'Token';
		}

		return $auth_scheme . ' ' . $api_token;
	}

		/**
		 * Get the Eventyay slug from a normalized event resource.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return string
		 */
	private function eventyay_event_slug( $event ) {
		$slug = $this->eventyay_first_present_text( $event, array( 'slug', 'identifier', 'code' ) );

		if ( empty( $slug ) ) {
			$url = $this->eventyay_url_value(
				$this->eventyay_first_present_raw( $event, array( 'url', 'frontend_url', 'frontend-url', 'public_url', 'public-url' ) ),
				''
			);

			if ( $url ) {
				$path  = wp_parse_url( $url, PHP_URL_PATH );
				$parts = array_filter( explode( '/', trim( (string) $path, '/' ) ) );
				$slug  = $parts ? end( $parts ) : '';
			}
		}

		return $this->sanitize_eventyay_path_segment( $slug );
	}

		/**
		 * Get the Eventyay title from a normalized event resource.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return string
		 */
	private function eventyay_event_title( $event ) {
		return $this->eventyay_first_present_text( $event, array( 'name', 'title', 'label' ) );
	}

		/**
		 * Get an event date/time value from Eventyay event or nested metadata.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $event Eventyay event resource.
		 * @param string $type  Date type. Accepts start or end.
		 * @return string
		 */
	private function eventyay_event_datetime( $event, $type ) {
		$keys = ( 'end' === $type )
			? array( 'date_to', 'date-to', 'ends_at', 'ends-at', 'end_time', 'end-time', 'end_date', 'end-date', 'end', 'to' )
			: array( 'date_from', 'date-from', 'starts_at', 'starts-at', 'start_time', 'start-time', 'start_date', 'start-date', 'start', 'from' );

		return $this->eventyay_scalar_value( $this->eventyay_event_first_present_raw( $event, $keys, false ) );
	}

	/**
	 * Get the Eventyay event timezone from likely fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_timezone( $event ) {
		return Wpfaevent_Meta_Event::sanitize_timezone(
			$this->eventyay_event_first_present_raw(
				$event,
				array( 'timezone', 'time_zone', 'time-zone', 'tz' ),
				true
			)
		);
	}

	/**
	 * Get the Eventyay event location from likely field shapes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_location( $event ) {
		$location = $this->eventyay_event_first_present_raw(
			$event,
			array(
				'location',
				'location_name',
				'location-name',
				'searchable_location_name',
				'searchable-location-name',
				'venue',
				'venue_name',
				'venue-name',
				'address',
			),
			false
		);

		return sanitize_text_field( $this->eventyay_location_text_value( $location ) );
	}

		/**
		 * Get Eventyay event languages from likely field shapes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return array<string>
		 */
	private function eventyay_event_languages( $event ) {
		$languages = $this->eventyay_event_first_present_raw(
			$event,
			array(
				'languages',
				'language',
				'event_languages',
				'event-languages',
				'event_language',
				'event-language',
				'supported_languages',
				'supported-languages',
				'content_languages',
				'content-languages',
				'content_locales',
				'content-locales',
				'locales',
				'locale',
			),
			true
		);

		return Wpfaevent_Meta_Event::sanitize_language_list( $languages );
	}

	/**
	 * Get Eventyay event theme colors from event settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return array<string, string>
	 */
	private function eventyay_event_colors( $event ) {
		$color_fields = array(
			'wpfa_event_primary_color'          => array( 'primary_color', 'primary-color', 'primaryColor', 'event_color', 'event-color', 'theme_color', 'theme-color', 'color' ),
			'wpfa_event_hover_button_color'     => array( 'hover_button_color', 'hover-button-color', 'hoverButtonColor', 'button_hover_color', 'button-hover-color' ),
			'wpfa_event_theme_background_color' => array( 'theme_color_background', 'theme-color-background', 'themeColorBackground', 'background_color', 'background-color' ),
			'wpfa_event_theme_success_color'    => array( 'theme_color_success', 'theme-color-success', 'themeColorSuccess', 'success_color', 'success-color' ),
			'wpfa_event_theme_danger_color'     => array( 'theme_color_danger', 'theme-color-danger', 'themeColorDanger', 'danger_color', 'danger-color' ),
		);
		$colors       = array();

		foreach ( $color_fields as $meta_key => $candidate_keys ) {
			$color = Wpfaevent_Meta_Event::sanitize_color_value(
				$this->eventyay_event_first_present_raw( $event, $candidate_keys, true )
			);

			if ( $color ) {
				$colors[ $meta_key ] = $color;
			}
		}

		return $colors;
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
	private function upsert_eventyay_event_post( $event, $settings ) {
		$event      = $this->normalize_eventyay_event_resource( $event );
		$event_slug = $this->eventyay_event_slug( $event );
		if ( empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_event_missing_slug',
				esc_html__( 'An Eventyay event was skipped because it did not contain a slug.', 'wpfaevent' )
			);
		}

		$organizer_slug = $settings['organizer_slug'];
		$title          = $this->eventyay_event_title( $event );
		$title          = $title ? $title : $event_slug;
		$description    = $this->eventyay_event_description( $event );
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

		$start_datetime       = $this->eventyay_event_datetime( $event, 'start' );
		$end_datetime         = $this->eventyay_event_datetime( $event, 'end' );
		$timezone             = $this->eventyay_event_timezone( $event );
		$timezone_object      = $this->eventyay_timezone_object( $timezone );
		$event_is_all_day     = ! $this->eventyay_datetime_has_time( $start_datetime ) && ! $this->eventyay_datetime_has_time( $end_datetime );
		$start_date           = $this->format_eventyay_date( $start_datetime, $timezone_object );
		$end_date             = $this->format_eventyay_date( $end_datetime, $timezone_object );
		$start_time           = $event_is_all_day ? '' : $this->format_eventyay_time( $start_datetime, $timezone_object );
		$end_time             = $event_is_all_day ? '' : $this->format_eventyay_time( $end_datetime, $timezone_object );
		$normalized_starts_at = $this->normalize_eventyay_datetime( $start_datetime );
		$normalized_ends_at   = $this->normalize_eventyay_datetime( $end_datetime );
		$location             = $this->eventyay_event_location( $event );
		$event_url            = $this->eventyay_public_event_url( $event, $settings, $event_slug );
		$languages            = $this->eventyay_event_languages( $event );
		$colors               = $this->eventyay_event_colors( $event );

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

		if ( ! empty( $colors ) || $this->eventyay_event_has_settings_payload( $event ) ) {
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

		$source_id = $this->eventyay_event_first_present_raw( $event, array( '_eventyay_source_id', 'id', 'code', 'identifier' ), false );
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
	 * Sync imported Eventyay event details into dashboard JSON data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Dashboard sync result.
	 */
	private function sync_eventyay_event_dashboard_data( $event_id, $event, $settings, $event_slug ) {
		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_dashboard_event',
				esc_html__( 'Could not update dashboard data for an imported Eventyay event without an event ID.', 'wpfaevent' )
			);
		}

		$settings_file      = 'site-settings-' . $event_id . '.json';
		$dashboard_settings = $this->store->read_dashboard_json_file( $settings_file, array() );
		$dashboard_settings = is_array( $dashboard_settings ) ? $dashboard_settings : array();
		$description        = $this->parser->eventyay_event_description( $event );
		$event_url          = $this->parser->eventyay_public_event_url( $event, $settings, $event_slug );
		$about_updated      = 0;

		$default_section_visibility = array(
			'about'      => true,
			'speakers'   => true,
			'schedule'   => true,
			'sponsors'   => true,
			'exhibitors' => true,
		);

		if ( empty( $dashboard_settings['section_visibility'] ) || ! is_array( $dashboard_settings['section_visibility'] ) ) {
			$dashboard_settings['section_visibility'] = $default_section_visibility;
		} else {
			$dashboard_settings['section_visibility'] = wp_parse_args( $dashboard_settings['section_visibility'], $default_section_visibility );
		}

		if ( '' !== trim( $description ) ) {
			$dashboard_settings['about_section_content'] = wp_kses_post( $description );
			$about_updated                               = 1;
		} elseif ( ! isset( $dashboard_settings['about_section_content'] ) ) {
			$dashboard_settings['about_section_content'] = '';
		}

		if ( empty( $dashboard_settings['reg_button_text'] ) ) {
			$dashboard_settings['reg_button_text'] = __( 'Get Tickets', 'wpfaevent' );
		}

		if ( $event_url ) {
			$dashboard_settings['reg_button_link'] = esc_url_raw( $event_url );
		}

		$write_result = $this->store->write_dashboard_json_file( $settings_file, $dashboard_settings );
		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		return array(
			'about_updated' => $about_updated,
		);
	}

	/**
	 * Normalize Eventyay submissions into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $submissions Eventyay submission resources.
	 * @param array  $settings    Import settings.
	 * @param string $event_slug  Eventyay event slug.
	 * @return array
	 */
	private function normalize_eventyay_submissions_payload( $submissions, $settings, $event_slug ) {
		$speakers      = array();
		$sessions      = array();
		$session_count = 0;

		if ( ! is_array( $submissions ) ) {
			return array(
				'speakers'      => array(),
				'sessions'      => array(),
				'session_count' => 0,
			);
		}

		foreach ( $submissions as $submission ) {
			if ( ! is_array( $submission ) ) {
				continue;
			}

			$submission        = $this->normalize_eventyay_api_resource( $submission );
			$speaker_resources = $this->eventyay_list_value( isset( $submission['speakers'] ) ? $submission['speakers'] : array() );
			$session           = $this->normalize_eventyay_submission_session( $submission );
			$speaker_names     = array();

			foreach ( $speaker_resources as $speaker_resource ) {
				if ( ! is_array( $speaker_resource ) ) {
					continue;
				}

				$speaker = $this->normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug );
				if ( empty( $speaker['name'] ) ) {
					continue;
				}

				$speaker_names[] = $speaker['name'];

				if ( empty( $speaker['category'] ) && ! empty( $session['track'] ) ) {
					$speaker['category'] = $session['track'];
				}

				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}

			$session['speakers'] = array_values( array_unique( $speaker_names ) );
			if ( $this->eventyay_session_has_content( $session ) ) {
				$sessions[] = $session;
				++$session_count;
			}
		}

			return array(
				'speakers'      => array_values( $speakers ),
				'sessions'      => $sessions,
				'session_count' => $session_count,
			);
	}

	/**
	 * Normalize Eventyay speaker profile resources into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker_resources Eventyay speaker resources.
	 * @param array  $settings          Import settings.
	 * @param string $event_slug        Eventyay event slug.
	 * @return array
	 */
	private function normalize_eventyay_speakers_payload( $speaker_resources, $settings, $event_slug ) {
		$speakers = array();
		$sessions = array();

		if ( ! is_array( $speaker_resources ) ) {
			return array(
				'speakers'      => array(),
				'sessions'      => array(),
				'session_count' => 0,
			);
		}

		foreach ( $speaker_resources as $speaker_resource ) {
			if ( ! is_array( $speaker_resource ) ) {
				continue;
			}

			$speaker_resource = $this->normalize_eventyay_api_resource( $speaker_resource );
			$speaker          = $this->normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug );
			if ( empty( $speaker['name'] ) ) {
				continue;
			}

			$speaker_sessions = $this->eventyay_list_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'submissions', 'sessions', 'talks' ) ) );
			if ( empty( $speaker_sessions ) ) {
				$this->merge_eventyay_speaker( $speakers, $speaker, array() );
				continue;
			}

			foreach ( $speaker_sessions as $session_resource ) {
				if ( ! is_array( $session_resource ) ) {
					continue;
				}

				$session             = $this->normalize_eventyay_submission_session( $this->normalize_eventyay_api_resource( $session_resource ) );
				$session['speakers'] = array_values( array_unique( array( $speaker['name'] ) ) );
				$speaker['category'] = empty( $speaker['category'] ) && ! empty( $session['track'] ) ? $session['track'] : $speaker['category'];
				$sessions            = $this->merge_eventyay_session_payload( $sessions, $session );
				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}
		}

		return array(
			'speakers'      => array_values( $speakers ),
			'sessions'      => array_values( $sessions ),
			'session_count' => count( $sessions ),
		);
	}

	/**
	 * Normalize Eventyay schedule slot resources into dashboard speaker and session data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $slot_resources Eventyay slot resources.
	 * @param array  $settings       Import settings.
	 * @param string $event_slug     Eventyay event slug.
	 * @return array
	 */
	private function normalize_eventyay_slots_payload( $slot_resources, $settings, $event_slug ) {
		$speakers = array();
		$sessions = array();

		if ( ! is_array( $slot_resources ) ) {
			return array(
				'speakers'      => array(),
				'sessions'      => array(),
				'session_count' => 0,
			);
		}

		foreach ( $slot_resources as $slot_resource ) {
			if ( ! is_array( $slot_resource ) ) {
				continue;
			}

			$slot_resource = $this->normalize_eventyay_api_resource( $slot_resource );
			$submission    = isset( $slot_resource['submission'] ) && is_array( $slot_resource['submission'] )
				? $this->normalize_eventyay_api_resource( $slot_resource['submission'] )
				: array();
			$session       = $this->normalize_eventyay_slot_session( $slot_resource, $submission );
			$speaker_names = array();

			$speaker_resources = $this->eventyay_list_value( isset( $submission['speakers'] ) ? $submission['speakers'] : array() );
			foreach ( $speaker_resources as $speaker_resource ) {
				if ( ! is_array( $speaker_resource ) ) {
					continue;
				}

				$speaker = $this->normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug );
				if ( empty( $speaker['name'] ) ) {
					continue;
				}

				$speaker_names[] = $speaker['name'];

				if ( empty( $speaker['category'] ) && ! empty( $session['track'] ) ) {
					$speaker['category'] = $session['track'];
				}

				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}

			$session['speakers'] = array_values( array_unique( $speaker_names ) );
			$sessions            = $this->merge_eventyay_session_payload( $sessions, $session );
		}

		return array(
			'speakers'      => array_values( $speakers ),
			'sessions'      => array_values( $sessions ),
			'session_count' => count( $sessions ),
		);
	}

	/**
	 * Merge two normalized Eventyay program payloads.
	 *
	 * @since 1.0.0
	 *
	 * @param array $base  Base program payload.
	 * @param array $extra Extra program payload.
	 * @return array
	 */
	private function merge_eventyay_program_payloads( $base, $extra ) {
		$base  = is_array( $base ) ? $base : array();
		$extra = is_array( $extra ) ? $extra : array();

		$merged_speakers = array();
		foreach ( array_merge( isset( $base['speakers'] ) && is_array( $base['speakers'] ) ? $base['speakers'] : array(), isset( $extra['speakers'] ) && is_array( $extra['speakers'] ) ? $extra['speakers'] : array() ) as $speaker ) {
			if ( ! is_array( $speaker ) || empty( $speaker['name'] ) ) {
				continue;
			}

			$this->merge_eventyay_speaker( $merged_speakers, $speaker, array() );
			if ( ! empty( $speaker['sessions'] ) && is_array( $speaker['sessions'] ) ) {
				foreach ( $speaker['sessions'] as $session ) {
					if ( is_array( $session ) ) {
						$this->merge_eventyay_speaker( $merged_speakers, $speaker, $session );
					}
				}
			}
		}

		$merged_sessions = array();
		foreach ( array_merge( isset( $base['sessions'] ) && is_array( $base['sessions'] ) ? $base['sessions'] : array(), isset( $extra['sessions'] ) && is_array( $extra['sessions'] ) ? $extra['sessions'] : array() ) as $session ) {
			if ( is_array( $session ) ) {
				$merged_sessions = $this->merge_eventyay_session_payload( $merged_sessions, $session );
			}
		}

		return array(
			'speakers'      => array_values( $merged_speakers ),
			'sessions'      => array_values( $merged_sessions ),
			'session_count' => count( $merged_sessions ),
		);
	}

	/**
	 * Add a session to a normalized session list if it is not already present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sessions Session list.
	 * @param array $session  Session payload.
	 * @return array
	 */
	private function merge_eventyay_session_payload( $sessions, $session ) {
		if ( ! $this->eventyay_session_has_content( $session ) ) {
			return $sessions;
		}

		$key = ! empty( $session['id'] ) ? 'id:' . sanitize_key( $session['id'] ) : 'title:' . sanitize_title( ( isset( $session['title'] ) ? $session['title'] : '' ) . '-' . ( isset( $session['date'] ) ? $session['date'] : '' ) . '-' . ( isset( $session['time'] ) ? $session['time'] : '' ) );
		if ( empty( $sessions[ $key ] ) ) {
			$sessions[ $key ] = $session;
			return $sessions;
		}

		if ( ! empty( $session['speakers'] ) && is_array( $session['speakers'] ) ) {
			$existing_speakers            = isset( $sessions[ $key ]['speakers'] ) && is_array( $sessions[ $key ]['speakers'] ) ? $sessions[ $key ]['speakers'] : array();
			$sessions[ $key ]['speakers'] = array_values( array_unique( array_merge( $existing_speakers, $session['speakers'] ) ) );
		}

		return $sessions;
	}

	/**
	 * Normalize a newer Eventyay submission as a speaker session.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	private function normalize_eventyay_submission_session( $submission ) {
		$submission = $this->normalize_eventyay_api_resource( $submission );
		$source_id  = $this->eventyay_resource_identifier( $submission );
		$slot       = $this->eventyay_first_slot( $submission );
		$track      = isset( $submission['track'] ) ? $submission['track'] : array();
		$room       = $this->eventyay_slot_room_name( $slot );
		$starts_at  = $this->eventyay_first_present_raw( $slot, array( 'start', 'starts_at', 'starts-at', 'start_time', 'start-time', 'date_from', 'date-from' ) );
		$ends_at    = $this->eventyay_first_present_raw( $slot, array( 'end', 'ends_at', 'ends-at', 'end_time', 'end-time', 'date_to', 'date-to' ) );

		if ( empty( $starts_at ) ) {
			$starts_at = $this->eventyay_first_present_raw( $submission, array( 'starts_at', 'starts-at', 'start_time', 'start-time', 'date_from', 'date-from' ) );
		}

		if ( empty( $ends_at ) ) {
			$ends_at = $this->eventyay_first_present_raw( $submission, array( 'ends_at', 'ends-at', 'end_time', 'end-time', 'date_to', 'date-to' ) );
		}

		if ( empty( $room ) ) {
			$room = $this->eventyay_first_present_text( $submission, array( 'room', 'room_name', 'room-name', 'venue' ) );
		}

		return array(
			'id'        => $source_id ? 'eventyay-submission-' . sanitize_key( $source_id ) : 'eventyay-submission-' . sanitize_title( $this->eventyay_text_value( isset( $submission['title'] ) ? $submission['title'] : '' ) ),
			'title'     => $this->eventyay_text_value( isset( $submission['title'] ) ? $submission['title'] : '' ),
			'date'      => $this->format_eventyay_date( $starts_at ),
			'time'      => $this->format_eventyay_time( $starts_at ),
			'end_time'  => $this->format_eventyay_time( $ends_at ),
			'starts_at' => $this->normalize_eventyay_datetime( $starts_at ),
			'ends_at'   => $this->normalize_eventyay_datetime( $ends_at ),
			'abstract'  => $this->eventyay_submission_abstract( $submission ),
			'track'     => is_array( $track ) ? $this->eventyay_text_value( isset( $track['name'] ) ? $track['name'] : '' ) : $this->eventyay_text_value( $track ),
			'room'      => $room,
			'source_id' => sanitize_text_field( $source_id ),
		);
	}

	/**
	 * Normalize a newer Eventyay schedule slot as a speaker session.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slot       Eventyay slot resource.
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	private function normalize_eventyay_slot_session( $slot, $submission ) {
		$slot       = $this->normalize_eventyay_api_resource( $slot );
		$submission = $this->normalize_eventyay_api_resource( $submission );
		$source_id  = $this->eventyay_resource_identifier( $submission );
		$slot_id    = $this->eventyay_resource_identifier( $slot );
		$track      = isset( $submission['track'] ) ? $submission['track'] : array();
		$room       = $this->eventyay_slot_room_name( $slot );
		$starts_at  = $this->eventyay_first_present_raw( $slot, array( 'start', 'starts_at', 'starts-at', 'start_time', 'start-time' ) );
		$ends_at    = $this->eventyay_first_present_raw( $slot, array( 'end', 'ends_at', 'ends-at', 'end_time', 'end-time' ) );
		$title      = $this->eventyay_first_present_text( $submission, array( 'title', 'name' ) );
		$abstract   = $this->eventyay_submission_abstract( $submission );

		if ( empty( $title ) ) {
			$title = $this->eventyay_first_present_text( $slot, array( 'title', 'name', 'description' ) );
		}

		if ( empty( $abstract ) ) {
			$abstract = $this->eventyay_first_present_rich_text( $slot, array( 'description', 'abstract' ) );
		}

		return array(
			'id'        => $source_id ? 'eventyay-submission-' . sanitize_key( $source_id ) : 'eventyay-slot-' . sanitize_key( $slot_id ),
			'title'     => $title,
			'date'      => $this->format_eventyay_date( $starts_at ),
			'time'      => $this->format_eventyay_time( $starts_at ),
			'end_time'  => $this->format_eventyay_time( $ends_at ),
			'starts_at' => $this->normalize_eventyay_datetime( $starts_at ),
			'ends_at'   => $this->normalize_eventyay_datetime( $ends_at ),
			'abstract'  => $abstract,
			'track'     => is_array( $track ) ? $this->eventyay_text_value( isset( $track['name'] ) ? $track['name'] : '' ) : $this->eventyay_text_value( $track ),
			'room'      => $room,
			'source_id' => sanitize_text_field( $source_id ? $source_id : $slot_id ),
		);
	}

	/**
	 * Determine whether a normalized Eventyay session has useful content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $session Normalized session.
	 * @return bool
	 */
	private function eventyay_session_has_content( $session ) {
		foreach ( array( 'title', 'date', 'time', 'end_time', 'abstract', 'track', 'room' ) as $key ) {
			if ( ! empty( $session[ $key ] ) ) {
				return true;
			}
		}

		return ! empty( $session['speakers'] );
	}

	/**
	 * Extract a display room name from an expanded Eventyay slot.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slot Eventyay slot resource.
	 * @return string
	 */
	private function eventyay_slot_room_name( $slot ) {
		$slot = $this->normalize_eventyay_api_resource( $slot );

		$room_name = $this->eventyay_first_present_text( $slot, array( 'room_name', 'room-name', 'venue' ) );
		if ( $room_name ) {
			return $room_name;
		}

		if ( empty( $slot['room'] ) ) {
			return '';
		}

		if ( is_array( $slot['room'] ) ) {
			return $this->eventyay_first_present_text( $this->normalize_eventyay_api_resource( $slot['room'] ), array( 'name', 'title', 'slug' ) );
		}

		return $this->eventyay_text_value( $slot['room'] );
	}

	/**
	 * Normalize a newer Eventyay speaker resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker_resource Eventyay speaker resource.
	 * @param array  $settings         Import settings.
	 * @param string $event_slug       Eventyay event slug.
	 * @return array
	 */
	private function normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug ) {
		$speaker_resource = $this->normalize_eventyay_api_resource( $speaker_resource );
		$source_id        = $this->eventyay_resource_identifier( $speaker_resource );
		$name             = $this->eventyay_first_present_text( $speaker_resource, array( 'name', 'fullname', 'full_name', 'full-name', 'public_name', 'public-name', 'display_name', 'display-name' ) );

		if ( empty( $name ) ) {
			$name = trim(
				$this->eventyay_text_value( isset( $speaker_resource['first_name'] ) ? $speaker_resource['first_name'] : '' ) . ' ' .
				$this->eventyay_text_value( isset( $speaker_resource['last_name'] ) ? $speaker_resource['last_name'] : '' )
			);
		}

		$eventyay_speaker_id = implode(
			':',
			array_filter(
				array(
					$settings['organizer_slug'],
					$event_slug,
					$source_id ? $source_id : sanitize_title( $name ),
				)
			)
		);

		$position     = $this->eventyay_first_present_text( $speaker_resource, array( 'position', 'job_title', 'job-title', 'title', 'role', 'speaking_experience', 'speaking-experience' ) );
		$organization = $this->eventyay_first_present_text( $speaker_resource, array( 'organization', 'organisation', 'company', 'affiliation' ) );
		$category     = $this->eventyay_first_present_text( $speaker_resource, array( 'category', 'track' ) );

		return array(
			'id'                  => 'eventyay-' . sanitize_key( $eventyay_speaker_id ),
			'eventyay_speaker_id' => sanitize_text_field( $eventyay_speaker_id ),
			'name'                => sanitize_text_field( $name ),
			'title'               => sanitize_text_field( $position ? $position : $organization ),
			'position'            => sanitize_text_field( $position ),
			'organization'        => sanitize_text_field( $organization ),
			'category'            => sanitize_text_field( $category ),
			'image'               => $this->eventyay_url_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'avatar', 'avatar_url', 'avatar-url', 'avatar_url_original', 'avatar-url-original', 'image', 'image_url', 'image-url', 'photo', 'photo_url', 'photo-url' ) ), $settings['base_url'] ),
			'bio'                 => $this->eventyay_first_present_rich_text( $speaker_resource, array( 'biography', 'bio', 'description', 'abstract', 'short_biography', 'short-biography', 'long_biography', 'long-biography' ) ),
			'social'              => array(
				'linkedin' => $this->eventyay_url_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'linkedin', 'linkedin_url', 'linkedin-url' ) ), $settings['base_url'] ),
				'twitter'  => $this->eventyay_url_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'twitter', 'twitter_url', 'twitter-url', 'x_url' ) ), $settings['base_url'] ),
				'github'   => $this->eventyay_url_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'github', 'github_url', 'github-url' ) ), $settings['base_url'] ),
				'website'  => $this->eventyay_url_value( $this->eventyay_first_present_raw( $speaker_resource, array( 'website', 'website_url', 'website-url', 'homepage', 'homepage_url', 'homepage-url', 'url' ) ), $settings['base_url'] ),
			),
			'featured'            => $this->eventyay_speaker_is_featured( $speaker_resource, $category ),
			'featured_order'      => $this->eventyay_speaker_featured_order( $speaker_resource ),
			'sessions'            => array(),
			'source'              => 'eventyay',
		);
	}

	/**
	 * Detect whether an Eventyay speaker is marked for featured display.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker_resource Normalized Eventyay speaker resource.
	 * @param string $category         Speaker category or track label.
	 * @return bool
	 */
	private function eventyay_speaker_is_featured( $speaker_resource, $category = '' ) {
		$featured = $this->eventyay_first_present_raw(
			$speaker_resource,
			array(
				'featured',
				'is_featured',
				'is-featured',
				'featured_speaker',
				'featured-speaker',
				'highlighted',
				'is_highlighted',
				'is-highlighted',
				'keynote',
				'is_keynote',
				'is-keynote',
				'show_on_frontpage',
				'show-on-frontpage',
			)
		);

		if ( $this->eventyay_truthy_value( $featured ) ) {
			return true;
		}

		return is_string( $category ) && (bool) preg_match( '/\b(featured|keynote|plenary|highlight)\b/i', $category );
	}

	/**
	 * Get an optional featured speaker order from Eventyay data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker_resource Normalized Eventyay speaker resource.
	 * @return int
	 */
	private function eventyay_speaker_featured_order( $speaker_resource ) {
		$order = $this->eventyay_first_present_raw(
			$speaker_resource,
			array(
				'featured_order',
				'featured-order',
				'featured_position',
				'featured-position',
				'featuredPosition',
				'speaker_order',
				'speaker-order',
				'sort_order',
				'sort-order',
				'order',
			)
		);

		return is_numeric( $order ) ? absint( $order ) : 0;
	}

	/**
	 * Convert Eventyay truth-ish values into a boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function eventyay_truthy_value( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return 0 !== (int) $value;
		}

		if ( is_array( $value ) ) {
			foreach ( array( 'value', 'featured', 'is_featured', 'enabled', 'selected' ) as $key ) {
				if ( array_key_exists( $key, $value ) ) {
					return $this->eventyay_truthy_value( $value[ $key ] );
				}
			}

			return ! empty( $value );
		}

		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'yes', 'y', 'on', 'featured', 'keynote', 'highlighted' ), true );
	}

	/**
	 * Extract a submission abstract from likely Eventyay fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return string
	 */
	private function eventyay_submission_abstract( $submission ) {
		return $this->eventyay_first_present_rich_text(
			$submission,
			array(
				'abstract',
				'description',
				'content',
				'notes',
			)
		);
	}

	/**
	 * Extract the first scheduled slot from a submission.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	private function eventyay_first_slot( $submission ) {
		if ( ! empty( $submission['slot'] ) && is_array( $submission['slot'] ) ) {
			return $this->normalize_eventyay_api_resource( $submission['slot'] );
		}

		if ( ! empty( $submission['slots'] ) && is_array( $submission['slots'] ) ) {
			foreach ( $this->eventyay_list_value( $submission['slots'] ) as $slot ) {
				if ( is_array( $slot ) ) {
					return $this->normalize_eventyay_api_resource( $slot );
				}
			}
		}

		return array();
	}

	/**
	 * Normalize an Eventyay resource into a flat field map.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @return array
	 */
	private function normalize_eventyay_api_resource( $eventyay_resource ) {
		if ( ! is_array( $eventyay_resource ) ) {
			return array();
		}

		if ( isset( $eventyay_resource['data'] ) && is_array( $eventyay_resource['data'] ) && $this->is_jsonapi_resource( $eventyay_resource['data'] ) ) {
			$eventyay_resource = $eventyay_resource['data'];
		}

		$normalized = array();

		if ( isset( $eventyay_resource['attributes'] ) && is_array( $eventyay_resource['attributes'] ) ) {
			$normalized = $eventyay_resource['attributes'];
		}

		foreach ( $eventyay_resource as $key => $value ) {
			if ( in_array( $key, array( 'attributes', 'relationships' ), true ) ) {
				continue;
			}

			$normalized[ $key ] = $value;
		}

		if ( isset( $eventyay_resource['relationships'] ) && is_array( $eventyay_resource['relationships'] ) ) {
			foreach ( $eventyay_resource['relationships'] as $relationship_key => $relationship ) {
				if ( ! is_array( $relationship ) || ! array_key_exists( 'data', $relationship ) ) {
					continue;
				}

				$normalized[ $relationship_key ] = $this->normalize_eventyay_relationship_data( $relationship['data'] );
			}
		}

		return $normalized;
	}

	/**
	 * Normalize JSON:API relationship data when included directly in Eventyay payloads.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $relationship_data Relationship data.
	 * @return mixed
	 */
	private function normalize_eventyay_relationship_data( $relationship_data ) {
		if ( ! is_array( $relationship_data ) ) {
			return $relationship_data;
		}

		if ( $this->is_jsonapi_resource( $relationship_data ) ) {
			return $this->normalize_eventyay_api_resource( $relationship_data );
		}

		$normalized = array();
		foreach ( $relationship_data as $item ) {
			$normalized[] = is_array( $item ) ? $this->normalize_eventyay_api_resource( $item ) : $item;
		}

		return $normalized;
	}

	/**
	 * Get a list value from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	private function eventyay_list_value( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		if ( isset( $value['results'] ) && is_array( $value['results'] ) ) {
			return $value['results'];
		}

		if ( array_key_exists( 'data', $value ) && is_array( $value['data'] ) ) {
			if ( $this->is_jsonapi_resource( $value['data'] ) ) {
				return array( $value['data'] );
			}

			return $value['data'];
		}

		if (
			! array_key_exists( 0, $value )
			&& (
				$this->is_jsonapi_resource( $value )
				|| isset( $value['id'] )
				|| isset( $value['name'] )
				|| isset( $value['title'] )
				|| isset( $value['attributes'] )
			)
		) {
			return array( $value );
		}

		return $value;
	}

	/**
	 * Extract a stable identifier from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @return string
	 */
	private function eventyay_resource_identifier( $eventyay_resource ) {
		foreach ( array( '_eventyay_source_id', 'code', 'id', 'slug' ) as $key ) {
			if ( isset( $eventyay_resource[ $key ] ) && is_scalar( $eventyay_resource[ $key ] ) && '' !== trim( (string) $eventyay_resource[ $key ] ) ) {
				return sanitize_text_field( (string) $eventyay_resource[ $key ] );
			}
		}

		return '';
	}

	/**
	 * Return the first non-empty plain text field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return string
	 */
	private function eventyay_first_present_text( $eventyay_resource, $keys ) {
		$value = $this->eventyay_first_present_raw( $eventyay_resource, $keys );

		return $this->eventyay_text_value( $value );
	}

	/**
	 * Return the first non-empty rich text field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return string
	 */
	private function eventyay_first_present_rich_text( $eventyay_resource, $keys ) {
		$value = $this->eventyay_first_present_raw( $eventyay_resource, $keys );

		return $this->eventyay_rich_text_value( $value );
	}

	/**
	 * Return the first non-empty raw field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return mixed
	 */
	private function eventyay_first_present_raw( $eventyay_resource, $keys ) {
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $eventyay_resource ) ) {
				continue;
			}

			if ( is_scalar( $eventyay_resource[ $key ] ) && '' !== trim( (string) $eventyay_resource[ $key ] ) ) {
				return $eventyay_resource[ $key ];
			}

			if ( is_array( $eventyay_resource[ $key ] ) && ! empty( $eventyay_resource[ $key ] ) ) {
				return $eventyay_resource[ $key ];
			}
		}

		return '';
	}

	/**
	 * Convert an Eventyay URL-ish value into an absolute URL.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $base_url Eventyay base URL.
	 * @return string
	 */
	private function eventyay_url_value( $value, $base_url ) {
		if ( is_array( $value ) ) {
			foreach ( array( 'url', 'href', 'download', 'thumbnail', 'image', 'en', 'default' ) as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					return $this->eventyay_url_value( $value[ $key ], $base_url );
				}
			}

			return '';
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( wp_http_validate_url( $value ) ) {
			return esc_url_raw( $value );
		}

		$base_url = untrailingslashit( esc_url_raw( $base_url ) );
		if ( ! empty( $base_url ) && wp_http_validate_url( $base_url ) && 0 === strpos( $value, '/' ) ) {
			return esc_url_raw( $base_url . $value );
		}

		return '';
	}

		/**
		 * Convert an Eventyay location-ish value into display text.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $value Raw location value.
		 * @return string
		 */
	private function eventyay_location_text_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $this->eventyay_text_value( $value );
		}

		foreach ( array( 'name', 'title', 'label', 'location', 'address', 'full_address', 'full-address', 'formatted_address', 'formatted-address' ) as $key ) {
			if ( ! empty( $value[ $key ] ) ) {
				$text = $this->eventyay_text_value( $value[ $key ] );

				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		$parts = array();
		foreach ( array( 'street', 'street_address', 'street-address', 'address_line_1', 'address-line-1', 'line1', 'postal_code', 'postal-code', 'postcode', 'zip', 'city', 'region', 'state', 'country' ) as $key ) {
			if ( empty( $value[ $key ] ) ) {
				continue;
			}

			$part = $this->eventyay_text_value( $value[ $key ] );
			if ( '' !== $part ) {
				$parts[] = $part;
			}
		}

		if ( ! empty( $parts ) ) {
			return implode( ', ', array_values( array_unique( $parts ) ) );
		}

		return $this->eventyay_text_value( $value );
	}

	/**
	 * Determine whether an Eventyay event has a fetched settings payload.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return bool
	 */
	private function eventyay_event_has_settings_payload( $event ) {
		foreach ( array( '_eventyay_settings', 'settings' ) as $settings_key ) {
			if ( ! empty( $event[ $settings_key ] ) && is_array( $event[ $settings_key ] ) ) {
				return true;
			}
		}

		return false;
	}

			/**
			 * Return the first non-empty raw event field from top-level data, metadata, or settings.
			 *
			 * @since 1.0.0
			 *
			 * @param array $event            Eventyay event resource.
			 * @param array $keys             Candidate keys.
			 * @param bool  $include_settings Whether to check Eventyay settings payloads.
			 * @return mixed
			 */
	private function eventyay_event_first_present_raw( $event, $keys, $include_settings = false ) {
		$value = $this->eventyay_first_present_raw( $event, $keys );
		if ( $this->eventyay_value_is_non_empty( $value ) ) {
			return $value;
		}

		foreach ( array( 'meta_data', 'metadata', 'meta' ) as $meta_key ) {
			if ( ! empty( $event[ $meta_key ] ) && is_array( $event[ $meta_key ] ) ) {
				$value = $this->eventyay_first_present_raw( $event[ $meta_key ], $keys );

				if ( $this->eventyay_value_is_non_empty( $value ) ) {
					return $value;
				}
			}
		}

		if ( $include_settings ) {
			foreach ( array( '_eventyay_settings', 'settings' ) as $settings_key ) {
				if ( ! empty( $event[ $settings_key ] ) && is_array( $event[ $settings_key ] ) ) {
					$settings_resource = $this->normalize_eventyay_api_resource( $event[ $settings_key ] );
					$value             = $this->eventyay_first_present_raw( $settings_resource, $keys );

					if ( $this->eventyay_value_is_non_empty( $value ) ) {
						return $value;
					}
				}
			}
		}

		return '';
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
	private function find_eventyay_event_post( $organizer_slug, $event_slug ) {
		$event_ids = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Imported Eventyay event identity is stored in post meta for idempotency.
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_wpfa_eventyay_organizer_slug',
						'value'   => sanitize_text_field( $organizer_slug ),
						'compare' => '=',
					),
					array(
						'key'     => '_wpfa_eventyay_event_slug',
						'value'   => sanitize_text_field( $event_slug ),
						'compare' => '=',
					),
				),
			)
		);

		return ! empty( $event_ids[0] ) ? absint( $event_ids[0] ) : 0;
	}

	/**
	 * Get a useful string from an Eventyay scalar or multi-language value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_text_value( $value ) {
		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'en', 'default', 'name', 'title' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) && is_scalar( $value[ $preferred_key ] ) && '' !== trim( (string) $value[ $preferred_key ] ) ) {
				return sanitize_text_field( (string) $value[ $preferred_key ] );
			}
		}

		foreach ( $value as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return sanitize_text_field( (string) $candidate );
			}
		}

		return '';
	}

	/**
	 * Get rich text from an Eventyay scalar or multi-language value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_rich_text_value( $value ) {
		if ( is_scalar( $value ) ) {
			return wp_kses_post( (string) $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'en', 'default', 'description', 'text' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) && is_scalar( $value[ $preferred_key ] ) && '' !== trim( (string) $value[ $preferred_key ] ) ) {
				return wp_kses_post( (string) $value[ $preferred_key ] );
			}
		}

		foreach ( $value as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return wp_kses_post( (string) $candidate );
			}
		}

		return '';
	}

	/**
	 * Get a scalar string from an Eventyay value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_scalar_value( $value ) {
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'value', 'date', 'datetime', 'start', 'end', 'en', 'default' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) ) {
				$resolved = $this->eventyay_scalar_value( $value[ $preferred_key ] );

				if ( '' !== trim( $resolved ) ) {
					return $resolved;
				}
			}
		}

		foreach ( $value as $candidate ) {
			$resolved = $this->eventyay_scalar_value( $candidate );

			if ( '' !== trim( $resolved ) ) {
				return $resolved;
			}
		}

		return '';
	}

	/**
	 * Extract event description content from an Eventyay event resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_description( $event ) {
		$value = $this->eventyay_event_first_present_raw(
			$event,
			array(
				'description',
				'frontpage_text',
				'frontpage-text',
				'event_info_text',
				'event-info-text',
				'about',
				'about_text',
				'about-text',
				'text',
				'intro',
				'short_description',
				'short-description',
				'subtitle',
				'summary',
			),
			true
		);

		return $this->eventyay_rich_text_value( $value );
	}

	/**
	 * Import Eventyay sponsors and exhibitors into event-specific dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_event_partner_data( $event_id, $event, $settings, $event_slug ) {
		$result = array(
			'sponsor_count'   => 0,
			'exhibitor_count' => 0,
			'skipped'         => 0,
		);

		$sponsors = $this->client->fetch_eventyay_partner_collection( $settings, $event, $event_slug, 'sponsors' );
		if ( is_wp_error( $sponsors ) ) {
			++$result['skipped'];
		} else {
			$normalized_sponsors = $this->parser->normalize_eventyay_sponsor_resources( $sponsors['resources'], $settings );
			$existing_sponsors   = $this->store->read_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', array() );
			$sponsor_groups      = $this->parser->merge_eventyay_sponsor_groups( $normalized_sponsors, $existing_sponsors );
			$write_result        = $this->store->write_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', $sponsor_groups );

			if ( is_wp_error( $write_result ) ) {
				return $write_result;
			}

			$result['sponsor_count'] = count( $normalized_sponsors );
		}

		$exhibitors = $this->client->fetch_eventyay_partner_collection( $settings, $event, $event_slug, 'exhibitors' );
		if ( is_wp_error( $exhibitors ) ) {
			++$result['skipped'];
		} else {
			$normalized_exhibitors = $this->parser->normalize_eventyay_exhibitor_resources( $exhibitors['resources'], $settings );
			$existing_exhibitors   = $this->store->read_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', array() );
			$merged_exhibitors     = $this->parser->merge_eventyay_flat_records( $normalized_exhibitors, $existing_exhibitors );
			$write_result          = $this->store->write_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', $merged_exhibitors );

			if ( is_wp_error( $write_result ) ) {
				return $write_result;
			}

			$result['exhibitor_count'] = count( $normalized_exhibitors );
		}

		return $result;
	}

	/**
	 * Import speakers and session data for an Eventyay event.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_event_program( $event_id, $settings, $event_slug ) {
		$endpoint = $this->client->build_eventyay_submissions_endpoint( $settings, $event_slug );
		$program  = array(
			'speakers'      => array(),
			'sessions'      => array(),
			'session_count' => 0,
		);
		$error    = null;

		if ( is_wp_error( $endpoint ) ) {
			$error = $endpoint;
		} else {
			$fetched = $this->client->fetch_eventyay_program_resources( $endpoint, $settings );
			if ( is_wp_error( $fetched ) ) {
				$error = $fetched;
			} else {
				$program = $this->parser->normalize_eventyay_submissions_payload( $fetched['submissions'], $settings, $event_slug );
			}
		}

		$speakers = $this->client->fetch_eventyay_event_speaker_program( $settings, $event_slug );
		if ( is_wp_error( $speakers ) ) {
			if ( null === $error ) {
				$error = $speakers;
			}
		} else {
			$program = $this->parser->merge_eventyay_program_payloads( $program, $speakers );
		}

		$slots = $this->client->fetch_eventyay_event_slot_program( $settings, $event_slug );
		if ( is_wp_error( $slots ) ) {
			if ( null === $error ) {
				$error = $slots;
			}
		} else {
			$program = $this->parser->merge_eventyay_program_payloads( $program, $slots );
		}

		if ( $error && empty( $program['speakers'] ) && empty( $program['sessions'] ) ) {
			return $error;
		}

		$cpt_result = array(
			'created' => 0,
			'updated' => 0,
		);

		$existing_speakers  = $this->store->read_dashboard_json_file( 'speakers-' . absint( $event_id ) . '.json', array() );
		$dashboard_speakers = $this->parser->merge_dashboard_speaker_state( $program['speakers'], $existing_speakers );
		$write_result       = $this->store->write_dashboard_json_file( 'speakers-' . absint( $event_id ) . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		$cpt_result = $this->speaker_repo->sync_eventyay_speaker_posts( $dashboard_speakers, $event_id );

		$schedule_rows = $this->event_repo->write_eventyay_schedule_table( $event_id, $program['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			return $schedule_rows;
		}

		return array(
			'speaker_count'    => count( $program['speakers'] ),
			'session_count'    => $program['session_count'],
			'created_speakers' => $cpt_result['created'],
			'updated_speakers' => $cpt_result['updated'],
			'schedule_rows'    => $schedule_rows,
		);
	}
}
