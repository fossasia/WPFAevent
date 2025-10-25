<?php
/**
 * Template part for displaying FOSSASIA Events
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$events = get_posts( [
	'post_type'      => 'wpfa_event',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
] );
?>

<ul class="wpfa-event-list">
	<?php foreach ( $events as $event ) : ?>
		<?php $event_date = get_post_meta( $event->ID, 'wpfa_event_date', true ); ?>
		<li>
			<h3><?php echo esc_html( $event->post_title ); ?></h3>
			<span class="wpfa-date">
				<?php echo esc_html( $event_date ); ?>
			</span>
		</li>
	<?php endforeach; ?>
</ul>