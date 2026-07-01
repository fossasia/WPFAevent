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
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-roles.php';

		// Register CPTs and taxonomies.
		Wpfaevent_CPT_Event::register();
		Wpfaevent_CPT_Speaker::register();
		Wpfaevent_Taxonomies::register();

		// Flush rewrite rules so CPT permalinks work.
		flush_rewrite_rules();

		// Schedule auto-sync cron if already configured.
		require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-cron-scheduler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wpfaevent-eventyay-importer.php';
		$settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
		if ( is_array( $settings ) ) {
			Wpfaevent_Cron_Scheduler::schedule( $settings );
		}

		// Grant custom capabilities to administrators.
		self::add_capabilities();
		Wpfaevent_Roles::register_roles_and_capabilities();
		update_option( Wpfaevent_Roles::ROLES_VERSION_OPTION, Wpfaevent_Roles::ROLES_VERSION, false );
	}

	/**
	 * Grant custom capabilities to administrator role.
	 *
	 * @since 1.0.0
	 */
	private static function add_capabilities() {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		// Event capabilities.
		// TODO: Future PR - Review capability list alignment with CPT registration.
		// Currently granting extended capabilities (delete_*, edit_private_*, etc.).
		// These are auto-derived by map_meta_cap. Consider either:
		// 1. Explicitly defining all capabilities in CPT registration, OR
		// 2. Only granting base capabilities defined in the CPT.
		// Reference: https://developer.wordpress.org/plugins/users/roles-and-capabilities/.
		$event_caps = array(
			'edit_event',
			'read_event',
			'delete_event',
			'edit_events',
			'edit_others_events',
			'publish_events',
			'read_private_events',
			'delete_events',
			'delete_private_events',
			'delete_published_events',
			'delete_others_events',
			'edit_private_events',
			'edit_published_events',
		);

		foreach ( $event_caps as $cap ) {
			$role->add_cap( $cap );
		}

		// Speaker capabilities.
		// TODO: Same capability review needed for speakers (see event_caps above).
		$speaker_caps = array(
			'edit_speaker',
			'read_speaker',
			'delete_speaker',
			'edit_speakers',
			'edit_others_speakers',
			'publish_speakers',
			'read_private_speakers',
			'delete_speakers',
			'delete_private_speakers',
			'delete_published_speakers',
			'delete_others_speakers',
			'edit_private_speakers',
			'edit_published_speakers',
		);

		foreach ( $speaker_caps as $cap ) {
			$role->add_cap( $cap );
		}
	}
}
