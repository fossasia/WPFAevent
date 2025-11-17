<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Core orchestrator that brings together loader and legacy implementation.
 */
class Wpfaevent {
    /** @var Wpfaevent_Loader */
    private $loader;

    /** @var Wpfaevent_Admin */
    private $plugin_admin;

    /** @var Wpfaevent_Public */
    private $plugin_public;

    /** @var FOSSASIA_Landing_Plugin|null */
    private $legacy = null;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    public function __construct() {
        $this->plugin_name = 'wpfaevent';
        $this->version = WPFAEVENT_VERSION;

        $this->load_dependencies();
        $this->define_core_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Loader
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-loader.php';

        // Data model classes.
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-event-cpt-event.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-event-cpt-speaker.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-event-taxonomies.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-event-meta.php';

        // Legacy plugin code (defines FOSSASIA_Landing_Plugin class)
        require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-landing.php';

        // Admin and Public classes
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpfaevent-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wpfaevent-public.php';

        // Optional utilities if present
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php';
        }
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wpfaevent-uninstaller.php' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-uninstaller.php';
        }

        $this->loader = new Wpfaevent_Loader();
    }

    private function define_core_hooks() {
        $this->loader->add_action( 'init', 'WPFA_Event_CPT_Event', 'register_post_type' );
        $this->loader->add_action( 'init', 'WPFA_Event_CPT_Speaker', 'register_post_type' );
        $this->loader->add_action( 'init', 'WPFA_Event_Taxonomies', 'register_taxonomies' );
        $this->loader->add_action( 'init', 'WPFA_Event_Meta', 'register_meta_fields' );
    }

    private function define_admin_hooks() {
        // Instantiate the admin class
        $this->plugin_admin = new Wpfaevent_Admin( $this->plugin_name, $this->version );

        // Register admin-specific stylesheet
        $this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );

        // Instantiate the legacy plugin and keep a reference so we can reuse its methods
        if ( class_exists( 'Wpfaevent_Landing' ) ) {
            $this->legacy = new Wpfaevent_Landing();
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
        // Instantiate the public class
        $this->plugin_public = new Wpfaevent_Public( $this->plugin_name, $this->version );

        // Register public-specific stylesheet
        $this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );

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
