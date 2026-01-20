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
	 * Previous/Next links, ellipsis for large page ranges, and
	 * proper ARIA attributes for accessibility.
	 *
	 * Pattern for large ranges: ← Previous 1 2 3 ... 10 11 12 Next →
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

		// Previous page link
		if ( $current_page > 1 ) {
			$prev_args = array_merge( $query_args, array( 'paged' => $current_page - 1 ) );
			$prev_link = esc_url( add_query_arg( $prev_args, $base_url ) );
			printf(
				'<a class="wpfa-page wpfa-page-prev" href="%s" rel="prev" aria-label="%s">%s</a>',
				$prev_link,
				esc_attr__( 'Previous page', 'wpfaevent' ),
				esc_html__( 'Previous', 'wpfaevent' )
			);
		} else {
			printf(
				'<span class="wpfa-page wpfa-page-prev is-disabled" aria-disabled="true">%s</span>',
				esc_html__( 'Previous', 'wpfaevent' )
			);
		}

		// Page number links with ellipsis for large ranges
		$range                = 2; // Number of pages to show on each side of current
		$ellipsis_shown_left  = false;
		$ellipsis_shown_right = false;

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			// Always show first page, last page, and pages within range of current
			$in_range = ( $i >= ( $current_page - $range ) && $i <= ( $current_page + $range ) );
			$is_edge  = ( 1 === $i || $total_pages === $i );

			if ( $is_edge || $in_range ) {
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
			} elseif ( $i < $current_page && ! $ellipsis_shown_left ) {
				// Left-side ellipsis (between first page and current range)
				echo '<span class="wpfa-page wpfa-page-ellipsis" aria-hidden="true">…</span>';
				$ellipsis_shown_left = true;
			} elseif ( $i > $current_page && ! $ellipsis_shown_right ) {
				// Right-side ellipsis (between current range and last page)
				echo '<span class="wpfa-page wpfa-page-ellipsis" aria-hidden="true">…</span>';
				$ellipsis_shown_right = true;
			}
		}

		// Next page link
		if ( $current_page < $total_pages ) {
			$next_args = array_merge( $query_args, array( 'paged' => $current_page + 1 ) );
			$next_link = esc_url( add_query_arg( $next_args, $base_url ) );
			printf(
				'<a class="wpfa-page wpfa-page-next" href="%s" rel="next" aria-label="%s">%s</a>',
				$next_link,
				esc_attr__( 'Next page', 'wpfaevent' ),
				esc_html__( 'Next', 'wpfaevent' )
			);
		} else {
			printf(
				'<span class="wpfa-page wpfa-page-next is-disabled" aria-disabled="true">%s</span>',
				esc_html__( 'Next', 'wpfaevent' )
			);
		}

		echo '</nav>';
	}
}
