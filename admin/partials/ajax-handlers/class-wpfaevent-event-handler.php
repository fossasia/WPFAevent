<?php
/**
 * Handles all event-related AJAX functionality in the admin area.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/ajax-handlers
 * @author     FOSSASIA <contact@fossasia.org>
 * @since      1.0.0
 */

class Wpfaevent_Event_Handler {

	/**
	 * The plugin name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The plugin name.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name    The name of this plugin.
	 * @param    string $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Handle AJAX request to get event data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_event() {
		// Verify nonce. Third param 'false' ensures we can handle the error response manually via JSON.
		if ( ! check_ajax_referer( 'wpfa_events_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized', 'wpfaevent' ) ),
				403
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id ) {
			wp_send_json_error( esc_html__( 'Invalid event ID', 'wpfaevent' ) );
		}

		$event = get_post( $event_id );

		if ( ! $event || $event->post_type !== 'wpfa_event' ) {
			wp_send_json_error( esc_html__( 'Event not found', 'wpfaevent' ) );
		}

		$data = array(
			'id'                => $event_id,
			'title'             => $event->post_title,
			'content'           => $event->post_content,
			'excerpt'           => $event->post_excerpt,
			'start_date'        => get_post_meta( $event_id, 'wpfa_event_start_date', true ),
			'end_date'          => get_post_meta( $event_id, 'wpfa_event_end_date', true ),
			'location'          => get_post_meta( $event_id, 'wpfa_event_location', true ),
			'event_url'         => get_post_meta( $event_id, 'wpfa_event_url', true ),
			'registration_link' => get_post_meta( $event_id, 'wpfa_event_registration_link', true ),
			'cfs_link'          => get_post_meta( $event_id, 'wpfa_event_cfs_link', true ),
			'featured_image'    => get_post_thumbnail_id( $event_id ),
		);

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to add a new event.
	 *
	 * @since    1.0.0
	 */
	public function ajax_add_event() {
		// Verify nonce. Third param 'false' ensures we can handle the error response manually via JSON.
		if ( ! check_ajax_referer( 'wpfa_events_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		$title             = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$excerpt           = isset( $_POST['excerpt'] ) ? sanitize_text_field( wp_unslash( $_POST['excerpt'] ) ) : '';
		$start_date        = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$location          = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
		$registration_link = isset( $_POST['registration_link'] ) ? esc_url_raw( wp_unslash( $_POST['registration_link'] ) ) : '';

		// Validate required fields
		$required_fields = array(
			'title'             => $title,
			'excerpt'           => $excerpt,
			'start_date'        => $start_date,
			'location'          => $location,
			'registration_link' => $registration_link,
		);

		foreach ( $required_fields as $field_name => $field_value ) {
			if ( empty( $field_value ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field_name ) );
			}
		}

		// Create event post
		$event_data = array(
			'post_title'   => $title,
			'post_content' => isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '',
			'post_excerpt' => $excerpt,
			'post_type'    => 'wpfa_event',
			'post_status'  => 'publish',
		);

		$event_id = wp_insert_post( $event_data );

		if ( is_wp_error( $event_id ) || 0 === $event_id ) {
			$error_message = is_wp_error( $event_id ) ? $event_id->get_error_message() : esc_html__( 'Failed to create event.', 'wpfaevent' );
			wp_send_json_error( $error_message );
		}

		// Save meta fields - using CORRECT form field names
		$meta_fields = array(
			'wpfa_event_start_date'        => 'start_date',
			'wpfa_event_end_date'          => 'end_date',
			'wpfa_event_time'              => 'time',
			'wpfa_event_location'          => 'location',
			'wpfa_event_lead_text'         => 'lead_text',
			'wpfa_event_registration_link' => 'registration_link',
			'wpfa_event_cfs_link'          => 'cfs_link',
		);

		foreach ( $meta_fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );

				// Special handling for URL fields
				if ( in_array( $post_key, array( 'registration_link', 'cfs_link' ), true ) ) {
					$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				}

				if ( strlen( $value ) > 0 ) {
					update_post_meta( $event_id, $meta_key, $value );
				}
			}
		}

