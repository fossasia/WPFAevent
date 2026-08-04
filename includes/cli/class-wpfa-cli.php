<?php
/**
 * WP-CLI Seeder for WPFA Event.
 *
 * @package Wpfaevent
 *
 * Usage:
 *   wp wpfa seed --minimal
 *   wp wpfa seed --from-json=wp-content/plugins/WPFAevent/assets/demo/minimal.json
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI seeding helpers for WPFA Event demo data.
 */
class WPFA_CLI {

	/**
	 * Seed command entry point.
	 *
	 * ## OPTIONS
	 *
	 * [--minimal]
	 * : Inserts 2 speakers and 1 event using placeholders.
	 *
	 * [--from-json=<path>]
	 * : Path to a JSON file with seed data.
	 *
	 * ## EXAMPLES
	 *   wp wpfa seed --minimal
	 *   wp wpfa seed --from-json=wp-content/plugins/WPFAevent/assets/demo/minimal.json
	 *
	 * @when after_wp_load
	 *
	 * @param array $args Positional command arguments.
	 * @param array $assoc_args Associative command arguments.
	 */
	public static function seed( $args, $assoc_args ) {
		if ( isset( $assoc_args['from-json'] ) ) {
			self::seed_from_json( $assoc_args['from-json'] );
			return;
		}

		if ( isset( $assoc_args['minimal'] ) ) {
			self::seed_minimal();
			return;
		}

		WP_CLI::error( 'No option provided. Use --minimal or --from-json=<path>.' );
	}

	/**
	 * Minimal hardcoded seed (2 speakers, 1 event).
	 */
	private static function seed_minimal() {
		$placeholder = 'https://via.placeholder.com/300x300.png?text=Speaker';

		$speakers = array(
			array(
				'post_title'   => 'Alex Example',
				'post_content' => 'Open source contributor and community speaker.',
				'meta'         => array(
					'wpfa_speaker_organization' => 'FOSSASIA',
					'wpfa_speaker_position'     => 'Developer Advocate',
					'wpfa_speaker_headshot_url' => $placeholder,
				),
				'slug'         => 'alex-example',
			),
			array(
				'post_title'   => 'Bao Nguyen',
				'post_content' => 'Engineer focusing on event platforms and accessibility.',
				'meta'         => array(
					'wpfa_speaker_organization' => 'Eventyay',
					'wpfa_speaker_position'     => 'Software Engineer',
					'wpfa_speaker_headshot_url' => $placeholder,
				),
				'slug'         => 'bao-nguyen',
			),
		);

		$event = array(
			'post_title'   => 'FOSSASIA Community Meetup',
			'post_content' => 'A casual meetup to discuss the roadmap and OSS collaboration.',
			'meta'         => array(
				'wpfa_event_start_date' => gmdate( 'Y-m-d', strtotime( '+30 days' ) ),
				'wpfa_event_end_date'   => gmdate( 'Y-m-d', strtotime( '+31 days' ) ),
				'wpfa_event_location'   => 'Online',
				'wpfa_event_url'        => 'https://eventyay.com/',
			),
			'slug'         => 'fossasia-community-meetup',
		);

		// Insert speakers (idempotent by slug).
		$speaker_ids = array();
		foreach ( $speakers as $s ) {
			$speaker_ids[] = self::upsert_post_by_slug(
				'wpfa_speaker',
				$s['slug'],
				array(
					'post_title'   => $s['post_title'],
					'post_content' => $s['post_content'],
					'post_status'  => 'publish',
					'post_type'    => 'wpfa_speaker',
				),
				$s['meta']
			);
		}

		// Insert event (idempotent by slug).
		$event_id = self::upsert_post_by_slug(
			'wpfa_event',
			$event['slug'],
			array(
				'post_title'   => $event['post_title'],
				'post_content' => $event['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'wpfa_event',
			),
			$event['meta']
		);

		// Relate event ↔ speakers (store both sides; skip if already there).
		self::sync_relationships( $event_id, $speaker_ids );

		WP_CLI::success( 'Seeded minimal data: 2 speakers, 1 event.' );
	}

	/**
	 * Seed from JSON file.
	 * JSON format example:
	 * {
	 *   "speakers": [{ "title":"...", "content":"...", "slug":"...", "org":"...", "position":"...", "photo":"..." }],
	 *   "events":   [{ "title":"...", "content":"...", "slug":"...", "start_date":"YYYY-MM-DD", "end_date":"YYYY-MM-DD", "location":"...", "url":"...", "speakers":["slug-1","slug-2"] }]
	 * }
	 *
	 * @param string $path Path to the JSON seed file.
	 */
	private static function seed_from_json( $path ) {
		if ( ! file_exists( $path ) ) {
			WP_CLI::error( "JSON file not found: {$path}" );
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( empty( $wp_filesystem ) ) {
			WP_CLI::error( 'Unable to initialize WordPress filesystem.' );
		}

		$json = $wp_filesystem->get_contents( $path );
		if ( false === $json ) {
			WP_CLI::error( "Unable to read JSON file: {$path}" );
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON structure.' );
		}

		// Insert speakers first.
		$slug_to_id = array();
		if ( ! empty( $data['speakers'] ) && is_array( $data['speakers'] ) ) {
			foreach ( $data['speakers'] as $s ) {
				$slug    = sanitize_title( $s['slug'] ?? $s['title'] ?? wp_generate_uuid4() );
				$title   = sanitize_text_field( $s['title'] ?? 'Speaker' );
				$content = wp_kses_post( $s['content'] ?? '' );

				$meta = array(
					'wpfa_speaker_organization' => isset( $s['org'] ) ? sanitize_text_field( $s['org'] ) : '',
					'wpfa_speaker_position'     => isset( $s['position'] ) ? sanitize_text_field( $s['position'] ) : '',
					'wpfa_speaker_headshot_url' => isset( $s['photo'] ) ? esc_url_raw( $s['photo'] ) : 'https://via.placeholder.com/300x300.png?text=Speaker',
				);

				$id                  = self::upsert_post_by_slug(
					'wpfa_speaker',
					$slug,
					array(
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'publish',
						'post_type'    => 'wpfa_speaker',
					),
					$meta
				);
				$slug_to_id[ $slug ] = $id;
			}
		}

		// Insert events and relate to speakers from slugs.
		if ( ! empty( $data['events'] ) && is_array( $data['events'] ) ) {
			foreach ( $data['events'] as $e ) {
				$slug    = sanitize_title( $e['slug'] ?? $e['title'] ?? wp_generate_uuid4() );
				$title   = sanitize_text_field( $e['title'] ?? 'Event' );
				$content = wp_kses_post( $e['content'] ?? '' );

				$meta = array(
					'wpfa_event_start_date' => isset( $e['start_date'] ) ? sanitize_text_field( $e['start_date'] ) : '',
					'wpfa_event_end_date'   => isset( $e['end_date'] ) ? sanitize_text_field( $e['end_date'] ) : '',
					'wpfa_event_location'   => isset( $e['location'] ) ? sanitize_text_field( $e['location'] ) : '',
					'wpfa_event_url'        => isset( $e['url'] ) ? esc_url_raw( $e['url'] ) : '',
				);

				$event_id = self::upsert_post_by_slug(
					'wpfa_event',
					$slug,
					array(
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'publish',
						'post_type'    => 'wpfa_event',
					),
					$meta
				);

				$event_speaker_slugs = ! empty( $e['speakers'] ) && is_array( $e['speakers'] ) ? $e['speakers'] : array();
				$speaker_ids         = array_values( array_intersect_key( $slug_to_id, array_flip( $event_speaker_slugs ) ) );

				self::sync_relationships( $event_id, $speaker_ids );
			}
		}

		WP_CLI::success( 'Seeded data from JSON.' );
	}

	/**
	 * Upsert by slug: create the post if not found; otherwise update meta.
	 *
	 * @param string $post_type Post type to upsert.
	 * @param string $slug Unique post slug.
	 * @param array  $postarr Post arguments passed to WordPress.
	 * @param array  $meta Meta fields to store on the post.
	 * @return int post ID
	 */
	private static function upsert_post_by_slug( $post_type, $slug, $postarr, $meta ) {
		$existing = get_page_by_path( $slug, OBJECT, $post_type );
		if ( $existing ) {
			$post_id = $existing->ID;
			// Update content/title if changed.
			$postarr['ID'] = $post_id;
			wp_update_post( $postarr );
		} else {
			$postarr['post_name'] = $slug;
			$post_id              = wp_insert_post( $postarr );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			WP_CLI::warning( "Failed to upsert {$post_type} : {$slug}" );
			return 0;
		}

		// Tag as seeded and update meta fields.
		update_post_meta( $post_id, '_wpfa_seeded', 1 );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $k => $v ) {
				update_post_meta( $post_id, $k, $v );
			}
		}
		return $post_id;
	}

