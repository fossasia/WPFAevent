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
// Define constants
define( 'WPFAEVENT_VERSION', '1.0.0' );
define( 'WPFAEVENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFAEVENT_URL',  plugin_dir_url( __FILE__ ) );

// Requires
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-i18n.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-loader.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-activator.php';
require_once WPFAEVENT_PATH . 'includes/class-wpfaevent-deactivator.php';

// Activation / Deactivation hooks
register_activation_hook( __FILE__, [ 'Wpfaevent_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Wpfaevent_Deactivator', 'deactivate' ] );

// Plugin init
add_action( 'plugins_loaded', function () {
    WPFAEvent_I18n::load_textdomain( 'wpfaevent' );

    if ( class_exists( 'WPFAEvent_Loader' ) ) {
        WPFAEvent_Loader::run();
    }
});
