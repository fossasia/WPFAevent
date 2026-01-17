<?php
/**
 * Code of Conduct Template - Header Partial
 *
 * Displays the navigation header for the Code of Conduct page.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Site logo should be available from parent scope
$site_logo_url = isset( $site_logo_url ) ? $site_logo_url : plugins_url( 'assets/images/logo.png', dirname( dirname( dirname( __FILE__ ) ) ) );
?>

<header class="nav" role="banner">
	<div class="container nav-inner">
		<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
			<img src="<?php echo esc_url( $site_logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="site-logo">
		</a>
		<nav class="nav-links" role="navigation" aria-label="<?php esc_attr_e( 'Primary', 'wpfaevent' ); ?>">
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'Upcoming Events', 'wpfaevent' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>"><?php esc_html_e( 'Past Events', 'wpfaevent' ); ?></a>
			<?php
			$coc_page = get_page_by_path( 'code-of-conduct' );
			if ( $coc_page ) :
				?>
				<a href="<?php echo esc_url( get_permalink( $coc_page ) ); ?>" style="background: #00000006;"><?php esc_html_e( 'Code of Conduct', 'wpfaevent' ); ?></a>
			<?php endif; ?>
		</nav>
	</div>
</header>