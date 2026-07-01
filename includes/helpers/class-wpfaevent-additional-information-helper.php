<?php
/**
 * Additional information page helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage the public Additional Information page.
 */
class Wpfaevent_Additional_Information_Helper {

	/**
	 * Callback provider to verify if current user can manage settings.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $roles_provider = null;

	/**
	 * Callback provider to get event colors.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $meta_event_provider = null;

	/**
	 * Set the roles provider callback.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $callback Provider callback.
	 * @return void
	 */
	public static function set_roles_provider( $callback ) {
		if ( is_callable( $callback ) ) {
			self::$roles_provider = $callback;
		}
	}

	/**
	 * Set the meta event provider callback.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $callback Provider callback.
	 * @return void
	 */
	public static function set_meta_event_provider( $callback ) {
		if ( is_callable( $callback ) ) {
			self::$meta_event_provider = $callback;
		}
	}

	/**
	 * Verify if current user can manage settings.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_settings() {
		if ( null !== self::$roles_provider ) {
			return (bool) call_user_func( self::$roles_provider );
		}

		if ( class_exists( 'Wpfaevent_Roles' ) ) {
			return Wpfaevent_Roles::current_user_can_manage_settings();
		}

		return false;
	}

	/**
	 * Get colors for a specific event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, string>
	 */
	public static function get_event_colors( $event_id ) {
		if ( null !== self::$meta_event_provider ) {
			return (array) call_user_func( self::$meta_event_provider, $event_id );
		}

		if ( class_exists( 'Wpfaevent_Meta_Event' ) ) {
			return Wpfaevent_Meta_Event::get_event_colors( $event_id );
		}

		return array();
	}

