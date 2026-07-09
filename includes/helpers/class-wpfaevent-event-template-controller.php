<?php
/**
 * Event template controller.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event detail page calculations and configurations.
 */
class Wpfaevent_Event_Template_Controller {

	/**
	 * Helper provider callback for testing/DI.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $partner_helper_provider = null;

	/**
	 * Meta event provider callback for testing/DI.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $meta_event_provider = null;

	/**
	 * Set the partner helper provider for DI.
	 *
	 * @since 1.0.0
	 * @param callable $callback Provider callback.
	 * @return void
	 */
	public static function set_partner_helper_provider( $callback ) {
		self::$partner_helper_provider = $callback;
	}

	/**
	 * Set the meta event provider for DI.
	 *
	 * @since 1.0.0
	 * @param callable $callback Provider callback.
	 * @return void
	 */
	public static function set_meta_event_provider( $callback ) {
		self::$meta_event_provider = $callback;
	}

	/**
	 * Wrapper to get partner detail URL.
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event post ID.
	 * @param string $type     Partner type.
	 * @param array  $partner  Partner array.
	 * @return string
	 */
	private static function get_partner_detail_url( $event_id, $type, $partner ) {
		if ( is_callable( self::$partner_helper_provider ) ) {
			return call_user_func( self::$partner_helper_provider, $event_id, $type, $partner );
		}
		return class_exists( 'Wpfaevent_Partner_Helper' )
			? Wpfaevent_Partner_Helper::get_partner_detail_url( $event_id, $type, $partner )
			: '';
	}

