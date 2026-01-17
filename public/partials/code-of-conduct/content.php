<?php
/**
 * Code of Conduct Template - Content Partial
 *
 * Displays the main content for the Code of Conduct page.
 * If the page has editor content, it displays that.
 * If empty, displays the default MVP message.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials/code-of-conduct
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Content should be available from parent scope
$content = isset( $content ) ? $content : '';

if ( $content ) {
	// Remove wpautop to prevent wrapping block elements in <p> tags
	remove_filter( 'the_content', 'wpautop' );

	// Display page content with WordPress filters
	echo wp_kses_post( apply_filters( 'the_content', $content ) );

	// Re-add wpautop for other content
	add_filter( 'the_content', 'wpautop' );
} else {
	// Display default content with filter for customization
	$default_content = apply_filters(
		'wpfa_coc_default_content',
		__( 'We are committed to a welcoming, inclusive community. Be respectful.', 'wpfaevent' )
	);
	echo '<p>' . esc_html( $default_content ) . '</p>';
}
