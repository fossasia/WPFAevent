<?php
/**
 * WP-CLI Seeder for WPFA Event.
 *
 * Usage:
 *   wp wpfa seed --minimal
 *   wp wpfa seed --from-json=wp-content/plugins/wpfa-event/assets/demo/minimal.json
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
	 *   wp wpfa seed --from-json=wp-content/plugins/wpfa-event/assets/demo/minimal.json
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
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
	 * Manage plugin settings.
	 *
	 * ## OPTIONS
	 *
	 * <command>
	 * : The command to run.
	 * ---
	 * options:
	 *   - get
	 *   - set
	 *   - list
	 * ---
	 *
	 * [<key>]
	 * : The setting key. Required for 'get' and 'set'.
	 *
	 * [<value>]
	 * : The value to set for the setting. Required for 'set'.
	 *
	 * ## EXAMPLES
	 *
	 *   # List all settings and their values
	 *   wp wpfa settings list
	 *
	 *   # Get the value of a single setting
	 *   wp wpfa settings get wpfa_image_base_path
	 *
	 *   # Set the value of a setting
	 *   wp wpfa settings set wpfa_image_base_path https://example.com/images/
	 *
	 *   # Enable a feature toggle
	 *   wp wpfa settings set wpfa_feature_toggle 1
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public static function settings( $args, $assoc_args ) {
		$valid_keys = array( 'wpfa_image_base_path', 'wpfa_default_placeholder', 'wpfa_feature_toggle' );
		$command    = $args[0] ?? 'list';

		switch ( $command ) {
			case 'list':
				$settings_data = array();
				foreach ( $valid_keys as $key ) {
					$settings_data[] = array(
						'key'   => $key,
						'value' => WPFA_Settings::get( $key, '(not set)' ),
					);
				}
				WP_CLI\Utils\format_items( 'table', $settings_data, array( 'key', 'value' ) );
				break;

			case 'get':
				$key = $args[1] ?? '';
				if ( empty( $key ) || ! in_array( $key, $valid_keys, true ) ) {
					WP_CLI::error( 'Please provide a valid setting key. Use `wp wpfa settings list` to see available keys.' );
				}
				WP_CLI::line( WPFA_Settings::get( $key, '(not set)' ) );
				break;

			case 'set':
				$key   = $args[1] ?? '';
				$value = $args[2] ?? null;
				if ( empty( $key ) || ! in_array( $key, $valid_keys, true ) || is_null( $value ) ) {
					WP_CLI::error( 'Usage: wp wpfa settings set <key> <value>' );
				}
				WPFA_Settings::update( $key, $value );
				WP_CLI::success( "Setting '{$key}' updated." );
				break;
		}
	}

	/**
	 * Minimal hardcoded seed (2 speakers, 1 event).
	 */
	private static function seed_minimal() {
		$placeholder = 'https://via.placeholder.com/150';

		$speakers = [
			[
				'post_title'   => 'Alex Example',
				'post_content' => 'Open source contributor and community speaker.',
				'meta'         => [
					'wpfa_speaker_org'      => 'FOSSASIA',
					'wpfa_speaker_position' => 'Developer Advocate',
					'wpfa_speaker_photo_url'    => $placeholder,
				],
				'slug'         => 'alex-example',
			],
			[
				'post_title'   => 'Bao Nguyen',
				'post_content' => 'Engineer focusing on event platforms and accessibility.',
				'meta'         => [
					'wpfa_speaker_org'      => 'Eventyay',
					'wpfa_speaker_position' => 'Software Engineer',
					'wpfa_speaker_photo_url'    => $placeholder,
				],
				'slug'         => 'bao-nguyen',
			],
		];

		$event = [
			'post_title'   => 'FOSSASIA Community Meetup',
			'post_content' => 'A casual meetup to discuss the roadmap and OSS collaboration.',
			'meta'         => [
				'wpfa_event_start_date' => date( 'Y-m-d', strtotime( '+30 days' ) ),
				'wpfa_event_end_date'   => date( 'Y-m-d', strtotime( '+31 days' ) ),
				'wpfa_event_location'   => 'Online',
				'wpfa_event_url'        => 'https://eventyay.com/',
			],
			'slug'         => 'fossasia-community-meetup',
		];

		// Insert speakers (idempotent by slug).
		$speaker_ids = [];
		foreach ( $speakers as $s ) {
			$speaker_ids[] = self::upsert_post_by_slug(
				'wpfa_speaker',
				sanitize_title($s['slug']),
				[
					'post_title'   => $s['post_title'],
					'post_content' => $s['post_content'],
					'post_status'  => 'publish',
					'post_type'    => 'wpfa_speaker', // Ensure this matches your CPT slug
				],
				$s['meta']
			);
		}

		// Insert event (idempotent by slug).
		$event_id = self::upsert_post_by_slug(
			'wpfa_event',
			sanitize_title($event['slug']),
			[
				'post_title'   => $event['post_title'],
				'post_content' => $event['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'wpfa_event', // Ensure this matches your CPT slug
			],
			$event['meta']
		);

		// Relate event ↔ speakers (store both sides; skip if already there).
		self::sync_relationships( $event_id, $speaker_ids );

		WP_CLI::success( 'Seeded minimal data: 2 speakers, 1 event.' );
	}

	/**
	 * Seed from JSON file.
	 *
	 * @param string $path
	 */
	private static function seed_from_json( $path ) {
		if ( ! file_exists( $path ) ) {
			WP_CLI::error( "JSON file not found: {$path}" );
		}
		$json = file_get_contents( $path );
		if ( false === $json ) {
			WP_CLI::error( "Unable to read JSON file: {$path}" );
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON structure.' );
		}

		// Insert speakers first.
		$slug_to_id = [];
		if ( ! empty( $data['speakers'] ) && is_array( $data['speakers'] ) ) {
			foreach ( $data['speakers'] as $s ) {
				$slug   = sanitize_title( $s['slug'] ?? $s['title'] ?? wp_generate_uuid4() );
				$title  = sanitize_text_field( $s['title'] ?? 'Speaker' );
				$content = wp_kses_post( $s['content'] ?? '' );

				$meta = [
					'wpfa_speaker_org'      => isset( $s['org'] ) ? sanitize_text_field( $s['org'] ) : '',
					'wpfa_speaker_position' => isset( $s['position'] ) ? sanitize_text_field( $s['position'] ) : '',
					'wpfa_speaker_photo_url'    => isset( $s['photo'] ) ? esc_url_raw( $s['photo'] ) : 'https://via.placeholder.com/150',
				];

				$id = self::upsert_post_by_slug( 'wpfa_speaker', $slug, [ 'post_title' => $title, 'post_content' => $content, 'post_status'  => 'publish', 'post_type' => 'wpfa_speaker', ], $meta );
				$slug_to_id[ $slug ] = $id;
			}
		}

		// Insert events and relate to speakers from slugs.
		if ( ! empty( $data['events'] ) && is_array( $data['events'] ) ) {
			foreach ( $data['events'] as $e ) {
				$slug    = sanitize_title( $e['slug'] ?? $e['title'] ?? wp_generate_uuid4() );
				$title   = sanitize_text_field( $e['title'] ?? 'Event' );
				$content = wp_kses_post( $e['content'] ?? '' );

				$meta = [
					'wpfa_event_start_date' => isset( $e['start_date'] ) ? sanitize_text_field( $e['start_date'] ) : '',
					'wpfa_event_end_date'   => isset( $e['end_date'] ) ? sanitize_text_field( $e['end_date'] ) : '',
					'wpfa_event_location'   => isset( $e['location'] ) ? sanitize_text_field( $e['location'] ) : '',
					'wpfa_event_url'        => isset( $e['url'] ) ? esc_url_raw( $e['url'] ) : '',
				];

				$event_id = self::upsert_post_by_slug( 'wpfa_event', $slug, [ 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'wpfa_event', ], $meta );

				$event_speaker_slugs = ! empty( $e['speakers'] ) && is_array( $e['speakers'] ) ? $e['speakers'] : [];
				$speaker_ids         = array_values( array_intersect_key( $slug_to_id, array_flip( $event_speaker_slugs ) ) );

				self::sync_relationships( $event_id, $speaker_ids );
			}
		}

		WP_CLI::success( 'Seeded data from JSON.' );
	}

	/**
	 * Upsert by slug: create the post if not found; otherwise update meta.
	 */
	private static function upsert_post_by_slug( $post_type, $slug, $postarr, $meta ) {
		$existing = get_page_by_path( $slug, OBJECT, $post_type );
		if ( $existing ) {
			$post_id = $existing->ID;
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
	 */
	private static function sync_relationships( $event_id, $speaker_ids ) {
		$event_id    = absint( $event_id );
		$speaker_ids = array_values( array_unique( array_map( 'absint', $speaker_ids ) ) );

		if ( ! $event_id || empty( $speaker_ids ) ) {
			return;
		}

		$current = (array) get_post_meta( $event_id, 'wpfa_event_speakers', true );
		$new     = array_values( array_unique( array_merge( $current, $speaker_ids ) ) );
		update_post_meta( $event_id, 'wpfa_event_speakers', $new );

		foreach ( $speaker_ids as $sid ) {
			$cur = (array) get_post_meta( $sid, 'wpfa_speaker_events', true );
			if ( ! in_array( $event_id, $cur, true ) ) {
				$cur[] = $event_id;
			}
			update_post_meta( $sid, 'wpfa_speaker_events', array_values( array_unique( $cur ) ) );
		}
	}
}