<?php
/**
 * Registers custom meta fields for Speaker CPT.
 *
 * @package    WPFAevent
 * @subpackage WPFAevent/includes/meta
 * @author     FOSSASIA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Wpfaevent_Meta_Speaker.
 *
 * Registers meta fields for the Speaker CPT.
 */
class Wpfaevent_Meta_Speaker {

	/**
	 * The custom post type key.
	 *
	 * @var string
	 */
	private static $post_type = 'wpfa_speaker';

	/**
	 * Registers all speaker meta fields.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		$string_meta_fields = array(
			'wpfa_speaker_position'      => array(
				'description'       => __( 'Speaker position/title', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_organization'  => array(
				'description'       => __( 'Speaker organization', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_bio'           => array(
				'description'       => __( 'Speaker biography', 'wpfaevent' ),
				'sanitize_callback' => 'wp_kses_post',
			),
			'wpfa_speaker_headshot_url'  => array(
				'description'       => __( 'Speaker headshot image URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_linkedin'      => array(
				'description'       => __( 'Speaker LinkedIn URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_twitter'       => array(
				'description'       => __( 'Speaker Twitter URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_github'        => array(
				'description'       => __( 'Speaker GitHub URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_website'       => array(
				'description'       => __( 'Speaker website URL', 'wpfaevent' ),
				'sanitize_callback' => 'esc_url_raw',
			),
			'wpfa_speaker_talk_title'    => array(
				'description'       => __( 'Speaker session title', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_date'     => array(
				'description'       => __( 'Speaker session date', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_time'     => array(
				'description'       => __( 'Speaker session start time', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_end_time' => array(
				'description'       => __( 'Speaker session end time', 'wpfaevent' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'wpfa_speaker_talk_abstract' => array(
				'description'       => __( 'Speaker session abstract', 'wpfaevent' ),
				'sanitize_callback' => 'wp_kses_post',
			),
		);

		foreach ( $string_meta_fields as $meta_key => $args ) {
			self::register_string_meta( $meta_key, $args['description'], $args['sanitize_callback'] );
		}

		// Related events for the bidirectional event-speaker relationship.
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_events',
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
				'sanitize_callback' => array( __CLASS__, 'sanitize_event_ids' ),
				'description'       => __( 'Related event post IDs', 'wpfaevent' ),
			)
		);
	}

	/**
	 * Register the Speaker Details meta box.
	 *
	 * @since 1.0.0
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'wpfa_speaker_details',
			__( 'Speaker Details', 'wpfaevent' ),
			array( __CLASS__, 'render_meta_box' ),
			self::$post_type,
			'normal',
			'high'
		);

		remove_meta_box( 'postcustom', self::$post_type, 'normal' );
	}

	/**
	 * Render the Speaker Details meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Speaker post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'wpfa_speaker_meta_nonce', 'wpfa_speaker_meta_nonce' );

		$position     = get_post_meta( $post->ID, 'wpfa_speaker_position', true );
		$organization = get_post_meta( $post->ID, 'wpfa_speaker_organization', true );
		$bio          = get_post_meta( $post->ID, 'wpfa_speaker_bio', true );
		$headshot_url = get_post_meta( $post->ID, 'wpfa_speaker_headshot_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_speaker_position"><?php esc_html_e( 'Position/Title', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_position" name="wpfa_speaker_position" value="<?php echo esc_attr( $position ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_organization"><?php esc_html_e( 'Organization', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_organization" name="wpfa_speaker_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_bio"><?php esc_html_e( 'Biography', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					wp_editor(
						$bio,
						'wpfa_speaker_bio',
						array(
							'textarea_name' => 'wpfa_speaker_bio',
							'textarea_rows' => 10,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_headshot_url"><?php esc_html_e( 'Headshot URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_speaker_headshot_url" name="wpfa_speaker_headshot_url" value="<?php echo esc_attr( $headshot_url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Speaker Details meta box data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Speaker post ID.
	 */
	public static function save_meta( $post_id ) {
		$speaker_nonce = isset( $_POST['wpfa_speaker_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_meta_nonce'] ) ) : '';

