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
				'sanitize_callback' => array( __CLASS__, 'sanitize_date_value' ),
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
				'sanitize_callback' => array( __CLASS__, 'sanitize_date_value' ),
				'description'       => __( 'Event end date', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_start_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event start time', 'wpfaevent' ),
			)
		);

		// Legacy single event time used by the front-end event modal.
		register_post_meta(
			self::$post_type,
			'wpfa_event_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_end_time',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_time_value' ),
				'description'       => __( 'Event end time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_timezone',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_timezone' ),
				'description'       => __( 'Event timezone', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_all_day',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_boolean_value' ),
				'description'       => __( 'Whether the event is an all-day event', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_starts_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Normalized event start date-time', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_ends_at',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Normalized event end date-time', 'wpfaevent' ),
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

		register_post_meta(
			self::$post_type,
			'wpfa_event_venue_information',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'Venue, hotel, and transportation information for attendees', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_custom_tabs',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'title'   => array(
									'type' => 'string',
								),
								'slug'    => array(
									'type' => 'string',
								),
								'content' => array(
									'type' => 'string',
								),
							),
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_custom_tabs' ),
				'description'       => __( 'Event-specific custom tab sections for attendee information', 'wpfaevent' ),
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

		register_post_meta(
			self::$post_type,
			'wpfa_event_header_image_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Event-specific header image URL', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_logo_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Event-specific logo or banner image URL', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_ticket_widget_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Eventyay ticket widget event URL', 'wpfaevent' ),
			)
		);

		// Event hero section lead text.
		register_post_meta(
			self::$post_type,
			'wpfa_event_lead_text',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'Event hero lead text', 'wpfaevent' ),
			)
		);

		// Event registration link.
		register_post_meta(
			self::$post_type,
			'wpfa_event_registration_link',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Event registration link', 'wpfaevent' ),
			)
		);

		// Call for speakers link.
		register_post_meta(
			self::$post_type,
			'wpfa_event_cfs_link',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
				'description'       => __( 'Call for speakers link', 'wpfaevent' ),
			)
		);

		register_post_meta(
			self::$post_type,
			'wpfa_event_languages',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_language_list' ),
				'description'       => __( 'Event languages', 'wpfaevent' ),
			)
		);

		foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) {
			register_post_meta(
				self::$post_type,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_color_value' ),
					'description'       => $label,
				)
			);
		}

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

		register_post_meta(
			self::$post_type,
			'wpfa_event_featured_speakers',
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
				'description'       => __( 'Featured speaker post IDs for this event', 'wpfaevent' ),
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

		add_meta_box(
			'wpfa_event_additional_information',
			__( 'Attendee Information', 'wpfaevent' ),
			array( __CLASS__, 'render_additional_information_meta_box' ),
			self::$post_type,
			'normal',
			'default'
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
		$start_time = get_post_meta( $post->ID, 'wpfa_event_start_time', true );
		$end_time   = get_post_meta( $post->ID, 'wpfa_event_end_time', true );
		$timezone   = self::get_event_timezone( $post->ID );
		$all_day    = self::get_event_all_day( $post->ID );
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$header_url = get_post_meta( $post->ID, 'wpfa_event_header_image_url', true );
		$logo_url   = get_post_meta( $post->ID, 'wpfa_event_logo_url', true );
		$widget_url = get_post_meta( $post->ID, 'wpfa_event_ticket_widget_url', true );
		$lead_text  = get_post_meta( $post->ID, 'wpfa_event_lead_text', true );
		$reg_link   = get_post_meta( $post->ID, 'wpfa_event_registration_link', true );
		$cfs_link   = get_post_meta( $post->ID, 'wpfa_event_cfs_link', true );
		$languages  = self::sanitize_language_list( get_post_meta( $post->ID, 'wpfa_event_languages', true ) );
		$colors     = self::get_event_colors( $post->ID );
		$speakers   = self::get_admin_event_speaker_ids( $post->ID );
		$featured   = array_values( array_intersect( self::get_event_featured_speaker_ids( $post->ID ), $speakers ) );
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
				<th><label for="wpfa_event_timezone"><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></label></th>
				<td>
					<select id="wpfa_event_timezone" name="wpfa_event_timezone" class="regular-text">
						<?php echo wp_timezone_choice( $timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes timezone option markup. ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used to interpret timed events and calendar exports. Leave as the site timezone when the event does not need a separate timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Time Format', 'wpfaevent' ); ?></th>
				<td>
					<label for="wpfa_event_all_day">
						<input type="checkbox" id="wpfa_event_all_day" name="wpfa_event_all_day" value="1" <?php checked( $all_day ); ?>>
						<?php esc_html_e( 'All-day event', 'wpfaevent' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'All-day events export as date-only calendar entries. Timed events use the event timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_start_time"><?php esc_html_e( 'Start Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_start_time" name="wpfa_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_time"><?php esc_html_e( 'End Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_end_time" name="wpfa_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text"></td>
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
				<th><label for="wpfa_event_header_image_url"><?php esc_html_e( 'Header Image URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_header_image_url" name="wpfa_event_header_image_url" value="<?php echo esc_attr( $header_url ); ?>" class="regular-text" placeholder="https://">
					<p class="description"><?php esc_html_e( 'Imported from Eventyay header, banner, hero, or cover image fields when available.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_logo_url"><?php esc_html_e( 'Event Logo URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_logo_url" name="wpfa_event_logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" placeholder="https://">
					<p class="description"><?php esc_html_e( 'Imported from Eventyay logo or shop banner image settings when available.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_ticket_widget_url"><?php esc_html_e( 'Ticket Widget URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_ticket_widget_url" name="wpfa_event_ticket_widget_url" value="<?php echo esc_attr( $widget_url ); ?>" class="regular-text" placeholder="https://eventyay.com/organizer/event/">
					<p class="description"><?php esc_html_e( 'Used to embed the Eventyay ticket purchasing widget on the event page.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_lead_text"><?php esc_html_e( 'Lead Text', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_lead_text" name="wpfa_event_lead_text" value="<?php echo esc_attr( $lead_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Short description for hero section', 'wpfaevent' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_registration_link"><?php esc_html_e( 'Registration Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_registration_link" name="wpfa_event_registration_link" value="<?php echo esc_attr( $reg_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/..."></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_cfs_link"><?php esc_html_e( 'Call for Speakers Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_cfs_link" name="wpfa_event_cfs_link" value="<?php echo esc_attr( $cfs_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/.../cfs"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_languages"><?php esc_html_e( 'Event Languages', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="text" id="wpfa_event_languages" name="wpfa_event_languages" value="<?php echo esc_attr( implode( ', ', $languages ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Comma-separated language names or codes, e.g. English, Hindi, German.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Event Colors', 'wpfaevent' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) : ?>
							<label style="display:block;margin-bottom:8px;" for="<?php echo esc_attr( $meta_key ); ?>">
								<span style="display:inline-block;min-width:180px;"><?php echo esc_html( $label ); ?></span>
								<input type="text" id="<?php echo esc_attr( $meta_key ); ?>" name="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( isset( $colors[ $meta_key ] ) ? $colors[ $meta_key ] : '' ); ?>" class="regular-text" placeholder="#D51007">
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Imported from Eventyay settings when the API exposes event theme colors.', 'wpfaevent' ); ?></p>
				</td>
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
			<tr>
				<th><label for="wpfa_event_featured_speakers"><?php esc_html_e( 'Featured Speakers', 'wpfaevent' ); ?></label></th>
				<td>
					<?php if ( ! empty( $speakers ) ) : ?>
						<select name="wpfa_event_featured_speakers[]" id="wpfa_event_featured_speakers" multiple class="wpfaevent-speakers-select">
							<?php foreach ( $speakers as $speaker_id ) : ?>
								<option value="<?php echo esc_attr( $speaker_id ); ?>" <?php selected( in_array( $speaker_id, $featured, true ) ); ?>>
									<?php echo esc_html( get_the_title( $speaker_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Featured speakers are highlighted and listed first on this event only.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'Add speakers to this event before selecting featured speakers.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Additional Information meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Event post object.
	 */
	public static function render_additional_information_meta_box( $post ) {
		$venue_info  = get_post_meta( $post->ID, 'wpfa_event_venue_information', true );
		$custom_tabs = self::sanitize_custom_tabs( get_post_meta( $post->ID, 'wpfa_event_custom_tabs', true ) );
		?>
		<p class="description">
			<?php esc_html_e( 'Use this area for attendee-facing information that appears on the event page. Keep the main section for general venue and travel notes, then add extra sections only for topics that need their own navigation item.', 'wpfaevent' ); ?>
		</p>
		<div class="wpfaevent-attendee-info">
			<div class="wpfaevent-attendee-info-section">
				<h3><?php esc_html_e( 'Main Additional Information', 'wpfaevent' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Shown as the default Additional Info section. Best for venue notes, directions, parking, transport, and accessibility details.', 'wpfaevent' ); ?>
				</p>
				<?php
				wp_editor(
					$venue_info,
					'wpfa_event_venue_information',
					array(
						'textarea_name' => 'wpfa_event_venue_information',
						'textarea_rows' => 8,
						'media_buttons' => false,
					)
				);
				?>
			</div>

			<div class="wpfaevent-attendee-info-section">
				<h3><?php esc_html_e( 'Extra Information Sections', 'wpfaevent' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Add separate event page sections for focused topics such as accommodation options, travel passes, childcare, or attendee resources.', 'wpfaevent' ); ?>
				</p>
				<div class="wpfaevent-custom-tabs" data-next-index="<?php echo esc_attr( count( $custom_tabs ) ); ?>">
					<div class="wpfaevent-custom-tabs-list">
						<?php foreach ( $custom_tabs as $index => $custom_tab ) : ?>
							<?php self::render_custom_tab_row( $index, $custom_tab ); ?>
						<?php endforeach; ?>
					</div>
					<p class="wpfaevent-custom-tabs-empty" <?php echo empty( $custom_tabs ) ? '' : 'hidden="hidden"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute fragment. ?>>
						<?php esc_html_e( 'No extra sections yet. Add one only when a topic needs its own tab in the event navigation.', 'wpfaevent' ); ?>
					</p>
					<p>
						<button type="button" class="button wpfaevent-add-custom-tab">
							<?php esc_html_e( 'Add Information Section', 'wpfaevent' ); ?>
						</button>
					</p>
					<script type="text/template" class="wpfaevent-custom-tab-template">
						<?php
						self::render_custom_tab_row(
							'{{INDEX}}',
							array(
								'title'   => '',
								'slug'    => '',
								'content' => '',
							)
						);
						?>
					</script>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one custom tab row.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $index Row index.
	 * @param array      $tab   Custom tab data.
	 */
	private static function render_custom_tab_row( $index, $tab ) {
		$index   = (string) $index;
		$title   = isset( $tab['title'] ) && is_scalar( $tab['title'] ) ? sanitize_text_field( $tab['title'] ) : '';
		$slug    = isset( $tab['slug'] ) && is_scalar( $tab['slug'] ) ? sanitize_title( $tab['slug'] ) : '';
		$content = isset( $tab['content'] ) && is_scalar( $tab['content'] ) ? (string) $tab['content'] : '';
		?>
		<div class="wpfaevent-custom-tab-row">
			<input type="hidden" name="wpfa_event_custom_tabs[<?php echo esc_attr( $index ); ?>][slug]" value="<?php echo esc_attr( $slug ); ?>">
			<p>
				<label for="wpfa_event_custom_tabs_<?php echo esc_attr( $index ); ?>_title">
					<strong><?php esc_html_e( 'Section Title', 'wpfaevent' ); ?></strong>
				</label>
				<input
					type="text"
					id="wpfa_event_custom_tabs_<?php echo esc_attr( $index ); ?>_title"
					name="wpfa_event_custom_tabs[<?php echo esc_attr( $index ); ?>][title]"
					value="<?php echo esc_attr( $title ); ?>"
					class="widefat"
					placeholder="<?php esc_attr_e( 'Accommodation', 'wpfaevent' ); ?>"
				>
			</p>
			<p>
				<label for="wpfa_event_custom_tabs_<?php echo esc_attr( $index ); ?>_content">
					<strong><?php esc_html_e( 'Section Content', 'wpfaevent' ); ?></strong>
				</label>
				<textarea
					id="wpfa_event_custom_tabs_<?php echo esc_attr( $index ); ?>_content"
					name="wpfa_event_custom_tabs[<?php echo esc_attr( $index ); ?>][content]"
					rows="6"
					class="widefat"
					placeholder="<?php esc_attr_e( 'List recommended hotels, booking links, travel notes, or other useful event details.', 'wpfaevent' ); ?>"
				><?php echo esc_textarea( $content ); ?></textarea>
			</p>
			<button type="button" class="button-link-delete wpfaevent-remove-custom-tab">
				<?php esc_html_e( 'Remove tab', 'wpfaevent' ); ?>
			</button>
		</div>
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
			$posted_start_date = sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_date'] ) );
			update_post_meta( $post_id, 'wpfa_event_start_date', self::sanitize_date_value( $posted_start_date ) );
		}

		if ( isset( $_POST['wpfa_event_end_date'] ) ) {
			$posted_end_date = sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_date'] ) );
			update_post_meta( $post_id, 'wpfa_event_end_date', self::sanitize_date_value( $posted_end_date ) );
		}

		$posted_timezone = isset( $_POST['wpfa_event_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_timezone'] ) ) : '';
		$timezone        = self::sanitize_timezone( $posted_timezone );
		if ( '' !== $timezone ) {
			update_post_meta( $post_id, 'wpfa_event_timezone', $timezone );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_timezone' );
		}

		$all_day = isset( $_POST['wpfa_event_all_day'] );
		update_post_meta( $post_id, 'wpfa_event_all_day', $all_day ? '1' : '0' );

		$posted_start_time = isset( $_POST['wpfa_event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_time'] ) ) : '';
		$posted_end_time   = isset( $_POST['wpfa_event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_time'] ) ) : '';
		$start_time        = self::sanitize_time_value( $posted_start_time );
		$end_time          = self::sanitize_time_value( $posted_end_time );

		if ( $all_day ) {
			delete_post_meta( $post_id, 'wpfa_event_start_time' );
			delete_post_meta( $post_id, 'wpfa_event_time' );
			delete_post_meta( $post_id, 'wpfa_event_end_time' );
			delete_post_meta( $post_id, 'wpfa_event_starts_at' );
			delete_post_meta( $post_id, 'wpfa_event_ends_at' );
		} else {
			self::update_or_delete_meta( $post_id, 'wpfa_event_start_time', $start_time );
			self::update_or_delete_meta( $post_id, 'wpfa_event_time', $start_time );
			self::update_or_delete_meta( $post_id, 'wpfa_event_end_time', $end_time );
			self::update_or_delete_meta( $post_id, 'wpfa_event_starts_at', self::build_datetime_value( get_post_meta( $post_id, 'wpfa_event_start_date', true ), $start_time, $timezone ) );
			self::update_or_delete_meta( $post_id, 'wpfa_event_ends_at', self::build_datetime_value( get_post_meta( $post_id, 'wpfa_event_end_date', true ), $end_time, $timezone ) );
		}

		if ( isset( $_POST['wpfa_event_location'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_location', sanitize_text_field( wp_unslash( $_POST['wpfa_event_location'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_venue_information'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_venue_information', wp_kses_post( wp_unslash( $_POST['wpfa_event_venue_information'] ) ) );
		}

		$posted_custom_tabs = isset( $_POST['wpfa_event_custom_tabs'] ) ? wp_unslash( $_POST['wpfa_event_custom_tabs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_custom_tabs().
		$custom_tabs        = self::sanitize_custom_tabs( $posted_custom_tabs );
		if ( ! empty( $custom_tabs ) ) {
			update_post_meta( $post_id, 'wpfa_event_custom_tabs', $custom_tabs );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_custom_tabs' );
		}

		if ( isset( $_POST['wpfa_event_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_event_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_url'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_header_image_url'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_header_image_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_header_image_url'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_logo_url'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_logo_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_logo_url'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_ticket_widget_url'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_ticket_widget_url', esc_url_raw( wp_unslash( $_POST['wpfa_event_ticket_widget_url'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_lead_text'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_lead_text', sanitize_text_field( wp_unslash( $_POST['wpfa_event_lead_text'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_registration_link'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_registration_link', esc_url_raw( wp_unslash( $_POST['wpfa_event_registration_link'] ) ) );
		}

		if ( isset( $_POST['wpfa_event_cfs_link'] ) ) {
			self::update_or_delete_meta( $post_id, 'wpfa_event_cfs_link', esc_url_raw( wp_unslash( $_POST['wpfa_event_cfs_link'] ) ) );
		}

		$languages = isset( $_POST['wpfa_event_languages'] ) ? self::sanitize_language_list( sanitize_text_field( wp_unslash( $_POST['wpfa_event_languages'] ) ) ) : array();
		if ( ! empty( $languages ) ) {
			update_post_meta( $post_id, 'wpfa_event_languages', $languages );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_languages' );
		}

		foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) {
			$color = isset( $_POST[ $meta_key ] ) ? self::sanitize_color_value( sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) ) : '';
			if ( '' !== $color ) {
				update_post_meta( $post_id, $meta_key, $color );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
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

		$featured_speakers = array();
		if ( isset( $_POST['wpfa_event_featured_speakers'] ) && is_array( $_POST['wpfa_event_featured_speakers'] ) ) {
			$featured_speakers = self::sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_event_featured_speakers'] )
				)
			);
		}
		$featured_speakers = array_values( array_intersect( $featured_speakers, $speakers ) );

		if ( ! empty( $featured_speakers ) ) {
			update_post_meta( $post_id, 'wpfa_event_featured_speakers', $featured_speakers );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_featured_speakers' );
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
	 * Get normalized featured speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int>
	 */
	public static function get_event_featured_speaker_ids( $event_id ) {
		return self::sanitize_post_id_list( get_post_meta( $event_id, 'wpfa_event_featured_speakers', true ) );
	}

	/**
	 * Resolve featured speaker IDs from event meta, dashboard JSON, and speaker categories.
	 *
	 * @since 1.0.0
	 *
	 * @param int               $event_id           Event post ID.
	 * @param array<int>        $speaker_ids        Linked speaker post IDs.
	 * @param array<int, array> $dashboard_speakers Imported dashboard speaker rows.
	 * @return array<int>
	 */
	public static function resolve_event_featured_speaker_ids( $event_id, $speaker_ids, $dashboard_speakers = array() ) {
		$event_id    = absint( $event_id );
		$speaker_ids = self::sanitize_post_id_list( $speaker_ids );
		$featured    = array_values( array_intersect( self::get_event_featured_speaker_ids( $event_id ), $speaker_ids ) );

		if ( is_array( $dashboard_speakers ) && ! empty( $dashboard_speakers ) ) {
			$eventyay_map = array();
			$name_map     = array();

			foreach ( $speaker_ids as $speaker_id ) {
				$eventyay_id = sanitize_text_field( get_post_meta( $speaker_id, '_wpfa_eventyay_speaker_id', true ) );

				if ( '' !== $eventyay_id ) {
					$eventyay_map[ $eventyay_id ] = $speaker_id;
				}

				$name_key = sanitize_title( get_the_title( $speaker_id ) );

				if ( '' !== $name_key ) {
					$name_map[ $name_key ] = $speaker_id;
				}
			}

			foreach ( $dashboard_speakers as $dashboard_speaker ) {
				if ( ! is_array( $dashboard_speaker ) || empty( $dashboard_speaker['featured'] ) ) {
					continue;
				}

				$matched_id = 0;

				if ( ! empty( $dashboard_speaker['eventyay_speaker_id'] ) && isset( $eventyay_map[ $dashboard_speaker['eventyay_speaker_id'] ] ) ) {
					$matched_id = (int) $eventyay_map[ $dashboard_speaker['eventyay_speaker_id'] ];
				} elseif ( ! empty( $dashboard_speaker['name'] ) ) {
					$name_key = sanitize_title( $dashboard_speaker['name'] );

					if ( isset( $name_map[ $name_key ] ) ) {
						$matched_id = (int) $name_map[ $name_key ];
					}
				}

				if ( $matched_id && ! in_array( $matched_id, $featured, true ) ) {
					$featured[] = $matched_id;
				}
			}
		}

		if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
			foreach ( $speaker_ids as $speaker_id ) {
				if ( in_array( $speaker_id, $featured, true ) ) {
					continue;
				}

				$terms = get_the_terms( $speaker_id, 'wpfa_speaker_category' );

				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					if ( preg_match( '/\b(featured|keynote|plenary|highlight)\b/i', $term->name ) ) {
						$featured[] = $speaker_id;
						break;
					}
				}
			}
		}

		$featured = self::sanitize_post_id_list( $featured );
		$featured = array_values( array_intersect( $featured, $speaker_ids ) );

		if ( empty( $featured ) && ! empty( $speaker_ids ) ) {
			$auto_limit = absint(
				apply_filters(
					'wpfa_event_auto_featured_speaker_limit',
					1,
					$event_id,
					$speaker_ids,
					$dashboard_speakers
				)
			);

			if ( $auto_limit > 0 ) {
				$featured = array_slice( $speaker_ids, 0, min( $auto_limit, count( $speaker_ids ) ) );
			}
		}

		/**
		 * Filter resolved featured speaker IDs for an event page.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int>        $featured           Resolved featured speaker IDs.
		 * @param int               $event_id           Event post ID.
		 * @param array<int>        $speaker_ids        Linked speaker post IDs.
		 * @param array<int, array> $dashboard_speakers Imported dashboard speaker rows.
		 */
		return apply_filters( 'wpfa_event_featured_speaker_ids', $featured, $event_id, $speaker_ids, $dashboard_speakers );
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
	 * Get event color meta fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function get_event_color_meta_fields() {
		return array(
			'wpfa_event_primary_color'          => __( 'Primary color', 'wpfaevent' ),
			'wpfa_event_hover_button_color'     => __( 'Button hover color', 'wpfaevent' ),
			'wpfa_event_theme_background_color' => __( 'Theme background color', 'wpfaevent' ),
			'wpfa_event_theme_success_color'    => __( 'Theme success color', 'wpfaevent' ),
			'wpfa_event_theme_danger_color'     => __( 'Theme danger color', 'wpfaevent' ),
		);
	}

	/**
	 * Get sanitized event colors for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, string>
	 */
	public static function get_event_colors( $event_id ) {
		$colors = array();

		foreach ( self::get_event_color_meta_fields() as $meta_key => $label ) {
			$color = self::sanitize_color_value( get_post_meta( $event_id, $meta_key, true ) );
			if ( '' !== $color ) {
				$colors[ $meta_key ] = $color;
			}
		}

		return $colors;
	}

	/**
	 * Sanitize event language values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $languages Raw language list.
	 * @return array<string>
	 */
	public static function sanitize_language_list( $languages ) {
		if ( is_string( $languages ) ) {
			$decoded = json_decode( $languages, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				$languages = $decoded;
			} else {
				$languages = preg_split( '/[,|]/', $languages );
			}
		}

		if ( is_scalar( $languages ) ) {
			$languages = array( $languages );
		}

		if ( ! is_array( $languages ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $languages as $language ) {
			if ( is_array( $language ) ) {
				foreach ( array( 'name', 'label', 'title', 'code', 'locale', 'language' ) as $key ) {
					if ( ! empty( $language[ $key ] ) && is_scalar( $language[ $key ] ) ) {
						$language = $language[ $key ];
						break;
					}
				}
			}

			if ( ! is_scalar( $language ) ) {
				continue;
			}

			$language = sanitize_text_field( (string) $language );
			$language = trim( str_replace( '_', '-', $language ) );

			if ( '' === $language ) {
				continue;
			}

			$key = sanitize_title( $language );
			if ( '' !== $key ) {
				$normalized[ $key ] = $language;
			}
		}

		return array_values( $normalized );
	}

	/**
	 * Sanitize event custom tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $tabs Raw tab data.
	 * @return array<int, array<string, string>>
	 */
	public static function sanitize_custom_tabs( $tabs ) {
		if ( is_string( $tabs ) ) {
			$decoded_tabs = json_decode( $tabs, true );

			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded_tabs ) ) {
				$tabs = $decoded_tabs;
			}
		}

		if ( ! is_array( $tabs ) ) {
			return array();
		}

		$sanitized_tabs = array();
		$used_slugs     = array();

		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}

			$title   = isset( $tab['title'] ) && is_scalar( $tab['title'] ) ? sanitize_text_field( $tab['title'] ) : '';
			$content = isset( $tab['content'] ) && is_scalar( $tab['content'] ) ? trim( wp_kses_post( (string) $tab['content'] ) ) : '';

			if ( '' === $title || '' === $content ) {
				continue;
			}

			$slug = isset( $tab['slug'] ) && is_scalar( $tab['slug'] ) ? sanitize_title( $tab['slug'] ) : '';
			if ( '' === $slug ) {
				$slug = sanitize_title( $title );
			}

			if ( '' === $slug ) {
				$slug = 'custom-tab-' . ( count( $sanitized_tabs ) + 1 );
			}

			$base_slug = $slug;
			$suffix    = 2;
			while ( isset( $used_slugs[ $slug ] ) ) {
				$slug = $base_slug . '-' . $suffix;
				++$suffix;
			}

			$used_slugs[ $slug ] = true;

			$sanitized_tabs[] = array(
				'title'   => $title,
				'slug'    => $slug,
				'content' => $content,
			);
		}

		return array_values( $sanitized_tabs );
	}

	/**
	 * Sanitize an Eventyay color value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $color Raw color.
	 * @return string
	 */
	public static function sanitize_color_value( $color ) {
		if ( is_array( $color ) ) {
			foreach ( array( 'value', 'color', 'hex', 'default' ) as $key ) {
				if ( isset( $color[ $key ] ) ) {
					return self::sanitize_color_value( $color[ $key ] );
				}
			}

			return '';
		}

		if ( ! is_scalar( $color ) ) {
			return '';
		}

		$color = trim( (string) $color );
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $color ) ) {
			return strtoupper( $color );
		}

		if ( preg_match( '/^[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $color ) ) {
			return '#' . strtoupper( $color );
		}

		if ( preg_match( '/^rgba?\(\\s*\\d{1,3}\\s*,\\s*\\d{1,3}\\s*,\\s*\\d{1,3}(\\s*,\\s*(0|1|0?\\.\\d+))?\\s*\)$/', $color ) ) {
			return $color;
		}

		return '';
	}

		/**
		 * Update or remove a meta value based on emptiness.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $post_id Post ID.
		 * @param string $key     Meta key.
		 * @param string $value   Meta value.
		 * @return void
		 */
	private static function update_or_delete_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
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
		$previous_speakers = self::sanitize_post_id_list(
			array_merge(
				self::sanitize_post_id_list( $previous_speakers ),
				Wpfaevent_Meta_Speaker::get_speakers_linked_to_event( $event_id )
			)
		);
		$current_speakers  = self::sanitize_post_id_list( $current_speakers );

		if ( ! $event_id ) {
			return;
		}

		foreach ( array_diff( $previous_speakers, $current_speakers ) as $speaker_id ) {
			Wpfaevent_Meta_Speaker::remove_event_from_speaker( $speaker_id, $event_id, false );
		}

		foreach ( $current_speakers as $speaker_id ) {
			Wpfaevent_Meta_Speaker::add_event_to_speaker( $speaker_id, $event_id, false );
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

		$speaker_ids = array_map( 'absint', $speaker_ids );
		$speaker_ids = array_filter( $speaker_ids );

		return array_values( array_unique( $speaker_ids ) );
	}

	/**
	 * Sanitize an event date value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $date Raw date.
	 * @return string
	 */
	public static function sanitize_date_value( $date ) {
		if ( ! is_scalar( $date ) ) {
			return '';
		}

		$date = trim( sanitize_text_field( (string) $date ) );

		if ( '' === $date ) {
			return '';
		}

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) ) {
			return '';
		}

		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return '';
		}

		return $date;
	}

	/**
	 * Sanitize an event time value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $time Raw time.
	 * @return string
	 */
	public static function sanitize_time_value( $time ) {
		if ( ! is_scalar( $time ) ) {
			return '';
		}

		$time = trim( sanitize_text_field( (string) $time ) );

		if ( '' === $time ) {
			return '';
		}

		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $time ) ) {
			return '';
		}

		return substr( $time, 0, 5 );
	}

	/**
	 * Sanitize a timezone identifier or UTC offset.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $timezone Raw timezone.
	 * @return string
	 */
	public static function sanitize_timezone( $timezone ) {
		if ( ! is_scalar( $timezone ) ) {
			return '';
		}

		$timezone = trim( sanitize_text_field( (string) $timezone ) );

		if ( '' === $timezone ) {
			return '';
		}

		$timezone = self::normalize_utc_offset_timezone( $timezone );

		try {
			new DateTimeZone( $timezone );
			return $timezone;
		} catch ( Exception $exception ) {
			return '';
		}
	}

	/**
	 * Sanitize a boolean-like meta value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_boolean_value( $value ) {
		return rest_sanitize_boolean( $value );
	}

	/**
	 * Get an event timezone, falling back to the WordPress site timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public static function get_event_timezone( $event_id ) {
		$timezone = self::sanitize_timezone( get_post_meta( $event_id, 'wpfa_event_timezone', true ) );

		if ( '' !== $timezone ) {
			return $timezone;
		}

		$site_timezone = self::sanitize_timezone( wp_timezone_string() );

		if ( '' !== $site_timezone ) {
			return $site_timezone;
		}

		return wp_timezone()->getName();
	}

	/**
	 * Determine whether an event should be treated as all-day.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	public static function get_event_all_day( $event_id ) {
		$value = get_post_meta( $event_id, 'wpfa_event_all_day', true );

		if ( '' !== $value ) {
			return rest_sanitize_boolean( $value );
		}

		return '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_start_time', true ) )
			&& '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_end_time', true ) )
			&& '' === self::sanitize_time_value( get_post_meta( $event_id, 'wpfa_event_time', true ) );
	}

	/**
	 * Build an ISO 8601 datetime for timed manual events.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date     Date in Y-m-d format.
	 * @param string $time     Time in H:i format.
	 * @param string $timezone Timezone identifier.
	 * @return string
	 */
	public static function build_datetime_value( $date, $time, $timezone ) {
		$date     = self::sanitize_date_value( $date );
		$time     = self::sanitize_time_value( $time );
		$timezone = self::sanitize_timezone( $timezone );

		if ( '' === $date || '' === $time ) {
			return '';
		}

		if ( '' === $timezone ) {
			$timezone = self::get_event_timezone( 0 );
		}

		try {
			$datetime = new DateTimeImmutable( $date . ' ' . $time, new DateTimeZone( $timezone ) );
		} catch ( Exception $exception ) {
			return '';
		}

		return $datetime->format( DATE_ATOM );
	}

	/**
	 * Normalize old WordPress UTC offset labels into DateTimeZone-compatible offsets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone Timezone string.
	 * @return string
	 */
	private static function normalize_utc_offset_timezone( $timezone ) {
		if ( ! preg_match( '/^UTC([+-])(\d{1,2})(?:\.(5|50))?$/', $timezone, $matches ) ) {
			return $timezone;
		}

		$hours   = absint( $matches[2] );
		$minutes = empty( $matches[3] ) ? 0 : 30;

		if ( $hours > 14 ) {
			return $timezone;
		}

		return sprintf( '%s%02d:%02d', $matches[1], $hours, $minutes );
	}
}
