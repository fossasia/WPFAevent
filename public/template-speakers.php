<?php
/**
 * Template part for displaying FOSSASIA Speakers
 * Loaded dynamically by shortcode or block.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$speakers = get_posts( [
	'post_type'      => 'wpfa_speaker',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );
?>

<div class="wpfa-speaker-grid">
	<?php foreach ( $speakers as $speaker ) : ?>
		<?php
		$org       = get_post_meta( $speaker->ID, 'wpfa_speaker_org', true );
		$role      = get_post_meta( $speaker->ID, 'wpfa_speaker_role', true );
		$photo_url = get_post_meta( $speaker->ID, 'wpfa_speaker_photo_url', true );
		?>
		<div class="wpfa-speaker-card">
			<img src="<?php echo esc_url( $photo_url ?: WPFA_DEFAULT_SPEAKER_PHOTO ); ?>" alt="<?php echo esc_attr( $speaker->post_title ); ?>">
			<h3><?php echo esc_html( $speaker->post_title ); ?></h3>
			<p class="wpfa-role"><?php echo esc_html( $role ); ?></p>
			<p class="wpfa-org"><?php echo esc_html( $org ); ?></p>
		</div>
	<?php endforeach; ?>
</div>