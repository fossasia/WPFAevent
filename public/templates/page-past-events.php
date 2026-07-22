<?php
/**
 * Template Name: WPFA - Past Events
 *
 * Displays a list of past FOSSASIA events.
 * Events are filtered by end date (before today) and ordered
 * by end date in descending order (most recent first).
 *
 * Each event displays:
 * - Event featured image
 * - Event title (linked to permalink or external URL)
 * - Social share buttons
 * - Event excerpt/description
 * - Start and end dates
 * - Location
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
	exit;
}

$wpfaevent_is_embed = ! empty( $GLOBALS['wpfaevent_template_embed'] );

/**
 * Filters the number of past events to display per page.
 *
 * @since 1.0.0
 *
 * @param int $posts_per_page Number of past events per page. Default 6.
 */
$wpfa_past_events_per_page = max( 1, (int) apply_filters( 'wpfa_past_events_per_page', 6 ) );
$wpfa_past_events_paged    = max( 1, (int) get_query_var( 'paged', 1 ) );

// Query past events (end date before today).
$today = current_time( 'Y-m-d' );
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for date-based past events query.
$args = array(
	'post_type'      => 'wpfa_event',
	'post_status'    => 'publish',
	'meta_query'     => array(
		array(
			'key'     => 'wpfa_event_end_date',
			'value'   => $today,
			'compare' => '<',
			'type'    => 'DATE',
		),
	),
	'orderby'        => 'meta_value',
	'meta_key'       => 'wpfa_event_end_date',
	'meta_type'      => 'DATE',
	'order'          => 'DESC',
	'posts_per_page' => $wpfa_past_events_per_page,
	'paged'          => $wpfa_past_events_paged,
);

$query = new WP_Query( $args );
// phpcs:enable

// Get site logo.
$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

