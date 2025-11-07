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
 * Plugin URI:        https://https://github.com/fossasia/WPFAevent
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
define( 'WPFAEVENT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activate_wpfaevent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent-activator.php';
	Wpfaevent_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wpfaevent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent-deactivator.php';
	Wpfaevent_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpfaevent' );
register_deactivation_hook( __FILE__, 'deactivate_wpfaevent' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_wpfaevent() {
	$plugin = new Wpfaevent();
	$plugin->run();

	
}

run_wpfaevent();