	/**
	 * Wrapper to get event colors.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	private static function get_event_colors( $event_id ) {
		if ( is_callable( self::$meta_event_provider ) ) {
			return call_user_func( self::$meta_event_provider, $event_id );
		}
		return class_exists( 'Wpfaevent_Meta_Event' )
			? Wpfaevent_Meta_Event::get_event_colors( $event_id )
			: array();
	}

	/**
	 * Compile all event template data.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_event_template_data( $event_id ) {
		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return self::get_default_event_template_data();
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

			$end_time = 2 === count( $parts ) ? trim( $parts[1] ) : '';

			return array(
				trim( $parts[0] ),
				$end_time,
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
				$fallback_end_time = $time_parts[1];
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

		$ticket_widget_assets   = $build_eventyay_widget_assets( $ticket_widget_url );
		$show_ticket_widget     = ! empty( $ticket_widget_assets['event_url'] );
		$ticket_widget_id       = 'wpfa-event-ticket-widget-' . absint( $event_id );
		$ticket_widget_skip_ssl = ! is_ssl();

		$about_content = isset( $site_settings['about_section_content'] ) ? trim( (string) $site_settings['about_section_content'] ) : '';
		$post_content  = trim( (string) get_post_field( 'post_content', $event_id ) );
		$event_lead    = trim( (string) get_post_meta( $event_id, '_event_lead_text', true ) );

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
		$speaker_placeholder_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/speaker-placeholder.svg' : '';
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
		$event_colors           = self::get_event_colors( $event_id );
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

				// Pre-compute detail URL to satisfy Dependency Inversion (Class Coupling Smell).
				$sponsor['detail_url'] = self::get_partner_detail_url( $event_id, 'sponsor', $sponsor );

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

			// Pre-compute detail URL to satisfy Dependency Inversion (Class Coupling Smell).
			$exhibitor['detail_url'] = self::get_partner_detail_url( $event_id, 'exhibitor', $exhibitor );

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
					$time_parts[0],
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
			$site_logo_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/logo.png' : '';
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

		return array(
			'event_style_attr'                         => $event_style_attr,
			'header_vars'                              => $header_vars,
			'event_title'                              => $event_title,
			'date_label'                               => $date_label,
			'event_start_content'                      => $event_start_content,
			'event_end_content'                        => $event_end_content,
			'event_time_label'                         => $event_time_label,
			'event_timezone_label'                     => $event_timezone_label,
			'event_url'                                => $event_url,
			'event_header_image_url'                   => $event_header_image_url,
			'event_logo_url'                           => $event_logo_url,
			'show_ticket_widget'                       => $show_ticket_widget,
			'ticket_widget_assets'                     => $ticket_widget_assets,
			'ticket_widget_id'                         => $ticket_widget_id,
			'ticket_widget_skip_ssl'                   => $ticket_widget_skip_ssl,
			'location'                                 => $location,
			'event_language_label'                     => $event_language_label,
			'schedule_items'                           => $schedule_items,
			'about_content'                            => $about_content,
			'register_url'                             => $register_url,
			'register_text'                            => $register_text,
			'event_google_url'                         => $event_google_url,
			'event_calendar_url'                       => $event_calendar_url,
			'speaker_count'                            => $speaker_count,
			'sponsor_count'                            => $sponsor_count,
			'visible_exhibitors'                       => $visible_exhibitors,
			'first_schedule'                           => $first_schedule,
			'wpfa_event_nav_items'                     => $wpfa_event_nav_items,
			'show_about'                               => $show_about,
			'show_speakers'                            => $show_speakers,
			'show_schedule'                            => $show_schedule,
			'show_sponsors'                            => $show_sponsors,
			'show_exhibitors'                          => $show_exhibitors,
			'venue_information'                        => $venue_information,
			'event_additional_url'                     => $event_additional_url,
			'custom_tabs'                              => $custom_tabs,
			'featured_speaker_ids'                     => $featured_speaker_ids,
			'featured_speaker_count'                   => $featured_speaker_count,
			'dashboard_featured_speakers'              => $dashboard_featured_speakers,
			'dashboard_regular_speakers'               => $dashboard_regular_speakers,
			'regular_speaker_overflow_count'           => $regular_speaker_overflow_count,
			'dashboard_regular_speaker_overflow_count' => $dashboard_regular_speaker_overflow_count,
			'speaker_placeholder_url'                  => $speaker_placeholder_url,
			'speakers_url'                             => $speakers_url,
			'selected_schedule_timezone_string'        => $selected_schedule_timezone_string,
			'schedule_timezone_options'                => $schedule_timezone_options,
			'format_timezone_label'                    => $format_timezone_label,
			'build_event_schedule_view_url'            => $build_event_schedule_view_url,
			'selected_schedule_timezone'               => $selected_schedule_timezone,
			'main_regular_speaker_ids'                 => $main_regular_speaker_ids,
			'main_dashboard_regular_speakers'          => $main_dashboard_regular_speakers,
			'main_dashboard_speakers'                  => $main_dashboard_speakers,
			'dashboard_speaker_overflow_count'         => $dashboard_speaker_overflow_count,
			'dashboard_speakers'                       => $dashboard_speakers,
			'schedule_preview_items'                   => $schedule_preview_items,
			'schedule_preview_day_groups'              => $schedule_preview_day_groups,
			'schedule_hidden_count'                    => $schedule_hidden_count,
			'event_schedule_url'                       => $event_schedule_url,
			'visible_sponsor_groups'                   => $visible_sponsor_groups,
			'current_schedule_view'                    => $current_schedule_view,
		);
	}

	/**
	 * Get fallback event template data structure.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_default_event_template_data() {
		return array(
			'event_style_attr'                         => '',
			'header_vars'                              => array(
				'site_logo_url'        => '',
				'event_page_url'       => home_url( '/events/' ),
				'show_back_button'     => true,
				'show_register_button' => false,
				'back_button_text'     => __( 'All Events', 'wpfaevent' ),
				'register_button_url'  => '',
				'register_button_text' => __( 'Get Tickets', 'wpfaevent' ),
			),
			'event_title'                              => '',
			'date_label'                               => '',
			'event_header_image_url'                   => '',
			'event_logo_url'                           => '',
			'show_ticket_widget'                       => false,
			'ticket_widget_assets'                     => array(),
			'ticket_widget_id'                         => '',
			'ticket_widget_skip_ssl'                   => false,
			'event_start_content'                      => '',
			'event_end_content'                        => '',
			'event_time_label'                         => '',
			'event_timezone_label'                     => '',
			'event_url'                                => '',
			'location'                                 => '',
			'event_language_label'                     => '',
			'schedule_items'                           => array(),
			'about_content'                            => '',
			'register_url'                             => '',
			'register_text'                            => '',
			'event_google_url'                         => '',
			'event_calendar_url'                       => '',
			'speaker_count'                            => 0,
			'sponsor_count'                            => 0,
			'visible_exhibitors'                       => array(),
			'first_schedule'                           => array(),
			'wpfa_event_nav_items'                     => array(),
			'show_about'                               => false,
			'show_speakers'                            => false,
			'show_schedule'                            => false,
			'show_sponsors'                            => false,
			'show_exhibitors'                          => false,
			'venue_information'                        => '',
			'event_additional_url'                     => '',
			'custom_tabs'                              => array(),
			'featured_speaker_ids'                     => array(),
			'featured_speaker_count'                   => 0,
			'dashboard_featured_speakers'              => array(),
			'dashboard_regular_speakers'               => array(),
			'regular_speaker_overflow_count'           => 0,
			'dashboard_regular_speaker_overflow_count' => 0,
			'speaker_placeholder_url'                  => '',
			'speakers_url'                             => '',
			'selected_schedule_timezone_string'        => '',
			'schedule_timezone_options'                => array(),
			'format_timezone_label'                    => static function ( $timezone_string = '' ) {
				unset( $timezone_string );
				return '';
			},
			'build_event_schedule_view_url'            => static function ( $view = '' ) {
				unset( $view );
				return '';
			},
			'selected_schedule_timezone'               => wp_timezone(),
			'main_regular_speaker_ids'                 => array(),
			'main_dashboard_regular_speakers'          => array(),
			'main_dashboard_speakers'                  => array(),
			'dashboard_speaker_overflow_count'         => 0,
			'dashboard_speakers'                       => array(),
			'schedule_preview_items'                   => array(),
			'schedule_preview_day_groups'              => array(),
			'schedule_hidden_count'                    => 0,
			'event_schedule_url'                       => '',
			'visible_sponsor_groups'                   => array(),
			'current_schedule_view'                    => 'list',
		);
	}
}
