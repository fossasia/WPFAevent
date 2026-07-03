<?php
/**
 * Event-Speaker relationship manager class.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage bidirectional relationships and relational database queries between Events and Speakers.
 */
class Wpfaevent_Event_Speaker_Relation_Manager {

	/**
	 * Custom post type key for Speaker.
	 *
	 * @var string
	 */
	private static $speaker_post_type = 'wpfa_speaker';

	/**
	 * Custom post type key for Event.
	 *
	 * @var string
	 */
	private static $event_post_type = 'wpfa_event';

	/**
	 * Get normalized speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_event_speaker_ids( $event_id ) {
		return self::sanitize_post_id_list( get_post_meta( $event_id, 'wpfa_event_speakers', true ) );
	}

	/**
	 * Get normalized featured speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_event_featured_speaker_ids( $event_id ) {
		return self::sanitize_post_id_list( get_post_meta( $event_id, 'wpfa_event_featured_speakers', true ) );
	}

	/**
	 * Resolve featured speaker IDs from event meta, dashboard JSON, and speaker categories.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id           Event post ID.
	 * @param array<int>        $speaker_ids        Linked speaker post IDs.
	 * @param array<int, array> $dashboard_speakers Imported dashboard speaker rows.
	 * @return array<int>
	 */
	public static function resolve_event_featured_speaker_ids( $event_id, $speaker_ids, $dashboard_speakers = array() ) {
		$event_id    = absint( $event_id );
		$speaker_ids = self::sanitize_post_id_list( $speaker_ids );
		$featured    = array_values( array_intersect( self::get_event_featured_speaker_ids( $event_id ), $speaker_ids ) );

		if ( is_array( $dashboard_speakers ) && ! empty( $dashboard_speakers ) ) {
			$eventyay_map = array();
			$name_map     = array();

			foreach ( $speaker_ids as $speaker_id ) {
				$eventyay_id = sanitize_text_field( get_post_meta( $speaker_id, '_wpfa_eventyay_speaker_id', true ) );

				if ( '' !== $eventyay_id ) {
					$eventyay_map[ $eventyay_id ] = $speaker_id;
				}

				$name_key = sanitize_title( get_the_title( $speaker_id ) );

				if ( '' !== $name_key ) {
					$name_map[ $name_key ] = $speaker_id;
				}
			}

			foreach ( $dashboard_speakers as $dashboard_speaker ) {
				if ( ! is_array( $dashboard_speaker ) || empty( $dashboard_speaker['featured'] ) ) {
					continue;
				}

				$matched_id = 0;

				if ( ! empty( $dashboard_speaker['eventyay_speaker_id'] ) && isset( $eventyay_map[ $dashboard_speaker['eventyay_speaker_id'] ] ) ) {
					$matched_id = (int) $eventyay_map[ $dashboard_speaker['eventyay_speaker_id'] ];
				} elseif ( ! empty( $dashboard_speaker['name'] ) ) {
					$name_key = sanitize_title( $dashboard_speaker['name'] );

					if ( isset( $name_map[ $name_key ] ) ) {
						$matched_id = (int) $name_map[ $name_key ];
					}
				}

				if ( $matched_id && ! in_array( $matched_id, $featured, true ) ) {
					$featured[] = $matched_id;
				}
			}
		}

		if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
			foreach ( $speaker_ids as $speaker_id ) {
				if ( in_array( $speaker_id, $featured, true ) ) {
					continue;
				}

				$terms = get_the_terms( $speaker_id, 'wpfa_speaker_category' );

				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					if ( preg_match( '/\b(featured|keynote|plenary|highlight)\b/i', $term->name ) ) {
						$featured[] = $speaker_id;
						break;
					}
				}
			}
		}

		$featured = self::sanitize_post_id_list( $featured );
		$featured = array_values( array_intersect( $featured, $speaker_ids ) );

		if ( empty( $featured ) && ! empty( $speaker_ids ) ) {
			$auto_limit = absint(
				apply_filters(
					'wpfa_event_auto_featured_speaker_limit',
					1,
					$event_id,
					$speaker_ids,
					$dashboard_speakers
				)
			);

			if ( $auto_limit > 0 ) {
				$featured = array_slice( $speaker_ids, 0, min( $auto_limit, count( $speaker_ids ) ) );
			}
		}

		return apply_filters( 'wpfa_event_featured_speaker_ids', $featured, $event_id, $speaker_ids, $dashboard_speakers );
	}

	/**
	 * Get speakers assigned to one event from event and reverse speaker meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_admin_event_speaker_ids( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id || get_post_type( $event_id ) !== self::$event_post_type ) {
			return array();
		}

		return self::sanitize_post_id_list(
			array_merge(
				self::get_event_speaker_ids( $event_id ),
				self::get_speakers_linked_to_event( $event_id ),
				self::get_eventyay_speakers_linked_to_event( $event_id )
			)
		);
	}

	/**
	 * Get every speaker owned by any event.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public static function get_all_event_owned_speaker_ids() {
		$speaker_ids = array();
		$event_ids   = get_posts(
			array(
				'post_type'      => self::$event_post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $event_ids as $event_id ) {
			$speaker_ids = array_merge( $speaker_ids, self::get_event_speaker_ids( $event_id ) );
		}

		return self::sanitize_post_id_list( array_merge( $speaker_ids, self::get_all_speakers_linked_to_events() ) );
	}

	/**
	 * Find speakers whose speaker-side event meta includes an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_speakers_linked_to_event( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array();
		}

		$speaker_ids = get_posts(
			array(
				'post_type'      => self::$speaker_post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored in post meta.
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => 'i:' . $event_id . ';',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => '"' . $event_id . '"',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => (string) $event_id,
						'compare' => '=',
					),
				),
			)
		);

		return self::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Find imported Eventyay speakers when relationship meta is missing.
	 *
	 * Older imports can have Eventyay speaker IDs prefixed by the Eventyay event
	 * slug without the newer bidirectional relationship meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_eventyay_speakers_linked_to_event( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array();
		}

		$event_slugs = self::get_eventyay_event_slugs( $event_id );

		if ( empty( $event_slugs ) ) {
			return array();
		}

		$meta_query = array( 'relation' => 'OR' );

		foreach ( $event_slugs as $event_slug ) {
			$meta_query[] = array(
				'key'     => '_wpfa_eventyay_speaker_id',
				'value'   => $event_slug . ':',
				'compare' => 'LIKE',
			);
		}

		$speaker_ids = get_posts(
			array(
				'post_type'      => self::$speaker_post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Eventyay speaker links are stored in post meta.
				'meta_query'     => $meta_query,
			)
		);

		return self::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Find all speakers with any event relationship.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public static function get_all_speakers_linked_to_events() {
		$speaker_ids = get_posts(
			array(
				'post_type'      => self::$speaker_post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Speaker ownership is stored in post meta.
				'meta_key'       => 'wpfa_speaker_events',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_compare -- Speaker ownership is stored in post meta.
				'meta_compare'   => 'EXISTS',
			)
		);

		return self::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Get possible Eventyay event slugs for an event post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string>
	 */
	private static function get_eventyay_event_slugs( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array();
		}

		$event_slugs = array(
			get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ),
			get_post_meta( $event_id, '_eventyay_event_slug', true ),
			get_post_field( 'post_name', $event_id ),
		);

		$event_slugs = array_map( 'sanitize_title', array_filter( array_map( 'strval', $event_slugs ) ) );

		return array_values( array_unique( $event_slugs ) );
	}

	/**
	 * Find events whose event-side speaker meta includes a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $speaker_id  Speaker post ID.
	 * @param string|array $post_status Event post status filter.
	 * @return array<int>
	 */
	public static function get_events_referencing_speaker( $speaker_id, $post_status = 'any' ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id ) {
			return array();
		}

		$event_ids = get_posts(
			array(
				'post_type'      => self::$event_post_type,
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored in post meta.
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'wpfa_event_speakers',
						'value'   => 'i:' . $speaker_id . ';',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_event_speakers',
						'value'   => '"' . $speaker_id . '"',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_event_speakers',
						'value'   => (string) $speaker_id,
						'compare' => '=',
					),
				),
			)
		);

		return self::sanitize_post_id_list( $event_ids );
	}

	/**
	 * Get normalized event IDs linked to a speaker from both relationship sides.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $speaker_id  Speaker post ID.
	 * @param string|array $post_status Event post status filter.
	 * @return array<int>
	 */
	public static function get_events_linked_to_speaker( $speaker_id, $post_status = 'publish' ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id || get_post_type( $speaker_id ) !== self::$speaker_post_type ) {
			return array();
		}

		$event_ids = self::sanitize_post_id_list(
			array_merge(
				self::get_speaker_event_ids( $speaker_id ),
				self::get_events_referencing_speaker( $speaker_id, $post_status )
			)
		);

		if ( empty( $event_ids ) || empty( $post_status ) || 'any' === $post_status ) {
			return $event_ids;
		}

		$event_ids = get_posts(
			array(
				'post_type'      => self::$event_post_type,
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__in'       => $event_ids,
				'orderby'        => 'post__in',
				'no_found_rows'  => true,
			)
		);

		return self::sanitize_post_id_list( $event_ids );
	}

	/**
	 * Add an event ID to a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $speaker_id       Speaker post ID.
	 * @param int  $event_id         Event post ID.
	 * @param bool $check_capability Whether to require edit access to the speaker.
	 */
	public static function add_event_to_speaker( $speaker_id, $event_id, $check_capability = true ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$speaker_post_type ) {
			return;
		}

		if ( $check_capability && ! current_user_can( 'edit_post', $speaker_id ) ) {
			return;
		}

		$event_ids   = self::get_speaker_event_ids( $speaker_id );
		$event_ids[] = $event_id;

		update_post_meta( $speaker_id, 'wpfa_speaker_events', self::sanitize_post_id_list( $event_ids ) );
	}

	/**
	 * Remove an event ID from a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $speaker_id       Speaker post ID.
	 * @param int  $event_id         Event post ID.
	 * @param bool $check_capability Whether to require edit access to the speaker.
	 */
	public static function remove_event_from_speaker( $speaker_id, $event_id, $check_capability = true ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$speaker_post_type ) {
			return;
		}

		if ( $check_capability && ! current_user_can( 'edit_post', $speaker_id ) ) {
			return;
		}

		$event_ids = array_diff( self::get_speaker_event_ids( $speaker_id ), array( $event_id ) );
		$event_ids = self::sanitize_post_id_list( $event_ids );

		if ( empty( $event_ids ) ) {
			delete_post_meta( $speaker_id, 'wpfa_speaker_events' );

			return;
		}

		update_post_meta( $speaker_id, 'wpfa_speaker_events', $event_ids );
	}

	/**
	 * Get normalized event IDs assigned to a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int>
	 */
	public static function get_speaker_event_ids( $speaker_id ) {
		return self::sanitize_post_id_list( get_post_meta( $speaker_id, 'wpfa_speaker_events', true ) );
	}

	/**
	 * Sync speaker-side event relationship meta after an event is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $event_id          Event post ID.
	 * @param array<int> $previous_speakers Speaker IDs before save.
	 * @param array<int> $current_speakers  Speaker IDs after save.
	 */
	public static function sync_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
		$event_id          = absint( $event_id );
		$previous_speakers = self::sanitize_post_id_list(
			array_merge(
				self::sanitize_post_id_list( $previous_speakers ),
				self::get_speakers_linked_to_event( $event_id )
			)
		);
		$current_speakers  = self::sanitize_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		foreach ( array_diff( $previous_speakers, $current_speakers ) as $speaker_id ) {
			self::remove_event_from_speaker( $speaker_id, $event_id, false );
		}

		foreach ( $current_speakers as $speaker_id ) {
			self::add_event_to_speaker( $speaker_id, $event_id, false );
		}
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int>
	 */
	public static function sanitize_post_id_list( $post_ids ) {
		if ( is_array( $post_ids ) ) {
			$normalized_post_ids = $post_ids;
		} elseif ( is_scalar( $post_ids ) ) {
			if ( is_string( $post_ids ) ) {
				$post_ids = trim( $post_ids );
			}

			if ( '' === $post_ids ) {
				return array();
			}

			$decoded_post_ids = is_string( $post_ids ) ? json_decode( $post_ids, true ) : null;

			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded_post_ids ) ) {
				$normalized_post_ids = $decoded_post_ids;
			} elseif ( JSON_ERROR_NONE === json_last_error() && is_scalar( $decoded_post_ids ) ) {
				$normalized_post_ids = array( $decoded_post_ids );
			} elseif ( is_string( $post_ids ) && false !== strpos( $post_ids, ',' ) ) {
				$normalized_post_ids = array_map( 'trim', explode( ',', $post_ids ) );
			} else {
				$normalized_post_ids = array( $post_ids );
			}
		} else {
			return array();
		}

		$post_ids = array_map( 'absint', $normalized_post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}
}