		if ( ! $speaker_nonce || ! wp_verify_nonce( $speaker_nonce, 'wpfa_speaker_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpfa_speaker_position'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_position', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_position'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_organization'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_organization', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_organization'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_bio'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_bio', wp_kses_post( wp_unslash( $_POST['wpfa_speaker_bio'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_headshot_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_headshot_url', esc_url_raw( wp_unslash( $_POST['wpfa_speaker_headshot_url'] ) ) );
		}
	}

	/**
	 * Find speakers whose speaker-side event meta includes an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_speakers_linked_to_event( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return array();
		}

		$speaker_ids = get_posts(
			array(
				'post_type'      => self::$post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$speaker_ids = array_filter(
			array_map(
				'absint',
				$speaker_ids
			),
			static function ( $speaker_id ) use ( $event_id ) {
				return in_array( $event_id, self::get_speaker_event_ids( $speaker_id ), true );
			}
		);

		return Wpfaevent_Meta_Event::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Find all speakers with any event relationship.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int>
	 */
	public static function get_all_speakers_linked_to_events() {
		$speaker_ids = get_posts(
			array(
				'post_type'      => self::$post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $speaker_ids ) ) {
			return array();
		}

		$speaker_ids = array_filter(
			array_map(
				'absint',
				$speaker_ids
			),
			static function ( $speaker_id ) {
				return ! empty( self::get_speaker_event_ids( $speaker_id ) );
			}
		);

		return Wpfaevent_Meta_Event::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Find events whose event-side speaker meta includes a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $speaker_id  Speaker post ID.
	 * @param string|array $post_status Event post status filter.
	 * @return array<int>
	 */
	public static function get_events_referencing_speaker( $speaker_id, $post_status = 'any' ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id ) {
			return array();
		}

		$event_ids = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$event_ids = array_filter(
			array_map(
				'absint',
				$event_ids
			),
			static function ( $event_id ) use ( $speaker_id ) {
				return in_array( $speaker_id, Wpfaevent_Meta_Event::get_event_speaker_ids( $event_id ), true );
			}
		);

		return Wpfaevent_Meta_Event::sanitize_post_id_list( $event_ids );
	}

	/**
	 * Get normalized event IDs linked to a speaker from both relationship sides.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $speaker_id  Speaker post ID.
	 * @param string|array $post_status Event post status filter.
	 * @return array<int>
	 */
	public static function get_events_linked_to_speaker( $speaker_id, $post_status = 'publish' ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id || get_post_type( $speaker_id ) !== self::$post_type ) {
			return array();
		}

		$event_ids = Wpfaevent_Meta_Event::sanitize_post_id_list(
			array_merge(
				self::get_speaker_event_ids( $speaker_id ),
				self::get_events_referencing_speaker( $speaker_id, $post_status )
			)
		);

		if ( empty( $event_ids ) || empty( $post_status ) || 'any' === $post_status ) {
			return $event_ids;
		}

		$event_ids = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__in'       => $event_ids,
				'orderby'        => 'post__in',
				'no_found_rows'  => true,
			)
		);

		return Wpfaevent_Meta_Event::sanitize_post_id_list( $event_ids );
	}

	/**
	 * Add an event ID to a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $speaker_id       Speaker post ID.
	 * @param int  $event_id         Event post ID.
	 * @param bool $check_capability Whether to require edit access to the speaker.
	 */
	public static function add_event_to_speaker( $speaker_id, $event_id, $check_capability = true ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$post_type ) {
			return;
		}

		if ( $check_capability && ! current_user_can( 'edit_post', $speaker_id ) ) {
			return;
		}

		$event_ids   = self::get_speaker_event_ids( $speaker_id );
		$event_ids[] = $event_id;

		update_post_meta( $speaker_id, 'wpfa_speaker_events', Wpfaevent_Meta_Event::sanitize_post_id_list( $event_ids ) );
	}

	/**
	 * Remove an event ID from a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $speaker_id       Speaker post ID.
	 * @param int  $event_id         Event post ID.
	 * @param bool $check_capability Whether to require edit access to the speaker.
	 */
	public static function remove_event_from_speaker( $speaker_id, $event_id, $check_capability = true ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$post_type ) {
			return;
		}

		if ( $check_capability && ! current_user_can( 'edit_post', $speaker_id ) ) {
			return;
		}

		$event_ids = array_diff( self::get_speaker_event_ids( $speaker_id ), array( $event_id ) );
		$event_ids = Wpfaevent_Meta_Event::sanitize_post_id_list( $event_ids );

		if ( empty( $event_ids ) ) {
			delete_post_meta( $speaker_id, 'wpfa_speaker_events' );
			return;
		}

		update_post_meta( $speaker_id, 'wpfa_speaker_events', $event_ids );
	}

	/**
	 * Get normalized event IDs assigned to a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int>
	 */
	public static function get_speaker_event_ids( $speaker_id ) {
		return Wpfaevent_Meta_Event::sanitize_post_id_list( get_post_meta( $speaker_id, 'wpfa_speaker_events', true ) );
	}

	/**
	 * Registers a single speaker string meta field.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $meta_key          Meta key to register.
	 * @param string          $description       REST/API field description.
	 * @param callable|string $sanitize_callback Sanitization callback.
	 * @return void
	 */
	private static function register_string_meta( $meta_key, $description, $sanitize_callback ) {
		register_post_meta(
			self::$post_type,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitize_callback,
				'description'       => $description,
			)
		);
	}

	/**
	 * Sanitizes an array of event IDs.
	 *
	 * @since 1.0.0
	 * @param array $event_ids Array of event post IDs.
	 * @return array Sanitized array of integers.
	 */
	public static function sanitize_event_ids( $event_ids ) {
		return Wpfaevent_Meta_Event::sanitize_post_id_list( $event_ids );
	}
}
