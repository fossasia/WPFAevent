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
	exit; }
get_header();

/**
 * Option A: show current Page content; editors can manage it normally.
 * Option B: if empty, display a default snippet for MVP parity.
 */
the_post();
$content = trim( get_the_content() );
?>
<main class="wpfa-coc">
	<h1><?php echo esc_html( get_the_title() ); ?></h1>
	<div class="entry">
		<?php
		if ( $content ) {
			echo wp_kses_post( apply_filters( 'the_content', $content ) );
		} else {
			echo wp_kses_post( '<p>' . __( 'We are committed to a welcoming, inclusive community. Be respectful.', 'wpfaevent' ) . '</p>' );
		}
		?>
	</div>
</main>
<?php get_footer(); ?>
