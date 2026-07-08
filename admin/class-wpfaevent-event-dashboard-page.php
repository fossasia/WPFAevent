<?php
/**
 * Event dashboard admin page controller.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register and render the per-event dashboard page.
 */
class Wpfaevent_Event_Dashboard_Page {

	/**
	 * Dashboard page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'wpfaevent-event-dashboard';

	/**
	 * Nonce action prefix.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wpfaevent_view_event_dashboard_';

	/**
	 * Dashboard data provider.
	 *
	 * @var Wpfaevent_Event_Dashboard_Data
	 */
	private $data_provider;

	/**
	 * Dashboard synchronization service.
	 *
	 * @var Wpfaevent_Event_Dashboard_Sync_Service
	 */
	private $sync_service;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Event_Dashboard_Data|null         $data_provider Dashboard data provider.
	 * @param Wpfaevent_Event_Dashboard_Sync_Service|null $sync_service  Dashboard sync service.
	 */
	public function __construct( $data_provider = null, $sync_service = null ) {
		$this->data_provider = $data_provider instanceof Wpfaevent_Event_Dashboard_Data ? $data_provider : new Wpfaevent_Event_Dashboard_Data();
		$this->sync_service  = $sync_service instanceof Wpfaevent_Event_Dashboard_Sync_Service ? $sync_service : new Wpfaevent_Event_Dashboard_Sync_Service();
	}

	/**
	 * Register the hidden event dashboard admin page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Event Dashboard', 'wpfaevent' ),
			esc_html__( 'Event Dashboard', 'wpfaevent' ),
			'edit_events',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Hide the dashboard page from the Events submenu.
	 *
	 * @return void
	 */
	public function hide_submenu() {
		remove_submenu_page( 'edit.php?post_type=wpfa_event', self::PAGE_SLUG );
	}

	/**
	 * Add the "View Dashboard" row action to Events list rows.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param WP_Post               $post    Current post object.
	 * @return array<string, string>
	 */
	public function add_row_action( $actions, $post ) {
		if ( ! $post instanceof WP_Post || 'wpfa_event' !== $post->post_type ) {
			return $actions;
		}

		if ( ! $this->current_user_can_access_event( $post->ID ) ) {
			return $actions;
		}

		$actions['wpfaevent_view_dashboard'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $this->get_dashboard_url( $post->ID ) ),
			esc_html__( 'View Dashboard', 'wpfaevent' )
		);

