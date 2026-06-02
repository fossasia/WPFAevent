<?php
/**
 * Single Event Template.
 *
 * Displays imported Eventyay event data, event-specific speakers, and schedule.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_id = get_queried_object_id();

if ( ! $event_id || 'wpfa_event' !== get_post_type( $event_id ) ) {
	return;
}

$read_dashboard_json = static function ( $filename, $fallback ) {
	$upload_dir = wp_upload_dir();

	if ( ! empty( $upload_dir['error'] ) ) {
		return $fallback;
	}

	$path = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data/' . sanitize_file_name( $filename );

	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return $fallback;
	}

	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading plugin-managed JSON from uploads.
	if ( false === $contents || '' === trim( $contents ) ) {
		return $fallback;
	}

	$decoded = json_decode( $contents, true );

	return ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : $fallback;
};

$normalize_post_id_list = static function ( $post_ids ) {
	if ( ! is_array( $post_ids ) ) {
		return array();
	}

	$post_ids = array_map( 'absint', $post_ids );
	$post_ids = array_filter( $post_ids );

	return array_values( array_unique( $post_ids ) );
};

$get_linked_speaker_ids = static function ( $current_event_id ) use ( $normalize_post_id_list ) {
	$current_event_id = absint( $current_event_id );
	$speaker_ids      = $normalize_post_id_list( get_post_meta( $current_event_id, 'wpfa_event_speakers', true ) );

	$reverse_speaker_ids = get_posts(
		array(
			'post_type'      => 'wpfa_speaker',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored as post meta.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => 'i:' . $current_event_id . ';',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => '"' . $current_event_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => (string) $current_event_id,
					'compare' => '=',
				),
			),
		)
	);

	return $normalize_post_id_list( array_merge( $speaker_ids, $reverse_speaker_ids ) );
};

$format_event_date = static function ( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $date;
};

$site_settings      = $read_dashboard_json( 'site-settings-' . absint( $event_id ) . '.json', array() );
$dashboard_speakers = $read_dashboard_json( 'speakers-' . absint( $event_id ) . '.json', array() );
$schedule_table     = $read_dashboard_json( 'schedule-' . absint( $event_id ) . '.json', array() );
$section_visibility = isset( $site_settings['section_visibility'] ) && is_array( $site_settings['section_visibility'] ) ? $site_settings['section_visibility'] : array();

$event_title   = get_the_title( $event_id );
$start_date    = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
$end_date      = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
$location      = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
$event_url     = get_post_meta( $event_id, 'wpfa_event_url', true );
$event_url     = $event_url ? esc_url_raw( $event_url ) : '';
$about_content = isset( $site_settings['about_section_content'] ) ? trim( (string) $site_settings['about_section_content'] ) : '';
$post_content  = trim( (string) get_post_field( 'post_content', $event_id ) );
$event_lead    = trim( (string) get_post_meta( $event_id, '_event_lead_text', true ) );
$speaker_ids   = $get_linked_speaker_ids( $event_id );
$event_slug    = get_post_field( 'post_name', $event_id );
$speakers_url  = add_query_arg( 'event', $event_slug, home_url( '/speakers/' ) );
$register_text = ! empty( $site_settings['reg_button_text'] ) ? sanitize_text_field( $site_settings['reg_button_text'] ) : __( 'Get Tickets', 'wpfaevent' );
$register_url  = ! empty( $site_settings['reg_button_link'] ) ? esc_url_raw( $site_settings['reg_button_link'] ) : $event_url;
$show_about    = ! array_key_exists( 'about', $section_visibility ) || ! empty( $section_visibility['about'] );
$show_speakers = ! array_key_exists( 'speakers', $section_visibility ) || ! empty( $section_visibility['speakers'] );
$show_schedule = ! array_key_exists( 'schedule', $section_visibility ) || ! empty( $section_visibility['schedule'] );
$schedule_rows = isset( $schedule_table['data'] ) && is_array( $schedule_table['data'] ) ? $schedule_table['data'] : array();
$schedule_head = ! empty( $schedule_rows[0] ) && is_array( $schedule_rows[0] ) ? $schedule_rows[0] : array();
$schedule_body = ! empty( $schedule_head ) ? array_slice( $schedule_rows, 1 ) : $schedule_rows;
$speaker_count = count( $speaker_ids );

if ( '' === $about_content ) {
	$about_content = '' !== $post_content ? $post_content : $event_lead;
}

$date_label = $format_event_date( $start_date );
if ( $end_date && $end_date !== $start_date ) {
	$date_label .= $date_label ? ' - ' . $format_event_date( $end_date ) : $format_event_date( $end_date );
}

$schedule_items = array();
foreach ( $schedule_body as $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}

	$schedule_items[] = array(
		'date'     => isset( $row[0] ) ? sanitize_text_field( $row[0] ) : '',
		'time'     => isset( $row[1] ) ? sanitize_text_field( $row[1] ) : '',
		'title'    => isset( $row[2] ) ? sanitize_text_field( $row[2] ) : '',
		'speakers' => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
		'track'    => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
		'room'     => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
	);
}

$first_schedule = ! empty( $schedule_items[0] ) ? $schedule_items[0] : array();

$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => true,
	'show_register_button' => ! empty( $register_url ),
	'back_button_text'     => __( 'All Events', 'wpfaevent' ),
	'register_button_url'  => $register_url,
	'register_button_text' => $register_text,
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template' ); ?>>
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

	<main class="wpfa-event-detail" itemscope itemtype="https://schema.org/Event">
		<section class="wpfa-event-hero">
			<div class="container wpfa-event-hero-inner">
				<div class="wpfa-event-hero-copy">
					<p class="wpfa-event-kicker"><?php esc_html_e( 'Eventyay Event', 'wpfaevent' ); ?></p>
					<h1 itemprop="name"><?php echo esc_html( $event_title ); ?></h1>
					<div class="wpfa-event-meta-list">
						<?php if ( $date_label ) : ?>
							<span itemprop="startDate" content="<?php echo esc_attr( $start_date ); ?>"><?php echo esc_html( $date_label ); ?></span>
						<?php endif; ?>
						<?php if ( $location ) : ?>
							<span itemprop="location"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $schedule_items ) ) : ?>
							<span>
								<?php
								printf(
									/* translators: %d: number of schedule sessions. */
									esc_html( _n( '%d session', '%d sessions', count( $schedule_items ), 'wpfaevent' ) ),
									absint( count( $schedule_items ) )
								);
								?>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( '' !== trim( $about_content ) ) : ?>
						<div class="wpfa-event-hero-text">
							<?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $about_content ), 34 ) ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<aside class="wpfa-event-ticket-panel" aria-label="<?php esc_attr_e( 'Event details', 'wpfaevent' ); ?>">
					<div class="wpfa-event-ticket-head">
						<p><?php esc_html_e( 'Registration', 'wpfaevent' ); ?></p>
						<strong><?php esc_html_e( 'Open', 'wpfaevent' ); ?></strong>
					</div>
					<?php if ( $register_url ) : ?>
						<a class="wpfa-event-register" href="<?php echo esc_url( $register_url ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $register_text ); ?>
						</a>
					<?php endif; ?>
					<dl class="wpfa-event-facts">
						<?php if ( $date_label ) : ?>
							<div>
								<dt><?php esc_html_e( 'When', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $date_label ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $location ) : ?>
							<div>
								<dt><?php esc_html_e( 'Where', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $location ); ?></dd>
							</div>
						<?php endif; ?>
						<div>
							<dt><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></dt>
							<dd><?php echo esc_html( number_format_i18n( $speaker_count ) ); ?></dd>
						</div>
						<?php if ( ! empty( $first_schedule['time'] ) ) : ?>
							<div>
								<dt><?php esc_html_e( 'Starts', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $first_schedule['time'] ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</aside>
			</div>
		</section>

		<nav class="wpfa-event-section-nav" aria-label="<?php esc_attr_e( 'Event sections', 'wpfaevent' ); ?>">
			<div class="container">
				<a href="#wpfa-event-about-title"><?php esc_html_e( 'Overview', 'wpfaevent' ); ?></a>
				<a href="#wpfa-event-speakers-title"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></a>
				<a href="#wpfa-event-schedule-title"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></a>
			</div>
		</nav>

		<?php if ( $show_about && '' !== trim( $about_content ) ) : ?>
			<section class="wpfa-event-section wpfa-event-about" aria-labelledby="wpfa-event-about-title">
				<div class="container">
					<div class="wpfa-event-section-layout">
						<header class="wpfa-event-section-label">
							<p><?php esc_html_e( 'Overview', 'wpfaevent' ); ?></p>
							<h2 id="wpfa-event-about-title"><?php esc_html_e( 'About this event', 'wpfaevent' ); ?></h2>
						</header>
						<div class="wpfa-event-rich-text" itemprop="description">
							<?php echo wp_kses_post( wpautop( $about_content ) ); ?>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_speakers ) : ?>
			<section class="wpfa-event-section wpfa-event-speakers" aria-labelledby="wpfa-event-speakers-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-speakers-title"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'People linked to this event only.', 'wpfaevent' ); ?></p>
						</div>
						<a href="<?php echo esc_url( $speakers_url ); ?>"><?php esc_html_e( 'Open Event Speaker List', 'wpfaevent' ); ?></a>
					</div>

					<?php if ( ! empty( $speaker_ids ) ) : ?>
						<div class="wpfa-speakers-grid">
							<?php
							$wpfa_hide_speaker_card_admin_actions = true;
							foreach ( $speaker_ids as $sid ) :
								if ( 'wpfa_speaker' !== get_post_type( $sid ) || 'publish' !== get_post_status( $sid ) ) {
									continue;
								}

								include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php';
							endforeach;
							unset( $wpfa_hide_speaker_card_admin_actions );
							?>
						</div>
					<?php elseif ( ! empty( $dashboard_speakers ) ) : ?>
						<div class="wpfa-speakers-grid">
							<?php foreach ( $dashboard_speakers as $speaker ) : ?>
								<?php
								if ( ! is_array( $speaker ) || empty( $speaker['name'] ) ) {
									continue;
								}

								$speaker_social = isset( $speaker['social'] ) && is_array( $speaker['social'] ) ? $speaker['social'] : array();
								$session        = ! empty( $speaker['sessions'][0] ) && is_array( $speaker['sessions'][0] ) ? $speaker['sessions'][0] : array();
								?>
									<article class="wpfa-speaker-card visible">
										<div class="wpfa-speaker-photo">
											<?php if ( ! empty( $speaker['image'] ) ) : ?>
												<?php /* translators: %s: Speaker name. */ ?>
												<img src="<?php echo esc_url( $speaker['image'] ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Photo of %s', 'wpfaevent' ), $speaker['name'] ) ); ?>" loading="lazy">
										<?php else : ?>
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" class="wpfa-placeholder-svg" role="img" aria-label="<?php esc_attr_e( 'Speaker photo placeholder', 'wpfaevent' ); ?>">
												<rect width="100%" height="100%" fill="#eee" />
												<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#999">Speaker</text>
											</svg>
										<?php endif; ?>
									</div>
									<div class="wpfa-speaker-meta">
										<?php if ( ! empty( $speaker['category'] ) ) : ?>
											<p class="pill"><?php echo esc_html( $speaker['category'] ); ?></p>
										<?php endif; ?>
										<h3 class="wpfa-speaker-name"><?php echo esc_html( $speaker['name'] ); ?></h3>
										<?php if ( ! empty( $speaker['position'] ) || ! empty( $speaker['organization'] ) ) : ?>
											<p class="wpfa-speaker-role">
												<?php echo esc_html( trim( ( $speaker['position'] ?? '' ) . ( ! empty( $speaker['position'] ) && ! empty( $speaker['organization'] ) ? ' | ' : '' ) . ( $speaker['organization'] ?? '' ) ) ); ?>
											</p>
										<?php endif; ?>
									</div>
									<div class="wpfa-speaker-expand">
										<?php if ( ! empty( $speaker['bio'] ) ) : ?>
											<div class="wpfa-speaker-bio"><?php echo wp_kses_post( wpautop( $speaker['bio'] ) ); ?></div>
										<?php endif; ?>
										<?php if ( ! empty( $session['title'] ) ) : ?>
											<div class="wpfa-speaker-session">
												<h4><?php esc_html_e( 'Session Details', 'wpfaevent' ); ?></h4>
												<p><strong><?php echo esc_html( $session['title'] ); ?></strong></p>
											</div>
										<?php endif; ?>
										<?php if ( ! empty( $speaker_social ) ) : ?>
											<div class="wpfa-speaker-social">
												<?php foreach ( $speaker_social as $social_label => $social_url ) : ?>
													<?php if ( $social_url ) : ?>
														<a href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpfa-social-link">
															<?php echo esc_html( ucfirst( $social_label ) ); ?>
														</a>
													<?php endif; ?>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="wpfa-empty-state"><?php esc_html_e( 'No speakers have been imported for this event yet.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_schedule ) : ?>
			<section class="wpfa-event-section wpfa-event-schedule" aria-labelledby="wpfa-event-schedule-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-schedule-title"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Times and rooms imported from Eventyay.', 'wpfaevent' ); ?></p>
						</div>
					</div>
					<?php if ( ! empty( $schedule_items ) ) : ?>
						<div class="wpfa-event-timeline">
							<?php foreach ( $schedule_items as $item ) : ?>
								<article class="wpfa-event-session">
									<div class="wpfa-event-session-time">
										<?php if ( ! empty( $item['date'] ) ) : ?>
											<span><?php echo esc_html( $item['date'] ); ?></span>
										<?php endif; ?>
										<strong><?php echo esc_html( $item['time'] ); ?></strong>
									</div>
									<div class="wpfa-event-session-body">
										<h3><?php echo esc_html( $item['title'] ); ?></h3>
										<div class="wpfa-event-session-meta">
											<?php if ( ! empty( $item['speakers'] ) ) : ?>
												<span><?php echo esc_html( $item['speakers'] ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $item['room'] ) ) : ?>
												<span><?php echo esc_html( $item['room'] ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $item['track'] ) ) : ?>
												<span><?php echo esc_html( $item['track'] ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="wpfa-empty-state"><?php esc_html_e( 'No schedule has been imported for this event yet.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	</main>

	<footer class="wpfa-footer">
		<div class="container">
			<small>
				<?php
				printf(
					/* translators: %s: Current year. */
					esc_html__( 'FOSSASIA %s - Open Source Community Events', 'wpfaevent' ),
					esc_html( date_i18n( 'Y' ) )
				);
				?>
			</small>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
