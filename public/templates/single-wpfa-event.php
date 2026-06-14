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

$build_eventyay_widget_assets = static function ( $widget_url ) {
	$widget_url = trim( (string) $widget_url );

	if ( '' === $widget_url || ! wp_http_validate_url( $widget_url ) ) {
		return array();
	}

	$parts = wp_parse_url( $widget_url );
	if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return array();
	}

	$scheme = strtolower( $parts['scheme'] );
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return array();
	}

	$origin = $scheme . '://' . strtolower( $parts['host'] );
	if ( isset( $parts['port'] ) ) {
		$origin .= ':' . absint( $parts['port'] );
	}

	$path      = ! empty( $parts['path'] ) ? trailingslashit( $parts['path'] ) : '/';
	$event_url = esc_url_raw( $origin . $path );

	if ( ! wp_http_validate_url( $event_url ) ) {
		return array();
	}

	$css_url    = esc_url_raw( trailingslashit( $event_url ) . 'widget/v1.css' );
	$script_url = esc_url_raw( trailingslashit( $origin ) . 'widget/v1.en.js' );

	if ( ! wp_http_validate_url( $css_url ) || ! wp_http_validate_url( $script_url ) ) {
		return array();
	}

	return array(
		'event_url'  => $event_url,
		'css_url'    => $css_url,
		'script_url' => $script_url,
	);
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

$event_timezone_string = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_timezone_string( $event_id ) : $site_timezone_string;
$event_timezone        = $get_timezone_object( $event_timezone_string, $site_timezone );

