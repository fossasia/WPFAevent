<?php
/**
 * Eventyay Importer Event Repository.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database operations for importing and updating event posts.
 */
class Wpfaevent_Event_Repository {

	/**
	 * JSONAPI Parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_JSONAPI_Parser|null $parser Optional parser instance.
	 */
	public function __construct( $parser = null ) {
		$this->parser = $parser ? $parser : new Wpfaevent_JSONAPI_Parser();
	}

	/**
	 * Upsert an Eventyay event post CPT entry.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event    Eventyay event resource.
	 * @param array $settings Import settings.
	 * @return array|WP_Error Import outcome details.
	 */
	public function upsert_eventyay_event_post( $event, $settings ) {
		$event_slug = $this->parser->eventyay_event_slug( $event );
		if ( empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_slug',
				esc_html__( 'Eventyay event does not have a valid identifier.', 'wpfaevent' )
			);
		}

		$post_id     = $this->find_eventyay_event_post( $settings['organizer_slug'], $event_slug );
		$is_new      = ! $post_id;
		$post_status = $is_new ? $settings['post_status'] : get_post_status( $post_id );

		$post_data = array(
			'post_type'    => 'wpfa_event',
			'post_status'  => $post_status ? $post_status : 'draft',
			'post_title'   => $this->eventyay_event_title( $event ),
			'post_content' => $this->eventyay_event_description( $event ),
			'post_name'    => $event_slug,
		);

