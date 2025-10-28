<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Wpfa_Event
 * @subpackage Wpfa_Event/public
 */

class WPFA_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpfa-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the shortcodes for the plugin.
	 */
	public function register_shortcodes() {
		add_shortcode( 'wpfa_speakers', array( $this, 'render_speakers_shortcode' ) );
	}

	/**
	 * Renders the [wpfa_speakers] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_speakers_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => -1,
			),
			$atts,
			'wpfa_speakers'
		);

		$query = new WP_Query(
			array(
				'post_type'      => 'wpfa_speaker',
				'posts_per_page' => intval( $atts['limit'] ),
				'post_status'    => 'publish',
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No speakers found.', 'wpfa-event' ) . '</p>';
		}

		ob_start();
		echo '<div class="wpfa-speakers-archive">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$org      = get_post_meta( get_the_ID(), 'wpfa_speaker_org', true );
			$position = get_post_meta( get_the_ID(), 'wpfa_speaker_position', true );
			$photo_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ?: 'https://via.placeholder.com/150';

			echo '<div class="wpfa-speaker-card">';
			echo '<img src="' . esc_url( $photo_url ) . '" alt="' . esc_attr( get_the_title() ) . '">';
			echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
			echo '<p>' . esc_html( $position ) . ', <em>' . esc_html( $org ) . '</em></p>';
			echo '</div>';
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

}