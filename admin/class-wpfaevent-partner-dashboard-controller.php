<?php
/**
 * Partner Dashboard Controller helper.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard_Controller
 */
class Wpfaevent_Partner_Dashboard_Controller {

	/**
	 * Query arg used to display reorder notices after redirects.
	 */
	const REORDER_NOTICE_QUERY_ARG = 'wpfaevent_reorder_status';

	/**
	 * Storage helper.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Statistics provider.
	 *
	 * @var Wpfaevent_Partner_Dashboard_Statistics
	 */
	private $stats;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Eventyay_Dashboard_Store|null     $store Optional dashboard store.
	 * @param Wpfaevent_Partner_Dashboard_Statistics|null $stats Optional statistics provider.
	 */
	public function __construct( $store = null, $stats = null ) {
		$this->store = $store instanceof Wpfaevent_Eventyay_Dashboard_Store ? $store : new Wpfaevent_Eventyay_Dashboard_Store();
		$this->stats = $stats instanceof Wpfaevent_Partner_Dashboard_Statistics ? $stats : new Wpfaevent_Partner_Dashboard_Statistics();
	}

	/**
	 * POST Handler to Save Sponsor/Exhibitor.
	 */
	public function handle_save_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_admin_referer() below.
		$id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		check_admin_referer( 'wpfaevent_save_partner_' . $id );

		$type     = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$event_id = isset( $_POST['event_id'] ) ? absint( wp_unslash( $_POST['event_id'] ) ) : 0;

		if ( ! $event_id || ! in_array( $type, array( 'sponsor', 'exhibitor' ), true ) ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$company     = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$logo        = isset( $_POST['logo'] ) ? esc_url_raw( wp_unslash( $_POST['logo'] ) ) : '';
		$link        = isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '';
		$cat         = isset( $_POST['cat'] ) ? sanitize_text_field( wp_unslash( $_POST['cat'] ) ) : '';
		$status_raw  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$status      = ( 'inactive' === $status_raw ) ? 'inactive' : 'active';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		if ( ! $name ) {
			wp_die( esc_html__( 'The Name field is required.', 'wpfaevent' ) );
		}

		$records = $this->stats->load_records( $type, $event_id );

		$is_new = false;
		if ( ! $id ) {
			$is_new = true;
			$id     = $this->generate_manual_partner_id( $type );
		}

		$new_record = array(
			'id'          => $id,
			'source'      => 'manual',
			'name'        => $name,
			'company'     => $company,
			'phone'       => $phone,
			'link'        => $link,
			'type'        => $cat,
			'status'      => $status,
			'description' => $description,
			'created_at'  => $is_new ? current_time( 'Y-m-d H:i:s' ) : '',
			'updated_at'  => current_time( 'Y-m-d H:i:s' ),
		);

		if ( 'sponsor' === $type ) {
			$new_record['email'] = $email;
			$new_record['image'] = $logo;
		} else {
			$new_record['contact_email'] = $email;
			$new_record['logo']          = $logo;
		}

		$updated_records = array();
		$found           = false;

		foreach ( $records as $rec ) {
			if ( isset( $rec['id'] ) && $rec['id'] === $id ) {
				$new_record['created_at'] = isset( $rec['created_at'] ) ? $rec['created_at'] : current_time( 'Y-m-d H:i:s' );
				$updated_records[]        = $new_record;
				$found                    = true;
			} else {
				$updated_records[] = $rec;
			}
		}

		if ( ! $found ) {
			if ( empty( $new_record['created_at'] ) ) {
				$new_record['created_at'] = current_time( 'Y-m-d H:i:s' );
			}
			$updated_records[] = $new_record;
		}

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			$existing_groups = $this->store->read_dashboard_json_file( 'sponsors-' . $event_id . '.json', array() );
			$write_data      = $this->build_sponsor_groups_from_records( $updated_records, $existing_groups );
			$this->store->write_dashboard_json_file( 'sponsors-' . $event_id . '.json', $write_data );
		} else {
			$this->store->write_dashboard_json_file( 'exhibitors-' . $event_id . '.json', $updated_records );
		}

