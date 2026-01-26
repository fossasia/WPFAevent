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

// Default values
$site_logo_url        = isset( $site_logo_url ) ? $site_logo_url : WPFAEVENT_URL . 'assets/images/logo.png';
$event_page_url       = isset( $event_page_url ) ? $event_page_url : home_url( '/events/' );
$show_back_button     = isset( $show_back_button ) ? $show_back_button : false;
$show_register_button = isset( $show_register_button ) ? $show_register_button : false;
$back_button_text     = isset( $back_button_text ) ? $back_button_text : __( 'Back to Event', 'wpfaevent' );
$register_button_url  = isset( $register_button_url ) ? $register_button_url : 'https://eventyay.com/e/4c0e0c27';
$register_button_text = isset( $register_button_text ) ? $register_button_text : __( 'Register', 'wpfaevent' );

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
			<div class="nav-links-main">
				<a href="<?php echo esc_url( $events_url ); ?>"><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></a>
				<a href="<?php echo esc_url( $past_events_url ); ?>"><?php esc_html_e( 'Past Events', 'wpfaevent' ); ?></a>
				<?php if ( $coc_page_id ) : ?>
					<a href="<?php echo esc_url( get_permalink( $coc_page_id ) ); ?>" class="<?php echo esc_attr( $is_current ); ?>"><?php esc_html_e( 'Code of Conduct', 'wpfaevent' ); ?></a>
				<?php endif; ?>
			</div>
			
			<?php if ( $show_back_button || $show_register_button ) : ?>
			<div class="nav-links-secondary">
				<?php if ( $show_back_button ) : ?>
					<a href="<?php echo esc_url( $event_page_url ); ?>" class="btn btn-secondary">
						<?php echo esc_html( $back_button_text ); ?>
					</a>
				<?php endif; ?>
				
				<?php if ( $show_register_button ) : ?>
					<a href="<?php echo esc_url( $register_button_url ); ?>" target="_blank" rel="noopener" class="btn btn-primary">
						<?php echo esc_html( $register_button_text ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</nav>
	</div>
</header>
