<?php
/**
 * Plugin Name:       FOSSASIA Event Plugin
 * Description:       A plugin for managing and displaying event and speaker data for FOSSASIA using Custom Post Types and a shortcode.
 * Version:           2.0.0
 * Author:            Nishil
 * Text Domain:       wpfa-event
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Define plugin constants
define( 'WPFA_VERSION', '2.0.0' );
define( 'WPFA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); 
define( 'WPFA_DEFAULT_SPEAKER_PHOTO', WPFA_PLUGIN_URL . 'assets/images/default-speaker.png' );

/**
 * The main plugin class.
 */
final class FOSSASIA_Event_Plugin {

	/**
	 * The single instance of the class.
	 */
	private static $_instance = null;

	/**
	 * Main FOSSASIA_Event_Plugin Instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files.
	 */
	public function includes() {
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-loader.php';
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', [ WPFA_Loader::class, 'init' ] );
		add_action( 'init', [ $this, 'load_textdomain' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	/**
	 * Load Localization files.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wpfa-event', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Trigger CPT registration to flush rewrite rules.
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-cpt.php';
		$cpt = new WPFA_CPT();
		$cpt->register_post_types();
		$cpt->register_meta_fields();
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}

/**
 * Begins execution of the plugin.
 */
function wpfa_event_plugin() {
	return FOSSASIA_Event_Plugin::instance();
}
wpfa_event_plugin();
