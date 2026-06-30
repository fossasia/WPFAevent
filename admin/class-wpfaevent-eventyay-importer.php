<?php
/**
 * Eventyay import and dashboard sync functionality.
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
 * Handles Eventyay settings, imports, and dashboard syncs.
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
	 * Speaker Repository.
	 *
	 * @var Wpfaevent_Speaker_Repository
	 */
	private $speaker_repo;

	/**
	 * Partner Store.
	 *
	 * @var Wpfaevent_Partner_Json_Store
	 */
	private $store;

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
		$this->client       = new Wpfaevent_Eventyay_API_Client();
		$this->parser       = new Wpfaevent_JSONAPI_Parser();
		$this->event_repo   = new Wpfaevent_Event_Repository();
		$this->speaker_repo = new Wpfaevent_Speaker_Repository();
		$this->store        = new Wpfaevent_Partner_Json_Store();
		$this->renderer     = new Wpfaevent_Admin_Settings_Renderer( $this );
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
	public function get_event_repository() {
		return $this->event_repo;
	}

	/**
	 * Get the speaker repository instance.
	 *
	 * @return Wpfaevent_Speaker_Repository
	 */
	public function get_speaker_repository() {
		return $this->speaker_repo;
	}

	/**
	 * Get the partner JSON store instance.
	 *
	 * @return Wpfaevent_Partner_Json_Store
	 */
	public function get_partner_store() {
		return $this->store;
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
	 * @since 1.0.0
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
	 * Import Eventyay events using the saved newer REST API settings.
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
						/* translators: 1: fetched events, 2: created events, 3: updated events, 4: skipped events, 5: sessions, 6: speakers, 7: sponsors, 8: exhibitors, 9: schedule rows, 10: about updates, 11: skipped program imports, 12: skipped sponsor/exhibitor imports. */
						esc_html__( 'Fetched %1$d Eventyay event(s). Created %2$d, updated %3$d, skipped %4$d. Imported %5$d session(s), %6$d speaker(s), %7$d sponsor(s), %8$d exhibitor(s), %9$d schedule row(s), and updated %10$d about section(s); skipped program import for %11$d event(s) and sponsor/exhibitor import for %12$d event(s).', 'wpfaevent' ),
						absint( $result['fetched'] ),
						absint( $result['created'] ),
						absint( $result['updated'] ),
						absint( $result['skipped'] ),
						absint( $result['sessions'] ),
						absint( $result['speakers'] ),
						absint( $result['sponsors'] ),
						absint( $result['exhibitors'] ),
						absint( $result['schedule_rows'] ),
						absint( $result['about_updates'] ),
						absint( $result['program_skipped'] ),
						absint( $result['partner_skipped'] )
					),
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=wpfa_event&page=' . $return_page ) );
		exit;
	}

	/**
	 * Get default Eventyay import settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_default_settings() {
		return array(
			'base_url'       => 'https://eventyay.com',
			'organizer_slug' => '',
			'event_slug'     => '',
			'api_token'      => '',
			'post_status'    => 'draft',
		);
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
	public function sanitize_eventyay_path_segment( $value ) {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );

		return preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
	}

	/**
	 * Parse a public Eventyay event URL into root URL and path slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Public Eventyay URL.
	 * @return array<string, string>
	 */
	public function parse_eventyay_public_event_url( $url ) {
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
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Import result.
	 */
	public function import_eventyay_events_from_settings() {
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
			'fetched'          => count( $events ),
			'created'          => 0,
			'updated'          => 0,
			'skipped'          => 0,
			'sessions'         => 0,
			'speakers'         => 0,
			'sponsors'         => 0,
			'exhibitors'       => 0,
			'created_speakers' => 0,
			'updated_speakers' => 0,
			'about_updates'    => 0,
			'schedule_rows'    => 0,
			'program_skipped'  => 0,
			'partner_skipped'  => 0,
		);

		foreach ( $events as $event ) {
			$event_res = $this->import_single_eventyay_event( $event, $settings );
			if ( is_wp_error( $event_res ) ) {
				++$result['skipped'];
				continue;
			}

			$result['created']          += absint( $event_res['created'] );
			$result['updated']          += absint( $event_res['updated'] );
			$result['skipped']          += absint( $event_res['skipped'] );
			$result['sessions']         += absint( $event_res['sessions'] );
			$result['speakers']         += absint( $event_res['speakers'] );
			$result['sponsors']         += absint( $event_res['sponsors'] );
			$result['exhibitors']       += absint( $event_res['exhibitors'] );
			$result['created_speakers'] += absint( $event_res['created_speakers'] );
			$result['updated_speakers'] += absint( $event_res['updated_speakers'] );
			$result['about_updates']    += absint( $event_res['about_updates'] );
			$result['schedule_rows']    += absint( $event_res['schedule_rows'] );
			$result['program_skipped']  += absint( $event_res['program_skipped'] );
			$result['partner_skipped']  += absint( $event_res['partner_skipped'] );
		}

		return $result;
	}

	/**
	 * Import a single Eventyay event's details, partner data, and program.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event    Event data array.
	 * @param array $settings Import settings.
	 * @return array|WP_Error Import results.
	 */
	public function import_single_eventyay_event( $event, $settings ) {
		$result = array(
			'created'          => 0,
			'updated'          => 0,
			'skipped'          => 0,
			'sessions'         => 0,
			'speakers'         => 0,
			'sponsors'         => 0,
			'exhibitors'       => 0,
			'created_speakers' => 0,
			'updated_speakers' => 0,
			'about_updates'    => 0,
			'schedule_rows'    => 0,
			'program_skipped'  => 0,
			'partner_skipped'  => 0,
		);

		$upsert = $this->event_repo->upsert_eventyay_event_post( $event, $settings );

		if ( is_wp_error( $upsert ) ) {
			$result['skipped'] = 1;
			return $upsert;
		}

		if ( ! empty( $upsert['created'] ) ) {
			$result['created'] = 1;
		} else {
			$result['updated'] = 1;
		}

		$dashboard = $this->sync_eventyay_event_dashboard_data( $upsert['id'], $event, $settings, $upsert['event_slug'] );
		if ( is_wp_error( $dashboard ) ) {
			$result['program_skipped'] = 1;
		} else {
			$result['about_updates'] += absint( $dashboard['about_updated'] );
		}

		$partners = $this->import_eventyay_event_partner_data( $upsert['id'], $event, $settings, $upsert['event_slug'] );
		if ( is_wp_error( $partners ) ) {
			$result['partner_skipped'] = 1;
		} else {
			$result['sponsors']        += absint( $partners['sponsor_count'] );
			$result['exhibitors']      += absint( $partners['exhibitor_count'] );
			$result['partner_skipped'] += absint( $partners['skipped'] );
		}

		$program = $this->import_eventyay_event_program( $upsert['id'], $settings, $upsert['event_slug'] );
		if ( is_wp_error( $program ) ) {
			$result['program_skipped'] = 1;
		} else {
			$result['sessions']         += absint( $program['session_count'] );
			$result['speakers']         += absint( $program['speaker_count'] );
			$result['created_speakers'] += absint( $program['created_speakers'] );
			$result['updated_speakers'] += absint( $program['updated_speakers'] );
			$result['schedule_rows']    += absint( $program['schedule_rows'] );
		}

		return $result;
	}

	/**
	 * Sync imported Eventyay event details into dashboard JSON data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Dashboard sync result.
	 */
	private function sync_eventyay_event_dashboard_data( $event_id, $event, $settings, $event_slug ) {
		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_dashboard_event',
				esc_html__( 'Could not update dashboard data for an imported Eventyay event without an event ID.', 'wpfaevent' )
			);
		}

		$settings_file      = 'site-settings-' . $event_id . '.json';
		$dashboard_settings = $this->store->read_dashboard_json_file( $settings_file, array() );
		$dashboard_settings = is_array( $dashboard_settings ) ? $dashboard_settings : array();
		$description        = $this->parser->eventyay_event_description( $event );
		$event_url          = $this->parser->eventyay_public_event_url( $event, $settings, $event_slug );
		$about_updated      = 0;

		$default_section_visibility = array(
			'about'      => true,
			'speakers'   => true,
			'schedule'   => true,
			'sponsors'   => true,
			'exhibitors' => true,
		);

		if ( empty( $dashboard_settings['section_visibility'] ) || ! is_array( $dashboard_settings['section_visibility'] ) ) {
			$dashboard_settings['section_visibility'] = $default_section_visibility;
		} else {
			$dashboard_settings['section_visibility'] = wp_parse_args( $dashboard_settings['section_visibility'], $default_section_visibility );
		}

		if ( '' !== trim( $description ) ) {
			$dashboard_settings['about_section_content'] = wp_kses_post( $description );
			$about_updated                               = 1;
		} elseif ( ! isset( $dashboard_settings['about_section_content'] ) ) {
			$dashboard_settings['about_section_content'] = '';
		}

		if ( empty( $dashboard_settings['reg_button_text'] ) ) {
			$dashboard_settings['reg_button_text'] = __( 'Get Tickets', 'wpfaevent' );
		}

		if ( $event_url ) {
			$dashboard_settings['reg_button_link'] = esc_url_raw( $event_url );
		}

		$write_result = $this->store->write_dashboard_json_file( $settings_file, $dashboard_settings );
		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		return array(
			'about_updated' => $about_updated,
		);
	}

	/**
	 * Import Eventyay sponsors and exhibitors into event-specific dashboard JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_event_partner_data( $event_id, $event, $settings, $event_slug ) {
		$result = array(
			'sponsor_count'   => 0,
			'exhibitor_count' => 0,
			'skipped'         => 0,
		);

		$sponsors = $this->client->fetch_eventyay_partner_collection( $settings, $event, $event_slug, 'sponsors' );
		if ( is_wp_error( $sponsors ) ) {
			++$result['skipped'];
		} else {
			$normalized_sponsors = $this->parser->normalize_eventyay_sponsor_resources( $sponsors['resources'], $settings );
			$existing_sponsors   = $this->store->read_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', array() );
			$sponsor_groups      = $this->parser->merge_eventyay_sponsor_groups( $normalized_sponsors, $existing_sponsors );
			$write_result        = $this->store->write_dashboard_json_file( 'sponsors-' . absint( $event_id ) . '.json', $sponsor_groups );

			if ( is_wp_error( $write_result ) ) {
				return $write_result;
			}

			$result['sponsor_count'] = count( $normalized_sponsors );
		}

		$exhibitors = $this->client->fetch_eventyay_partner_collection( $settings, $event, $event_slug, 'exhibitors' );
		if ( is_wp_error( $exhibitors ) ) {
			++$result['skipped'];
		} else {
			$normalized_exhibitors = $this->parser->normalize_eventyay_exhibitor_resources( $exhibitors['resources'], $settings );
			$existing_exhibitors   = $this->store->read_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', array() );
			$merged_exhibitors     = $this->parser->merge_eventyay_flat_records( $normalized_exhibitors, $existing_exhibitors );
			$write_result          = $this->store->write_dashboard_json_file( 'exhibitors-' . absint( $event_id ) . '.json', $merged_exhibitors );

			if ( is_wp_error( $write_result ) ) {
				return $write_result;
			}

			$result['exhibitor_count'] = count( $normalized_exhibitors );
		}

		return $result;
	}

	/**
	 * Import speakers and session data for an Eventyay event.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $event_id   Imported WordPress event post ID.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_event_program( $event_id, $settings, $event_slug ) {
		$endpoint = $this->client->build_eventyay_submissions_endpoint( $settings, $event_slug );
		$program  = array(
			'speakers'      => array(),
			'sessions'      => array(),
			'session_count' => 0,
		);
		$error    = null;

		if ( is_wp_error( $endpoint ) ) {
			$error = $endpoint;
		} else {
			$fetched = $this->client->fetch_eventyay_program_resources( $endpoint, $settings );
			if ( is_wp_error( $fetched ) ) {
				$error = $fetched;
			} else {
				$program = $this->parser->normalize_eventyay_submissions_payload( $fetched['submissions'], $settings, $event_slug );
			}
		}

		$speakers = $this->client->fetch_eventyay_event_speaker_program( $settings, $event_slug );
		if ( is_wp_error( $speakers ) ) {
			if ( null === $error ) {
				$error = $speakers;
			}
		} else {
			$program = $this->parser->merge_eventyay_program_payloads( $program, $speakers );
		}

		$slots = $this->client->fetch_eventyay_event_slot_program( $settings, $event_slug );
		if ( is_wp_error( $slots ) ) {
			if ( null === $error ) {
				$error = $slots;
			}
		} else {
			$program = $this->parser->merge_eventyay_program_payloads( $program, $slots );
		}

		if ( $error && empty( $program['speakers'] ) && empty( $program['sessions'] ) ) {
			return $error;
		}

		$cpt_result = array(
			'created' => 0,
			'updated' => 0,
		);

		$existing_speakers  = $this->store->read_dashboard_json_file( 'speakers-' . absint( $event_id ) . '.json', array() );
		$dashboard_speakers = $this->parser->merge_dashboard_speaker_state( $program['speakers'], $existing_speakers );
		$write_result       = $this->store->write_dashboard_json_file( 'speakers-' . absint( $event_id ) . '.json', $dashboard_speakers );

		if ( is_wp_error( $write_result ) ) {
			return $write_result;
		}

		$cpt_result = $this->speaker_repo->sync_eventyay_speaker_posts( $dashboard_speakers, $event_id );

		$schedule_rows = $this->event_repo->write_eventyay_schedule_table( $event_id, $program['sessions'] );
		if ( is_wp_error( $schedule_rows ) ) {
			return $schedule_rows;
		}

		return array(
			'speaker_count'    => count( $program['speakers'] ),
			'session_count'    => $program['session_count'],
			'created_speakers' => $cpt_result['created'],
			'updated_speakers' => $cpt_result['updated'],
			'schedule_rows'    => $schedule_rows,
		);
	}
}
