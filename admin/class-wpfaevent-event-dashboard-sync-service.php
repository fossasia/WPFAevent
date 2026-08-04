<?php
/**
 * Event dashboard synchronization service.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronize one dashboard-backed event with its Eventyay source.
 */
class Wpfaevent_Event_Dashboard_Sync_Service {

	/**
	 * Dashboard JSON store.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Eventyay importer.
	 *
	 * @var Wpfaevent_Eventyay_Importer
	 */
	private $importer;

	/**
	 * Eventyay parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Eventyay_Dashboard_Store|null $store    Dashboard store.
	 * @param Wpfaevent_Eventyay_Importer|null        $importer Eventyay importer.
	 * @param Wpfaevent_JSONAPI_Parser|null           $parser   Eventyay parser.
	 */
	public function __construct( $store = null, $importer = null, $parser = null ) {
		$this->store    = $store instanceof Wpfaevent_Eventyay_Dashboard_Store ? $store : new Wpfaevent_Eventyay_Dashboard_Store();
		$this->importer = $importer instanceof Wpfaevent_Eventyay_Importer ? $importer : new Wpfaevent_Eventyay_Importer();
		$this->parser   = $parser instanceof Wpfaevent_JSONAPI_Parser ? $parser : new Wpfaevent_JSONAPI_Parser();
	}

	/**
	 * Synchronize one event from Eventyay.
	 *
	 * @param int  $event_id        Event post ID.
	 * @param bool $overwrite_logo  Whether to overwrite the saved event logo.
	 * @return array<string, mixed>|WP_Error
	 */
	public function sync_event( $event_id, $overwrite_logo = false ) {
		$event_id = absint( $event_id );

		if ( ! $event_id || 'wpfa_event' !== get_post_type( $event_id ) ) {
			return new WP_Error(
				'wpfaevent_dashboard_invalid_event',
				esc_html__( 'The selected event is invalid.', 'wpfaevent' )
			);
		}

		$settings = $this->build_eventyay_settings_for_event( $event_id );
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		$event = $this->importer->fetch_single_eventyay_event_from_settings( $settings );
		if ( is_wp_error( $event ) ) {
			return $event;
		}

		$result = $this->importer->import_single_eventyay_event( $event, $settings, $event_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$media_update = $this->update_dashboard_media_settings( $event_id, $event, $settings, $overwrite_logo );
		if ( is_wp_error( $media_update ) ) {
			return $media_update;
		}

		update_post_meta( $event_id, '_wpfa_eventyay_last_synced_at', current_time( 'mysql', true ) );

		$result['import_source']    = 'eventyay';
		$result['media_updated']    = ! empty( $media_update['updated'] );
		$result['logo_overwritten'] = ! empty( $media_update['logo_overwritten'] );

		return $result;
	}

	/**
	 * Build Eventyay import settings for one event from global settings and event meta.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, mixed>|WP_Error
	 */
	private function build_eventyay_settings_for_event( $event_id ) {
		$settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
		$settings = wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			$this->importer->get_eventyay_import_default_settings()
		);

		$organizer_slug = sanitize_text_field( get_post_meta( $event_id, '_wpfa_eventyay_organizer_slug', true ) );
		$event_slug     = sanitize_text_field( get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ) );

		if ( '' === $organizer_slug || '' === $event_slug ) {
			return new WP_Error(
				'wpfaevent_dashboard_missing_source',
				esc_html__( 'This event is not linked to an Eventyay import source yet.', 'wpfaevent' )
			);
		}

		$settings['organizer_slug'] = $organizer_slug;
		$settings['event_slug']     = $event_slug;

		return $settings;
	}

	/**
	 * Update dashboard media settings from an imported Eventyay event.
	 *
	 * @param int   $event_id       Event post ID.
	 * @param array $event          Eventyay event payload.
	 * @param array $settings       Eventyay import settings.
	 * @param bool  $overwrite_logo Whether to overwrite the saved event logo.
	 * @return array<string, bool>|WP_Error
	 */
	private function update_dashboard_media_settings( $event_id, $event, $settings, $overwrite_logo ) {
		$settings_file      = 'site-settings-' . absint( $event_id ) . '.json';
		$dashboard_settings = $this->store->read_dashboard_json_file( $settings_file, array() );
		$dashboard_settings = is_array( $dashboard_settings ) ? $dashboard_settings : array();
		$media              = $this->extract_event_media_urls( $event, $settings );
		$updated            = false;
		$logo_overwritten   = false;

		if ( $overwrite_logo && ! empty( $media['event_logo_url'] ) ) {
			$dashboard_settings['event_logo_url'] = $media['event_logo_url'];
			$updated                              = true;
			$logo_overwritten                     = true;
		}

		if ( ! empty( $media['hero_image_url'] ) && ( empty( $dashboard_settings['hero_image_url'] ) || $dashboard_settings['hero_image_url'] !== $media['hero_image_url'] ) ) {
			$dashboard_settings['hero_image_url'] = $media['hero_image_url'];
			$updated                              = true;
		}

		if ( ! $updated ) {
			return array(
				'updated'          => false,
				'logo_overwritten' => false,
			);
		}

		$write_result = $this->store->write_dashboard_json_file( $settings_file, $dashboard_settings );
		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		return array(
			'updated'          => true,
			'logo_overwritten' => $logo_overwritten,
		);
	}

	/**
	 * Extract likely event media URLs from an Eventyay event resource.
	 *
	 * @param array $event    Eventyay event resource.
	 * @param array $settings Eventyay import settings.
	 * @return array<string, string>
	 */
	private function extract_event_media_urls( $event, $settings ) {
		$logo = $this->parser->eventyay_url_value(
			$this->first_present_value(
				$event,
				array(
					'logo_url',
					'logo-url',
					'logo',
					'icon_image_url',
					'icon-image-url',
					'icon',
				)
			),
			$settings['base_url']
		);

		$hero = $this->parser->eventyay_url_value(
			$this->first_present_value(
				$event,
				array(
					'hero_image_url',
					'hero-image-url',
					'header_image_url',
					'header-image-url',
					'banner_url',
					'banner-url',
					'cover_image_url',
					'cover-image-url',
					'original_image_url',
					'original-image-url',
					'large_image_url',
					'large-image-url',
					'image_url',
					'image-url',
					'image',
				)
			),
			$settings['base_url']
		);

		return array(
			'event_logo_url' => esc_url_raw( $logo ),
			'hero_image_url' => esc_url_raw( $hero ),
		);
	}

	/**
	 * Get the first non-empty scalar/array value for a list of keys.
	 *
	 * @param array $data Source array.
	 * @param array $keys Candidate keys.
	 * @return mixed
	 */
	private function first_present_value( $data, $keys ) {
		if ( ! is_array( $data ) ) {
			return '';
		}

		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = $data[ $key ];

			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return $value;
			}

			if ( is_array( $value ) && ! empty( $value ) ) {
				return $value;
			}
		}

		return '';
	}
}
