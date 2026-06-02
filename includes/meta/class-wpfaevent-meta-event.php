<?php
/**
 * Registers custom meta fields for Event CPT.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/meta
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Meta_Event.
 *
 * Registers meta fields for the Event CPT.
 */
class Wpfaevent_Meta_Event {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	private static $post_type = 'wpfa_event';

	/**
	 * Registers all event meta fields.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		// Event date fields.
		register_post_meta(
			self::$post_type,
			'wpfa_event_start_date',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event start date', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_end_date',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event end date', 'wpfaevent' ),
			)
		);

		// Event location.
		register_post_meta(
			self::$post_type,
			'wpfa_event_location',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event venue/location', 'wpfaevent' ),
			)
		);

		// Event external URL.
		register_post_meta(
			self::$post_type,
			'wpfa_event_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'External event link (Eventyay, etc.)', 'wpfaevent' ),
			)
		);

		// Event speakers as an array of speaker IDs.
		register_post_meta(
			self::$post_type,
			'wpfa_event_speakers',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_speaker_ids' ),
				'description'       => __( 'Related speaker post IDs', 'wpfaevent' ),
			)
		);
	}

	/**
	 * Register the Event Details meta box.
	 *
	 * @since 1.0.0
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'wpfa_event_details',
			__( 'Event Details', 'wpfaevent' ),
			array( __CLASS__, 'render_meta_box' ),
			self::$post_type,
			'normal',
			'high'
		);

		remove_meta_box( 'postcustom', self::$post_type, 'normal' );
	}

	/**
	 * Render the Event Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Event post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'wpfa_event_meta_nonce', 'wpfa_event_meta_nonce' );

		$start_date = get_post_meta( $post->ID, 'wpfa_event_start_date', true );
		$end_date   = get_post_meta( $post->ID, 'wpfa_event_end_date', true );
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$speakers   = self::get_admin_event_speaker_ids( $post->ID );
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_event_start_date"><?php esc_html_e( 'Start Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_start_date" name="wpfa_event_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_date"><?php esc_html_e( 'End Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_end_date" name="wpfa_event_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_location"><?php esc_html_e( 'Location', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_location" name="wpfa_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_url"><?php esc_html_e( 'Event URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_url" name="wpfa_event_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_speakers"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
						$all_speaker_ids     = get_posts(
							array(
								'post_type'      => 'wpfa_speaker',
								'posts_per_page' => -1,
								'orderby'        => 'title',
								'order'          => 'ASC',
								'fields'         => 'ids',
								'no_found_rows'  => true,
							)
						);
					$other_event_speaker_ids = array_diff( self::get_all_event_owned_speaker_ids(), $speakers );
					$speaker_ids             = array_diff( $all_speaker_ids, $other_event_speaker_ids );
					$speaker_ids             = self::sanitize_post_id_list( array_merge( $speakers, $speaker_ids ) );
					usort(
						$speaker_ids,
						static function ( $speaker_a, $speaker_b ) {
							return strcasecmp( get_the_title( $speaker_a ), get_the_title( $speaker_b ) );
						}
					);

					if ( $speaker_ids ) :
						?>
						<select name="wpfa_event_speakers[]" id="wpfa_event_speakers" multiple class="wpfaevent-speakers-select">
							<?php foreach ( $speaker_ids as $speaker_id ) : ?>
								<option value="<?php echo esc_attr( $speaker_id ); ?>" <?php selected( in_array( $speaker_id, $speakers, true ) ); ?>>
									<?php echo esc_html( get_the_title( $speaker_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Only site speakers and speakers already linked to this event are shown.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No speakers found. Create speakers first.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Event Details meta box data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Event post ID.
	 */
	public static function save_meta( $post_id ) {
		$event_nonce = isset( $_POST['wpfa_event_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_meta_nonce'] ) ) : '';

