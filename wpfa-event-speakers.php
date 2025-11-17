<?php
/**
 * Provides the view for the speakers shortcode.
 *
 * @link       https://fossasia.org
 */

$json_file_path = WPFAEVENT_PATH . 'assets/data/minimal.json';
$speakers       = array();

if ( file_exists( $json_file_path ) ) {
	$json_content = file_get_contents( $json_file_path );
	$data         = json_decode( $json_content, true );

	if ( json_last_error() === JSON_ERROR_NONE && isset( $data['speakers'] ) && is_array( $data['speakers'] ) ) {
		$speakers = $data['speakers'];
	}
}
?>

<div class="wpfa-event-speakers">
	<?php if ( ! empty( $speakers ) ) : ?>
		<div class="speakers-list">
			<?php foreach ( $speakers as $speaker ) : ?>
				<div class="speaker-item" id="speaker-<?php echo esc_attr( $speaker['slug'] ); ?>">
					<?php if ( ! empty( $speaker['photo'] ) ) : ?>
						<img src="<?php echo esc_url( $speaker['photo'] ); ?>" alt="<?php echo esc_attr( $speaker['title'] ); ?>" class="speaker-photo">
					<?php endif; ?>
					<div class="speaker-details">
						<h3 class="speaker-title"><?php echo esc_html( $speaker['title'] ); ?></h3>
						<?php if ( ! empty( $speaker['position'] ) && ! empty( $speaker['org'] ) ) : ?>
							<p class="speaker-org-position">
								<?php echo esc_html( $speaker['position'] ); ?>, <strong><?php echo esc_html( $speaker['org'] ); ?></strong>
							</p>
						<?php endif; ?>
						<div class="speaker-content">
							<?php echo wp_kses_post( $speaker['content'] ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p>No speakers found or data could not be loaded.</p>
	<?php endif; ?>
</div>