<?php
/**
 * Eventyay Importer API Client.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles communication with the Eventyay API.
 */
class Wpfaevent_Eventyay_API_Client {

	/**
	 * JSONAPI Parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_JSONAPI_Parser|null $parser Optional parser instance.
	 */
	public function __construct( $parser = null ) {
		$this->parser = $parser ? $parser : new Wpfaevent_JSONAPI_Parser();
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

		return array( $primary_endpoint );
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

		$url        = trailingslashit( $base_url ) . $path;
		$query_args = array(
			'lang'      => 'en',
			'page_size' => absint( apply_filters( 'wpfaevent_eventyay_import_page_size', 100 ) ),
		);

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
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'The Eventyay organizer or event slug is missing.', 'wpfaevent' )
			);
		}

		$path = sprintf(
			'api/v1/events/%s/event-settings',
			rawurlencode( $event_slug )
		);

		return esc_url_raw( trailingslashit( $base_url ) . $path );
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
	 * Fetch settings from the Eventyay event-settings API.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Event slug.
	 * @return array|WP_Error Settings payload.
	 */
	public function fetch_eventyay_event_settings_resource( $settings, $event_slug ) {
		$endpoint = $this->build_eventyay_event_settings_endpoint( $settings, $event_slug );
		if ( is_wp_error( $endpoint ) ) {
			return $endpoint;
		}

		$payload = $this->fetch_eventyay_rest_json( $endpoint, $settings['api_token'] );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		if ( ! empty( $payload['data']['attributes'] ) && is_array( $payload['data']['attributes'] ) ) {
			return $payload['data']['attributes'];
		}

		return array();
	}

	/**
	 * Normalize a next page URL returned by the Eventyay pagination header.
	 *
	 * @since 1.0.0
	 *
	 * @param string $next_url      Next page URL.
	 * @param string $reference_url Reference URL.
	 * @return string|WP_Error Normalized next URL.
	 */
	public function normalize_eventyay_next_url( $next_url, $reference_url ) {
		$next_url = trim( (string) $next_url );
		if ( '' === $next_url ) {
			return '';
		}

		if ( ! wp_http_validate_url( $next_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_next_url',
				esc_html__( 'Eventyay pagination returned an invalid next page URL.', 'wpfaevent' )
			);
		}

		if ( ! $this->eventyay_urls_share_origin( $next_url, $reference_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_unsafe_pagination',
				esc_html__( 'Eventyay pagination returned a next URL with a different host origin.', 'wpfaevent' )
			);
		}

		return esc_url_raw( $next_url );
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
	public function eventyay_urls_share_origin( $url, $base_url ) {
		$origin_1 = $this->eventyay_url_origin( $url );
		$origin_2 = $this->eventyay_url_origin( $base_url );

		return '' !== $origin_1 && $origin_1 === $origin_2;
	}

	/**
	 * Build a normalized origin string (scheme://host:port) for a URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return string Origin.
	 */
	public function eventyay_url_origin( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = strtolower( $parts['scheme'] );
		$host   = strtolower( $parts['host'] );
		$port   = isset( $parts['port'] ) ? absint( $parts['port'] ) : $this->default_port_for_scheme( $scheme );

		return $scheme . '://' . $host . ':' . $port;
	}

	/**
	 * Get the default HTTP port for a scheme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scheme Scheme.
	 * @return int Port.
	 */
	public function default_port_for_scheme( $scheme ) {
		return 'https' === strtolower( $scheme ) ? 443 : 80;
	}

	/**
	 * Fetch a REST resource payload from the Eventyay API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url   API URL.
	 * @param string $api_token API access token.
	 * @return array|WP_Error Payload map.
	 */
	public function fetch_eventyay_rest_json( $api_url, $api_token = '' ) {
		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$schemes = $this->get_eventyay_authorization_schemes( $api_token );
		$errors  = array();

		foreach ( $schemes as $scheme ) {
			$headers  = $this->build_eventyay_rest_headers( $api_token, $scheme );
			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'     => 30,
					'redirection' => 3,
					'headers'     => $headers,
				)
			);

			if ( is_wp_error( $response ) ) {
				$errors[] = $response;
				continue;
			}

			$decoded = $this->decode_eventyay_rest_response( $response, $api_url );
			if ( ! is_wp_error( $decoded ) ) {
				return $decoded;
			}

			$errors[] = $decoded;
		}

		return isset( $errors[0] ) ? $errors[0] : new WP_Error(
			'wpfaevent_eventyay_request_failed',
			esc_html__( 'All Eventyay API authentication retries failed.', 'wpfaevent' )
		);
	}

	/**
	 * Decode response payloads from Eventyay REST resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $response Response array.
	 * @param string $api_url  API URL.
	 * @return array|WP_Error
	 */
	public function decode_eventyay_rest_response( $response, $api_url ) {
		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'wpfaevent_eventyay_http_error',
				sprintf(
					/* translators: 1: URL, 2: status code. */
					esc_html__( 'Eventyay API request to %1$s failed with HTTP %2$d.', 'wpfaevent' ),
					esc_url( $api_url ),
					$status
				),
				array(
					'http_status' => $status,
					'body'        => $this->decode_eventyay_error_body( $body ),
				)
			);
		}

		if ( '' === trim( $body ) ) {
			return array();
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_malformed_json',
				esc_html__( 'Eventyay API returned malformed JSON.', 'wpfaevent' ),
				array(
					'http_status'     => $status,
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->parser->truncate_string( $body ),
				)
			);
		}

		return $decoded;
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
	 * Build header fields for Eventyay REST requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token   Optional API token.
	 * @param string $auth_scheme Authorization scheme.
	 * @return array<string, string> Headers.
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
}
