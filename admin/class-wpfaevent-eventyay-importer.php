<?php
/**
 * Eventyay event import functionality.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Eventyay settings and event post imports.
 *
 * Acts as the orchestrator/facade for the decoupled modular classes.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Eventyay_Importer {

	/**
	 * API Client.
	 *
	 * @var Wpfaevent_Eventyay_API_Client
	 */
	private $client;

	/**
	 * Parser.
	 *
	 * @var Wpfaevent_JSONAPI_Parser
	 */
	private $parser;

	/**
	 * Event Repository.
	 *
	 * @var Wpfaevent_Event_Repository
	 */
	private $event_repo;

	/**
	 * Settings Renderer.
	 *
	 * @var Wpfaevent_Admin_Settings_Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client     = new Wpfaevent_Eventyay_API_Client();
		$this->parser     = new Wpfaevent_JSONAPI_Parser();
		$this->event_repo = new Wpfaevent_Event_Repository( $this->parser );
		$this->renderer   = new Wpfaevent_Admin_Settings_Renderer( $this );
	}

	/**
	 * Get the API client instance.
	 *
	 * @return Wpfaevent_Eventyay_API_Client
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Get the JSONAPI parser instance.
	 *
	 * @return Wpfaevent_JSONAPI_Parser
	 */
	public function get_parser() {
		return $this->parser;
	}

	/**
	 * Get the event repository instance.
	 *
	 * @return Wpfaevent_Event_Repository
	 */
	public function get_event_repo() {
		return $this->event_repo;
	}

	/**
	 * Sanitize Eventyay import options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input Raw option input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_eventyay_import_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->get_eventyay_import_default_settings();
		$current  = $this->get_eventyay_import_settings();
		$settings = $defaults;

		$base_url = isset( $input['base_url'] ) ? trim( (string) wp_unslash( $input['base_url'] ) ) : '';
		$base_url = $base_url ? esc_url_raw( $base_url ) : $defaults['base_url'];

		if ( ! wp_http_validate_url( $base_url ) ) {
			add_settings_error(
				'wpfaevent_eventyay_import',
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL must be a valid HTTP(S) URL.', 'wpfaevent' ),
				'error'
			);
			$base_url = $current['base_url'];
		}

		$settings['organizer_slug'] = isset( $input['organizer_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['organizer_slug'] ) : '';
		$settings['event_slug']     = isset( $input['event_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['event_slug'] ) : '';
		$parsed_event_url           = $this->parse_eventyay_public_event_url( $base_url );

		if ( $parsed_event_url ) {
			$base_url = $parsed_event_url['base_url'];

			if ( empty( $settings['organizer_slug'] ) ) {
				$settings['organizer_slug'] = $parsed_event_url['organizer_slug'];
			}

			if ( empty( $settings['event_slug'] ) ) {
				$settings['event_slug'] = $parsed_event_url['event_slug'];
			}
		}

		$settings['base_url'] = untrailingslashit( $base_url );

		if ( ! empty( $input['clear_api_token'] ) ) {
			$settings['api_token'] = '';
		} elseif ( isset( $input['api_token'] ) && '' !== trim( (string) wp_unslash( $input['api_token'] ) ) ) {
			$settings['api_token'] = sanitize_text_field( wp_unslash( $input['api_token'] ) );
		} else {
			$settings['api_token'] = isset( $current['api_token'] ) ? $current['api_token'] : '';
		}

		$post_status = isset( $input['post_status'] ) ? sanitize_key( wp_unslash( $input['post_status'] ) ) : $defaults['post_status'];
		if ( ! in_array( $post_status, array( 'draft', 'publish', 'pending', 'private' ), true ) ) {
			$post_status = $defaults['post_status'];
		}
		$settings['post_status'] = $post_status;

		return $settings;
	}

	/**
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		$this->renderer->render_settings_page();
	}

	/**
	 * Render the Eventyay update page.
	 *
	 * @since 1.0.0
	 */
	public function render_update_events_page() {
		$this->renderer->render_update_events_page();
	}

	/**
	 * Import Eventyay events using the saved newer REST API settings (Synchronous POST Fallback).
	 *
	 * @since 1.0.0
	 */
	public function handle_eventyay_events_import() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to import Eventyay events.', 'wpfaevent' ) );
		}

		check_admin_referer( 'wpfaevent_import_eventyay_events' );

		$return_page = 'wpfaevent-import-events';
		if ( isset( $_POST['wpfaevent_eventyay_return_page'] ) ) {
			$return_page = sanitize_key( wp_unslash( $_POST['wpfaevent_eventyay_return_page'] ) );
		}

		if ( ! in_array( $return_page, array( 'wpfaevent-import-events', 'wpfaevent-update-events' ), true ) ) {
			$return_page = 'wpfaevent-import-events';
		}

		$result     = $this->import_eventyay_events_from_settings();
		$notice_key = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();

		if ( is_wp_error( $result ) ) {
			set_transient(
				$notice_key,
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				),
				MINUTE_IN_SECONDS
			);
		} else {
			set_transient(
				$notice_key,
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: fetched events, 2: created events, 3: updated events, 4: skipped events. */
						esc_html__( 'Fetched %1$d Eventyay event(s). Created %2$d, updated %3$d, skipped %4$d.', 'wpfaevent' ),
						absint( $result['fetched'] ),
						absint( $result['created'] ),
						absint( $result['updated'] ),
						absint( $result['skipped'] )
					),
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=wpfa_event&page=' . $return_page ) );
		exit;
	}

	/**
	 * Import a single event (called via AJAX chunked import).
	 *
	 * @since 1.0.0
	 *
	 * @param array $event    Event data.
	 * @param array $settings Settings options.
	 * @return array|WP_Error
	 */
	public function import_single_eventyay_event( $event, $settings ) {
		return $this->event_repo->upsert_eventyay_event_post( $event, $settings );
	}

	/**
	 * Get default Eventyay import settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_default_settings() {
		return $this->client->get_eventyay_import_default_settings();
	}

	/**
	 * Get Eventyay import settings with defaults applied.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_settings() {
		$settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
	}

	/**
	 * Sanitize a path segment used by Eventyay organizer and event slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw path segment.
	 * @return string
	 */
	private function sanitize_eventyay_path_segment( $value ) {
		return $this->client->sanitize_eventyay_path_segment( $value );
	}

	/**
	 * Parse a public Eventyay event URL into root URL and path slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Public Eventyay URL.
	 * @return array<string, string>
	 */
	private function parse_eventyay_public_event_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
			return array();
		}

		$path = trim( $parts['path'], '/' );
		if ( '' === $path || 0 === strpos( $path, 'api/' ) || 0 === strpos( $path, 'v1/' ) ) {
			return array();
		}

		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( count( $segments ) < 2 ) {
			return array();
		}

		$organizer_slug = $this->sanitize_eventyay_path_segment( $segments[0] );
		$event_slug     = $this->sanitize_eventyay_path_segment( $segments[1] );

		if ( empty( $organizer_slug ) || empty( $event_slug ) ) {
			return array();
		}

		$base_url = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$base_url .= ':' . absint( $parts['port'] );
		}

		return array(
			'base_url'       => esc_url_raw( $base_url ),
			'organizer_slug' => $organizer_slug,
			'event_slug'     => $event_slug,
		);
	}

	/**
	 * Import Eventyay event resources from saved settings.
	 *
	 * Called by ReflectionMethod in WP-CLI headless executions.
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_events_from_settings() {
		$settings = $this->get_eventyay_import_settings();

		if ( empty( $settings['organizer_slug'] ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'Please save an Eventyay organizer slug before importing.', 'wpfaevent' )
			);
		}

		$fetched = $this->client->fetch_eventyay_event_resources( $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		$events = isset( $fetched['events'] ) && is_array( $fetched['events'] ) ? $fetched['events'] : array();
		if ( empty( $events ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_no_events',
				esc_html__( 'No Eventyay events were returned by the configured endpoint.', 'wpfaevent' )
			);
		}

		$result = array(
			'fetched' => count( $events ),
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
		);

		foreach ( $events as $event ) {
			$upsert = $this->event_repo->upsert_eventyay_event_post( $event, $settings );

			if ( is_wp_error( $upsert ) ) {
				++$result['skipped'];
				continue;
			}

			if ( ! empty( $upsert['created'] ) ) {
				++$result['created'];
			} else {
				++$result['updated'];
			}
		}

		return $result;
	}
}
