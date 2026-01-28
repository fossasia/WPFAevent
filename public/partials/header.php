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

// Get Code of Conduct page ID (with caching handled by cache class)
$coc_page_id = Wpfaevent_Cache::get_coc_page_id();

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