<?php
/**
 * Event overview section.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $about_content ) || '' === trim( $about_content ) ) {
	return;
}
?>
<section id="about" class="wpfa-event-section wpfa-event-about" aria-labelledby="wpfa-event-about-title">
	<div class="container">
		<div class="wpfa-event-section-layout">
			<header class="wpfa-event-section-label">
				<p><?php esc_html_e( 'Overview', 'wpfaevent' ); ?></p>
				<h2 id="wpfa-event-about-title"><?php esc_html_e( 'About this event', 'wpfaevent' ); ?></h2>
			</header>
			<div class="wpfa-event-rich-text" itemprop="description">
				<?php echo wp_kses_post( wpautop( $about_content ) ); ?>
			</div>
		</div>
	</div>
</section>
