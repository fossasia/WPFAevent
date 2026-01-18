<?php
/**
 * Template Name: WPFA - Code of Conduct
 *
 * Displays the Code of Conduct page for FOSSASIA events.
 *
 * Content Handling:
 * - If the page has content, displays the page content with proper
 *   WordPress content filters applied (wpautop, shortcodes, etc.)
 * - If the page is empty, displays a default Code of Conduct message
 *
 * This allows site administrators to manage the Code of Conduct
 * content through the WordPress editor while providing a sensible
 * default for MVP parity.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_logo_url = get_option( 'wpfa_site_logo_url', WPFAEVENT_URL . 'assets/images/logo.png' );
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );
if ( have_posts() ) {
	the_post();
	$content = trim( get_the_content() );
} else {
	$content = '';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
	// Load shared navigation partial
	$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
	if ( file_exists( $nav_partial ) ) {
		include $nav_partial;
	}
	?>

	<main>
		<header class="page-hero">
			<h1><?php echo esc_html( get_the_title() ); ?></h1>
			<p><?php echo esc_html( apply_filters( 'wpfa_coc_hero_text', __( 'Our commitment to a safe, respectful, and harassment-free event experience for everyone.', 'wpfaevent' ) ) ); ?></p>
		</header>

		<div class="container">
			<div class="main-content">
				<?php
				// Load content partial
				$content_partial = WPFAEVENT_PATH . 'public/partials/code-of-conduct/content.php';
				if ( file_exists( $content_partial ) ) {
					include $content_partial;
				}
				?>
			</div>
		</div>
	</main>
</div>

<?php wp_footer(); ?>
</body>
</html>