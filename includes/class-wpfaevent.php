<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Core orchestrator that brings together loader and legacy implementation.
 */
class Wpfaevent {
    /** @var Wpfaevent_Loader */
    private $loader;

    /** @var FOSSASIA_Landing_Plugin|null */
    private $legacy = null;

    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Loader
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-loader.php';

        // Legacy plugin code (defines FOSSASIA_Landing_Plugin class)
        require_once plugin_dir_path( __FILE__ ) . '../fossasia-landing.php';

        // Optional utilities if present
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php';
        }
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wpfaevent-uninstaller.php' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-uninstaller.php';
        }

        $this->loader = new Wpfaevent_Loader();
    }

    private function define_admin_hooks() {
        // Instantiate the legacy plugin and keep a reference so we can reuse its methods
        if ( class_exists( 'FOSSASIA_Landing_Plugin' ) ) {
            $this->legacy = new FOSSASIA_Landing_Plugin();
        }

        if ( ! $this->legacy ) { return; }

        // Register admin-facing hooks via the loader so tests/tooling can inspect them
        $this->loader->add_action( 'admin_enqueue_scripts', $this->legacy, 'enqueue_admin_scripts' );

        // Register the many AJAX handlers the legacy class provides
        $ajax_methods = [
            'fossasia_manage_speakers' => 'ajax_manage_speakers',
            'fossasia_manage_sponsors' => 'ajax_manage_sponsors',
            'fossasia_manage_site_settings' => 'ajax_manage_site_settings',
            'fossasia_manage_sections' => 'ajax_manage_sections',
            'fossasia_manage_schedule' => 'ajax_manage_schedule',
            'fossasia_manage_navigation' => 'ajax_manage_navigation',
            'fossasia_sync_eventyay' => 'ajax_sync_eventyay',
            'fossasia_create_event_page' => 'ajax_create_event_page',
            'fossasia_edit_event_page' => 'ajax_edit_event_page',
            'fossasia_delete_event_page' => 'ajax_delete_event_page',
            'fossasia_manage_theme_settings' => 'ajax_manage_theme_settings',
            'fossasia_import_sample_data' => 'ajax_import_sample_data',
            'fossasia_add_sample_event' => 'ajax_add_sample_event',
            'fossasia_manage_coc' => 'ajax_manage_coc',
        ];

        foreach ( $ajax_methods as $action => $method ) {
            // admin ajax
            $this->loader->add_action( 'wp_ajax_' . $action, $this->legacy, $method );
        }
    }

    private function define_public_hooks() {
        if ( ! $this->legacy ) { return; }

        // Template registration and inclusion
        $this->loader->add_filter( 'theme_page_templates', $this->legacy, 'register_template' );
        $this->loader->add_filter( 'template_include', $this->legacy, 'load_template', 99 );
        $this->loader->add_action( 'init', $this->legacy, 'setup_pages' );
    }

    public function run() {
        $this->loader->run();
    }
}
