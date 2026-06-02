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
		// Speaker position or title.
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_position',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Speaker position/title', 'wpfaevent' ),
			)
		);

		// Speaker organization.
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_organization',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Speaker organization', 'wpfaevent' ),
			)
		);

		// Speaker biography.
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_bio',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'Speaker biography', 'wpfaevent' ),
			)
		);

		// Speaker headshot URL.
		register_post_meta(
			self::$post_type,
			'wpfa_speaker_headshot_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Speaker headshot image URL', 'wpfaevent' ),
			)
		);

		foreach ( self::get_url_meta_fields() as $meta_key => $description ) {
			register_post_meta(
				self::$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'esc_url_raw',
					'description'       => $description,
				)
			);
		}

		foreach ( self::get_text_meta_fields() as $meta_key => $description ) {
			register_post_meta(
				self::$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => $description,
				)
			);
		}

		register_post_meta(
			self::$post_type,
			'wpfa_speaker_talk_abstract',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'Speaker talk abstract', 'wpfaevent' ),
			)
		);

		// Related events for a bidirectional relationship.
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored in post meta.
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => 'i:' . $event_id . ';',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => '"' . $event_id . '"',
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'wpfa_speaker_events',
						'value'   => (string) $event_id,
						'compare' => '=',
					),
				),
			)
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Speaker ownership is stored in post meta.
				'meta_key'       => 'wpfa_speaker_events',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_compare -- Speaker ownership is stored in post meta.
				'meta_compare'   => 'EXISTS',
			)
		);

		return Wpfaevent_Meta_Event::sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Add an event ID to a speaker's related events.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id   Event post ID.
	 */
	public static function add_event_to_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $speaker_id ) ) {
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
	 * @param int $speaker_id Speaker post ID.
	 * @param int $event_id   Event post ID.
	 */
	public static function remove_event_from_speaker( $speaker_id, $event_id ) {
		$speaker_id = absint( $speaker_id );
		$event_id   = absint( $event_id );

		if ( ! $speaker_id || ! $event_id || get_post_type( $speaker_id ) !== self::$post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $speaker_id ) ) {
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
	 * URL meta fields owned by speakers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	private static function get_url_meta_fields() {
		return array(
			'wpfa_speaker_linkedin' => __( 'Speaker LinkedIn URL', 'wpfaevent' ),
			'wpfa_speaker_twitter'  => __( 'Speaker Twitter/X URL', 'wpfaevent' ),
			'wpfa_speaker_github'   => __( 'Speaker GitHub URL', 'wpfaevent' ),
			'wpfa_speaker_website'  => __( 'Speaker website URL', 'wpfaevent' ),
		);
	}

	/**
	 * Plain-text meta fields owned by speakers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	private static function get_text_meta_fields() {
		return array(
			'wpfa_speaker_talk_title'    => __( 'Speaker talk title', 'wpfaevent' ),
			'wpfa_speaker_talk_date'     => __( 'Speaker talk date', 'wpfaevent' ),
			'wpfa_speaker_talk_time'     => __( 'Speaker talk start time', 'wpfaevent' ),
			'wpfa_speaker_talk_end_time' => __( 'Speaker talk end time', 'wpfaevent' ),
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
		if ( ! is_array( $event_ids ) ) {
			return array();
		}

		return array_map( 'absint', $event_ids );
	}
}
