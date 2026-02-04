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
 * @subpackage Wpfaevent/public/partials/speakers
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
$photo_url = get_post_meta( $sid, 'wpfa_speaker_headshot_url', true );
$link      = get_permalink( $sid );
$is_admin  = current_user_can( 'manage_options' );

// Get categories from taxonomy
$speaker_categories = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
	$terms = wp_get_post_terms( $sid, 'wpfa_speaker_category' );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$speaker_categories = wp_list_pluck( $terms, 'name' );
	}
}

// Get session details
$talk_title    = get_post_meta( $sid, 'wpfa_speaker_talk_title', true );
$talk_date     = get_post_meta( $sid, 'wpfa_speaker_talk_date', true );
$talk_time     = get_post_meta( $sid, 'wpfa_speaker_talk_time', true );
$talk_end_time = get_post_meta( $sid, 'wpfa_speaker_talk_end_time', true );
$talk_abstract = get_post_meta( $sid, 'wpfa_speaker_talk_abstract', true );
?>
<article class="wpfa-speaker-card" itemscope itemtype="https://schema.org/Person" data-speaker-id="<?php echo esc_attr( $sid ); ?>">
	<a class="wpfa-speaker-photo" href="<?php echo esc_url( $link ); ?>">
		<?php if ( $photo_url ) : ?>
			<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Photo of %s', 'wpfaevent' ), $name ) ); ?>" loading="lazy" itemprop="image" />
		<?php else : ?>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" class="wpfa-placeholder-svg">
				<rect width="100%" height="100%" fill="#eee" />
				<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#999">Speaker</text>
			</svg>
		<?php endif; ?>
	</a>
	<div class="wpfa-speaker-meta">
		<?php if ( $is_admin ) : ?>
			<button class="btn-edit-speaker" data-id="<?php echo esc_attr( $sid ); ?>" data-name="<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Edit Speaker', 'wpfaevent' ); ?>">
				✎
			</button>
			<button class="btn-delete-speaker" data-id="<?php echo esc_attr( $sid ); ?>" data-name="<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Delete Speaker', 'wpfaevent' ); ?>">
				×
			</button>
		<?php endif; ?>
		
		<?php if ( ! empty( $speaker_categories ) ) : ?>
			<p class="pill">
				<?php echo esc_html( $speaker_categories[0] ); ?>
			</p>
		<?php endif; ?>
		
		<h3 class="wpfa-speaker-name" itemprop="name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></h3>
		<?php if ( $position || $org ) : ?>
			<p class="wpfa-speaker-role"><?php echo esc_html( trim( $position . ( $position && $org ? ' · ' : '' ) . $org ) ); ?></p>
		<?php endif; ?>
	</div>
	<div class="wpfa-speaker-expand">
		<?php
		$bio = get_post_meta( $sid, 'wpfa_speaker_bio', true );
		if ( $bio ) :
			?>
			<div class="wpfa-speaker-bio">
				<?php echo wp_kses_post( wpautop( $bio ) ); ?>
			</div>
		<?php endif; ?>
		
		<?php if ( $talk_title ) : ?>
			<div class="wpfa-speaker-session">
				<h4><?php esc_html_e( 'Session Details', 'wpfaevent' ); ?></h4>
				<p><strong><?php echo esc_html( $talk_title ); ?></strong></p>
				
				<?php if ( $talk_date || $talk_time ) : ?>
					<p>
						<?php
						$date_time = array();
						if ( $talk_date ) {
							$date_time[] = esc_html( $talk_date );
						}
						if ( $talk_time ) {
							$date_time[] = esc_html( $talk_time );
							if ( $talk_end_time ) {
								$date_time[] = esc_html( $talk_end_time );
							}
						}
						echo esc_html( implode( ' • ', $date_time ) );
						?>
					</p>
				<?php endif; ?>
				
				<?php if ( $talk_abstract ) : ?>
					<div class="wpfa-talk-abstract">
						<?php echo wp_kses_post( wpautop( $talk_abstract ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<?php
		// Get social links
		$linkedin = get_post_meta( $sid, 'wpfa_speaker_linkedin', true );
		$twitter  = get_post_meta( $sid, 'wpfa_speaker_twitter', true );
		$github   = get_post_meta( $sid, 'wpfa_speaker_github', true );
		$website  = get_post_meta( $sid, 'wpfa_speaker_website', true );

		if ( $linkedin || $twitter || $github || $website ) :
			?>
			<div class="wpfa-speaker-social">
				<?php if ( $linkedin ) : ?>
					<a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
						LinkedIn
					</a>
				<?php endif; ?>
				<?php if ( $twitter ) : ?>
					<a href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
						Twitter
					</a>
				<?php endif; ?>
				<?php if ( $github ) : ?>
					<a href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
						GitHub
					</a>
				<?php endif; ?>
				<?php if ( $website ) : ?>
					<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
						Website
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</article>