	/**
	 * Get the public additional information page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_additional_information_page_url() {
		$page_id = absint( get_option( 'wpfaevent_additional_information_page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			$url = get_permalink( $page_id );

			return apply_filters( 'wpfaevent_additional_information_page_url', $url );
		}

		$page = get_page_by_path( 'additional-information' );
		if ( $page instanceof WP_Post ) {
			$page_id = absint( $page->ID );
			update_option( 'wpfaevent_additional_information_page_id', $page_id, false );

			$url = get_permalink( $page_id );

			return apply_filters( 'wpfaevent_additional_information_page_url', $url );
		}

		$url = home_url( '/additional-information/' );

		return apply_filters( 'wpfaevent_additional_information_page_url', $url );
	}

	/**
	 * Get an event-specific additional information URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_additional_information_url( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return self::get_additional_information_page_url();
		}

		return add_query_arg( 'event', get_post_field( 'post_name', $event_id ), self::get_additional_information_page_url() );
	}

	/**
	 * Ensure the public additional information page exists.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $check_capability Whether to require plugin settings access before creating the page.
	 * @return int Page ID, or 0 when the page could not be ensured.
	 */
	public static function ensure_additional_information_page( $check_capability = true ) {
		if ( $check_capability && ! self::current_user_can_manage_settings() ) {
			return 0;
		}

		$page_id = absint( get_option( 'wpfaevent_additional_information_page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			self::ensure_additional_information_page_template( $page_id );

			return $page_id;
		}

		$page = get_page_by_path( 'additional-information' );
		if ( $page instanceof WP_Post ) {
			$page_id = absint( $page->ID );
			self::ensure_additional_information_page_template( $page_id );
			update_option( 'wpfaevent_additional_information_page_id', $page_id, false );

			return $page_id;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'     => __( 'Additional Information', 'wpfaevent' ),
				'post_name'      => 'additional-information',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_content'   => '',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return 0;
		}

		$page_id = absint( $page_id );
		update_post_meta( $page_id, '_wp_page_template', 'page-additional-information.php' );
		update_post_meta( $page_id, '_wpfaevent_managed_page', 'additional_information' );
		update_option( 'wpfaevent_additional_information_page_id', $page_id, false );

		return $page_id;
	}

	/**
	 * Ensure the page uses the plugin additional information template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	private static function ensure_additional_information_page_template( $page_id ) {
		$page_id = absint( $page_id );

		if ( ! $page_id ) {
			return;
		}

		$template = get_page_template_slug( $page_id );
		if ( 'page-additional-information.php' === $template ) {
			return;
		}

		update_post_meta( $page_id, '_wp_page_template', 'page-additional-information.php' );
	}

	/**
	 * Get the default additional information page data structure.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_additional_information_page_data() {
		$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
		if ( empty( $site_logo_url ) ) {
			$site_logo_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/logo.png' : '';
		}
		$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

		return array(
			'selected_event_id'               => 0,
			'selected_event_slug'             => '',
			'selected_event_title'            => '',
			'selected_event_url'              => '',
			'venue_information'               => '',
			'has_information'                 => false,
			'additional_information_page_url' => self::get_additional_information_page_url(),
			'event_schedule_url'              => home_url( '/full-schedule/' ),
			'event_additional_url'            => self::get_additional_information_page_url(),
			'event_style_attr'                => '',
			'header_vars'                     => array(
				'site_logo_url'        => $site_logo_url,
				'event_page_url'       => home_url( '/events/' ),
				'show_back_button'     => true,
				'show_register_button' => false,
				'back_button_text'     => __( 'Back to Events', 'wpfaevent' ),
				'register_button_url'  => '',
				'register_button_text' => __( 'Register', 'wpfaevent' ),
			),
		);
	}

	/**
	 * Get computed page data for additional information page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_page_url Optional. Current page URL to override.
	 * @return array<string, mixed>
	 */
	public static function get_additional_information_page_data( $current_page_url = '' ) {
		$data = self::get_default_additional_information_page_data();

		$additional_information_page_url = $current_page_url ? $current_page_url : get_permalink();
		if ( ! $additional_information_page_url ) {
			$additional_information_page_url = self::get_additional_information_page_url();
		}
		$data['additional_information_page_url'] = $additional_information_page_url;

		$event_filter = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page filters.
		if ( isset( $_GET['event'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized below.
			$value = wp_unslash( $_GET['event'] );
			if ( ! is_array( $value ) ) {
				$event_filter = sanitize_text_field( $value );
			}
		}

		$event_id = self::resolve_event_id( $event_filter );

		if ( ! $event_id ) {
			$data['event_additional_url'] = $additional_information_page_url;

			return $data;
		}

		$data['selected_event_id']    = $event_id;
		$data['selected_event_slug']  = (string) get_post_field( 'post_name', $event_id );
		$data['selected_event_title'] = (string) get_the_title( $event_id );
		$data['selected_event_url']   = (string) get_permalink( $event_id );

		$venue_information         = trim( (string) get_post_meta( $event_id, 'wpfa_event_venue_information', true ) );
		$data['venue_information'] = $venue_information;
		$data['has_information']   = '' !== trim( wp_strip_all_tags( $venue_information ) );

		$schedule_page_url            = class_exists( 'Wpfaevent_Schedule_Helper' ) ? Wpfaevent_Schedule_Helper::get_schedule_page_url() : home_url( '/full-schedule/' );
		$data['event_schedule_url']   = add_query_arg( 'event', $data['selected_event_slug'], $schedule_page_url );
		$data['event_additional_url'] = add_query_arg( 'event', $data['selected_event_slug'], $additional_information_page_url );

		// Style attributes.
		$event_colors        = self::get_event_colors( $event_id );
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

		$data['event_style_attr'] = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';

		// Header vars.
		$data['header_vars']['event_page_url']   = $data['selected_event_url'];
		$data['header_vars']['back_button_text'] = __( 'Back to Event', 'wpfaevent' );

		return $data;
	}

	/**
	 * Resolve an event ID from a slug or numeric ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $event_filter Event slug or ID.
	 * @return int
	 */
	private static function resolve_event_id( $event_filter ) {
		$event_filter = trim( (string) $event_filter );

		if ( '' === $event_filter ) {
			return 0;
		}

		if ( is_numeric( $event_filter ) ) {
			$event_id = absint( $event_filter );

			return ( $event_id && 'wpfa_event' === get_post_type( $event_id ) && 'publish' === get_post_status( $event_id ) ) ? $event_id : 0;
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
}
