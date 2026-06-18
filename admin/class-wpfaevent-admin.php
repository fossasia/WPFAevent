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
	 * Eventyay REST API import service.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Wpfaevent_Eventyay_Importer
	 */
	private $eventyay_importer;

	/**
	 * Eventyay JSON:API dashboard sync service.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Wpfaevent_Eventyay_Ajax_Sync
	 */
	private $eventyay_ajax_sync;

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
	 * Get the Eventyay REST API import service.
	 *
	 * @since 1.0.0
	 *
	 * @return Wpfaevent_Eventyay_Importer
	 */
	private function get_eventyay_importer() {
		if ( ! $this->eventyay_importer instanceof Wpfaevent_Eventyay_Importer ) {
			$this->eventyay_importer = new Wpfaevent_Eventyay_Importer();
		}

		return $this->eventyay_importer;
	}

	/**
	 * Get the Eventyay JSON:API dashboard sync service.
	 *
	 * @since 1.0.0
	 *
	 * @return Wpfaevent_Eventyay_Ajax_Sync
	 */
	private function get_eventyay_ajax_sync() {
		if ( ! $this->eventyay_ajax_sync instanceof Wpfaevent_Eventyay_Ajax_Sync ) {
			$this->eventyay_ajax_sync = new Wpfaevent_Eventyay_Ajax_Sync();
		}

		return $this->eventyay_ajax_sync;
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

		$import_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'edit.php?post_type=wpfa_event&page=wpfaevent-import-events' ) ),
			esc_html__( 'Import Events', 'wpfaevent' )
		);

		array_unshift( $links, $settings_link, $import_link );
		return $links;
	}

	/**
	 * Register the settings page in WordPress admin.
	 *
	 * @since    1.0.0
	 */
	public function register_settings_page() {
		add_menu_page(
			esc_html__( 'WPFAEvent Settings', 'wpfaevent' ),
			esc_html__( 'WPFAEvent', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_MANAGE_SETTINGS,
			'wpfaevent-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'wpfaevent-settings',
			esc_html__( 'WPFAEvent Settings', 'wpfaevent' ),
			esc_html__( 'Settings', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_MANAGE_SETTINGS,
			'wpfaevent-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Import Events from Eventyay', 'wpfaevent' ),
			esc_html__( 'Import Events', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_IMPORT_EVENTYAY,
			'wpfaevent-import-events',
			array( $this, 'render_eventyay_import_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Update Events from Eventyay', 'wpfaevent' ),
			esc_html__( 'Update Events', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_IMPORT_EVENTYAY,
			'wpfaevent-update-events',
			array( $this, 'render_eventyay_update_page' )
		);
	}

	/**
	 * Render the settings page placeholder.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! Wpfaevent_Roles::current_user_can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$can_manage_access = Wpfaevent_Roles::current_user_can_manage_plugin_access();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $can_manage_access ) : ?>
				<?php settings_errors( Wpfaevent_Roles::SETTINGS_GROUP ); ?>
				<div class="card" style="max-width: 960px;">
					<h2><?php esc_html_e( 'Event Plugin Access', 'wpfaevent' ); ?></h2>
					<p><?php esc_html_e( 'Assign Event Organizer or Event Contributor access to existing WordPress users. Their normal WordPress role stays unchanged.', 'wpfaevent' ); ?></p>
					<p class="description"><?php esc_html_e( 'Administrators always have full plugin access. Organizers can import and publish. Contributors can edit existing event and speaker content only.', 'wpfaevent' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php
						settings_fields( Wpfaevent_Roles::SETTINGS_GROUP );
						$this->render_user_access_settings_fields();
						submit_button( __( 'Save Event Plugin Access', 'wpfaevent' ) );
						?>
					</form>
				</div>
			<?php endif; ?>

			<div class="card">
				<h2><?php esc_html_e( 'Plugin Information', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'This page is reserved for the future WPFAEvent admin dashboard and shared plugin settings.', 'wpfaevent' ); ?></p>
				<?php if ( Wpfaevent_Roles::current_user_can_import_eventyay() ) : ?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpfa_event&page=wpfaevent-import-events' ) ); ?>">
							<?php esc_html_e( 'Open Eventyay Import', 'wpfaevent' ); ?>
						</a>
					</p>
				<?php endif; ?>
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

	/**
	 * Register plugin settings stored under WPFAEvent -> Settings.
	 *
	 * @since 1.0.0
	 */
	public function register_plugin_settings() {
		register_setting(
			Wpfaevent_Roles::SETTINGS_GROUP,
			Wpfaevent_Roles::ACCESS_LEVELS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Wpfaevent_Roles', 'sanitize_user_access_levels' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Render the per-user plugin access assignment table.
	 *
	 * @since 1.0.0
	 */
	private function render_user_access_settings_fields() {
		$access_labels   = Wpfaevent_Roles::get_access_level_labels();
		$assigned_levels = Wpfaevent_Roles::get_user_access_levels();
		$users           = get_users(
			array(
				'fields'  => 'all',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);

		if ( empty( $users ) ) {
			echo '<p>' . esc_html__( 'No WordPress users are available to assign.', 'wpfaevent' ) . '</p>';
			return;
		}

		$this->render_user_access_level_guide();
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'User', 'wpfaevent' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Email', 'wpfaevent' ); ?></th>
					<th scope="col"><?php esc_html_e( 'WordPress role', 'wpfaevent' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Event plugin access', 'wpfaevent' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $users as $user ) : ?>
					<?php
					$user_id        = absint( $user->ID );
					$role_names     = array_map( 'translate_user_role', array_filter( (array) $user->roles ) );
					$wordpress_role = ! empty( $role_names ) ? implode( ', ', $role_names ) : __( 'No role', 'wpfaevent' );
					$assigned_level = isset( $assigned_levels[ $user_id ] ) ? $assigned_levels[ $user_id ] : '';
					$field_name     = Wpfaevent_Roles::ACCESS_LEVELS_OPTION . '[' . $user_id . ']';
					?>
					<tr>
						<td><?php echo esc_html( $user->display_name ? $user->display_name : $user->user_login ); ?></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( $wordpress_role ); ?></td>
						<td>
							<?php if ( Wpfaevent_Roles::user_is_site_administrator( $user ) ) : ?>
								<em><?php esc_html_e( 'Full access (Administrator)', 'wpfaevent' ); ?></em>
							<?php else : ?>
								<label class="screen-reader-text" for="<?php echo esc_attr( 'wpfaevent-access-' . $user_id ); ?>">
									<?php
									printf(
										/* translators: %s: user display name. */
										esc_html__( 'Event plugin access for %s', 'wpfaevent' ),
										esc_html( $user->display_name )
									);
									?>
								</label>
								<select id="<?php echo esc_attr( 'wpfaevent-access-' . $user_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>">
									<?php foreach ( $access_labels as $level => $label ) : ?>
										<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $assigned_level, $level ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the access-level reference guide shown above the assignment table.
	 *
	 * @since 1.0.0
	 */
	private function render_user_access_level_guide() {
		?>
		<h3 class="title" style="margin-top: 1.5em;"><?php esc_html_e( 'Access level guide', 'wpfaevent' ); ?></h3>
		<table class="widefat striped" style="margin-bottom: 1em;">
			<thead>
				<tr>
					<th scope="col" style="width: 28%;"><?php esc_html_e( 'Access level', 'wpfaevent' ); ?></th>
					<th scope="col"><?php esc_html_e( 'What they can do', 'wpfaevent' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Administrator', 'wpfaevent' ); ?></strong></td>
					<td><?php esc_html_e( 'Full plugin access automatically. Can import from Eventyay, publish and delete events and speakers, and open WPFAEvent settings.', 'wpfaevent' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php echo esc_html( Wpfaevent_Roles::get_access_level_labels()[ Wpfaevent_Roles::ACCESS_ORGANIZER ] ); ?></strong></td>
					<td><?php esc_html_e( 'Import and sync events from Eventyay, publish events and speakers, delete content, and open WPFAEvent settings. Does not change their WordPress role.', 'wpfaevent' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php echo esc_html( Wpfaevent_Roles::get_access_level_labels()[ Wpfaevent_Roles::ACCESS_CONTRIBUTOR ] ); ?></strong></td>
					<td><?php esc_html_e( 'Edit existing event and speaker content only. Cannot import, publish, delete, or change plugin settings.', 'wpfaevent' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php echo esc_html( Wpfaevent_Roles::get_access_level_labels()[''] ); ?></strong></td>
					<td><?php esc_html_e( 'No access to WPFAEvent features. The user keeps their normal WordPress permissions only.', 'wpfaevent' ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Register Eventyay import options.
	 *
	 * @since 1.0.0
	 */
	public function register_eventyay_import_settings() {
		register_setting(
			'wpfaevent_eventyay_import',
			'wpfaevent_eventyay_import_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_eventyay_import_settings' ),
				'default'           => $this->get_eventyay_importer()->get_eventyay_import_default_settings(),
			)
		);
	}

	/**
	 * Sanitize Eventyay import options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input Raw option input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_eventyay_import_settings( $input ) {
		return $this->get_eventyay_importer()->sanitize_eventyay_import_settings( $input );
	}

	/**
	 * Render the Eventyay import page.
	 *
	 * @since 1.0.0
	 */
	public function render_eventyay_import_page() {
		$this->get_eventyay_importer()->render_settings_page();
	}

	/**
	 * Render the Eventyay update page.
	 *
	 * @since 1.0.0
	 */
	public function render_eventyay_update_page() {
		$this->get_eventyay_importer()->render_update_events_page();
	}

	/**
	 * Handle Eventyay JSON:API speaker sync for the admin dashboard.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_sync_eventyay() {
		$this->get_eventyay_ajax_sync()->ajax_sync_eventyay();
	}

	/**
	 * Handle Eventyay import form submissions.
	 *
	 * @since 1.0.0
	 */
	public function handle_eventyay_events_import() {
		$this->get_eventyay_importer()->handle_eventyay_events_import();
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
		$start_time = get_post_meta( $post->ID, 'wpfa_event_start_time', true );
		$end_time   = get_post_meta( $post->ID, 'wpfa_event_end_time', true );

		if ( '' === $start_time ) {
			$start_time = get_post_meta( $post->ID, 'wpfa_event_time', true );
		}

		$timezone  = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_timezone( $post->ID ) : wp_timezone_string();
		$all_day   = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_all_day( $post->ID ) : false;
		$location  = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url       = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$lead_text = get_post_meta( $post->ID, 'wpfa_event_lead_text', true );
		$reg_link  = get_post_meta( $post->ID, 'wpfa_event_registration_link', true );
		$cfs_link  = get_post_meta( $post->ID, 'wpfa_event_cfs_link', true );
		$speakers  = $this->get_event_speaker_ids( $post->ID );

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
				<th><label for="wpfa_event_timezone"><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></label></th>
				<td>
					<select id="wpfa_event_timezone" name="wpfa_event_timezone" class="regular-text">
						<?php echo wp_timezone_choice( $timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes timezone option markup. ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used to interpret timed events and calendar exports. Leave as the site timezone when the event does not need a separate timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Time Format', 'wpfaevent' ); ?></th>
				<td>
					<label for="wpfa_event_all_day">
						<input type="checkbox" id="wpfa_event_all_day" name="wpfa_event_all_day" value="1" <?php checked( $all_day ); ?>>
						<?php esc_html_e( 'All-day event', 'wpfaevent' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'All-day events export as date-only calendar entries. Timed events use the event timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_start_time"><?php esc_html_e( 'Start Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_start_time" name="wpfa_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_time"><?php esc_html_e( 'End Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_end_time" name="wpfa_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text"></td>
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

		$posted_start_date = isset( $_POST['wpfa_event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_date'] ) ) : '';
		$posted_end_date   = isset( $_POST['wpfa_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_date'] ) ) : '';
		$posted_timezone   = isset( $_POST['wpfa_event_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_timezone'] ) ) : '';
		$start_date        = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_date_value( $posted_start_date ) : $posted_start_date;
		$end_date          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_date_value( $posted_end_date ) : $posted_end_date;
		$timezone          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_timezone( $posted_timezone ) : $posted_timezone;

		if ( isset( $_POST['wpfa_event_start_date'] ) ) {
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_date', $start_date );
		}

		if ( isset( $_POST['wpfa_event_end_date'] ) ) {
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_date', $end_date );
		}

		if ( '' !== $timezone ) {
			update_post_meta( $post_id, 'wpfa_event_timezone', $timezone );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_timezone' );
		}

		$all_day = isset( $_POST['wpfa_event_all_day'] );
		update_post_meta( $post_id, 'wpfa_event_all_day', $all_day ? '1' : '0' );

		$posted_start_time = isset( $_POST['wpfa_event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_time'] ) ) : '';
		$posted_end_time   = isset( $_POST['wpfa_event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_time'] ) ) : '';
		$start_time        = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_time_value( $posted_start_time ) : $posted_start_time;
		$end_time          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_time_value( $posted_end_time ) : $posted_end_time;

		if ( $all_day ) {
			delete_post_meta( $post_id, 'wpfa_event_start_time' );
			delete_post_meta( $post_id, 'wpfa_event_time' );
			delete_post_meta( $post_id, 'wpfa_event_end_time' );
			delete_post_meta( $post_id, 'wpfa_event_starts_at' );
			delete_post_meta( $post_id, 'wpfa_event_ends_at' );
		} else {
			$end_date_for_datetime = '' !== $end_date ? $end_date : $start_date;

			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_time', $start_time );
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_time', $start_time );
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_time', $end_time );
			$this->update_or_delete_post_meta(
				$post_id,
				'wpfa_event_starts_at',
				class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_datetime_value( $start_date, $start_time, $timezone ) : ''
			);
			$this->update_or_delete_post_meta(
				$post_id,
				'wpfa_event_ends_at',
				class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_datetime_value( $end_date_for_datetime, $end_time, $timezone ) : ''
			);
		}

		$meta_fields = array(
			'wpfa_event_location',
			'wpfa_event_lead_text',
			'wpfa_event_url',
			'wpfa_event_registration_link',
			'wpfa_event_cfs_link',
		);

		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$raw_value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

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

		Wpfaevent_Meta_Event::sync_event_speaker_relationships( $post_id, $previous_speakers, $speakers );
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
	 * Update a meta key when it has content, otherwise delete it.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param string $value   Meta value.
	 * @return void
	 */
	private function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
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
}
