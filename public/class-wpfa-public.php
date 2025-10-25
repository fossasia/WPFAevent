<?php
/**
 * Public-facing functionality of the plugin.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Public.
 */
class WPFA_Public {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wpfa_speakers', [ $this, 'render_speakers_shortcode' ] );
		add_shortcode( 'wpfa_events', [ $this, 'render_events_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] ); // Enqueue styles conditionally
	}

	/**
	 * Enqueue public-facing styles.
	 */
	public function enqueue_styles() {
		global $post;

		// Only enqueue styles if the shortcode is present in the post content.
		if ( isset( $post->post_content ) && (
			has_shortcode( $post->post_content, 'wpfa_speakers' ) ||
			has_shortcode( $post->post_content, 'wpfa_events' )
		) ) {
			wp_enqueue_style(
				'wpfa-public-css',
				WPFA_PLUGIN_URL . 'public/css/wpfa-public.css',
				[],
				WPFA_VERSION
			);
		}
	}

	/**
	 * Render the [wpfa_speakers] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_speakers_shortcode() {
		ob_start();
		$template = WPFA_PLUGIN_PATH . 'templates/template-speakers.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<p>' . esc_html__( 'Speaker template not found.', 'wpfa-event' ) . '</p>';
		}
		return ob_get_clean();
	}

	/**
	 * Render the [wpfa_events] shortcode.
	 *
	 * @return string Rendered HTML.
	 */
	public function render_events_shortcode() {
		ob_start();
		$template = WPFA_PLUGIN_PATH . 'templates/template-events.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<p>' . esc_html__( 'Event template not found.', 'wpfa-event' ) . '</p>';
		}
		return ob_get_clean();
	}
}