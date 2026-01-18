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
	// Apply standard WordPress content filters.
	$processed_content = apply_filters( 'the_content', $content );

	// For non-block content, ensure paragraphs are added locally without
	// changing global filters. Block content (with <!-- wp: markers)
	// should generally not be run through wpautop.
	if ( false === strpos( $processed_content, '<!-- wp:' ) && false === strpos( $processed_content, '<!-- /wp:' ) ) {
		$processed_content = wpautop( $processed_content );
	}

	// Display page content safely.
	echo wp_kses_post( $processed_content );
} else {
	// Display default content with filter for customization
	$default_content = apply_filters(
		'wpfa_coc_default_content',
		__( 'We are committed to a welcoming, inclusive community. Be respectful.', 'wpfaevent' )
	);
	echo '<p>' . esc_html( $default_content ) . '</p>';
}
