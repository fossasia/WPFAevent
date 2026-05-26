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
	 * Add a settings link to the plugin action links.
	 *
	 * @since    1.0.0
	 * @param    array $links Existing plugin action links.
	 * @return   array Modified plugin action links.
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
	 * Register the settings page in WordPress admin.
	 *
	 * @since    1.0.0
	 */
	public function register_settings_page() {
		add_menu_page(
			esc_html__( 'FOSSASIA Event Settings', 'wpfaevent' ), // Page title.
			esc_html__( 'FOSSASIA Event', 'wpfaevent' ), // Menu title.
			'manage_options', // Capability.
			'wpfaevent-settings', // Menu slug.
			array( $this, 'render_settings_page' ), // Callback.
			'dashicons-calendar-alt', // Icon.
			30 // Position.
		);
	}

	/**
	 * Render the settings page placeholder.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		// Check user capabilities.
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
		// Event meta boxes.
		add_meta_box(
			'wpfa_event_details',
			__( 'Event Details', 'wpfaevent' ),
			array( $this, 'render_event_meta_box' ),
			'wpfa_event',
			'normal',
			'high'
		);

		// Speaker meta boxes.
		add_meta_box(
			'wpfa_speaker_details',
			__( 'Speaker Details', 'wpfaevent' ),
			array( $this, 'render_speaker_meta_box' ),
			'wpfa_speaker',
			'normal',
			'high'
		);

		// Remove the default Custom Fields meta box to avoid UI clutter.
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
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$speakers   = get_post_meta( $post->ID, 'wpfa_event_speakers', true );

		// Normalize to array.
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
				<th><label for="wpfa_event_location"><?php esc_html_e( 'Location', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_location" name="wpfa_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_url"><?php esc_html_e( 'Event URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_url" name="wpfa_event_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://"></td>
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
		$event_nonce = isset( $_POST['wpfa_event_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_meta_nonce'] ) ) : '';

		if ( ! $event_nonce || ! wp_verify_nonce( $event_nonce, 'wpfa_event_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpfa_event_start_date'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_start_date', sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_date'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_end_date'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_end_date', sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_date'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_location'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_location', sanitize_text_field( wp_unslash( $_POST['wpfa_event_location'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_url'] ) ) );
		}

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
		$speaker_nonce = isset( $_POST['wpfa_speaker_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_meta_nonce'] ) ) : '';

		if ( ! $speaker_nonce || ! wp_verify_nonce( $speaker_nonce, 'wpfa_speaker_meta_nonce' ) ) {
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
		// Verify nonce.
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions.
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

		if ( ! $speaker || 'wpfa_speaker' !== $speaker->post_type ) {
			wp_send_json_error( esc_html__( 'Speaker not found', 'wpfaevent' ) );
		}

		// Get category term.
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
		// Verify nonce.
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		// Validate required fields.
		$required_fields = array( 'name', 'position', 'bio', 'talk_title', 'talk_date', 'talk_time', 'talk_end_time' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				/* translators: %s: Required field key. */
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field ) );
			}
		}

		// Create speaker post.
		$speaker_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$speaker_data = array(
			'post_title'   => $speaker_name,
			'post_type'    => 'wpfa_speaker',
			'post_status'  => 'publish',
			'post_content' => '',
		);

		$speaker_id = wp_insert_post( $speaker_data );

		if ( is_wp_error( $speaker_id ) ) {
			wp_send_json_error( $speaker_id->get_error_message() );
		}

		// Handle image upload.
		$image_url = '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- media_handle_upload() requires the raw $_FILES payload.
		$uploaded_file = ( isset( $_FILES['image_upload'] ) && is_array( $_FILES['image_upload'] ) ) ? $_FILES['image_upload'] : array();
		if ( ! empty( $uploaded_file['name'] ) ) {
			// Validate file type.
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = isset( $uploaded_file['type'] ) ? sanitize_mime_type( wp_unslash( $uploaded_file['type'] ) ) : '';

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max).
			$max_size  = 2 * 1024 * 1024; // 2MB in bytes.
			$file_size = isset( $uploaded_file['size'] ) ? absint( $uploaded_file['size'] ) : 0;
			if ( $file_size > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment.
			$attachment_id = media_handle_upload( 'image_upload', 0 );

			if ( is_wp_error( $attachment_id ) ) {
				/* translators: %s: Upload error message. */
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			$image_url = wp_get_attachment_url( $attachment_id );
		} elseif ( ! empty( $_POST['image_url'] ) ) {
			$image_url = esc_url_raw( wp_unslash( $_POST['image_url'] ) );
		}

		// Save meta fields.
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
			if ( 'image_url' === $post_key && ! empty( $image_url ) ) {
				// Use uploaded image URL or provided URL.
				update_post_meta( $speaker_id, $meta_key, $image_url );
			} elseif ( isset( $_POST[ $post_key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );

				if ( 'bio' === $post_key ) {
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

				if ( 'talk_abstract' === $post_key ) {
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

			// If it's numeric, it's a term ID.
			if ( is_numeric( $category ) ) {
				$term_id = (int) $category;
				wp_set_object_terms( $speaker_id, $term_id, 'wpfa_speaker_category' );
			} elseif ( '_custom' === $category && isset( $_POST['category_custom'] ) && ! empty( $_POST['category_custom'] ) ) {
				// If it's "_custom" with custom value.
				$category_name = sanitize_text_field( wp_unslash( $_POST['category_custom'] ) );
				wp_set_object_terms( $speaker_id, $category_name, 'wpfa_speaker_category' );
			} elseif ( ! empty( $category ) && '_custom' !== $category ) {
				// If it's a slug or name.
				wp_set_object_terms( $speaker_id, $category, 'wpfa_speaker_category' );
			} else {
				// Empty value.
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
		// Verify nonce.
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions.
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

		// Verify the speaker exists and the user can edit it.
		$speaker = get_post( $speaker_id );
		if ( ! $speaker || 'wpfa_speaker' !== $speaker->post_type || ! current_user_can( 'edit_post', $speaker_id ) ) {
			wp_send_json_error( __( 'Cannot edit this speaker', 'wpfaevent' ) );
		}

		// Validate required fields.
		$required_fields = array( 'name', 'position', 'bio', 'talk_title', 'talk_date', 'talk_time', 'talk_end_time' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				/* translators: %s: Required field key. */
				wp_send_json_error( sprintf( esc_html__( 'Missing required field: %s', 'wpfaevent' ), $field ) );
			}
		}

		// Update post title if the name changed.
		$speaker_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( ! empty( $speaker_name ) ) {
			wp_update_post(
				array(
					'ID'         => $speaker_id,
					'post_title' => $speaker_name,
				)
			);
		}

		// Handle image upload.
		$image_url = '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- media_handle_upload() requires the raw $_FILES payload.
		$uploaded_file = ( isset( $_FILES['image_upload'] ) && is_array( $_FILES['image_upload'] ) ) ? $_FILES['image_upload'] : array();
		if ( ! empty( $uploaded_file['name'] ) ) {
			// Validate file type.
			$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
			$file_type     = isset( $uploaded_file['type'] ) ? sanitize_mime_type( wp_unslash( $uploaded_file['type'] ) ) : '';

			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				wp_send_json_error( esc_html__( 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'wpfaevent' ) );
			}

			// Validate file size (2MB max).
			$max_size  = 2 * 1024 * 1024; // 2MB in bytes.
			$file_size = isset( $uploaded_file['size'] ) ? absint( $uploaded_file['size'] ) : 0;
			if ( $file_size > $max_size ) {
				wp_send_json_error( esc_html__( 'File size exceeds 2MB limit.', 'wpfaevent' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// Upload and create attachment.
			$attachment_id = media_handle_upload( 'image_upload', $speaker_id );

			if ( is_wp_error( $attachment_id ) ) {
				/* translators: %s: Upload error message. */
				wp_send_json_error( sprintf( esc_html__( 'Image upload failed: %s', 'wpfaevent' ), $attachment_id->get_error_message() ) );
			}

			$image_url = wp_get_attachment_url( $attachment_id );
		} elseif ( ! empty( $_POST['image_url'] ) ) {
			$image_url = esc_url_raw( wp_unslash( $_POST['image_url'] ) );
		}

		// Save meta fields.
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
			if ( 'image_url' === $post_key && ! empty( $image_url ) ) {
				// Use uploaded image URL or provided URL.
				update_post_meta( $speaker_id, $meta_key, $image_url );
			} elseif ( isset( $_POST[ $post_key ] ) ) {

				if ( 'bio' === $post_key ) {
					$value = wp_kses_post( wp_unslash( $_POST[ $post_key ] ) );
				} elseif ( in_array( $post_key, array( 'linkedin', 'twitter', 'github', 'website' ), true ) ) {
					$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				} else {
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}

				// Delete meta when the field is intentionally cleared to avoid storing empty values.
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

				if ( 'talk_abstract' === $post_key ) {
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

			// If it's numeric, it's a term ID.
			if ( is_numeric( $category ) ) {
				$term_id = (int) $category;
				wp_set_object_terms( $speaker_id, $term_id, 'wpfa_speaker_category' );
			} elseif ( '_custom' === $category && isset( $_POST['category_custom'] ) && ! empty( $_POST['category_custom'] ) ) {
				// If it's "_custom" with a custom value.
				$category_name = sanitize_text_field( wp_unslash( $_POST['category_custom'] ) );
				wp_set_object_terms( $speaker_id, $category_name, 'wpfa_speaker_category' );
			} elseif ( ! empty( $category ) && '_custom' !== $category ) {
				// If it's a slug or name.
				wp_set_object_terms( $speaker_id, $category, 'wpfa_speaker_category' );
			} else {
				// Empty value.
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
		// Verify nonce.
		if ( ! check_ajax_referer( 'wpfa_speakers_ajax', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		// Check permissions.
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

		// Verify the speaker exists and the user can delete it.
		$speaker = get_post( $speaker_id );
		if ( ! $speaker || 'wpfa_speaker' !== $speaker->post_type || ! current_user_can( 'delete_post', $speaker_id ) ) {
			wp_send_json_error( __( 'Cannot delete this speaker', 'wpfaevent' ) );
		}

		// Delete the speaker.
		$result = wp_delete_post( $speaker_id, true );

		if ( ! $result ) {
			wp_send_json_error( __( 'Failed to delete speaker', 'wpfaevent' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Handle Eventyay JSON:API speaker sync for the admin dashboard.
	 *
	 * @since 1.0.0
	 */
	public function ajax_sync_eventyay() {
		if ( ! check_ajax_referer( 'fossasia_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
				),
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'wpfaevent' ),
				),
				403
			);
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( ! $event_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Missing event ID.', 'wpfaevent' ),
				),
				400
			);
		}

		$api_url = $this->get_eventyay_sync_url( $event_id );
		if ( empty( $api_url ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please save an Eventyay API URL before syncing.', 'wpfaevent' ),
				),
				400
			);
		}

		$api_url = $this->prepare_eventyay_sync_url( $api_url );
		if ( is_wp_error( $api_url ) ) {
			$this->send_eventyay_ajax_error( $api_url );
		}

		$settings_write = $this->persist_eventyay_sync_url( $event_id, $api_url );
		if ( is_wp_error( $settings_write ) ) {
			$this->send_eventyay_ajax_error( $settings_write );
		}

		$payload = $this->fetch_eventyay_json( $api_url );
		if ( is_wp_error( $payload ) ) {
			$this->send_eventyay_ajax_error( $payload );
		}

		$import = $this->normalize_eventyay_payload( $payload );
		if ( is_wp_error( $import ) ) {
			$this->send_eventyay_ajax_error( $import );
		}

		$existing_speakers  = $this->read_dashboard_json_file( 'speakers-' . $event_id . '.json', array() );
		$dashboard_speakers = $this->merge_dashboard_speaker_state( $import['speakers'], $existing_speakers );
		$write_result       = $this->write_dashboard_json_file( 'speakers-' . $event_id . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			$this->send_eventyay_ajax_error( $write_result );
		}

		$cpt_result = $this->sync_eventyay_speaker_posts( $import['speakers'], $event_id );

		wp_send_json_success(
			array(
				'message'          => sprintf(
					/* translators: 1: speaker count, 2: session count. */
					esc_html__( 'Synced %1$d speaker(s) from %2$d Eventyay session(s).', 'wpfaevent' ),
					count( $import['speakers'] ),
					$import['session_count']
				),
				'speaker_count'    => count( $import['speakers'] ),
				'session_count'    => $import['session_count'],
				'created_speakers' => $cpt_result['created'],
				'updated_speakers' => $cpt_result['updated'],
			)
		);
	}

	/**
	 * Send a structured Eventyay sync failure response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error Error object.
	 * @return void
	 */
	private function send_eventyay_ajax_error( $error ) {
		$error_data = $error->get_error_data();
		$status     = 500;
		$response   = array(
			'message' => $error->get_error_message(),
			'code'    => $error->get_error_code(),
		);

		if ( is_array( $error_data ) ) {
			$response = array_merge( $response, $error_data );

			if ( isset( $error_data['status'] ) ) {
				$status = absint( $error_data['status'] );
			}
		}

		if ( $status < 400 || $status > 599 ) {
			$status = 500;
		}

		wp_send_json_error( $response, $status );
	}

	/**
	 * Resolve the Eventyay sync URL from POST data or saved dashboard settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function get_eventyay_sync_url( $event_id ) {
		$api_url = '';

		// Nonce is verified in ajax_sync_eventyay() before this helper is called.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['eventyay_api_url'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$api_url = esc_url_raw( wp_unslash( $_POST['eventyay_api_url'] ) );
		}

		if ( empty( $api_url ) ) {
			$settings = $this->read_dashboard_json_file( 'site-settings-' . absint( $event_id ) . '.json', array() );

			if ( is_array( $settings ) && ! empty( $settings['eventyay_api_url'] ) ) {
				$api_url = esc_url_raw( $settings['eventyay_api_url'] );
			}
		}

		/**
		 * Filters the Eventyay Open API URL used by dashboard sync.
		 *
		 * @since 1.0.0
		 *
		 * @param string $api_url  Eventyay API URL.
		 * @param int    $event_id Event post ID.
		 */
		return apply_filters( 'wpfaevent_eventyay_sync_url', $api_url, absint( $event_id ) );
	}

	/**
	 * Persist the Eventyay sync URL into the dashboard settings JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $api_url  Eventyay API URL.
	 * @return true|WP_Error
	 */
	private function persist_eventyay_sync_url( $event_id, $api_url ) {
		$filename = 'site-settings-' . absint( $event_id ) . '.json';
		$settings = $this->read_dashboard_json_file( $filename, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['eventyay_api_url'] = esc_url_raw( $api_url );

		return $this->write_dashboard_json_file( $filename, $settings );
	}

	/**
	 * Validate and complete a dashboard Eventyay API URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Raw API URL.
	 * @return string|WP_Error
	 */
	private function prepare_eventyay_sync_url( $api_url ) {
		$api_url = trim( $api_url );

		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
			return new WP_Error(
				'eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is not a valid HTTP(S) URL.', 'wpfaevent' ),
				array( 'status' => 400 )
			);
		}

		$parts  = wp_parse_url( $api_url );
		$scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new WP_Error(
				'eventyay_invalid_url_scheme',
				esc_html__( 'The Eventyay API URL must use HTTP or HTTPS.', 'wpfaevent' ),
				array( 'status' => 400 )
			);
		}

		$path = isset( $parts['path'] ) ? $parts['path'] : '';
		if ( false !== strpos( $path, '/sessions' ) ) {
			$query_args = array();
			if ( ! empty( $parts['query'] ) ) {
				wp_parse_str( $parts['query'], $query_args );
			}

			if ( empty( $query_args['include'] ) ) {
				$api_url = add_query_arg( 'include', 'speakers,track', $api_url );
			}

			if ( empty( $query_args['page']['size'] ) ) {
				$api_url = add_query_arg( 'page[size]', 200, $api_url );
			}
		}

		return $api_url;
	}

	/**
	 * Fetch and decode an Eventyay JSON:API document.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url Eventyay API URL.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_json( $api_url ) {
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'headers'     => array(
					'Accept' => 'application/vnd.api+json, application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'eventyay_request_failed',
				esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
				array(
					'status'  => 502,
					'details' => $response->get_error_message(),
				)
			);
		}

		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'eventyay_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					esc_html__( 'Eventyay API returned HTTP %d.', 'wpfaevent' ),
					$status
				),
				array(
					'status'      => ( $status >= 400 && $status <= 599 ) ? $status : 502,
					'http_status' => $status,
					'body'        => $this->decode_eventyay_error_body( $body ),
				)
			);
		}

		if ( '' === trim( $body ) ) {
			return new WP_Error(
				'eventyay_empty_response',
				esc_html__( 'Eventyay API returned an empty response.', 'wpfaevent' ),
				array(
					'status'      => 502,
					'http_status' => $status,
				)
			);
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error(
				'eventyay_malformed_json',
				esc_html__( 'Eventyay API returned malformed JSON.', 'wpfaevent' ),
				array(
					'status'          => 502,
					'http_status'     => $status,
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->truncate_string( $body ),
				)
			);
		}

		if ( ! array_key_exists( 'data', $decoded ) ) {
			return new WP_Error(
				'eventyay_invalid_jsonapi',
				esc_html__( 'Eventyay API response does not contain a JSON:API data member.', 'wpfaevent' ),
				array(
					'status'      => 502,
					'http_status' => $status,
					'body'        => $decoded,
				)
			);
		}

		return $decoded;
	}

	/**
	 * Decode an Eventyay error body if possible.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body Response body.
	 * @return array|string
	 */
	private function decode_eventyay_error_body( $body ) {
		$decoded = json_decode( $body, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return $decoded;
		}

		return $this->truncate_string( $body );
	}

	/**
	 * Normalize Eventyay JSON:API sessions or speakers into dashboard speaker data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload JSON:API document.
	 * @return array|WP_Error
	 */
	private function normalize_eventyay_payload( $payload ) {
		$data = isset( $payload['data'] ) ? $payload['data'] : array();

		if ( $this->is_jsonapi_resource( $data ) ) {
			$data = array( $data );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'eventyay_invalid_data',
				esc_html__( 'Eventyay API data member is not a resource list.', 'wpfaevent' ),
				array( 'status' => 502 )
			);
		}

		$included      = $this->index_jsonapi_resources( isset( $payload['included'] ) ? $payload['included'] : array() );
		$speakers      = array();
		$session_count = 0;

		foreach ( $data as $resource ) {
			if ( ! $this->is_jsonapi_resource( $resource ) ) {
				continue;
			}

			if ( $this->jsonapi_type_is( $resource, 'speaker' ) ) {
				$speaker = $this->normalize_eventyay_speaker_resource( $resource );

				if ( ! empty( $speaker['name'] ) ) {
					$this->merge_eventyay_speaker( $speakers, $speaker, array() );
				}

				continue;
			}

			if ( ! $this->jsonapi_type_is( $resource, 'session' ) ) {
				continue;
			}

			++$session_count;

			$session      = $this->normalize_eventyay_session_resource( $resource, $included );
			$speaker_refs = $this->get_jsonapi_relationship_resources( $resource, 'speakers' );

			foreach ( $speaker_refs as $speaker_ref ) {
				$speaker_resource = $this->resolve_jsonapi_resource( $speaker_ref, $included );

				if ( ! $this->is_jsonapi_resource( $speaker_resource ) ) {
					continue;
				}

				$speaker = $this->normalize_eventyay_speaker_resource( $speaker_resource );
				if ( empty( $speaker['name'] ) ) {
					continue;
				}

				if ( empty( $speaker['category'] ) && ! empty( $session['track'] ) ) {
					$speaker['category'] = $session['track'];
				}

				$this->merge_eventyay_speaker( $speakers, $speaker, $session );
			}
		}

		$speakers = array_values( $speakers );

		if ( ! empty( $data ) && empty( $speakers ) ) {
			return new WP_Error(
				'eventyay_no_speakers',
				esc_html__( 'No speaker records were found in the Eventyay response. Use a sessions URL with include=speakers,track or a speakers URL.', 'wpfaevent' ),
				array( 'status' => 422 )
			);
		}

		return array(
			'speakers'      => $speakers,
			'session_count' => $session_count,
		);
	}

	/**
	 * Normalize a JSON:API session resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $session_resource Session resource.
	 * @param array $included         Indexed included resources.
	 * @return array
	 */
	private function normalize_eventyay_session_resource( $session_resource, $included ) {
		$attributes = $this->get_jsonapi_attributes( $session_resource );
		$starts_at  = $this->attribute_value( $attributes, array( 'starts-at', 'start-time', 'starts_at' ) );
		$ends_at    = $this->attribute_value( $attributes, array( 'ends-at', 'end-time', 'ends_at' ) );
		$track_name = '';
		$track_refs = $this->get_jsonapi_relationship_resources( $session_resource, 'track' );

		if ( ! empty( $track_refs ) ) {
			$track = $this->resolve_jsonapi_resource( $track_refs[0], $included );
			if ( $this->is_jsonapi_resource( $track ) ) {
				$track_attributes = $this->get_jsonapi_attributes( $track );
				$track_name       = $this->attribute_value( $track_attributes, array( 'name', 'title' ) );
			}
		}

		return array(
			'id'        => isset( $session_resource['id'] ) ? 'eventyay-session-' . sanitize_key( $session_resource['id'] ) : '',
			'title'     => sanitize_text_field( $this->attribute_value( $attributes, array( 'title', 'subtitle', 'name' ) ) ),
			'date'      => $this->format_eventyay_date( $starts_at ),
			'time'      => $this->format_eventyay_time( $starts_at ),
			'end_time'  => $this->format_eventyay_time( $ends_at ),
			'abstract'  => wp_kses_post( $this->attribute_value( $attributes, array( 'long-abstract', 'short-abstract', 'abstract', 'description' ) ) ),
			'track'     => sanitize_text_field( $track_name ),
			'source_id' => isset( $session_resource['id'] ) ? sanitize_text_field( $session_resource['id'] ) : '',
		);
	}

	/**
	 * Normalize a JSON:API speaker resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker_resource Speaker resource.
	 * @return array
	 */
	private function normalize_eventyay_speaker_resource( $speaker_resource ) {
		$attributes = $this->get_jsonapi_attributes( $speaker_resource );
		$source_id  = isset( $speaker_resource['id'] ) ? sanitize_text_field( $speaker_resource['id'] ) : '';
		$name       = $this->attribute_value( $attributes, array( 'name', 'full-name', 'display-name' ) );

		if ( empty( $name ) ) {
			$name = trim(
				$this->attribute_value( $attributes, array( 'first-name', 'first_name' ) ) . ' ' .
				$this->attribute_value( $attributes, array( 'last-name', 'last_name' ) )
			);
		}

		$position     = $this->attribute_value( $attributes, array( 'position', 'job-title', 'designation' ) );
		$organization = $this->attribute_value( $attributes, array( 'organisation', 'organization', 'company' ) );

		return array(
			'id'                  => $source_id ? 'eventyay-' . sanitize_key( $source_id ) : 'eventyay-' . sanitize_title( $name ),
			'eventyay_speaker_id' => $source_id,
			'name'                => sanitize_text_field( $name ),
			'title'               => sanitize_text_field( $position ? $position : $organization ),
			'position'            => sanitize_text_field( $position ),
			'organization'        => sanitize_text_field( $organization ),
			'category'            => sanitize_text_field( $this->attribute_value( $attributes, array( 'category', 'track' ) ) ),
			'image'               => esc_url_raw(
				$this->attribute_value(
					$attributes,
					array(
						'photo-url',
						'thumbnail-image-url',
						'small-image-url',
						'icon-image-url',
						'original-image-url',
						'avatar-url',
					)
				)
			),
			'bio'                 => wp_kses_post( $this->attribute_value( $attributes, array( 'long-biography', 'short-biography', 'biography', 'speaking-experience' ) ) ),
			'social'              => array(
				'linkedin' => esc_url_raw( $this->attribute_value( $attributes, array( 'linkedin', 'linkedin-url' ) ) ),
				'twitter'  => esc_url_raw( $this->attribute_value( $attributes, array( 'twitter', 'twitter-url' ) ) ),
				'github'   => esc_url_raw( $this->attribute_value( $attributes, array( 'github', 'github-url' ) ) ),
				'website'  => esc_url_raw( $this->attribute_value( $attributes, array( 'website', 'website-url' ) ) ),
			),
			'featured'            => false,
			'sessions'            => array(),
			'source'              => 'eventyay',
		);
	}

	/**
	 * Merge a speaker and optional session into a keyed speaker list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speakers Speaker list keyed by normalized source.
	 * @param array $speaker  Speaker data.
	 * @param array $session  Session data.
	 * @return void
	 */
	private function merge_eventyay_speaker( &$speakers, $speaker, $session ) {
		$key = ! empty( $speaker['eventyay_speaker_id'] ) ? 'eventyay:' . $speaker['eventyay_speaker_id'] : 'name:' . sanitize_title( $speaker['name'] );

		if ( empty( $speakers[ $key ] ) ) {
			$speakers[ $key ] = $speaker;
		} else {
			foreach ( array( 'title', 'position', 'organization', 'category', 'image', 'bio' ) as $field ) {
				if ( empty( $speakers[ $key ][ $field ] ) && ! empty( $speaker[ $field ] ) ) {
					$speakers[ $key ][ $field ] = $speaker[ $field ];
				}
			}

			foreach ( $speaker['social'] as $field => $value ) {
				if ( empty( $speakers[ $key ]['social'][ $field ] ) && ! empty( $value ) ) {
					$speakers[ $key ]['social'][ $field ] = $value;
				}
			}
		}

		if ( empty( $session ) ) {
			return;
		}

		$session_ids = wp_list_pluck( $speakers[ $key ]['sessions'], 'id' );
		if ( empty( $session['id'] ) || ! in_array( $session['id'], $session_ids, true ) ) {
			$speakers[ $key ]['sessions'][] = $session;
		}
	}

	/**
	 * Preserve dashboard-only speaker state across Eventyay syncs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $imported Imported Eventyay speakers.
	 * @param array $existing Existing dashboard speakers.
	 * @return array
	 */
	private function merge_dashboard_speaker_state( $imported, $existing ) {
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$state = array();
		foreach ( $existing as $speaker ) {
			if ( ! is_array( $speaker ) ) {
				continue;
			}

			foreach ( $this->get_dashboard_speaker_state_keys( $speaker ) as $key ) {
				$state[ $key ] = array(
					'featured'       => ! empty( $speaker['featured'] ),
					'featured_order' => isset( $speaker['featured_order'] ) ? absint( $speaker['featured_order'] ) : null,
					'image'          => isset( $speaker['image'] ) ? esc_url_raw( $speaker['image'] ) : '',
				);
			}
		}

		foreach ( $imported as &$speaker ) {
			foreach ( $this->get_dashboard_speaker_state_keys( $speaker ) as $key ) {
				if ( ! isset( $state[ $key ] ) ) {
					continue;
				}

				$speaker['featured'] = $state[ $key ]['featured'];
				if ( null !== $state[ $key ]['featured_order'] ) {
					$speaker['featured_order'] = $state[ $key ]['featured_order'];
				}
				if ( empty( $speaker['image'] ) && ! empty( $state[ $key ]['image'] ) ) {
					$speaker['image'] = $state[ $key ]['image'];
				}
				break;
			}
		}
		unset( $speaker );

		foreach ( $existing as $speaker ) {
			if ( ! is_array( $speaker ) || $this->is_eventyay_dashboard_speaker( $speaker ) ) {
				continue;
			}

			$imported[] = $speaker;
		}

		return array_values( $imported );
	}

	/**
	 * Get matching keys used to preserve dashboard speaker state.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker Speaker data.
	 * @return array
	 */
	private function get_dashboard_speaker_state_keys( $speaker ) {
		$keys = array();

		if ( ! empty( $speaker['eventyay_speaker_id'] ) ) {
			$keys[] = 'eventyay:' . sanitize_text_field( $speaker['eventyay_speaker_id'] );
		}

		if ( ! empty( $speaker['id'] ) ) {
			$keys[] = 'id:' . sanitize_text_field( $speaker['id'] );
		}

		if ( ! empty( $speaker['name'] ) ) {
			$keys[] = 'name:' . sanitize_title( $speaker['name'] );
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Determine whether a dashboard speaker record originated from Eventyay.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker Speaker data.
	 * @return bool
	 */
	private function is_eventyay_dashboard_speaker( $speaker ) {
		if ( isset( $speaker['source'] ) && 'eventyay' === $speaker['source'] ) {
			return true;
		}

		return ! empty( $speaker['id'] ) && 0 === strpos( (string) $speaker['id'], 'eventyay-' );
	}

	/**
	 * Upsert synced speakers into the maintained speaker CPT path.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speakers Imported speakers.
	 * @param int   $event_id Event post ID.
	 * @return array
	 */
	private function sync_eventyay_speaker_posts( $speakers, $event_id ) {
		$result = array(
			'created' => 0,
			'updated' => 0,
			'ids'     => array(),
		);

		foreach ( $speakers as $speaker ) {
			$upsert = $this->upsert_eventyay_speaker_post( $speaker );

			if ( is_wp_error( $upsert ) || empty( $upsert['id'] ) ) {
				continue;
			}

			$result['ids'][] = absint( $upsert['id'] );
			if ( ! empty( $upsert['created'] ) ) {
				++$result['created'];
			} else {
				++$result['updated'];
			}
		}

		$result['ids'] = $this->sanitize_eventyay_post_id_list( $result['ids'] );

		if ( $event_id && 'wpfa_event' === get_post_type( $event_id ) && ! empty( $result['ids'] ) ) {
			$previous_speakers = $this->get_eventyay_event_speaker_ids( $event_id );
			$current_speakers  = $this->sanitize_eventyay_post_id_list( array_merge( $previous_speakers, $result['ids'] ) );

			update_post_meta( $event_id, 'wpfa_event_speakers', $current_speakers );
			$this->sync_eventyay_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers );
		}

		return $result;
	}

	/**
	 * Get normalized speaker IDs assigned to an event for Eventyay sync.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	private function get_eventyay_event_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_speakers', true );

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
	private function get_eventyay_speaker_event_ids( $speaker_id ) {
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
	private function sanitize_eventyay_post_id_list( $post_ids ) {
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
	private function sync_eventyay_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
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
	private function add_eventyay_event_to_speaker( $speaker_id, $event_id ) {
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
	private function remove_eventyay_event_from_speaker( $speaker_id, $event_id ) {
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
	 * @param array $speaker Speaker data.
	 * @return array|WP_Error
	 */
	private function upsert_eventyay_speaker_post( $speaker ) {
		if ( empty( $speaker['eventyay_speaker_id'] ) || empty( $speaker['name'] ) ) {
			return new WP_Error(
				'eventyay_speaker_missing_id',
				esc_html__( 'Eventyay speaker is missing an ID or name.', 'wpfaevent' )
			);
		}

		$speaker_id = $this->find_eventyay_speaker_post( $speaker['eventyay_speaker_id'] );
		$post_data  = array(
			'post_title'   => sanitize_text_field( $speaker['name'] ),
			'post_type'    => 'wpfa_speaker',
			'post_status'  => 'publish',
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
	private function find_eventyay_speaker_post( $eventyay_speaker_id ) {
		$speaker_ids = get_posts(
			array(
				'post_type'      => 'wpfa_speaker',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Eventyay IDs are stored in speaker post meta for sync idempotency.
				'meta_key'       => '_wpfa_eventyay_speaker_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Eventyay IDs are stored in speaker post meta for sync idempotency.
				'meta_value'     => sanitize_text_field( $eventyay_speaker_id ),
			)
		);

		return ! empty( $speaker_ids[0] ) ? absint( $speaker_ids[0] ) : 0;
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
	private function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Get a JSON:API resource's attributes array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $jsonapi_resource JSON:API resource.
	 * @return array
	 */
	private function get_jsonapi_attributes( $jsonapi_resource ) {
		return isset( $jsonapi_resource['attributes'] ) && is_array( $jsonapi_resource['attributes'] ) ? $jsonapi_resource['attributes'] : array();
	}

	/**
	 * Get a scalar attribute value by trying multiple possible keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Attribute map.
	 * @param array $keys       Candidate keys.
	 * @return string
	 */
	private function attribute_value( $attributes, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ) {
				return (string) $attributes[ $key ];
			}
		}

		return '';
	}

	/**
	 * Index JSON:API included resources by type and ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resources Included resources.
	 * @return array
	 */
	private function index_jsonapi_resources( $resources ) {
		$index = array();

		if ( ! is_array( $resources ) ) {
			return $index;
		}

		foreach ( $resources as $resource ) {
			if ( ! $this->is_jsonapi_resource( $resource ) ) {
				continue;
			}

			$key           = $this->jsonapi_resource_key( $resource['type'], $resource['id'] );
			$index[ $key ] = $resource;
		}

		return $index;
	}

	/**
	 * Resolve a relationship identifier from included resources.
	 *
	 * @since 1.0.0
	 *
	 * @param array $resource_identifier JSON:API resource identifier.
	 * @param array $included            Indexed included resources.
	 * @return array
	 */
	private function resolve_jsonapi_resource( $resource_identifier, $included ) {
		if ( ! $this->is_jsonapi_resource( $resource_identifier ) ) {
			return array();
		}

		$type       = strtolower( (string) $resource_identifier['type'] );
		$id         = (string) $resource_identifier['id'];
		$candidates = array( $type );

		if ( 's' === substr( $type, -1 ) ) {
			$candidates[] = substr( $type, 0, -1 );
		} else {
			$candidates[] = $type . 's';
		}

		foreach ( array_unique( $candidates ) as $candidate_type ) {
			$key = $this->jsonapi_resource_key( $candidate_type, $id );
			if ( isset( $included[ $key ] ) ) {
				return $included[ $key ];
			}
		}

		if ( ! empty( $resource_identifier['attributes'] ) ) {
			return $resource_identifier;
		}

		return array();
	}

	/**
	 * Get relationship resources as a list.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $jsonapi_resource JSON:API resource.
	 * @param string $name             Relationship name.
	 * @return array
	 */
	private function get_jsonapi_relationship_resources( $jsonapi_resource, $name ) {
		if ( empty( $jsonapi_resource['relationships'][ $name ] ) || ! is_array( $jsonapi_resource['relationships'][ $name ] ) ) {
			return array();
		}

		$relationship = $jsonapi_resource['relationships'][ $name ];
		if ( ! array_key_exists( 'data', $relationship ) || null === $relationship['data'] ) {
			return array();
		}

		if ( $this->is_jsonapi_resource( $relationship['data'] ) ) {
			return array( $relationship['data'] );
		}

		return is_array( $relationship['data'] ) ? $relationship['data'] : array();
	}

	/**
	 * Determine whether an array resembles a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $maybe_resource Possible resource.
	 * @return bool
	 */
	private function is_jsonapi_resource( $maybe_resource ) {
		return is_array( $maybe_resource ) && isset( $maybe_resource['type'], $maybe_resource['id'] );
	}

	/**
	 * Compare a JSON:API resource type, accepting singular or plural forms.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $jsonapi_resource JSON:API resource.
	 * @param string $type             Expected singular type.
	 * @return bool
	 */
	private function jsonapi_type_is( $jsonapi_resource, $type ) {
		if ( empty( $jsonapi_resource['type'] ) ) {
			return false;
		}

		$resource_type = strtolower( (string) $jsonapi_resource['type'] );
		$type          = strtolower( $type );

		return $resource_type === $type || $resource_type === $type . 's';
	}

	/**
	 * Build an index key for a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Resource type.
	 * @param string $id   Resource ID.
	 * @return string
	 */
	private function jsonapi_resource_key( $type, $id ) {
		return strtolower( (string) $type ) . ':' . (string) $id;
	}

	/**
	 * Format an Eventyay date-time value as a date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date-time value.
	 * @return string
	 */
	private function format_eventyay_date( $value ) {
		$timestamp = strtotime( $value );

		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '';
	}

	/**
	 * Format an Eventyay date-time value as a time.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date-time value.
	 * @return string
	 */
	private function format_eventyay_time( $value ) {
		$timestamp = strtotime( $value );

		return $timestamp ? gmdate( 'H:i', $timestamp ) : '';
	}

	/**
	 * Read a dashboard JSON file from the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private function read_dashboard_json_file( $filename, $fallback ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $fallback;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) || ! $filesystem->exists( $path ) ) {
			return $fallback;
		}

		$contents = $filesystem->get_contents( $path );
		if ( false === $contents || '' === trim( $contents ) ) {
			return $fallback;
		}

		$decoded = json_decode( $contents, true );

		return ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : $fallback;
	}

	/**
	 * Write a dashboard JSON file into the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $data     Data to write.
	 * @return true|WP_Error
	 */
	private function write_dashboard_json_file( $filename, $data ) {
		$path = $this->get_dashboard_json_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$filesystem = $this->get_wp_filesystem();
		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error(
				'eventyay_json_encode_failed',
				esc_html__( 'Could not encode synced Eventyay speakers.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		$chmod_file = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		if ( ! $filesystem->put_contents( $path, $json, $chmod_file ) ) {
			return new WP_Error(
				'eventyay_json_write_failed',
				esc_html__( 'Could not write synced Eventyay speakers to the dashboard data file.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Get a safe dashboard JSON path under the uploads data directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @return string|WP_Error
	 */
	private function get_dashboard_json_path( $filename ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error(
				'eventyay_upload_dir_failed',
				esc_html__( 'Could not access the WordPress uploads directory.', 'wpfaevent' ),
				array(
					'status'  => 500,
					'details' => $upload_dir['error'],
				)
			);
		}

		$data_dir = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';
		if ( ! wp_mkdir_p( $data_dir ) ) {
			return new WP_Error(
				'eventyay_data_dir_failed',
				esc_html__( 'Could not create the dashboard data directory.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return trailingslashit( $data_dir ) . sanitize_file_name( $filename );
	}

	/**
	 * Initialize and return the WordPress filesystem API.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Filesystem_Base|WP_Error
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return new WP_Error(
				'eventyay_filesystem_failed',
				esc_html__( 'Could not initialize the WordPress filesystem.', 'wpfaevent' ),
				array( 'status' => 500 )
			);
		}

		return $wp_filesystem;
	}

	/**
	 * Truncate a string for structured error output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value String value.
	 * @return string
	 */
	private function truncate_string( $value ) {
		$value = wp_strip_all_tags( (string) $value );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 1000 );
		}

		return substr( $value, 0, 1000 );
	}
}
