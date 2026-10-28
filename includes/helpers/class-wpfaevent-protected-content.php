<?php
/**
 * Password-protected event and speaker content helpers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep protected event and speaker text hidden until the post is unlocked.
 */
class Wpfaevent_Protected_Content {

	/**
	 * Meta keys holding protected text, by post type.
	 *
	 * Mirrors core, which protects post content and excerpt but not the title or date.
	 *
	 * @since 1.0.0
	 * @var array<string, array<int, string>>
	 */
	const PROTECTED_META = array(
		'wpfa_event'   => array( 'wpfa_event_lead_text' ),
		'wpfa_speaker' => array( 'wpfa_speaker_bio', 'wpfa_speaker_talk_abstract' ),
	);

	/**
	 * Whether the current visitor must not see a post's protected text.
	 *
	 * Users who can edit the post are exempt so the front-end edit modals keep working.
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_Post $post Post ID or object.
	 * @return bool
	 */
	public static function is_locked( $post ) {
		$post = get_post( $post );

		if ( ! $post || '' === $post->post_password ) {
			return false;
		}

		return post_password_required( $post ) && ! current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Blank protected meta in REST responses until the post password is supplied.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response REST response.
	 * @param WP_Post          $post     Post being prepared.
	 * @param WP_REST_Request  $request  Current request.
	 * @return WP_REST_Response
	 */
	public static function filter_rest_response( $response, $post, $request ) {
		if ( ! self::is_locked( $post ) ) {
			return $response;
		}

		$password = (string) $request->get_param( 'password' );
		if ( '' !== $password && hash_equals( $post->post_password, $password ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( empty( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
			return $response;
		}

		$keys = isset( self::PROTECTED_META[ $post->post_type ] ) ? self::PROTECTED_META[ $post->post_type ] : array();
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $data['meta'] ) ) {
				$data['meta'][ $key ] = '';
			}
		}

		$response->set_data( $data );

		return $response;
	}
}
