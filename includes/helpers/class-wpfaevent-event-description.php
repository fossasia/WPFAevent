<?php
/**
 * Event description helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and stores event descriptions in post_content.
 *
 * Imports, the WordPress editor and the frontend Edit Event modal all keep the
 * description in post_content. Earlier versions also copied it into
 * post_excerpt; those copies are ignored and cleared when the event is saved.
 */
class Wpfaevent_Event_Description {

	/**
	 * Get an event's description.
	 *
	 * Events created in the frontend modal before post_content became the
	 * description only have it in post_excerpt, so that is the fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_Post $event Event post ID or object.
	 * @return string Description HTML.
	 */
	public static function get( $event ) {
		$post = get_post( $event );

		if ( ! $post ) {
			return '';
		}

		$content = trim( (string) $post->post_content );

		return '' !== $content ? $content : trim( (string) $post->post_excerpt );
	}

	/**
	 * Check whether an excerpt only holds a copy of the description.
	 *
	 * Imports and the Edit Event modal used to store the description in the
	 * excerpt as well. A copy matches post_content as text, or is the only
	 * description for events whose post_content is empty. Any other excerpt
	 * is a summary someone wrote, and is kept.
	 *
	 * @since 1.0.0
	 *
	 * @param string $excerpt Saved post_excerpt.
	 * @param string $content Saved post_content.
	 * @return bool True when the excerpt can be cleared.
	 */
	public static function is_description_copy( $excerpt, $content ) {
		$excerpt_text = self::text( $excerpt );

		if ( '' === $excerpt_text ) {
			return false;
		}

		$content_text = self::text( $content );

		return '' === $content_text || $excerpt_text === $content_text;
	}

	/**
	 * Get the excerpt to save with a new description.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID, or 0 for a new event.
	 * @return string|null Empty string to clear a copied excerpt, or null to leave it alone.
	 */
	public static function excerpt_to_save( $event_id ) {
		$post = $event_id ? get_post( $event_id ) : null;

		if ( ! $post ) {
			return null;
		}

		return self::is_description_copy( $post->post_excerpt, $post->post_content ) ? '' : null;
	}

	/**
	 * Get the visible text of a description, with whitespace collapsed.
	 *
	 * Comparing text instead of markup treats a description re-saved by an
	 * editor (different line breaks or paragraph tags) as the same description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Description HTML.
	 * @return string Plain text.
	 */
	private static function text( $html ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );

		return trim( (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $text ) );
	}
}
