<?php
/**
 * Template Name: WPFA - Past Events
 *
 * Displays a list of past FOSSASIA events.
 * Events are filtered by end date (before today) and ordered
 * by end date in descending order (most recent first).
 *
 * Each event displays:
 * - Event title (linked to permalink)
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

$per_page = max( 1, (int) apply_filters( 'wpfa_past_events_per_page', 10 ) );
$paged    = max( 1, (int) get_query_var( 'paged', 1 ) );

$args  = [
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'meta_query'     => [
		[
			'key'     => 'wpfa_event_end_date',
			'value'   => $today,
			'compare' => '<',
			'type'    => 'DATE',
		],
	],
	'orderby'        => 'meta_value',
	'meta_key'       => 'wpfa_event_end_date',
	'meta_type'      => 'DATE',
	'order'          => 'DESC',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'fields'         => 'ids',
];

$q = new WP_Query( $args );
?>
<main class="wpfa-events">
	<h1><?php esc_html_e( 'Past Events', 'wpfaevent' ); ?></h1>
	<?php if ( $q->have_posts() ) : ?>
		<ul class="wpfa-event-list">
		<?php
		foreach ( $q->posts as $eid ) :
			$title = get_the_title( $eid );
			$start = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );
			$end   = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_end_date', true ) );
			$loc   = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) );
			$url   = get_post_meta( $eid, 'wpfa_event_url', true ) ?: get_permalink( $eid );
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

		<?php
		// ADD PAGINATION HERE:
		$total = max( 1, (int) ceil( $q->found_posts / $per_page ) );
		if ( $total > 1 ) :
			echo '<nav class="wpfa-pagination" aria-label="' . esc_attr__( 'Past events pagination', 'wpfaevent' ) . '">';
			for ( $i = 1; $i <= $total; $i++ ) {
				$link = esc_url( add_query_arg( [ 'paged' => $i ], get_permalink() ) );

				if ( $i === $paged ) {
					printf(
						'<span class="wpfa-page is-current" aria-current="page">%d</span>',
						$i
					);
				} else {
					printf(
						'<a class="wpfa-page" href="%s">%d</a>',
						$link,
						$i
					);
				}
			}
			echo '</nav>';
		endif;
		?>

	<?php else : ?>
		<p><?php esc_html_e( 'No past events found.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
