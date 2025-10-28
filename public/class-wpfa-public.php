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
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_shortcode( 'wpfa_speakers', [ $this, 'render_speakers_shortcode' ] );
	}

	/**
	 * Enqueue public-facing styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'wpfa-public-css',
			WPFA_PLUGIN_URL . 'public/wpfa-public.css',
			[],
			WPFA_VERSION
		);
	}

	/**
	 * Render the [wpfa_speakers] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function render_speakers_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'limit' => -1,
		], $atts, 'wpfa_speakers' );

		$query = new WP_Query( [
			'post_type'      => 'wpfa_speaker',
			'posts_per_page' => intval( $atts['limit'] ),
			'no_found_rows'  => true,
		] );

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No speakers found.', 'wpfa-event' ) . '</p>';
		}

		ob_start();
		echo '<div class="wpfa-speakers-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$org      = get_post_meta( get_the_ID(), 'wpfa_speaker_org', true );
			$role     = get_post_meta( get_the_ID(), 'wpfa_speaker_role', true );
			$photo_url = get_post_meta( get_the_ID(), 'wpfa_speaker_photo_url', true );
			?>
			<div class="wpfa-speaker-card">
				<?php if ( $photo_url ) : ?>
					<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php the_title_attribute(); ?>" class="wpfa-speaker-photo">
				<?php endif; ?>
				<h3 class="wpfa-speaker-name"><?php the_title(); ?></h3>
				<p class="wpfa-speaker-meta"><?php echo esc_html( $role ); ?>, <em><?php echo esc_html( $org ); ?></em></p>
			</div>
			<?php
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
}