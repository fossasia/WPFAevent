<?php
/**
 * WP-CLI commands for FOSSASIA Event Plugin.
 *
 * Usage:
 *   wp wpfa seed --minimal
 *   wp wpfa import <file>
 *   wp wpfa export
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
	 * ## EXAMPLES
	 *   wp wpfa seed --minimal
	 *
	 * @alias seed
	 * @subcommand seed
	 *
	 * @when after_wp_load
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public static function seed( $args, $assoc_args ) {
		if ( isset( $assoc_args['minimal'] ) ) {
			self::seed_minimal();
			return;
		}

		WP_CLI::error( 'Please provide an option, e.g., --minimal.' );
	}

	/**
	 * Minimal hardcoded seed (2 speakers, 1 event).
	 */
	private static function seed_minimal() {
		$placeholder = 'https://via.placeholder.com/300x300.png?text=Speaker';

		$speakers = [
			[
				'post_title'   => 'Alex Example',
				'post_content' => 'Open source contributor and community speaker.',
				'meta' => [
					'wpfa_speaker_org'       => 'FOSSASIA',
					'wpfa_speaker_role'      => 'Developer Advocate',
					'wpfa_speaker_photo_url' => $placeholder,
				],
				'slug'         => 'alex-example',
			],
			[
				'post_title'   => 'Bao Nguyen',
				'post_content' => 'Engineer focusing on event platforms.',
				'meta' => [
					'wpfa_speaker_org'       => 'Eventyay',
					'wpfa_speaker_role'      => 'Software Engineer',
					'wpfa_speaker_photo_url' => $placeholder,
				],
				'slug'         => 'bao-nguyen',
			],
		];

		$event = [
			'post_title'   => 'FOSSASIA Community Meetup',
			'post_content' => 'A casual meetup to discuss the roadmap and OSS collaboration.',
			'meta'         => [
				'wpfa_event_date'  => date( 'Y-m-d', strtotime( '+30 days' ) ),
				'wpfa_event_venue' => 'Online',
				'wpfa_event_link'  => 'https://eventyay.com/',
			],
			'slug'         => 'fossasia-community-meetup',
		];

		// Insert speakers (idempotent by slug).
		$speaker_ids = [];
		foreach ( $speakers as $s ) {
			$speaker_ids[] = self::upsert_post_by_slug(
				'wpfa_speaker',
				$s['slug'],
				[
					'post_title'   => $s['post_title'],
					'post_content' => $s['post_content'],
					'post_status'  => 'publish',
					'post_type'    => 'wpfa_speaker',
				],
				$s['meta']
			);
		}

		// Insert event (idempotent by slug).
		$event_id = self::upsert_post_by_slug(
			'wpfa_event',
			$event['slug'],
			[
				'post_title'   => $event['post_title'],
				'post_content' => $event['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'wpfa_event',
			],
			$event['meta']
		);

		WP_CLI::success( 'Seeded minimal data: 2 speakers, 1 event.' );
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
}