$selected_schedule_timezone_string = $event_timezone_string;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only timezone converter for the public schedule.
if ( isset( $_GET['schedule_tz'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately before validation.
	$requested_schedule_timezone = sanitize_text_field( wp_unslash( $_GET['schedule_tz'] ) );

	if ( '' !== $requested_schedule_timezone ) {
		try {
			new DateTimeZone( $requested_schedule_timezone );
			$selected_schedule_timezone_string = $requested_schedule_timezone;
		} catch ( Exception $exception ) {
			$selected_schedule_timezone_string = $event_timezone_string;
		}
	}
}

$selected_schedule_timezone = $get_timezone_object( $selected_schedule_timezone_string, $site_timezone );
$schedule_timezone_options  = array_values(
	array_unique(
		array_merge(
			array(
				$event_timezone_string,
				$site_timezone_string,
				'UTC',
			),
			DateTimeZone::listIdentifiers()
		)
	)
);

$current_schedule_view = 'list';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only schedule view toggle.
if ( isset( $_GET['schedule_view'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized before validation.
	$requested_schedule_view = sanitize_title( wp_unslash( $_GET['schedule_view'] ) );

	if ( in_array( $requested_schedule_view, array( 'list', 'calendar' ), true ) ) {
		$current_schedule_view = $requested_schedule_view;
	}
}

$format_timezone_label = static function ( $timezone_string ) use ( $event_timezone_string, $site_timezone_string ) {
	$label = str_replace( '_', ' ', $timezone_string );

	if ( $timezone_string === $event_timezone_string ) {
		return sprintf(
			/* translators: %s: event timezone. */
			__( 'Event timezone (%s)', 'wpfaevent' ),
			$label
		);
	}

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

$format_schedule_date = static function ( $start_datetime, $fallback_date, $fallback_time ) use ( $format_datetime_value, $build_schedule_fallback_datetime, $selected_schedule_timezone, $event_timezone ) {
	if ( $start_datetime ) {
		$formatted_date = $format_datetime_value( $start_datetime, get_option( 'date_format' ), $selected_schedule_timezone );

		if ( $formatted_date ) {
			return $formatted_date;
		}
	}

	$fallback_datetime = $build_schedule_fallback_datetime( $fallback_date, $fallback_time, $event_timezone );

	return $fallback_datetime ? wp_date( get_option( 'date_format' ), $fallback_datetime->getTimestamp(), $selected_schedule_timezone ) : $fallback_date;
};

$format_schedule_time = static function ( $start_datetime, $end_datetime, $fallback_date, $fallback_time ) use ( $format_datetime_value, $build_schedule_fallback_datetime, $selected_schedule_timezone, $event_timezone, $split_schedule_time_range ) {
	$start_label = $start_datetime ? $format_datetime_value( $start_datetime, get_option( 'time_format' ), $selected_schedule_timezone ) : '';
	$end_label   = $end_datetime ? $format_datetime_value( $end_datetime, get_option( 'time_format' ), $selected_schedule_timezone ) : '';

	if ( ! $start_label && $fallback_time ) {
		$time_parts        = $split_schedule_time_range( $fallback_time );
		$fallback_start    = $build_schedule_fallback_datetime( $fallback_date, $time_parts[0], $event_timezone );
		$fallback_end_time = isset( $time_parts[1] ) ? $time_parts[1] : '';
		$fallback_end      = $fallback_end_time ? $build_schedule_fallback_datetime( $fallback_date, $fallback_end_time, $event_timezone ) : null;
		$start_label       = $fallback_start ? wp_date( get_option( 'time_format' ), $fallback_start->getTimestamp(), $selected_schedule_timezone ) : '';
		$end_label         = $fallback_end ? wp_date( get_option( 'time_format' ), $fallback_end->getTimestamp(), $selected_schedule_timezone ) : '';
	}

	if ( $start_label && $end_label && $start_label !== $end_label ) {
		return $start_label . ' - ' . $end_label;
	}

	return $start_label ? $start_label : $fallback_time;
};

$parse_schedule_datetime = static function ( $datetime ) {
	$datetime = trim( (string) $datetime );

	if ( '' === $datetime ) {
		return null;
	}

	try {
		return new DateTimeImmutable( $datetime );
	} catch ( Exception $exception ) {
		return null;
	}
};

$site_settings      = $read_dashboard_json( 'site-settings-' . absint( $event_id ) . '.json', array() );
$dashboard_speakers = $read_dashboard_json( 'speakers-' . absint( $event_id ) . '.json', array() );
$schedule_table     = $read_dashboard_json( 'schedule-' . absint( $event_id ) . '.json', array() );
$sponsor_groups     = $read_dashboard_json( 'sponsors-' . absint( $event_id ) . '.json', array() );
$exhibitors         = $read_dashboard_json( 'exhibitors-' . absint( $event_id ) . '.json', array() );
$section_visibility = isset( $site_settings['section_visibility'] ) && is_array( $site_settings['section_visibility'] ) ? $site_settings['section_visibility'] : array();

$event_title            = get_the_title( $event_id );
$start_date             = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
$end_date               = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
$location               = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
$venue_information      = trim( (string) get_post_meta( $event_id, 'wpfa_event_venue_information', true ) );
$custom_tabs            = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_custom_tabs( get_post_meta( $event_id, 'wpfa_event_custom_tabs', true ) ) : array();
$event_languages        = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_language_list( get_post_meta( $event_id, 'wpfa_event_languages', true ) ) : array();
$event_language_label   = implode( ', ', $event_languages );
$event_url              = get_post_meta( $event_id, 'wpfa_event_url', true );
$event_url              = $event_url ? esc_url_raw( $event_url ) : '';
$event_header_image_url = get_post_meta( $event_id, 'wpfa_event_header_image_url', true );
$event_header_image_url = $event_header_image_url ? esc_url_raw( $event_header_image_url ) : '';
$event_logo_url         = get_post_meta( $event_id, 'wpfa_event_logo_url', true );
$event_logo_url         = $event_logo_url ? esc_url_raw( $event_logo_url ) : '';
$ticket_widget_url      = get_post_meta( $event_id, 'wpfa_event_ticket_widget_url', true );
$ticket_widget_url      = $ticket_widget_url ? esc_url_raw( $ticket_widget_url ) : '';
$about_content          = isset( $site_settings['about_section_content'] ) ? trim( (string) $site_settings['about_section_content'] ) : '';
$post_content           = trim( (string) get_post_field( 'post_content', $event_id ) );
$event_lead             = trim( (string) get_post_meta( $event_id, '_event_lead_text', true ) );

if ( ! $event_header_image_url && ! empty( $site_settings['event_header_image_url'] ) ) {
	$event_header_image_url = esc_url_raw( $site_settings['event_header_image_url'] );
}

if ( ! $event_logo_url && ! empty( $site_settings['event_logo_url'] ) ) {
	$event_logo_url = esc_url_raw( $site_settings['event_logo_url'] );
}

if ( ! $ticket_widget_url && ! empty( $site_settings['ticket_widget_url'] ) ) {
	$ticket_widget_url = esc_url_raw( $site_settings['ticket_widget_url'] );
}

if ( ! $ticket_widget_url && $event_url && get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ) ) {
	$ticket_widget_url = $event_url;
}

if ( ! $event_header_image_url ) {
	$event_header_image_url = get_the_post_thumbnail_url( $event_id, 'full' );
	$event_header_image_url = $event_header_image_url ? esc_url_raw( $event_header_image_url ) : '';
}

$ticket_widget_assets = $build_eventyay_widget_assets( $ticket_widget_url );
$show_ticket_widget   = ! empty( $ticket_widget_assets['event_url'] );

if ( $show_ticket_widget ) {
	wp_enqueue_style(
		'wpfaevent-eventyay-widget-' . absint( $event_id ),
		$ticket_widget_assets['css_url'],
		array(),
		WPFAEVENT_VERSION,
		'all'
	);

	wp_enqueue_script(
		'wpfaevent-eventyay-widget-' . absint( $event_id ),
		$ticket_widget_assets['script_url'],
		array(),
		WPFAEVENT_VERSION,
		true
	);

	wp_script_add_data( 'wpfaevent-eventyay-widget-' . absint( $event_id ), 'async', true );
}

$main_speaker_limit             = absint( apply_filters( 'wpfa_event_main_speaker_limit', 20, $event_id ) );
$main_speaker_limit             = $main_speaker_limit ? $main_speaker_limit : 20;
$speaker_ids                    = $get_linked_speaker_ids( $event_id );
$featured_speaker_ids           = class_exists( 'Wpfaevent_Meta_Event' )
	? Wpfaevent_Meta_Event::resolve_event_featured_speaker_ids( $event_id, $speaker_ids, $dashboard_speakers )
	: array();
$regular_speaker_ids            = array_values( array_diff( $speaker_ids, $featured_speaker_ids ) );
$main_speaker_ids               = array_slice( $speaker_ids, 0, $main_speaker_limit );
$main_regular_speaker_ids       = array_slice( $regular_speaker_ids, 0, $main_speaker_limit );
$main_speaker_overflow_count    = max( 0, count( $speaker_ids ) - count( $main_speaker_ids ) );
$regular_speaker_overflow_count = max( 0, count( $regular_speaker_ids ) - count( $main_regular_speaker_ids ) );

$event_slug              = get_post_field( 'post_name', $event_id );
$speaker_placeholder_url = WPFAEVENT_URL . 'assets/images/speaker-placeholder.svg';
$speakers_url            = add_query_arg( 'event', $event_slug, home_url( '/speakers/' ) );
$schedule_page_url       = class_exists( 'Wpfaevent_Schedule_Helper' ) ? Wpfaevent_Schedule_Helper::get_schedule_page_url() : home_url( '/full-schedule/' );
$event_schedule_args     = array(
	'event' => $event_slug,
);

if ( 'calendar' === $current_schedule_view ) {
	$event_schedule_args['view'] = 'calendar';
}

if ( $selected_schedule_timezone_string && $selected_schedule_timezone_string !== $event_timezone_string ) {
	$event_schedule_args['schedule_tz'] = $selected_schedule_timezone_string;
}

$event_schedule_url   = add_query_arg( $event_schedule_args, $schedule_page_url );
$additional_page_url  = class_exists( 'Wpfaevent_Additional_Information_Helper' ) ? Wpfaevent_Additional_Information_Helper::get_additional_information_page_url() : home_url( '/additional-information/' );
$event_additional_url = add_query_arg( 'event', $event_slug, $additional_page_url );

$build_event_schedule_view_url = static function ( $view ) use ( $event_id, $event_timezone_string, $selected_schedule_timezone_string ) {
	$args = array();

	if ( $selected_schedule_timezone_string && $selected_schedule_timezone_string !== $event_timezone_string ) {
		$args['schedule_tz'] = $selected_schedule_timezone_string;
	}

	if ( 'calendar' === $view ) {
		$args['schedule_view'] = 'calendar';
	}

	return add_query_arg( $args, get_permalink( $event_id ) ) . '#wpfa-event-schedule-title';
};

$register_text = ! empty( $site_settings['reg_button_text'] ) ? sanitize_text_field( $site_settings['reg_button_text'] ) : __( 'Get Tickets', 'wpfaevent' );
$register_url  = ! empty( $site_settings['reg_button_link'] ) ? esc_url_raw( $site_settings['reg_button_link'] ) : $event_url;

if ( ! $register_url && $show_ticket_widget ) {
	$register_url = $ticket_widget_assets['event_url'];
}

$event_calendar_data = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_calendar_data( $event_id ) : array();
$event_calendar_data = is_wp_error( $event_calendar_data ) ? array() : $event_calendar_data;
$event_calendar_url  = ! empty( $event_calendar_data ) && class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_ics_url( $event_id ) : '';
$event_google_url    = ! empty( $event_calendar_data ) && class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::build_google_calendar_url( $event_calendar_data ) : '';

$show_about             = ! array_key_exists( 'about', $section_visibility ) || ! empty( $section_visibility['about'] );
$show_speakers          = ! array_key_exists( 'speakers', $section_visibility ) || ! empty( $section_visibility['speakers'] );
$show_schedule          = ! array_key_exists( 'schedule', $section_visibility ) || ! empty( $section_visibility['schedule'] );
$show_sponsors          = ! array_key_exists( 'sponsors', $section_visibility ) || ! empty( $section_visibility['sponsors'] );
$show_exhibitors        = ! array_key_exists( 'exhibitors', $section_visibility ) || ! empty( $section_visibility['exhibitors'] );
$schedule_rows          = isset( $schedule_table['data'] ) && is_array( $schedule_table['data'] ) ? $schedule_table['data'] : array();
$schedule_meta          = isset( $schedule_table['sessions'] ) && is_array( $schedule_table['sessions'] ) ? $schedule_table['sessions'] : array();
$schedule_head          = ! empty( $schedule_rows[0] ) && is_array( $schedule_rows[0] ) ? $schedule_rows[0] : array();
$schedule_body          = ! empty( $schedule_head ) ? array_slice( $schedule_rows, 1 ) : $schedule_rows;
$speaker_count          = count( $speaker_ids );
$featured_speaker_count = count( $featured_speaker_ids );
$visible_sponsor_groups = array();
$sponsor_count          = 0;
$visible_exhibitors     = array();
$event_colors           = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_colors( $event_id ) : array();
$event_color_var_map    = array(
	'wpfa_event_primary_color'          => '--event-primary',
	'wpfa_event_hover_button_color'     => '--event-primary-dark',
	'wpfa_event_theme_background_color' => '--event-soft',
	'wpfa_event_theme_success_color'    => '--event-success',
	'wpfa_event_theme_danger_color'     => '--event-danger',
);
$event_style_vars       = array();

foreach ( $event_color_var_map as $meta_key => $css_var ) {
	if ( ! empty( $event_colors[ $meta_key ] ) ) {
		$event_style_vars[] = $css_var . ': ' . $event_colors[ $meta_key ];
	}
}

$event_style_attr = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';

foreach ( $sponsor_groups as $sponsor_group ) {
	if ( ! is_array( $sponsor_group ) ) {
		continue;
	}

	$group_sponsors         = isset( $sponsor_group['sponsors'] ) && is_array( $sponsor_group['sponsors'] ) ? $sponsor_group['sponsors'] : array();
	$visible_group_sponsors = array();

	foreach ( $group_sponsors as $sponsor ) {
		if ( ! is_array( $sponsor ) || ( empty( $sponsor['name'] ) && empty( $sponsor['image'] ) ) ) {
			continue;
		}

		$visible_group_sponsors[] = $sponsor;
	}

	if ( empty( $visible_group_sponsors ) ) {
		continue;
	}

	$sponsor_group['sponsors'] = $visible_group_sponsors;
	$visible_sponsor_groups[]  = $sponsor_group;
	$sponsor_count            += count( $visible_group_sponsors );
}

foreach ( $exhibitors as $exhibitor ) {
	if ( ! is_array( $exhibitor ) || empty( $exhibitor['name'] ) ) {
		continue;
	}

	$visible_exhibitors[] = $exhibitor;
}

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

$main_dashboard_speakers                  = array_slice( $dashboard_speakers, 0, $main_speaker_limit );
$main_dashboard_regular_speakers          = array_slice( $dashboard_regular_speakers, 0, $main_speaker_limit );
$dashboard_speaker_overflow_count         = max( 0, count( $dashboard_speakers ) - count( $main_dashboard_speakers ) );
$dashboard_regular_speaker_overflow_count = max( 0, count( $dashboard_regular_speakers ) - count( $main_dashboard_regular_speakers ) );

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

$date_label           = ! empty( $event_calendar_data['date_label'] ) ? sanitize_text_field( $event_calendar_data['date_label'] ) : $format_event_date( $start_date );
$event_time_label     = ! empty( $event_calendar_data['time_label'] ) ? sanitize_text_field( $event_calendar_data['time_label'] ) : '';
$event_timezone_label = ! empty( $event_calendar_data['timezone_label'] ) ? sanitize_text_field( $event_calendar_data['timezone_label'] ) : str_replace( '_', ' ', $event_timezone_string );
$event_start_content  = ! empty( $event_calendar_data['start_content'] ) ? sanitize_text_field( $event_calendar_data['start_content'] ) : $start_date;
$event_end_content    = ! empty( $event_calendar_data['end_content'] ) ? sanitize_text_field( $event_calendar_data['end_content'] ) : $end_date;

if ( empty( $event_calendar_data['date_label'] ) && $end_date && $end_date !== $start_date ) {
	$date_label .= $date_label ? ' - ' . $format_event_date( $end_date ) : $format_event_date( $end_date );
}

$build_schedule_calendar_url = static function ( $item ) use ( $build_schedule_fallback_datetime, $event_timezone, $event_timezone_string, $event_title, $event_url, $location, $parse_schedule_datetime, $split_schedule_time_range ) {
	if ( ! class_exists( 'Wpfaevent_Calendar' ) ) {
		return '';
	}

	$title      = ! empty( $item['title'] ) ? $item['title'] : $event_title;
	$time_parts = $split_schedule_time_range( isset( $item['time'] ) ? $item['time'] : '' );
	$start      = $parse_schedule_datetime( isset( $item['start_datetime'] ) ? $item['start_datetime'] : '' );
	$end        = $parse_schedule_datetime( isset( $item['end_datetime'] ) ? $item['end_datetime'] : '' );
	$all_day    = false;
	$start_date = '';
	$end_date   = '';

	if ( ! $start ) {
		$start = $build_schedule_fallback_datetime(
			isset( $item['date'] ) ? $item['date'] : '',
			isset( $time_parts[0] ) ? $time_parts[0] : '',
			$event_timezone
		);
	}

	if ( $start && ! $end && ! empty( $time_parts[1] ) ) {
		$end = $build_schedule_fallback_datetime( isset( $item['date'] ) ? $item['date'] : '', $time_parts[1], $event_timezone );
	}

	if ( $start && $end && $end <= $start ) {
		$end = $end->modify( '+1 day' );
	}

	if ( ! $start && empty( $time_parts[0] ) ) {
		$start_date = class_exists( 'Wpfaevent_Meta_Event' )
			? Wpfaevent_Meta_Event::sanitize_date_value( isset( $item['date'] ) ? $item['date'] : '' )
			: '';
		$end_date   = $start_date;
		$all_day    = '' !== $start_date;
	}

	if ( ! $start && ! $all_day ) {
		return '';
	}

	$details = array_filter(
		array(
			$event_title ? sprintf(
				/* translators: %s: event title. */
				__( 'Event: %s', 'wpfaevent' ),
				$event_title
			) : '',
			! empty( $item['speakers'] ) ? sprintf(
				/* translators: %s: speaker names. */
				__( 'Speakers: %s', 'wpfaevent' ),
				$item['speakers']
			) : '',
			! empty( $item['track'] ) ? sprintf(
				/* translators: %s: session track. */
				__( 'Track: %s', 'wpfaevent' ),
				$item['track']
			) : '',
			! empty( $item['room'] ) ? sprintf(
				/* translators: %s: session room. */
				__( 'Room: %s', 'wpfaevent' ),
				$item['room']
			) : '',
		)
	);

	$session_location = ! empty( $item['room'] ) ? $item['room'] : $location;

	return Wpfaevent_Calendar::build_google_calendar_url(
		array(
			'title'           => $title,
			'description'     => implode( "\n", $details ),
			'location'        => $session_location,
			'url'             => $event_url,
			'timezone_string' => $event_timezone_string,
			'all_day'         => $all_day,
			'start_date'      => $start_date,
			'end_date'        => $end_date,
			'start_datetime'  => $all_day ? null : $start,
			'end_datetime'    => $all_day ? null : $end,
		)
	);
};

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

	$schedule_item = array(
		'date'           => $row_date,
		'date_label'     => $format_schedule_date( $start_datetime, $row_date, $row_time ),
		'time'           => $row_time,
		'time_label'     => $format_schedule_time( $start_datetime, $end_datetime, $row_date, $row_time ),
		'title'          => ! empty( $row[2] ) ? sanitize_text_field( $row[2] ) : $event_title,
		'speakers'       => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
		'track'          => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
		'room'           => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
		'start_datetime' => $start_datetime,
		'end_datetime'   => $end_datetime,
	);

	$schedule_item['calendar_url'] = $build_schedule_calendar_url( $schedule_item );

	$time_parts                  = preg_split( '/\s*-\s*/', $schedule_item['time_label'], 2 );
	$schedule_item['time_start'] = isset( $time_parts[0] ) ? trim( $time_parts[0] ) : $schedule_item['time_label'];
	$schedule_item['time_end']   = isset( $time_parts[1] ) ? trim( $time_parts[1] ) : '';

	$schedule_items[] = $schedule_item;
}

$schedule_preview_limit      = absint( apply_filters( 'wpfa_event_schedule_preview_limit', 3, $event_id ) );
$schedule_preview_items      = $schedule_preview_limit ? array_slice( $schedule_items, 0, $schedule_preview_limit ) : $schedule_items;
$schedule_preview_day_groups = array();

foreach ( $schedule_preview_items as $schedule_item ) {
	$day_key = ! empty( $schedule_item['date_label'] ) ? $schedule_item['date_label'] : __( 'TBD', 'wpfaevent' );

	if ( ! isset( $schedule_preview_day_groups[ $day_key ] ) ) {
		$schedule_preview_day_groups[ $day_key ] = array();
	}

	$schedule_preview_day_groups[ $day_key ][] = $schedule_item;
}

$schedule_hidden_count = max( 0, count( $schedule_items ) - count( $schedule_preview_items ) );
$first_schedule        = ! empty( $schedule_items[0] ) ? $schedule_items[0] : array();
$custom_sections       = array();

foreach ( $custom_tabs as $custom_tab ) {
	if ( empty( $custom_tab['slug'] ) || empty( $custom_tab['title'] ) || empty( $custom_tab['content'] ) ) {
		continue;
	}

	$custom_sections[ $custom_tab['slug'] ] = $custom_tab['title'];
}

$wpfa_event_nav_context = array(
	'show_about'      => $show_about,
	'show_speakers'   => $show_speakers,
	'show_schedule'   => $show_schedule,
	'show_sponsors'   => $show_sponsors,
	'show_exhibitors' => $show_exhibitors,
	'has_about'       => $show_about && '' !== trim( $about_content ),
	'has_tickets'     => $show_ticket_widget,
	'has_speakers'    => $show_speakers && ( ! empty( $speaker_ids ) || ! empty( $dashboard_speakers ) ),
	'has_schedule'    => $show_schedule && ! empty( $schedule_items ),
	'has_sponsors'    => $show_sponsors && ! empty( $visible_sponsor_groups ),
	'has_exhibitors'  => $show_exhibitors && ! empty( $visible_exhibitors ),
	'has_venue'       => '' !== trim( wp_strip_all_tags( $venue_information ) ),
	'custom_sections' => $custom_sections,
);
$wpfa_event_nav_items   = class_exists( 'Wpfaevent_Event_Navigation_Helper' )
	? Wpfaevent_Event_Navigation_Helper::build_nav_items( $wpfa_event_nav_context )
	: array();

$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$hero_classes = array( 'wpfa-event-hero' );
if ( $event_header_image_url ) {
	$hero_classes[] = 'has-event-header-image';
}

if ( $event_logo_url && $event_header_image_url && $event_logo_url === $event_header_image_url ) {
	$event_logo_url = '';
}

$hero_style_attr = $event_header_image_url ? ' style="' . esc_attr( '--event-header-image: url("' . esc_url_raw( $event_header_image_url ) . '");' ) . '"' : '';

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
		<?php if ( $event_header_image_url ) : ?>
			<meta itemprop="image" content="<?php echo esc_url( $event_header_image_url ); ?>">
		<?php endif; ?>
		<section class="<?php echo esc_attr( implode( ' ', $hero_classes ) ); ?>"<?php echo $hero_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
			<div class="container wpfa-event-hero-inner">
				<div class="wpfa-event-hero-copy">
					<?php if ( $event_logo_url ) : ?>
						<div class="wpfa-event-logo-mark">
							<img src="<?php echo esc_url( $event_logo_url ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" loading="eager">
						</div>
					<?php endif; ?>
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
					<?php if ( $show_ticket_widget ) : ?>
						<a class="wpfa-event-register" href="#tickets">
							<?php echo esc_html( $register_text ); ?>
						</a>
					<?php elseif ( $register_url ) : ?>
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

		<?php if ( $show_ticket_widget ) : ?>
			<section id="tickets" class="wpfa-event-section wpfa-event-tickets" aria-labelledby="wpfa-event-tickets-title">
				<div class="container">
					<div class="wpfa-event-section-head">
						<div>
							<h2 id="wpfa-event-tickets-title"><?php esc_html_e( 'Tickets', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Select ticket options and continue checkout through Eventyay.', 'wpfaevent' ); ?></p>
						</div>
						<a href="<?php echo esc_url( $ticket_widget_assets['event_url'] ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Open on Eventyay', 'wpfaevent' ); ?>
						</a>
					</div>
					<div class="wpfa-event-ticket-widget">
						<eventyay-widget event="<?php echo esc_url( $ticket_widget_assets['event_url'] ); ?>"></eventyay-widget>
						<noscript>
							<p class="wpfa-event-ticket-fallback">
								<?php esc_html_e( 'JavaScript is required to show Eventyay tickets here.', 'wpfaevent' ); ?>
								<a href="<?php echo esc_url( $ticket_widget_assets['event_url'] ); ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'Buy tickets on Eventyay', 'wpfaevent' ); ?>
								</a>
							</p>
						</noscript>
					</div>
				</div>
			</section>
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
										$sponsor_detail_url  = class_exists( 'Wpfaevent_Partner_Helper' )
											? Wpfaevent_Partner_Helper::get_partner_detail_url( $event_id, 'sponsor', $sponsor )
											: '';
										?>
										<a class="wpfa-event-partner-card wpfa-event-partner-card-link" href="<?php echo esc_url( $sponsor_detail_url ? $sponsor_detail_url : '#' ); ?>">
											<?php if ( $sponsor_image ) : ?>
												<div class="wpfa-event-partner-logo" style="--partner-logo-size: <?php echo esc_attr( $logo_size ); ?>px;">
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
							$exhibitor_detail_url  = class_exists( 'Wpfaevent_Partner_Helper' )
								? Wpfaevent_Partner_Helper::get_partner_detail_url( $event_id, 'exhibitor', $exhibitor )
								: '';
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
