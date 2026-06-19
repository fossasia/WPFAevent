<?php
/**
 * Eventyay JSONAPI Parser.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses Eventyay payload structures.
 */
class Wpfaevent_JSONAPI_Parser {

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
	 * Normalize an Eventyay event resource into a flat field map.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return array
	 */
	public function normalize_eventyay_event_resource( $event ) {
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
	 * Extract Eventyay event resources from REST or JSON:API-shaped payloads.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload API payload.
	 * @return array
	 */
	public function extract_eventyay_event_resources( $payload ) {
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
	 * Determine whether a list event should be hydrated through its detail endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Normalized Eventyay event resource.
	 * @return bool
	 */
	public function eventyay_event_resource_needs_detail( $event ) {
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
	public function merge_eventyay_event_resource( $base, $detail ) {
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
	 * Determine whether an Eventyay value is meaningfully populated.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw Eventyay value.
	 * @return bool
	 */
	public function eventyay_value_is_non_empty( $value ) {
		if ( is_scalar( $value ) ) {
			return '' !== trim( (string) $value );
		}

		return is_array( $value ) && ! empty( $value );
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
	public function normalize_eventyay_sponsor_resources( $resources, $settings ) {
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
	public function normalize_eventyay_sponsor_resource( $sponsor_resource, $settings ) {
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
	 * Normalize Eventyay exhibitor resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Eventyay exhibitor resources.
	 * @param array $settings  Import settings.
	 * @return array
	 */
	public function normalize_eventyay_exhibitor_resources( $resources, $settings ) {
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
	public function normalize_eventyay_exhibitor_resource( $exhibitor_resource, $settings ) {
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
	 * Normalize Eventyay submissions into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $submissions Eventyay submission resources.
	 * @param array  $settings    Import settings.
	 * @param string $event_slug  Eventyay event slug.
	 * @return array
	 */
	public function normalize_eventyay_submissions_payload( $submissions, $settings, $event_slug ) {
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
			'sessions'      => array_values( $sessions ),
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
	public function normalize_eventyay_speakers_payload( $speaker_resources, $settings, $event_slug ) {
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
	public function normalize_eventyay_slots_payload( $slot_resources, $settings, $event_slug ) {
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
	public function merge_eventyay_program_payloads( $base, $extra ) {
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
	 * Normalize a newer Eventyay submission as a speaker session.
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
	public function normalize_eventyay_slot_session( $slot, $submission ) {
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
	public function eventyay_session_has_content( $session ) {
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
	 * Normalize a newer Eventyay speaker resource.
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
	 * Extract the first scheduled slot from a submission.
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
	 * Normalize an Eventyay resource into a flat field map.
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
	 * Normalize JSON:API relationship data when included directly in Eventyay payloads.
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
	 * @return mixed
	 */
	public function eventyay_first_present_raw( $eventyay_resource, $keys ) {
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
	 * Determine whether an Eventyay event has a fetched settings payload.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return bool
	 */
	public function eventyay_event_has_settings_payload( $event ) {
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
	public function eventyay_event_first_present_raw( $event, $keys, $include_settings = false ) {
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
	 * Get a useful string from an Eventyay scalar or multi-language value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	public function eventyay_text_value( $value ) {
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
	public function eventyay_rich_text_value( $value ) {
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
	 * Extract event description content from an Eventyay event resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_description( $event ) {
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
	 * Build the public Eventyay URL for an imported event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return string
	 */
	public function eventyay_public_event_url( $event, $settings, $event_slug ) {
		$url = $this->eventyay_url_value(
			$this->eventyay_event_first_present_raw(
				$event,
				array(
					'url',
					'frontend_url',
					'frontend-url',
					'public_url',
					'public-url',
					'web_url',
					'web-url',
					'absolute_url',
					'absolute-url',
					'event_url',
					'event-url',
					'registration_url',
					'registration-url',
				),
				true
			),
			$settings['base_url']
		);

		if ( $url ) {
			return $url;
		}

		$url = trailingslashit( $settings['base_url'] ) . rawurlencode( $settings['organizer_slug'] ) . '/' . rawurlencode( $event_slug ) . '/';

		return esc_url_raw(
			apply_filters(
				'wpfaevent_eventyay_import_event_url',
				$url,
				$event,
				$settings
			)
		);
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
	 * @param array $included         Indexed included resources.
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
	 * Get the Eventyay slug from a normalized event resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_slug( $event ) {
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
	public function eventyay_event_title( $event ) {
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
	public function eventyay_event_datetime( $event, $type ) {
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
	public function eventyay_event_timezone( $event ) {
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
	public function eventyay_event_location( $event ) {
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
	public function eventyay_event_languages( $event ) {
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
	public function eventyay_event_colors( $event ) {
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
	 * Group imported sponsors by Eventyay type or level.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sponsors Imported sponsors.
	 * @return array
	 */
	public function group_eventyay_sponsors( $sponsors ) {
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
	public function is_eventyay_sponsor_group( $group ) {
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
	 * Merge imported Eventyay sponsors with manually maintained dashboard groups.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported sponsors.
	 * @param array $existing Existing dashboard sponsor groups.
	 * @return array
	 */
	public function merge_eventyay_sponsor_groups( $imported, $existing ) {
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
	 * Merge imported Eventyay flat records with manually maintained records.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported records.
	 * @param array $existing Existing records.
	 * @return array
	 */
	public function merge_eventyay_flat_records( $imported, $existing ) {
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
	 * Preserve dashboard-only speaker state across Eventyay syncs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported Eventyay speakers.
	 * @param array $existing Existing dashboard speakers.
	 * @return array
	 */
	public function merge_dashboard_speaker_state( $imported, $existing ) {
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
	public function get_dashboard_speaker_state_keys( $speaker ) {
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
	public function is_eventyay_dashboard_speaker( $speaker ) {
		if ( isset( $speaker['source'] ) && 'eventyay' === $speaker['source'] ) {
			return true;
		}

		return ! empty( $speaker['id'] ) && 0 === strpos( (string) $speaker['id'], 'eventyay-' );
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
}
