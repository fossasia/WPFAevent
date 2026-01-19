<?php
/**
 * Speaker Card Partial
 *
 * Reusable template partial for displaying a single speaker card.
 * This partial is included by the Speakers template and expects
 * the $sid variable (speaker post ID) to be set in the parent scope.
 *
 * Displays:
 * - Speaker headshot (with fallback placeholder)
 * - Speaker name (linked to speaker page)
 * - Speaker position and organization
 *
 * Includes Schema.org Person microdata for SEO enhancement.
 *
 * Required Variables:
 *
 * @var int $sid Speaker post ID (must be set by parent template)
 *
 * Meta Fields Used:
 * - wpfa_speaker_headshot_url: Speaker photo URL
 * - wpfa_speaker_organization: Organization/company name
 * - wpfa_speaker_position: Job title/position
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Speaker post ID passed from parent template;
// guard prevents misuse of this partial.
if ( empty( $sid ) || ! is_numeric( $sid ) ) {
	return;
}

$sid = (int) $sid;

$name      = get_the_title( $sid );
$org       = sanitize_text_field( get_post_meta( $sid, 'wpfa_speaker_organization', true ) );
$position  = sanitize_text_field( get_post_meta( $sid, 'wpfa_speaker_position', true ) );
$photo_url = esc_url_raw( get_post_meta( $sid, 'wpfa_speaker_headshot_url', true ) ); // Removed external fallback
$link      = get_permalink( $sid );
?>
<article class="wpfa-speaker-card" itemscope itemtype="https://schema.org/Person">
	<a class="wpfa-speaker-photo" href="<?php echo esc_url( $link ); ?>">
		<?php if ( $photo_url ) : ?>
			<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" itemprop="image" />
		<?php else : ?>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" class="wpfa-placeholder-svg">
				<rect width="100%" height="100%" fill="#eee" />
				<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#999">Speaker</text>
			</svg>
		<?php endif; ?>
	</a>
	<div class="wpfa-speaker-meta">
		<h3 class="wpfa-speaker-name" itemprop="name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></h3>
		<?php if ( $position || $org ) : ?>
			<p class="wpfa-speaker-role"><?php echo esc_html( trim( $position . ( $position && $org ? ' · ' : '' ) . $org ) ); ?></p>
		<?php endif; ?>
	</div>
</article>
