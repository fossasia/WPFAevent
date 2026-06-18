<?php
/**
 * Eventyay JSON:API dashboard sync and speaker post management.
 *
 * Handles the AJAX-based Eventyay sync path that uses the JSON:API format
 * (as opposed to the newer REST API import flow in class-wpfaevent-eventyay-importer.php).
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

/**
 * Eventyay JSON:API speaker sync and dashboard file management.
 *
 * Extracted from Wpfaevent_Eventyay_Importer to keep each file to a
 * reviewable size. Responsible for:
 *  - The wp_ajax_fossasia_sync_eventyay AJAX action handler
 *  - JSON:API payload normalisation (sessions, speakers)
 *  - Dashboard speaker-post CRUD and event-speaker relationship sync
 *  - Dashboard JSON file read/write helpers
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Eventyay_Ajax_Sync extends Wpfaevent_Eventyay_Importer {
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

		$payload = $this->fetch_eventyay_json( $api_url );
		if ( is_wp_error( $payload ) ) {
			$this->send_eventyay_ajax_error( $payload );
		}

		$import = $this->normalize_eventyay_payload( $payload );
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
	 * Fetch and decode an Eventyay JSON:API document.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Eventyay API URL.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_json( $api_url ) {
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'headers'     => array(
					'Accept' => 'application/vnd.api+json, application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'eventyay_request_failed',
				esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
				array(
					'status'  => 502,
					'details' => $response->get_error_message(),
				)
			);
		}

		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'eventyay_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					esc_html__( 'Eventyay API returned HTTP %d.', 'wpfaevent' ),
					$status
				),
				array(
					'status'      => ( $status >= 400 && $status <= 599 ) ? $status : 502,
					'http_status' => $status,
					'body'        => $this->decode_eventyay_error_body( $body ),
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
					'http_status'     => $status,
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->truncate_string( $body ),
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
	 * Normalize Eventyay JSON:API sessions or speakers into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload JSON:API document.
	 * @return array|WP_Error
	 */
	private function normalize_eventyay_payload( $payload ) {
		$data = isset( $payload['data'] ) ? $payload['data'] : array();

		if ( $this->is_jsonapi_resource( $data ) ) {
			$data = array( $data );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'eventyay_invalid_data',
				esc_html__( 'Eventyay API data member is not a resource list.', 'wpfaevent' ),
				array( 'status' => 502 )
			);
		}

		$included      = $this->index_jsonapi_resources( isset( $payload['included'] ) ? $payload['included'] : array() );
		$speakers      = array();
		$session_count = 0;

		foreach ( $data as $resource ) {
			if ( ! $this->is_jsonapi_resource( $resource ) ) {
				continue;
			}

			if ( $this->jsonapi_type_is( $resource, 'speaker' ) ) {
				$speaker = $this->normalize_eventyay_speaker_resource( $resource );

				if ( ! empty( $speaker['name'] ) ) {
					$this->merge_eventyay_speaker( $speakers, $speaker, array() );
				}

				continue;
			}

			if ( ! $this->jsonapi_type_is( $resource, 'session' ) ) {
				continue;
			}

			++$session_count;

			$session      = $this->normalize_eventyay_session_resource( $resource, $included );
			$speaker_refs = $this->get_jsonapi_relationship_resources( $resource, 'speakers' );

			foreach ( $speaker_refs as $speaker_ref ) {
				$speaker_resource = $this->resolve_jsonapi_resource( $speaker_ref, $included );

				if ( ! $this->is_jsonapi_resource( $speaker_resource ) ) {
					continue;
				}

				$speaker = $this->normalize_eventyay_speaker_resource( $speaker_resource );
				if ( empty( $speaker['name'] ) ) {
					continue;
				}

				if ( empty( $speaker['category'] ) && ! empty( $session['track'] ) ) {
					$speaker['category'] = $session['track'];
				}

				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}
		}

		$speakers = array_values( $speakers );

		if ( ! empty( $data ) && empty( $speakers ) ) {
			return new WP_Error(
				'eventyay_no_speakers',
				esc_html__( 'No speaker records were found in the Eventyay response. Use a sessions URL with include=speakers,track or a speakers URL.', 'wpfaevent' ),
				array( 'status' => 422 )
			);
		}

		return array(
			'speakers'      => $speakers,
			'session_count' => $session_count,
		);
	}

	/**
	 * Normalize a JSON:API session resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $session_resource Session resource.
	 * @param array $included         Indexed included resources.
	 * @return array
	 */
	private function normalize_eventyay_session_resource( $session_resource, $included ) {
		$attributes = $this->get_jsonapi_attributes( $session_resource );
		$starts_at  = $this->attribute_value( $attributes, array( 'starts-at', 'start-time', 'starts_at' ) );
		$ends_at    = $this->attribute_value( $attributes, array( 'ends-at', 'end-time', 'ends_at' ) );
		$track_name = '';
		$track_refs = $this->get_jsonapi_relationship_resources( $session_resource, 'track' );

		if ( ! empty( $track_refs ) ) {
			$track = $this->resolve_jsonapi_resource( $track_refs[0], $included );
			if ( $this->is_jsonapi_resource( $track ) ) {
				$track_attributes = $this->get_jsonapi_attributes( $track );
				$track_name       = $this->attribute_value( $track_attributes, array( 'name', 'title' ) );
			}
		}

		return array(
			'id'        => isset( $session_resource['id'] ) ? 'eventyay-session-' . sanitize_key( $session_resource['id'] ) : '',
			'title'     => sanitize_text_field( $this->attribute_value( $attributes, array( 'title', 'subtitle', 'name' ) ) ),
			'date'      => $this->format_eventyay_date( $starts_at ),
			'time'      => $this->format_eventyay_time( $starts_at ),
			'end_time'  => $this->format_eventyay_time( $ends_at ),
			'starts_at' => $this->normalize_eventyay_datetime( $starts_at ),
			'ends_at'   => $this->normalize_eventyay_datetime( $ends_at ),
			'abstract'  => wp_kses_post( $this->attribute_value( $attributes, array( 'long-abstract', 'short-abstract', 'abstract', 'description' ) ) ),
			'track'     => sanitize_text_field( $track_name ),
			'source_id' => isset( $session_resource['id'] ) ? sanitize_text_field( $session_resource['id'] ) : '',
		);
	}

	/**
	 * Normalize a JSON:API speaker resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker_resource Speaker resource.
	 * @return array
	 */
	private function normalize_eventyay_speaker_resource( $speaker_resource ) {
		$attributes = $this->get_jsonapi_attributes( $speaker_resource );
		$source_id  = isset( $speaker_resource['id'] ) ? sanitize_text_field( $speaker_resource['id'] ) : '';
		$name       = $this->attribute_value( $attributes, array( 'name', 'full-name', 'display-name' ) );

		if ( empty( $name ) ) {
			$name = trim(
				$this->attribute_value( $attributes, array( 'first-name', 'first_name' ) ) . ' ' .
				$this->attribute_value( $attributes, array( 'last-name', 'last_name' ) )
			);
		}

		$position     = $this->attribute_value( $attributes, array( 'position', 'job-title', 'designation' ) );
		$organization = $this->attribute_value( $attributes, array( 'organisation', 'organization', 'company' ) );
		$category     = $this->attribute_value( $attributes, array( 'category', 'track' ) );

		return array(
			'id'                  => $source_id ? 'eventyay-' . sanitize_key( $source_id ) : 'eventyay-' . sanitize_title( $name ),
			'eventyay_speaker_id' => $source_id,
			'name'                => sanitize_text_field( $name ),
			'title'               => sanitize_text_field( $position ? $position : $organization ),
			'position'            => sanitize_text_field( $position ),
			'organization'        => sanitize_text_field( $organization ),
			'category'            => sanitize_text_field( $category ),
			'image'               => esc_url_raw(
				$this->attribute_value(
					$attributes,
					array(
						'photo-url',
						'thumbnail-image-url',
						'small-image-url',
						'icon-image-url',
						'original-image-url',
						'avatar-url',
					)
				)
			),
			'bio'                 => wp_kses_post( $this->attribute_value( $attributes, array( 'long-biography', 'short-biography', 'biography', 'speaking-experience' ) ) ),
			'social'              => array(
				'linkedin' => esc_url_raw( $this->attribute_value( $attributes, array( 'linkedin', 'linkedin-url' ) ) ),
				'twitter'  => esc_url_raw( $this->attribute_value( $attributes, array( 'twitter', 'twitter-url' ) ) ),
				'github'   => esc_url_raw( $this->attribute_value( $attributes, array( 'github', 'github-url' ) ) ),
				'website'  => esc_url_raw( $this->attribute_value( $attributes, array( 'website', 'website-url' ) ) ),
			),
			'featured'            => $this->eventyay_speaker_is_featured( $attributes, $category ),
			'featured_order'      => $this->eventyay_speaker_featured_order( $attributes ),
			'sessions'            => array(),
			'source'              => 'eventyay',
		);
	}

















	/**
	 * Get a JSON:API resource's attributes array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $jsonapi_resource JSON:API resource.
	 * @return array
	 */
	private function get_jsonapi_attributes( $jsonapi_resource ) {
		return isset( $jsonapi_resource['attributes'] ) && is_array( $jsonapi_resource['attributes'] ) ? $jsonapi_resource['attributes'] : array();
	}

	/**
	 * Get a scalar attribute value by trying multiple possible keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Attribute map.
	 * @param array $keys       Candidate keys.
	 * @return string
	 */
	private function attribute_value( $attributes, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ) {
				return (string) $attributes[ $key ];
			}
		}

		return '';
	}

	/**
	 * Index JSON:API included resources by type and ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Included resources.
	 * @return array
	 */
	private function index_jsonapi_resources( $resources ) {
		$index = array();

		if ( ! is_array( $resources ) ) {
			return $index;
		}

		foreach ( $resources as $resource ) {
			if ( ! $this->is_jsonapi_resource( $resource ) ) {
				continue;
			}

			$key           = $this->jsonapi_resource_key( $resource['type'], $resource['id'] );
			$index[ $key ] = $resource;
		}

		return $index;
	}

	/**
	 * Resolve a relationship identifier from included resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resource_identifier JSON:API resource identifier.
	 * @param array $included            Indexed included resources.
	 * @return array
	 */
	private function resolve_jsonapi_resource( $resource_identifier, $included ) {
		if ( ! $this->is_jsonapi_resource( $resource_identifier ) ) {
			return array();
		}

		$type       = strtolower( (string) $resource_identifier['type'] );
		$id         = (string) $resource_identifier['id'];
		$candidates = array( $type );

		if ( 's' === substr( $type, -1 ) ) {
			$candidates[] = substr( $type, 0, -1 );
		} else {
			$candidates[] = $type . 's';
		}

		foreach ( array_unique( $candidates ) as $candidate_type ) {
			$key = $this->jsonapi_resource_key( $candidate_type, $id );
			if ( isset( $included[ $key ] ) ) {
				return $included[ $key ];
			}
		}

		if ( ! empty( $resource_identifier['attributes'] ) ) {
			return $resource_identifier;
		}

		return array();
	}

	/**
	 * Get relationship resources as a list.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $jsonapi_resource JSON:API resource.
	 * @param string $name             Relationship name.
	 * @return array
	 */
	private function get_jsonapi_relationship_resources( $jsonapi_resource, $name ) {
		if ( empty( $jsonapi_resource['relationships'][ $name ] ) || ! is_array( $jsonapi_resource['relationships'][ $name ] ) ) {
			return array();
		}

		$relationship = $jsonapi_resource['relationships'][ $name ];
		if ( ! array_key_exists( 'data', $relationship ) || null === $relationship['data'] ) {
			return array();
		}

		if ( $this->is_jsonapi_resource( $relationship['data'] ) ) {
			return array( $relationship['data'] );
		}

		return is_array( $relationship['data'] ) ? $relationship['data'] : array();
	}


	/**
	 * Compare a JSON:API resource type, accepting singular or plural forms.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $jsonapi_resource JSON:API resource.
	 * @param string $type             Expected singular type.
	 * @return bool
	 */
	private function jsonapi_type_is( $jsonapi_resource, $type ) {
		if ( empty( $jsonapi_resource['type'] ) ) {
			return false;
		}

		$resource_type = strtolower( (string) $jsonapi_resource['type'] );
		$type          = strtolower( $type );

		return $resource_type === $type || $resource_type === $type . 's';
	}

	/**
	 * Build an index key for a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Resource type.
	 * @param string $id   Resource ID.
	 * @return string
	 */
	private function jsonapi_resource_key( $type, $id ) {
		return strtolower( (string) $type ) . ':' . (string) $id;
	}
}
