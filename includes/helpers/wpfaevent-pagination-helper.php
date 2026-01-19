<?php
/**
 * Pagination Helper Functions
 *
 * Reusable pagination rendering utilities for WPFA event templates.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wpfa_render_pagination' ) ) {
	/**
	 * Render pagination navigation for WPFA templates.
	 *
	 * Outputs accessible pagination with current page highlighted,
	 * proper ARIA attributes, and optional query parameter preservation.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $total_pages  Total number of pages.
	 * @param int    $current_page Current page number.
	 * @param string $aria_label   Accessible label for navigation. Default 'Pagination'.
	 * @param array  $query_args   Optional. Additional query args to preserve (e.g., search term).
	 *                             Default empty array.
	 *
	 * @return void Outputs HTML directly.
	 */
	function wpfa_render_pagination( $total_pages, $current_page, $aria_label = 'Pagination', $query_args = array() ) {
		// Bail if only one page
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url = get_permalink();

		echo '<nav class="wpfa-pagination" aria-label="' . esc_attr( $aria_label ) . '">';

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			// Merge page number with any additional query args
			$args = array_merge( $query_args, array( 'paged' => $i ) );
			$link = esc_url( add_query_arg( $args, $base_url ) );

			// Current page as span with aria-current, others as links
			if ( $i === $current_page ) {
				printf(
					'<span class="wpfa-page is-current" aria-current="page">%d</span>',
					$i
				);
			} else {
				printf(
					'<a class="wpfa-page" href="%s">%d</a>',
					$link,
					$i
				);
			}
		}

		echo '</nav>';
	}
}
