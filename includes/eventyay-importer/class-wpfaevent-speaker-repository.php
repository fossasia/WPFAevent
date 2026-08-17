<?php
/**
 * Eventyay Speaker Repository.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages speaker Custom Post Type posts and relationships.
 */
class Wpfaevent_Speaker_Repository {

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Upsert synced speakers into the maintained speaker CPT path.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speakers Imported speakers.
	 * @param int   $event_id Event post ID.
	 * @return array
	 */
	public function sync_eventyay_speaker_posts( $speakers, $event_id ) {
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
	 * Determine whether a speaker post is managed by Eventyay import.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return bool
	 */
	public function is_eventyay_speaker_post( $speaker_id ) {
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
	public function get_eventyay_event_speaker_ids( $event_id ) {
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
	public function get_eventyay_event_featured_speaker_ids( $event_id ) {
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
	public function get_eventyay_speaker_event_ids( $speaker_id ) {
		$event_ids = get_post_meta( $speaker_id, 'wpfa_speaker_events', true );

		return $this->sanitize_eventyay_post_id_list( $event_ids );
	}

	/**
	 * Sanitize, deduplicate, and reindex post IDs for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int>
	 */
	public function sanitize_eventyay_post_id_list( $post_ids ) {
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
	public function sync_eventyay_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
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
	public function add_eventyay_event_to_speaker( $speaker_id, $event_id ) {
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
	public function remove_eventyay_event_from_speaker( $speaker_id, $event_id ) {
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
	 * Create or update one Eventyay speaker post.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $speaker     Speaker data.
	 * @param string $post_status Optional. Post status. Default 'draft'.
	 * @return array|WP_Error
	 */
	public function upsert_eventyay_speaker_post( $speaker, $post_status = 'draft' ) {
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

		$this->reconcile_duplicate_eventyay_speakers( $saved_id, $speaker['eventyay_speaker_id'], $speaker['name'] );
		$this->store_eventyay_speaker_lookup( $speaker['eventyay_speaker_id'], $saved_id );

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
	public function find_eventyay_speaker_post( $eventyay_speaker_id ) {
		$eventyay_speaker_id = sanitize_text_field( $eventyay_speaker_id );
		$lookup_map          = $this->get_eventyay_speaker_lookup_map();

		foreach ( $this->get_eventyay_speaker_lookup_keys( $eventyay_speaker_id ) as $lookup_key ) {
			if ( empty( $lookup_map[ $lookup_key ] ) ) {
				continue;
			}

			$post_id = absint( $lookup_map[ $lookup_key ] );
			if ( $post_id && 'wpfa_speaker' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		$this->prime_eventyay_speaker_lookup_map();
		$lookup_map = $this->get_eventyay_speaker_lookup_map();

		foreach ( $this->get_eventyay_speaker_lookup_keys( $eventyay_speaker_id ) as $lookup_key ) {
			if ( empty( $lookup_map[ $lookup_key ] ) ) {
				continue;
			}

			$post_id = absint( $lookup_map[ $lookup_key ] );
			if ( $post_id && 'wpfa_speaker' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		$compatible_ids = $this->find_compatible_eventyay_speaker_posts( $eventyay_speaker_id );

		return ! empty( $compatible_ids ) ? absint( $compatible_ids[0] ) : 0;
	}

	/**
	 * Get the imported Eventyay speaker lookup map.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	private function get_eventyay_speaker_lookup_map() {
		$lookup_map = get_option( 'wpfaevent_eventyay_speaker_lookup', array() );

		return is_array( $lookup_map ) ? $lookup_map : array();
	}

	/**
	 * Store a lookup entry for an imported Eventyay speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param string $eventyay_speaker_id Eventyay speaker ID.
	 * @param int    $post_id             WordPress post ID.
	 * @return void
	 */
	private function store_eventyay_speaker_lookup( $eventyay_speaker_id, $post_id ) {
		$eventyay_speaker_id = sanitize_text_field( $eventyay_speaker_id );
		$post_id             = absint( $post_id );

		if ( '' === $eventyay_speaker_id || ! $post_id ) {
			return;
		}

		$lookup_map = $this->get_eventyay_speaker_lookup_map();

		foreach ( $this->get_eventyay_speaker_lookup_keys( $eventyay_speaker_id ) as $lookup_key ) {
			$lookup_map[ $lookup_key ] = $post_id;
		}

		update_option( 'wpfaevent_eventyay_speaker_lookup', $lookup_map, false );
	}

	/**
	 * Prime the imported Eventyay speaker lookup map from existing posts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function prime_eventyay_speaker_lookup_map() {
		$speaker_ids = get_posts(
			array(
				'post_type'              => 'wpfa_speaker',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $speaker_ids ) ) {
			return;
		}

		$lookup_map = $this->get_eventyay_speaker_lookup_map();

		foreach ( $speaker_ids as $speaker_id ) {
			$speaker_id          = absint( $speaker_id );
			$eventyay_speaker_id = sanitize_text_field( (string) get_post_meta( $speaker_id, '_wpfa_eventyay_speaker_id', true ) );

			if ( '' === $eventyay_speaker_id ) {
				continue;
			}

			foreach ( $this->get_eventyay_speaker_lookup_keys( $eventyay_speaker_id ) as $lookup_key ) {
				$lookup_map[ $lookup_key ] = $speaker_id;
			}
		}

		update_option( 'wpfaevent_eventyay_speaker_lookup', $lookup_map, false );
	}

	/**
	 * Build exact and compatibility lookup keys for an Eventyay speaker ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $eventyay_speaker_id Eventyay speaker ID.
	 * @return array<string>
	 */
	private function get_eventyay_speaker_lookup_keys( $eventyay_speaker_id ) {
		$eventyay_speaker_id = sanitize_text_field( $eventyay_speaker_id );

		if ( '' === $eventyay_speaker_id ) {
			return array();
		}

		$lookup_keys = array( $eventyay_speaker_id );
		$id_parts    = explode( ':', $eventyay_speaker_id );
		$base_id     = sanitize_text_field( end( $id_parts ) );

		if ( '' !== $base_id ) {
			$lookup_keys[] = $base_id;
		}

		return array_values( array_unique( array_filter( $lookup_keys ) ) );
	}

	/**
	 * Find speaker posts that share the same exact or legacy-compatible Eventyay ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $eventyay_speaker_id Eventyay speaker ID.
	 * @param string $speaker_name        Optional speaker name for filtering.
	 * @return array<int>
	 */
	private function find_compatible_eventyay_speaker_posts( $eventyay_speaker_id, $speaker_name = '' ) {
		$eventyay_speaker_id = sanitize_text_field( $eventyay_speaker_id );
		$speaker_name        = sanitize_text_field( $speaker_name );

		if ( '' === $eventyay_speaker_id ) {
			return array();
		}

		$lookup_keys = $this->get_eventyay_speaker_lookup_keys( $eventyay_speaker_id );
		$base_id     = end( $lookup_keys );
		$meta_query  = array( 'relation' => 'OR' );

		foreach ( $lookup_keys as $lookup_key ) {
			$meta_query[] = array(
				'key'   => '_wpfa_eventyay_speaker_id',
				'value' => $lookup_key,
			);
		}

		if ( $base_id && $base_id !== $eventyay_speaker_id ) {
			$meta_query[] = array(
				'key'     => '_wpfa_eventyay_speaker_id',
				'value'   => ':' . $base_id,
				'compare' => 'LIKE',
			);
		}

		$speaker_ids = get_posts(
			array(
				'post_type'              => 'wpfa_speaker',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Eventyay speaker IDs are stored in post meta for sync idempotency.
				'meta_query'             => $meta_query,
			)
		);

		$speaker_ids = $this->sanitize_eventyay_post_id_list( $speaker_ids );

		if ( '' === $speaker_name ) {
			return $speaker_ids;
		}

		$target_name = sanitize_title( $speaker_name );

		return array_values(
			array_filter(
				$speaker_ids,
				static function ( $speaker_id ) use ( $target_name ) {
					return '' !== $target_name && sanitize_title( get_the_title( $speaker_id ) ) === $target_name;
				}
			)
		);
	}

	/**
	 * Reconcile duplicate Eventyay speaker posts created by legacy/current ID mismatches.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $primary_speaker_id  Speaker post ID to keep.
	 * @param string $eventyay_speaker_id Eventyay speaker ID being synced.
	 * @param string $speaker_name        Speaker name.
	 * @return void
	 */
	private function reconcile_duplicate_eventyay_speakers( $primary_speaker_id, $eventyay_speaker_id, $speaker_name ) {
		$primary_speaker_id = absint( $primary_speaker_id );

		if ( ! $primary_speaker_id ) {
			return;
		}

		$duplicate_ids = array_diff(
			$this->find_compatible_eventyay_speaker_posts( $eventyay_speaker_id, $speaker_name ),
			array( $primary_speaker_id )
		);

		if ( empty( $duplicate_ids ) ) {
			return;
		}

		foreach ( $duplicate_ids as $duplicate_id ) {
			$duplicate_id = absint( $duplicate_id );

			if ( ! $duplicate_id || 'wpfa_speaker' !== get_post_type( $duplicate_id ) ) {
				continue;
			}

			$this->merge_duplicate_speaker_event_links( $primary_speaker_id, $duplicate_id );
			delete_post_meta( $duplicate_id, '_wpfa_eventyay_speaker_id' );
		}
	}

	/**
	 * Move event relationships from a duplicate speaker post to the primary post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $primary_speaker_id   Speaker post ID to keep.
	 * @param int $duplicate_speaker_id Speaker post ID to detach.
	 * @return void
	 */
	private function merge_duplicate_speaker_event_links( $primary_speaker_id, $duplicate_speaker_id ) {
		$primary_speaker_id   = absint( $primary_speaker_id );
		$duplicate_speaker_id = absint( $duplicate_speaker_id );

		if ( ! $primary_speaker_id || ! $duplicate_speaker_id || $primary_speaker_id === $duplicate_speaker_id ) {
			return;
		}

		$primary_event_ids   = $this->get_eventyay_speaker_event_ids( $primary_speaker_id );
		$duplicate_event_ids = $this->get_eventyay_speaker_event_ids( $duplicate_speaker_id );
		$merged_event_ids    = $this->sanitize_eventyay_post_id_list( array_merge( $primary_event_ids, $duplicate_event_ids ) );

		if ( empty( $merged_event_ids ) ) {
			delete_post_meta( $primary_speaker_id, 'wpfa_speaker_events' );
		} else {
			update_post_meta( $primary_speaker_id, 'wpfa_speaker_events', $merged_event_ids );
		}

		foreach ( $duplicate_event_ids as $event_id ) {
			$event_id = absint( $event_id );

			if ( ! $event_id || 'wpfa_event' !== get_post_type( $event_id ) ) {
				continue;
			}

			$speaker_ids  = $this->replace_event_speaker_reference( $this->get_eventyay_event_speaker_ids( $event_id ), $duplicate_speaker_id, $primary_speaker_id );
			$featured_ids = $this->replace_event_speaker_reference( $this->get_eventyay_event_featured_speaker_ids( $event_id ), $duplicate_speaker_id, $primary_speaker_id );

			if ( empty( $speaker_ids ) ) {
				delete_post_meta( $event_id, 'wpfa_event_speakers' );
			} else {
				update_post_meta( $event_id, 'wpfa_event_speakers', $speaker_ids );
			}

			if ( empty( $featured_ids ) ) {
				delete_post_meta( $event_id, 'wpfa_event_featured_speakers' );
			} else {
				update_post_meta( $event_id, 'wpfa_event_featured_speakers', $featured_ids );
			}
		}

		delete_post_meta( $duplicate_speaker_id, 'wpfa_speaker_events' );
	}

	/**
	 * Replace one speaker ID with another inside an event speaker list.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int> $speaker_ids          Event speaker IDs.
	 * @param int        $duplicate_speaker_id Duplicate speaker post ID.
	 * @param int        $primary_speaker_id   Speaker post ID to keep.
	 * @return array<int>
	 */
	private function replace_event_speaker_reference( $speaker_ids, $duplicate_speaker_id, $primary_speaker_id ) {
		$duplicate_speaker_id = absint( $duplicate_speaker_id );
		$primary_speaker_id   = absint( $primary_speaker_id );

		if ( empty( $speaker_ids ) ) {
			return array();
		}

		$updated_ids = array_map(
			static function ( $speaker_id ) use ( $duplicate_speaker_id, $primary_speaker_id ) {
				return absint( $speaker_id ) === $duplicate_speaker_id ? $primary_speaker_id : absint( $speaker_id );
			},
			(array) $speaker_ids
		);

		return $this->sanitize_eventyay_post_id_list( $updated_ids );
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
	public function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
