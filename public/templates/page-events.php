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
	exit; }
get_header();

$today = current_time( 'Y-m-d' );
$args  = [
	'post_type'   => 'wpfa_event',
	'post_status' => 'publish',
	'meta_query'  => [
		[
			'key'     => 'wpfa_event_start_date',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		],
	],
	'orderby'        => 'meta_value',
	'meta_key'       => 'wpfa_event_start_date',
	'order'          => 'ASC',
	'posts_per_page' => -1,
	'fields'         => 'ids',
];
$q     = new WP_Query( $args );
?>
<main class="wpfa-events">
	<h1><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></h1>
	<?php if ( $q->have_posts() ) : ?>
		<ul class="wpfa-event-list">
		<?php
		foreach ( $q->posts as $eid ) :
			$title = get_the_title( $eid );
			$start = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );
			$end   = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_end_date', true ) );
			$loc   = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) );
			$url   = esc_url_raw( get_post_meta( $eid, 'wpfa_event_url', true ) ) ?: get_permalink( $eid );
			?>
			<li class="wpfa-event">
				<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
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
	<?php else : ?>
		<p><?php esc_html_e( 'No upcoming events.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
