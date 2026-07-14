<?php
/**
 * Partner Dashboard Statistics helper.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard_Statistics
 */
class Wpfaevent_Partner_Dashboard_Statistics {

	/**
	 * Storage helper.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->store = new Wpfaevent_Eventyay_Dashboard_Store();
	}

	/**
	 * Retrieve all events.
	 *
	 * @return array<int, string> Map of event ID to event title.
	 */
	public function get_events() {
		$events = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$event_options = array();
		foreach ( $events as $event ) {
			$event_options[ $event->ID ] = $event->post_title;
		}

		return $event_options;
	}

	/**
	 * Load flat records array for sponsors or exhibitors of a given event.
	 *
	 * @param string $type     Accepts 'sponsor' or 'exhibitor'.
	 * @param int    $event_id Event ID.
	 * @return array
	 */
	public function load_records( $type, $event_id ) {
		if ( ! $event_id ) {
			return array();
		}

		if ( 'sponsor' === $type ) {
			$raw_groups = $this->store->read_dashboard_json_file( 'sponsors-' . $event_id . '.json', array() );
			$raw_groups = is_array( $raw_groups ) ? $raw_groups : array();
			$sponsors   = array();

			foreach ( $raw_groups as $group ) {
				if ( ! is_array( $group ) || empty( $group['sponsors'] ) || ! is_array( $group['sponsors'] ) ) {
					continue;
				}
				$group_name = isset( $group['group_name'] ) ? $group['group_name'] : '';
				foreach ( $group['sponsors'] as $sponsor ) {
					if ( is_array( $sponsor ) ) {
						if ( ! isset( $sponsor['type'] ) ) {
							$sponsor['type'] = $group_name;
						}
						$sponsors[] = $sponsor;
					}
				}
			}

			return $sponsors;
		} else {
			$exhibitors = $this->store->read_dashboard_json_file( 'exhibitors-' . $event_id . '.json', array() );

			return is_array( $exhibitors ) ? $exhibitors : array();
		}
	}

	/**
	 * Calculate active and inactive counts for stats.
	 *
	 * @param array $records Records list.
	 * @return array<string, int> Map of count types to count values.
	 */
	public function get_active_inactive_stats( $records ) {
		$active_count   = 0;
		$inactive_count = 0;
		foreach ( $records as $rec ) {
			$status = isset( $rec['status'] ) ? $rec['status'] : 'active';
			if ( 'inactive' === $status ) {
				++$inactive_count;
			} else {
				++$active_count;
			}
		}

		return array(
			'active'   => $active_count,
			'inactive' => $inactive_count,
		);
	}

	/**
	 * Filter, search, and sort records.
	 *
	 * @param array  $records       Unfiltered records.
	 * @param string $type          Sponsor or exhibitor.
	 * @param string $search_query  Search query.
	 * @param string $status_filter Status filter value.
	 * @param string $cat_filter    Category filter value.
	 * @param string $orderby       Field to order by.
	 * @param string $order         Asc or desc order.
	 * @return array Filtered and sorted records.
	 */
	public function filter_and_sort_records( $records, $type, $search_query, $status_filter, $cat_filter, $orderby, $order ) {
		$filtered_records = array();

		foreach ( $records as $rec ) {
			$rec_cat = isset( $rec['type'] ) ? $rec['type'] : '';

			// Apply Search.
			if ( $search_query ) {
				$name_match          = isset( $rec['name'] ) && false !== stripos( $rec['name'], $search_query );
				$company_match       = isset( $rec['company'] ) && false !== stripos( $rec['company'], $search_query );
				$email_match         = isset( $rec['email'] ) && false !== stripos( $rec['email'], $search_query );
				$contact_email_match = isset( $rec['contact_email'] ) && false !== stripos( $rec['contact_email'], $search_query );
				if ( ! $name_match && ! $company_match && ! $email_match && ! $contact_email_match ) {
					continue;
				}
			}

			// Apply Status Filter.
			if ( $status_filter ) {
				$status = isset( $rec['status'] ) ? $rec['status'] : 'active';
				if ( $status !== $status_filter ) {
					continue;
				}
			}

			// Apply Category/Package Filter.
			if ( $cat_filter ) {
				if ( $rec_cat !== $cat_filter ) {
					continue;
				}
			}

			$filtered_records[] = $rec;
		}

		// Sorting.
		usort(
			$filtered_records,
			function ( $a, $b ) use ( $orderby, $order ) {
				$val_a = '';
				$val_b = '';

				if ( 'name' === $orderby ) {
					$val_a = isset( $a['name'] ) ? $a['name'] : '';
					$val_b = isset( $b['name'] ) ? $b['name'] : '';
				} elseif ( 'company' === $orderby ) {
					$val_a = isset( $a['company'] ) ? $a['company'] : '';
					$val_b = isset( $b['company'] ) ? $b['company'] : '';
				} elseif ( 'status' === $orderby ) {
					$val_a = isset( $a['status'] ) ? $a['status'] : 'active';
					$val_b = isset( $b['status'] ) ? $b['status'] : 'active';
				} elseif ( 'date' === $orderby ) {
					$val_a = isset( $a['created_at'] ) ? $a['created_at'] : '';
					$val_b = isset( $b['created_at'] ) ? $b['created_at'] : '';
				}

				if ( 'asc' === $order ) {
					return strcasecmp( $val_a, $val_b );
				} else {
					return strcasecmp( $val_b, $val_a );
				}
			}
		);

		return $filtered_records;
	}
}
