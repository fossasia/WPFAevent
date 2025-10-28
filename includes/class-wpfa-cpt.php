<?php
/**
 * Registers Custom Post Types and meta fields.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_CPT.
 */
class WPFA_CPT {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_meta_fields' ] );
	}

	/**
	 * Register CPTs.
	 */
	public function register_post_types() {
		$speaker_labels = [
			'name'          => _x( 'Speakers', 'Post Type General Name', 'wpfa-event' ),
			'singular_name' => _x( 'Speaker', 'Post Type Singular Name', 'wpfa-event' ),
			'menu_name'     => __( 'Speakers', 'wpfa-event' ),
		];
		$speaker_args   = [
			'label'               => __( 'Speaker', 'wpfa-event' ),
			'labels'              => $speaker_labels,
			'supports'            => [ 'title', 'editor', 'thumbnail' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=wpfa_event',
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-megaphone',
		];
		register_post_type( 'wpfa_speaker', $speaker_args );

		$event_labels = [
			'name'          => _x( 'Events', 'Post Type General Name', 'wpfa-event' ),
			'singular_name' => _x( 'Event', 'Post Type Singular Name', 'wpfa-event' ),
			'menu_name'     => __( 'FOSSASIA Events', 'wpfa-event' ),
		];
		$event_args   = [
			'label'               => __( 'Event', 'wpfa-event' ),
			'labels'              => $event_labels,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-calendar-alt',
		];
		register_post_type( 'wpfa_event', $event_args );
	}

	/**
	 * Register meta fields for CPTs.
	 */
	public function register_meta_fields() {
		// Speaker Meta.
		register_post_meta(
			'wpfa_speaker',
			'wpfa_speaker_org',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
		register_post_meta(
			'wpfa_speaker',
			'wpfa_speaker_role',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
		register_post_meta(
			'wpfa_speaker',
			'wpfa_speaker_photo_url',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'sanitize_callback' => 'esc_url_raw',
			]
		);

		// Event Meta.
		register_post_meta( 'wpfa_event', 'wpfa_event_date', [ 'show_in_rest' => true, 'single' => true, 'type' => 'string' ] );
		register_post_meta( 'wpfa_event', 'wpfa_event_venue', [ 'show_in_rest' => true, 'single' => true, 'type' => 'string' ] );
		register_post_meta(
			'wpfa_event',
			'wpfa_event_link',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'sanitize_callback' => 'esc_url_raw',
			]
		);
	}
}