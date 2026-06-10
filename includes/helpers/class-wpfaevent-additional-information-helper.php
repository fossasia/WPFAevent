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
	 * Get the public additional information page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_additional_information_page_url() {
		$page = get_page_by_path( 'additional-information' );

		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}

		$pages = get_pages(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Managed page lookup by assigned template.
				'meta_key'    => '_wp_page_template',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Managed page lookup by assigned template.
				'meta_value'  => 'page-additional-information.php',
				'number'      => 1,
				'post_status' => 'publish',
			)
		);

		if ( empty( $pages ) ) {
			$pages = get_pages(
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Back-compat managed page lookup by assigned template.
					'meta_key'    => '_wp_page_template',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Back-compat managed page lookup by assigned template.
					'meta_value'  => 'public/partials/additional-information-page.php',
					'number'      => 1,
					'post_status' => 'publish',
				)
			);
		}

		$url = ! empty( $pages[0] ) ? get_permalink( $pages[0] ) : home_url( '/additional-information/' );

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
		if ( $check_capability && ! Wpfaevent_Roles::current_user_can_manage_settings() ) {
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
}
