<?php
/**
 * Eventyay Admin Settings Renderer.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes/eventyay-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders admin layout partial templates.
 */
class Wpfaevent_Admin_Settings_Renderer {

	/**
	 * Importer instance.
	 *
	 * @var Wpfaevent_Eventyay_Importer
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * @param Wpfaevent_Eventyay_Importer $importer Importer instance.
	 */
	public function __construct( $importer ) {
		$this->importer = $importer;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$settings         = $this->importer->get_eventyay_import_settings();
		$endpoint_preview = ! empty( $settings['organizer_slug'] ) ? $this->importer->get_client()->build_eventyay_events_endpoint( $settings ) : '';
		$notice_key       = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();
		$notice           = get_transient( $notice_key );

		if ( $notice ) {
			delete_transient( $notice_key );
		}

		require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/partials/eventyay-import-settings.php';
	}

	/**
	 * Render the Eventyay update page.
	 *
	 * @since 1.0.0
	 */
	public function render_update_events_page() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$settings         = $this->importer->get_eventyay_import_settings();
		$endpoint_preview = ! empty( $settings['organizer_slug'] ) ? $this->importer->get_client()->build_eventyay_events_endpoint( $settings ) : '';
		$notice_key       = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();
		$notice           = get_transient( $notice_key );

		if ( $notice ) {
			delete_transient( $notice_key );
		}

		require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/partials/eventyay-update-events.php';
	}
}
