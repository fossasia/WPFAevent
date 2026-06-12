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
		$time       = get_post_meta( $post->ID, 'wpfa_event_time', true );
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$lead_text  = get_post_meta( $post->ID, 'wpfa_event_lead_text', true );
		$reg_link   = get_post_meta( $post->ID, 'wpfa_event_registration_link', true );
		$cfs_link   = get_post_meta( $post->ID, 'wpfa_event_cfs_link', true );
		$speakers   = $this->get_event_speaker_ids( $post->ID );

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
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					if ( $speaker_ids ) :
						?>
						<select name="wpfa_event_speakers[]" id="wpfa_event_speakers" multiple class="wpfaevent-relationship-select wpfaevent-speakers-select">
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
		$events       = $this->sanitize_post_id_list(
			array_merge(
				$this->get_speaker_event_ids( $post->ID ),
				$this->get_events_linked_to_speaker( $post->ID )
			)
		);
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
			<tr>
				<th><label for="wpfa_speaker_events"><?php esc_html_e( 'Related Events', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					$event_ids = get_posts(
						array(
							'post_type'      => 'wpfa_event',
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					if ( $event_ids ) :
						?>
						<select name="wpfa_speaker_events[]" id="wpfa_speaker_events" multiple class="wpfaevent-relationship-select wpfaevent-events-select">
							<?php foreach ( $event_ids as $event_id ) : ?>
								<?php $is_selected = in_array( $event_id, $events, true ); ?>
								<option value="<?php echo esc_attr( $event_id ); ?>" <?php selected( $is_selected, true ); ?>>
									<?php echo esc_html( get_the_title( $event_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple events.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No events found. Create events first.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
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

		// List of all meta fields to save.
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
				$raw_value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

				// Special handling for URL fields.
				if ( in_array( $field, array( 'wpfa_event_url', 'wpfa_event_registration_link', 'wpfa_event_cfs_link' ), true ) ) {
					$value = esc_url_raw( $raw_value );
				} else {
					$value = $raw_value;
				}

				update_post_meta( $post_id, $field, $value );
			}
		}

		$previous_speakers = $this->get_event_speaker_ids( $post_id );
		$speakers          = array();

		// Handle speakers array.
		if ( isset( $_POST['wpfa_event_speakers'] ) && is_array( $_POST['wpfa_event_speakers'] ) ) {
			$speakers = $this->sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_event_speakers'] )
				)
			);
		}

		$this->update_post_id_list_meta( $post_id, 'wpfa_event_speakers', $speakers );

		$this->sync_event_speaker_relationships( $post_id, $previous_speakers, $speakers );
	}

	/**
	 * Get normalized speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int> Speaker post IDs.
	 */
	private function get_event_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_speakers', true );

		return $this->sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Get normalized event IDs assigned to a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int> Event post IDs.
	 */
	private function get_speaker_event_ids( $speaker_id ) {
		$event_ids = get_post_meta( $speaker_id, 'wpfa_speaker_events', true );

		return $this->sanitize_post_id_list( $event_ids );
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int> Sanitized post IDs.
	 */
	private function sanitize_post_id_list( $post_ids ) {
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array( $post_ids );
		}

		$post_ids = array_map( 'absint', $post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Save a normalized post ID list as post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $post_id  Post ID.
	 * @param string     $meta_key Meta key.
	 * @param array<int> $post_ids Post IDs to save.
	 * @return void
	 */
	private function update_post_id_list_meta( $post_id, $meta_key, $post_ids ) {
		$post_ids = $this->sanitize_post_id_list( $post_ids );

		if ( empty( $post_ids ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $post_ids );
	}

	/**
	 * Sync speaker-side event relationship meta after an event is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $event_id          Event post ID.
	 * @param array<int> $previous_speakers Speaker IDs before save.
	 * @param array<int> $current_speakers  Speaker IDs after save.
	 * @return void
	 */
	private function sync_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
		$event_id          = absint( $event_id );
		$previous_speakers = $this->sanitize_post_id_list( $previous_speakers );
		$current_speakers  = $this->sanitize_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		$previous_speakers = array_values(
			array_unique(
				array_merge(
					$previous_speakers,
					$this->get_speakers_linked_to_event( $event_id )
				)
			)
		);

		$removed_speakers = array_diff( $previous_speakers, $current_speakers );

		foreach ( $removed_speakers as $speaker_id ) {
			$this->remove_event_from_speaker( $speaker_id, $event_id );
		}

		foreach ( $current_speakers as $speaker_id ) {
			$this->add_event_to_speaker( $speaker_id, $event_id );
		}
	}

	/**
	 * Find speakers whose speaker-side event meta includes an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int> Speaker post IDs.
	 */
	private function get_speakers_linked_to_event( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array();
		}

		$batch_size   = 100;
		$current_page = 1;
		$speaker_ids  = array();

		do {
			$batch_ids = get_posts(
				array(
					'post_type'              => 'wpfa_speaker',
					'post_status'            => 'any',
					'posts_per_page'         => $batch_size,
					'paged'                  => $current_page,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( empty( $batch_ids ) ) {
				break;
			}

			$batch_count = count( $batch_ids );
			update_meta_cache( 'post', $batch_ids );

			foreach ( $batch_ids as $speaker_id ) {
				if ( in_array( $event_id, $this->get_speaker_event_ids( $speaker_id ), true ) ) {
					$speaker_ids[] = $speaker_id;
				}
			}

			++$current_page;
		} while ( $batch_count === $batch_size );

		return $this->sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Add an event ID to a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private function add_event_to_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$event_ids   = $this->get_speaker_event_ids( $speaker_id );
		$event_ids[] = $event_id;

		$this->update_post_id_list_meta( $speaker_id, 'wpfa_speaker_events', $event_ids );
	}

	/**
	 * Remove an event ID from a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private function remove_event_from_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$event_ids = array_diff( $this->get_speaker_event_ids( $speaker_id ), array( $event_id ) );

		$this->update_post_id_list_meta( $speaker_id, 'wpfa_speaker_events', $event_ids );
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

		$previous_events = $this->get_speaker_event_ids( $post_id );
		$events          = array();

		if ( isset( $_POST['wpfa_speaker_events'] ) && is_array( $_POST['wpfa_speaker_events'] ) ) {
			$events = $this->sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_speaker_events'] )
				)
			);
		}

		$this->update_post_id_list_meta( $post_id, 'wpfa_speaker_events', $events );
		$this->sync_speaker_event_relationships( $post_id, $previous_events, $events );
	}

	/**
	 * Sync event-side speaker relationship meta after a speaker is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $speaker_id      Speaker post ID.
	 * @param array<int> $previous_events Event IDs before save.
	 * @param array<int> $current_events  Event IDs after save.
	 * @return void
	 */
	private function sync_speaker_event_relationships( $speaker_id, $previous_events, $current_events ) {
		$speaker_id      = absint( $speaker_id );
		$previous_events = $this->sanitize_post_id_list( $previous_events );
		$current_events  = $this->sanitize_post_id_list( $current_events );

		if ( ! $speaker_id || 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$previous_events = array_values(
			array_unique(
				array_merge(
					$previous_events,
					$this->get_events_linked_to_speaker( $speaker_id )
				)
			)
		);

		$removed_events = array_diff( $previous_events, $current_events );

		foreach ( $removed_events as $event_id ) {
			$this->remove_speaker_from_event( $event_id, $speaker_id );
		}

		foreach ( $current_events as $event_id ) {
			$this->add_speaker_to_event( $event_id, $speaker_id );
		}
	}

	/**
	 * Find events whose event-side speaker meta includes a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int> Event post IDs.
	 */
	private function get_events_linked_to_speaker( $speaker_id ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id ) {
			return array();
		}

		$batch_size   = 100;
		$current_page = 1;
		$event_ids    = array();

		do {
			$batch_ids = get_posts(
				array(
					'post_type'              => 'wpfa_event',
					'post_status'            => 'any',
					'posts_per_page'         => $batch_size,
					'paged'                  => $current_page,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( empty( $batch_ids ) ) {
				break;
			}

			$batch_count = count( $batch_ids );
			update_meta_cache( 'post', $batch_ids );

			foreach ( $batch_ids as $event_id ) {
				if ( in_array( $speaker_id, $this->get_event_speaker_ids( $event_id ), true ) ) {
					$event_ids[] = $event_id;
				}
			}

			++$current_page;
		} while ( $batch_count === $batch_size );

		return $this->sanitize_post_id_list( $event_ids );
	}

	/**
	 * Add a speaker ID to an event's related speakers.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
	private function add_speaker_to_event( $event_id, $speaker_id ) {
		$event_id   = absint( $event_id );
		$speaker_id = absint( $speaker_id );

		if ( ! $event_id || ! $speaker_id ) {
			return;
		}

		if ( 'wpfa_event' !== get_post_type( $event_id ) ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$speaker_ids   = $this->get_event_speaker_ids( $event_id );
		$speaker_ids[] = $speaker_id;

		$this->update_post_id_list_meta( $event_id, 'wpfa_event_speakers', $speaker_ids );
	}

	/**
	 * Remove a speaker ID from an event's related speakers.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
	private function remove_speaker_from_event( $event_id, $speaker_id ) {
		$event_id   = absint( $event_id );
		$speaker_id = absint( $speaker_id );

		if ( ! $event_id || ! $speaker_id ) {
			return;
		}

		if ( 'wpfa_event' !== get_post_type( $event_id ) ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$speaker_ids = array_diff( $this->get_event_speaker_ids( $event_id ), array( $speaker_id ) );

		$this->update_post_id_list_meta( $event_id, 'wpfa_event_speakers', $speaker_ids );
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
}
