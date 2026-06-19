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
}
