<?php

/**
 * The core plugin class.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    WPFA_Event
 * @subpackage WPFA_Event/includes
 */

class WPFA_Event {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'WPFA_EVENT_VERSION' ) ) {
			$this->version = WPFA_EVENT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wpfa-event';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpfaevent-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpfaevent-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpfaevent-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wpfaevent-public.php';

		$this->loader = new WPFA_Event_Loader();

	}

	private function set_locale() {

		$plugin_i18n = new WPFA_Event_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_admin_hooks() {

		$plugin_admin = new WPFA_Event_Admin( $this->get_plugin_name(), $this->get_version() );

		// This is a placeholder for future admin-specific hooks.
		// Example:
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	private function define_public_hooks() {

		$plugin_public = new WPFA_Event_Public( $this->get_plugin_name(), $this->get_version() );

		// This is a placeholder for future public-facing hooks.
		// Example:
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

}