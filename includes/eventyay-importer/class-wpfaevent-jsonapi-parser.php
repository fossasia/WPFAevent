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
	 * Low-level JSON:API resource helpers.
	 *
	 * @var Wpfaevent_JSONAPI_Resource_Utils
	 */
	private $resource_utils;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->resource_utils = new Wpfaevent_JSONAPI_Resource_Utils();
	}

	/**
	 * Determine whether an array resembles a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $maybe_resource Possible resource.
	 * @return bool
	 */
	public function is_jsonapi_resource( $maybe_resource ) {
		return $this->resource_utils->is_jsonapi_resource( $maybe_resource );
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
		return $this->resource_utils->extract_eventyay_event_resources( $payload );
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
		return $this->resource_utils->normalize_eventyay_api_resource( $eventyay_resource );
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
		return $this->resource_utils->normalize_eventyay_relationship_data( $relationship_data );
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
		return $this->resource_utils->eventyay_list_value( $value );
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
		return $this->resource_utils->eventyay_resource_identifier( $eventyay_resource );
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
		return $this->resource_utils->eventyay_first_present_raw( $eventyay_resource, $keys, $include_settings );
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
		return $this->resource_utils->eventyay_text_value( $value );
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
		return $this->resource_utils->eventyay_rich_text_value( $value );
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
		return $this->resource_utils->eventyay_scalar_value( $value );
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
		return $this->resource_utils->eventyay_url_value( $value, $base_url );
	}

	/**
	 * Helper method to validate if a string is a syntactically valid HTTP/HTTPS URL.
	 * Unlike wp_http_validate_url, it does not reject local hostnames/IPs.
	 *
	 * @param string $url URL to validate.
	 * @return bool
	 */
	public function is_valid_http_url( $url ) {
		return $this->resource_utils->is_valid_http_url( $url );
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
		return $this->resource_utils->eventyay_location_text_value( $value );
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
		return $this->resource_utils->get_jsonapi_attributes( $jsonapi_resource );
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
		return $this->resource_utils->attribute_value( $attributes, $keys );
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
		return $this->resource_utils->truncate_string( $value );
	}

	/**
	 * Normalize an Eventyay API program payload into the internal format.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $payload    JSON:API document or Eventyay REST paginated response.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error
	 */
	public function normalize_eventyay_payload( $payload, $settings = array(), $event_slug = '' ) {
		// Eventyay REST API (Pretix-based) returns {"count":N,"results":[...]} instead of JSON:API.
		if ( ! array_key_exists( 'data', $payload ) && array_key_exists( 'results', $payload ) && is_array( $payload['results'] ) ) {
			return $this->normalize_eventyay_rest_speakers_payload( $payload['results'], $settings, $event_slug );
		}

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
		$sessions      = array();
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

			$session       = $this->normalize_eventyay_session_resource( $resource, $included );
			$speaker_refs  = $this->get_jsonapi_relationship_resources( $resource, 'speakers' );
			$speaker_names = array();

			foreach ( $speaker_refs as $speaker_ref ) {
				$speaker_resource = $this->resolve_jsonapi_resource( $speaker_ref, $included );

				if ( ! $this->is_jsonapi_resource( $speaker_resource ) ) {
					continue;
				}

				$speaker = $this->normalize_eventyay_speaker_resource( $speaker_resource );
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
			'sessions'      => array_values( $sessions ),
			'session_count' => $session_count,
		);
	}

	/**
	 * Normalize Eventyay REST API paginated speakers ({"count":N,"results":[...]}) into
	 * the internal speaker format.
	 *
	 * The Pretix-based Eventyay API returns speaker resources with fields:
	 * code, name, biography, avatar, answers.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $results    Raw results array from the Eventyay REST API.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array
	 */
	public function normalize_eventyay_rest_speakers_payload( $results, $settings = array(), $event_slug = '' ) {
		$speakers = array();
		$sessions = array();

		if ( ! is_array( $results ) ) {
			return array(
				'speakers'      => array(),
				'sessions'      => array(),
				'session_count' => 0,
			);
		}

		if ( empty( $settings['base_url'] ) ) {
			$settings['base_url'] = 'https://api.eventyay.com';
		}
		if ( empty( $settings['organizer_slug'] ) ) {
			$settings['organizer_slug'] = '';
		}

		foreach ( $results as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item    = $this->normalize_eventyay_api_resource( $item );
			$speaker = $this->normalize_eventyay_submission_speaker( $item, $settings, $event_slug );
			if ( empty( $speaker['name'] ) ) {
				continue;
			}

			$speaker_sessions = $this->eventyay_list_value( $this->eventyay_first_present_raw( $item, array( 'submissions', 'sessions', 'talks' ) ) );
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
	 * Transform expanded slots schedule payload into the standard speakers list format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slots Raw slots array from the Eventyay slots API.
	 * @return array Reconstructed speakers payload in Pretix REST format.
	 */
	public function transform_slots_to_speakers_payload( $slots ) {
		$speakers_map = array();

		if ( ! is_array( $slots ) ) {
			return array(
				'count'    => 0,
				'next'     => null,
				'previous' => null,
				'results'  => array(),
			);
		}

		foreach ( $slots as $slot ) {
			if ( empty( $slot['submission'] ) || ! is_array( $slot['submission'] ) ) {
				continue;
			}

			$submission    = $slot['submission'];
			$speakers      = isset( $submission['speakers'] ) ? $submission['speakers'] : array();
			$submission_id = isset( $submission['code'] ) ? $submission['code'] : ( isset( $submission['id'] ) ? $submission['id'] : '' );

			if ( empty( $submission_id ) ) {
				continue;
			}

			// Reconstruct slot resource to nest inside submission.
			$embedded_slot = array(
				'id'       => isset( $slot['id'] ) ? $slot['id'] : '',
				'start'    => isset( $slot['start'] ) ? $slot['start'] : '',
				'end'      => isset( $slot['end'] ) ? $slot['end'] : '',
				'duration' => isset( $slot['duration'] ) ? $slot['duration'] : 0,
			);

			if ( ! empty( $slot['room'] ) ) {
				$embedded_slot['room'] = $slot['room'];
			}

			$submission['slots'] = array( $embedded_slot );
			$submission['slot']  = $embedded_slot;

			foreach ( $speakers as $spk ) {
				if ( ! is_array( $spk ) || empty( $spk['code'] ) ) {
					continue;
				}

				$code = sanitize_text_field( (string) $spk['code'] );

				if ( ! isset( $speakers_map[ $code ] ) ) {
					$speakers_map[ $code ] = array(
						'code'           => $code,
						'name'           => isset( $spk['fullname'] ) ? $spk['fullname'] : ( isset( $spk['name'] ) ? $spk['name'] : '' ),
						'biography'      => isset( $spk['biography'] ) ? $spk['biography'] : '',
						'avatar'         => isset( $spk['avatar_url'] ) ? $spk['avatar_url'] : ( isset( $spk['avatar'] ) ? $spk['avatar'] : '' ),
						'position'       => isset( $spk['position'] ) ? $spk['position'] : '',
						'organization'   => isset( $spk['organization'] ) ? $spk['organization'] : '',
						'category'       => isset( $spk['category'] ) ? $spk['category'] : '',
						'linkedin'       => isset( $spk['linkedin'] ) ? $spk['linkedin'] : '',
						'twitter'        => isset( $spk['twitter'] ) ? $spk['twitter'] : '',
						'github'         => isset( $spk['github'] ) ? $spk['github'] : '',
						'website'        => isset( $spk['website'] ) ? $spk['website'] : '',
						'featured'       => isset( $spk['featured'] ) ? (bool) $spk['featured'] : false,
						'featured_order' => isset( $spk['featured_order'] ) ? (int) $spk['featured_order'] : 0,
						'submissions'    => array(),
					);
				}

				$speakers_map[ $code ]['submissions'][] = $submission;
			}
		}

		return array(
			'count'    => count( $speakers_map ),
			'next'     => null,
			'previous' => null,
			'results'  => array_values( $speakers_map ),
		);
	}

	/**
	 * Normalize a single Eventyay submission speaker resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker_resource Eventyay speaker resource.
	 * @param array  $settings         Import settings.
	 * @param string $event_slug       Eventyay event slug.
	 * @return array
	 */
	public function normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug ) {
		$speaker_resource = $this->normalize_eventyay_api_resource( $speaker_resource );
		$source_id        = $this->eventyay_resource_identifier( $speaker_resource );
		$name             = $this->eventyay_first_present_text( $speaker_resource, array( 'name', 'fullname', 'full_name', 'full-name', 'public_name', 'public-name', 'display_name', 'display-name' ) );

		if ( empty( $name ) ) {
			$name = trim(
				$this->eventyay_text_value( isset( $speaker_resource['first_name'] ) ? $speaker_resource['first_name'] : '' ) . ' ' .
				$this->eventyay_text_value( isset( $speaker_resource['last_name'] ) ? $speaker_resource['last_name'] : '' )
			);
		}

		$organizer_slug      = ! empty( $settings['organizer_slug'] ) ? $settings['organizer_slug'] : '';
		$eventyay_speaker_id = implode(
			':',
			array_filter(
				array(
					$organizer_slug,
					$event_slug,
					$source_id ? $source_id : sanitize_title( $name ),
				)
			)
		);

		$position     = $this->eventyay_first_present_text( $speaker_resource, array( 'position', 'job_title', 'job-title', 'title', 'role', 'speaking_experience', 'speaking-experience' ) );
		$organization = $this->eventyay_first_present_text( $speaker_resource, array( 'organization', 'organisation', 'company', 'affiliation' ) );
		$category     = $this->eventyay_first_present_text( $speaker_resource, array( 'category', 'track' ) );

		$avatar_raw   = $this->eventyay_first_present_raw( $speaker_resource, array( 'avatar', 'avatar_url', 'avatar-url', 'avatar_url_original', 'avatar-url-original', 'image', 'image_url', 'image-url', 'photo', 'photo_url', 'photo-url' ) );
		$linkedin_raw = $this->eventyay_first_present_raw( $speaker_resource, array( 'linkedin', 'linkedin_url', 'linkedin-url' ) );
		$twitter_raw  = $this->eventyay_first_present_raw( $speaker_resource, array( 'twitter', 'twitter_url', 'twitter-url', 'x_url' ) );
		$github_raw   = $this->eventyay_first_present_raw( $speaker_resource, array( 'github', 'github_url', 'github-url' ) );
		$website_raw  = $this->eventyay_first_present_raw( $speaker_resource, array( 'website', 'website_url', 'website-url', 'homepage', 'homepage_url', 'homepage-url', 'url' ) );

		return array(
			'id'                  => 'eventyay-' . sanitize_key( $eventyay_speaker_id ),
			'eventyay_speaker_id' => sanitize_text_field( $eventyay_speaker_id ),
			'name'                => sanitize_text_field( $name ),
			'title'               => sanitize_text_field( $position ? $position : $organization ),
			'position'            => sanitize_text_field( $position ),
			'organization'        => sanitize_text_field( $organization ),
			'category'            => sanitize_text_field( $category ),
			'image'               => $this->eventyay_url_value( $avatar_raw, $settings['base_url'] ),
			'bio'                 => $this->eventyay_first_present_rich_text( $speaker_resource, array( 'biography', 'bio', 'description', 'abstract', 'short_biography', 'short-biography', 'long_biography', 'long-biography' ) ),
			'social'              => array(
				'linkedin' => $this->eventyay_url_value( $linkedin_raw, $settings['base_url'] ),
				'twitter'  => $this->eventyay_url_value( $twitter_raw, $settings['base_url'] ),
				'github'   => $this->eventyay_url_value( $github_raw, $settings['base_url'] ),
				'website'  => $this->eventyay_url_value( $website_raw, $settings['base_url'] ),
			),
			'featured'            => $this->eventyay_speaker_is_featured( $speaker_resource, $category ),
			'featured_order'      => $this->eventyay_speaker_featured_order( $speaker_resource ),
			'sessions'            => array(),
			'source'              => 'eventyay',
		);
	}

	/**
	 * Get the room name from a schedule slot resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slot Eventyay slot resource.
	 * @return string
	 */
	public function eventyay_slot_room_name( $slot ) {
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
	 * Extract the first scheduled slot from a submission resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	public function eventyay_first_slot( $submission ) {
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
	 * Extract a submission abstract from likely Eventyay fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return string
	 */
	public function eventyay_submission_abstract( $submission ) {
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
	 * Normalize an Eventyay submission resource into the internal speaker session format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	public function normalize_eventyay_submission_session( $submission ) {
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

		$title = $this->eventyay_text_value( isset( $submission['title'] ) ? $submission['title'] : '' );

		return array(
			'id'        => $source_id ? 'eventyay-submission-' . sanitize_key( $source_id ) : 'eventyay-submission-' . sanitize_title( $title ),
			'title'     => $title,
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

	/**
	 * Determine whether a normalized Eventyay session has displayable content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $session Normalized session.
	 * @return bool
	 */
	public function eventyay_session_has_content( $session ) {
		foreach ( array( 'title', 'date', 'time', 'end_time', 'abstract', 'track', 'room' ) as $key ) {
			if ( ! empty( $session[ $key ] ) ) {
				return true;
			}
		}

		return ! empty( $session['speakers'] );
	}

	/**
	 * Merge an Eventyay session into a session list, combining duplicate speaker names.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sessions Session list.
	 * @param array $session  Session payload.
	 * @return array
	 */
	public function merge_eventyay_session_payload( $sessions, $session ) {
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
	 * Normalize an Eventyay API program payload into the internal format.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $payload    JSON:API document or Eventyay REST paginated response.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error
	 */
	public function normalize_eventyay_payload( $payload, $settings = array(), $event_slug = '' ) {
		// Eventyay REST API (Pretix-based) returns {"count":N,"results":[...]} instead of JSON:API.
		if ( ! array_key_exists( 'data', $payload ) && array_key_exists( 'results', $payload ) && is_array( $payload['results'] ) ) {
			return $this->normalize_eventyay_rest_speakers_payload( $payload['results'], $settings, $event_slug );
		}

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
	 * Normalize Eventyay REST API paginated speakers ({"count":N,"results":[...]}) into
	 * the internal speaker format.
	 *
	 * The Pretix-based Eventyay API returns speaker resources with fields:
	 * code, name, biography, avatar, answers.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $results    Raw results array from the Eventyay REST API.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array
	 */
	public function normalize_eventyay_rest_speakers_payload( $results, $settings = array(), $event_slug = '' ) {
		$speakers = array();
		$sessions = array();

		if ( ! is_array( $results ) ) {
			return array(
				'speakers'      => array(),
				'session_count' => 0,
			);
		}

		if ( empty( $settings['base_url'] ) ) {
			$settings['base_url'] = 'https://api.eventyay.com';
		}
		if ( empty( $settings['organizer_slug'] ) ) {
			$settings['organizer_slug'] = '';
		}

		foreach ( $results as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item    = $this->normalize_eventyay_api_resource( $item );
			$speaker = $this->normalize_eventyay_submission_speaker( $item, $settings, $event_slug );
			if ( empty( $speaker['name'] ) ) {
				continue;
			}

			$speaker_sessions = $this->eventyay_list_value( $this->eventyay_first_present_raw( $item, array( 'submissions', 'sessions', 'talks' ) ) );
			if ( empty( $speaker_sessions ) ) {
				$this->merge_eventyay_speaker( $speakers, $speaker, array() );
				continue;
			}

			foreach ( $speaker_sessions as $session_resource ) {
				if ( ! is_array( $session_resource ) ) {
					continue;
				}

				$session                    = $this->normalize_eventyay_submission_session( $this->normalize_eventyay_api_resource( $session_resource ) );
				$session['speakers']        = array_values( array_unique( array( $speaker['name'] ) ) );
				$speaker['category']        = empty( $speaker['category'] ) && ! empty( $session['track'] ) ? $session['track'] : $speaker['category'];
				$sessions[ $session['id'] ] = $session;
				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}
		}

		return array(
			'speakers'      => array_values( $speakers ),
			'session_count' => count( $sessions ),
		);
	}

	/**
	 * Transform expanded slots schedule payload into the standard speakers list format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slots Raw slots array from the Eventyay slots API.
	 * @return array Reconstructed speakers payload in Pretix REST format.
	 */
	public function transform_slots_to_speakers_payload( $slots ) {
		$speakers_map = array();

		if ( ! is_array( $slots ) ) {
			return array(
				'count'    => 0,
				'next'     => null,
				'previous' => null,
				'results'  => array(),
			);
		}

		foreach ( $slots as $slot ) {
			if ( empty( $slot['submission'] ) || ! is_array( $slot['submission'] ) ) {
				continue;
			}

			$submission    = $slot['submission'];
			$speakers      = isset( $submission['speakers'] ) ? $submission['speakers'] : array();
			$submission_id = isset( $submission['code'] ) ? $submission['code'] : ( isset( $submission['id'] ) ? $submission['id'] : '' );

			if ( empty( $submission_id ) ) {
				continue;
			}

			// Reconstruct slot resource to nest inside submission.
			$embedded_slot = array(
				'id'       => isset( $slot['id'] ) ? $slot['id'] : '',
				'start'    => isset( $slot['start'] ) ? $slot['start'] : '',
				'end'      => isset( $slot['end'] ) ? $slot['end'] : '',
				'duration' => isset( $slot['duration'] ) ? $slot['duration'] : 0,
			);

			if ( ! empty( $slot['room'] ) ) {
				$embedded_slot['room'] = $slot['room'];
			}

			$submission['slots'] = array( $embedded_slot );
			$submission['slot']  = $embedded_slot;

			foreach ( $speakers as $spk ) {
				if ( ! is_array( $spk ) || empty( $spk['code'] ) ) {
					continue;
				}

				$code = sanitize_text_field( (string) $spk['code'] );

				if ( ! isset( $speakers_map[ $code ] ) ) {
					$speakers_map[ $code ] = array(
						'code'           => $code,
						'name'           => isset( $spk['fullname'] ) ? $spk['fullname'] : ( isset( $spk['name'] ) ? $spk['name'] : '' ),
						'biography'      => isset( $spk['biography'] ) ? $spk['biography'] : '',
						'avatar'         => isset( $spk['avatar_url'] ) ? $spk['avatar_url'] : ( isset( $spk['avatar'] ) ? $spk['avatar'] : '' ),
						'position'       => isset( $spk['position'] ) ? $spk['position'] : '',
						'organization'   => isset( $spk['organization'] ) ? $spk['organization'] : '',
						'category'       => isset( $spk['category'] ) ? $spk['category'] : '',
						'linkedin'       => isset( $spk['linkedin'] ) ? $spk['linkedin'] : '',
						'twitter'        => isset( $spk['twitter'] ) ? $spk['twitter'] : '',
						'github'         => isset( $spk['github'] ) ? $spk['github'] : '',
						'website'        => isset( $spk['website'] ) ? $spk['website'] : '',
						'featured'       => isset( $spk['featured'] ) ? (bool) $spk['featured'] : false,
						'featured_order' => isset( $spk['featured_order'] ) ? (int) $spk['featured_order'] : 0,
						'submissions'    => array(),
					);
				}

				$speakers_map[ $code ]['submissions'][] = $submission;
			}
		}

		return array(
			'count'    => count( $speakers_map ),
			'next'     => null,
			'previous' => null,
			'results'  => array_values( $speakers_map ),
		);
	}

	/**
	 * Normalize a single Eventyay submission speaker resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker_resource Eventyay speaker resource.
	 * @param array  $settings         Import settings.
	 * @param string $event_slug       Eventyay event slug.
	 * @return array
	 */
	public function normalize_eventyay_submission_speaker( $speaker_resource, $settings, $event_slug ) {
		$speaker_resource = $this->normalize_eventyay_api_resource( $speaker_resource );
		$source_id        = $this->eventyay_resource_identifier( $speaker_resource );
		$name             = $this->eventyay_first_present_text( $speaker_resource, array( 'name', 'fullname', 'full_name', 'full-name', 'public_name', 'public-name', 'display_name', 'display-name' ) );

		if ( empty( $name ) ) {
			$name = trim(
				$this->eventyay_text_value( isset( $speaker_resource['first_name'] ) ? $speaker_resource['first_name'] : '' ) . ' ' .
				$this->eventyay_text_value( isset( $speaker_resource['last_name'] ) ? $speaker_resource['last_name'] : '' )
			);
		}

		$organizer_slug      = ! empty( $settings['organizer_slug'] ) ? $settings['organizer_slug'] : '';
		$eventyay_speaker_id = implode(
			':',
			array_filter(
				array(
					$organizer_slug,
					$event_slug,
					$source_id ? $source_id : sanitize_title( $name ),
				)
			)
		);

		$position     = $this->eventyay_first_present_text( $speaker_resource, array( 'position', 'job_title', 'job-title', 'title', 'role', 'speaking_experience', 'speaking-experience' ) );
		$organization = $this->eventyay_first_present_text( $speaker_resource, array( 'organization', 'organisation', 'company', 'affiliation' ) );
		$category     = $this->eventyay_first_present_text( $speaker_resource, array( 'category', 'track' ) );

		$avatar_raw   = $this->eventyay_first_present_raw( $speaker_resource, array( 'avatar', 'avatar_url', 'avatar-url', 'avatar_url_original', 'avatar-url-original', 'image', 'image_url', 'image-url', 'photo', 'photo_url', 'photo-url' ) );
		$linkedin_raw = $this->eventyay_first_present_raw( $speaker_resource, array( 'linkedin', 'linkedin_url', 'linkedin-url' ) );
		$twitter_raw  = $this->eventyay_first_present_raw( $speaker_resource, array( 'twitter', 'twitter_url', 'twitter-url', 'x_url' ) );
		$github_raw   = $this->eventyay_first_present_raw( $speaker_resource, array( 'github', 'github_url', 'github-url' ) );
		$website_raw  = $this->eventyay_first_present_raw( $speaker_resource, array( 'website', 'website_url', 'website-url', 'homepage', 'homepage_url', 'homepage-url', 'url' ) );

		return array(
			'id'                  => 'eventyay-' . sanitize_key( $eventyay_speaker_id ),
			'eventyay_speaker_id' => sanitize_text_field( $eventyay_speaker_id ),
			'name'                => sanitize_text_field( $name ),
			'title'               => sanitize_text_field( $position ? $position : $organization ),
			'position'            => sanitize_text_field( $position ),
			'organization'        => sanitize_text_field( $organization ),
			'category'            => sanitize_text_field( $category ),
			'image'               => $this->eventyay_url_value( $avatar_raw, $settings['base_url'] ),
			'bio'                 => $this->eventyay_first_present_rich_text( $speaker_resource, array( 'biography', 'bio', 'description', 'abstract', 'short_biography', 'short-biography', 'long_biography', 'long-biography' ) ),
			'social'              => array(
				'linkedin' => $this->eventyay_url_value( $linkedin_raw, $settings['base_url'] ),
				'twitter'  => $this->eventyay_url_value( $twitter_raw, $settings['base_url'] ),
				'github'   => $this->eventyay_url_value( $github_raw, $settings['base_url'] ),
				'website'  => $this->eventyay_url_value( $website_raw, $settings['base_url'] ),
			),
			'featured'            => $this->eventyay_speaker_is_featured( $speaker_resource, $category ),
			'featured_order'      => $this->eventyay_speaker_featured_order( $speaker_resource ),
			'sessions'            => array(),
			'source'              => 'eventyay',
		);
	}

	/**
	 * Get the room name from a schedule slot resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $slot Eventyay slot resource.
	 * @return string
	 */
	public function eventyay_slot_room_name( $slot ) {
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
	 * Extract the first scheduled slot from a submission resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	public function eventyay_first_slot( $submission ) {
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
	 * Extract a submission abstract from likely Eventyay fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return string
	 */
	public function eventyay_submission_abstract( $submission ) {
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
	 * Normalize an Eventyay submission resource into the internal speaker session format.
	 *
	 * @since 1.0.0
	 *
	 * @param array $submission Eventyay submission resource.
	 * @return array
	 */
	public function normalize_eventyay_submission_session( $submission ) {
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

		$title = $this->eventyay_text_value( isset( $submission['title'] ) ? $submission['title'] : '' );

		return array(
			'id'        => $source_id ? 'eventyay-submission-' . sanitize_key( $source_id ) : 'eventyay-submission-' . sanitize_title( $title ),
			'title'     => $title,
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
