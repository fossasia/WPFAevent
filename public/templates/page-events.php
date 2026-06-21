<?php
/**
 * Template Name: WPFA - Events
 *
 * An interactive Events Hub for the FOSSASIA network.
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
$today              = current_time( 'Y-m-d' );
$is_admin           = current_user_can( 'manage_options' );

// Pull all published event IDs.
$event_ids = get_posts( array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
) );

$upcoming_events = array();
$past_events     = array();

foreach ( $event_ids as $eid ) {
	$start    = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );
	$end      = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_end_date', true ) );
	$is_valid = ! empty( $start );

	if ( ! $is_valid && $is_admin ) {
		$upcoming_events[] = array( 'id' => (int) $eid, 'start' => '', 'end' => $end, 'is_past' => false );
		continue;
	}
	if ( ! $is_valid ) {
		continue;
	}

	$is_upcoming = ( $start >= $today || ( ! empty( $end ) && $end >= $today ) );
	if ( $is_upcoming ) {
		$upcoming_events[] = array( 'id' => (int) $eid, 'start' => $start, 'end' => $end, 'is_past' => false );
	} else {
		$past_events[] = array( 'id' => (int) $eid, 'start' => $start, 'end' => $end, 'is_past' => true );
	}
}

// Sort upcoming chronologically ascending.
usort( $upcoming_events, static function ( $a, $b ) {
	$c = strcmp( $a['start'], $b['start'] );
	return 0 !== $c ? $c : ( $a['id'] < $b['id'] ? -1 : 1 );
} );

// Sort past chronologically descending (most-recent first).
usort( $past_events, static function ( $a, $b ) {
	$da = ! empty( $a['end'] ) ? $a['end'] : $a['start'];
	$db = ! empty( $b['end'] ) ? $b['end'] : $b['start'];
	$c  = strcmp( $db, $da );
	return 0 !== $c ? $c : ( $a['id'] > $b['id'] ? -1 : 1 );
} );

// Merge all events: upcoming first, then past.
$all_events   = array_merge( $upcoming_events, $past_events );
$total_events = count( $all_events );

// Track taxonomy terms for filter dropdown.
$track_terms = get_terms( array(
	'taxonomy'   => 'wpfa_event_track',
	'hide_empty' => false,
	'orderby'    => 'name',
) );
$track_terms = is_wp_error( $track_terms ) ? array() : $track_terms;

// Collect unique locations for filter dropdown.
$location_options = array();
foreach ( $event_ids as $eid ) {
	$loc = trim( sanitize_text_field( get_post_meta( $eid, 'wpfa_event_location', true ) ) );
	if ( '' !== $loc ) {
		$location_options[ $loc ] = $loc;
	}
}
ksort( $location_options );

// Header config.
$header_vars = array(
	'site_logo_url'        => apply_filters( 'wpfa_site_logo_url', get_option( 'wpfa_site_logo_url', WPFAEVENT_URL . 'assets/images/logo.png' ) ),
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => false,
	'show_register_button' => false,
	'back_button_text'     => __( 'Back to Hub', 'wpfaevent' ),
	'register_button_url'  => '#',
	'register_button_text' => __( 'Register', 'wpfaevent' ),
);
?>
<?php if ( ! $wpfaevent_is_embed ) : ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent' ); ?>>
	<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
	$site_logo_url        = $header_vars['site_logo_url'];
	$event_page_url       = $header_vars['event_page_url'];
	$show_back_button     = $header_vars['show_back_button'];
	$show_register_button = $header_vars['show_register_button'];
	$back_button_text     = $header_vars['back_button_text'];
	$register_button_url  = $header_vars['register_button_url'];
	$register_button_text = $header_vars['register_button_text'];

	$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
	if ( file_exists( $nav_partial ) ) {
		include $nav_partial;
	}
	?>
<?php endif; ?>

<?php if ( $wpfaevent_is_embed ) : ?>
	<section class="wpfa-events">
<?php else : ?>
	<main>
<?php endif; ?>

		<!-- Hero Section -->
		<header class="events-hub-hero">
			<div class="container">
				<div class="events-hub-hero-inner">
					<div class="events-hub-hero-text">
						<p class="events-hub-eyebrow"><?php esc_html_e( 'EVENT DIRECTORY', 'wpfaevent' ); ?></p>
						<h1><?php esc_html_e( 'Open source events from the FOSSASIA community', 'wpfaevent' ); ?></h1>
						<p class="events-hub-subtitle"><?php esc_html_e( 'Search by event name, topic, track, date, location, and language and find the right event faster.', 'wpfaevent' ); ?></p>
					</div>
					<div class="events-hub-stat-box">
						<span class="events-hub-stat-number"><?php echo esc_html( $total_events ); ?></span>
						<span class="events-hub-stat-label"><?php esc_html_e( 'PUBLISHED EVENTS', 'wpfaevent' ); ?></span>
					</div>
				</div>
			</div>
		</header>

		<!-- Filter Section -->
		<section class="events-filter-section">
			<div class="container">
				<div class="events-filter-card">
					<h2><?php esc_html_e( 'Find Events', 'wpfaevent' ); ?></h2>
					<p><?php esc_html_e( 'Filter events by name, topic, track, date, location, and language.', 'wpfaevent' ); ?></p>

					<div class="events-filter-grid">
						<div class="filter-group filter-group--search">
							<label for="eventSearchInput"><?php esc_html_e( 'SEARCH EVENTS', 'wpfaevent' ); ?></label>
							<input type="search" id="eventSearchInput" class="filter-input" placeholder="<?php esc_attr_e( 'Search by event name or topic', 'wpfaevent' ); ?>">
						</div>

						<div class="filter-group">
							<label for="filterTrack"><?php esc_html_e( 'TRACK', 'wpfaevent' ); ?></label>
							<select id="filterTrack" class="filter-select">
								<option value=""><?php esc_html_e( 'All tracks', 'wpfaevent' ); ?></option>
								<?php foreach ( $track_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="filter-group">
							<label for="filterTopic"><?php esc_html_e( 'TOPIC', 'wpfaevent' ); ?></label>
							<select id="filterTopic" class="filter-select">
								<option value=""><?php esc_html_e( 'All topics', 'wpfaevent' ); ?></option>
							</select>
						</div>

						<div class="filter-group">
							<label for="filterLocation"><?php esc_html_e( 'LOCATION', 'wpfaevent' ); ?></label>
							<select id="filterLocation" class="filter-select">
								<option value=""><?php esc_html_e( 'All locations', 'wpfaevent' ); ?></option>
								<?php foreach ( $location_options as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc ); ?>"><?php echo esc_html( $loc ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="filter-group">
							<label for="filterLanguage"><?php esc_html_e( 'LANGUAGE', 'wpfaevent' ); ?></label>
							<select id="filterLanguage" class="filter-select">
								<option value=""><?php esc_html_e( 'All languages', 'wpfaevent' ); ?></option>
							</select>
						</div>
					</div>

					<div class="events-filter-footer">
						<div class="date-filter-tabs" role="group" aria-label="<?php esc_attr_e( 'Filter by date', 'wpfaevent' ); ?>">
							<button class="date-filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'wpfaevent' ); ?></button>
							<button class="date-filter-btn" data-filter="upcoming"><?php esc_html_e( 'Upcoming', 'wpfaevent' ); ?></button>
							<button class="date-filter-btn" data-filter="past"><?php esc_html_e( 'Past', 'wpfaevent' ); ?></button>
						</div>
						<button id="searchEventsBtn" class="btn btn-primary">
							<?php esc_html_e( 'Search Events', 'wpfaevent' ); ?>
						</button>
					</div>
				</div>
			</div>
		</section>

		<!-- Events List Section -->
		<section class="events-list-section">
			<div class="container">
				<div class="events-list-header">
					<h2><?php esc_html_e( 'Events', 'wpfaevent' ); ?></h2>
					<p class="results-info">
						<?php esc_html_e( 'Showing', 'wpfaevent' ); ?>
						<span id="resultsCount"><?php echo esc_html( $total_events ); ?></span>
						<?php esc_html_e( 'of', 'wpfaevent' ); ?>
						<span id="totalCount"><?php echo esc_html( $total_events ); ?></span>
						<?php esc_html_e( 'matching events', 'wpfaevent' ); ?>
					</p>
				</div>

				<div id="events-container">
					<?php if ( ! empty( $all_events ) ) : ?>
						<?php foreach ( $all_events as $event ) :
							$event_id = $event['id'];
							$_is_past = $event['is_past'];
							$post = get_post( $event_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );

							include WPFAEVENT_PATH . 'public/partials/events/event-card.php';
						endforeach;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<p class="placeholder-text"><?php esc_html_e( 'No events found.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>

<?php if ( $wpfaevent_is_embed ) : ?>
	</section>
<?php else : ?>
	</main>

	<?php require WPFAEVENT_PATH . 'public/partials/footer.php'; ?>
</div>
<?php endif; ?>

<?php
if ( $is_admin ) {
	include WPFAEVENT_PATH . 'public/partials/events/event-modal.php';
}
?>

<?php if ( ! $wpfaevent_is_embed ) : ?>
	<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>
