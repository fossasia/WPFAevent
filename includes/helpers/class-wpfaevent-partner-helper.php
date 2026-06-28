<?php
/**
 * Sponsor and exhibitor detail page helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage public sponsor/exhibitor detail pages.
 */
class Wpfaevent_Partner_Helper {

	/**
	 * Supported partner types.
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	private static $partner_types = array( 'exhibitor', 'sponsor' );

	/**
	 * Roles provider callback for dependency injection.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $roles_provider = null;

	/**
	 * Meta event provider callback for dependency injection.
	 *
	 * @since 1.0.0
	 * @var callable|null
	 */
	private static $meta_event_provider = null;

	/**
	 * Set the roles provider callback.
	 *
	 * @since 1.0.0
	 * @param callable $callback Callback function.
	 * @return void
	 */
	public static function set_roles_provider( $callback ) {
		self::$roles_provider = $callback;
	}

	/**
	 * Set the meta event provider callback.
	 *
	 * @since 1.0.0
	 * @param callable $callback Callback function.
	 * @return void
	 */
	public static function set_meta_event_provider( $callback ) {
		self::$meta_event_provider = $callback;
	}

	/**
	 * Check if current user can manage settings.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function current_user_can_manage_settings() {
		if ( is_callable( self::$roles_provider ) ) {
			return call_user_func( self::$roles_provider );
		}
		return class_exists( 'Wpfaevent_Roles' ) && Wpfaevent_Roles::current_user_can_manage_settings();
	}

	/**
	 * Get event colors.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array<string, string>
	 */
	public static function get_event_colors( $event_id ) {
		if ( is_callable( self::$meta_event_provider ) ) {
			return call_user_func( self::$meta_event_provider, $event_id );
		}
		return class_exists( 'Wpfaevent_Meta_Event' )
			? Wpfaevent_Meta_Event::get_event_colors( $event_id )
			: array();
	}

	/**
	 * Get the public partner detail page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_partner_page_url() {
		$page = get_page_by_path( 'partner' );

		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}

		$pages = get_pages(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Managed page lookup by assigned template.
				'meta_key'    => '_wp_page_template',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Managed page lookup by assigned template.
				'meta_value'  => 'page-partner.php',
				'number'      => 1,
				'post_status' => 'publish',
			)
		);

		$url = ! empty( $pages[0] ) ? get_permalink( $pages[0] ) : home_url( '/partner/' );

		return apply_filters( 'wpfaevent_partner_page_url', $url );
	}

	/**
	 * Build a stable partner key for URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $partner Partner record.
	 * @return string
	 */
	public static function get_partner_key( $partner ) {
		if ( ! is_array( $partner ) ) {
			return '';
		}

		if ( ! empty( $partner['id'] ) ) {
			return sanitize_key( $partner['id'] );
		}

		if ( ! empty( $partner['name'] ) ) {
			return sanitize_title( $partner['name'] );
		}

		return '';
	}

	/**
	 * Build a partner detail URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $type     Partner type. Accepts exhibitor or sponsor.
	 * @param array  $partner  Partner record.
	 * @return string
	 */
	public static function get_partner_detail_url( $event_id, $type, $partner ) {
		$event_id = absint( $event_id );
		$type     = sanitize_key( $type );
		$key      = self::get_partner_key( $partner );

		if ( ! $event_id || ! in_array( $type, self::$partner_types, true ) || '' === $key ) {
			return self::get_partner_page_url();
		}

		return add_query_arg(
			array(
				'event'   => get_post_field( 'post_name', $event_id ),
				'type'    => $type,
				'partner' => $key,
			),
			self::get_partner_page_url()
		);
	}

	/**
	 * Resolve the current partner detail request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function resolve_partner_request() {
		$read_filter_value = static function ( $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page filters.
			if ( ! isset( $_GET[ $key ] ) ) {
				return '';
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized below.
			$value = wp_unslash( $_GET[ $key ] );

			if ( is_array( $value ) ) {
				return '';
			}

			return sanitize_text_field( $value );
		};

		$event_filter  = $read_filter_value( 'event' );
		$partner_type  = sanitize_key( $read_filter_value( 'type' ) );
		$partner_key   = sanitize_key( $read_filter_value( 'partner' ) );
		$event_id      = self::resolve_event_id( $event_filter );
		$partner       = array();
		$group_name    = '';
		$partner_label = '';

		if ( $event_id && $partner_key && in_array( $partner_type, self::$partner_types, true ) ) {
			if ( 'exhibitor' === $partner_type ) {
				$partner       = self::find_exhibitor_by_key( $event_id, $partner_key );
				$partner_label = __( 'Exhibitor', 'wpfaevent' );
			} else {
				$match = self::find_sponsor_by_key( $event_id, $partner_key );
				if ( ! empty( $match['partner'] ) ) {
					$partner    = $match['partner'];
					$group_name = isset( $match['group_name'] ) ? $match['group_name'] : '';
				}
				$partner_label = __( 'Sponsor', 'wpfaevent' );
			}
		}

		return array(
			'event_id'      => $event_id,
			'event_slug'    => $event_id ? get_post_field( 'post_name', $event_id ) : '',
			'event_title'   => $event_id ? get_the_title( $event_id ) : '',
			'event_url'     => $event_id ? get_permalink( $event_id ) : '',
			'type'          => $partner_type,
			'partner_key'   => $partner_key,
			'partner'       => is_array( $partner ) ? $partner : array(),
			'group_name'    => $group_name,
			'partner_label' => $partner_label,
		);
	}

	/**
	 * Ensure the public partner detail page exists.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $check_capability Whether to require plugin settings access before creating the page.
	 * @return int Page ID, or 0 when the page could not be ensured.
	 */
	public static function ensure_partner_page( $check_capability = true ) {
		if ( $check_capability && ! self::current_user_can_manage_settings() ) {
			return 0;
		}

		$page_id = absint( get_option( 'wpfaevent_partner_page_id', 0 ) );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			self::ensure_partner_page_template( $page_id );

			return $page_id;
		}

