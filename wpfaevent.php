<?php
/**
 * Plugin Name: WPFA Event (compat wrapper)
 * Description: Compatibility wrapper / refactor of the FOSSASIA Landing plugin into WPFAevent structure.
 * Version: 1.0.0
 * Author: Automated Refactor
 */

if ( ! defined( 'WPINC' ) ) { die; }

define( 'WPFAEVENT_VERSION', '1.0.0' );
define( 'WPFAEVENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Bootstrap core
require_once WPFAEVENT_PLUGIN_DIR . 'includes/class-wpfaevent.php';

/**
 * Activation wrapper: instantiate legacy plugin and call its on_activate method.
 */
function wpfaevent_activate() {
    if ( ! class_exists( 'FOSSASIA_Landing_Plugin' ) ) {
        require_once WPFAEVENT_PLUGIN_DIR . 'fossasia-landing.php';
    }
    if ( class_exists( 'FOSSASIA_Landing_Plugin' ) ) {
        $legacy = new FOSSASIA_Landing_Plugin();
        if ( method_exists( $legacy, 'on_activate' ) ) {
            $legacy->on_activate();
        }
    }
}

/**
 * Deactivation wrapper: instantiate legacy plugin and call its on_deactivate method.
 */
function wpfaevent_deactivate() {
    if ( ! class_exists( 'FOSSASIA_Landing_Plugin' ) ) {
        require_once WPFAEVENT_PLUGIN_DIR . 'fossasia-landing.php';
    }
    if ( class_exists( 'FOSSASIA_Landing_Plugin' ) ) {
        $legacy = new FOSSASIA_Landing_Plugin();
        if ( method_exists( $legacy, 'on_deactivate' ) ) {
            $legacy->on_deactivate();
        }
    }
}

register_activation_hook( __FILE__, 'wpfaevent_activate' );
register_deactivation_hook( __FILE__, 'wpfaevent_deactivate' );

// Instantiate and run the core orchestrator
$plugin = new Wpfaevent();
$plugin->run();
