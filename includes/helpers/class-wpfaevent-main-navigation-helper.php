<?php
/**
 * Main header navigation helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to manage and render header navigation and dropdowns.
 */
class Wpfaevent_Main_Navigation_Helper {

	/**
	 * Render the header navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $fallback_args Optional fallback arguments for default links.
	 * @param int                  $event_id      Optional event ID.
	 * @return void
	 */
	public static function render_navigation( $fallback_args = array(), $event_id = 0 ) {
		if ( $event_id <= 0 ) {
			if ( is_singular( 'wpfa_event' ) ) {
				$event_id = (int) get_the_ID();
			} elseif ( isset( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = absint( $_GET['event_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		$items = null;
		if ( $event_id > 0 ) {
			$meta = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
			if ( is_array( $meta ) && ! empty( $meta ) ) {
				$items = $meta;
			}
		}

		if ( null === $items ) {
			$opt   = get_option( 'wpfaevent_header_navigation', null );
			$items = ( is_array( $opt ) && ! empty( $opt ) ) ? $opt : self::get_latest_custom_navigation();
		}

		self::render_custom_nav_items( $items ? $items : self::get_default_nav_items( $fallback_args ) );
	}

	/**
	 * Get the latest custom navigation configured across events.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public static function get_latest_custom_navigation() {
		global $wpdb;

		if ( ! $wpdb ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$raw_meta = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY meta_id DESC LIMIT 1",
				'wpfa_event_custom_navigation'
			)
		);

		$items = $raw_meta ? maybe_unserialize( $raw_meta ) : null;
		return ( is_array( $items ) && ! empty( $items ) ) ? $items : null;
	}

	/**
	 * Get the default navigation items.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Optional arguments for URLs.
	 * @return array<int, array<string, mixed>> Default navigation items.
	 */
	public static function get_default_nav_items( $args = array() ) {
		$coc_page_id = class_exists( 'Wpfaevent_Cache' ) ? Wpfaevent_Cache::get_coc_page_id() : 0;
		$events_url  = isset( $args['events_url'] ) ? (string) $args['events_url'] : apply_filters( 'wpfaevent_events_url', home_url( '/events/' ) );
		$past_url    = isset( $args['past_events_url'] ) ? (string) $args['past_events_url'] : apply_filters( 'wpfaevent_past_events_url', home_url( '/events/?filter=past' ) );
		$coc_url     = isset( $args['coc_url'] ) ? (string) $args['coc_url'] : ( $coc_page_id ? get_permalink( $coc_page_id ) : home_url( '/code-of-conduct/' ) );

		$is_events_active = ! empty( $args['is_events_active'] ) && ( 'active' === $args['is_events_active'] || true === $args['is_events_active'] );
		$is_past_active   = ! empty( $args['is_past_events_active'] ) && ( 'active' === $args['is_past_events_active'] || true === $args['is_past_events_active'] );
		$is_coc_active    = ! empty( $args['is_coc_active'] ) && ( 'active' === $args['is_coc_active'] || true === $args['is_coc_active'] );

		return array(
			array(
				'text'      => __( 'Upcoming Events', 'wpfaevent' ),
				'type'      => 'link',
				'href'      => $events_url,
				'is_active' => $is_events_active,
			),
			array(
				'text'      => __( 'Past Events', 'wpfaevent' ),
				'type'      => 'link',
				'href'      => $past_url,
				'is_active' => $is_past_active,
			),
			array(
				'text'      => __( 'Code of Conduct', 'wpfaevent' ),
				'type'      => 'link',
				'href'      => $coc_url ? $coc_url : home_url( '/code-of-conduct/' ),
				'is_active' => $is_coc_active,
			),
		);
	}

	/**
	 * Render custom navigation items and dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $items Custom navigation items.
	 * @return void
	 */
	public static function render_custom_nav_items( $items ) {
		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		foreach ( (array) $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['text'] ) ) {
				continue;
			}

			$text = sanitize_text_field( (string) $item['text'] );
			$type = isset( $item['type'] ) && 'dropdown' === $item['type'] ? 'dropdown' : 'link';

			if ( 'dropdown' === $type && ! empty( $item['items'] ) && is_array( $item['items'] ) ) {
				$sub_links = '';
				$is_active = ! empty( $item['is_active'] );

				foreach ( $item['items'] as $sub ) {
					if ( is_array( $sub ) && ! empty( $sub['text'] ) ) {
						$sub_href = ! empty( $sub['href'] ) ? (string) $sub['href'] : '';
						$active   = ( ! empty( $sub['is_active'] ) || self::is_url_active( $sub_href, $current_uri ) );
						if ( $active ) {
							$is_active = true;
						}
						$sub_links .= sprintf(
							'<a href="%s" class="%s">%s</a>',
							esc_url( $sub_href ),
							$active ? 'active' : '',
							esc_html( (string) $sub['text'] )
						);
					}
				}

				if ( '' === $sub_links ) {
					continue;
				}

				$dropdown_class = 'nav-dropdown' . ( $is_active ? ' active' : '' );
				$toggle_class   = 'nav-dropdown-toggle' . ( $is_active ? ' active' : '' );
				?>
				<div class="<?php echo esc_attr( $dropdown_class ); ?>">
					<button type="button" class="<?php echo esc_attr( $toggle_class ); ?>" aria-haspopup="true" aria-expanded="false">
						<?php echo esc_html( $text ); ?>
						<span class="nav-dropdown-caret" aria-hidden="true">&#9662;</span>
					</button>
					<div class="nav-dropdown-content">
						<?php echo $sub_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
				<?php
			} else {
				$href      = ! empty( $item['href'] ) ? (string) $item['href'] : '';
				$is_active = ( ! empty( $item['is_active'] ) || self::is_url_active( $href, $current_uri ) );
				$active    = $is_active ? 'active' : '';
				printf(
					'<a href="%s" class="%s">%s</a>',
					esc_url( $href ),
					esc_attr( $active ),
					esc_html( $text )
				);
			}
		}
	}

	/**
	 * Check whether a URL matches the current request URI.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url         Target URL.
	 * @param string $current_uri Current request URI.
	 * @return bool
	 */
	public static function is_url_active( $url, $current_uri ) {
		$url_path  = trim( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), '/' );
		$curr_path = trim( (string) wp_parse_url( (string) $current_uri, PHP_URL_PATH ), '/' );

		if ( '' === $url_path || $url_path !== $curr_path ) {
			return false;
		}

		$url_q  = (string) wp_parse_url( (string) $url, PHP_URL_QUERY );
		$curr_q = (string) wp_parse_url( (string) $current_uri, PHP_URL_QUERY );

		$url_params  = array();
		$curr_params = array();
		parse_str( $url_q, $url_params );
		parse_str( $curr_q, $curr_params );
		ksort( $url_params );
		ksort( $curr_params );

		return $url_params === $curr_params;
	}

	/**
	 * Retrieve a custom page from an event's navigation by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $slug     Custom page slug.
	 * @return array<string, mixed>|null Custom page navigation item or null if not found.
	 */
	public static function get_custom_page( $event_id, $slug ) {
		if ( $event_id <= 0 || empty( $slug ) ) {
			return null;
		}

		$items = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
		if ( ! is_array( $items ) ) {
			return null;
		}

		foreach ( $items as $item ) {
			if ( isset( $item['type'] ) && 'custom_page' === $item['type'] && isset( $item['slug'] ) && $item['slug'] === $slug ) {
				return $item;
			}

			if ( isset( $item['type'] ) && 'dropdown' === $item['type'] && ! empty( $item['items'] ) && is_array( $item['items'] ) ) {
				foreach ( $item['items'] as $sub ) {
					if ( isset( $sub['type'] ) && 'custom_page' === $sub['type'] && isset( $sub['slug'] ) && $sub['slug'] === $slug ) {
						return $sub;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Check whether an event has a custom page matching the given slug.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id Event post ID.
	 * @param string $slug     Custom page slug.
	 * @return bool
	 */
	public static function has_custom_page( $event_id, $slug ) {
		return null !== self::get_custom_page( $event_id, $slug );
	}

	/**
	 * Render the default header navigation links.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Arguments for default links.
	 * @return void
	 */
	public static function render_default_navigation( $args = array() ) {
		self::render_custom_nav_items( self::get_default_nav_items( $args ) );
	}
}