	/**
	 * Sync event <-> speakers relationships.
	 *
	 * @param int   $event_id Event post ID.
	 * @param array $speaker_ids Related speaker post IDs.
	 * @return void
	 */
	private static function sync_relationships( $event_id, $speaker_ids ) {
		$event_id    = absint( $event_id );
		$speaker_ids = array_values( array_unique( array_map( 'absint', $speaker_ids ) ) );

		if ( ! $event_id || empty( $speaker_ids ) ) {
			return;
		}

		// Update event side.
		$current = (array) get_post_meta( $event_id, 'wpfa_event_speakers', true );
		$new     = array_values( array_unique( array_merge( $current, $speaker_ids ) ) );
		update_post_meta( $event_id, 'wpfa_event_speakers', $new );

		// Update each speaker side.
		foreach ( $speaker_ids as $sid ) {
			$cur = (array) get_post_meta( $sid, 'wpfa_speaker_events', true );
			if ( ! in_array( $event_id, $cur, true ) ) {
				$cur[] = $event_id;
			}
			update_post_meta( $sid, 'wpfa_speaker_events', array_values( array_unique( $cur ) ) );
		}
	}

	/**
	 * Import command entry point to trigger REST API ingestion.
	 *
	 * ## EXAMPLES
	 *   wp wpfa import
	 *
	 * @when after_wp_load
	 *
	 * @param array $args Positional command arguments.
	 * @param array $assoc_args Associative command arguments.
	 * @return void
	 */
	public static function import( $args, $assoc_args ) {
		unset( $args, $assoc_args );

		if ( ! class_exists( 'Wpfaevent_Eventyay_Importer' ) ) {
			WP_CLI::error( 'Eventyay Importer class not found.' );
		}

		$importer = new Wpfaevent_Eventyay_Importer();
		WP_CLI::log( 'Starting Eventyay import from settings...' );

		$result = $importer->import_eventyay_events_from_settings();

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success(
			sprintf(
				'Import completed successfully. Fetched: %1$d, Created: %2$d, Updated: %3$d, Skipped: %4$d.',
				absint( $result['fetched'] ),
				absint( $result['created'] ),
				absint( $result['updated'] ),
				absint( $result['skipped'] )
			)
		);
	}
}
