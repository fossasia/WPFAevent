<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPFA Seeder - adds minimal demo data for testing
 */
class WPFA_Seeder {

    public static function register_cli() {
        // Register the WP-CLI command if WP-CLI is available
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'wpfa seed-minimal', [ __CLASS__, 'seed' ] );
        }
    }

    /**
     * Seed minimal sample data (2 speakers + 1 event)
     *
     * ## OPTIONS
     *
     * [--minimal]
     * : Inserts 1 demo event and 2 demo speakers.
     *
     * ## EXAMPLES
     *   wp wpfa seed-minimal
     *
     * @when after_wp_load
     */
    public static function seed( $args, $assoc_args ) {

        // Create a sample event
        wp_insert_post( [
            'post_title'   => 'FOSSASIA Summit Demo Event',
            'post_content' => 'A placeholder event for testing the plugin setup.',
            'post_status'  => 'publish',
            'post_type'    => 'wpfa_event', // Use correct CPT
        ] );

        // Create two speakers
        $speakers = [
            [
                'post_title'   => 'Speaker One',
                'post_content' => 'Expert in Open Source & Community Building.',
                'meta_input'   => [ 'wpfa_speaker_photo' => 'https://via.placeholder.com/300?text=Speaker+1' ],
            ],
            [
                'post_title'   => 'Speaker Two',
                'post_content' => 'Web Technologies Researcher.',
                'meta_input'   => [ 'wpfa_speaker_photo' => 'https://via.placeholder.com/300?text=Speaker+2' ],
            ],
        ];

        foreach ( $speakers as $s ) {
            wp_insert_post( array_merge( $s, [
                'post_status' => 'publish',
                'post_type'   => 'wpfa_speaker', // Use correct CPT
            ] ) );
        }

        WP_CLI::success( '✅ Minimal demo data inserted successfully!' );
    }
}