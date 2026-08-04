<?php
/**
 * Schedule template controller.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller class to parse, query, and format schedule page data.
 */
class Wpfaevent_Schedule_Controller {

	/**
	 * Retrieve all parsed request parameters, query data, localizations, timezones, and templates settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function get_schedule_view_data() {
		$wpfaevent_is_embed = ! empty( $GLOBALS['wpfaevent_template_embed'] );
		$events_per_page    = max( 1, (int) apply_filters( 'wpfa_schedule_events_per_page', 20 ) );
		$current_page       = max( 1, (int) get_query_var( 'paged', 1 ) );
		$schedule_page_url  = get_permalink();

		if ( ! $schedule_page_url && class_exists( 'Wpfaevent_Schedule_Helper' ) ) {
			$schedule_page_url = Wpfaevent_Schedule_Helper::get_schedule_page_url();
		}

		$current_language     = self::read_filter_value( 'language', 'slug' );
		$current_event_filter = self::read_filter_value( 'event' );
		$current_view         = self::read_filter_value( 'view', 'slug' );
		$query_page           = self::read_filter_value( 'paged', 'int' );

		if ( $query_page ) {
			$current_page = max( 1, $query_page );
		}

		if ( ! in_array( $current_view, array( 'list', 'calendar' ), true ) ) {
			$current_view = 'list';
		}

		$selected_event_id   = self::resolve_event_filter( $current_event_filter );
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
				$languages[ sanitize_title( $language ) ] = self::format_language_label( $language );
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
				$entry['language_label'] = implode( ', ', array_map( array( 'self', 'format_language_label' ), $event_languages ) );
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

		return array(
			'wpfaevent_is_embed'             => $wpfaevent_is_embed,
			'events_per_page'                => $events_per_page,
			'current_page'                   => $current_page,
			'schedule_page_url'              => $schedule_page_url,
			'current_language'               => $current_language,
			'current_event_filter'           => $current_event_filter,
			'current_view'                   => $current_view,
			'selected_event_id'              => $selected_event_id,
			'selected_event_slug'            => $selected_event_slug,
			'selected_event_title'           => $selected_event_title,
			'selected_schedule_timezone_str' => $selected_schedule_timezone_str,
			'selected_schedule_timezone'     => $selected_schedule_timezone,
			'schedule_timezone_options'      => $schedule_timezone_options,
			'event_ids'                      => $event_ids,
			'schedule_events'                => $schedule_events,
			'languages'                      => $languages,
			'filtered_event_ids'             => $filtered_event_ids,
			'is_event_schedule'              => $is_event_schedule,
			'event_session_schedule'         => $event_session_schedule,
			'total_events'                   => $total_events,
			'groups'                         => $groups,
			'event_style_attr'               => $event_style_attr,
			'header_vars'                    => $header_vars,
			'filter_form_classes'            => $filter_form_classes,
			'site_timezone_string'           => $site_timezone_string,
			'primary_timezone_string'        => $primary_timezone_string,
		);
	}

	/**
	 * Read a query parameter safely.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  Request GET variable name.
	 * @param string $type Data format type to sanitize by.
	 * @return string|int
	 */
	public static function read_filter_value( $key, $type = 'text' ) {
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
	}

	/**
	 * Resolve an event filter into a post ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $event_filter Slug or ID.
	 * @return int
	 */
	public static function resolve_event_filter( $event_filter ) {
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
	}

	/**
	 * Get the list of languages and labels, filterable for extension (OCP compliant).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function get_languages() {
		$languages = array(
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

		return (array) apply_filters( 'wpfa_schedule_languages', $languages );
	}

	/**
	 * Formats a language tag into a human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @param string $language Language tag.
	 * @return string
	 */
	public static function format_language_label( $language ) {
		$language = trim( (string) $language );

		if ( '' === $language ) {
			return '';
		}

		$normalized = strtolower( str_replace( '_', '-', $language ) );
		$labels     = self::get_languages();

		if ( isset( $labels[ $normalized ] ) ) {
			return $labels[ $normalized ];
		}

		if ( false === strpos( $language, ' ' ) && preg_match( '/^[a-z]{2,3}(?:[-_][a-z0-9]+)*$/i', $language ) ) {
			return ucwords( str_replace( '-', ' ', $normalized ) );
		}

		return $language;
	}

	/**
	 * Builds a view URL with the specified parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view                      View slug ('list' or 'calendar').
	 * @param string $schedule_page_url         Schedule base URL.
	 * @param string $selected_event_slug       Selected event slug.
	 * @param string $current_language          Current language.
	 * @param string $selected_schedule_timezone_str Target timezone.
	 * @param string $primary_timezone_string   Primary timezone.
	 * @return string
	 */
	public static function build_view_url( $view, $schedule_page_url, $selected_event_slug, $current_language, $selected_schedule_timezone_str, $primary_timezone_string ) {
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
	}
}
