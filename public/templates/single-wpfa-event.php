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

	try {
		$datetime = new DateTimeImmutable( $date, wp_timezone() );
	} catch ( Exception $exception ) {
		return $date;
	}

	return wp_date( get_option( 'date_format' ), $datetime->getTimestamp(), wp_timezone() );
};

$get_timezone_object = static function ( $timezone_string, $fallback_timezone ) {
	try {
		return new DateTimeZone( $timezone_string );
	} catch ( Exception $exception ) {
		return $fallback_timezone;
	}
};

$site_timezone        = wp_timezone();
$site_timezone_string = wp_timezone_string();

if ( '' === trim( (string) $site_timezone_string ) ) {
	$site_timezone_string = $site_timezone->getName();
}

$selected_schedule_timezone_string = $site_timezone_string;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only timezone converter for the public schedule.
if ( isset( $_GET['schedule_tz'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately before validation.
	$requested_schedule_timezone = sanitize_text_field( wp_unslash( $_GET['schedule_tz'] ) );

	if ( '' !== $requested_schedule_timezone ) {
		try {
			new DateTimeZone( $requested_schedule_timezone );
			$selected_schedule_timezone_string = $requested_schedule_timezone;
		} catch ( Exception $exception ) {
			$selected_schedule_timezone_string = $site_timezone_string;
		}
	}
}

$selected_schedule_timezone = $get_timezone_object( $selected_schedule_timezone_string, $site_timezone );
$schedule_timezone_options  = array_values(
	array_unique(
		array_merge(
			array(
				$site_timezone_string,
				'UTC',
			),
			DateTimeZone::listIdentifiers()
		)
	)
);

$format_timezone_label = static function ( $timezone_string ) use ( $site_timezone_string ) {
	$label = str_replace( '_', ' ', $timezone_string );

	if ( $timezone_string === $site_timezone_string ) {
		return sprintf(
			/* translators: %s: WordPress site timezone. */
			__( 'Site timezone (%s)', 'wpfaevent' ),
			$label
		);
	}

	return $label;
};

$format_datetime_value = static function ( $value, $format, $timezone ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	try {
		$datetime = new DateTimeImmutable( $value );
	} catch ( Exception $exception ) {
		return '';
	}

	return wp_date( $format, $datetime->getTimestamp(), $timezone );
};

$split_schedule_time_range = static function ( $time ) {
	$parts = explode( ' - ', trim( (string) $time ), 2 );

	if ( 2 !== count( $parts ) ) {
		$parts = explode( '-', trim( (string) $time ), 2 );
	}

	return array(
		isset( $parts[0] ) ? trim( $parts[0] ) : '',
		isset( $parts[1] ) ? trim( $parts[1] ) : '',
	);
};

$build_schedule_fallback_datetime = static function ( $date, $time, $source_timezone ) use ( $split_schedule_time_range ) {
	$date = trim( (string) $date );
	$time = trim( (string) $time );

	if ( '' === $date ) {
		return null;
	}

	if ( '' !== $time ) {
		$time_parts = $split_schedule_time_range( $time );
		$time       = $time_parts[0];
	}

	$value = trim( $date . ' ' . $time );

	try {
		return new DateTimeImmutable( $value, $source_timezone );
	} catch ( Exception $exception ) {
		return null;
	}
};

$format_schedule_date = static function ( $start_datetime, $fallback_date, $fallback_time ) use ( $format_datetime_value, $build_schedule_fallback_datetime, $selected_schedule_timezone, $site_timezone ) {
	if ( $start_datetime ) {
		$formatted_date = $format_datetime_value( $start_datetime, get_option( 'date_format' ), $selected_schedule_timezone );

		if ( $formatted_date ) {
			return $formatted_date;
		}
	}

	$fallback_datetime = $build_schedule_fallback_datetime( $fallback_date, $fallback_time, $site_timezone );

	return $fallback_datetime ? wp_date( get_option( 'date_format' ), $fallback_datetime->getTimestamp(), $selected_schedule_timezone ) : $fallback_date;
};

$format_schedule_time = static function ( $start_datetime, $end_datetime, $fallback_date, $fallback_time ) use ( $format_datetime_value, $build_schedule_fallback_datetime, $selected_schedule_timezone, $site_timezone, $split_schedule_time_range ) {
	$start_label = $start_datetime ? $format_datetime_value( $start_datetime, get_option( 'time_format' ), $selected_schedule_timezone ) : '';
	$end_label   = $end_datetime ? $format_datetime_value( $end_datetime, get_option( 'time_format' ), $selected_schedule_timezone ) : '';

	if ( ! $start_label && $fallback_time ) {
		$time_parts        = $split_schedule_time_range( $fallback_time );
		$fallback_start    = $build_schedule_fallback_datetime( $fallback_date, $time_parts[0], $site_timezone );
		$fallback_end_time = isset( $time_parts[1] ) ? $time_parts[1] : '';
		$fallback_end      = $fallback_end_time ? $build_schedule_fallback_datetime( $fallback_date, $fallback_end_time, $site_timezone ) : null;
		$start_label       = $fallback_start ? wp_date( get_option( 'time_format' ), $fallback_start->getTimestamp(), $selected_schedule_timezone ) : '';
		$end_label         = $fallback_end ? wp_date( get_option( 'time_format' ), $fallback_end->getTimestamp(), $selected_schedule_timezone ) : '';
	}

	if ( $start_label && $end_label && $start_label !== $end_label ) {
		return $start_label . ' - ' . $end_label;
	}

	return $start_label ? $start_label : $fallback_time;
};

$site_settings      = $read_dashboard_json( 'site-settings-' . absint( $event_id ) . '.json', array() );
$dashboard_speakers = $read_dashboard_json( 'speakers-' . absint( $event_id ) . '.json', array() );
$schedule_table     = $read_dashboard_json( 'schedule-' . absint( $event_id ) . '.json', array() );
$section_visibility = isset( $site_settings['section_visibility'] ) && is_array( $site_settings['section_visibility'] ) ? $site_settings['section_visibility'] : array();

$event_title             = get_the_title( $event_id );
$start_date              = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
$end_date                = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
$location                = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
$event_languages         = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_language_list( get_post_meta( $event_id, 'wpfa_event_languages', true ) ) : array();
$event_language_label    = implode( ', ', $event_languages );
$event_url               = get_post_meta( $event_id, 'wpfa_event_url', true );
$event_url               = $event_url ? esc_url_raw( $event_url ) : '';
$about_content           = isset( $site_settings['about_section_content'] ) ? trim( (string) $site_settings['about_section_content'] ) : '';
$post_content            = trim( (string) get_post_field( 'post_content', $event_id ) );
$event_lead              = trim( (string) get_post_meta( $event_id, '_event_lead_text', true ) );
$speaker_ids             = $get_linked_speaker_ids( $event_id );
$featured_speaker_ids    = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_featured_speaker_ids( $event_id ) : array();
$featured_speaker_ids    = array_values( array_intersect( $featured_speaker_ids, $speaker_ids ) );
$regular_speaker_ids     = array_values( array_diff( $speaker_ids, $featured_speaker_ids ) );
$event_slug              = get_post_field( 'post_name', $event_id );
$speaker_placeholder_url = WPFAEVENT_URL . 'assets/images/speaker-placeholder.svg';
$speakers_url            = add_query_arg( 'event', $event_slug, home_url( '/speakers/' ) );
$register_text           = ! empty( $site_settings['reg_button_text'] ) ? sanitize_text_field( $site_settings['reg_button_text'] ) : __( 'Get Tickets', 'wpfaevent' );
$register_url            = ! empty( $site_settings['reg_button_link'] ) ? esc_url_raw( $site_settings['reg_button_link'] ) : $event_url;
$show_about              = ! array_key_exists( 'about', $section_visibility ) || ! empty( $section_visibility['about'] );
$show_speakers           = ! array_key_exists( 'speakers', $section_visibility ) || ! empty( $section_visibility['speakers'] );
$show_schedule           = ! array_key_exists( 'schedule', $section_visibility ) || ! empty( $section_visibility['schedule'] );
$schedule_rows           = isset( $schedule_table['data'] ) && is_array( $schedule_table['data'] ) ? $schedule_table['data'] : array();
$schedule_meta           = isset( $schedule_table['sessions'] ) && is_array( $schedule_table['sessions'] ) ? $schedule_table['sessions'] : array();
$schedule_head           = ! empty( $schedule_rows[0] ) && is_array( $schedule_rows[0] ) ? $schedule_rows[0] : array();
$schedule_body           = ! empty( $schedule_head ) ? array_slice( $schedule_rows, 1 ) : $schedule_rows;
$speaker_count           = count( $speaker_ids );
$featured_speaker_count  = count( $featured_speaker_ids );
$event_colors            = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_colors( $event_id ) : array();
$event_color_var_map     = array(
	'wpfa_event_primary_color'          => '--event-primary',
	'wpfa_event_hover_button_color'     => '--event-primary-dark',
	'wpfa_event_theme_background_color' => '--event-soft',
	'wpfa_event_theme_success_color'    => '--event-success',
	'wpfa_event_theme_danger_color'     => '--event-danger',
);
$event_style_vars        = array();

foreach ( $event_color_var_map as $meta_key => $css_var ) {
	if ( ! empty( $event_colors[ $meta_key ] ) ) {
		$event_style_vars[] = $css_var . ': ' . $event_colors[ $meta_key ];
	}
}

$event_style_attr = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';

$dashboard_featured_speakers = array();
$dashboard_regular_speakers  = array();

foreach ( $dashboard_speakers as $dashboard_speaker ) {
	if ( ! is_array( $dashboard_speaker ) || empty( $dashboard_speaker['name'] ) ) {
		continue;
	}

	if ( ! empty( $dashboard_speaker['featured'] ) ) {
		$dashboard_featured_speakers[] = $dashboard_speaker;
		continue;
	}

	$dashboard_regular_speakers[] = $dashboard_speaker;
}

if ( ! empty( $dashboard_featured_speakers ) ) {
	usort(
		$dashboard_featured_speakers,
		static function ( $speaker_a, $speaker_b ) {
			$order_a = isset( $speaker_a['featured_order'] ) ? absint( $speaker_a['featured_order'] ) : 0;
			$order_b = isset( $speaker_b['featured_order'] ) ? absint( $speaker_b['featured_order'] ) : 0;

			if ( $order_a && $order_b && $order_a !== $order_b ) {
				return $order_a <=> $order_b;
			}

			if ( $order_a !== $order_b ) {
				return $order_a ? -1 : 1;
			}

			return strcasecmp( $speaker_a['name'] ?? '', $speaker_b['name'] ?? '' );
		}
	);
}

if ( '' === $about_content ) {
	$about_content = '' !== $post_content ? $post_content : $event_lead;
}

$date_label = $format_event_date( $start_date );
if ( $end_date && $end_date !== $start_date ) {
	$date_label .= $date_label ? ' - ' . $format_event_date( $end_date ) : $format_event_date( $end_date );
}

$schedule_items = array();
foreach ( $schedule_body as $row_index => $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}

	$row_meta       = isset( $schedule_meta[ $row_index ] ) && is_array( $schedule_meta[ $row_index ] ) ? $schedule_meta[ $row_index ] : array();
	$start_datetime = isset( $row_meta['starts_at'] ) ? sanitize_text_field( $row_meta['starts_at'] ) : '';
	$end_datetime   = isset( $row_meta['ends_at'] ) ? sanitize_text_field( $row_meta['ends_at'] ) : '';
	$row_date       = isset( $row[0] ) ? sanitize_text_field( $row[0] ) : '';
	$row_time       = isset( $row[1] ) ? sanitize_text_field( $row[1] ) : '';

	$schedule_items[] = array(
		'date'           => $row_date,
		'date_label'     => $format_schedule_date( $start_datetime, $row_date, $row_time ),
		'time'           => $row_time,
		'time_label'     => $format_schedule_time( $start_datetime, $end_datetime, $row_date, $row_time ),
		'title'          => isset( $row[2] ) ? sanitize_text_field( $row[2] ) : '',
		'speakers'       => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
		'track'          => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
		'room'           => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
		'start_datetime' => $start_datetime,
		'end_datetime'   => $end_datetime,
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
							<span itemprop="startDate" content="<?php echo esc_attr( $start_date ); ?>"><?php echo esc_html( $date_label ); ?></span>
						<?php endif; ?>
						<?php if ( $location ) : ?>
							<span itemprop="location"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
						<?php if ( $event_language_label ) : ?>
							<span><?php echo esc_html( $event_language_label ); ?></span>
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

							<?php if ( ! empty( $regular_speaker_ids ) ) : ?>
								<div class="wpfa-event-speaker-list">
									<h3><?php esc_html_e( 'More Speakers', 'wpfaevent' ); ?></h3>
									<ul>
										<?php foreach ( $regular_speaker_ids as $sid ) : ?>
											<?php
											if ( 'wpfa_speaker' !== get_post_type( $sid ) || 'publish' !== get_post_status( $sid ) ) {
												continue;
											}

											$speaker_role_parts = array_filter(
												array(
													sanitize_text_field( get_post_meta( $sid, 'wpfa_speaker_position', true ) ),
													sanitize_text_field( get_post_meta( $sid, 'wpfa_speaker_organization', true ) ),
												)
											);
											$speaker_role       = implode( ' | ', $speaker_role_parts );
											?>
											<li>
												<a href="<?php echo esc_url( get_permalink( $sid ) ); ?>"><?php echo esc_html( get_the_title( $sid ) ); ?></a>
												<?php if ( $speaker_role ) : ?>
													<span><?php echo esc_html( $speaker_role ); ?></span>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<div class="wpfa-speakers-grid">
								<?php
								$wpfa_hide_speaker_card_admin_actions = true;
								$wpfa_schedule_display_timezone       = $selected_schedule_timezone;
								foreach ( $speaker_ids as $sid ) :
									if ( 'wpfa_speaker' !== get_post_type( $sid ) || 'publish' !== get_post_status( $sid ) ) {
										continue;
									}

									include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php';
								endforeach;
								unset( $wpfa_hide_speaker_card_admin_actions );
								unset( $wpfa_schedule_display_timezone );
								?>
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
								<div class="wpfa-event-speaker-list">
									<h3><?php esc_html_e( 'More Speakers', 'wpfaevent' ); ?></h3>
									<ul>
										<?php foreach ( $dashboard_regular_speakers as $speaker ) : ?>
											<?php
											$speaker_name       = sanitize_text_field( $speaker['name'] ?? '' );
											$speaker_role_parts = array_filter(
												array(
													sanitize_text_field( $speaker['position'] ?? '' ),
													sanitize_text_field( $speaker['organization'] ?? '' ),
												)
											);
											$speaker_role       = implode( ' | ', $speaker_role_parts );
											?>
											<?php if ( $speaker_name ) : ?>
												<li>
													<strong><?php echo esc_html( $speaker_name ); ?></strong>
													<?php if ( $speaker_role ) : ?>
														<span><?php echo esc_html( $speaker_role ); ?></span>
													<?php endif; ?>
												</li>
											<?php endif; ?>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						<?php else : ?>
							<div class="wpfa-speakers-grid">
								<?php foreach ( $dashboard_speakers as $speaker ) : ?>
									<?php include WPFAEVENT_PATH . 'public/partials/speakers/dashboard-speaker-card.php'; ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
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
						<?php if ( ! empty( $schedule_items ) ) : ?>
							<form class="wpfa-event-timezone-form" action="<?php echo esc_url( get_permalink( $event_id ) . '#wpfa-event-schedule-title' ); ?>" method="get">
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
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $schedule_items ) ) : ?>
						<div class="wpfa-event-timeline">
							<?php foreach ( $schedule_items as $item ) : ?>
								<article class="wpfa-event-session">
									<div class="wpfa-event-session-time">
										<?php if ( ! empty( $item['date_label'] ) ) : ?>
											<span><?php echo esc_html( $item['date_label'] ); ?></span>
										<?php endif; ?>
										<strong><?php echo esc_html( $item['time_label'] ); ?></strong>
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
