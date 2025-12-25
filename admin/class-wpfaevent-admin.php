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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/wpfaevent-admin.css', array(), $this->version, 'all' );

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
	 * @param    array    $links    Existing plugin action links
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

}