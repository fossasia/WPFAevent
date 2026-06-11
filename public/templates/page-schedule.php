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

$events_per_page = max( 1, (int) apply_filters( 'wpfa_schedule_events_per_page', 20 ) );
$current_page    = max( 1, (int) get_query_var( 'paged', 1 ) );

$site_timezone_string           = wp_timezone_string();
$selected_schedule_timezone_str = class_exists( 'Wpfaevent_Schedule_Helper' )
	? Wpfaevent_Schedule_Helper::get_selected_timezone_string( $site_timezone_string )
	: $site_timezone_string;

try {
	$selected_schedule_timezone = new DateTimeZone( $selected_schedule_timezone_str );
} catch ( Exception $exception ) {
	$selected_schedule_timezone     = wp_timezone();
	$selected_schedule_timezone_str = $selected_schedule_timezone->getName();
}

$schedule_timezone_options = class_exists( 'Wpfaevent_Schedule_Helper' )
	? Wpfaevent_Schedule_Helper::get_timezone_options( $site_timezone_string )
	: array( $site_timezone_string, 'UTC' );

$format_timezone_label = static function ( $timezone_string ) use ( $site_timezone_string ) {
	return class_exists( 'Wpfaevent_Schedule_Helper' )
		? Wpfaevent_Schedule_Helper::format_timezone_label( $timezone_string, $site_timezone_string )
		: str_replace( '_', ' ', $timezone_string );
};

$event_ids       = get_posts(
	array(
		'post_type'      => 'wpfa_event',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$schedule_events = array();

foreach ( $event_ids as $event_id ) {
	$entry = class_exists( 'Wpfaevent_Schedule_Helper' )
		? Wpfaevent_Schedule_Helper::build_event_schedule_entry( $event_id, $selected_schedule_timezone )
		: null;

	if ( ! $entry ) {
		continue;
	}

	$schedule_events[] = $entry;
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

$schedule_page_url = get_permalink();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-schedule-template' ); ?>>
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

	<main class="wpfa-schedule">
		<div class="container">
			<div class="wpfa-schedule-head">
				<div>
					<h1><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></h1>
					<p><?php esc_html_e( 'Upcoming events grouped by start date.', 'wpfaevent' ); ?></p>
				</div>
				<?php if ( ! empty( $schedule_events ) ) : ?>
					<form class="wpfa-event-timezone-form" action="<?php echo esc_url( $schedule_page_url ); ?>" method="get">
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
						<button type="submit"><?php esc_html_e( 'Convert', 'wpfaevent' ); ?></button>
					</form>
				<?php endif; ?>
			</div>

			<?php if ( $groups ) : ?>
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
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>

				<?php
				$total           = max( 1, (int) ceil( $total_events / $events_per_page ) );
				$pagination_args = array();
				if ( $selected_schedule_timezone_str && $selected_schedule_timezone_str !== $site_timezone_string ) {
					$pagination_args['schedule_tz'] = $selected_schedule_timezone_str;
				}
				wpfa_render_pagination( $total, $current_page, __( 'Schedule pagination', 'wpfaevent' ), $pagination_args );
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'No schedule entries yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>
	</main>
</div>

<?php wp_footer(); ?>
</body>
</html>