		// Redirect back.
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'wpfa_event',
					'page'      => 'wpfaevent-' . $type . 's',
					'event_id'  => $event_id,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Generate a WordPress-safe identifier for manually created partners.
	 *
	 * The dashboard edit/delete flows read IDs back through sanitize_key(), so
	 * locally generated IDs must already be normalized to preserve lookups and
	 * nonce action strings across requests.
	 *
	 * @param string $type Partner type.
	 * @return string
	 */
	private function generate_manual_partner_id( $type ) {
		return sanitize_key( 'manual-' . $type . '-' . wp_generate_password( 8, false ) );
	}

	/**
	 * Save sponsor group order for an event.
	 *
	 * @return void
	 */
	public function handle_reorder_sponsor_groups() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( wp_unslash( $_POST['event_id'] ) ) : 0;
		check_admin_referer( 'wpfaevent_reorder_sponsor_groups_' . $event_id );

		if ( ! $event_id ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$raw_group_keys = isset( $_POST['group_keys'] ) ? wp_unslash( $_POST['group_keys'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		$group_keys     = array();

		if ( is_array( $raw_group_keys ) ) {
			foreach ( $raw_group_keys as $group_key ) {
				$group_key = sanitize_key( $group_key );

				if ( '' !== $group_key ) {
					$group_keys[] = $group_key;
				}
			}
		}

		$result = $this->process_reorder_sponsor_groups( $event_id, $group_keys );

		wp_safe_redirect( $result['redirect_url'] );
		exit;
	}

	/**
	 * GET Handler to Delete Sponsor/Exhibitor.
	 */
	public function handle_delete_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$id           = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
		$record_index = isset( $_GET['record_index'] ) ? absint( wp_unslash( $_GET['record_index'] ) ) : null;
		check_admin_referer( 'wpfaevent_delete_partner_' . $id . '_' . $record_index );

		$type     = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;

		if ( ! $event_id || ! $id || null === $record_index || ! in_array( $type, array( 'sponsor', 'exhibitor' ), true ) ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$records         = $this->stats->load_records( $type, $event_id );
		$updated_records = $this->remove_partner_record( $records, $id, $record_index );

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			$existing_groups = $this->store->read_dashboard_json_file( 'sponsors-' . $event_id . '.json', array() );
			$write_data      = $this->build_sponsor_groups_from_records( $updated_records, $existing_groups );
			$this->store->write_dashboard_json_file( 'sponsors-' . $event_id . '.json', $write_data );
		} else {
			$this->store->write_dashboard_json_file( 'exhibitors-' . $event_id . '.json', $updated_records );
		}

		// Redirect back.
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'wpfa_event',
					'page'      => 'wpfaevent-' . $type . 's',
					'event_id'  => $event_id,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Remove an indexed partner using the normalized key used by dashboard URLs.
	 *
	 * Legacy manually created partners may contain uppercase characters in their
	 * stored IDs. Normalize both sides so those records remain deletable, while
	 * the record index prevents collisions from removing multiple records.
	 *
	 * @param array  $records      Partner records.
	 * @param string $id           Requested partner ID.
	 * @param int    $record_index Requested record index.
	 * @return array
	 */
	private function remove_partner_record( $records, $id, $record_index ) {
		$id = sanitize_key( $id );

		if ( ! is_array( $records ) || '' === $id || ! isset( $records[ $record_index ] ) ) {
			return is_array( $records ) ? $records : array();
		}

		if ( is_array( $records[ $record_index ] ) && Wpfaevent_Partner_Helper::get_partner_key( $records[ $record_index ] ) === $id ) {
			unset( $records[ $record_index ] );
		}

		return array_values( $records );
	}

	/**
	 * Build grouped sponsor JSON data from flat sponsor records.
	 *
	 * @param array $records         Flat sponsor records.
	 * @param array $existing_groups Existing sponsor groups.
	 * @return array
	 */
	private function build_sponsor_groups_from_records( $records, $existing_groups ) {
		$existing_groups = is_array( $existing_groups ) ? $existing_groups : array();
		$groups          = array();
		$order           = array();
		$templates       = array();

		foreach ( $existing_groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$group_key = Wpfaevent_Partner_Helper::get_sponsor_group_key( $group );
			if ( '' === $group_key ) {
				continue;
			}

			$order[]                 = $group_key;
			$templates[ $group_key ] = $group;
		}

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$group_name = isset( $record['type'] ) && '' !== trim( (string) $record['type'] ) ? sanitize_text_field( $record['type'] ) : __( 'Sponsors', 'wpfaevent' );
			$group_key  = Wpfaevent_Partner_Helper::normalize_sponsor_group_key( $group_name );

			if ( '' === $group_key ) {
				continue;
			}

			if ( ! isset( $groups[ $group_key ] ) ) {
				$template = isset( $templates[ $group_key ] ) && is_array( $templates[ $group_key ] ) ? $templates[ $group_key ] : array();

				$groups[ $group_key ] = array(
					'group_name' => $group_name,
					'logo_size'  => isset( $template['logo_size'] ) ? absint( $template['logo_size'] ) : 160,
					'centered'   => ! empty( $template['centered'] ),
					'sponsors'   => array(),
				);

				if ( ! empty( $template['source'] ) ) {
					$groups[ $group_key ]['source'] = sanitize_key( $template['source'] );
				} elseif ( isset( $record['source'] ) && 'eventyay' === $record['source'] ) {
					$groups[ $group_key ]['source'] = 'eventyay';
				}

				if ( ! empty( $template['eventyay_group_key'] ) ) {
					$groups[ $group_key ]['eventyay_group_key'] = Wpfaevent_Partner_Helper::normalize_sponsor_group_key( $template['eventyay_group_key'] );
				}

				if ( ! in_array( $group_key, $order, true ) ) {
					$order[] = $group_key;
				}
			}

			$groups[ $group_key ]['sponsors'][] = $record;
		}

		$ordered_groups = array();

		foreach ( $order as $group_key ) {
			if ( isset( $groups[ $group_key ] ) ) {
				$ordered_groups[] = $groups[ $group_key ];
				unset( $groups[ $group_key ] );
			}
		}

		foreach ( $groups as $group ) {
			$ordered_groups[] = $group;
		}

		return $ordered_groups;
	}

	/**
	 * Reorder sponsor groups using submitted group keys.
	 *
	 * @param array $groups      Existing groups.
	 * @param array $group_order Submitted order.
	 * @return array
	 */
	private function reorder_sponsor_groups( $groups, $group_order ) {
		$groups      = is_array( $groups ) ? $groups : array();
		$group_order = is_array( $group_order ) ? $group_order : array();
		$group_map   = array();
		$ordered     = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$group_key = Wpfaevent_Partner_Helper::get_sponsor_group_key( $group );
			if ( '' === $group_key ) {
				continue;
			}

			$group_map[ $group_key ] = $group;
		}

		foreach ( $group_order as $group_key ) {
			if ( isset( $group_map[ $group_key ] ) ) {
				$ordered[] = $group_map[ $group_key ];
				unset( $group_map[ $group_key ] );
			}
		}

		foreach ( $group_map as $group ) {
			$ordered[] = $group;
		}

		return $ordered;
	}

	/**
	 * Persist reordered sponsor groups and build the post-redirect destination.
	 *
	 * @param int   $event_id   Event ID.
	 * @param array $group_keys Submitted group keys.
	 * @return array<string, mixed>
	 */
	private function process_reorder_sponsor_groups( $event_id, $group_keys ) {
		$existing_groups = $this->store->read_dashboard_json_file( 'sponsors-' . $event_id . '.json', array() );
		$reordered       = $this->reorder_sponsor_groups( $existing_groups, $group_keys );
		$write_result    = $this->store->write_dashboard_json_file( 'sponsors-' . $event_id . '.json', $reordered );
		$notice_status   = is_wp_error( $write_result ) ? 'error' : 'success';

		return array(
			'redirect_url' => add_query_arg(
				array(
					'post_type'                    => 'wpfa_event',
					'page'                         => 'wpfaevent-sponsors',
					'event_id'                     => $event_id,
					self::REORDER_NOTICE_QUERY_ARG => $notice_status,
				),
				admin_url( 'edit.php' )
			),
			'reordered'    => $reordered,
			'write_result' => $write_result,
		);
	}
}
