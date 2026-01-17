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
 * Later PR can add a Session CPT. For now, show a table-like schedule of events.
 */
$args   = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'meta_key'       => 'wpfa_event_start_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'posts_per_page' => -1,
	'fields'         => 'ids',
);
$q      = new WP_Query( $args );
$groups = array();
foreach ( $q->posts as $eid ) {
	$d = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );

	// Normalize date for proper chronological sorting
	if ( $d ) {
		// Convert to timestamp for sorting, keep original for display
		$timestamp = strtotime( $d );
		if ( $timestamp !== false ) {
			$sort_key     = $timestamp;
			$display_date = $d;
		} else {
			// Invalid date format - treat as TBD
			$sort_key     = PHP_INT_MAX;
			$display_date = __( 'TBD', 'wpfaevent' );
		}
	} else {
		// No date - treat as TBD
		$sort_key     = PHP_INT_MAX;
		$display_date = __( 'TBD', 'wpfaevent' );
	}

	if ( ! isset( $groups[ $sort_key ] ) ) {
		$groups[ $sort_key ] = [
			'date' => $display_date,
			'events' => [],
		];
	}
	$groups[ $sort_key ]['events'][] = $eid;

	// MVP note: sessions are not available yet.
	// A future iteration may add a Session CPT and attach
	// session IDs to each date group, e.g.:
	// $groups[ $sort_key ]['sessions'][] = $session_id;

}

// Sort by timestamp (chronological order)
ksort( $groups, SORT_NUMERIC );
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
						$title = get_the_title( $eid );
						$loc   = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) );
						$url   = get_permalink( $eid );
						?>
				<li>
					<strong><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></strong>
							<?php
							if ( $loc ) :
								?>
								— <span><?php echo esc_html( $loc ); ?></span><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endforeach; else : ?>
		<p><?php esc_html_e( 'No schedule entries yet.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
