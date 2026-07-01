<?php
/**
 * Core plugin class bootstrap.
 *
 * @package Wpfaevent
 */

/**
 * Prevent direct access to this file
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	/**
	 * Plugin loader instance.
	 *
	 * @var Wpfaevent_Loader
	 */
	private $loader;

	/**
	 * Admin plugin instance.
	 *
	 * @var Wpfaevent_Admin
	 */
	private $plugin_admin;

	/**
	 * Public plugin instance.
	 *
	 * @var Wpfaevent_Public
	 */
	private $plugin_public;

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
		Wpfaevent_Roles::init();
		$this->define_cpt_hooks();
		$this->define_taxonomy_hooks();
		$this->define_meta_hooks();
		$this->define_page_hooks();
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
		// Loader.
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-loader.php';

		// Cache management.
		require_once plugin_dir_path( __FILE__ ) . 'cache/class-wpfaevent-cache.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-roles.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-event-speaker-relation-manager.php';

		// Data model classes - Custom Post Types.
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-event.php';
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-speaker.php';

		// Data model classes - Taxonomies.
		require_once plugin_dir_path( __FILE__ ) . 'taxonomies/class-wpfaevent-taxonomies.php';
		require_once plugin_dir_path( __FILE__ ) . 'taxonomies/class-wpfaevent-taxonomies-speaker.php';

		// Data model classes - Meta Fields.
		require_once plugin_dir_path( __FILE__ ) . 'meta/class-wpfaevent-meta-event.php';
		require_once plugin_dir_path( __FILE__ ) . 'meta/class-wpfaevent-meta-speaker.php';

		// Calendar export support.
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-calendar.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-event-navigation-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-additional-information-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-schedule-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-partner-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-schedule-controller.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-event-template-controller.php';

		// Eventyay Importer modular classes.
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-jsonapi-resource-utils.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-jsonapi-parser.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-partner-json-store.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-event-repository.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-speaker-repository.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-eventyay-api-client.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-eventyay-post-manager.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-admin-settings-renderer.php';
		require_once plugin_dir_path( __FILE__ ) . 'eventyay-importer/class-wpfaevent-ajax-controller.php';

		// Admin and Public classes.
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-eventyay-dashboard-store.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-eventyay-schedule-sync.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-eventyay-importer.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-eventyay-ajax-sync.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-admin.php';
		require_once plugin_dir_path( __DIR__ ) . 'public/class-wpfaevent-public.php';

		// AJAX handler classes.
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/ajax-handlers/class-wpfaevent-footer-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/ajax-handlers/class-wpfaevent-event-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/ajax-handlers/class-wpfaevent-speakers-handler.php';

		// Cron scheduler for auto-sync.
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-cron-scheduler.php';

		// Optional utilities if present.
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-wpfa-cli.php';
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
		// Register Event CPT (static method call).
		$this->loader->add_action( 'init', 'Wpfaevent_CPT_Event', 'register' );

		// Register Speaker CPT (static method call).
		$this->loader->add_action( 'init', 'Wpfaevent_CPT_Speaker', 'register' );
	}

	/**
	 * Register all Custom Taxonomies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_taxonomy_hooks() {
		// Register Event and Speaker taxonomies.
		$this->loader->add_action( 'init', 'Wpfaevent_Taxonomies', 'register' );
		$this->loader->add_action( 'init', 'Wpfaevent_Taxonomies_Speaker', 'register' );
	}

	/**
	 * Register all Meta Fields.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_meta_hooks() {
		// Register Event meta fields.
		$this->loader->add_action( 'init', 'Wpfaevent_Meta_Event', 'register' );

		// Register Speaker meta fields.
		$this->loader->add_action( 'init', 'Wpfaevent_Meta_Speaker', 'register' );
	}

	/**
	 * Register hooks for plugin-managed pages.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_page_hooks() {
		$this->loader->add_action( 'init', 'Wpfaevent_Additional_Information_Helper', 'ensure_additional_information_page', 21 );
		$this->loader->add_action( 'init', 'Wpfaevent_Partner_Helper', 'ensure_partner_page', 22 );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Instantiate the admin class.
		$this->plugin_admin = new Wpfaevent_Admin( $this->plugin_name, $this->version );

		// Register admin-specific stylesheet and scripts.
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts' );

		// Register settings page.
		$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'register_settings_page' );
		$this->loader->add_action( 'admin_init', $this->plugin_admin, 'register_plugin_settings' );
		$this->loader->add_action( 'admin_init', $this->plugin_admin, 'register_eventyay_import_settings' );

		// Add settings link to the plugins page.
		$plugin_basename = plugin_basename( dirname( __DIR__ ) . '/wpfaevent.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this->plugin_admin, 'add_settings_link' );

		// Add meta boxes to CPTs.
		$this->loader->add_action( 'add_meta_boxes', $this->plugin_admin, 'add_meta_boxes' );

		// Save meta box data.
		$this->loader->add_action( 'save_post_wpfa_event', $this->plugin_admin, 'save_event_meta' );
		$this->loader->add_action( 'save_post_wpfa_speaker', $this->plugin_admin, 'save_speaker_meta' );

		// Keep event-owned speakers out of the global speaker admin list.
		$this->loader->add_action( 'restrict_manage_posts', $this->plugin_admin, 'render_speaker_event_filter' );
		$this->loader->add_action( 'pre_get_posts', $this->plugin_admin, 'filter_speaker_admin_list' );
		$this->loader->add_filter( 'views_edit-wpfa_speaker', $this->plugin_admin, 'filter_speaker_admin_views' );

		// Register AJAX handlers for the speakers page.
		$plugin_speakers_handler = new Wpfaevent_Speakers_Handler();
		$this->loader->add_action( 'wp_ajax_wpfa_get_speaker', $plugin_speakers_handler, 'ajax_get_speaker' );
		$this->loader->add_action( 'wp_ajax_wpfa_add_speaker', $plugin_speakers_handler, 'ajax_add_speaker' );
		$this->loader->add_action( 'wp_ajax_wpfa_update_speaker', $plugin_speakers_handler, 'ajax_update_speaker' );
		$this->loader->add_action( 'wp_ajax_wpfa_delete_speaker', $plugin_speakers_handler, 'ajax_delete_speaker' );

		// Register AJAX handlers for the events page.
		$plugin_event_handler = new Wpfaevent_Event_Handler();
		$this->loader->add_action( 'wp_ajax_wpfa_get_event', $plugin_event_handler, 'ajax_get_event' );
		$this->loader->add_action( 'wp_ajax_wpfa_add_event', $plugin_event_handler, 'ajax_add_event' );
		$this->loader->add_action( 'wp_ajax_wpfa_update_event', $plugin_event_handler, 'ajax_update_event' );
		$this->loader->add_action( 'wp_ajax_wpfa_delete_event', $plugin_event_handler, 'ajax_delete_event' );

		// Register AJAX handler for footer text update.
		$plugin_footer_handler = new Wpfaevent_Footer_Handler();
		$this->loader->add_action( 'wp_ajax_wpfa_update_footer_text', $plugin_footer_handler, 'ajax_update_footer_text' );

		// Register AJAX handler for Eventyay sync and chunked imports.
		$eventyay_ajax_controller = new Wpfaevent_AJAX_Controller();
		$this->loader->add_action( 'wp_ajax_wpfaevent_import_get_events', $eventyay_ajax_controller, 'ajax_import_get_events' );
		$this->loader->add_action( 'wp_ajax_wpfaevent_import_single_event', $eventyay_ajax_controller, 'ajax_import_single_event' );
		$this->loader->add_action( 'wp_ajax_wpfaevent_import_save_summary', $eventyay_ajax_controller, 'ajax_import_save_summary' );

		// Register AJAX handler for dashboard JSON:API sync.
		$this->loader->add_action( 'wp_ajax_fossasia_sync_eventyay', $this->plugin_admin, 'ajax_sync_eventyay' );
		$this->loader->add_action( 'admin_post_wpfaevent_import_eventyay_events', $this->plugin_admin, 'handle_eventyay_events_import' );

		// Scheduled auto-sync cron callback.
		$this->loader->add_action( Wpfaevent_Cron_Scheduler::HOOK, 'Wpfaevent_Cron_Scheduler', 'run' );

		// Re-schedule when import settings are saved.
		$this->loader->add_action( 'update_option_wpfaevent_eventyay_import_settings', 'Wpfaevent_Cron_Scheduler', 'handle_settings_update', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Instantiate the public class.
		$this->plugin_public = new Wpfaevent_Public( $this->plugin_name, $this->version );

		// Register public-specific stylesheet.
		$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );

		// Cache invalidation hooks (static method calls).
		$this->loader->add_action( 'save_post', 'Wpfaevent_Cache', 'clear_page_cache' );
		$this->loader->add_action( 'delete_post', 'Wpfaevent_Cache', 'clear_page_cache' );

		// Calendar export endpoint.
		$this->loader->add_action( 'rest_api_init', 'Wpfaevent_Calendar', 'register_rest_routes' );
		$this->loader->add_filter( 'rest_pre_serve_request', 'Wpfaevent_Calendar', 'serve_rest_calendar', 10, 4 );
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
