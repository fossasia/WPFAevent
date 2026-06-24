<?php
/**
 * Template Name: WPFA - Schedule
 *
 * Displays a schedule of FOSSASIA events grouped by date.
 * Events are ordered chronologically by start date, with
 * each date forming a section header.
 *
 * Each event displays:
 * - Event title (linked to permalink)
 * - Start time in the selected timezone
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

$schedule_data = Wpfaevent_Schedule_Controller::get_schedule_view_data();

$wpfaevent_is_embed             = isset( $schedule_data['wpfaevent_is_embed'] ) ? (bool) $schedule_data['wpfaevent_is_embed'] : false;
$events_per_page                = isset( $schedule_data['events_per_page'] ) ? (int) $schedule_data['events_per_page'] : 20;
$current_page                   = isset( $schedule_data['current_page'] ) ? (int) $schedule_data['current_page'] : 1;
$schedule_page_url              = isset( $schedule_data['schedule_page_url'] ) ? (string) $schedule_data['schedule_page_url'] : '';
$current_language               = isset( $schedule_data['current_language'] ) ? (string) $schedule_data['current_language'] : '';
$current_event_filter           = isset( $schedule_data['current_event_filter'] ) ? (string) $schedule_data['current_event_filter'] : '';
$current_view                   = isset( $schedule_data['current_view'] ) ? (string) $schedule_data['current_view'] : 'list';
$selected_event_id              = isset( $schedule_data['selected_event_id'] ) ? (int) $schedule_data['selected_event_id'] : 0;
$selected_event_slug            = isset( $schedule_data['selected_event_slug'] ) ? (string) $schedule_data['selected_event_slug'] : '';
$selected_event_title           = isset( $schedule_data['selected_event_title'] ) ? (string) $schedule_data['selected_event_title'] : '';
$selected_schedule_timezone_str = isset( $schedule_data['selected_schedule_timezone_str'] ) ? (string) $schedule_data['selected_schedule_timezone_str'] : '';
$selected_schedule_timezone     = isset( $schedule_data['selected_schedule_timezone'] ) ? $schedule_data['selected_schedule_timezone'] : null;
$schedule_timezone_options      = isset( $schedule_data['schedule_timezone_options'] ) && is_array( $schedule_data['schedule_timezone_options'] ) ? $schedule_data['schedule_timezone_options'] : array();
$event_ids                      = isset( $schedule_data['event_ids'] ) && is_array( $schedule_data['event_ids'] ) ? $schedule_data['event_ids'] : array();
$schedule_events                = isset( $schedule_data['schedule_events'] ) && is_array( $schedule_data['schedule_events'] ) ? $schedule_data['schedule_events'] : array();
$languages                      = isset( $schedule_data['languages'] ) && is_array( $schedule_data['languages'] ) ? $schedule_data['languages'] : array();
$filtered_event_ids             = isset( $schedule_data['filtered_event_ids'] ) && is_array( $schedule_data['filtered_event_ids'] ) ? $schedule_data['filtered_event_ids'] : array();
$is_event_schedule              = isset( $schedule_data['is_event_schedule'] ) ? (bool) $schedule_data['is_event_schedule'] : false;
$event_session_schedule         = isset( $schedule_data['event_session_schedule'] ) && is_array( $schedule_data['event_session_schedule'] ) ? $schedule_data['event_session_schedule'] : array();
$total_events                   = isset( $schedule_data['total_events'] ) ? (int) $schedule_data['total_events'] : 0;
$groups                         = isset( $schedule_data['groups'] ) && is_array( $schedule_data['groups'] ) ? $schedule_data['groups'] : array();
$event_style_attr               = isset( $schedule_data['event_style_attr'] ) ? (string) $schedule_data['event_style_attr'] : '';
$header_vars                    = isset( $schedule_data['header_vars'] ) && is_array( $schedule_data['header_vars'] ) ? $schedule_data['header_vars'] : array();
$filter_form_classes            = isset( $schedule_data['filter_form_classes'] ) ? (string) $schedule_data['filter_form_classes'] : '';
$site_timezone_string           = isset( $schedule_data['site_timezone_string'] ) ? (string) $schedule_data['site_timezone_string'] : '';
$primary_timezone_string        = isset( $schedule_data['primary_timezone_string'] ) ? (string) $schedule_data['primary_timezone_string'] : '';

$build_schedule_view_url = static function ( $view ) use ( $schedule_page_url, $selected_event_slug, $current_language, $selected_schedule_timezone_str, $primary_timezone_string ) {
	return Wpfaevent_Schedule_Controller::build_view_url( $view, $schedule_page_url, $selected_event_slug, $current_language, $selected_schedule_timezone_str, $primary_timezone_string );
};

$format_timezone_label = static function ( $timezone_string ) use ( $primary_timezone_string ) {
	return class_exists( 'Wpfaevent_Schedule_Helper' )
		? Wpfaevent_Schedule_Helper::format_timezone_label( $timezone_string, $primary_timezone_string )
		: str_replace( '_', ' ', $timezone_string );
};
?>
<?php if ( ! $wpfaevent_is_embed ) : ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-schedule-template' ); ?><?php echo $event_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
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
	<section class="wpfa-schedule">
<?php else : ?>
	<main class="wpfa-schedule">
<?php endif; ?>
		<div class="container">
			<div class="wpfa-schedule-head">
				<div>
					<?php if ( $is_event_schedule && $selected_event_title ) : ?>
						<h1><?php echo esc_html( $selected_event_title ); ?></h1>
						<p><?php esc_html_e( 'Event schedule grouped by session date.', 'wpfaevent' ); ?></p>
					<?php else : ?>
						<h1><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></h1>
						<p><?php esc_html_e( 'Upcoming events grouped by start date.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $event_ids ) ) : ?>
					<div class="wpfa-schedule-controls">
						<nav class="wpfa-schedule-view-switch" aria-label="<?php esc_attr_e( 'Schedule view', 'wpfaevent' ); ?>">
							<a
								class="<?php echo esc_attr( 'list' === $current_view ? 'is-active' : '' ); ?>"
								href="<?php echo esc_url( $build_schedule_view_url( 'list' ) ); ?>"
								<?php if ( 'list' === $current_view ) : ?>
									aria-current="page"
								<?php endif; ?>
							>
								<?php esc_html_e( 'List', 'wpfaevent' ); ?>
							</a>
							<a
								class="<?php echo esc_attr( 'calendar' === $current_view ? 'is-active' : '' ); ?>"
								href="<?php echo esc_url( $build_schedule_view_url( 'calendar' ) ); ?>"
								<?php if ( 'calendar' === $current_view ) : ?>
									aria-current="page"
								<?php endif; ?>
							>
								<?php esc_html_e( 'Calendar', 'wpfaevent' ); ?>
							</a>
						</nav>
						<form class="<?php echo esc_attr( $filter_form_classes ); ?>" action="<?php echo esc_url( $schedule_page_url ); ?>" method="get">
							<?php if ( $selected_event_slug ) : ?>
								<input type="hidden" name="event" value="<?php echo esc_attr( $selected_event_slug ); ?>">
							<?php endif; ?>
							<?php if ( 'calendar' === $current_view ) : ?>
								<input type="hidden" name="view" value="calendar">
							<?php endif; ?>
							<?php if ( ! empty( $languages ) ) : ?>
								<label for="wpfa-schedule-language">
									<span><?php esc_html_e( 'Language', 'wpfaevent' ); ?></span>
									<select id="wpfa-schedule-language" name="language">
										<option value=""><?php esc_html_e( 'All languages', 'wpfaevent' ); ?></option>
										<?php foreach ( $languages as $language_key => $language_label ) : ?>
											<option value="<?php echo esc_attr( $language_key ); ?>" <?php selected( $current_language, $language_key ); ?>>
												<?php echo esc_html( $language_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</label>
							<?php endif; ?>
							<label for="wpfa-schedule-timezone">
								<span><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></span>
								<select id="wpfa-schedule-timezone" class="wpfa-event-timezone-select" name="schedule_tz">
									<?php foreach ( $schedule_timezone_options as $timezone_option ) : ?>
										<option value="<?php echo esc_attr( $timezone_option ); ?>" <?php selected( $selected_schedule_timezone_str, $timezone_option ); ?>>
											<?php echo esc_html( $format_timezone_label( $timezone_option ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<button type="submit"><?php esc_html_e( 'Apply', 'wpfaevent' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $is_event_schedule ) : ?>
				<?php if ( ! empty( $event_session_schedule['groups'] ) ) : ?>
					<?php if ( 'calendar' === $current_view ) : ?>
						<div class="wpfa-schedule-calendar" role="list">
							<?php foreach ( $event_session_schedule['groups'] as $day_label => $day_sessions ) : ?>
								<section class="wpfa-schedule-calendar-day" role="listitem" aria-labelledby="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-calendar-heading">
									<header class="wpfa-schedule-calendar-day-head">
										<h2 id="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-calendar-heading"><?php echo esc_html( $day_label ); ?></h2>
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
												<h3><?php echo esc_html( $item['title'] ); ?></h3>
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
							<?php foreach ( $event_session_schedule['groups'] as $day_label => $day_sessions ) : ?>
								<section class="wpfa-schedule-day" aria-labelledby="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-heading">
									<header class="wpfa-schedule-day-head">
										<div>
											<h2 id="<?php echo esc_attr( sanitize_title( $day_label ) ); ?>-heading"><?php echo esc_html( $day_label ); ?></h2>
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
													<h3 class="wpfa-schedule-session-title"><?php echo esc_html( $item['title'] ); ?></h3>

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
				<?php else : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'No schedule has been imported for this event yet.', 'wpfaevent' ); ?></p>
				<?php endif; ?>
			<?php elseif ( $groups ) : ?>
				<?php if ( 'calendar' === $current_view ) : ?>
					<div class="wpfa-schedule-calendar" role="list">
						<?php foreach ( $groups as $group ) : ?>
							<section class="wpfa-schedule-calendar-day" role="listitem">
								<header class="wpfa-schedule-calendar-day-head">
									<h2><?php echo esc_html( $group['date'] ); ?></h2>
									<span>
										<?php
										printf(
											/* translators: %d: number of events on this day. */
											esc_html( _n( '%d event', '%d events', count( $group['events'] ), 'wpfaevent' ) ),
											absint( count( $group['events'] ) )
										);
										?>
									</span>
								</header>
								<div class="wpfa-schedule-calendar-slots">
									<?php foreach ( $group['events'] as $schedule_event ) : ?>
										<article class="wpfa-schedule-calendar-slot">
											<?php if ( ! empty( $schedule_event['time_label'] ) ) : ?>
												<time><?php echo esc_html( $schedule_event['time_label'] ); ?></time>
											<?php endif; ?>
											<h3>
												<a href="<?php echo esc_url( $schedule_event['permalink'] ); ?>">
													<?php echo esc_html( $schedule_event['title'] ); ?>
												</a>
											</h3>
											<?php if ( ! empty( $schedule_event['location'] ) || ! empty( $schedule_event['language_label'] ) ) : ?>
												<ul class="wpfa-schedule-calendar-meta">
													<?php if ( ! empty( $schedule_event['location'] ) ) : ?>
														<li><?php echo esc_html( $schedule_event['location'] ); ?></li>
													<?php endif; ?>
													<?php if ( ! empty( $schedule_event['language_label'] ) ) : ?>
														<li><?php echo esc_html( $schedule_event['language_label'] ); ?></li>
													<?php endif; ?>
												</ul>
											<?php endif; ?>
											<div class="wpfa-schedule-calendar-actions">
												<a href="<?php echo esc_url( $schedule_event['schedule_url'] ); ?>"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></a>
												<?php if ( ! empty( $schedule_event['calendar_url'] ) ) : ?>
													<?php
													$calendar_label = sprintf(
														/* translators: %s: event title. */
														__( 'Add %s to Google Calendar', 'wpfaevent' ),
														$schedule_event['title']
													);
													?>
													<a
														href="<?php echo esc_url( $schedule_event['calendar_url'] ); ?>"
														target="_blank"
														rel="noopener"
														aria-label="<?php echo esc_attr( $calendar_label ); ?>"
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
				<?php else : ?>
					<?php foreach ( $groups as $group ) : ?>
						<section class="wpfa-schedule-group">
							<h2 class="wpfa-schedule-date"><?php echo esc_html( $group['date'] ); ?></h2>
							<ul class="wpfa-schedule-items">
								<?php foreach ( $group['events'] as $schedule_event ) : ?>
									<li class="wpfa-schedule-item">
										<strong>
											<a href="<?php echo esc_url( $schedule_event['permalink'] ); ?>">
												<?php echo esc_html( $schedule_event['title'] ); ?>
											</a>
										</strong>
										<?php if ( ! empty( $schedule_event['time_label'] ) ) : ?>
											<span class="wpfa-schedule-time"><?php echo esc_html( $schedule_event['time_label'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $schedule_event['location'] ) ) : ?>
											<span class="wpfa-schedule-location"><?php echo esc_html( $schedule_event['location'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $schedule_event['language_label'] ) ) : ?>
											<span class="wpfa-schedule-language"><?php echo esc_html( $schedule_event['language_label'] ); ?></span>
										<?php endif; ?>
										<div class="wpfa-schedule-item-actions">
											<span class="wpfa-schedule-actions">
												<a href="<?php echo esc_url( $schedule_event['schedule_url'] ); ?>"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></a>
											</span>
											<?php if ( ! empty( $schedule_event['calendar_url'] ) ) : ?>
												<?php
												$calendar_label = sprintf(
													/* translators: %s: event title. */
													__( 'Add %s to Google Calendar', 'wpfaevent' ),
													$schedule_event['title']
												);
												?>
												<a
													class="wpfa-calendar-action"
													href="<?php echo esc_url( $schedule_event['calendar_url'] ); ?>"
													target="_blank"
													rel="noopener"
													aria-label="<?php echo esc_attr( $calendar_label ); ?>"
												>
													<?php esc_html_e( 'Add to calendar', 'wpfaevent' ); ?>
												</a>
											<?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php
				$total           = max( 1, (int) ceil( $total_events / $events_per_page ) );
				$pagination_args = array();
				if ( $selected_schedule_timezone_str && $selected_schedule_timezone_str !== $site_timezone_string ) {
					$pagination_args['schedule_tz'] = $selected_schedule_timezone_str;
				}
				if ( $current_language ) {
					$pagination_args['language'] = $current_language;
				}
				if ( 'calendar' === $current_view ) {
					$pagination_args['view'] = 'calendar';
				}
				wpfa_render_pagination( $total, $current_page, __( 'Schedule pagination', 'wpfaevent' ), $pagination_args );
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'No schedule entries yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>
<?php if ( $wpfaevent_is_embed ) : ?>
	</section>
<?php else : ?>
	</main>
</div>

	<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>
