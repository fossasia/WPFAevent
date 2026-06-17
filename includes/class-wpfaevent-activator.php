<?php
/**
 * Fired during plugin activation
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Load CPT classes to register them before flushing.
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-event.php';
		require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-speaker.php';
		require_once plugin_dir_path( __FILE__ ) . 'taxonomies/class-wpfaevent-taxonomies.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-additional-information-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-schedule-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wpfaevent-partner-helper.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-roles.php';

		// Register CPTs and taxonomies.
		Wpfaevent_CPT_Event::register();
		Wpfaevent_CPT_Speaker::register();
		Wpfaevent_Taxonomies::register();

		// Create the public schedule page used by event detail links.
		Wpfaevent_Schedule_Helper::ensure_schedule_page( false );
		Wpfaevent_Additional_Information_Helper::ensure_additional_information_page( false );
		Wpfaevent_Partner_Helper::ensure_partner_page( false );

		// Flush rewrite rules so CPT permalinks work.
		flush_rewrite_rules();

		// Register plugin roles and grant capabilities.
		Wpfaevent_Roles::register_roles_and_capabilities();
		update_option( Wpfaevent_Roles::ROLES_VERSION_OPTION, Wpfaevent_Roles::ROLES_VERSION, false );
	}
}