		// Handle featured image upload - use CORRECT file field name
		if ( ! empty( $_FILES['featured_image']['name'] ) ) {

			// Validate file type
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = $_FILES['featured_image']['type'];

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max)
			$max_size = 2 * 1024 * 1024; // 2MB in bytes
			if ( $_FILES['featured_image']['size'] > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment
			$attachment_id = media_handle_upload( 'featured_image', $event_id );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			// Set as featured image
			set_post_thumbnail( $event_id, $attachment_id );
		}

		wp_send_json_success(
			array(
				'event_id' => $event_id,
				'message'  => esc_html__( 'Event created successfully!', 'wpfaevent' ),
			)
		);
	}

	/**
	 * Handle AJAX request to update an event.
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_event() {
		// Verify nonce. Third param 'false' ensures we can handle the error response manually via JSON.
		if ( ! check_ajax_referer( 'wpfa_events_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized', 'wpfaevent' ) ),
				403
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id ) {
			wp_send_json_error( __( 'Invalid event ID', 'wpfaevent' ) );
		}

		// Verify event exists and user can edit it
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'wpfa_event' || ! current_user_can( 'edit_post', $event_id ) ) {
			wp_send_json_error( __( 'Cannot edit this event', 'wpfaevent' ) );
		}

		// Validate required fields
		$required_fields = array( 'title', 'excerpt', 'start_date', 'location', 'registration_link' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field ) );
			}
		}

		// Update post
		$event_data = array(
			'ID'           => $event_id,
			'post_title'   => sanitize_text_field( wp_unslash( $_POST['title'] ) ),
			'post_content' => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'post_excerpt' => sanitize_text_field( wp_unslash( $_POST['excerpt'] ) ),
		);

		wp_update_post( $event_data );

		// Save meta fields
		$meta_fields = array(
			'wpfa_event_start_date'        => 'start_date',
			'wpfa_event_end_date'          => 'end_date',
			'wpfa_event_time'              => 'time',
			'wpfa_event_location'          => 'location',
			'wpfa_event_lead_text'         => 'lead_text',
			'wpfa_event_url'               => 'event_url',
			'wpfa_event_registration_link' => 'registration_link',
			'wpfa_event_cfs_link'          => 'cfs_link',
		);

		foreach ( $meta_fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );

				if ( in_array( $post_key, array( 'event_url', 'registration_link', 'cfs_link' ), true ) ) {
					$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				}

				if ( strlen( $value ) === 0 ) {
					delete_post_meta( $event_id, $meta_key );
				} else {
					update_post_meta( $event_id, $meta_key, $value );
				}
			}
		}

		// Handle featured image upload
		if ( ! empty( $_FILES['featured_image']['name'] ) ) {
			// Validate file type
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = $_FILES['featured_image']['type'];

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max)
			$max_size = 2 * 1024 * 1024; // 2MB in bytes
			if ( $_FILES['featured_image']['size'] > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment
			$attachment_id = media_handle_upload( 'featured_image', $event_id );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			// Set as featured image
			set_post_thumbnail( $event_id, $attachment_id );
		} elseif ( isset( $_POST['remove_featured_image'] ) && $_POST['remove_featured_image'] === 'true' ) {
			// Remove featured image
			delete_post_thumbnail( $event_id );
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to delete an event.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_event() {
		// Verify nonce. Third param 'false' ensures we can handle the error response manually via JSON.
		if ( ! check_ajax_referer( 'wpfa_events_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized', 'wpfaevent' ) ),
				403
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id ) {
			wp_send_json_error( __( 'Invalid event ID', 'wpfaevent' ) );
		}

		// Verify event exists and user can delete it
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'wpfa_event' || ! current_user_can( 'delete_post', $event_id ) ) {
			wp_send_json_error( __( 'Cannot delete this event', 'wpfaevent' ) );
		}

		// Delete the event
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			wp_send_json_error( __( 'Failed to delete event', 'wpfaevent' ) );
		}

		wp_send_json_success();
	}
}
