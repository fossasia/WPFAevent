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

$event_data                               = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );
$event_style_attr                         = $event_data['event_style_attr'];
$header_vars                              = $event_data['header_vars'];
$event_title                              = $event_data['event_title'];
$date_label                               = $event_data['date_label'];
$event_start_content                      = $event_data['event_start_content'];
$event_end_content                        = $event_data['event_end_content'];
$event_time_label                         = $event_data['event_time_label'];
$event_timezone_label                     = $event_data['event_timezone_label'];
$location                                 = $event_data['location'];
$event_language_label                     = $event_data['event_language_label'];
$event_url                                = $event_data['event_url'];
$schedule_items                           = $event_data['schedule_items'];
$about_content                            = $event_data['about_content'];
$register_url                             = $event_data['register_url'];
$register_text                            = $event_data['register_text'];
$event_google_url                         = $event_data['event_google_url'];
$event_calendar_url                       = $event_data['event_calendar_url'];
$speaker_count                            = $event_data['speaker_count'];
$sponsor_count                            = $event_data['sponsor_count'];
$visible_exhibitors                       = $event_data['visible_exhibitors'];
$first_schedule                           = $event_data['first_schedule'];
$wpfa_event_nav_items                     = $event_data['wpfa_event_nav_items'];
$show_about                               = $event_data['show_about'];
$show_speakers                            = $event_data['show_speakers'];
$show_schedule                            = $event_data['show_schedule'];
$show_sponsors                            = $event_data['show_sponsors'];
$show_exhibitors                          = $event_data['show_exhibitors'];
$venue_information                        = $event_data['venue_information'];
$event_additional_url                     = $event_data['event_additional_url'];
$custom_tabs                              = $event_data['custom_tabs'];
$featured_speaker_ids                     = $event_data['featured_speaker_ids'];
$featured_speaker_count                   = $event_data['featured_speaker_count'];
$dashboard_featured_speakers              = $event_data['dashboard_featured_speakers'];
$dashboard_regular_speakers               = $event_data['dashboard_regular_speakers'];
$regular_speaker_overflow_count           = $event_data['regular_speaker_overflow_count'];
$dashboard_regular_speaker_overflow_count = $event_data['dashboard_regular_speaker_overflow_count'];
$speaker_placeholder_url                  = $event_data['speaker_placeholder_url'];
$speakers_url                             = $event_data['speakers_url'];
$selected_schedule_timezone_string        = $event_data['selected_schedule_timezone_string'];
$schedule_timezone_options                = $event_data['schedule_timezone_options'];
$format_timezone_label                    = $event_data['format_timezone_label'];
$build_event_schedule_view_url            = $event_data['build_event_schedule_view_url'];
$selected_schedule_timezone               = $event_data['selected_schedule_timezone'];
$main_regular_speaker_ids                 = $event_data['main_regular_speaker_ids'];
$main_dashboard_regular_speakers          = $event_data['main_dashboard_regular_speakers'];
$main_dashboard_speakers                  = $event_data['main_dashboard_speakers'];
$dashboard_speaker_overflow_count         = $event_data['dashboard_speaker_overflow_count'];
$dashboard_speakers                       = $event_data['dashboard_speakers'];
$schedule_preview_items                   = $event_data['schedule_preview_items'];
$schedule_preview_day_groups              = $event_data['schedule_preview_day_groups'];
$schedule_hidden_count                    = $event_data['schedule_hidden_count'];
$event_schedule_url                       = $event_data['event_schedule_url'];
$visible_sponsor_groups                   = $event_data['visible_sponsor_groups'];
$current_schedule_view                    = $event_data['current_schedule_view'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template' ); ?><?php echo $event_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
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
								<span itemprop="startDate" content="<?php echo esc_attr( $event_start_content ); ?>"><?php echo esc_html( $date_label ); ?></span>
								<?php if ( $event_end_content ) : ?>
									<meta itemprop="endDate" content="<?php echo esc_attr( $event_end_content ); ?>">
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( $event_time_label ) : ?>
								<span><?php echo esc_html( $event_time_label ); ?></span>
							<?php endif; ?>
						<?php if ( $location ) : ?>
								<span itemprop="location"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
						<?php if ( $event_language_label ) : ?>
							<span><?php echo esc_html( $event_language_label ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $event_url ) && ( empty( $register_url ) || $event_url !== $register_url ) ) : ?>
							<a class="btn btn-secondary" href="<?php echo esc_url( $event_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Event Website', 'wpfaevent' ); ?>
							</a>
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
					<?php if ( $event_google_url ) : ?>
						<a class="wpfa-event-calendar-link" href="<?php echo esc_url( $event_google_url ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Add to calendar', 'wpfaevent' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $event_calendar_url ) : ?>
						<a class="wpfa-event-calendar-download" href="<?php echo esc_url( $event_calendar_url ); ?>">
							<?php esc_html_e( 'Download .ics', 'wpfaevent' ); ?>
						</a>
					<?php endif; ?>
					<dl class="wpfa-event-facts">
							<?php if ( $date_label ) : ?>
								<div>
									<dt><?php esc_html_e( 'When', 'wpfaevent' ); ?></dt>
									<dd><?php echo esc_html( $date_label ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $event_time_label ) : ?>
								<div>
									<dt><?php esc_html_e( 'Time', 'wpfaevent' ); ?></dt>
									<dd><?php echo esc_html( $event_time_label ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $event_timezone_label ) : ?>
								<div>
									<dt><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></dt>
									<dd><?php echo esc_html( $event_timezone_label ); ?></dd>
								</div>
							<?php endif; ?>
							<?php if ( $location ) : ?>
								<div>
								<dt><?php esc_html_e( 'Where', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $location ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( $event_language_label ) : ?>
							<div>
								<dt><?php esc_html_e( 'Languages', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $event_language_label ); ?></dd>
							</div>
						<?php endif; ?>
						<div>
							<dt><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></dt>
							<dd><?php echo esc_html( number_format_i18n( $speaker_count ) ); ?></dd>
						</div>
						<?php if ( $sponsor_count ) : ?>
							<div>
								<dt><?php esc_html_e( 'Sponsors', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $sponsor_count ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $visible_exhibitors ) ) : ?>
							<div>
								<dt><?php esc_html_e( 'Exhibitors', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( count( $visible_exhibitors ) ) ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $first_schedule['time_label'] ) ) : ?>
							<div>
								<dt><?php esc_html_e( 'Starts', 'wpfaevent' ); ?></dt>
								<dd><?php echo esc_html( $first_schedule['time_label'] ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</aside>
			</div>
		</section>

		<?php if ( ! empty( $wpfa_event_nav_items ) ) : ?>
			<?php include WPFAEVENT_PATH . 'public/partials/event-section-nav.php'; ?>
		<?php endif; ?>

		<?php if ( $show_about && '' !== trim( $about_content ) ) : ?>
			<section id="about" class="wpfa-event-section wpfa-event-about" aria-labelledby="wpfa-event-about-title">
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
			<section id="speakers" class="wpfa-event-section wpfa-event-speakers" aria-labelledby="wpfa-event-speakers-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-speakers-title"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'People linked to this event only.', 'wpfaevent' ); ?></p>
						</div>
						<a href="<?php echo esc_url( $speakers_url ); ?>"><?php esc_html_e( 'Open Event Speaker List', 'wpfaevent' ); ?></a>
					</div>

					<?php if ( ! empty( $speaker_ids ) ) : ?>
						<?php if ( $featured_speaker_count ) : ?>
							<div class="wpfa-event-featured-speakers">
								<h3><?php esc_html_e( 'Featured Speakers', 'wpfaevent' ); ?></h3>
								<div class="wpfa-speakers-grid wpfa-featured-speakers-grid">
									<?php
									$wpfa_hide_speaker_card_admin_actions = true;
									$wpfa_schedule_display_timezone       = $selected_schedule_timezone;
									$wpfa_featured_speaker_ids            = $featured_speaker_ids;
									foreach ( $featured_speaker_ids as $sid ) :
										if ( 'wpfa_speaker' !== get_post_type( $sid ) || 'publish' !== get_post_status( $sid ) ) {
											continue;
										}

										include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php';
									endforeach;
									unset( $wpfa_hide_speaker_card_admin_actions );
									unset( $wpfa_schedule_display_timezone );
									unset( $wpfa_featured_speaker_ids );
									?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $regular_speaker_ids ) ) : ?>
							<div class="wpfa-event-regular-speakers">
								<h3><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h3>
								<div class="wpfa-speakers-grid">
									<?php
									$wpfa_hide_speaker_card_admin_actions = true;
									$wpfa_schedule_display_timezone       = $selected_schedule_timezone;
									foreach ( $main_regular_speaker_ids as $sid ) :
										if ( 'wpfa_speaker' !== get_post_type( $sid ) || 'publish' !== get_post_status( $sid ) ) {
											continue;
										}

										include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php';
									endforeach;
									unset( $wpfa_hide_speaker_card_admin_actions );
									unset( $wpfa_schedule_display_timezone );
									?>
								</div>
								<?php if ( $regular_speaker_overflow_count ) : ?>
									<p class="wpfa-event-speaker-limit-note">
										<?php
										printf(
											/* translators: 1: shown speaker count, 2: total speaker count. */
											esc_html__( 'Showing the main %1$d of %2$d speakers. Open the full event speaker list to view everyone.', 'wpfaevent' ),
											absint( count( $main_regular_speaker_ids ) ),
											absint( count( $regular_speaker_ids ) )
										);
										?>
									</p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php elseif ( ! empty( $dashboard_speakers ) ) : ?>
						<?php if ( ! empty( $dashboard_featured_speakers ) ) : ?>
							<div class="wpfa-event-featured-speakers">
								<h3><?php esc_html_e( 'Featured Speakers', 'wpfaevent' ); ?></h3>
								<div class="wpfa-speakers-grid wpfa-featured-speakers-grid">
									<?php foreach ( $dashboard_featured_speakers as $speaker ) : ?>
										<?php
										$wpfa_dashboard_speaker_is_featured = true;
										include WPFAEVENT_PATH . 'public/partials/speakers/dashboard-speaker-card.php';
										unset( $wpfa_dashboard_speaker_is_featured );
										?>
									<?php endforeach; ?>
								</div>
							</div>

							<?php if ( ! empty( $dashboard_regular_speakers ) ) : ?>
								<div class="wpfa-event-regular-speakers">
									<h3><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h3>
									<div class="wpfa-speakers-grid">
										<?php foreach ( $main_dashboard_regular_speakers as $speaker ) : ?>
											<?php include WPFAEVENT_PATH . 'public/partials/speakers/dashboard-speaker-card.php'; ?>
										<?php endforeach; ?>
									</div>
									<?php if ( $dashboard_regular_speaker_overflow_count ) : ?>
										<p class="wpfa-event-speaker-limit-note">
											<?php
											printf(
												/* translators: 1: shown speaker count, 2: total speaker count. */
												esc_html__( 'Showing the main %1$d of %2$d speakers. Open the full event speaker list to view everyone.', 'wpfaevent' ),
												absint( count( $main_dashboard_regular_speakers ) ),
												absint( count( $dashboard_regular_speakers ) )
											);
											?>
										</p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<div class="wpfa-speakers-grid">
								<?php foreach ( $main_dashboard_speakers as $speaker ) : ?>
									<?php include WPFAEVENT_PATH . 'public/partials/speakers/dashboard-speaker-card.php'; ?>
								<?php endforeach; ?>
							</div>
							<?php if ( $dashboard_speaker_overflow_count ) : ?>
								<p class="wpfa-event-speaker-limit-note">
									<?php
									printf(
										/* translators: 1: shown speaker count, 2: total speaker count. */
										esc_html__( 'Showing the main %1$d of %2$d speakers. Open the full event speaker list to view everyone.', 'wpfaevent' ),
										absint( count( $main_dashboard_speakers ) ),
										absint( count( $dashboard_speakers ) )
									);
									?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					<?php else : ?>
						<p class="wpfa-empty-state"><?php esc_html_e( 'No speakers have been imported for this event yet.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_schedule ) : ?>
			<section id="schedule-overview" class="wpfa-event-section wpfa-event-schedule" aria-labelledby="wpfa-event-schedule-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-schedule-title"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Times and rooms imported from Eventyay.', 'wpfaevent' ); ?></p>
						</div>
						<?php if ( ! empty( $schedule_items ) ) : ?>
							<div class="wpfa-event-section-actions">
								<nav class="wpfa-schedule-view-switch" aria-label="<?php esc_attr_e( 'Schedule view', 'wpfaevent' ); ?>">
									<a
										class="<?php echo esc_attr( 'list' === $current_schedule_view ? 'is-active' : '' ); ?>"
										href="<?php echo esc_url( $build_event_schedule_view_url( 'list' ) ); ?>"
										<?php if ( 'list' === $current_schedule_view ) : ?>
											aria-current="page"
										<?php endif; ?>
									>
										<?php esc_html_e( 'List', 'wpfaevent' ); ?>
									</a>
									<a
										class="<?php echo esc_attr( 'calendar' === $current_schedule_view ? 'is-active' : '' ); ?>"
										href="<?php echo esc_url( $build_event_schedule_view_url( 'calendar' ) ); ?>"
										<?php if ( 'calendar' === $current_schedule_view ) : ?>
											aria-current="page"
										<?php endif; ?>
									>
										<?php esc_html_e( 'Calendar', 'wpfaevent' ); ?>
									</a>
								</nav>
								<a href="<?php echo esc_url( $event_schedule_url ); ?>"><?php esc_html_e( 'Full Schedule', 'wpfaevent' ); ?></a>
								<form class="wpfa-event-timezone-form" action="<?php echo esc_url( get_permalink( $event_id ) . '#wpfa-event-schedule-title' ); ?>" method="get">
									<?php if ( 'calendar' === $current_schedule_view ) : ?>
										<input type="hidden" name="schedule_view" value="calendar">
									<?php endif; ?>
									<label for="wpfa-event-schedule-timezone">
										<span><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></span>
										<select id="wpfa-event-schedule-timezone" class="wpfa-event-timezone-select" name="schedule_tz">
											<?php foreach ( $schedule_timezone_options as $timezone_option ) : ?>
												<option value="<?php echo esc_attr( $timezone_option ); ?>" <?php selected( $selected_schedule_timezone_string, $timezone_option ); ?>>
													<?php echo esc_html( $format_timezone_label( $timezone_option ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>
									<button type="submit"><?php esc_html_e( 'Convert', 'wpfaevent' ); ?></button>
								</form>
							</div>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $schedule_preview_items ) ) : ?>
						<?php if ( 'calendar' === $current_schedule_view ) : ?>
							<div class="wpfa-schedule-calendar" role="list">
								<?php foreach ( $schedule_preview_day_groups as $day_label => $day_sessions ) : ?>
									<section class="wpfa-schedule-calendar-day" role="listitem" aria-labelledby="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-preview-calendar-heading">
										<header class="wpfa-schedule-calendar-day-head">
											<h3 id="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-preview-calendar-heading"><?php echo esc_html( $day_label ); ?></h3>
											<span>
												<?php
												printf(
													/* translators: %d: number of sessions on this day. */
													esc_html( _n( '%d session', '%d sessions', count( $day_sessions ), 'wpfaevent' ) ),
													absint( count( $day_sessions ) )
												);
												?>
											</span>
										</header>
										<div class="wpfa-schedule-calendar-slots">
											<?php foreach ( $day_sessions as $item ) : ?>
												<article class="wpfa-schedule-calendar-slot">
													<?php if ( ! empty( $item['time_label'] ) ) : ?>
														<time datetime="<?php echo esc_attr( $item['start_datetime'] ); ?>"><?php echo esc_html( $item['time_label'] ); ?></time>
													<?php endif; ?>
													<h4><?php echo esc_html( $item['title'] ); ?></h4>
													<?php if ( ! empty( $item['speakers'] ) || ! empty( $item['room'] ) || ! empty( $item['track'] ) ) : ?>
														<ul class="wpfa-schedule-calendar-meta">
															<?php if ( ! empty( $item['speakers'] ) ) : ?>
																<li><?php echo esc_html( $item['speakers'] ); ?></li>
															<?php endif; ?>
															<?php if ( ! empty( $item['room'] ) ) : ?>
																<li><?php echo esc_html( $item['room'] ); ?></li>
															<?php endif; ?>
															<?php if ( ! empty( $item['track'] ) ) : ?>
																<li><?php echo esc_html( $item['track'] ); ?></li>
															<?php endif; ?>
														</ul>
													<?php endif; ?>
													<?php if ( ! empty( $item['calendar_url'] ) ) : ?>
														<?php
														$session_calendar_label = sprintf(
															/* translators: %s: session title. */
															__( 'Add %s to Google Calendar', 'wpfaevent' ),
															$item['title']
														);
														?>
														<a
															class="wpfa-schedule-session-calendar"
															href="<?php echo esc_url( $item['calendar_url'] ); ?>"
															target="_blank"
															rel="noopener"
															aria-label="<?php echo esc_attr( $session_calendar_label ); ?>"
														>
															<?php esc_html_e( 'Add to calendar', 'wpfaevent' ); ?>
														</a>
													<?php endif; ?>
												</article>
											<?php endforeach; ?>
										</div>
									</section>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="wpfa-schedule-program">
								<?php foreach ( $schedule_preview_day_groups as $day_label => $day_sessions ) : ?>
									<section class="wpfa-schedule-day" aria-labelledby="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-heading">
										<header class="wpfa-schedule-day-head">
											<div>
												<h3 id="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-heading"><?php echo esc_html( $day_label ); ?></h3>
												<p>
													<?php
													printf(
														/* translators: %d: number of sessions on this day. */
														esc_html( _n( '%d session', '%d sessions', count( $day_sessions ), 'wpfaevent' ) ),
														absint( count( $day_sessions ) )
													);
													?>
												</p>
											</div>
										</header>

										<div class="wpfa-schedule-day-sessions" role="list">
											<?php foreach ( $day_sessions as $item ) : ?>
												<article class="wpfa-schedule-session" role="listitem">
													<div class="wpfa-schedule-session-timecol" aria-label="<?php esc_attr_e( 'Session time', 'wpfaevent' ); ?>">
														<?php if ( ! empty( $item['time_start'] ) ) : ?>
															<time class="wpfa-schedule-session-start" datetime="<?php echo esc_attr( $item['start_datetime'] ); ?>">
																<?php echo esc_html( $item['time_start'] ); ?>
															</time>
														<?php endif; ?>
														<?php if ( ! empty( $item['time_end'] ) ) : ?>
															<span class="wpfa-schedule-session-end"><?php echo esc_html( $item['time_end'] ); ?></span>
														<?php endif; ?>
													</div>

													<div class="wpfa-schedule-session-main">
														<h4 class="wpfa-schedule-session-title"><?php echo esc_html( $item['title'] ); ?></h4>

														<?php if ( ! empty( $item['speakers'] ) || ! empty( $item['room'] ) || ! empty( $item['track'] ) ) : ?>
															<dl class="wpfa-schedule-session-details">
																<?php if ( ! empty( $item['speakers'] ) ) : ?>
																	<div class="wpfa-schedule-detail">
																		<dt><?php esc_html_e( 'Speaker', 'wpfaevent' ); ?></dt>
																		<dd><?php echo esc_html( $item['speakers'] ); ?></dd>
																	</div>
																<?php endif; ?>
																<?php if ( ! empty( $item['room'] ) ) : ?>
																	<div class="wpfa-schedule-detail">
																		<dt><?php esc_html_e( 'Room', 'wpfaevent' ); ?></dt>
																		<dd><?php echo esc_html( $item['room'] ); ?></dd>
																	</div>
																<?php endif; ?>
																<?php if ( ! empty( $item['track'] ) ) : ?>
																	<div class="wpfa-schedule-detail">
																		<dt><?php esc_html_e( 'Track', 'wpfaevent' ); ?></dt>
																		<dd><?php echo esc_html( $item['track'] ); ?></dd>
																	</div>
																<?php endif; ?>
															</dl>
														<?php endif; ?>

														<?php if ( ! empty( $item['calendar_url'] ) ) : ?>
															<?php
															$session_calendar_label = sprintf(
																/* translators: %s: session title. */
																__( 'Add %s to Google Calendar', 'wpfaevent' ),
																$item['title']
															);
															?>
															<a
																class="wpfa-schedule-session-calendar"
																href="<?php echo esc_url( $item['calendar_url'] ); ?>"
																target="_blank"
																rel="noopener"
																aria-label="<?php echo esc_attr( $session_calendar_label ); ?>"
															>
																<?php esc_html_e( 'Add to calendar', 'wpfaevent' ); ?>
															</a>
														<?php endif; ?>
													</div>
												</article>
											<?php endforeach; ?>
										</div>
									</section>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( $schedule_hidden_count ) : ?>
							<p class="wpfa-event-schedule-preview-note">
								<?php
								printf(
									/* translators: 1: shown session count, 2: total session count. */
									esc_html__( 'Showing the first %1$d of %2$d sessions. Open the full schedule to view every session.', 'wpfaevent' ),
									absint( count( $schedule_preview_items ) ),
									absint( count( $schedule_items ) )
								);
								?>
							</p>
						<?php endif; ?>
					<?php else : ?>
						<p class="wpfa-empty-state"><?php esc_html_e( 'No schedule has been imported for this event yet.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( '' !== trim( wp_strip_all_tags( $venue_information ) ) ) : ?>
			<section id="venue" class="wpfa-event-section wpfa-event-venue" aria-labelledby="wpfa-event-venue-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-venue-title"><?php esc_html_e( 'Additional information', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Nearby hotels, transportation, parking, directions, and venue notes.', 'wpfaevent' ); ?></p>
						</div>
						<a href="<?php echo esc_url( $event_additional_url ); ?>"><?php esc_html_e( 'View Additional Information', 'wpfaevent' ); ?></a>
					</div>
					<div class="wpfa-event-rich-text wpfa-event-additional-preview">
						<?php echo wp_kses_post( wpautop( $venue_information ) ); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php foreach ( $custom_tabs as $custom_tab ) : ?>
			<?php
			if ( empty( $custom_tab['slug'] ) || empty( $custom_tab['title'] ) || empty( $custom_tab['content'] ) ) {
				continue;
			}

			$custom_tab_title_id = 'wpfa-event-custom-tab-' . sanitize_html_class( $custom_tab['slug'] ) . '-title';
			?>
			<section id="custom-section-<?php echo esc_attr( $custom_tab['slug'] ); ?>" class="wpfa-event-section wpfa-event-custom-tab" aria-labelledby="<?php echo esc_attr( $custom_tab_title_id ); ?>">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="<?php echo esc_attr( $custom_tab_title_id ); ?>"><?php echo esc_html( $custom_tab['title'] ); ?></h2>
						</div>
					</div>
					<div class="wpfa-event-rich-text wpfa-event-custom-tab-content">
						<?php echo wp_kses_post( wpautop( $custom_tab['content'] ) ); ?>
					</div>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( $show_sponsors && ! empty( $visible_sponsor_groups ) ) : ?>
			<section id="sponsors" class="wpfa-event-section wpfa-event-sponsors" aria-labelledby="wpfa-event-sponsors-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-sponsors-title"><?php esc_html_e( 'Sponsors', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Organizations supporting this event.', 'wpfaevent' ); ?></p>
						</div>
					</div>

					<div class="wpfa-event-partner-groups">
						<?php foreach ( $visible_sponsor_groups as $sponsor_group ) : ?>
							<?php
							$group_name = ! empty( $sponsor_group['group_name'] ) ? sanitize_text_field( $sponsor_group['group_name'] ) : __( 'Sponsors', 'wpfaevent' );
							$logo_size  = ! empty( $sponsor_group['logo_size'] ) ? absint( $sponsor_group['logo_size'] ) : 160;
							?>
							<div class="wpfa-event-partner-group">
								<h3><?php echo esc_html( $group_name ); ?></h3>
								<div class="wpfa-event-partner-grid">
									<?php foreach ( $sponsor_group['sponsors'] as $sponsor ) : ?>
										<?php
										$sponsor_name        = ! empty( $sponsor['name'] ) ? sanitize_text_field( $sponsor['name'] ) : '';
										$sponsor_image       = ! empty( $sponsor['image'] ) ? esc_url_raw( $sponsor['image'] ) : '';
										$sponsor_description = ! empty( $sponsor['description'] ) ? wp_kses_post( $sponsor['description'] ) : '';
										$sponsor_detail_url  = ! empty( $sponsor['detail_url'] ) ? $sponsor['detail_url'] : '';
										?>
										<a class="wpfa-event-partner-card wpfa-event-partner-card-link" href="<?php echo esc_url( $sponsor_detail_url ? $sponsor_detail_url : '#' ); ?>">
											<?php if ( $sponsor_image ) : ?>
													<div class="wpfa-event-partner-logo" style="--partner-logo-size: <?php echo esc_attr( sprintf( '%d', absint( $logo_size ) ) ); ?>px;">
													<img src="<?php echo esc_url( $sponsor_image ); ?>" alt="<?php echo esc_attr( $sponsor_name ); ?>" loading="lazy">
												</div>
											<?php endif; ?>
											<div class="wpfa-event-partner-body">
												<?php if ( $sponsor_name ) : ?>
													<h4><?php echo esc_html( $sponsor_name ); ?></h4>
												<?php endif; ?>
												<?php if ( $sponsor_description ) : ?>
													<div class="wpfa-event-partner-description"><?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $sponsor_description ), 24, '…' ) ) ); ?></div>
												<?php endif; ?>
												<span class="wpfa-event-partner-view-details"><?php esc_html_e( 'View details', 'wpfaevent' ); ?></span>
											</div>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $show_exhibitors && ! empty( $visible_exhibitors ) ) : ?>
			<section id="exhibitors" class="wpfa-event-section wpfa-event-exhibitors" aria-labelledby="wpfa-event-exhibitors-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-exhibitors-title"><?php esc_html_e( 'Exhibitors', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Exhibitor booths and resources for this event.', 'wpfaevent' ); ?></p>
						</div>
					</div>

					<div class="wpfa-event-exhibitor-grid">
						<?php foreach ( $visible_exhibitors as $exhibitor ) : ?>
							<?php
							$exhibitor_name        = sanitize_text_field( $exhibitor['name'] );
							$exhibitor_logo        = ! empty( $exhibitor['logo'] ) ? esc_url_raw( $exhibitor['logo'] ) : '';
							$exhibitor_banner      = ! empty( $exhibitor['banner'] ) ? esc_url_raw( $exhibitor['banner'] ) : '';
							$exhibitor_initial     = $exhibitor_name ? strtoupper( substr( $exhibitor_name, 0, 1 ) ) : '';
							$exhibitor_card_class  = 'wpfa-event-exhibitor-card';
							$exhibitor_card_class .= $exhibitor_banner ? ' has-banner' : ' no-banner';
							$exhibitor_card_class .= $exhibitor_logo ? ' has-logo' : ' no-logo';
							$exhibitor_detail_url  = ! empty( $exhibitor['detail_url'] ) ? $exhibitor['detail_url'] : '';
							?>
							<a class="<?php echo esc_attr( $exhibitor_card_class ); ?> wpfa-event-exhibitor-card-link" href="<?php echo esc_url( $exhibitor_detail_url ? $exhibitor_detail_url : '#' ); ?>">
								<?php if ( $exhibitor_banner ) : ?>
									<img class="wpfa-event-exhibitor-banner" src="<?php echo esc_url( $exhibitor_banner ); ?>" alt="<?php echo esc_attr( $exhibitor_name ); ?>" loading="lazy">
								<?php endif; ?>
								<span class="wpfa-event-exhibitor-summary">
									<span class="wpfa-event-exhibitor-main">
										<?php if ( $exhibitor_logo ) : ?>
											<span class="wpfa-event-exhibitor-logo">
												<img src="<?php echo esc_url( $exhibitor_logo ); ?>" alt="<?php echo esc_attr( $exhibitor_name ); ?>" loading="lazy">
											</span>
										<?php else : ?>
											<span class="wpfa-event-exhibitor-placeholder" aria-hidden="true">
												<?php echo esc_html( $exhibitor_initial ); ?>
											</span>
										<?php endif; ?>
										<span class="wpfa-event-exhibitor-copy">
											<span class="wpfa-event-exhibitor-eyebrow"><?php esc_html_e( 'Exhibitor', 'wpfaevent' ); ?></span>
											<span class="wpfa-event-exhibitor-name"><?php echo esc_html( $exhibitor_name ); ?></span>
										</span>
									</span>
									<span class="wpfa-event-exhibitor-toggle">
										<span class="wpfa-event-exhibitor-toggle-closed"><?php esc_html_e( 'View details', 'wpfaevent' ); ?></span>
									</span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
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
