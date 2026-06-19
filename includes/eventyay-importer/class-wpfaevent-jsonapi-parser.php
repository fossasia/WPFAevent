<?php
/**
 * Eventyay Importer JSONAPI Parser.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles normalization, validation, and JSON:API format traversal.
 */
class Wpfaevent_JSONAPI_Parser {

	/**
	 * Determine whether an array resembles a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $maybe_resource Possible resource.
	 * @return bool
	 */
	public function is_jsonapi_resource( $maybe_resource ) {
		return is_array( $maybe_resource ) && isset( $maybe_resource['type'], $maybe_resource['id'] );
	}

	/**
	 * Extract event resources from an Eventyay JSON:API document.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload JSON:API document payload.
	 * @return array Event resources.
	 */
	public function extract_eventyay_event_resources( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			return array();
		}

		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			if ( $this->is_jsonapi_resource( $payload['data'] ) ) {
				return array( $payload['data'] );
			}

			return array_filter( $payload['data'], 'is_array' );
		}

		if ( isset( $payload['id'] ) || isset( $payload['attributes'] ) ) {
			return array( $payload );
		}

		return array();
	}

	/**
	 * Normalize a single Eventyay event resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return array Normalized event resource.
	 */
	public function normalize_eventyay_event_resource( $event ) {
		$event = $this->normalize_eventyay_api_resource( $event );

		$defaults = array(
			'identifier'               => '',
			'name'                     => '',
			'description'              => '',
			'starts_at'                => '',
			'ends_at'                  => '',
			'timezone'                 => '',
			'location_name'            => '',
			'searchable_location_name' => '',
			'latitude'                 => '',
			'longitude'                => '',
			'external_event_url'       => '',
			'logo_url'                 => '',
			'banner_url'               => '',
			'organizer_name'           => '',
			'organizer_description'    => '',
			'ticket_url'               => '',
			'code'                     => '',
		);

		return wp_parse_args( $event, $defaults );
	}

	/**
	 * Check whether an Eventyay event resource is sparse and needs detail.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return bool
	 */
	public function eventyay_event_resource_needs_detail( $event ) {
		return empty( $event['description'] ) || empty( $event['starts_at'] ) || empty( $event['timezone'] );
	}

	/**
	 * Merge sparse and detailed Eventyay event resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array $base   Base event resource.
	 * @param array $detail Detailed event resource.
	 * @return array Merged event resource.
	 */
	public function merge_eventyay_event_resource( $base, $detail ) {
		$detail = $this->normalize_eventyay_event_resource( $detail );

		foreach ( $detail as $key => $value ) {
			if ( $this->eventyay_value_is_non_empty( $value ) ) {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * Check whether an Eventyay value is non-empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	public function eventyay_value_is_non_empty( $value ) {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		return '' !== trim( (string) $value );
	}

	/**
	 * Normalize a raw Eventyay resource into a flat field map.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @return array
	 */
	public function normalize_eventyay_api_resource( $eventyay_resource ) {
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
	 * Normalize JSON:API relationship data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $relationship_data Relationship data.
	 * @return mixed
	 */
	public function normalize_eventyay_relationship_data( $relationship_data ) {
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
	public function eventyay_list_value( $value ) {
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
	public function eventyay_resource_identifier( $eventyay_resource ) {
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
	public function eventyay_first_present_text( $eventyay_resource, $keys ) {
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
	public function eventyay_first_present_rich_text( $eventyay_resource, $keys ) {
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
	 * @param bool  $include_settings  Unused parameter to maintain signature compat.
	 * @return mixed
	 */
	public function eventyay_first_present_raw( $eventyay_resource, $keys, $include_settings = false ) {
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

		if ( $include_settings && ! empty( $eventyay_resource['_eventyay_settings'] ) && is_array( $eventyay_resource['_eventyay_settings'] ) ) {
			return $this->eventyay_first_present_raw( $eventyay_resource['_eventyay_settings'], $keys, false );
		}

		return '';
	}

	/**
	 * Extract event slug.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_slug( $event ) {
		return $this->eventyay_first_present_text( $event, array( 'identifier', 'code', 'slug', 'id' ) );
	}

	/**
	 * Get a plain text string from an Eventyay value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	public function eventyay_text_value( $value ) {
		$resolved = $this->eventyay_scalar_value( $value );

		return sanitize_text_field( $resolved );
	}

	/**
	 * Get a rich text string from an Eventyay value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	public function eventyay_rich_text_value( $value ) {
		if ( is_scalar( $value ) ) {
			return wp_kses_post( trim( (string) $value ) );
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
	public function eventyay_scalar_value( $value ) {
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
	 * Convert an Eventyay URL-ish value into an absolute URL.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $base_url Eventyay base URL.
	 * @return string
	 */
	public function eventyay_url_value( $value, $base_url ) {
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
	public function eventyay_location_text_value( $value ) {
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
	 * Get a JSON:API resource's attributes array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $jsonapi_resource JSON:API resource.
	 * @return array
	 */
	public function get_jsonapi_attributes( $jsonapi_resource ) {
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
	public function attribute_value( $attributes, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ) {
				return (string) $attributes[ $key ];
			}
		}

		return '';
	}

	/**
	 * Truncate a string for structured error output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value String value.
	 * @return string
	 */
	public function truncate_string( $value ) {
		$value = wp_strip_all_tags( (string) $value );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 1000 );
		}

		return substr( $value, 0, 1000 );
	}

	/**
	 * Normalize Eventyay JSON:API sessions or speakers into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload JSON:API document.
	 * @return array|WP_Error
	 */
	public function normalize_eventyay_payload( $payload ) {
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
	 * @param array $included         Included resources index.
	 * @return array
	 */
	public function normalize_eventyay_session_resource( $session_resource, $included ) {
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
	public function normalize_eventyay_speaker_resource( $speaker_resource ) {
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
	 * Merge a speaker and optional session into a keyed speaker list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speakers Speaker list keyed by normalized source.
	 * @param array $speaker  Speaker data.
	 * @param array $session  Session data.
	 * @return void
	 */
	public function merge_eventyay_speaker( &$speakers, $speaker, $session ) {
		$key = ! empty( $speaker['eventyay_speaker_id'] ) ? 'eventyay:' . $speaker['eventyay_speaker_id'] : 'name:' . sanitize_title( $speaker['name'] );

		if ( empty( $speakers[ $key ] ) ) {
			$speakers[ $key ] = $speaker;
		} else {
			foreach ( array( 'title', 'position', 'organization', 'category', 'image', 'bio' ) as $field ) {
				if ( empty( $speakers[ $key ][ $field ] ) && ! empty( $speaker[ $field ] ) ) {
					$speakers[ $key ][ $field ] = $speaker[ $field ];
				}
			}

			foreach ( $speaker['social'] as $field => $value ) {
				if ( empty( $speakers[ $key ]['social'][ $field ] ) && ! empty( $value ) ) {
					$speakers[ $key ]['social'][ $field ] = $value;
				}
			}
		}

		if ( ! empty( $speaker['featured'] ) ) {
			$speakers[ $key ]['featured'] = true;
		}

		if ( ! empty( $speaker['featured_order'] ) ) {
			$current_order = isset( $speakers[ $key ]['featured_order'] ) ? absint( $speakers[ $key ]['featured_order'] ) : 0;
			$new_order     = absint( $speaker['featured_order'] );
			if ( ! $current_order || $new_order < $current_order ) {
				$speakers[ $key ]['featured_order'] = $new_order;
			}
		}

		if ( empty( $session ) ) {
			return;
		}

		$session_ids = wp_list_pluck( $speakers[ $key ]['sessions'], 'id' );
		if ( empty( $session['id'] ) || ! in_array( $session['id'], $session_ids, true ) ) {
			$speakers[ $key ]['sessions'][] = $session;
		}
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
	public function eventyay_speaker_is_featured( $speaker_resource, $category = '' ) {
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
	public function eventyay_speaker_featured_order( $speaker_resource ) {
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
	public function eventyay_truthy_value( $value ) {
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
	 * Index JSON:API included resources by type and ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Included resources.
	 * @return array
	 */
	public function index_jsonapi_resources( $resources ) {
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
	public function resolve_jsonapi_resource( $resource_identifier, $included ) {
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
	public function get_jsonapi_relationship_resources( $jsonapi_resource, $name ) {
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
	public function jsonapi_type_is( $jsonapi_resource, $type ) {
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
	public function jsonapi_resource_key( $type, $id ) {
		return strtolower( (string) $type ) . ':' . (string) $id;
	}

	/**
	 * Format an Eventyay date-time value as a date.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone for display fields.
	 * @return string
	 */
	public function format_eventyay_date( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'Y-m-d' );
	}

	/**
	 * Format an Eventyay date-time value as a time.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone for display fields.
	 * @return string
	 */
	public function format_eventyay_time( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'H:i' );
	}

	/**
	 * Determine whether an Eventyay value contains a time component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date or date-time value.
	 * @return bool
	 */
	public function eventyay_datetime_has_time( $value ) {
		return is_scalar( $value ) && 1 === preg_match( '/[T\s]\d{1,2}:\d{2}/', (string) $value );
	}

	/**
	 * Build a timezone object from a sanitized Eventyay timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone Timezone identifier.
	 * @return DateTimeZone|null
	 */
	public function eventyay_timezone_object( $timezone ) {
		$timezone = Wpfaevent_Meta_Event::sanitize_timezone( $timezone );

		if ( '' === $timezone ) {
			return null;
		}

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Normalize an Eventyay date-time value while preserving its timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date-time value.
	 * @return string
	 */
	public function normalize_eventyay_datetime( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		return $date->format( DATE_ATOM );
	}
}
