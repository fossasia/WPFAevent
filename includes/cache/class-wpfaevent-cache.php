<?php
/**
 * WPFA Cache Management
 *
 * Handles caching and cache invalidation for WPFA page lookups.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/cache
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache management class for WPFA templates.
 */
class Wpfaevent_Cache {

	/**
	 * Get Code of Conduct page ID with caching.
	 *
	 * @since    1.0.0
	 * @return   int    Page ID, or 0 if not found.
	 */
	public static function get_coc_page_id() {
		// Try to get from cache first
		$coc_page_id = wp_cache_get( 'wpfaevent_coc_page_id', 'wpfaevent' );

		// Cache MISS - query database
		if ( false === $coc_page_id ) {
			$coc_query = new WP_Query(
				array(
					'pagename'       => 'code-of-conduct',
					'post_type'      => 'page',
					'posts_per_page' => 1,
					'no_found_rows'  => true,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $coc_query->posts ) ) {
				$coc_page_id = (int) $coc_query->posts[0];
			} else {
				$coc_page_id = 0;
			}

			// Store in cache for 1 hour
			wp_cache_set( 'wpfaevent_coc_page_id', $coc_page_id, 'wpfaevent', HOUR_IN_SECONDS );
		}

		return $coc_page_id;
	}

	/**
	 * Clear cached WPFA page IDs when pages are saved or deleted.
	 *
	 * This ensures navigation links update immediately when page slugs
	 * are changed or pages are deleted, preventing stale cache issues.
	 *
	 * @since    1.0.0
	 * @param    int $post_id    The post ID being saved/deleted.
	 */
	public static function clear_page_cache( $post_id ) {
		// Only clear for pages (not posts, CPTs, etc.)
		if ( 'page' !== get_post_type( $post_id ) ) {
			return;
		}

		// Clear Code of Conduct page cache
		wp_cache_delete( 'wpfaevent_coc_page_id', 'wpfaevent' );

		// Future: Add other page caches here as templates are implemented
		// wp_cache_delete( 'wpfaevent_events_page_id', 'wpfaevent' );
		// wp_cache_delete( 'wpfaevent_speakers_page_id', 'wpfaevent' );
	}
}
