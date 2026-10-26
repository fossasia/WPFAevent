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
	 * Check whether an excerpt only repeats a description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $excerpt     Excerpt HTML.
	 * @param string $description Description HTML.
	 * @return bool True when both have the same visible text.
	 */
	public static function is_description_copy( $excerpt, $description ) {
		$excerpt_text = self::text( $excerpt );

		return '' !== $excerpt_text && self::text( $description ) === $excerpt_text;
	}

	/**
	 * Clear an event excerpt that only copies the description.
	 *
	 * Imports and the Edit Event modal used to store the description in the
	 * excerpt as well. Running on every save covers imports, the modal, the
	 * WordPress editor and REST alike. An excerpt matching the saved or the
	 * incoming description is cleared; any other excerpt is a summary someone
	 * wrote, and is kept.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data    Slashed post data about to be saved.
	 * @param array<string, mixed> $postarr Slashed post data passed to wp_insert_post().
	 * @return array<string, mixed> Post data.
	 */
	public static function clear_copied_excerpt( $data, $postarr ) {
		if ( ! isset( $data['post_type'], $data['post_excerpt'] ) || 'wpfa_event' !== $data['post_type'] ) {
			return $data;
		}

		$excerpt      = wp_unslash( (string) $data['post_excerpt'] );
		$descriptions = array( wp_unslash( isset( $data['post_content'] ) ? (string) $data['post_content'] : '' ) );
		$saved        = ! empty( $postarr['ID'] ) ? get_post( absint( $postarr['ID'] ) ) : null;

		if ( $saved ) {
			$descriptions[] = $saved->post_content;
		}

		foreach ( $descriptions as $description ) {
			if ( self::is_description_copy( $excerpt, $description ) ) {
				$data['post_excerpt'] = '';
				break;
			}
		}

		return $data;
	}

	/**
	 * Get the visible text of a description, with whitespace collapsed.
	 *
	 * Tags count as word breaks, so a description re-saved by an editor (with
	 * different line breaks or paragraph tags) still reads as the same text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Description HTML.
	 * @return string Plain text.
	 */
	private static function text( $html ) {
		$text = wp_strip_all_tags( str_replace( '<', ' <', (string) $html ) );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		return trim( (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $text ) );
	}
}
