<?php
/**
 * Handles REST API routes for the WPFA Event plugin.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_REST.
 */
class WPFA_REST {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$namespace = 'wpfa/v1';

		// Route: /wp-json/wpfa/v1/speakers
		register_rest_route(
			$namespace,
			'/speakers',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_speakers' ],
					'permission_callback' => '__return_true',
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_speaker' ],
					'permission_callback' => [ $this, 'can_manage' ],
					'args'                => $this->get_speaker_schema(),
				],
			]
		);

		// Route: /wp-json/wpfa/v1/events
		register_rest_route(
			$namespace,
			'/events',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_events' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Get all speakers.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_speakers( WP_REST_Request $request ) {
		$cache_key = 'wpfa_speakers';
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return rest_ensure_response( $cached );
		}

		$per_page = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 10;
		$page     = $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;

		$query = new WP_Query(
			[
				'post_type'      => 'wpfa_speaker',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'post_status'    => 'publish',
			]
		);

		if ( ! $query->have_posts() ) {
			return rest_ensure_response( [] );
		}

		$speakers = array_map(
			function( $post ) {
				return [
					'id'           => $post->ID,
					'name'         => $post->post_title,
					'bio'          => wp_strip_all_tags( $post->post_content ),
					'organization' => get_post_meta( $post->ID, 'wpfa_speaker_org', true ),
					'role'         => get_post_meta( $post->ID, 'wpfa_speaker_role', true ),
					'photo_url'    => get_post_meta( $post->ID, 'wpfa_speaker_photo_url', true ),
				];
			},
			$query->posts
		);

		// Only cache the first page of results to avoid storing large datasets.
		if ( 1 === $page ) {
			set_transient( $cache_key, $speakers, HOUR_IN_SECONDS );
		}

		$response = rest_ensure_response( $speakers );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );
		return $response;
	}

	/**
	 * Create a new speaker (authenticated only).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_speaker( WP_REST_Request $request ) {
		$name = sanitize_text_field( $request['name'] );
		$bio  = sanitize_textarea_field( $request['bio'] );

		$speaker_id = wp_insert_post(
			[
				'post_type'    => 'wpfa_speaker',
				'post_title'   => $name,
				'post_content' => $bio,
				'post_status'  => 'publish',
			]
		);

		if ( is_wp_error( $speaker_id ) ) {
			return new WP_Error( 'create_failed', 'Could not create speaker.', [ 'status' => 500 ] );
		}

		update_post_meta( $speaker_id, 'wpfa_speaker_org', sanitize_text_field( $request['organization'] ) );
		update_post_meta( $speaker_id, 'wpfa_speaker_role', sanitize_text_field( $request['role'] ) );
		update_post_meta( $speaker_id, 'wpfa_speaker_photo_url', esc_url_raw( $request['photo_url'] ) );

		// Invalidate speaker cache on creation.
		delete_transient( 'wpfa_speakers' );

		return rest_ensure_response( [ 'id' => $speaker_id, 'message' => 'Speaker created successfully.' ] );
	}

	/**
	 * Get all events.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_events( WP_REST_Request $request ) {
		$cache_key = 'wpfa_events';
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return rest_ensure_response( $cached );
		}

		$per_page = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 10;
		$page     = $request->get_param( 'page' ) ? (int) $request->get_param( 'page' ) : 1;

		$query = new WP_Query(
			[
				'post_type'      => 'wpfa_event',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'post_status'    => 'publish',
			]
		);

		if ( ! $query->have_posts() ) {
			return rest_ensure_response( [] );
		}

		$events = array_map(
			function( $post ) {
				return [
					'id'      => $post->ID,
					'title'   => $post->post_title,
					'content' => wp_strip_all_tags( $post->post_content ),
					'date'    => get_post_meta( $post->ID, 'wpfa_event_date', true ),
					'venue'   => get_post_meta( $post->ID, 'wpfa_event_venue', true ),
					'link'    => get_post_meta( $post->ID, 'wpfa_event_link', true ),
				];
			},
			$query->posts
		);

		// Only cache the first page of results to avoid storing large datasets.
		if ( 1 === $page ) {
			set_transient( $cache_key, $events, HOUR_IN_SECONDS );
		}

		$response = rest_ensure_response( $events );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );
		return $response;
	}

	/**
	 * Permission check for write routes.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Define request schema for speaker creation.
	 *
	 * @return array
	 */
	private function get_speaker_schema() {
		return [
			'name'         => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'bio'          => [
				'required' => false,
				'type'     => 'string',
			],
			'organization' => [
				'required' => false,
				'type'     => 'string',
			],
			'role'         => [
				'required' => false,
				'type'     => 'string',
			],
			'photo_url'    => [
				'required' => false,
				'type'     => 'string',
				'format'   => 'uri',
			],
		];
	}
}