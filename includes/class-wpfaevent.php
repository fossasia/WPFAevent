<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
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

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'wpfaevent';
		$this->version     = WPFAEVENT_VERSION;

		$this->load_dependencies();
		$this->define_cpt_hooks();
		$this->define_taxonomy_hooks();
		$this->define_meta_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wpfaevent_Loader. Orchestrates the hooks of the plugin.
	 * - Wpfaevent_i18n. Defines internationalization functionality.
	 * - Wpfaevent_Admin. Defines all hooks for the admin area.
	 * - Wpfaevent_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Loader
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-loader.php';

		// Data model classes - Custom Post Types
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-event.php';
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-speaker.php';

		// Data model classes - Taxonomies
		require_once plugin_dir_path( __FILE__ ) . 'taxonomies/class-wpfaevent-taxonomies.php';

		// Data model classes - Meta Fields
		require_once plugin_dir_path( __FILE__ ) . 'meta/class-wpfaevent-meta-event.php';
		require_once plugin_dir_path( __FILE__ ) . 'meta/class-wpfaevent-meta-speaker.php';

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

	/**
	 * Register all Custom Post Types.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_cpt_hooks() {
		// Register Event CPT
		$event_cpt = new Wpfaevent_CPT_Event();
		$this->loader->add_action( 'init', $event_cpt, 'register' );

		// Register Speaker CPT
		$speaker_cpt = new Wpfaevent_CPT_Speaker();
		$this->loader->add_action( 'init', $speaker_cpt, 'register' );
	}

	/**
	 * Register all Custom Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_taxonomy_hooks() {
		// Register Event Taxonomies (Track and Tag)
		$taxonomies = new Wpfaevent_Taxonomies();
		$this->loader->add_action( 'init', $taxonomies, 'register' );
	}

	/**
	 * Register all Meta Fields.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_meta_hooks() {
		// Register Event Meta Fields
		$event_meta = new Wpfaevent_Meta_Event();
		$this->loader->add_action( 'init', $event_meta, 'register' );

		// Register Speaker Meta Fields
		$speaker_meta = new Wpfaevent_Meta_Speaker();
		$this->loader->add_action( 'init', $speaker_meta, 'register' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Instantiate the admin class
		$this->plugin_admin = new Wpfaevent_Admin( $this->plugin_name, $this->version );

		// Register admin-specific stylesheet
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );

		// Register settings page
		$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'register_settings_page' );

		// Add settings link to plugins page
		$plugin_basename = plugin_basename( dirname( __FILE__, 2 ) . '/wpfaevent.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this->plugin_admin, 'add_settings_link' );

		// Add meta boxes to CPTs
		$this->loader->add_action( 'add_meta_boxes', $this->plugin_admin, 'add_meta_boxes' );

		// Save meta box data
		$this->loader->add_action( 'save_post_wpfa_event', $this->plugin_admin, 'save_event_meta' );
		$this->loader->add_action( 'save_post_wpfa_speaker', $this->plugin_admin, 'save_speaker_meta' );

		// Instantiate the legacy plugin and keep a reference so we can reuse its methods
		// if ( class_exists( 'Wpfaevent_Landing' ) ) {
		// $this->legacy = new Wpfaevent_Landing();
		// }

		if ( ! $this->legacy ) {
			return; }

		// Register admin-facing hooks via the loader so tests/tooling can inspect them
		// $this->loader->add_action( 'admin_enqueue_scripts', $this->legacy, 'enqueue_admin_scripts' );

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

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Instantiate the public class
		$this->plugin_public = new Wpfaevent_Public( $this->plugin_name, $this->version );

		// Register public-specific stylesheet
		$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );

		// if ( ! $this->legacy ) { return; }

		// Template registration and inclusion
		// $this->loader->add_filter( 'theme_page_templates', $this->legacy, 'register_template' );
		// $this->loader->add_filter( 'template_include', $this->legacy, 'load_template', 99 );
		// $this->loader->add_action( 'init', $this->legacy, 'setup_pages' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}
}
