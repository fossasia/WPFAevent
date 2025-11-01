<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin handler stub for the WPFAevent structure.
 * For now this acts as a light wrapper; admin behaviour is implemented
 * in the legacy FOSSASIA_Landing_Plugin class. This file provides the
 * expected class file for the boilerplate.
 */
class Wpfaevent_Admin {
    public function __construct() {}

    public function enqueue_styles() {
        // Example: wp_enqueue_style( 'wpfaevent-admin', plugin_dir_url( __FILE__ ) . '../assets/admin.css', [], WPFAEVENT_VERSION );
    }

    public function enqueue_scripts() {
        // Example: wp_enqueue_script( 'wpfaevent-admin', plugin_dir_url( __FILE__ ) . '../assets/admin.js', [ 'jquery' ], WPFAEVENT_VERSION, true );
    }

    public function display_admin_page() {
        // Load admin UI partial (which currently proxies to the legacy template)
        include_once dirname( __FILE__ ) . '/partials/wpfaevent-admin-display.php';
    }
}
