<?php

/**
 * News Feed Helper Functions
 *
 * Functions for fetching and displaying news from external RSS feeds.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 * @since      1.0.0
 */

/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure WordPress feed functions are available.
 *
 * @since 1.0.0
 * @return void
 */
function wpfa_ensure_feed_loaded() {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}
}

/**
 * Fetches and renders latest blog posts from FOSSASIA blog.
 *
 * Features:
 * - Fetches RSS feed from https://blog.fossasia.org/rss/
 * - Caches results for performance (default: 1 hour)
 * - Provides fallback content if feed is unavailable
 * - Outputs HTML directly (echos content)
 * - Includes "Visit Blog" call-to-action button
 *
 * @since 1.0.0
 * @return void Outputs HTML directly
 */
function wpfa_render_latest_news() {
	wpfa_ensure_feed_loaded();

	// Check for cached news
	$cached_news = get_transient( 'wpfa_latest_news' );

	if ( false !== $cached_news ) {
		echo $cached_news;
		return;
	}

	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed( 'https://blog.fossasia.org/rss/' );

	if ( is_wp_error( $rss ) ) {

		// Show fallback news
		wpfa_render_fallback_news();
		return;
	}

	// Figure out how many total items there are, but limit it to 5.
	$maxitems = $rss->get_item_quantity( 5 );

	// Build an array of all the items, starting with element 0 (first element).
	$rss_items = $rss->get_items( 0, $maxitems );

	ob_start();

	if ( $maxitems == 0 ) {
		echo '<p>' . esc_html__( 'No news items found.', 'wpfaevent' ) . '</p>';
	} else {
		// Loop through each feed item and display each item as a hyperlink.
		echo '<ul class="news-list">';
		foreach ( $rss_items as $item ) :
			$permalink = esc_url( $item->get_permalink() );
			$title     = esc_html( $item->get_title() );
			$date      = esc_html( $item->get_date( 'F j, Y' ) );
			$full_date = esc_attr( sprintf( __( 'Posted %s', 'wpfaevent' ), $item->get_date( 'F j, Y' ) ) );
			?>
			<li class="news-item">
				<a href="<?php echo $permalink; ?>"
					title="<?php echo $full_date; ?>"
					target="_blank"
					rel="noopener noreferrer">
					<?php echo $title; ?>
				</a>
				<small class="news-date"><?php echo $date; ?></small>
			</li>
			<?php
		endforeach;
		echo '</ul>';

		// Add "Visit Blog" CTA
		echo '<div class="news-cta">';
		echo '<a href="https://blog.fossasia.org/" target="_blank" rel="noopener noreferrer" class="btn btn-primary">';
		echo esc_html__( 'Visit Blog →', 'wpfaevent' );
		echo '</a>';
		echo '</div>';
	}

	$news_html = ob_get_clean();

	// Cache for 1 hour to reduce load
	set_transient( 'wpfa_latest_news', $news_html, HOUR_IN_SECONDS );

	echo $news_html;
}

/**
 * Render fallback news content when RSS feed is not available.
 *
 * @since 1.0.0
 * @return void Outputs HTML directly
 */
function wpfa_render_fallback_news() {
	ob_start();
	?>
	<ul class="news-list">
		<li class="news-item">
			<a href="https://blog.fossasia.org/fossasia-summit-2025-announced/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'FOSSASIA Summit 2025 Announced', 'wpfaevent' ); ?>
			</a>
			<small class="news-date"><?php esc_html_e( 'January 15, 2025', 'wpfaevent' ); ?></small>
		</li>
		<li class="news-item">
			<a href="https://blog.fossasia.org/open-source-community-grows/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open Source Community Grows', 'wpfaevent' ); ?>
			</a>
			<small class="news-date"><?php esc_html_e( 'January 10, 2025', 'wpfaevent' ); ?></small>
		</li>
		<li class="news-item">
			<a href="https://blog.fossasia.org/new-eventyay-features/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'New Eventyay Features Released', 'wpfaevent' ); ?>
			</a>
			<small class="news-date"><?php esc_html_e( 'January 5, 2025', 'wpfaevent' ); ?></small>
		</li>
		<li class="news-item">
			<a href="https://blog.fossasia.org/developer-tools-update/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Developer Tools Update', 'wpfaevent' ); ?>
			</a>
			<small class="news-date"><?php esc_html_e( 'December 28, 2024', 'wpfaevent' ); ?></small>
		</li>
		<li class="news-item">
			<a href="https://blog.fossasia.org/asia-open-source-conference/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Asia Open Source Conference Recap', 'wpfaevent' ); ?>
			</a>
			<small class="news-date"><?php esc_html_e( 'December 20, 2024', 'wpfaevent' ); ?></small>
		</li>
	</ul>
	<div class="news-cta">
		<a href="https://blog.fossasia.org/" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
			<?php esc_html_e( 'Visit Blog →', 'wpfaevent' ); ?>
		</a>
	</div>
	<?php

	$fallback_html = ob_get_clean();

	/**
	 * Filter the cache duration for fallback news.
	 *
	 * @since 1.0.0
	 * @param int $duration Cache duration in seconds.
	 */
	$fallback_cache_duration = apply_filters( 'wpfa_fallback_news_cache_duration', 4 * HOUR_IN_SECONDS );
	set_transient( 'wpfa_latest_news', $fallback_html, $fallback_cache_duration );

	echo $fallback_html;
}

/**
 * Clear the news cache (useful for debugging).
 *
 * @since 1.0.0
 * @return bool True on success, false on failure.
 */
function wpfa_clear_news_cache() {
	return delete_transient( 'wpfa_latest_news' );
}

/**
 * Test if RSS feed is working.
 *
 * Primarily used for debugging and admin testing. Returns an array
 * with success status, message, and fetched items (if any).
 *
 * @since 1.0.0
 * @return array {
 *     Test results.
 *
 *     @type bool   $success Whether the feed was fetched successfully.
 *     @type string $message Human-readable status message.
 * }
 */
function wpfa_test_news_feed() {
	wpfa_ensure_feed_loaded();

	$rss = fetch_feed( 'https://blog.fossasia.org/rss/' );

	if ( is_wp_error( $rss ) ) {
		return array(
			'success' => false,
			'message' => $rss->get_error_message(),
		);
	}

	$item_count = $rss->get_item_quantity( 1 );
	$items      = $rss->get_items( 0, 1 );

	return array(
		'success' => true,
		'message' => sprintf(
			/* translators: %d: Number of news items fetched from the RSS feed */
			__( 'Feed loaded successfully. Found %d items.', 'wpfaevent' ),
			$item_count
		),
	);
}