<?php
/**
 * Handles all footer-related functionality in the admin area.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials/ajax-handlers
 * @author     FOSSASIA <contact@fossasia.org>
 * @since      1.0.0
 */

class Wpfaevent_Footer_Handler {

    /**
     * The plugin name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The plugin name.
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
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    string $plugin_name    The name of this plugin.
     * @param    string $version        The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Handle AJAX request to update footer text.
     *
     * @since    1.0.0
     * @return   void
     */
    public function ajax_update_footer_text() {
        // Verify nonce. Third param 'false' ensures we can handle the error response manually via JSON.
        if ( ! check_ajax_referer( 'wpfa_events_ajax', 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Invalid nonce', 'wpfaevent' ),
                ),
                403
            );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Unauthorized', 'wpfaevent' ) ),
                403
            );
        }

        // Check for the correct parameter name
        if ( ! isset( $_POST['footer_text'] ) ) {
            wp_send_json_error(
                array(
                    'message' => esc_html__( 'Footer text is required', 'wpfaevent' ),
                )
            );
        }

        $footer_text = sanitize_text_field( wp_unslash( $_POST['footer_text'] ) );
        
        // Save to options
        if ( update_option( 'wpfa_footer_text', $footer_text ) || get_option( 'wpfa_footer_text' ) === $footer_text ) {
            wp_send_json_success(
                array( 
                    'message' => esc_html__( 'Footer text updated successfully', 'wpfaevent' ) 
                ) 
            );
        } else {
            wp_send_json_error(
                array( 
                    'message' => esc_html__( 'Failed to update footer text', 'wpfaevent' ) 
                ) 
            );
        }
    }
}