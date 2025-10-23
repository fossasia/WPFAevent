<?php
/**
 * Plugin Name: WPFA Event
 * Description: A plugin to create and manage events, speakers, schedules, and sponsors within WordPress using Custom Post Types.
 * Version: 1.0.0
 * Author: Nishil
 * Text Domain: wpfa-event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPFA_EVENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPFA_EVENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class.
 */
final class WPFA_Event {

	/**
	 * The single instance of the class.
	 *
	 * @var WPFA_Event
	 */
	private static $instance;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPFA_Event ) ) {
			self::$instance = new WPFA_Event();
			self::$instance->setup_constants();
			self::$instance->includes();
		}
		return self::$instance;
	}

	/**
	 * Setup plugin constants.
	 */
	private function setup_constants() {
		// Plugin version.
		define( 'WPFA_EVENT_VERSION', '1.0.0' );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once WPFA_EVENT_PLUGIN_DIR . 'includes/class-wpfa-cpt.php';
		require_once WPFA_EVENT_PLUGIN_DIR . 'public/class-wpfa-public.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WPFA_EVENT_PLUGIN_DIR . 'includes/class-wpfa-cli.php';
		}
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		$cpt_handler    = new WPFA_CPT();
		$public_handler = new WPFA_Public( 'wpfa-event', WPFA_EVENT_VERSION );

		add_action( 'init', array( $cpt_handler, 'register_cpts' ) );
		add_action( 'init', array( $cpt_handler, 'register_meta' ) );

		add_action( 'wp_enqueue_scripts', array( $public_handler, 'enqueue_styles' ) );
		add_action( 'init', array( $public_handler, 'register_shortcodes' ) );
	}
}

/**
 * Begins execution of the plugin.
 */
function run_wpfa_event() {
	$plugin = WPFA_Event::instance();
	$plugin->run();
}

add_action( 'plugins_loaded', 'run_wpfa_event' );

// Register WP-CLI command if in CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'wpfa', 'WPFA_CLI' );
}
