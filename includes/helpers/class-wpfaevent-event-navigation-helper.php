<?php
/**
 * Event section navigation helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build and filter event section navigation items.
 */
class Wpfaevent_Event_Navigation_Helper {

	/**
	 * Return the dashboard data directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_data_dir() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'fossasia-data';
	}

	/**
	 * Read configured navigation items from dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function read_navigation_items() {
		$file = self::get_data_dir() . '/navigation.json';

		if ( ! file_exists( $file ) ) {
			return array();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local dashboard JSON.
		$data = json_decode( file_get_contents( $file ), true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Map legacy section anchors to dashboard navigation targets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $href Navigation href.
	 * @return string
	 */
	public static function normalize_href( $href ) {
		$href = trim( (string) $href );

		$legacy_map = array(
			'#wpfa-event-about-title'    => '#about',
			'#wpfa-event-speakers-title' => '#speakers',
			'#wpfa-event-schedule-title' => '#schedule-overview',
		);

		return isset( $legacy_map[ $href ] ) ? $legacy_map[ $href ] : $href;
	}

	/**
	 * Determine whether a navigation target should be shown.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $href    Navigation href.
	 * @param array<string, mixed> $context Section availability context.
	 * @return bool
	 */
	public static function section_is_visible( $href, $context ) {
		$href = self::normalize_href( $href );

		switch ( $href ) {
			case '#about':
				return ! empty( $context['show_about'] ) && ! empty( $context['has_about'] );
			case '#speakers':
				return ! empty( $context['show_speakers'] ) && ! empty( $context['has_speakers'] );
			case '#schedule-overview':
				return ! empty( $context['show_schedule'] ) && ! empty( $context['has_schedule'] );
			case '#sponsors':
				return ! empty( $context['show_sponsors'] ) && ! empty( $context['has_sponsors'] );
			case '#exhibitors':
				return ! empty( $context['show_exhibitors'] ) && ! empty( $context['has_exhibitors'] );
			case '#venue':
				return ! empty( $context['has_venue'] );
		}

		if ( 0 === strpos( $href, '#custom-section-' ) ) {
			$section_id = sanitize_title( substr( $href, strlen( '#custom-section-' ) ) );

			return ! empty( $context['custom_sections'] ) && ! empty( $context['custom_sections'][ $section_id ] );
		}

		return '' !== $href;
	}

	/**
	 * Build navigation items for an event page.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>   $context           Section availability context.
	 * @param array<int, array>|null $navigation_items  Optional preloaded navigation items.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_nav_items( $context, $navigation_items = null ) {
		if ( null === $navigation_items ) {
			$navigation_items = self::read_navigation_items();
		}

		$items = array();

		foreach ( $navigation_items as $item ) {
			if ( ! is_array( $item ) || empty( $item['text'] ) ) {
				continue;
			}

			$type = isset( $item['type'] ) ? (string) $item['type'] : 'link';

			if ( 'dropdown' === $type && ! empty( $item['items'] ) && is_array( $item['items'] ) ) {
				$sub_items = array();

				foreach ( $item['items'] as $sub_item ) {
					if ( ! is_array( $sub_item ) || empty( $sub_item['text'] ) || empty( $sub_item['href'] ) ) {
						continue;
					}

					$href = self::normalize_href( $sub_item['href'] );

					if ( ! self::section_is_visible( $href, $context ) ) {
						continue;
					}

					$sub_items[] = array(
						'text' => sanitize_text_field( $sub_item['text'] ),
						'href' => $href,
					);
				}

				if ( ! empty( $sub_items ) ) {
					$items[] = array(
						'text'  => sanitize_text_field( $item['text'] ),
						'type'  => 'dropdown',
						'items' => $sub_items,
					);
				}

				continue;
			}

			if ( 'link' !== $type || empty( $item['href'] ) ) {
				continue;
			}

			$href = self::normalize_href( $item['href'] );

			if ( ! self::section_is_visible( $href, $context ) ) {
				continue;
			}

			$items[] = array(
				'text' => sanitize_text_field( $item['text'] ),
				'type' => 'link',
				'href' => $href,
			);
		}

		if ( ! empty( $items ) ) {
			return $items;
		}

		$defaults = array(
			array(
				'href' => '#about',
				'text' => __( 'Overview', 'wpfaevent' ),
			),
			array(
				'href' => '#speakers',
				'text' => __( 'Speakers', 'wpfaevent' ),
			),
			array(
				'href' => '#schedule-overview',
				'text' => __( 'Schedule', 'wpfaevent' ),
			),
			array(
				'href' => '#venue',
				'text' => __( 'Additional Info', 'wpfaevent' ),
			),
		);

		if ( ! empty( $context['custom_sections'] ) && is_array( $context['custom_sections'] ) ) {
			foreach ( $context['custom_sections'] as $section_id => $section_label ) {
				$section_id = sanitize_title( $section_id );
				if ( '' === $section_id ) {
					continue;
				}

				$defaults[] = array(
					'href' => '#custom-section-' . $section_id,
					'text' => is_scalar( $section_label ) ? sanitize_text_field( $section_label ) : __( 'Event Info', 'wpfaevent' ),
				);
			}
		}

		$defaults[] = array(
			'href' => '#sponsors',
			'text' => __( 'Sponsors', 'wpfaevent' ),
		);
		$defaults[] = array(
			'href' => '#exhibitors',
			'text' => __( 'Exhibitors', 'wpfaevent' ),
		);

		foreach ( $defaults as $default_item ) {
			if ( self::section_is_visible( $default_item['href'], $context ) ) {
				$items[] = array(
					'text' => $default_item['text'],
					'type' => 'link',
					'href' => $default_item['href'],
				);
			}
		}

		return $items;
	}
}
