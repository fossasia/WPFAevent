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

$wpfaevent_is_embed = ! empty( $GLOBALS['wpfaevent_template_embed'] );
$events_per_page    = max( 1, (int) apply_filters( 'wpfa_schedule_events_per_page', 20 ) );
$current_page       = max( 1, (int) get_query_var( 'paged', 1 ) );
$schedule_page_url  = get_permalink();

if ( ! $schedule_page_url && class_exists( 'Wpfaevent_Schedule_Helper' ) ) {
	$schedule_page_url = Wpfaevent_Schedule_Helper::get_schedule_page_url();
}

$read_filter_value = static function ( $key, $type = 'text' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are read-only schedule filters.
	if ( ! isset( $_GET[ $key ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized by type below.
	$value = wp_unslash( $_GET[ $key ] );

	if ( is_array( $value ) ) {
		return '';
	}

	if ( 'slug' === $type ) {
		return sanitize_title( $value );
	}

	if ( 'int' === $type ) {
		return absint( $value );
	}

	return sanitize_text_field( $value );
};

$current_language     = $read_filter_value( 'language', 'slug' );
$current_event_filter = $read_filter_value( 'event' );
$current_view         = $read_filter_value( 'view', 'slug' );
$query_page           = $read_filter_value( 'paged', 'int' );

if ( $query_page ) {
	$current_page = max( 1, $query_page );
}

if ( ! in_array( $current_view, array( 'list', 'calendar' ), true ) ) {
	$current_view = 'list';
}

$resolve_event_filter = static function ( $event_filter ) {
	$event_filter = trim( (string) $event_filter );

	if ( '' === $event_filter ) {
		return 0;
	}

	if ( is_numeric( $event_filter ) ) {
		$event_id = absint( $event_filter );

		return ( $event_id && 'wpfa_event' === get_post_type( $event_id ) ) ? $event_id : 0;
	}

	$events = get_posts(
		array(
			'name'           => sanitize_title( $event_filter ),
			'post_type'      => 'wpfa_event',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return ! empty( $events[0] ) ? absint( $events[0] ) : 0;
};

$selected_event_id   = $resolve_event_filter( $current_event_filter );
$selected_event_slug = $selected_event_id ? get_post_field( 'post_name', $selected_event_id ) : '';

$site_timezone_string    = wp_timezone_string();
$primary_timezone_string = $site_timezone_string;

if ( $selected_event_id && class_exists( 'Wpfaevent_Calendar' ) ) {
	$primary_timezone_string = Wpfaevent_Calendar::get_event_timezone_string( $selected_event_id );
}

$selected_schedule_timezone_str = class_exists( 'Wpfaevent_Schedule_Helper' )
	? Wpfaevent_Schedule_Helper::get_selected_timezone_string( $primary_timezone_string )
	: $primary_timezone_string;

try {
	$selected_schedule_timezone = new DateTimeZone( $selected_schedule_timezone_str );
} catch ( Exception $exception ) {
	$selected_schedule_timezone     = wp_timezone();
	$selected_schedule_timezone_str = $selected_schedule_timezone->getName();
}

$schedule_timezone_options = class_exists( 'Wpfaevent_Schedule_Helper' )
	? Wpfaevent_Schedule_Helper::get_timezone_options( $primary_timezone_string )
	: array( $primary_timezone_string, 'UTC' );

$build_schedule_view_url = static function ( $view ) use ( $schedule_page_url, $selected_event_slug, $current_language, $selected_schedule_timezone_str, $primary_timezone_string ) {
	$args = array();

	if ( $selected_event_slug ) {
		$args['event'] = $selected_event_slug;
	}

	if ( $current_language ) {
		$args['language'] = $current_language;
	}

	if ( $selected_schedule_timezone_str && $selected_schedule_timezone_str !== $primary_timezone_string ) {
		$args['schedule_tz'] = $selected_schedule_timezone_str;
	}

	if ( 'calendar' === $view ) {
		$args['view'] = 'calendar';
	}

	return add_query_arg( $args, $schedule_page_url );
};

$format_timezone_label = static function ( $timezone_string ) use ( $primary_timezone_string ) {
	return class_exists( 'Wpfaevent_Schedule_Helper' )
		? Wpfaevent_Schedule_Helper::format_timezone_label( $timezone_string, $primary_timezone_string )
		: str_replace( '_', ' ', $timezone_string );
};

$format_language_label = static function ( $language ) {
	$language = trim( (string) $language );

	if ( '' === $language ) {
		return '';
	}

	$normalized = strtolower( str_replace( '_', '-', $language ) );
	$labels     = array(
		'ar'          => __( 'Arabic', 'wpfaevent' ),
		'bn'          => __( 'Bengali', 'wpfaevent' ),
		'bg'          => __( 'Bulgarian', 'wpfaevent' ),
		'ca'          => __( 'Catalan', 'wpfaevent' ),
		'cs'          => __( 'Czech', 'wpfaevent' ),
		'da'          => __( 'Danish', 'wpfaevent' ),
		'de'          => __( 'German', 'wpfaevent' ),
		'en'          => __( 'English', 'wpfaevent' ),
		'en-ca'       => __( 'English (Canada)', 'wpfaevent' ),
		'es'          => __( 'Spanish', 'wpfaevent' ),
		'fr'          => __( 'French', 'wpfaevent' ),
		'gu'          => __( 'Gujarati', 'wpfaevent' ),
		'hi'          => __( 'Hindi', 'wpfaevent' ),
		'it'          => __( 'Italian', 'wpfaevent' ),
		'ja'          => __( 'Japanese', 'wpfaevent' ),
		'ko'          => __( 'Korean', 'wpfaevent' ),
		'nl'          => __( 'Dutch', 'wpfaevent' ),
		'nl-informal' => __( 'Dutch (Informal)', 'wpfaevent' ),
		'pt'          => __( 'Portuguese', 'wpfaevent' ),
		'ru'          => __( 'Russian', 'wpfaevent' ),
		'si'          => __( 'Sinhala', 'wpfaevent' ),
		'ta'          => __( 'Tamil', 'wpfaevent' ),
		'zh-hans'     => __( 'Chinese (Simplified)', 'wpfaevent' ),
		'zh-hant'     => __( 'Chinese (Traditional)', 'wpfaevent' ),
	);

	if ( isset( $labels[ $normalized ] ) ) {
		return $labels[ $normalized ];
	}

	if ( false === strpos( $language, ' ' ) && preg_match( '/^[a-z]{2,3}(?:[-_][a-z0-9]+)*$/i', $language ) ) {
		return ucwords( str_replace( '-', ' ', $normalized ) );
	}

	return $language;
};

$event_ids              = get_posts(
	array(
		'post_type'      => 'wpfa_event',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$schedule_events        = array();
$languages              = array();
$filtered_event_ids     = array();
$is_event_schedule      = (bool) $selected_event_id;
$event_session_schedule = array(
	'items'  => array(),
	'groups' => array(),
);

foreach ( $event_ids as $event_id ) {
	$event_id        = absint( $event_id );
	$event_languages = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_language_list( get_post_meta( $event_id, 'wpfa_event_languages', true ) ) : array();
	$language_keys   = array_map( 'sanitize_title', $event_languages );

	if ( $selected_event_id && $selected_event_id !== $event_id ) {
		continue;
	}

	foreach ( $event_languages as $language ) {
		$languages[ sanitize_title( $language ) ] = $format_language_label( $language );
	}

	if ( $current_language && ! in_array( $current_language, $language_keys, true ) ) {
		continue;
	}

	$filtered_event_ids[] = $event_id;

	if ( ! $is_event_schedule ) {
		$entry = class_exists( 'Wpfaevent_Schedule_Helper' )
			? Wpfaevent_Schedule_Helper::build_event_schedule_entry( $event_id, $selected_schedule_timezone )
			: null;

		if ( ! $entry ) {
			continue;
		}

		$entry['languages']      = $event_languages;
		$entry['language_keys']  = $language_keys;
		$entry['language_label'] = implode( ', ', array_map( $format_language_label, $event_languages ) );
		$schedule_events[]       = $entry;
	}
}

asort( $languages );

if ( $is_event_schedule && in_array( $selected_event_id, $filtered_event_ids, true ) && class_exists( 'Wpfaevent_Schedule_Helper' ) ) {
	$event_session_schedule = Wpfaevent_Schedule_Helper::build_event_session_schedule( $selected_event_id, $selected_schedule_timezone );
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
	$group_key = $schedule_event['group_key'];

	if ( ! isset( $groups[ $group_key ] ) ) {
		$groups[ $group_key ] = array(
			'date'   => $schedule_event['date_label'],
			'events' => array(),
		);
	}

	$groups[ $group_key ]['events'][] = $schedule_event;
}

$selected_event_title = $selected_event_id ? get_the_title( $selected_event_id ) : '';
$event_style_attr     = '';

if ( $selected_event_id && class_exists( 'Wpfaevent_Meta_Event' ) ) {
	$event_colors        = Wpfaevent_Meta_Event::get_event_colors( $selected_event_id );
	$event_color_var_map = array(
		'wpfa_event_primary_color'          => '--event-primary',
		'wpfa_event_hover_button_color'     => '--event-primary-dark',
		'wpfa_event_theme_background_color' => '--event-soft',
		'wpfa_event_theme_success_color'    => '--event-success',
		'wpfa_event_theme_danger_color'     => '--event-danger',
	);
	$event_style_vars    = array();

	foreach ( $event_color_var_map as $meta_key => $css_var ) {
		if ( ! empty( $event_colors[ $meta_key ] ) ) {
			$event_style_vars[] = $css_var . ': ' . $event_colors[ $meta_key ];
		}
	}

	$event_style_attr = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';
}

$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => false,
	'show_register_button' => false,
	'back_button_text'     => __( 'Back to Events', 'wpfaevent' ),
	'register_button_url'  => '',
	'register_button_text' => __( 'Register', 'wpfaevent' ),
);

$filter_form_classes = 'wpfa-schedule-filter-form';
if ( ! empty( $languages ) ) {
	$filter_form_classes .= ' has-language-filter';
}
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
					<div class="wpfa-schedule-calendar" role="list" style="<?php echo 'calendar' === $current_view ? '' : 'display:none;'; ?>">
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
												<time datetime="<?php echo esc_attr( $item['start_datetime'] ); ?>" data-utc-start="<?php echo esc_attr( $item['start_datetime'] ); ?>" data-utc-end="<?php echo esc_attr( $item['end_datetime'] ); ?>"><?php echo esc_html( $item['time_label'] ); ?></time>
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

					<div class="wpfa-schedule-program" style="<?php echo 'list' === $current_view ? '' : 'display:none;'; ?>">
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
													<time class="wpfa-schedule-session-start" datetime="<?php echo esc_attr( $item['start_datetime'] ); ?>" data-utc-start="<?php echo esc_attr( $item['start_datetime'] ); ?>">
														<?php echo esc_html( $item['time_start'] ); ?>
													</time>
												<?php endif; ?>
													<?php if ( ! empty( $item['time_end'] ) ) : ?>
														<time class="wpfa-schedule-session-end" datetime="<?php echo esc_attr( $item['end_datetime'] ); ?>" data-utc-start="<?php echo esc_attr( $item['end_datetime'] ); ?>"><?php echo esc_html( $item['time_end'] ); ?></time>
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
										<div class="wpfa-schedule-details">
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
										</div>
										<div class="wpfa-schedule-item-actions">
											<div class="wpfa-schedule-actions">
												<a class="wpfa-schedule-action" href="<?php echo esc_url( $schedule_event['schedule_url'] ); ?>"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></a>
											</div>
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
