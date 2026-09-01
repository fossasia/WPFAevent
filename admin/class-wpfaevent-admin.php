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

		wp_enqueue_style( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'css/wpfaevent-admin.css', array(), $this->version, 'all' );

		$screen = get_current_screen();
		if ( $screen && ( 'wpfa_speaker' === $screen->post_type || 'edit-wpfa_speaker' === $screen->id || 'wpfa_event' === $screen->post_type || false !== strpos( $screen->id, 'wpfaevent-sponsors' ) || false !== strpos( $screen->id, 'wpfaevent-exhibitors' ) ) ) {
			wp_enqueue_style( $this->plugin_name . '-speaker-dashboard', plugin_dir_url( __FILE__ ) . 'css/speaker-dashboard.css', array(), $this->version, 'all' );
		}
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

		wp_enqueue_script( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'js/wpfaevent-admin.js', array( 'jquery' ), $this->version, false );
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
			esc_html__( 'Import Event', 'wpfaevent' )
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
			esc_html__( 'Import Event from Eventyay', 'wpfaevent' ),
			esc_html__( 'Import Event', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_IMPORT_EVENTYAY,
			'wpfaevent-import-events',
			array( $this, 'render_eventyay_import_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Update Event from Eventyay', 'wpfaevent' ),
			esc_html__( 'Update Event', 'wpfaevent' ),
			Wpfaevent_Roles::CAP_IMPORT_EVENTYAY,
			'wpfaevent-update-events',
			array( $this, 'render_eventyay_update_page' )
		);
	}

	/**
	 * Remove taxonomy submenu items from the Events admin menu.
	 *
	 * @since 1.0.0
	 */
	public function remove_event_taxonomy_submenus() {
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'edit-tags.php?taxonomy=wpfa_event_track&post_type=wpfa_event' );
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'edit-tags.php?taxonomy=wpfa_event_tag&post_type=wpfa_event' );
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'edit.php?post_type=wpfa_speaker' );
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'wpfaevent-update-events' );
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'wpfaevent-sponsors' );
		remove_submenu_page( 'edit.php?post_type=wpfa_event', 'wpfaevent-exhibitors' );
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
				'autoload'          => false,
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

	/**
	 * Render a scope filter on the Speakers admin list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Current admin list post type.
	 */
	public function render_speaker_event_filter( $post_type = '' ) {
		if ( 'wpfa_speaker' !== $post_type ) {
			return;
		}

		$current_event = $this->get_current_speaker_admin_event_filter();
		$events        = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $events ) ) {
			return;
		}
		?>
		<label class="screen-reader-text" for="wpfa_speaker_event"><?php esc_html_e( 'Filter speakers by event', 'wpfaevent' ); ?></label>
		<select name="wpfa_speaker_event" id="wpfa_speaker_event">
			<option value="0"><?php esc_html_e( 'Site speakers', 'wpfaevent' ); ?></option>
			<?php foreach ( $events as $event ) : ?>
				<option value="<?php echo esc_attr( (string) absint( $event->ID ) ); ?>" <?php selected( $current_event, absint( $event->ID ) ); ?>>
					<?php echo esc_html( sprintf( /* translators: %s: Event title. */ __( 'Event: %s', 'wpfaevent' ), get_the_title( $event ) ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wpfa_view'] ) && 'table' === $_GET['wpfa_view'] ) {
			echo '<input type="hidden" name="wpfa_view" value="table">';
		}
	}

	/**
	 * Filter the Speakers admin query by ownership scope.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Admin posts query.
	 */
	public function filter_speaker_admin_list( $query ) {
		if ( ! $query instanceof WP_Query || ! $this->is_speaker_admin_list_query( $query ) ) {
			return;
		}

		$scope             = $this->get_current_speaker_admin_scope();
		$event_filter      = $this->get_current_speaker_admin_event_filter();
		$event_speaker_ids = Wpfaevent_Event_Speaker_Relation_Manager::get_all_event_owned_speaker_ids();

		if ( $event_filter ) {
			$filtered_speaker_ids = Wpfaevent_Event_Speaker_Relation_Manager::get_admin_event_speaker_ids( $event_filter );

			if ( empty( $filtered_speaker_ids ) ) {
				$query->set( 'post__in', array( 0 ) );
				return;
			}

			$query->set( 'post__in', $filtered_speaker_ids );
			return;
		}

		if ( 'event' === $scope ) {
			if ( empty( $event_speaker_ids ) ) {
				$query->set( 'post__in', array( 0 ) );
				return;
			}

			$query->set( 'post__in', $event_speaker_ids );
			return;
		}

		if ( 'all' === $scope || empty( $event_speaker_ids ) ) {
			return;
		}

		$query->set( 'post__not_in', $event_speaker_ids );
	}

	/**
	 * Add custom view links for the Speakers admin list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $views Existing list table views.
	 * @return array
	 */
	public function filter_speaker_admin_views( $views ) {
		unset( $views );
		$current_scope = $this->get_current_speaker_admin_scope();
		$scope_options = $this->get_speaker_admin_scope_options();
		$custom_views  = array();

		foreach ( $scope_options as $scope => $label ) {
			$args = array(
				'post_type'               => 'wpfa_speaker',
				'wpfaevent_speaker_scope' => $scope,
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wpfa_view'] ) && 'table' === $_GET['wpfa_view'] ) {
				$args['wpfa_view'] = 'table';
			}

			$url = add_query_arg( $args, admin_url( 'edit.php' ) );

			$custom_views[ 'wpfaevent_scope_' . $scope ] = sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( $url ),
				$scope === $current_scope ? 'class="current" aria-current="page"' : '',
				esc_html( $label )
			);
		}

		return $custom_views;
	}

	/**
	 * Intercept the Speakers admin list screen to render our custom dashboard layout.
	 *
	 * @since 1.0.0
	 */
	public function intercept_speaker_list_screen() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-wpfa_speaker' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wpfa_view'] ) && 'table' === $_GET['wpfa_view'] ) {
			return;
		}

		$event_filter = $this->get_current_speaker_admin_event_filter();

		if ( $event_filter ) {
			$db_speaker_ids       = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_admin_event_speaker_ids( $event_filter ) : array();
			$total_speakers_count = count( $db_speaker_ids );
			$event_owned_count    = $total_speakers_count;
			$standalone_count     = 0;

			$speaker_cat_ids = array();
			if ( ! empty( $db_speaker_ids ) ) {
				foreach ( $db_speaker_ids as $sp_id ) {
					$terms = wp_get_post_terms( $sp_id, 'wpfa_speaker_category', array( 'fields' => 'ids' ) );
					if ( is_array( $terms ) ) {
						$speaker_cat_ids = array_merge( $speaker_cat_ids, $terms );
					}
				}
			}
			$speaker_cat_ids        = array_unique( $speaker_cat_ids );
			$total_categories_count = count( $speaker_cat_ids );

			$speakers_preview = array();
			if ( ! empty( $db_speaker_ids ) ) {
				$speakers_preview = get_posts(
					array(
						'post_type'      => 'wpfa_speaker',
						'post_status'    => 'any',
						'post__in'       => $db_speaker_ids,
						'posts_per_page' => 5,
						'orderby'        => 'post__in',
					)
				);
			}

			$categories_preview = array();
			if ( ! empty( $speaker_cat_ids ) ) {
				$categories_preview = get_terms(
					array(
						'taxonomy'   => 'wpfa_speaker_category',
						'hide_empty' => false,
						'number'     => 5,
						'include'    => $speaker_cat_ids,
					)
				);
				$categories_preview = is_array( $categories_preview ) ? $categories_preview : array();
			}
		} else {
			// Calculate counts.
			$all_posts_count      = wp_count_posts( 'wpfa_speaker' );
			$total_speakers_count = isset( $all_posts_count->publish ) ? (int) $all_posts_count->publish : 0;
			$event_speaker_ids    = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_all_event_owned_speaker_ids() : array();
			$event_owned_count    = count( $event_speaker_ids );
			$standalone_count     = max( 0, $total_speakers_count - $event_owned_count );

			$categories_count_raw   = wp_count_terms( array( 'taxonomy' => 'wpfa_speaker_category' ) );
			$total_categories_count = ! is_wp_error( $categories_count_raw ) ? (int) $categories_count_raw : 0;

			// Fetch preview arrays (limited to 5).
			$speakers_preview = get_posts(
				array(
					'post_type'      => 'wpfa_speaker',
					'post_status'    => 'any',
					'posts_per_page' => 5,
				)
			);

			$categories_preview = get_terms(
				array(
					'taxonomy'   => 'wpfa_speaker_category',
					'hide_empty' => false,
					'number'     => 5,
				)
			);
			$categories_preview = is_array( $categories_preview ) ? $categories_preview : array();
		}

		// Set up global variables so that admin-header.php renders the correct sidebar menu and highlighted items.
		global $parent_file, $submenu_file, $title, $post_type;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to load admin-header.php in custom context.
		$title = __( 'Speakers Dashboard', 'wpfaevent' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to load admin-header.php in custom context.
		$parent_file = 'edit.php?post_type=wpfa_event';
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to load admin-header.php in custom context.
		$submenu_file = 'edit.php?post_type=wpfa_speaker';
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required to load admin-header.php in custom context.
		$post_type = 'wpfa_speaker';

		require_once ABSPATH . 'wp-admin/admin-header.php';
		require WPFAEVENT_PATH . 'admin/partials/speakers-dashboard.php';
		require_once ABSPATH . 'wp-admin/admin-footer.php';
		exit;
	}

	/**
	 * Render the opening of the dashboard layout wrapper on the classic list table screen.
	 *
	 * @since 1.0.0
	 */
	public function begin_speaker_table_layout() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-wpfa_speaker' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpfa_view'] ) || 'table' !== $_GET['wpfa_view'] ) {
			return;
		}

		// Calculate counts for stats.
		$all_posts_count      = wp_count_posts( 'wpfa_speaker' );
		$total_speakers_count = isset( $all_posts_count->publish ) ? (int) $all_posts_count->publish : 0;
		$event_speaker_ids    = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_all_event_owned_speaker_ids() : array();
		$event_owned_count    = count( $event_speaker_ids );
		$standalone_count     = max( 0, $total_speakers_count - $event_owned_count );

		$categories_count_raw   = wp_count_terms( array( 'taxonomy' => 'wpfa_speaker_category' ) );
		$total_categories_count = ! is_wp_error( $categories_count_raw ) ? (int) $categories_count_raw : 0;

		?>
		<div class="wpfaevent-dashboard-shell">
			<!-- Hero Section -->
			<?php
			$new_speaker_url   = admin_url( 'post-new.php?post_type=wpfa_speaker' );
			$switch_view_url   = remove_query_arg( 'wpfa_view' );
			$switch_view_label = __( 'Switch to Dashboard View', 'wpfaevent' );
			require WPFAEVENT_PATH . 'admin/partials/speaker-dashboard-header.php';
			?>

			<!-- Statistics Grid -->
			<?php require WPFAEVENT_PATH . 'admin/partials/speaker-dashboard-stats.php'; ?>

			<!-- Table Card Wrapper -->
			<div class="wpfaevent-dashboard-card">
				<h2 style="margin-bottom:15px;"><?php esc_html_e( 'All Speakers List Table', 'wpfaevent' ); ?></h2>
		<?php
	}

	/**
	 * Render the closing of the dashboard layout wrapper on the classic list table screen.
	 *
	 * @since 1.0.0
	 */
	public function end_speaker_table_layout() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-wpfa_speaker' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpfa_view'] ) || 'table' !== $_GET['wpfa_view'] ) {
			return;
		}

		?>
			</div> <!-- Close wpfaevent-dashboard-card -->
		</div> <!-- Close wpfaevent-dashboard-shell -->
		<?php
	}

	/**
	 * Check whether a query is the main Speakers admin list query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query to inspect.
	 * @return bool
	 */
	private function is_speaker_admin_list_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return false;
		}

		if ( 'edit.php' !== $GLOBALS['pagenow'] ) {
			return false;
		}

		return 'wpfa_speaker' === $query->get( 'post_type' );
	}

	/**
	 * Get the selected scope for the Speakers admin list.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_current_speaker_admin_scope() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter persisted via query string.
		$scope = isset( $_GET['wpfaevent_speaker_scope'] ) ? sanitize_key( wp_unslash( $_GET['wpfaevent_speaker_scope'] ) ) : 'all';

		if ( ! array_key_exists( $scope, $this->get_speaker_admin_scope_options() ) ) {
			return 'all';
		}

		return $scope;
	}

	/**
	 * Get the selected event filter for the Speakers admin list.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	private function get_current_speaker_admin_event_filter() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter persisted via query string.
		$event_id = isset( $_GET['wpfa_speaker_event'] ) ? absint( wp_unslash( $_GET['wpfa_speaker_event'] ) ) : 0;

		if ( ! $event_id ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Backward compatibility for earlier PR builds of this read-only filter.
			$event_id = isset( $_GET['wpfaevent_speaker_event'] ) ? absint( wp_unslash( $_GET['wpfaevent_speaker_event'] ) ) : 0;
		}

		if ( ! $event_id || 'wpfa_event' !== get_post_type( $event_id ) ) {
			return 0;
		}

		return $event_id;
	}

	/**
	 * Get supported scope options for the Speakers admin list.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	private function get_speaker_admin_scope_options() {
		return array(
			'all'        => __( 'All Speakers', 'wpfaevent' ),
			'standalone' => __( 'Standalone Speakers', 'wpfaevent' ),
			'event'      => __( 'Event-Owned Speakers', 'wpfaevent' ),
		);
	}

	/**
	 * Render a "Back to Event Dashboard" button on standard admin list pages.
	 *
	 * @since 1.0.0
	 */
	public function render_back_to_dashboard_button() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_speaker_list = ( 'edit-wpfa_speaker' === $screen->id );
		$is_track_list   = ( 'edit-wpfa_event_track' === $screen->id );

		if ( ! $is_speaker_list && ! $is_track_list ) {
			return;
		}

		$event_id = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['wpfa_speaker_event'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$event_id = absint( wp_unslash( $_GET['wpfa_speaker_event'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['event_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$event_id = absint( wp_unslash( $_GET['event_id'] ) );
		}

		if ( ! $event_id ) {
			return;
		}

		$dashboard_page = new Wpfaevent_Event_Dashboard_Page();
		$dashboard_url  = $dashboard_page->get_dashboard_url( $event_id );
		?>
		<div style="margin: 10px 0 20px 0; display: flex; align-items: center;">
			<a href="<?php echo esc_url( $dashboard_url ); ?>" class="button" style="display:inline-flex; align-items:center; gap:6px; font-weight:600; padding:6px 12px; border-radius:6px; border:1px solid #ccd0d4; background:#f6f7f7; color:#2c3338; text-decoration:none;">
				&larr; <?php esc_html_e( 'Back to Event Dashboard', 'wpfaevent' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render a hidden event_id field on the Add Track taxonomy form.
	 *
	 * @since 1.0.0
	 */
	public function render_track_form_event_id_field() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event_id = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;

		if ( $event_id ) {
			echo '<input type="hidden" name="event_id" value="' . esc_attr( (string) $event_id ) . '">';
		}
	}

	/**
	 * Associate a newly created track term with its parent event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term ID.
	 */
	public function associate_created_track_with_event( $term_id ) {
		$term_id  = absint( $term_id );
		$event_id = 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['event_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			$event_id = absint( wp_unslash( $_REQUEST['event_id'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			$query   = wp_parse_url( $referer, PHP_URL_QUERY );
			if ( $query ) {
				parse_str( (string) $query, $params );
				if ( isset( $params['event_id'] ) ) {
					$event_id = absint( $params['event_id'] );
				}
			}
		}

		if ( $term_id && $event_id && 'wpfa_event' === get_post_type( $event_id ) ) {
			wp_set_post_terms( $event_id, array( $term_id ), 'wpfa_event_track', true );
		}
	}
}
