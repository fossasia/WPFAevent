<?php
/**
 * Dashboard Speaker Card Partial.
 *
 * Renders a speaker entry from imported dashboard JSON.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials/speakers
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $speaker ) || ! is_array( $speaker ) || empty( $speaker['name'] ) ) {
	return;
}

$speaker_name        = sanitize_text_field( $speaker['name'] );
$speaker_social      = isset( $speaker['social'] ) && is_array( $speaker['social'] ) ? $speaker['social'] : array();
$placeholder_url     = ! empty( $speaker_placeholder_url ) ? $speaker_placeholder_url : WPFAEVENT_URL . 'assets/images/speaker-placeholder.svg';
$is_featured_speaker = ! empty( $wpfa_dashboard_speaker_is_featured ) || ! empty( $speaker['featured'] );
?>
<article class="wpfa-speaker-card visible <?php echo esc_attr( $is_featured_speaker ? 'is-featured' : '' ); ?>">
	<div class="wpfa-speaker-photo">
		<?php if ( ! empty( $speaker['image'] ) ) : ?>
			<?php /* translators: %s: Speaker name. */ ?>
			<img src="<?php echo esc_url( $speaker['image'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Photo of %s', 'wpfaevent' ), $speaker_name ) ); ?>" loading="lazy">
		<?php else : ?>
			<img src="<?php echo esc_url( $placeholder_url ); ?>" alt="<?php esc_attr_e( 'Speaker photo placeholder', 'wpfaevent' ); ?>" loading="lazy" class="wpfa-speaker-placeholder-img">
		<?php endif; ?>
	</div>
	<div class="wpfa-speaker-meta">
		<?php if ( $is_featured_speaker ) : ?>
			<p class="wpfa-speaker-featured-badge"><?php esc_html_e( 'Featured Speaker', 'wpfaevent' ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $speaker['category'] ) ) : ?>
			<p class="pill"><?php echo esc_html( $speaker['category'] ); ?></p>
		<?php endif; ?>

		<h3 class="wpfa-speaker-name"><?php echo esc_html( $speaker_name ); ?></h3>
		<?php if ( ! empty( $speaker['position'] ) || ! empty( $speaker['organization'] ) ) : ?>
			<p class="wpfa-speaker-role">
				<?php echo esc_html( trim( ( $speaker['position'] ?? '' ) . ( ! empty( $speaker['position'] ) && ! empty( $speaker['organization'] ) ? ' | ' : '' ) . ( $speaker['organization'] ?? '' ) ) ); ?>
			</p>
		<?php endif; ?>
	</div>
	<div class="wpfa-speaker-expand">
		<?php if ( ! empty( $speaker['bio'] ) ) : ?>
			<div class="wpfa-speaker-bio"><?php echo wp_kses_post( wpautop( $speaker['bio'] ) ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $speaker_social ) ) : ?>
			<div class="wpfa-speaker-social">
				<?php foreach ( $speaker_social as $social_label => $social_url ) : ?>
					<?php if ( $social_url ) : ?>
						<a href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
							<?php echo esc_html( ucfirst( $social_label ) ); ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</article>