// Set up header variables for the partial.
$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => false,
	'show_register_button' => false,
	'back_button_text'     => __( 'Back to Event', 'wpfaevent' ),
	'register_button_url'  => '',
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
	// Load shared navigation header.
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
	<section class="wpfa-past-events">
	<?php else : ?>
	<main class="wpfa-past-events">
	<?php endif; ?>
		<section class="wpfa-past-events-hero">
			<div class="container">
				<h1><?php esc_html_e( 'Past FOSSASIA Events', 'wpfaevent' ); ?></h1>
				<p><?php esc_html_e( 'A look back at our community events, meetups, and conferences.', 'wpfaevent' ); ?></p>
				<div class="hero-ctas">
					<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn-secondary">
						<?php esc_html_e( 'View Upcoming Events', 'wpfaevent' ); ?>
					</a>
				</div>
			</div>
		</section>

		<div class="container">
			<?php if ( $query->have_posts() ) : ?>
				<div class="wpfa-results-info">
					<?php
					$wpfa_past_events_showing = count( $query->posts );
					$wpfa_past_events_total   = (int) $query->found_posts;
					printf(
						/* translators: %1$d: number of events showing, %2$d: total events found */
						esc_html__( 'Showing %1$d of %2$d past events', 'wpfaevent' ),
						absint( $wpfa_past_events_showing ),
						absint( $wpfa_past_events_total )
					);
					?>
				</div>

				<div class="wpfa-past-events-grid" id="wpfa-past-events-grid">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						?>
						<?php
						$event_id      = get_the_ID();
						$event_title   = get_the_title();
						$excerpt       = get_the_excerpt();
						$start_date    = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
						$end_date      = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
						$location      = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
						$event_url_raw = get_post_meta( $event_id, 'wpfa_event_url', true );
						$event_url     = $event_url_raw ? esc_url( $event_url_raw ) : get_permalink();
						$share_url     = apply_filters( 'wpfa_past_event_share_url', get_permalink( $event_id ), $event_id );
						$share_url     = esc_url_raw( $share_url );
						$share_text    = apply_filters(
							'wpfa_past_event_share_text',
							sprintf(
								/* translators: %s: event title */
								__( 'Check out this past event: %s', 'wpfaevent' ),
								$event_title
							),
							$event_id,
							$event_title
						);
						$share_text    = wp_strip_all_tags( $share_text );
						$calendar_data = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_calendar_data( $event_id ) : array();
						$calendar_data = is_wp_error( $calendar_data ) ? array() : $calendar_data;
						$share_links   = array();

						if ( ! empty( $share_url ) ) {
							$share_links = array(
								array(
									'label' => __( 'Facebook', 'wpfaevent' ),
									'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $share_url ),
								),
								array(
									'label' => __( 'X', 'wpfaevent' ),
									'url'   => 'https://twitter.com/intent/tweet?url=' . rawurlencode( $share_url ) . '&text=' . rawurlencode( $share_text ),
								),
								array(
									'label' => __( 'LinkedIn', 'wpfaevent' ),
									'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $share_url ),
								),
								array(
									'label' => __( 'WhatsApp', 'wpfaevent' ),
									'url'   => 'https://api.whatsapp.com/send?text=' . rawurlencode( $share_text . ' ' . $share_url ),
								),
							);
						}
						$image_url = get_the_post_thumbnail_url( $event_id, 'medium' );
						if ( ! $image_url ) {
							$image_url = get_post_meta( $event_id, 'wpfa_event_logo_url', true );
						}
						if ( ! $image_url ) {
							$image_url = get_post_meta( $event_id, 'wpfa_event_header_image_url', true );
						}
						if ( ! $image_url ) {
							$upload_dir = wp_upload_dir();
							if ( empty( $upload_dir['error'] ) ) {
								$settings_file = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data/site-settings-' . absint( $event_id ) . '.json';
								if ( file_exists( $settings_file ) && is_readable( $settings_file ) ) {
									$settings_contents = file_get_contents( $settings_file );
									if ( $settings_contents ) {
										$settings_data = json_decode( $settings_contents, true );
										if ( is_array( $settings_data ) ) {
											if ( ! empty( $settings_data['event_logo_url'] ) ) {
												$image_url = $settings_data['event_logo_url'];
											} elseif ( ! empty( $settings_data['event_header_image_url'] ) ) {
												$image_url = $settings_data['event_header_image_url'];
											}
										}
									}
								}
							}
						}
						$image_url = $image_url ? $image_url : '';
						// Format date for display.
						$display_date      = '';
						$display_time_meta = '';

						if ( ! empty( $calendar_data['date_label'] ) ) {
							$display_date      = sanitize_text_field( $calendar_data['date_label'] );
							$display_time_meta = ! empty( $calendar_data['time_label'] ) ? sanitize_text_field( $calendar_data['time_label'] ) : '';

							if ( $display_time_meta && empty( $calendar_data['all_day'] ) && ! empty( $calendar_data['timezone_label'] ) ) {
								$display_time_meta .= ' (' . sanitize_text_field( $calendar_data['timezone_label'] ) . ')';
							}
						} elseif ( ! empty( $start_date ) ) {
							$start_datetime = date_create( $start_date );

							if ( $start_datetime instanceof DateTime ) {

								// Default: single-day event.
								$display_date = date_i18n(
									get_option( 'date_format' ),
									$start_datetime->getTimestamp()
								);

								// Multi-day event.
								if ( ! empty( $end_date ) && $end_date !== $start_date ) {
									$end_datetime = date_create( $end_date );

									if ( $end_datetime instanceof DateTime ) {
										$display_date =
											date_i18n( get_option( 'date_format' ), $start_datetime->getTimestamp() )
											. ' – ' .
											date_i18n( get_option( 'date_format' ), $end_datetime->getTimestamp() );
									}
								}
							}
						}

						// If no excerpt, strip tags and get a trimmed version of content.
						if ( empty( $excerpt ) ) {
							$content = get_post_field( 'post_content', $event_id );
							$excerpt = wp_trim_words( $content, 20, '...' );
						}
						?>
						<article class="wpfa-past-event-card">
							<a href="<?php echo esc_url( $event_url ); ?>" class="wpfa-past-event-card-link">
								<?php if ( $image_url ) : ?>
									<div class="wpfa-past-event-card-image">
										<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" loading="lazy" />
									</div>
								<?php else : ?>
									<div class="wpfa-past-event-card-image">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 200" class="wpfa-placeholder-svg">
											<rect width="100%" height="100%" fill="#f0f0f0" />
											<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="16" fill="#999">
												<?php esc_html_e( 'Event Image', 'wpfaevent' ); ?>
											</text>
										</svg>
									</div>
								<?php endif; ?>
								<div class="wpfa-past-event-card-content">
									<h3 class="wpfa-past-event-card-title">
										<?php echo esc_html( $event_title ); ?>
									</h3>
									
									<?php if ( ! empty( $excerpt ) ) : ?>
										<p class="wpfa-past-event-card-description">
											<?php echo esc_html( $excerpt ); ?>
										</p>
									<?php endif; ?>
									
									<div class="wpfa-past-event-card-meta">
										<?php if ( $display_date ) : ?>
											<div class="wpfa-past-event-card-meta-item">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
													<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path>
												</svg>
												<?php echo esc_html( $display_date ); ?>
												<?php if ( $display_time_meta ) : ?>
													<span class="wpfa-past-event-card-time"><?php echo esc_html( ' | ' . $display_time_meta ); ?></span>
												<?php endif; ?>
											</div>
										<?php endif; ?>
										<?php if ( $location ) : ?>
											<div class="wpfa-past-event-card-meta-item">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
													<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
												</svg>
												<?php echo esc_html( $location ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</a>
							<?php if ( ! empty( $share_links ) ) : ?>
								<div class="wpfa-past-event-card-share" aria-label="<?php echo esc_attr__( 'Share this past event', 'wpfaevent' ); ?>">
									<span class="wpfa-past-event-card-share-label"><?php esc_html_e( 'Share', 'wpfaevent' ); ?></span>
									<div class="wpfa-past-event-card-share-links">
										<?php foreach ( $share_links as $share_link ) : ?>
											<a
												class="wpfa-past-event-card-share-link"
												href="<?php echo esc_url( $share_link['url'] ); ?>"
												target="_blank"
												rel="noopener noreferrer"
											>
												<?php echo esc_html( $share_link['label'] ); ?>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
				</div>

				<?php wp_reset_postdata(); ?>

				<?php
				// Pagination.
				$total = max( 1, (int) ceil( $query->found_posts / $wpfa_past_events_per_page ) );
				if ( function_exists( 'wpfa_render_pagination' ) ) {
					wpfa_render_pagination(
						$total,
						$wpfa_past_events_paged,
						__( 'Past events pagination', 'wpfaevent' )
					);
				} elseif ( $total > 1 ) {
					// Manual pagination similar to page-speakers.php.
					echo '<nav class="wpfa-pagination" aria-label="' . esc_attr__( 'Past events pagination', 'wpfaevent' ) . '">';
					for ( $page_num = 1; $page_num <= $total; $page_num++ ) {
						$pagination_url = get_pagenum_link( $page_num );
						$is_current     = ( $page_num === $wpfa_past_events_paged );
						$page_classes   = trim( 'wpfa-page' . ( $is_current ? ' is-current' : '' ) );
						printf(
							'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
							esc_attr( $page_classes ),
							esc_url( $pagination_url ),
							$is_current ? ' aria-current="page"' : '',
							esc_html( (string) $page_num )
						);
					}
					echo '</nav>';
				}
				?>

			<?php else : ?>
				<div class="wpfa-past-events-empty">
					<h3><?php esc_html_e( 'No past events found', 'wpfaevent' ); ?></h3>
					<p><?php esc_html_e( 'Check back later or view our upcoming events.', 'wpfaevent' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	<?php if ( $wpfaevent_is_embed ) : ?>
	</section>
	<?php else : ?>
	</main>
	<?php endif; ?>

<?php if ( ! $wpfaevent_is_embed ) : ?>
	<footer class="wpfa-footer">
		<div class="container">
			<!-- Footer copyright notice -->
			<small>
				<?php
				echo esc_html(
					apply_filters(
						'wpfa_footer_text',
						sprintf(
							/* translators: %s: Current year */
							__( '© FOSSASIA %s • Open Source Community Events', 'wpfaevent' ),
							date_i18n( 'Y' )
						)
					)
				);
				?>
			</small>
		</div>
	</footer>
</div><!-- #page -->

	<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>
