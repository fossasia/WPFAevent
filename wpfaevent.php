<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://fossasia.org
 * @since             1.0.0
 * @package           Wpfaevent
 *
 * @wordpress-plugin
 * Plugin Name:       FOSSASIA event plugin
 * Plugin URI:        https://github.com/fossasia/WPFAevent
 * Description:       The FOSSASIA Event Plugin provides WordPress integrations for Eventyay-based events.
 *
 * Version:           1.0.0
 * Author:            FOSSASIA
 * Author URI:        https://fossasia.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfaevent
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
// Define constants
define( 'WPFAEVENT_VERSION', '1.0.0' );
define( 'WPFAEVENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFAEVENT_URL', plugin_dir_url( __FILE__ ) );

// Requires
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-i18n.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-loader.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-activator.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-deactivator.php';

// Activation / Deactivation hooks
register_activation_hook( __FILE__, [ 'Wpfaevent_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Wpfaevent_Deactivator', 'deactivate' ] );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpfaevent() {

	// Load translations
	if ( class_exists( 'Wpfaevent_i18n' ) ) {
		$i18n = new Wpfaevent_i18n();
		$i18n->load_plugin_textdomain();
	}

	// Run the core plugin
	if ( class_exists( 'Wpfaevent' ) ) {
		$plugin = new Wpfaevent();
		$plugin->run();
	}
}

run_wpfaevent();

// Register WP-CLI commands when running in CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WPFAEVENT_PATH . 'includes/cli/class-wpfa-cli.php';
	WP_CLI::add_command( 'wpfa seed', [ 'WPFA_CLI', 'seed' ] );
}