		if ( ! $is_new ) {
			$post_data['ID'] = $post_id;
			$result_id       = wp_update_post( $post_data, true );
		} else {
			$result_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result_id ) ) {
			return $result_id;
		}

		$post_id  = absint( $result_id );
		$timezone = $this->eventyay_timezone_object( $this->eventyay_event_timezone( $event ) );

		update_post_meta( $post_id, '_eventyay_organizer_slug', $settings['organizer_slug'] );
		update_post_meta( $post_id, '_eventyay_event_slug', $event_slug );

		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_timezone', Wpfaevent_Meta_Event::sanitize_timezone( $this->eventyay_event_timezone( $event ) ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_location', $this->eventyay_event_location( $event ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_url', $this->eventyay_public_event_url( $event, $settings, $event_slug ) );

		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_date', $this->format_eventyay_date( $this->eventyay_event_datetime( $event, 'start' ), $timezone ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_time', $this->format_eventyay_time( $this->eventyay_event_datetime( $event, 'start' ), $timezone ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_date', $this->format_eventyay_date( $this->eventyay_event_datetime( $event, 'end' ), $timezone ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_time', $this->format_eventyay_time( $this->eventyay_event_datetime( $event, 'end' ), $timezone ) );

		$logo = $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $event, array( 'logo_image', 'logo_url', 'logo-url', 'logo', 'event_logo_image' ), true ), $settings['base_url'] );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_logo', $logo );

		$latitude  = $this->parser->eventyay_scalar_value( $this->parser->eventyay_first_present_raw( $event, array( 'latitude', 'lat' ), true ) );
		$longitude = $this->parser->eventyay_scalar_value( $this->parser->eventyay_first_present_raw( $event, array( 'longitude', 'lng', 'lon', 'long' ), true ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_latitude', $latitude );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_longitude', $longitude );

		// Import additional metadata fields.
		$lead_text = $this->parser->eventyay_text_value( $this->parser->eventyay_first_present_raw( $event, array( 'lead_text', 'lead-text', 'subtitle', 'frontpage_text', 'frontpage-text', 'short_description', 'short-description', 'summary' ), true ) );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_lead_text', $lead_text );

		// Set the post excerpt as a fallback source for the hero text on the single-event template.
		if ( ! empty( $lead_text ) ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $lead_text,
				)
			);
		}

		$reg_link = $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $event, array( 'ticket_url', 'ticket-url', 'registration_link', 'registration-link', 'register_link', 'register-link' ), true ), $settings['base_url'] );
		// Eventyay does not expose a separate ticket URL; fall back to the public event page which IS the registration page.
		if ( empty( $reg_link ) ) {
			$reg_link = $this->eventyay_public_event_url( $event, $settings, $event_slug );
		}
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_registration_link', $reg_link );

		$cfs_link = $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $event, array( 'cfs_link', 'cfs-link', 'cfp_link', 'cfp-link', 'cfp_url', 'cfp-url', 'speakers_url', 'speakers-url' ), true ), $settings['base_url'] );
		$this->update_or_delete_post_meta( $post_id, 'wpfa_event_cfs_link', $cfs_link );

		// Import banner as featured image.
		$banner_url = $this->parser->eventyay_url_value( $this->parser->eventyay_first_present_raw( $event, array( 'banner_url', 'banner-url', 'banner', 'logo_image', 'event_logo_image', 'logo_image_large', 'logo_url', 'logo-url', 'logo' ), true ), $settings['base_url'] );
		$this->sideload_event_featured_image( $post_id, $banner_url );

		return array(
			'post_id' => $post_id,
			'created' => $is_new ? 1 : 0,
			'updated' => $is_new ? 0 : 1,
			'skipped' => 0,
		);
	}

	/**
	 * Find an existing Eventyay CPT event post.
	 *
	 * @since 1.0.0
	 *
	 * @param string $organizer_slug Eventyay organizer slug.
	 * @param string $event_slug     Eventyay event slug.
	 * @return int Post ID if found, 0 otherwise.
	 */
	public function find_eventyay_event_post( $organizer_slug, $event_slug ) {
		// First try searching by post_name (slug), which is indexed in wp_posts and extremely fast.
		$posts = get_posts(
			array(
				'post_type'              => 'wpfa_event',
				'post_status'            => 'any',
				'name'                   => $event_slug,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $posts ) ) {
			return absint( $posts[0] );
		}

		// Fallback to meta query for backwards compatibility with older imports.
		$posts = get_posts(
			array(
				'post_type'              => 'wpfa_event',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'   => '_eventyay_organizer_slug',
						'value' => $organizer_slug,
					),
					array(
						'key'   => '_eventyay_event_slug',
						'value' => $event_slug,
					),
				),
			)
		);

		return ! empty( $posts ) ? absint( $posts[0] ) : 0;
	}

	/**
	 * Extract event title.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_title( $event ) {
		return $this->parser->eventyay_first_present_text( $event, array( 'name', 'title' ) );
	}

	/**
	 * Extract event description.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_description( $event ) {
		$value = $this->parser->eventyay_first_present_raw(
			$event,
			array(
				'description',
				'frontpage_text',
				'frontpage-text',
				'event_info_text',
				'event-info-text',
				'about',
				'about_text',
				'about-text',
				'text',
				'intro',
				'short_description',
				'short-description',
				'subtitle',
				'summary',
			),
			true
		);

		return $this->parser->eventyay_rich_text_value( $value );
	}

	/**
	 * Extract event date-time strings.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $event Eventyay event resource.
	 * @param string $type  "start" or "end".
	 * @return string
	 */
	public function eventyay_event_datetime( $event, $type ) {
		$keys = 'start' === $type ? array( 'starts_at', 'starts-at', 'start_time', 'start-time', 'start_date', 'start-date', 'start', 'date_from', 'date-from' ) : array( 'ends_at', 'ends-at', 'end_time', 'end-time', 'end_date', 'end-date', 'end', 'date_to', 'date-to' );

		return $this->parser->eventyay_scalar_value( $this->parser->eventyay_first_present_raw( $event, $keys, true ) );
	}

	/**
	 * Extract event timezone identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_timezone( $event ) {
		return $this->parser->eventyay_first_present_text( $event, array( 'timezone', 'time_zone', 'timezone_name', 'timezone-name' ) );
	}

	/**
	 * Extract event location name.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	public function eventyay_event_location( $event ) {
		$value = $this->parser->eventyay_first_present_raw( $event, array( 'location_name', 'location-name', 'searchable_location_name', 'searchable-location-name', 'location', 'venue', 'venue_name', 'venue-name', 'address' ), true );

		return $this->parser->eventyay_location_text_value( $value );
	}

	/**
	 * Build public Eventyay URL.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Event slug.
	 * @return string
	 */
	public function eventyay_public_event_url( $event, $settings, $event_slug ) {
		$url = $this->parser->eventyay_url_value(
			$this->parser->eventyay_first_present_raw(
				$event,
				array(
					'url',
					'frontend_url',
					'frontend-url',
					'public_url',
					'public-url',
					'web_url',
					'web-url',
					'absolute_url',
					'absolute-url',
					'event_url',
					'event-url',
					'registration_url',
					'registration-url',
				),
				true
			),
			$settings['base_url']
		);

		if ( $url ) {
			return $url;
		}

		$url = trailingslashit( $settings['base_url'] ) . rawurlencode( $settings['organizer_slug'] ) . '/' . rawurlencode( $event_slug ) . '/';

		return esc_url_raw(
			apply_filters(
				'wpfaevent_eventyay_import_event_url',
				$url,
				$event,
				$settings
			)
		);
	}

	/**
	 * Format an Eventyay date-time value as a date.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return string
	 */
	public function format_eventyay_date( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'Y-m-d' );
	}

	/**
	 * Format an Eventyay date-time value as a time.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return string
	 */
	public function format_eventyay_time( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'H:i' );
	}

	/**
	 * Build a timezone object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone Timezone identifier.
	 * @return DateTimeZone|null
	 */
	public function eventyay_timezone_object( $timezone ) {
		$timezone = Wpfaevent_Meta_Event::sanitize_timezone( $timezone );

		if ( '' === $timezone ) {
			return null;
		}

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Update or delete post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	public function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Download a remote image and set it as the post thumbnail.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $image_url  Remote image URL.
	 * @return int|false Attachment ID or false on failure.
	 */
	public function sideload_event_featured_image( $post_id, $image_url ) {
		$image_url = trim( (string) $image_url );
		if ( ! $image_url ) {
			return false;
		}

		// Prevent re-downloading if the image URL hasn't changed.
		$existing_banner = get_post_meta( $post_id, '_eventyay_imported_banner_url', true );
		if ( $existing_banner === $image_url && has_post_thumbnail( $post_id ) ) {
			return get_post_thumbnail_id( $post_id );
		}

		// Temporarily allow local hostnames during validation and download.
		add_filter( 'http_request_host_is_external', '__return_true' );

		if ( ! $this->parser->is_valid_http_url( $image_url ) || ! wp_http_validate_url( $image_url ) ) {
			remove_filter( 'http_request_host_is_external', '__return_true' );
			return false;
		}

		// Include media framework files if not loaded.
		if ( ! function_exists( 'media_handle_sideload' ) || ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// Download the remote URL to a temporary file.
		$tmp_file = download_url( $image_url );

		// Remove the temporary host filter.
		remove_filter( 'http_request_host_is_external', '__return_true' );

		if ( is_wp_error( $tmp_file ) ) {
			return false;
		}

		// Extract filename.
		$file_name = basename( wp_parse_url( $image_url, PHP_URL_PATH ) );
		if ( ! $file_name ) {
			$file_name = 'event-banner.jpg';
		}

		// Construct file array.
		$file_array = array(
			'name'     => $file_name,
			'tmp_name' => $tmp_file,
		);

		// Upload the file to uploads and create attachment.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Clean up the temporary file.
		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
			return false;
		}

		// Set post thumbnail.
		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, '_eventyay_imported_banner_url', $image_url );

		return $attachment_id;
	}
}