		if ( ! $event_nonce || ! wp_verify_nonce( $event_nonce, 'wpfa_event_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpfa_event_start_date'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_start_date', sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_date'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_end_date'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_end_date', sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_date'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_location'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_location', sanitize_text_field( wp_unslash( $_POST['wpfa_event_location'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_url'] ) ) );
		}

		$previous_speakers = self::get_event_speaker_ids( $post_id );
		$speakers          = array();

		if ( isset( $_POST['wpfa_event_speakers'] ) && is_array( $_POST['wpfa_event_speakers'] ) ) {
			$speakers = self::sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_event_speakers'] )
				)
			);
		}

		if ( ! empty( $speakers ) ) {
			update_post_meta( $post_id, 'wpfa_event_speakers', $speakers );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_speakers' );
		}

		self::sync_event_speaker_relationships( $post_id, $previous_speakers, $speakers );
	}

	/**
	 * Get normalized speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_event_speaker_ids( $event_id ) {
		return self::sanitize_post_id_list( get_post_meta( $event_id, 'wpfa_event_speakers', true ) );
	}

	/**
	 * Get speakers assigned to one event from event and reverse speaker meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_admin_event_speaker_ids( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id || get_post_type( $event_id ) !== self::$post_type ) {
			return array();
		}

		return self::sanitize_post_id_list(
			array_merge(
				self::get_event_speaker_ids( $event_id ),
				Wpfaevent_Meta_Speaker::get_speakers_linked_to_event( $event_id )
			)
		);
	}

	/**
	 * Get every speaker owned by any event.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public static function get_all_event_owned_speaker_ids() {
		$speaker_ids = array();
		$event_ids   = get_posts(
			array(
				'post_type'      => self::$post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $event_ids as $event_id ) {
			$speaker_ids = array_merge( $speaker_ids, self::get_event_speaker_ids( $event_id ) );
		}

		return self::sanitize_post_id_list( array_merge( $speaker_ids, Wpfaevent_Meta_Speaker::get_all_speakers_linked_to_events() ) );
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int>
	 */
	public static function sanitize_post_id_list( $post_ids ) {
		if ( is_array( $post_ids ) ) {
			$normalized_post_ids = $post_ids;
		} elseif ( is_scalar( $post_ids ) ) {
			if ( is_string( $post_ids ) ) {
				$post_ids = trim( $post_ids );
			}

			if ( '' === $post_ids ) {
				return array();
			}

			$decoded_post_ids = is_string( $post_ids ) ? json_decode( $post_ids, true ) : null;

			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded_post_ids ) ) {
				$normalized_post_ids = $decoded_post_ids;
			} elseif ( JSON_ERROR_NONE === json_last_error() && is_scalar( $decoded_post_ids ) ) {
				$normalized_post_ids = array( $decoded_post_ids );
			} elseif ( is_string( $post_ids ) && false !== strpos( $post_ids, ',' ) ) {
				$normalized_post_ids = array_map( 'trim', explode( ',', $post_ids ) );
			} else {
				$normalized_post_ids = array( $post_ids );
			}
		} else {
			return array();
		}

		$post_ids = array_map( 'absint', $normalized_post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Sync speaker-side event relationship meta after an event is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $event_id          Event post ID.
	 * @param array<int> $previous_speakers Speaker IDs before save.
	 * @param array<int> $current_speakers  Speaker IDs after save.
	 */
	private static function sync_event_speaker_relationships( $event_id, $previous_speakers, $current_speakers ) {
		$event_id          = absint( $event_id );
		$previous_speakers = self::sanitize_post_id_list( $previous_speakers );
		$current_speakers  = self::sanitize_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		if ( empty( $previous_speakers ) ) {
			$previous_speakers = Wpfaevent_Meta_Speaker::get_speakers_linked_to_event( $event_id );
		}

		foreach ( array_diff( $previous_speakers, $current_speakers ) as $speaker_id ) {
			Wpfaevent_Meta_Speaker::remove_event_from_speaker( $speaker_id, $event_id );
		}

		foreach ( $current_speakers as $speaker_id ) {
			Wpfaevent_Meta_Speaker::add_event_to_speaker( $speaker_id, $event_id );
		}
	}

	/**
	 * Sanitizes an array of speaker IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $speaker_ids Array of speaker post IDs.
	 * @return array Sanitized array of integers.
	 */
	public static function sanitize_speaker_ids( $speaker_ids ) {
		if ( ! is_array( $speaker_ids ) ) {
			return array();
		}

		return array_map( 'absint', $speaker_ids );
	}
}
