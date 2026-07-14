<?php
/**
 * Event dashboard data provider.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build normalized event dashboard data for admin views.
 */
class Wpfaevent_Event_Dashboard_Data {

	/**
	 * Dashboard JSON storage helper.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Eventyay_Dashboard_Store|null $store Dashboard store.
	 */
	public function __construct( $store = null ) {
		$this->store = $store instanceof Wpfaevent_Eventyay_Dashboard_Store ? $store : new Wpfaevent_Eventyay_Dashboard_Store();
	}

	/**
	 * Get event dashboard data.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, mixed>
	 */
	public function get_event_dashboard_data( $event_id ) {
		$event_id = absint( $event_id );

		$site_settings = $this->read_json( 'site-settings-' . $event_id . '.json', array() );
		$speakers      = $this->read_json( 'speakers-' . $event_id . '.json', array() );
		$schedule      = $this->read_json( 'schedule-' . $event_id . '.json', array() );
		$sponsors      = $this->read_json( 'sponsors-' . $event_id . '.json', array() );
		$exhibitors    = $this->read_json( 'exhibitors-' . $event_id . '.json', array() );

		$site_settings = is_array( $site_settings ) ? $site_settings : array();
		$speakers      = is_array( $speakers ) ? $speakers : array();
		$schedule      = is_array( $schedule ) ? $schedule : array();
		$sponsors      = is_array( $sponsors ) ? $sponsors : array();
		$exhibitors    = is_array( $exhibitors ) ? $exhibitors : array();
		$sessions      = $this->get_schedule_sessions( $schedule );
		$tracks        = $this->get_track_names( $event_id, $sessions );

		$db_speaker_ids = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_admin_event_speaker_ids( $event_id ) : array();
		$speakers_list  = array();
		if ( ! empty( $db_speaker_ids ) ) {
			$speaker_posts = get_posts(
				array(
					'post_type'      => 'wpfa_speaker',
					'post_status'    => 'any',
					'post__in'       => $db_speaker_ids,
					'posts_per_page' => -1,
					'orderby'        => 'post__in',
				)
			);
			foreach ( $speaker_posts as $sp_post ) {
				$position     = get_post_meta( $sp_post->ID, 'wpfa_speaker_position', true );
				$organization = get_post_meta( $sp_post->ID, 'wpfa_speaker_organization', true );
				$headshot_url = get_post_meta( $sp_post->ID, 'wpfa_speaker_headshot_url', true );
				if ( empty( $headshot_url ) ) {
					$headshot_url = get_the_post_thumbnail_url( $sp_post->ID, 'thumbnail' );
				}
				$featured_ids = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_event_featured_speaker_ids( $event_id ) : array();
				$is_featured  = in_array( $sp_post->ID, $featured_ids, true );

				$speakers_list[] = array(
					'name'         => $sp_post->post_title,
					'title'        => $position,
					'organization' => $organization,
					'image'        => $headshot_url,
					'featured'     => $is_featured,
				);
			}
		}

		if ( empty( $speakers_list ) ) {
			$speakers_list = $this->get_dashboard_speakers( $speakers );
		}

		$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
		$assets        = $this->get_event_assets(
			$event_id,
			$site_settings,
			$site_logo_url
		);

		$event_speaker_ids    = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_event_speaker_ids( $event_id ) : array();
		$featured_speaker_ids = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::resolve_event_featured_speaker_ids( $event_id, $event_speaker_ids, $speakers ) : array();
		$custom_tabs          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_custom_tabs( get_post_meta( $event_id, 'wpfa_event_custom_tabs', true ) ) : array();

		$section_visibility = $this->normalize_section_visibility(
			isset( $site_settings['section_visibility'] ) ? $site_settings['section_visibility'] : array()
		);
		$about_content      = isset( $site_settings['about_section_content'] ) ? trim( wp_strip_all_tags( (string) $site_settings['about_section_content'] ) ) : '';
		$registration_link  = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_registration_link', true ) );
		$cfs_link           = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_cfs_link', true ) );

		return array(
			'event'      => array(
				'id'            => $event_id,
				'title'         => get_the_title( $event_id ),
				'status'        => get_post_status( $event_id ),
				'edit_url'      => get_edit_post_link( $event_id, 'raw' ),
				'view_url'      => get_permalink( $event_id ),
				'start_date'    => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) ),
				'end_date'      => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) ),
				'location'      => sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) ),
				'time'          => $this->get_event_time_summary( $event_id ),
				'modified'      => get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $event_id, true ),
				'created'       => get_post_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), true, $event_id ),
				'tracks'        => $this->get_term_names( $event_id, 'wpfa_event_track' ),
				'tags'          => $this->get_term_names( $event_id, 'wpfa_event_tag' ),
				'event_url'     => esc_url_raw( get_post_meta( $event_id, 'wpfa_event_url', true ) ),
				'register_url'  => $registration_link,
				'cfs_url'       => $cfs_link,
				'speaker_count' => count( $event_speaker_ids ),
				'languages'     => class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_language_list( get_post_meta( $event_id, 'wpfa_event_languages', true ) ) : array(),
				'description'   => get_post_field( 'post_content', $event_id ),
			),
			'stats'      => array(
				array(
					'label' => __( 'Linked speakers', 'wpfaevent' ),
					'value' => count( $event_speaker_ids ),
					'help'  => __( 'Speaker posts attached to this event.', 'wpfaevent' ),
				),
				array(
					'label' => __( 'Featured speakers', 'wpfaevent' ),
					'value' => count( $featured_speaker_ids ),
					'help'  => __( 'Featured speaker records resolved from event data and dashboard JSON.', 'wpfaevent' ),
				),
				array(
					'label' => __( 'Schedule rows', 'wpfaevent' ),
					'value' => count( $sessions ),
					'help'  => __( 'Imported or saved schedule entries for this event.', 'wpfaevent' ),
				),
				array(
					'label' => __( 'Sponsors', 'wpfaevent' ),
					'value' => $this->count_sponsors( $sponsors ),
					'help'  => __( 'Sponsor items stored in dashboard data.', 'wpfaevent' ),
				),
				array(
					'label' => __( 'Exhibitors', 'wpfaevent' ),
					'value' => count( $exhibitors ),
					'help'  => __( 'Exhibitor items stored in dashboard data.', 'wpfaevent' ),
				),
				array(
					'label' => __( 'Tracks', 'wpfaevent' ),
					'value' => count( $tracks ),
					'help'  => __( 'Resolved from event tracks and imported sessions.', 'wpfaevent' ),
				),
			),
			'sections'   => array(
				'visibility'       => $section_visibility,
				'custom_tab_count' => count( $custom_tabs ),
				'about_excerpt'    => $about_content,
			),
			'import'     => array(
				'source'           => $this->get_import_source_label( $event_id ),
				'is_eventyay'      => $this->is_eventyay_event( $event_id ),
				'event_id'         => sanitize_text_field( get_post_meta( $event_id, '_wpfa_eventyay_event_id', true ) ),
				'organizer_slug'   => sanitize_text_field( get_post_meta( $event_id, '_wpfa_eventyay_organizer_slug', true ) ),
				'event_slug'       => sanitize_text_field( get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ) ),
				'last_imported_at' => $this->format_meta_datetime( get_post_meta( $event_id, '_wpfa_eventyay_last_imported_at', true ) ),
				'last_synced_at'   => $this->format_meta_datetime( get_post_meta( $event_id, '_wpfa_eventyay_last_synced_at', true ) ),
				'last_program_at'  => $this->format_meta_datetime( get_post_meta( $event_id, '_wpfa_eventyay_speakers_synced_at', true ) ),
			),
			'sync'       => array(
				'status'           => $this->get_sync_status( $event_id ),
				'can_sync'         => $this->is_eventyay_event( $event_id ) && Wpfaevent_Roles::current_user_can_import_eventyay(),
				'eventyay_api_url' => ! empty( $site_settings['eventyay_api_url'] ) ? esc_url_raw( $site_settings['eventyay_api_url'] ) : '',
			),
			'settings'   => array(
				'eventyay_api_url' => ! empty( $site_settings['eventyay_api_url'] ) ? esc_url_raw( $site_settings['eventyay_api_url'] ) : '',
				'reg_button_text'  => ! empty( $site_settings['reg_button_text'] ) ? sanitize_text_field( $site_settings['reg_button_text'] ) : '',
				'reg_button_link'  => ! empty( $site_settings['reg_button_link'] ) ? esc_url_raw( $site_settings['reg_button_link'] ) : '',
				'event_logo_url'   => ! empty( $site_settings['event_logo_url'] ) ? esc_url_raw( $site_settings['event_logo_url'] ) : '',
				'hero_image_url'   => ! empty( $site_settings['hero_image_url'] ) ? esc_url_raw( $site_settings['hero_image_url'] ) : '',
			),
			'speakers'   => $speakers_list,
			'sponsors'   => $sponsors,
			'exhibitors' => $exhibitors,
			'sessions'   => array_slice( $sessions, 0, 6 ),
			'tracks'     => $tracks,
			'assets'     => $assets,
			'resources'  => array(
				array(
					'label'   => __( 'Site settings', 'wpfaevent' ),
					'file'    => 'site-settings-' . $event_id . '.json',
					'present' => ! empty( $site_settings ),
					'summary' => $this->get_site_settings_summary( $site_settings ),
				),
				array(
					'label'   => __( 'Speakers', 'wpfaevent' ),
					'file'    => 'speakers-' . $event_id . '.json',
					'present' => ! empty( $speakers ),
					'summary' => sprintf(
						/* translators: %d: speaker count. */
						_n( '%d speaker record', '%d speaker records', count( $speakers ), 'wpfaevent' ),
						count( $speakers )
					),
				),
				array(
					'label'   => __( 'Schedule', 'wpfaevent' ),
					'file'    => 'schedule-' . $event_id . '.json',
					'present' => ! empty( $schedule ),
					'summary' => sprintf(
						/* translators: %d: schedule row count. */
						_n( '%d schedule row', '%d schedule rows', $this->count_schedule_rows( $schedule ), 'wpfaevent' ),
						$this->count_schedule_rows( $schedule )
					),
				),
				array(
					'label'   => __( 'Sponsors', 'wpfaevent' ),
					'file'    => 'sponsors-' . $event_id . '.json',
					'present' => ! empty( $sponsors ),
					'summary' => sprintf(
						/* translators: %d: sponsor count. */
						_n( '%d sponsor', '%d sponsors', $this->count_sponsors( $sponsors ), 'wpfaevent' ),
						$this->count_sponsors( $sponsors )
					),
				),
				array(
					'label'   => __( 'Exhibitors', 'wpfaevent' ),
					'file'    => 'exhibitors-' . $event_id . '.json',
					'present' => ! empty( $exhibitors ),
					'summary' => sprintf(
						/* translators: %d: exhibitor count. */
						_n( '%d exhibitor', '%d exhibitors', count( $exhibitors ), 'wpfaevent' ),
						count( $exhibitors )
					),
				),
			),
		);
	}

	/**
	 * Read a dashboard JSON file.
	 *
	 * @param string $filename File name.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	private function read_json( $filename, $fallback ) {
		return $this->store->read_dashboard_json_file( $filename, $fallback );
	}

	/**
	 * Normalize section visibility flags.
	 *
	 * @param mixed $visibility Raw visibility configuration.
	 * @return array<string, bool>
	 */
	private function normalize_section_visibility( $visibility ) {
		$defaults = array(
			'about'      => true,
			'speakers'   => true,
			'schedule'   => true,
			'sponsors'   => true,
			'exhibitors' => true,
		);

		if ( ! is_array( $visibility ) ) {
			return $defaults;
		}

		foreach ( $defaults as $key => $default_value ) {
			$defaults[ $key ] = ! array_key_exists( $key, $visibility ) ? $default_value : ! empty( $visibility[ $key ] );
		}

		return $defaults;
	}

	/**
	 * Get taxonomy term names for an event.
	 *
	 * @param int    $event_id  Event post ID.
	 * @param string $taxonomy  Taxonomy slug.
	 * @return array<int, string>
	 */
	private function get_term_names( $event_id, $taxonomy ) {
		$terms = get_the_terms( $event_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static function ( $term ) {
						if ( ! $term instanceof WP_Term ) {
							return '';
						}

						return sanitize_text_field( $term->name );
					},
					$terms
				)
			)
		);
	}

	/**
	 * Count schedule rows from stored dashboard data.
	 *
	 * @param mixed $schedule Stored schedule data.
	 * @return int
	 */
	private function count_schedule_rows( $schedule ) {
		if ( ! is_array( $schedule ) || empty( $schedule ) ) {
			return 0;
		}

		if ( isset( $schedule['rows'] ) && is_numeric( $schedule['rows'] ) ) {
			return max( 0, absint( $schedule['rows'] ) - 1 );
		}

		if ( isset( $schedule['data'] ) && is_array( $schedule['data'] ) ) {
			return count( $schedule['data'] );
		}

		if ( isset( $schedule[0] ) && is_array( $schedule[0] ) ) {
			return max( 0, count( $schedule ) - 1 );
		}

		return count( $schedule );
	}

	/**
	 * Build a short event time summary.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function get_event_time_summary( $event_id ) {
		$all_day    = '1' === get_post_meta( $event_id, 'wpfa_event_all_day', true );
		$start_time = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_time', true ) );
		$end_time   = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_time', true ) );

		if ( $all_day ) {
			return __( 'All day', 'wpfaevent' );
		}

		if ( '' !== $start_time && '' !== $end_time ) {
			return $start_time . ' - ' . $end_time;
		}

		return $start_time ? $start_time : __( 'Not set', 'wpfaevent' );
	}

	/**
	 * Get normalized session rows from saved schedule data.
	 *
	 * @param array $schedule Schedule payload.
	 * @return array<int, array<string, string>>
	 */
	private function get_schedule_sessions( $schedule ) {
		if ( ! is_array( $schedule ) ) {
			return array();
		}

		if ( ! empty( $schedule['sessions'] ) && is_array( $schedule['sessions'] ) ) {
			return array_values(
				array_filter(
					$schedule['sessions'],
					static function ( $session ) {
						return is_array( $session ) && ! empty( $session['title'] );
					}
				)
			);
		}

		if ( empty( $schedule['data'] ) || ! is_array( $schedule['data'] ) ) {
			return array();
		}

		$sessions = array();

		foreach ( array_slice( $schedule['data'], 1 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$sessions[] = array(
				'date'     => isset( $row[0] ) ? sanitize_text_field( $row[0] ) : '',
				'time'     => isset( $row[1] ) ? sanitize_text_field( $row[1] ) : '',
				'title'    => isset( $row[2] ) ? sanitize_text_field( $row[2] ) : '',
				'speakers' => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
				'track'    => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
				'room'     => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
			);
		}

		return array_values(
			array_filter(
				$sessions,
				static function ( $session ) {
					return ! empty( $session['title'] );
				}
			)
		);
	}

	/**
	 * Get a compact list of dashboard speakers.
	 *
	 * @param array $speakers Raw speakers payload.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_dashboard_speakers( $speakers ) {
		if ( ! is_array( $speakers ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $speakers as $speaker ) {
			if ( ! is_array( $speaker ) || empty( $speaker['name'] ) ) {
				continue;
			}

			$normalized[] = array(
				'name'         => sanitize_text_field( $speaker['name'] ),
				'title'        => ! empty( $speaker['title'] ) ? sanitize_text_field( $speaker['title'] ) : '',
				'organization' => ! empty( $speaker['organization'] ) ? sanitize_text_field( $speaker['organization'] ) : '',
				'image'        => ! empty( $speaker['image'] ) ? esc_url_raw( $speaker['image'] ) : '',
				'featured'     => ! empty( $speaker['featured'] ),
			);
		}

		return $normalized;
	}

	/**
	 * Get unique track names from taxonomy terms and sessions.
	 *
	 * @param int   $event_id  Event post ID.
	 * @param array $sessions  Normalized schedule sessions.
	 * @return array<int, string>
	 */
	private function get_track_names( $event_id, $sessions ) {
		$tracks = $this->get_term_names( $event_id, 'wpfa_event_track' );

		foreach ( $sessions as $session ) {
			if ( ! empty( $session['track'] ) ) {
				$tracks[] = sanitize_text_field( $session['track'] );
			}
		}

		$tracks = array_values( array_unique( array_filter( $tracks ) ) );
		sort( $tracks );

		return $tracks;
	}

	/**
	 * Build event asset cards from saved settings and post media.
	 *
	 * @param int    $event_id      Event post ID.
	 * @param array  $site_settings Site settings JSON.
	 * @param string $site_logo_url Global site logo URL.
	 * @return array<int, array<string, string>>
	 */
	private function get_event_assets( $event_id, $site_settings, $site_logo_url ) {
		$featured_image_url = get_the_post_thumbnail_url( $event_id, 'large' );
		$featured_image_id  = get_post_thumbnail_id( $event_id );
		$assets             = array(
			array(
				'label'  => __( 'Event logo', 'wpfaevent' ),
				'url'    => ! empty( $site_settings['event_logo_url'] ) ? esc_url_raw( $site_settings['event_logo_url'] ) : '',
				'source' => ! empty( $site_settings['event_logo_url'] ) ? __( 'Dashboard settings', 'wpfaevent' ) : '',
			),
			array(
				'label'  => __( 'Header image', 'wpfaevent' ),
				'url'    => ! empty( $site_settings['hero_image_url'] ) ? esc_url_raw( $site_settings['hero_image_url'] ) : '',
				'source' => ! empty( $site_settings['hero_image_url'] ) ? __( 'Dashboard settings', 'wpfaevent' ) : '',
			),
			array(
				'label'  => __( 'Featured image', 'wpfaevent' ),
				'url'    => $featured_image_url ? esc_url_raw( $featured_image_url ) : '',
				'source' => $featured_image_id ? __( 'WordPress featured image', 'wpfaevent' ) : '',
			),
			array(
				'label'  => __( 'Fallback site logo', 'wpfaevent' ),
				'url'    => $site_logo_url ? esc_url_raw( $site_logo_url ) : '',
				'source' => $site_logo_url ? __( 'Global plugin setting', 'wpfaevent' ) : '',
			),
		);

		return array_values(
			array_filter(
				$assets,
				static function ( $asset ) {
					if ( in_array( $asset['label'], array( __( 'Event logo', 'wpfaevent' ), __( 'Header image', 'wpfaevent' ) ), true ) ) {
						return true;
					}
					return ! empty( $asset['url'] );
				}
			)
		);
	}

	/**
	 * Get a friendly import source label.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function get_import_source_label( $event_id ) {
		return $this->is_eventyay_event( $event_id ) ? __( 'Eventyay', 'wpfaevent' ) : __( 'Manual / local content', 'wpfaevent' );
	}

	/**
	 * Whether the event is linked to Eventyay metadata.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	private function is_eventyay_event( $event_id ) {
		return '' !== trim( (string) get_post_meta( $event_id, '_wpfa_eventyay_event_slug', true ) );
	}

	/**
	 * Format stored sync/import timestamps.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	private function format_meta_datetime( $value ) {
		if ( is_numeric( $value ) ) {
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $value ) );
		}

		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( '' === $value ) {
			return '';
		}

		$timestamp = strtotime( $value );

		return $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : sanitize_text_field( $value );
	}

	/**
	 * Get a simple sync status summary.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	private function get_sync_status( $event_id ) {
		if ( ! $this->is_eventyay_event( $event_id ) ) {
			return __( 'No external source linked', 'wpfaevent' );
		}

		$last_sync = get_post_meta( $event_id, '_wpfa_eventyay_last_synced_at', true );
		if ( '' !== trim( (string) $last_sync ) ) {
			return __( 'Eventyay source connected', 'wpfaevent' );
		}

		$last_import = get_post_meta( $event_id, '_wpfa_eventyay_last_imported_at', true );

		return '' !== trim( (string) $last_import ) ? __( 'Imported, waiting for dashboard sync', 'wpfaevent' ) : __( 'Source linked but not imported yet', 'wpfaevent' );
	}

	/**
	 * Count sponsors from sponsor groups.
	 *
	 * @param mixed $sponsors Stored sponsor group data.
	 * @return int
	 */
	private function count_sponsors( $sponsors ) {
		if ( ! is_array( $sponsors ) || empty( $sponsors ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $sponsors as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			if ( ! empty( $group['sponsors'] ) && is_array( $group['sponsors'] ) ) {
				$count += count( $group['sponsors'] );
			}
		}

		return $count;
	}

	/**
	 * Build a short site settings summary.
	 *
	 * @param array<string, mixed> $site_settings Site settings.
	 * @return string
	 */
	private function get_site_settings_summary( $site_settings ) {
		if ( empty( $site_settings ) ) {
			return __( 'No dashboard settings saved yet.', 'wpfaevent' );
		}

		$visible_sections = 0;
		$section_flags    = $this->normalize_section_visibility(
			isset( $site_settings['section_visibility'] ) ? $site_settings['section_visibility'] : array()
		);

		foreach ( $section_flags as $is_visible ) {
			if ( $is_visible ) {
				++$visible_sections;
			}
		}

		return sprintf(
			/* translators: %d: visible section count. */
			_n( '%d visible section', '%d visible sections', $visible_sections, 'wpfaevent' ),
			$visible_sections
		);
	}
}
