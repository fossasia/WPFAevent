<?php
/**
 * Main loader for the plugin.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Loader.
 */
class WPFA_Loader {

	/**
	 * Initializes all plugin components.
	 */
	public static function init() {
		self::load_dependencies();
		self::init_classes();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private static function load_dependencies() {
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-cpt.php';
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-settings.php';
		require_once WPFA_PLUGIN_PATH . 'public/class-wpfa-public.php';
		require_once WPFA_PLUGIN_PATH . 'admin/class-wpfa-admin.php';
		require_once WPFA_PLUGIN_PATH . 'admin/class-wpfa-import-export.php';
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-rest.php';
		require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-rest.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WPFA_PLUGIN_PATH . 'includes/class-wpfa-cli.php';
		}
	}

	/**
	 * Initialize the classes.
	 */
	private static function init_classes() {
		new WPFA_CPT();
		new WPFA_Public();
		new WPFA_Admin();
		new WPFA_REST();
	}
}