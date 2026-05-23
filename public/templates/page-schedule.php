<?php
/**
 * Template Name: WPFA - Schedule
 *
 * Displays a schedule of FOSSASIA events grouped by date.
 * Events are ordered chronologically by start date, with
 * each date forming a section header.
 *
 * MVP Implementation Note:
 * This template displays events grouped by date. A future version
 * may add a Session CPT to display individual session schedules.
 *
 * Each event displays:
 * - Event title (linked to permalink)
 * - Location
 *
 * Events without a start date are grouped under "TBD".
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

/**
 * MVP parity: we don’t have sessions yet, so render events grouped by date.
 * Later PRs can add a Session CPT. For now, show a table-like schedule of events.
 */

$events_per_page = max( 1, (int) apply_filters( 'wpfa_schedule_events_per_page', 20 ) );
$current_page    = max( 1, (int) get_query_var( 'paged', 1 ) );

$args = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
);

$event_ids       = get_posts( $args );
$schedule_events = array();

foreach ( $event_ids as $eid ) {
	$d = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );

	// Normalize the date for proper chronological sorting.
	if ( $d ) {
		// Convert to a timestamp for sorting, and keep the original for display.
		// Use explicit formats for predictable parsing instead of relying on strtotime().
		$timestamp = false;

		// Adjust or extend this list if other canonical formats are stored for wpfa_event_start_date.
		$date_formats = array(
			'Y-m-d',
			'Y-m-d H:i:s',
		);

		foreach ( $date_formats as $date_format ) {
			$dt = DateTimeImmutable::createFromFormat( $date_format, $d );
			if ( $dt instanceof DateTimeInterface ) {
				$timestamp = $dt->getTimestamp();
				break;
			}
		}

		if ( false !== $timestamp ) {
			$sort_key     = $timestamp;
			$display_date = $d;
		} else {
			// Invalid or unexpected date format; treat it as TBD.
			$sort_key     = PHP_INT_MAX;
			$display_date = __( 'TBD', 'wpfaevent' );
		}
	} else {
		// No date; treat it as TBD.
		$sort_key     = PHP_INT_MAX;
		$display_date = __( 'TBD', 'wpfaevent' );
	}

	$schedule_events[] = array(
		'id'       => (int) $eid,
		'sort_key' => $sort_key,
		'date'     => $display_date,
	);
}

usort(
	$schedule_events,
	static function ( $event_a, $event_b ) {
		if ( $event_a['sort_key'] === $event_b['sort_key'] ) {
			if ( $event_a['id'] === $event_b['id'] ) {
				return 0;
			}

			return ( $event_a['id'] < $event_b['id'] ) ? -1 : 1;
		}

		return ( $event_a['sort_key'] < $event_b['sort_key'] ) ? -1 : 1;
	}
);

$total_events          = count( $schedule_events );
$offset                = ( $current_page - 1 ) * $events_per_page;
$paged_schedule_events = array_slice( $schedule_events, $offset, $events_per_page );
$groups                = array();

foreach ( $paged_schedule_events as $schedule_event ) {
	$eid          = $schedule_event['id'];
	$sort_key     = $schedule_event['sort_key'];
	$display_date = $schedule_event['date'];

	if ( ! isset( $groups[ $sort_key ] ) ) {
		$groups[ $sort_key ] = array(
			'date'   => $display_date,
			'events' => array(),
		);
	}
	$groups[ $sort_key ]['events'][] = $eid;

	// MVP note: sessions are not available yet.
	// A future iteration may add a Session CPT and attach
	// session IDs to each date group in a future release.

}
?>
<main class="wpfa-schedule">
	<h1><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></h1>
	<?php
	if ( $groups ) :
		foreach ( $groups as $group ) :
			?>
			<h2 class="wpfa-schedule-date"><?php echo esc_html( $group['date'] ); ?></h2>
			<ul class="wpfa-schedule-items">
					<?php
					foreach ( $group['events'] as $eid ) :
						$event_title = get_the_title( $eid );
						$loc = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) );
						$url = get_permalink( $eid );
					?>
				<li>
				<strong><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $event_title ); ?></a></strong>
					<?php
						if ( $loc ) :
					?>
					— <span><?php echo esc_html( $loc ); ?></span><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endforeach; // End foreach groups. ?>
		<?php
		// Pagination.
		$total = max( 1, (int) ceil( $total_events / $events_per_page ) );
		wpfa_render_pagination( $total, $current_page, __( 'Schedule pagination', 'wpfaevent' ) );
		?>

	<?php else : ?>
		<p><?php esc_html_e( 'No schedule entries yet.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