		return $actions;
	}

	/**
	 * Render the event dashboard page.
	 *
	 * @return void
	 */
	public function render_page() {
		$event_id = $this->get_validated_event_id();

		if ( ! $event_id ) {
			wp_die(
				esc_html__( 'The requested event dashboard could not be loaded.', 'wpfaevent' ),
				esc_html__( 'Access denied', 'wpfaevent' ),
				array( 'response' => 403 )
			);
		}

		$dashboard_data   = $this->data_provider->get_event_dashboard_data( $event_id );
		$dashboard_notice = $this->consume_notice();
		$dashboard_url    = $this->get_dashboard_url( $event_id );
		$module_urls      = $this->get_module_urls( $event_id, $dashboard_data );
		$sync_action_url  = $this->get_sync_action_url();
		$sync_ajax_url    = admin_url( 'admin-ajax.php' );

		require WPFAEVENT_PATH . 'admin/partials/event-dashboard.php';
	}

	/**
	 * Handle the synchronize action from the event dashboard.
	 *
	 * @return void
	 */
	public function handle_sync() {
		$event_id = $this->get_posted_sync_event_id();

		if ( ! $event_id ) {
			wp_die(
				esc_html__( 'The synchronize request is invalid.', 'wpfaevent' ),
				esc_html__( 'Access denied', 'wpfaevent' ),
				array( 'response' => 403 )
			);
		}

		$result = $this->run_sync_request( $event_id );

		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();

			if ( is_array( $error_data ) && ! empty( $error_data['status'] ) && 403 === absint( $error_data['status'] ) ) {
				wp_die(
					esc_html( $result->get_error_message() ),
					esc_html__( 'Access denied', 'wpfaevent' ),
					array( 'response' => 403 )
				);
			}

			$this->store_notice(
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				)
			);
		} else {
			$this->store_notice(
				array(
					'type'    => 'success',
					'message' => $this->build_sync_success_message( $result ),
				)
			);
		}

		wp_safe_redirect( $this->get_dashboard_url( $event_id ) );
		exit;
	}

	/**
	 * Handle the dashboard synchronize action over AJAX.
	 *
	 * @return void
	 */
	public function handle_sync_ajax() {
		$event_id = $this->get_posted_sync_event_id();
		$result   = $this->run_sync_request( $event_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				$this->json_error_status( $result )
			);
		}

		wp_send_json_success(
			array(
				'message'       => $this->build_sync_success_message( $result ),
				'dashboard_url' => $this->get_dashboard_url( $event_id ),
				'result'        => $result,
			)
		);
	}

	/**
	 * Get an event dashboard URL.
	 *
	 * @param int $event_id Event post ID.
	 * @return string
	 */
	public function get_dashboard_url( $event_id ) {
		$event_id = absint( $event_id );

		return wp_nonce_url(
			add_query_arg(
				array(
					'post_type' => 'wpfa_event',
					'page'      => self::PAGE_SLUG,
					'event_id'  => $event_id,
				),
				admin_url( 'edit.php' )
			),
			self::NONCE_ACTION . $event_id
		);
	}

	/**
	 * Get the synchronize form action URL.
	 *
	 * @return string
	 */
	public function get_sync_action_url() {
		return admin_url( 'admin-post.php?action=wpfaevent_sync_event_dashboard' );
	}

	/**
	 * Validate the current event dashboard request and return the event ID.
	 *
	 * @return int
	 */
	private function get_validated_event_id() {
		if ( ! Wpfaevent_Roles::current_user_can_manage_dashboard() ) {
			return 0;
		}

		$event_id = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;
		$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $event_id || '' === $nonce ) {
			return 0;
		}

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION . $event_id ) ) {
			return 0;
		}

		if ( 'wpfa_event' !== get_post_type( $event_id ) ) {
			return 0;
		}

		if ( ! $this->current_user_can_access_event( $event_id ) ) {
			return 0;
		}

		return $event_id;
	}

	/**
	 * Whether the current user can access one event dashboard.
	 *
	 * @param int $event_id Event post ID.
	 * @return bool
	 */
	private function current_user_can_access_event( $event_id ) {
		$event_id = absint( $event_id );

		if ( ! $event_id ) {
			return false;
		}

		return Wpfaevent_Roles::current_user_can_manage_dashboard() && current_user_can( 'edit_post', $event_id );
	}

	/**
	 * Get validated sync request event ID from POST data.
	 *
	 * @return int
	 */
	private function get_posted_sync_event_id() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in run_sync_request() before the value is acted upon.
		return isset( $_POST['event_id'] ) ? absint( wp_unslash( $_POST['event_id'] ) ) : 0;
	}

	/**
	 * Run one dashboard sync request after validating permissions and nonce.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<string, mixed>|WP_Error
	 */
	private function run_sync_request( $event_id ) {
		$event_id = absint( $event_id );
		$nonce    = isset( $_POST['wpfaevent_sync_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfaevent_sync_nonce'] ) ) : '';

		if ( ! $event_id || ! wp_verify_nonce( $nonce, 'wpfaevent_sync_event_dashboard_' . $event_id ) ) {
			return new WP_Error(
				'wpfaevent_dashboard_invalid_sync_request',
				esc_html__( 'The synchronize request is invalid.', 'wpfaevent' ),
				array( 'status' => 403 )
			);
		}

		if ( ! $this->current_user_can_access_event( $event_id ) || ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			return new WP_Error(
				'wpfaevent_dashboard_sync_forbidden',
				esc_html__( 'You are not allowed to synchronize this event.', 'wpfaevent' ),
				array( 'status' => 403 )
			);
		}

		$overwrite_logo = ! empty( $_POST['overwrite_existing_logo'] );

		return $this->sync_service->sync_event( $event_id, $overwrite_logo );
	}

	/**
	 * Store one dashboard notice for the current user.
	 *
	 * @param array<string, string> $notice Notice payload.
	 * @return void
	 */
	private function store_notice( $notice ) {
		set_transient( $this->get_notice_transient_key(), $notice, MINUTE_IN_SECONDS * 5 );
	}

	/**
	 * Read and delete the pending dashboard notice for the current user.
	 *
	 * @return array<string, string>|null
	 */
	private function consume_notice() {
		$key    = $this->get_notice_transient_key();
		$notice = get_transient( $key );

		if ( false === $notice || ! is_array( $notice ) ) {
			return null;
		}

		delete_transient( $key );

		return $notice;
	}

	/**
	 * Build a transient key for per-user dashboard notices.
	 *
	 * @return string
	 */
	private function get_notice_transient_key() {
		return 'wpfaevent_dashboard_notice_' . get_current_user_id();
	}

	/**
	 * Build contextual admin URLs for dashboard module cards.
	 *
	 * @param int                 $event_id        Event post ID.
	 * @param array<string,mixed> $dashboard_data  Dashboard data payload.
	 * @return array<string, string>
	 */
	private function get_module_urls( $event_id, $dashboard_data ) {
		$event_id       = absint( $event_id );
		$dashboard_url  = $this->get_dashboard_url( $event_id );
		$event_edit_url = isset( $dashboard_data['event']['edit_url'] ) ? (string) $dashboard_data['event']['edit_url'] : '';

		return array(
			'speakers' => add_query_arg(
				array(
					'post_type'               => 'wpfa_speaker',
					'wpfaevent_speaker_scope' => 'event',
					'wpfa_speaker_event'      => $event_id,
				),
				admin_url( 'edit.php' )
			),
			'sessions' => $dashboard_url . '#wpfaevent-sessions',
			'tracks'   => $event_edit_url ? $event_edit_url . '#tagsdiv-wpfa_event_track' : $dashboard_url . '#wpfaevent-tracks',
			'settings' => $event_edit_url ? $event_edit_url : $dashboard_url . '#wpfaevent-settings',
			'source'   => $dashboard_url . '#wpfaevent-source',
			'sync'     => $dashboard_url . '#wpfaevent-sync',
		);
	}

	/**
	 * Resolve an HTTP status for an AJAX error response.
	 *
	 * @param WP_Error $error Error object.
	 * @return int
	 */
	private function json_error_status( $error ) {
		$data = is_wp_error( $error ) ? $error->get_error_data() : array();

		return is_array( $data ) && ! empty( $data['status'] ) ? absint( $data['status'] ) : 400;
	}

	/**
	 * Build a friendly synchronization success message.
	 *
	 * @param array<string, mixed> $result Sync result.
	 * @return string
	 */
	private function build_sync_success_message( $result ) {
		$message = sprintf(
			/* translators: 1: sessions count, 2: speakers count, 3: tracks count. */
			esc_html__( 'Event synchronized. Updated %1$d sessions, %2$d speakers, and %3$d tracks.', 'wpfaevent' ),
			isset( $result['sessions'] ) ? absint( $result['sessions'] ) : 0,
			isset( $result['speakers'] ) ? absint( $result['speakers'] ) : 0,
			isset( $result['tracks'] ) ? absint( $result['tracks'] ) : 0
		);

		if ( ! empty( $result['logo_overwritten'] ) ) {
			$message .= ' ' . esc_html__( 'The event logo was updated from Eventyay.', 'wpfaevent' );
		}

		return $message;
	}
}
