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

		if ( ! empty( $lookup_map[ $eventyay_speaker_id ] ) ) {
			$post_id = absint( $lookup_map[ $eventyay_speaker_id ] );
			if ( $post_id && 'wpfa_speaker' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		$this->prime_eventyay_speaker_lookup_map();
		$lookup_map = $this->get_eventyay_speaker_lookup_map();

		return ! empty( $lookup_map[ $eventyay_speaker_id ] ) ? absint( $lookup_map[ $eventyay_speaker_id ] ) : 0;
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

		$lookup_map                         = $this->get_eventyay_speaker_lookup_map();
		$lookup_map[ $eventyay_speaker_id ] = $post_id;
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

			$lookup_map[ $eventyay_speaker_id ] = $speaker_id;
		}

		update_option( 'wpfaevent_eventyay_speaker_lookup', $lookup_map, false );
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
