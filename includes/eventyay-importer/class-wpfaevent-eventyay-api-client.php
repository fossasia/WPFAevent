<?php
/**
 * Eventyay API Client.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles REST/JSON and JSON:API remote resource retrieval.
 */
class Wpfaevent_Eventyay_API_Client {

	/**
	 * Parser instance.
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
	 * Fetch Eventyay event resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array|WP_Error Event resources and metadata.
	 */
	public function fetch_eventyay_event_resources( $settings ) {
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
	public function fetch_eventyay_event_resources_from_endpoint( $endpoint, $settings ) {
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

			foreach ( $this->parser->extract_eventyay_event_resources( $payload ) as $event ) {
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
	public function build_eventyay_event_endpoint_candidates( $settings ) {
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
	public function build_eventyay_legacy_event_endpoint_candidates( $settings ) {
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
	public function trim_eventyay_legacy_api_version_path( $api_base ) {
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
	public function eventyay_error_has_http_status( $error, $http_status ) {
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
	public function eventyay_event_not_found_error( $endpoints, $not_found_errors ) {
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
	public function build_eventyay_events_endpoint( $settings ) {
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
	public function build_eventyay_event_endpoint( $settings, $event_slug ) {
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
	public function build_eventyay_event_settings_endpoint( $settings, $event_slug ) {
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
	 * Fetch event-level settings such as frontpage/about text when available.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error
	 */
	public function fetch_eventyay_event_settings_resource( $settings, $event_slug ) {
		$settings_endpoint = $this->build_eventyay_event_settings_endpoint( $settings, $event_slug );
		if ( is_wp_error( $settings_endpoint ) ) {
			return $settings_endpoint;
		}

		return $this->fetch_eventyay_rest_json( $settings_endpoint, $settings['api_token'] );
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
	public function build_eventyay_submissions_endpoint( $settings, $event_slug ) {
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
	public function build_eventyay_speakers_endpoint( $settings, $event_slug ) {
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
	public function build_eventyay_slots_endpoint( $settings, $event_slug ) {
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
	public function build_eventyay_partner_endpoint_candidates( $settings, $event, $event_slug, $resource_type ) {
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
	public function build_eventyay_modern_partner_endpoint( $settings, $event_slug, $resource_type ) {
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
	public function build_eventyay_legacy_partner_endpoints( $settings, $event, $event_slug, $resource_type ) {
		$settings      = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url      = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug    = $this->sanitize_eventyay_path_segment( $event_slug );
		$resource_type = sanitize_key( $resource_type );
		$source_id     = $this->parser->eventyay_event_first_present_raw( $event, array( '_eventyay_source_id', 'id', 'code', 'identifier' ), false );
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
	 * Fetch Eventyay partner resources (sponsors or exhibitors) for an event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings      Import settings.
	 * @param array  $event         Eventyay event resource.
	 * @param string $event_slug    Eventyay event slug.
	 * @param string $resource_type Partner resource type.
	 * @return array|WP_Error Partner resources.
	 */
	public function fetch_eventyay_partner_collection( $settings, $event, $event_slug, $resource_type ) {
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
	public function fetch_eventyay_partner_resources( $endpoint, $settings, $resource_type ) {
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
				foreach ( $this->parser->eventyay_list_value( $payload['data'] ) as $resource ) {
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
	 * Fetch Eventyay submission resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Submission endpoint.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Submission resources and metadata.
	 */
	public function fetch_eventyay_program_resources( $endpoint, $settings ) {
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
	public function fetch_eventyay_event_speaker_program( $settings, $event_slug ) {
		$endpoint = $this->build_eventyay_speakers_endpoint( $settings, $event_slug );
		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$fetched = $this->fetch_eventyay_speaker_resources( $endpoint, $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		return $this->parser->normalize_eventyay_speakers_payload( $fetched['speakers'], $settings, $event_slug );
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
	public function fetch_eventyay_speaker_resources( $endpoint, $settings ) {
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
				foreach ( $this->parser->eventyay_list_value( $payload['data'] ) as $speaker ) {
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
	public function fetch_eventyay_event_slot_program( $settings, $event_slug ) {
		$endpoint = $this->build_eventyay_slots_endpoint( $settings, $event_slug );
		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$fetched = $this->fetch_eventyay_slot_resources( $endpoint, $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		return $this->parser->normalize_eventyay_slots_payload( $fetched['slots'], $settings, $event_slug );
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
	public function fetch_eventyay_slot_resources( $endpoint, $settings ) {
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
				foreach ( $this->parser->eventyay_list_value( $payload['data'] ) as $slot ) {
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
	public function normalize_eventyay_next_url( $next_url, $reference_url ) {
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
	public function eventyay_urls_share_origin( $url, $base_url ) {
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
	public function eventyay_url_origin( $url ) {
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
	public function default_port_for_scheme( $scheme ) {
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
	public function fetch_eventyay_rest_json( $api_url, $api_token = '' ) {
		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$auth_schemes = $this->get_eventyay_authorization_schemes( $api_token );
		$last_error   = null;

		foreach ( $auth_schemes as $auth_scheme ) {
			$headers = $this->build_eventyay_rest_headers( $api_token, $auth_scheme );
			$this->safe_debug_log(
				'Sending API Request (REST)',
				array(
					'url'     => $api_url,
					'headers' => $headers,
				)
			);

			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
					'headers'     => $headers,
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->safe_debug_log(
					'API Request Failed (WP_Error, REST)',
					array(
						'url'           => $api_url,
						'error_message' => $response->get_error_message(),
						'error_code'    => $response->get_error_code(),
					)
				);
				return new WP_Error(
					'wpfaevent_eventyay_request_failed',
					esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
					array( 'details' => $response->get_error_message() )
				);
			}

			$status       = absint( wp_remote_retrieve_response_code( $response ) );
			$resp_headers = wp_remote_retrieve_headers( $response );
			$headers_arr  = is_array( $resp_headers ) ? $resp_headers : ( method_exists( $resp_headers, 'getAll' ) ? $resp_headers->getAll() : (array) $resp_headers );
			$body         = wp_remote_retrieve_body( $response );

			$this->safe_debug_log(
				'Received API Response (REST)',
				array(
					'url'              => $api_url,
					'response_code'    => $status,
					'response_headers' => $headers_arr,
					'response_body'    => $body,
				)
			);

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
	public function decode_eventyay_rest_response( $response, $api_url ) {
		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			$error_message = '';
			$decoded_body  = $this->decode_eventyay_error_body( $body );
			if ( is_array( $decoded_body ) ) {
				if ( ! empty( $decoded_body['detail'] ) ) {
					$error_message = $decoded_body['detail'];
				} elseif ( ! empty( $decoded_body['error'] ) ) {
					$error_message = $decoded_body['error'];
				} elseif ( ! empty( $decoded_body['errors'] ) && is_array( $decoded_body['errors'] ) ) {
					$details = array();
					foreach ( $decoded_body['errors'] as $err ) {
						if ( is_array( $err ) && ! empty( $err['detail'] ) ) {
							$details[] = $err['detail'];
						}
					}
					if ( ! empty( $details ) ) {
						$error_message = implode( '; ', $details );
					}
				}
			}

			$display_body = $body;
			if ( is_array( $decoded_body ) ) {
				$display_body = wp_json_encode( $decoded_body, JSON_PRETTY_PRINT );
			} else {
				$display_body = wp_strip_all_tags( (string) $body );
			}

			$full_msg = sprintf(
				/* translators: 1: HTTP status code, 2: Eventyay API URL. */
				esc_html__( 'Eventyay API returned HTTP %1$d for %2$s.', 'wpfaevent' ),
				$status,
				esc_url_raw( $api_url )
			);

			if ( ! empty( $error_message ) ) {
				$full_msg .= "\n" . sprintf(
					/* translators: %s: API error message detail. */
					esc_html__( 'API Error: %s', 'wpfaevent' ),
					$error_message
				);
			}

			$full_msg .= "\n\n" . sprintf( "HTTP Status: %d\nURL: %s\nResponse:\n%s", $status, esc_url_raw( $api_url ), $display_body );

			return new WP_Error(
				'wpfaevent_eventyay_http_error',
				$full_msg,
				array(
					'http_status' => $status,
					'url'         => esc_url_raw( $api_url ),
					'body'        => $decoded_body,
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
					'response_sample' => $this->parser->truncate_string( $body ),
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
	public function build_eventyay_rest_headers( $api_token, $auth_scheme ) {
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
	public function get_eventyay_authorization_schemes( $api_token ) {
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
	public function format_eventyay_authorization_header( $api_token, $auth_scheme ) {
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
	 * Decode an Eventyay error body if possible.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body Response body.
	 * @return array|string
	 */
	public function decode_eventyay_error_body( $body ) {
		$decoded = json_decode( $body, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return $decoded;
		}

		return $this->parser->truncate_string( $body );
	}

	/**
	 * Fetch and decode an Eventyay JSON:API document.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Eventyay API URL.
	 * @return array|WP_Error
	 */
	public function fetch_eventyay_json( $api_url ) {
		$headers = array(
			'Accept' => 'application/vnd.api+json, application/json',
		);
		$this->safe_debug_log(
			'Sending API Request (JSON:API)',
			array(
				'url'     => $api_url,
				'headers' => $headers,
			)
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'headers'     => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->safe_debug_log(
				'API Request Failed (WP_Error, JSON:API)',
				array(
					'url'           => $api_url,
					'error_message' => $response->get_error_message(),
					'error_code'    => $response->get_error_code(),
				)
			);
			return new WP_Error(
				'eventyay_request_failed',
				esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
				array(
					'status'  => 502,
					'details' => $response->get_error_message(),
				)
			);
		}

		$status       = absint( wp_remote_retrieve_response_code( $response ) );
		$resp_headers = wp_remote_retrieve_headers( $response );
		$headers_arr  = is_array( $resp_headers ) ? $resp_headers : ( method_exists( $resp_headers, 'getAll' ) ? $resp_headers->getAll() : (array) $resp_headers );
		$body         = wp_remote_retrieve_body( $response );

		$this->safe_debug_log(
			'Received API Response (JSON:API)',
			array(
				'url'              => $api_url,
				'response_code'    => $status,
				'response_headers' => $headers_arr,
				'response_body'    => $body,
			)
		);

		if ( $status < 200 || $status >= 300 ) {
			$error_message = '';
			$decoded_body  = $this->decode_eventyay_error_body( $body );
			if ( is_array( $decoded_body ) ) {
				if ( ! empty( $decoded_body['detail'] ) ) {
					$error_message = $decoded_body['detail'];
				} elseif ( ! empty( $decoded_body['error'] ) ) {
					$error_message = $decoded_body['error'];
				} elseif ( ! empty( $decoded_body['errors'] ) && is_array( $decoded_body['errors'] ) ) {
					$details = array();
					foreach ( $decoded_body['errors'] as $err ) {
						if ( is_array( $err ) && ! empty( $err['detail'] ) ) {
							$details[] = $err['detail'];
						}
					}
					if ( ! empty( $details ) ) {
						$error_message = implode( '; ', $details );
					}
				}
			}

			$display_body = $body;
			if ( is_array( $decoded_body ) ) {
				$display_body = wp_json_encode( $decoded_body, JSON_PRETTY_PRINT );
			} else {
				$display_body = wp_strip_all_tags( (string) $body );
			}

			$full_msg = sprintf(
				/* translators: 1: HTTP status code, 2: Eventyay API URL. */
				esc_html__( 'Eventyay API returned HTTP %1$d for %2$s.', 'wpfaevent' ),
				$status,
				esc_url_raw( $api_url )
			);

			if ( ! empty( $error_message ) ) {
				$full_msg .= "\n" . sprintf(
					/* translators: %s: API error message detail. */
					esc_html__( 'API Error: %s', 'wpfaevent' ),
					$error_message
				);
			}

			$full_msg .= "\n\n" . sprintf( "HTTP Status: %d\nURL: %s\nResponse:\n%s", $status, esc_url_raw( $api_url ), $display_body );

			return new WP_Error(
				'eventyay_http_error',
				$full_msg,
				array(
					'status'      => ( $status >= 400 && $status <= 599 ) ? $status : 502,
					'http_status' => $status,
					'body'        => $decoded_body,
				)
			);
		}

		if ( '' === trim( $body ) ) {
			return new WP_Error(
				'eventyay_empty_response',
				esc_html__( 'Eventyay API returned an empty response.', 'wpfaevent' ),
				array(
					'status'      => 502,
					'http_status' => $status,
				)
			);
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error(
				'eventyay_malformed_json',
				esc_html__( 'Eventyay API returned malformed JSON.', 'wpfaevent' ),
				array(
					'status'          => 502,
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->parser->truncate_string( $body ),
				)
			);
		}

		if ( ! array_key_exists( 'data', $decoded ) ) {
			return new WP_Error(
				'eventyay_invalid_jsonapi',
				esc_html__( 'Eventyay API response does not contain a JSON:API data member.', 'wpfaevent' ),
				array(
					'status'      => 502,
					'http_status' => $status,
					'body'        => $decoded,
				)
			);
		}

		return $decoded;
	}

	/**
	 * Prepare and validate the Eventyay API URL for sync.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Eventyay API URL.
	 * @return string|WP_Error
	 */
	public function prepare_eventyay_sync_url( $api_url ) {
		$api_url = trim( $api_url );

		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
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

		return $api_url;
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
	public function hydrate_eventyay_event_resource( $event, $settings, $fetch_detail ) {
		$event      = $this->parser->normalize_eventyay_event_resource( $event );
		$event_slug = $this->parser->eventyay_event_slug( $event );

		if ( $fetch_detail && $event_slug && $this->parser->eventyay_event_resource_needs_detail( $event ) ) {
			$detail_endpoint = $this->build_eventyay_event_endpoint( $settings, $event_slug );

			if ( ! is_wp_error( $detail_endpoint ) ) {
				$detail_payload = $this->fetch_eventyay_rest_json( $detail_endpoint, $settings['api_token'] );

				if ( ! is_wp_error( $detail_payload ) ) {
					foreach ( $this->parser->extract_eventyay_event_resources( $detail_payload ) as $detail_event ) {
						$event = $this->parser->merge_eventyay_event_resource( $event, $detail_event );
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
	 * Safe debug log helper.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	private function safe_debug_log( $message, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$log_entry = '[WPFAevent Eventyay Debug] ' . $message;
			if ( ! empty( $context ) ) {
				$log_entry .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
			}
			error_log( $log_entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
