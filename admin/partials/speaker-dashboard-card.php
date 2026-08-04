<?php
/**
 * Speaker dashboard card partial.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card variables.
 *
 * @var WP_Post $sp The speaker post object.
 */
if ( ! isset( $sp ) || ! $sp instanceof WP_Post ) {
	return;
}

$position     = get_post_meta( $sp->ID, 'wpfa_speaker_position', true );
$organization = get_post_meta( $sp->ID, 'wpfa_speaker_organization', true );
$headshot_url = get_post_meta( $sp->ID, 'wpfa_speaker_headshot_url', true );
if ( empty( $headshot_url ) ) {
	$headshot_url = get_the_post_thumbnail_url( $sp->ID, 'thumbnail' );
}

$linked_events = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_speaker_event_ids( $sp->ID ) : array();

$initials = '';
if ( ! $headshot_url ) {
	$name_parts = explode( ' ', $sp->post_title );
	$initials   = strtoupper( substr( $name_parts[0], 0, 1 ) );
	if ( count( $name_parts ) > 1 ) {
		$initials .= strtoupper( substr( end( $name_parts ), 0, 1 ) );
	}
}
?>
<div class="wpfaevent-list-item">
	<?php if ( $headshot_url ) : ?>
		<img src="<?php echo esc_url( $headshot_url ); ?>" alt="<?php echo esc_attr( $sp->post_title ); ?>">
	<?php else : ?>
		<div class="wpfaevent-list-avatar-fallback">
			<?php echo esc_html( $initials ); ?>
		</div>
	<?php endif; ?>
	<div class="wpfaevent-list-copy">
		<strong><?php echo esc_html( $sp->post_title ); ?></strong>
		<div class="description">
			<?php echo esc_html( trim( $position . ( ! empty( $organization ) ? ' - ' . $organization : '' ) ) ); ?>
		</div>
		<?php if ( ! empty( $linked_events ) ) : ?>
			<div class="wpfaevent-tag-list">
				<?php foreach ( $linked_events as $event_id ) : ?>
					<span class="wpfaevent-tag"><?php echo esc_html( get_the_title( $event_id ) ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="wpfaevent-tag-list">
				<span class="wpfaevent-tag is-standalone"><?php esc_html_e( 'Standalone', 'wpfaevent' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
</div>
