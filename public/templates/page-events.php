<?php
/**
 * Template Name: WPFA - Events
 *
 * An interactive Events Hub for the FOSSASIA network.
 * Key Features:
 * - Real-time client-side event filtering by name, location, and description.
 * - Dynamic Meta Queries: Public users see upcoming events; Admins see all events.
 * - Integrated Admin CRUD: Front-end tools for event management via AJAX modals.
 * - Shows both upcoming AND past events on the same page.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$today           = current_time( 'Y-m-d' );
$events_per_page = max( 1, (int) apply_filters( 'wpfa_events_per_page', 10 ) );
$current_page    = max( 1, (int) get_query_var( 'paged', 1 ) );
$is_admin        = Wpfaevent_Roles::current_user_can_manage_dashboard();

// Pull all published event IDs to replicate upstream's data handling pattern.
$args = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => true,
);

$event_ids       = get_posts( $args );
$upcoming_events = array();
$past_events     = array();

foreach ( $event_ids as $eid ) {
	$start    = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_start_date', true ) );
	$end      = sanitize_text_field( get_post_meta( $eid, 'wpfa_event_end_date', true ) );
	$is_valid = ! empty( $start );

	// Admins can see items even if the start date metadata is missing or broken.
	if ( ! $is_valid && $is_admin ) {
		$upcoming_events[] = array(
			'id'    => (int) $eid,
			'start' => '',
			'end'   => $end,
		);
		continue;
	}

	if ( ! $is_valid ) {
		continue;
	}

	// Route events into upcoming or past piles depending on target timestamp values.
	$is_upcoming = ( $start >= $today || ( ! empty( $end ) && $end >= $today ) );

	if ( $is_upcoming ) {
		$upcoming_events[] = array(
			'id'    => (int) $eid,
			'start' => $start,
			'end'   => $end,
		);
	} else {
		$past_events[] = array(
			'id'    => (int) $eid,
			'start' => $start,
			'end'   => $end,
		);
	}
}

// Sort upcoming events chronologically ascending (Soonest first).
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

// Sort past events chronologically descending (Most recent historical first).
usort(
	$past_events,
	static function ( $event_a, $event_b ) {
		$date_a       = ! empty( $event_a['end'] ) ? $event_a['end'] : $event_a['start'];
		$date_b       = ! empty( $event_b['end'] ) ? $event_b['end'] : $event_b['start'];
		$date_compare = strcmp( $date_b, $date_a );
		if ( 0 !== $date_compare ) {
			return $date_compare;
		}
		if ( $event_a['id'] === $event_b['id'] ) {
			return 0;
		}
		return ( $event_a['id'] > $event_b['id'] ) ? -1 : 1;
	}
);

$total_upcoming = count( $upcoming_events );
$total_past     = count( $past_events );
$total_events   = $total_upcoming + $total_past;

// Slice upcoming list into active views according to pagination offsets.
$offset       = ( $current_page - 1 ) * $events_per_page;
$paged_events = array_slice( $upcoming_events, $offset, $events_per_page );

// Limit displayed past events on this hub dashboard to the top 6.
$displayed_past_events = array_slice( $past_events, 0, 6 );

// Set up custom layout header configurations.
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

	<main>
		<!-- Hero Section -->
		<header class="page-hero">
			<div class="hero-bg" aria-hidden="true"></div>
			<h1><?php esc_html_e( 'FOSSASIA Events', 'wpfaevent' ); ?></h1>
			<p><?php esc_html_e( 'Discover upcoming community events, local meetups, and partner conferences from the FOSSASIA network.', 'wpfaevent' ); ?></p>
		</header>

		<div class="container">
			<div class="page-layout">
				<div class="main-content">
					<div class="main-content-header">
						<h1><?php esc_html_e( 'Events', 'wpfaevent' ); ?></h1>
						<?php if ( $is_admin ) : ?>
							<button id="createEventBtn" class="btn btn-primary">
								<?php esc_html_e( 'Create Custom Event', 'wpfaevent' ); ?>
							</button>
						<?php endif; ?>
					</div>

					<!-- Search Section -->
					<div class="wpfaevent-search-section">
						<form class="wpfa-search-form" onsubmit="return false;">
							<label for="eventSearchInput" class="screen-reader-text"><?php esc_html_e( 'Search events', 'wpfaevent' ); ?></label>
							<input type="search" id="eventSearchInput" class="wpfaevent-search-input" placeholder="<?php esc_attr_e( 'Search by name, place, or description...', 'wpfaevent' ); ?>">
							<button type="submit" class="screen-reader-text"><?php esc_html_e( 'Search', 'wpfaevent' ); ?></button>
						</form>
					</div>

					<div class="results-info">
						<?php esc_html_e( 'Showing', 'wpfaevent' ); ?> <span id="resultsCount"><?php echo esc_html( $total_events ); ?></span> <?php esc_html_e( 'events', 'wpfaevent' ); ?>
					</div>

					<!-- Events Container for UPCOMING events -->
					<div id="events-container">
						<?php if ( ! empty( $paged_events ) ) : ?>
							<?php
							foreach ( $paged_events as $event ) :
								$event_id = $event['id'];
								// Setup the global post context for structural template partial dependencies.
								$post = get_post( $event_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								setup_postdata( $post );

								include WPFAEVENT_PATH . 'public/partials/events/event-card.php';
							endforeach;
							wp_reset_postdata();
							?>
						<?php else : ?>
							<p class="placeholder-text"><?php esc_html_e( 'No upcoming events found.', 'wpfaevent' ); ?></p>
						<?php endif; ?>
					</div>

					<?php
					// Render pagination using upstream's collection counter tracking.
					$total_pages = max( 1, (int) ceil( $total_upcoming / $events_per_page ) );
					if ( $total_pages > 1 ) {
						wpfa_render_pagination( $total_pages, $current_page, __( 'Events pagination', 'wpfaevent' ) );
					}
					?>

					<!-- Past Events Section (if there are past events) -->
					<?php if ( ! empty( $displayed_past_events ) ) : ?>
						<div class="past-events-section">
							<div class="main-content-header">
								<h2><?php esc_html_e( 'Past Events', 'wpfaevent' ); ?></h2>
							</div>

							<!-- Past Events Container -->
							<div id="past-events-container" class="events-container">
								<?php
								foreach ( $displayed_past_events as $pevent ) :
									$event_id = $pevent['id'];

									// Extract historical metadata directly.
									$event_date        = $pevent['start'];
									$event_end_date    = $pevent['end'];
									$event_place       = get_post_meta( $event_id, 'wpfa_event_location', true );
									$event_description = get_the_excerpt( $event_id );
									$thumbnail_raw     = get_the_post_thumbnail_url( $event_id, 'large' );
									$featured_img_url  = ! empty( $thumbnail_raw ) ? $thumbnail_raw : '';

									// Format descriptive timeline layout markers.
									$formatted_date = __( 'Date not set', 'wpfaevent' );
									if ( ! empty( $event_date ) ) {
										if ( ! empty( $event_end_date ) && $event_end_date !== $event_date ) {
											$formatted_date = date_i18n( 'M j', strtotime( $event_date ) ) . ' - ' . date_i18n( 'M j, Y', strtotime( $event_end_date ) );
										} else {
											$formatted_date = date_i18n( 'F j, Y', strtotime( $event_date ) );
										}
									}
									?>

									<div class="event-card past-event-card"
										data-post-id="<?php echo esc_attr( $event_id ); ?>"
										data-name="<?php echo esc_attr( get_the_title( $event_id ) ); ?>"
										data-date="<?php echo esc_attr( $event_date ); ?>"
										data-place="<?php echo esc_attr( $event_place ); ?>"
										data-end-date="<?php echo esc_attr( $event_end_date ); ?>"
										data-description="<?php echo esc_attr( $event_description ); ?>">

										<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="event-card-link">
											<div class="event-card-image">
												<img src="<?php echo esc_url( $featured_img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>">
											</div>
											<div class="event-card-content">
												<h3><?php echo esc_html( get_the_title( $event_id ) ); ?></h3>
												<p>
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
														<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path>
													</svg>
													<?php echo esc_html( $formatted_date ); ?>
												</p>
												<?php if ( ! empty( $event_place ) ) : ?>
												<p>
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
														<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
													</svg>
													<?php echo esc_html( $event_place ); ?>
												</p>
												<?php endif; ?>
												<?php if ( ! empty( $event_description ) ) : ?>
													<p class="event-card-description"><?php echo esc_html( $event_description ); ?></p>
												<?php endif; ?>
											</div>
										</a>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<!-- Calendar/Archive Link -->
					<div class="wpfaevent-calendar-link-section">
						<a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>" class="wpfaevent-archive-link">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="wpfaevent-icon-archive">
								<path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"></path>
							</svg>
							<span><?php esc_html_e( 'View Full Past Events Archive', 'wpfaevent' ); ?></span>
						</a>
					</div>
				</div>

				<!-- Sidebar with Latest News -->
				<aside class="sidebar">
					<h2><?php esc_html_e( 'Latest News', 'wpfaevent' ); ?></h2>
					<?php
					if ( function_exists( 'wpfa_render_latest_news' ) ) {
						wpfa_render_latest_news();
					} else {
						echo '<p class="news-loading-text">' . esc_html__( 'Latest news feed loading...', 'wpfaevent' ) . '</p>';

						if ( Wpfaevent_Roles::current_user_can_manage_dashboard() ) {
							echo '<div class="wpfaevent-admin-warning">';
								echo '<svg class="wpfaevent-warning-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">';
									echo '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>';
								echo '</svg>';
								echo '<div>';
									echo '<strong>' . esc_html__( 'Admin Technical Note:', 'wpfaevent' ) . '</strong><br>';
									esc_html_e( 'The wpfa_render_latest_news() function is undefined. Please ensure the News module or related plugin is activated.', 'wpfaevent' );
								echo '</div>';
							echo '</div>';
						}
					}
					?>
				</aside>
			</div>
		</div>
	</main>

	<!-- Include Footer Custom Partials -->
	<?php require WPFAEVENT_PATH . 'public/partials/footer.php'; ?>
</div>

<?php
if ( $is_admin ) {
	include WPFAEVENT_PATH . 'public/partials/events/event-modal.php';
}
?>

<?php wp_footer(); ?>
</body>
</html>
