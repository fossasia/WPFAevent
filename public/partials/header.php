<?php
/**
 * Shared Navigation Header Partial
 * Displays the navigation header used across WPFA templates.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Site logo should be available from parent scope
$site_logo_url = isset( $site_logo_url ) ? $site_logo_url : WPFAEVENT_URL . 'assets/images/logo.png';

// Get Code of Conduct page ID (with caching)
$coc_page_id = wp_cache_get( 'wpfaevent_coc_page_id', 'wpfaevent' );

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

	wp_cache_set( 'wpfaevent_coc_page_id', $coc_page_id, 'wpfaevent', HOUR_IN_SECONDS );
}

// Determine if current page is Code of Conduct
$is_current = ( $coc_page_id && is_page( $coc_page_id ) ) ? 'active' : '';

// Allow customization of events URLs via filters while keeping current behavior as default.
$events_url      = apply_filters( 'wpfaevent_events_url', home_url( '/events/' ) );
$past_events_url = apply_filters( 'wpfaevent_past_events_url', home_url( '/past-events/' ) );
?>

<header class="nav" role="banner">
	<div class="container nav-inner">
		<a href="<?php echo esc_url( $events_url ); ?>">
			<img src="<?php echo esc_url( $site_logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="site-logo">
		</a>
		<nav class="nav-links" role="navigation" aria-label="<?php esc_attr_e( 'Primary', 'wpfaevent' ); ?>">
			<a href="<?php echo esc_url( $events_url ); ?>"><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></a>
			<a href="<?php echo esc_url( $past_events_url ); ?>"><?php esc_html_e( 'Past Events', 'wpfaevent' ); ?></a>
			<?php if ( $coc_page_id ) : ?>
				<a href="<?php echo esc_url( get_permalink( $coc_page_id ) ); ?>" class="<?php echo esc_attr( $is_current ); ?>"><?php esc_html_e( 'Code of Conduct', 'wpfaevent' ); ?></a>
			<?php endif; ?>
		</nav>
	</div>
</header>