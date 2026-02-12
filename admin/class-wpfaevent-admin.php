<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfaevent_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfaevent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpfaevent-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpfaevent_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpfaevent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpfaevent-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Add settings link to plugin action links
	 *
	 * @since    1.0.0
	 * @param    array $links    Existing plugin action links
	 * @return   array              Modified plugin action links
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wpfaevent-settings' ) ),
			esc_html__( 'Settings', 'wpfaevent' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register settings page in WordPress admin
	 *
	 * @since    1.0.0
	 */
	public function register_settings_page() {
		add_menu_page(
			esc_html__( 'FOSSASIA Event Settings', 'wpfaevent' ),  // Page title
			esc_html__( 'FOSSASIA Event', 'wpfaevent' ),           // Menu title
			'manage_options',                                       // Capability
			'wpfaevent-settings',                                   // Menu slug
			array( $this, 'render_settings_page' ),                // Callback
			'dashicons-calendar-alt',                               // Icon
			30                                                      // Position
		);
	}

	/**
	 * Render settings page placeholder
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Plugin Skeleton Active', 'wpfaevent' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'This is a placeholder settings page. Settings functionality will be implemented in future updates.', 'wpfaevent' ); ?>
				</p>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Plugin Information', 'wpfaevent' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Version', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( $this->version ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Plugin Name', 'wpfaevent' ); ?></th>
						<td><code><?php echo esc_html( $this->plugin_name ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Text Domain', 'wpfaevent' ); ?></th>
						<td><code>wpfaevent</code></td>
					</tr>
				</table>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Documentation', 'wpfaevent' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: GitHub repository link */
						esc_html__( 'For setup instructions and documentation, visit the %s.', 'wpfaevent' ),
						sprintf(
							'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
							esc_url( 'https://github.com/fossasia/WPFAevent' ),
							esc_html__( 'GitHub repository', 'wpfaevent' )
						)
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	// ========================================
	// META BOXES
	// ========================================

	/**
	 * Register meta boxes for Event and Speaker CPTs.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		// Event meta boxes
		add_meta_box(
			'wpfa_event_details',
			__( 'Event Details', 'wpfaevent' ),
			array( $this, 'render_event_meta_box' ),
			'wpfa_event',
			'normal',
			'high'
		);

		// Speaker meta boxes
		add_meta_box(
			'wpfa_speaker_details',
			__( 'Speaker Details', 'wpfaevent' ),
			array( $this, 'render_speaker_meta_box' ),
			'wpfa_speaker',
			'normal',
			'high'
		);

		// Remove the default Custom Fields meta box to avoid UI clutter
		// since we have enabled 'custom-fields' support for REST API visibility.
		remove_meta_box( 'postcustom', 'wpfa_event', 'normal' );
		remove_meta_box( 'postcustom', 'wpfa_speaker', 'normal' );
	}

	/**
	 * Render Event meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_event_meta_box( $post ) {
		wp_nonce_field( 'wpfa_event_meta_nonce', 'wpfa_event_meta_nonce' );

		$start_date = get_post_meta( $post->ID, 'wpfa_event_start_date', true );
		$end_date   = get_post_meta( $post->ID, 'wpfa_event_end_date', true );
		$time       = get_post_meta( $post->ID, 'wpfa_event_time', true );
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$lead_text  = get_post_meta( $post->ID, 'wpfa_event_lead_text', true );
		$reg_link   = get_post_meta( $post->ID, 'wpfa_event_registration_link', true );
		$cfs_link   = get_post_meta( $post->ID, 'wpfa_event_cfs_link', true );
		$speakers   = get_post_meta( $post->ID, 'wpfa_event_speakers', true );

		// Normalize to array
		if ( ! is_array( $speakers ) ) {
			$speakers = ! empty( $speakers ) ? array( $speakers ) : array();
		}

		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_event_start_date"><?php esc_html_e( 'Start Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_start_date" name="wpfa_event_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_date"><?php esc_html_e( 'End Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_end_date" name="wpfa_event_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_time"><?php esc_html_e( 'Event Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_time" name="wpfa_event_time" value="<?php echo esc_attr( $time ); ?>" class="regular-text" placeholder="e.g., 10:00"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_location"><?php esc_html_e( 'Location', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_location" name="wpfa_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_lead_text"><?php esc_html_e( 'Lead Text', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_lead_text" name="wpfa_event_lead_text" value="<?php echo esc_attr( $lead_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Short description for hero section', 'wpfaevent' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_url"><?php esc_html_e( 'Event URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_url" name="wpfa_event_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_registration_link"><?php esc_html_e( 'Registration Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_registration_link" name="wpfa_event_registration_link" value="<?php echo esc_attr( $reg_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/..."></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_cfs_link"><?php esc_html_e( 'Call for Speakers Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_cfs_link" name="wpfa_event_cfs_link" value="<?php echo esc_attr( $cfs_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/.../cfs"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_speakers"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					$speaker_ids = get_posts(
						array(
							'post_type'      => 'wpfa_speaker',
							'posts_per_page' => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					if ( $speaker_ids ) :
						?>
						<select name="wpfa_event_speakers[]" id="wpfa_event_speakers" multiple class="wpfaevent-speakers-select">
							<?php foreach ( $speaker_ids as $speaker_id ) : ?>
								<?php $is_selected = is_array( $speakers ) && in_array( $speaker_id, $speakers, true ); ?>
									<option value="<?php echo esc_attr( $speaker_id ); ?>"
										<?php selected( $is_selected, true ); ?>>
									<?php echo esc_html( get_the_title( $speaker_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple speakers.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No speakers found. Create speakers first.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Speaker meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_speaker_meta_box( $post ) {
		wp_nonce_field( 'wpfa_speaker_meta_nonce', 'wpfa_speaker_meta_nonce' );

		$position     = get_post_meta( $post->ID, 'wpfa_speaker_position', true );
		$organization = get_post_meta( $post->ID, 'wpfa_speaker_organization', true );
		$bio          = get_post_meta( $post->ID, 'wpfa_speaker_bio', true );
		$headshot_url = get_post_meta( $post->ID, 'wpfa_speaker_headshot_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_speaker_position"><?php esc_html_e( 'Position/Title', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_position" name="wpfa_speaker_position" value="<?php echo esc_attr( $position ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_organization"><?php esc_html_e( 'Organization', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_organization" name="wpfa_speaker_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_bio"><?php esc_html_e( 'Biography', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					wp_editor(
						$bio,
						'wpfa_speaker_bio',
						array(
							'textarea_name' => 'wpfa_speaker_bio',
							'textarea_rows' => 10,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_headshot_url"><?php esc_html_e( 'Headshot URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_speaker_headshot_url" name="wpfa_speaker_headshot_url" value="<?php echo esc_attr( $headshot_url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Event meta box data.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 */
	public function save_event_meta( $post_id ) {
		if ( ! isset( $_POST['wpfa_event_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wpfa_event_meta_nonce'] ), 'wpfa_event_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// List of all meta fields to save
		$meta_fields = array(
			'wpfa_event_start_date',
			'wpfa_event_end_date',
			'wpfa_event_time',
			'wpfa_event_location',
			'wpfa_event_lead_text',
			'wpfa_event_url',
			'wpfa_event_registration_link',
			'wpfa_event_cfs_link',
		);

		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = wp_unslash( $_POST[ $field ] );

				// Special handling for URL fields
				if ( in_array( $field, array( 'wpfa_event_url', 'wpfa_event_registration_link', 'wpfa_event_cfs_link' ), true ) ) {
					$value = esc_url_raw( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_post_meta( $post_id, $field, $value );
			}
		}

		// Handle speakers array
		if ( isset( $_POST['wpfa_event_speakers'] ) && is_array( $_POST['wpfa_event_speakers'] ) ) {
			$speakers = array_map( 'absint', $_POST['wpfa_event_speakers'] );
			update_post_meta( $post_id, 'wpfa_event_speakers', $speakers );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_speakers' );
		}
	}

	/**
	 * Save Speaker meta box data.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 */
	public function save_speaker_meta( $post_id ) {
		if ( ! isset( $_POST['wpfa_speaker_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wpfa_speaker_meta_nonce'] ), 'wpfa_speaker_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpfa_speaker_position'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_position', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_position'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_organization'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_organization', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_organization'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_bio'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_bio', wp_kses_post( wp_unslash( $_POST['wpfa_speaker_bio'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_headshot_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_headshot_url', esc_url_raw( wp_unslash( $_POST['wpfa_speaker_headshot_url'] ) ) );
		}
	}

	/**
	 * Show notice when block themes are active.
	 *
	 * @since 1.0.0
	 */
	public function maybe_show_block_theme_notice() {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo esc_html__(
			'WPFA Event page templates require a classic theme. Block themes (e.g., Twenty Twenty-Five) do not support PHP page templates.',
			'wpfaevent'
		);
		echo '</p></div>';
	}

	/**
	 * Handle AJAX request to get speaker data.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_speaker() {
		// Verify nonce
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
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

		$speaker_id = isset( $_POST['speaker_id'] ) ? absint( $_POST['speaker_id'] ) : 0;

		if ( ! $speaker_id ) {
			wp_send_json_error( esc_html__( 'Invalid speaker ID', 'wpfaevent' ) );
		}

		$speaker = get_post( $speaker_id );

		if ( ! $speaker || $speaker->post_type !== 'wpfa_speaker' ) {
			wp_send_json_error( esc_html__( 'Speaker not found', 'wpfaevent' ) );
		}

		// Get category term
		$category      = '';
		$category_slug = '';
		$terms         = wp_get_object_terms( $speaker_id, 'wpfa_speaker_category' );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$category      = $terms[0]->name;
			$category_slug = $terms[0]->slug;
		}

		$data = array(
			'id'            => $speaker_id,
			'name'          => $speaker->post_title,
			'position'      => get_post_meta( $speaker_id, 'wpfa_speaker_position', true ),
			'organization'  => get_post_meta( $speaker_id, 'wpfa_speaker_organization', true ),
			'bio'           => get_post_meta( $speaker_id, 'wpfa_speaker_bio', true ),
			'headshot_url'  => get_post_meta( $speaker_id, 'wpfa_speaker_headshot_url', true ),
			'linkedin'      => get_post_meta( $speaker_id, 'wpfa_speaker_linkedin', true ),
			'twitter'       => get_post_meta( $speaker_id, 'wpfa_speaker_twitter', true ),
			'github'        => get_post_meta( $speaker_id, 'wpfa_speaker_github', true ),
			'website'       => get_post_meta( $speaker_id, 'wpfa_speaker_website', true ),
			'category'      => $category,
			'category_slug' => $category_slug,
			'talk_title'    => get_post_meta( $speaker_id, 'wpfa_speaker_talk_title', true ),
			'talk_date'     => get_post_meta( $speaker_id, 'wpfa_speaker_talk_date', true ),
			'talk_time'     => get_post_meta( $speaker_id, 'wpfa_speaker_talk_time', true ),
			'talk_end_time' => get_post_meta( $speaker_id, 'wpfa_speaker_talk_end_time', true ),
			'talk_abstract' => get_post_meta( $speaker_id, 'wpfa_speaker_talk_abstract', true ),
		);

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to add a new speaker.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_speaker() {
		// Verify nonce
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
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

		// Validate required fields
		$required_fields = array( 'name', 'position', 'bio', 'talk_title', 'talk_date', 'talk_time', 'talk_end_time' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field ) );
			}
		}

		// Create speaker post
		$speaker_data = array(
			'post_title'   => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
			'post_type'    => 'wpfa_speaker',
			'post_status'  => 'publish',
			'post_content' => '',
		);

		$speaker_id = wp_insert_post( $speaker_data );

		if ( is_wp_error( $speaker_id ) ) {
			wp_send_json_error( $speaker_id->get_error_message() );
		}

		// Handle image upload
		$image_url = '';
		if ( ! empty( $_FILES['image_upload']['name'] ) ) {
			// Validate file type
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = $_FILES['image_upload']['type'];

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max)
			$max_size = 2 * 1024 * 1024; // 2MB in bytes
			if ( $_FILES['image_upload']['size'] > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment
			$attachment_id = media_handle_upload( 'image_upload', 0 );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			$image_url = wp_get_attachment_url( $attachment_id );
		} elseif ( ! empty( $_POST['image_url'] ) ) {
			$image_url = esc_url_raw( wp_unslash( $_POST['image_url'] ) );
		}

		// Save meta fields
		$meta_fields = array(
			'wpfa_speaker_position'     => 'position',
			'wpfa_speaker_organization' => 'organization',
			'wpfa_speaker_bio'          => 'bio',
			'wpfa_speaker_headshot_url' => 'image_url',
			'wpfa_speaker_linkedin'     => 'linkedin',
			'wpfa_speaker_twitter'      => 'twitter',
			'wpfa_speaker_github'       => 'github',
			'wpfa_speaker_website'      => 'website',
		);

		foreach ( $meta_fields as $meta_key => $post_key ) {
			if ( $post_key === 'image_url' && ! empty( $image_url ) ) {
				// Use uploaded image URL or provided URL
				update_post_meta( $speaker_id, $meta_key, $image_url );
			} elseif ( isset( $_POST[ $post_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );

				if ( $post_key === 'bio' ) {
					$value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
				} elseif ( in_array( $post_key, array( 'linkedin', 'twitter', 'github', 'website' ), true ) ) {
					$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}

				if ( strlen( $value ) === 0 ) {
					delete_post_meta( $speaker_id, $meta_key );
				} else {
					update_post_meta( $speaker_id, $meta_key, $value );
				}
			}
		}

		$session_fields = array(
			'wpfa_speaker_talk_title'    => 'talk_title',
			'wpfa_speaker_talk_date'     => 'talk_date',
			'wpfa_speaker_talk_time'     => 'talk_time',
			'wpfa_speaker_talk_end_time' => 'talk_end_time',
			'wpfa_speaker_talk_abstract' => 'talk_abstract',
		);

		foreach ( $session_fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {

				if ( $post_key === 'talk_abstract' ) {
					$value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}

				if ( strlen( $value ) === 0 ) {
					delete_post_meta( $speaker_id, $meta_key );
				} else {
					update_post_meta( $speaker_id, $meta_key, $value );
				}
			}
		}

		if ( isset( $_POST['category'] ) ) {
			$category = sanitize_text_field( wp_unslash( $_POST['category'] ) );

			// If it's numeric, it's a term ID
			if ( is_numeric( $category ) ) {
				$term_id = (int) $category;
				wp_set_object_terms( $speaker_id, $term_id, 'wpfa_speaker_category' );
			}
			// If it's "_custom" with custom value
			elseif ( $category === '_custom' && isset( $_POST['category_custom'] ) && ! empty( $_POST['category_custom'] ) ) {
				$category_name = sanitize_text_field( wp_unslash( $_POST['category_custom'] ) );
				wp_set_object_terms( $speaker_id, $category_name, 'wpfa_speaker_category' );
			}
			// If it's a slug/name
			elseif ( ! empty( $category ) && $category !== '_custom' ) {
				wp_set_object_terms( $speaker_id, $category, 'wpfa_speaker_category' );
			}
			// Empty
			else {
				wp_set_object_terms( $speaker_id, array(), 'wpfa_speaker_category' );
			}
		}

		wp_send_json_success( array( 'speaker_id' => $speaker_id ) );
	}

	/**
	 * Handle AJAX request to update a speaker.
	 *
	 * @since 1.0.0
	 */
	public function ajax_update_speaker() {
		// Verify nonce
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
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

		$speaker_id = isset( $_POST['speaker_id'] ) ? absint( $_POST['speaker_id'] ) : 0;

		if ( ! $speaker_id ) {
			wp_send_json_error( __( 'Invalid speaker ID', 'wpfaevent' ) );
		}

		// Verify speaker exists and user can edit it
		$speaker = get_post( $speaker_id );
		if ( ! $speaker || $speaker->post_type !== 'wpfa_speaker' || ! current_user_can( 'edit_post', $speaker_id ) ) {
			wp_send_json_error( __( 'Cannot edit this speaker', 'wpfaevent' ) );
		}

		// Validate required fields
		$required_fields = array( 'name', 'position', 'bio', 'talk_title', 'talk_date', 'talk_time', 'talk_end_time' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field ) );
			}
		}

		// Update post title if name changed
		if ( ! empty( $_POST['name'] ) ) {
			wp_update_post(
				array(
					'ID'         => $speaker_id,
					'post_title' => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
				)
			);
		}

		// Handle image upload
		$image_url = '';
		if ( ! empty( $_FILES['image_upload']['name'] ) ) {
			// Validate file type
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = $_FILES['image_upload']['type'];

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max)
			$max_size = 2 * 1024 * 1024; // 2MB in bytes
			if ( $_FILES['image_upload']['size'] > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment
			$attachment_id = media_handle_upload( 'image_upload', $speaker_id );

			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			$image_url = wp_get_attachment_url( $attachment_id );
		} elseif ( ! empty( $_POST['image_url'] ) ) {
			$image_url = esc_url_raw( wp_unslash( $_POST['image_url'] ) );
		}

		// Save meta fields
		$meta_fields = array(
			'wpfa_speaker_position'     => 'position',
			'wpfa_speaker_organization' => 'organization',
			'wpfa_speaker_bio'          => 'bio',
			'wpfa_speaker_headshot_url' => 'image_url',
			'wpfa_speaker_linkedin'     => 'linkedin',
			'wpfa_speaker_twitter'      => 'twitter',
			'wpfa_speaker_github'       => 'github',
			'wpfa_speaker_website'      => 'website',
		);

		foreach ( $meta_fields as $meta_key => $post_key ) {
			if ( $post_key === 'image_url' && ! empty( $image_url ) ) {
				// Use uploaded image URL or provided URL
				update_post_meta( $speaker_id, $meta_key, $image_url );
			} elseif ( isset( $_POST[ $post_key ] ) ) {

				if ( $post_key === 'bio' ) {
					$value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
				} elseif ( in_array( $post_key, array( 'linkedin', 'twitter', 'github', 'website' ), true ) ) {
					$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}

				// Delete meta when field is intentionally cleared to avoid storing empty values
				if ( strlen( $value ) === 0 ) {
					delete_post_meta( $speaker_id, $meta_key );
				} else {
					update_post_meta( $speaker_id, $meta_key, $value );
				}
			}
		}

		$session_fields = array(
			'wpfa_speaker_talk_title'    => 'talk_title',
			'wpfa_speaker_talk_date'     => 'talk_date',
			'wpfa_speaker_talk_time'     => 'talk_time',
			'wpfa_speaker_talk_end_time' => 'talk_end_time',
			'wpfa_speaker_talk_abstract' => 'talk_abstract',
		);

		foreach ( $session_fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {

				if ( $post_key === 'talk_abstract' ) {
					$value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}

				if ( strlen( $value ) === 0 ) {
					delete_post_meta( $speaker_id, $meta_key );
				} else {
					update_post_meta( $speaker_id, $meta_key, $value );
				}
			}
		}

		if ( isset( $_POST['category'] ) ) {
			$category = sanitize_text_field( wp_unslash( $_POST['category'] ) );

			// If it's numeric, it's a term ID
			if ( is_numeric( $category ) ) {
				$term_id = (int) $category;
				wp_set_object_terms( $speaker_id, $term_id, 'wpfa_speaker_category' );
			}
			// If it's "_custom" with custom value
			elseif ( $category === '_custom' && isset( $_POST['category_custom'] ) && ! empty( $_POST['category_custom'] ) ) {
				$category_name = sanitize_text_field( wp_unslash( $_POST['category_custom'] ) );
				wp_set_object_terms( $speaker_id, $category_name, 'wpfa_speaker_category' );
			}
			// If it's a slug/name
			elseif ( ! empty( $category ) && $category !== '_custom' ) {
				wp_set_object_terms( $speaker_id, $category, 'wpfa_speaker_category' );
			}
			// Empty
			else {
				wp_set_object_terms( $speaker_id, array(), 'wpfa_speaker_category' );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to delete a speaker.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_speaker() {
		// Verify nonce
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
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

		$speaker_id = isset( $_POST['speaker_id'] ) ? absint( $_POST['speaker_id'] ) : 0;

		if ( ! $speaker_id ) {
			wp_send_json_error( __( 'Invalid speaker ID', 'wpfaevent' ) );
		}

		// Verify speaker exists and user can delete it
		$speaker = get_post( $speaker_id );
		if ( ! $speaker || $speaker->post_type !== 'wpfa_speaker' || ! current_user_can( 'delete_post', $speaker_id ) ) {
			wp_send_json_error( __( 'Cannot delete this speaker', 'wpfaevent' ) );
		}

		// Delete the speaker
		$result = wp_delete_post( $speaker_id, true );

		if ( ! $result ) {
			wp_send_json_error( __( 'Failed to delete speaker', 'wpfaevent' ) );
		}

		wp_send_json_success();
	}
}