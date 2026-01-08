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
        // Load CPT classes to register them before flushing
        require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-event.php';
        require_once plugin_dir_path( __FILE__ ) . 'cpt/class-wpfaevent-cpt-speaker.php';
        require_once plugin_dir_path( __FILE__ ) . 'taxonomies/class-wpfaevent-taxonomies.php';

        // Register CPTs and taxonomies
        Wpfaevent_CPT_Event::register();
        Wpfaevent_CPT_Speaker::register();
        Wpfaevent_Taxonomies::register();

        // Flush rewrite rules so CPT permalinks work
        flush_rewrite_rules();

        // Grant custom capabilities to administrator
        self::add_capabilities();
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

        // Event capabilities
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

        // Speaker capabilities
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