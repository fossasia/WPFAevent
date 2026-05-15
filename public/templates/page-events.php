<?php
/**
 * Template Name: WPFA - Events
 *
 * Displays a list of upcoming FOSSASIA events.
 * Events are filtered by start date (today or later) and ordered
 * chronologically by start date in ascending order.
 *
 * Each event displays:
 * - Event title (linked to event URL or permalink)
 * - Start and end dates
 * - Location
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wpfaevent_is_embed = ! empty( $GLOBALS['wpfaevent_template_embed'] );

if ( ! $wpfaevent_is_embed ) {
	get_header();
}

$today = current_time( 'Y-m-d' );

$events_per_page = max( 1, (int) apply_filters( 'wpfa_events_per_page', 10 ) );
$current_page    = max( 1, (int) get_query_var( 'paged', 1 ) );

$args = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter upcoming events by stored start date.
	'meta_query'     => array(
		array(
			'key'     => 'wpfa_event_start_date',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		),
	),
	'orderby'        => 'meta_value',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required to sort upcoming events by stored start date.
	'meta_key'       => 'wpfa_event_start_date',
	'meta_type'      => 'DATE',
	'order'          => 'ASC',
	'posts_per_page' => $events_per_page,
	'paged'          => $current_page,
	'fields'         => 'ids',
);

$q = new WP_Query( $args );
?>
<?php if ( $wpfaevent_is_embed ) : ?>
<section class="wpfa-events">
<?php else : ?>
<main class="wpfa-events">
<?php endif; ?>
	<h1><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></h1>
	<?php if ( $q->have_posts() ) : ?>
		<ul class="wpfa-event-list">
		<?php
		foreach ( $q->posts as $eid ) :
				$event_title = get_the_title( $eid );
				$start       = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );
				$end         = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_end_date', true ) );
				$loc         = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) );
				$event_url   = get_post_meta( $eid, 'wpfa_event_url', true );
				$event_url   = $event_url ? $event_url : get_permalink( $eid );
			?>
			<li class="wpfa-event">
					<h3><a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( $event_title ); ?></a></h3>
				<p class="wpfa-event-meta">
					<?php echo esc_html( $start . ( $end ? ' – ' . $end : '' ) ); ?>
					<?php
					if ( $loc ) :
						?>
						· <?php echo esc_html( $loc ); ?><?php endif; ?>
				</p>
			</li>
		<?php endforeach; ?>
		</ul>

		<?php
			// Pagination.
			$total = max( 1, (int) ceil( $q->found_posts / $events_per_page ) );
			wpfa_render_pagination( $total, $current_page, __( 'Events pagination', 'wpfaevent' ) );
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'No upcoming events.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
<?php if ( $wpfaevent_is_embed ) : ?>
</section>
<?php else : ?>
</main>
	<?php get_footer(); ?>
<?php endif; ?>
