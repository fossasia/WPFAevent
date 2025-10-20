<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://fossasia.org
 * @since             1.0.0
 * @package           WPFA_Event
 *
 * @wordpress-plugin
 * Plugin Name:       WPFA Event
 * Plugin URI:        https://github.com/fossasia/wp-fa-event
 * Description:       Base plugin scaffold for FOSSASIA event listings.
 * Version:           1.0.0
 * Author:            FOSSASIA
 * Author URI:        https://fossasia.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfa-event
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPFA_EVENT_VERSION', '1.0.0' );

function activate_wpfaevent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent-activator.php';
	WPFA_Event_Activator::activate();
}

function deactivate_wpfaevent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent-deactivator.php';
	WPFA_Event_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpfaevent' );
register_deactivation_hook( __FILE__, 'deactivate_wpfaevent' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfaevent.php';

function run_wpfaevent() {
	$plugin = new WPFA_Event();
	$plugin->run();
}
add_action( 'plugins_loaded', 'run_wpfaevent' );