		$page = get_page_by_path( 'partner' );
		if ( $page instanceof WP_Post ) {
			$page_id = absint( $page->ID );
			self::ensure_partner_page_template( $page_id );
			update_option( 'wpfaevent_partner_page_id', $page_id, false );

			return $page_id;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'     => __( 'Partner', 'wpfaevent' ),
				'post_name'      => 'partner',
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
		update_post_meta( $page_id, '_wp_page_template', 'page-partner.php' );
		update_post_meta( $page_id, '_wpfaevent_managed_page', 'partner' );
		update_option( 'wpfaevent_partner_page_id', $page_id, false );

		return $page_id;
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

	/**
	 * Find an exhibitor record by key.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id    Event post ID.
	 * @param string $partner_key Partner key.
	 * @return array
	 */
	private static function find_exhibitor_by_key( $event_id, $partner_key ) {
		$exhibitors = self::read_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', array() );

		foreach ( $exhibitors as $exhibitor ) {
			if ( ! is_array( $exhibitor ) ) {
				continue;
			}

			if ( self::get_partner_key( $exhibitor ) === $partner_key ) {
				return $exhibitor;
			}
		}

		return array();
	}

	/**
	 * Find a sponsor record by key.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id    Event post ID.
	 * @param string $partner_key Partner key.
	 * @return array<string, mixed>
	 */
	private static function find_sponsor_by_key( $event_id, $partner_key ) {
		$sponsor_groups = self::read_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', array() );

		foreach ( $sponsor_groups as $sponsor_group ) {
			if ( ! is_array( $sponsor_group ) || empty( $sponsor_group['sponsors'] ) || ! is_array( $sponsor_group['sponsors'] ) ) {
				continue;
			}

			$group_name = ! empty( $sponsor_group['group_name'] ) ? sanitize_text_field( $sponsor_group['group_name'] ) : __( 'Sponsors', 'wpfaevent' );

			foreach ( $sponsor_group['sponsors'] as $sponsor ) {
				if ( ! is_array( $sponsor ) ) {
					continue;
				}

				if ( self::get_partner_key( $sponsor ) === $partner_key ) {
					return array(
						'partner'    => $sponsor,
						'group_name' => $group_name,
					);
				}
			}
		}

		return array();
	}

	/**
	 * Read plugin-managed dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private static function read_dashboard_json_file( $filename, $fallback ) {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return $fallback;
		}

		$path = trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data/' . sanitize_file_name( $filename );

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return $fallback;
		}

		if ( function_exists( 'wp_json_file_decode' ) ) {
			$decoded = wp_json_file_decode( $path, array( 'associative' => true ) );

			return is_array( $decoded ) ? $decoded : $fallback;
		}

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return $fallback;
		}

		$contents = $wp_filesystem->get_contents( $path );
		if ( false === $contents || '' === trim( $contents ) ) {
			return $fallback;
		}

		$decoded = json_decode( $contents, true );

		return ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : $fallback;
	}

	/**
	 * Ensure the page uses the plugin partner template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	private static function ensure_partner_page_template( $page_id ) {
		$page_id = absint( $page_id );

		if ( ! $page_id ) {
			return;
		}

		$template = get_page_template_slug( $page_id );
		if ( 'page-partner.php' === $template ) {
			return;
		}

		update_post_meta( $page_id, '_wp_page_template', 'page-partner.php' );
	}

	/**
	 * Compile and retrieve all partner page template data.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_partner_page_data() {
		$partner_request = self::resolve_partner_request();

		$event_id      = absint( $partner_request['event_id'] );
		$event_title   = $partner_request['event_title'];
		$event_url     = $partner_request['event_url'];
		$partner_type  = $partner_request['type'];
		$partner       = $partner_request['partner'];
		$group_name    = $partner_request['group_name'];
		$partner_label = $partner_request['partner_label'];
		$has_partner   = ! empty( $partner['name'] );
		$partner_name  = $has_partner ? sanitize_text_field( $partner['name'] ) : '';
		$description   = ! empty( $partner['description'] ) ? wp_kses_post( $partner['description'] ) : '';
		$website_link  = ! empty( $partner['link'] ) ? esc_url_raw( $partner['link'] ) : '';
		$logo_url      = '';
		$banner_url    = '';
		$video_url     = ! empty( $partner['video'] ) ? esc_url_raw( $partner['video'] ) : '';
		$slides_url    = ! empty( $partner['slides'] ) ? esc_url_raw( $partner['slides'] ) : '';
		$contact_link  = ! empty( $partner['contact_link'] ) ? esc_url_raw( $partner['contact_link'] ) : '';
		$contact_email = ! empty( $partner['contact_email'] ) ? sanitize_email( $partner['contact_email'] ) : '';

		if ( 'sponsor' === $partner_type ) {
			$logo_url = ! empty( $partner['image'] ) ? esc_url_raw( $partner['image'] ) : '';
		} else {
			$logo_url   = ! empty( $partner['logo'] ) ? esc_url_raw( $partner['logo'] ) : '';
			$banner_url = ! empty( $partner['banner'] ) ? esc_url_raw( $partner['banner'] ) : '';
		}

		$event_style_attr = '';
		if ( $event_id ) {
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

			$event_style_attr = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';
		}

		$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
		if ( empty( $site_logo_url ) ) {
			$site_logo_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/logo.png' : '';
		}
		$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

		$back_url = $event_url ? $event_url . '#exhibitors' : home_url( '/events/' );
		if ( 'sponsor' === $partner_type && $event_url ) {
			$back_url = $event_url . '#sponsors';
		}

		$header_vars = array(
			'site_logo_url'        => $site_logo_url,
			'event_page_url'       => $event_url ? $event_url : home_url( '/events/' ),
			'show_back_button'     => true,
			'show_register_button' => false,
			'back_button_text'     => $event_url ? __( 'Back to Event', 'wpfaevent' ) : __( 'Back to Events', 'wpfaevent' ),
			'register_button_url'  => '',
			'register_button_text' => __( 'Register', 'wpfaevent' ),
		);

		$partner_initial = $partner_name ? strtoupper( substr( $partner_name, 0, 1 ) ) : '';
		$has_links       = $website_link || $video_url || $slides_url || $contact_link || $contact_email;
		$partner_classes = array(
			'wpfa-partner-detail',
			$partner_type ? 'is-' . sanitize_html_class( $partner_type ) : 'is-partner',
			$logo_url ? 'has-logo' : 'no-logo',
			$banner_url ? 'has-banner' : 'no-banner',
			$has_links ? 'has-links' : 'no-links',
		);
		$partner_label   = $partner_label ? $partner_label : __( 'Partner', 'wpfaevent' );

		return array(
			'event_id'         => $event_id,
			'event_title'      => $event_title,
			'event_url'        => $event_url,
			'partner_type'     => $partner_type,
			'partner'          => $partner,
			'group_name'       => $group_name,
			'partner_label'    => $partner_label,
			'has_partner'      => $has_partner,
			'partner_name'     => $partner_name,
			'description'      => $description,
			'website_link'     => $website_link,
			'logo_url'         => $logo_url,
			'banner_url'       => $banner_url,
			'video_url'        => $video_url,
			'slides_url'       => $slides_url,
			'contact_link'     => $contact_link,
			'contact_email'    => $contact_email,
			'event_style_attr' => $event_style_attr,
			'back_url'         => $back_url,
			'header_vars'      => $header_vars,
			'partner_initial'  => $partner_initial,
			'partner_classes'  => $partner_classes,
			'has_links'        => $has_links,
		);
	}

	/**
	 * Get the default/fallback partner page data structure.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_default_partner_page_data() {
		$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
		if ( empty( $site_logo_url ) ) {
			$site_logo_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/logo.png' : '';
		}
		$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

		return array(
			'event_id'         => 0,
			'event_title'      => '',
			'event_url'        => '',
			'partner_type'     => '',
			'partner'          => array(),
			'group_name'       => '',
			'partner_label'    => __( 'Partner', 'wpfaevent' ),
			'has_partner'      => false,
			'partner_name'     => '',
			'description'      => '',
			'website_link'     => '',
			'logo_url'         => '',
			'banner_url'       => '',
			'video_url'        => '',
			'slides_url'       => '',
			'contact_link'     => '',
			'contact_email'    => '',
			'event_style_attr' => '',
			'back_url'         => home_url( '/events/' ),
			'header_vars'      => array(
				'site_logo_url'        => $site_logo_url,
				'event_page_url'       => home_url( '/events/' ),
				'show_back_button'     => true,
				'show_register_button' => false,
				'back_button_text'     => __( 'Back to Events', 'wpfaevent' ),
				'register_button_url'  => '',
				'register_button_text' => __( 'Register', 'wpfaevent' ),
			),
			'partner_initial'  => '',
			'partner_classes'  => array( 'wpfa-partner-detail', 'is-partner', 'no-logo', 'no-banner', 'no-links' ),
			'has_links'        => false,
		);
	}
}
