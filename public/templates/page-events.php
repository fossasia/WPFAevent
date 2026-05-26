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

get_header();

$today = current_time( 'Y-m-d' );

$events_per_page = max( 1, (int) apply_filters( 'wpfa_events_per_page', 10 ) );
$current_page    = max( 1, (int) get_query_var( 'paged', 1 ) );

$args = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
);

$event_ids       = get_posts( $args );
$upcoming_events = array();

foreach ( $event_ids as $eid ) {
	$start = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );

	if ( empty( $start ) || $start < $today ) {
		continue;
	}

	$upcoming_events[] = array(
		'id'    => (int) $eid,
		'start' => $start,
	);
}

usort(
	$upcoming_events,
	static function ( $event_a, $event_b ) {
		$date_compare = strcmp( $event_a['start'], $event_b['start'] );

		if ( 0 !== $date_compare ) {
			return $date_compare;
		}

		if ( $event_a['id'] === $event_b['id'] ) {
			return 0;
		}

		return ( $event_a['id'] < $event_b['id'] ) ? -1 : 1;
	}
);

$total_events = count( $upcoming_events );
$offset       = ( $current_page - 1 ) * $events_per_page;
$paged_events = array_slice( $upcoming_events, $offset, $events_per_page );
?>
<main class="wpfa-events">
	<h1><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></h1>
	<?php if ( $paged_events ) : ?>
		<ul class="wpfa-event-list">
		<?php
		foreach ( $paged_events as $event ) :
			$eid         = $event['id'];
			$event_title = get_the_title( $eid );
			$start       = $event['start'];
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
		$total = max( 1, (int) ceil( $total_events / $events_per_page ) );
		wpfa_render_pagination( $total, $current_page, __( 'Events pagination', 'wpfaevent' ) );
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'No upcoming events.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
