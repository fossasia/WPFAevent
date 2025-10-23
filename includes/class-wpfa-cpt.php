<?php
/**
 * Handles CPT registration.
 *
 * @package    Wpfa_Event
 * @subpackage Wpfa_Event/includes
 */

class WPFA_CPT {

	/**
	 * Constructor to add admin filters.
	 */
	public function __construct() {
		add_filter( 'manage_wpfa_speaker_posts_columns', array( $this, 'add_speaker_admin_columns' ) );
		add_action( 'manage_wpfa_speaker_posts_custom_column', array( $this, 'render_speaker_admin_columns' ), 10, 2 );
	}

	/**
	 * Register Custom Post Types.
	 */
	public function register_cpts() {
		// Events CPT.
		register_post_type(
			'wpfa_event',
			array(
				'labels'       => array(
					'name'          => __( 'Events', 'wpfa-event' ),
					'singular_name' => __( 'Event', 'wpfa-event' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_rest' => true,
			)
		);

		// Speakers CPT.
		register_post_type(
			'wpfa_speaker',
			array(
				'labels'       => array(
					'name'          => __( 'Speakers', 'wpfa-event' ),
					'singular_name' => __( 'Speaker', 'wpfa-event' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Register meta fields for CPTs.
	 */
	public function register_meta() {
		register_post_meta( 'wpfa_speaker', 'wpfa_speaker_org', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'wpfa_speaker', 'wpfa_speaker_position', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'wpfa_speaker', 'wpfa_speaker_photo_url', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw' ) );

		register_post_meta( 'wpfa_event', 'wpfa_event_start_date', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'wpfa_event', 'wpfa_event_end_date', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'wpfa_event', 'wpfa_event_location', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'wpfa_event', 'wpfa_event_url', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw' ) );
	}

	/**
	 * Adds custom columns to the speaker CPT admin list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_speaker_admin_columns( $columns ) {
		$columns['speaker_role'] = __( 'Role', 'wpfa-event' );
		$columns['speaker_org']  = __( 'Organization', 'wpfa-event' );
		return $columns;
	}

	/**
	 * Renders the content for the custom admin columns.
	 *
	 * @param string $column  The column name.
	 * @param int    $post_id The post ID.
	 */
	public function render_speaker_admin_columns( $column, $post_id ) {
		if ( 'speaker_role' === $column ) {
			echo esc_html( get_post_meta( $post_id, 'wpfa_speaker_position', true ) );
		}
		if ( 'speaker_org' === $column ) {
			echo esc_html( get_post_meta( $post_id, 'wpfa_speaker_org', true ) );
		}
	}
}