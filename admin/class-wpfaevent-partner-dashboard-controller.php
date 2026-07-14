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
	 */
	public function __construct() {
		$this->store = new Wpfaevent_Eventyay_Dashboard_Store();
		$this->stats = new Wpfaevent_Partner_Dashboard_Statistics();
	}

	/**
	 * POST Handler to Save Sponsor/Exhibitor.
	 */
	public function handle_save_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		check_admin_referer( 'wpfaevent_save_partner_' . $id );

		$type     = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

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
		$status      = isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ? 'inactive' : 'active';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		if ( ! $name ) {
			wp_die( esc_html__( 'The Name field is required.', 'wpfaevent' ) );
		}

		$records = $this->stats->load_records( $type, $event_id );

		$is_new = false;
		if ( ! $id ) {
			$is_new = true;
			$id     = 'manual-' . $type . '-' . wp_generate_password( 8, false );
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
			$updated_records[] = $new_record;
		}

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			// Structure as sponsor groups.
			$groups = array();
			foreach ( $updated_records as $rec ) {
				$group_name = isset( $rec['type'] ) && $rec['type'] ? $rec['type'] : __( 'Sponsors', 'wpfaevent' );
				if ( ! isset( $groups[ $group_name ] ) ) {
					$groups[ $group_name ] = array(
						'group_name' => $group_name,
						'logo_size'  => 160,
						'sponsors'   => array(),
					);
					if ( isset( $rec['source'] ) && 'eventyay' === $rec['source'] ) {
						$groups[ $group_name ]['source'] = 'eventyay';
					}
				}
				$groups[ $group_name ]['sponsors'][] = $rec;
			}

			// Clean array keys for JSON serialization.
			$write_data = array_values( $groups );
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
	 * GET Handler to Delete Sponsor/Exhibitor.
	 */
	public function handle_delete_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$id = isset( $_GET['id'] ) ? sanitize_key( $_GET['id'] ) : '';
		check_admin_referer( 'wpfaevent_delete_partner_' . $id );

		$type     = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;

		if ( ! $event_id || ! $id || ! in_array( $type, array( 'sponsor', 'exhibitor' ), true ) ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$records         = $this->stats->load_records( $type, $event_id );
		$updated_records = array();

		foreach ( $records as $rec ) {
			if ( isset( $rec['id'] ) && $rec['id'] === $id ) {
				continue; // Skip the deleted record.
			}
			$updated_records[] = $rec;
		}

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			// Structure as sponsor groups.
			$groups = array();
			foreach ( $updated_records as $rec ) {
				$group_name = isset( $rec['type'] ) && $rec['type'] ? $rec['type'] : __( 'Sponsors', 'wpfaevent' );
				if ( ! isset( $groups[ $group_name ] ) ) {
					$groups[ $group_name ] = array(
						'group_name' => $group_name,
						'logo_size'  => 160,
						'sponsors'   => array(),
					);
				}
				$groups[ $group_name ]['sponsors'][] = $rec;
			}

			$write_data = array_values( $groups );
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
}
