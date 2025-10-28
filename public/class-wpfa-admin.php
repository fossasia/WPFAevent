<?php
/**
 * Admin-facing functionality of the plugin.
 *
 * @package FOSSASIA-Event-Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPFA_Admin.
 */
class WPFA_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ 'WPFA_Settings', 'register_settings' ] );
		add_action( 'admin_head', [ $this, 'add_help_tabs' ] );
		add_filter( 'manage_wpfa_speaker_posts_columns', [ $this, 'add_speaker_columns' ] );
		add_action( 'manage_wpfa_speaker_posts_custom_column', [ $this, 'render_speaker_columns' ], 10, 2 );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			__( 'FOSSASIA Event Settings', 'wpfa-event' ),
			__( 'Settings', 'wpfa-event' ),
			'manage_options',
			'wpfa_settings',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			__( 'Import / Export', 'wpfa-event' ),
			__( 'Import / Export', 'wpfa-event' ),
			'manage_options',
			'wpfa_import_export',
			[ 'WPFA_Import_Export', 'render_page' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		require_once WPFA_PLUGIN_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Add custom columns to the speaker list table.
	 */
	public function add_speaker_columns( $columns ) {
		$columns['wpfa_speaker_role'] = __( 'Role', 'wpfa-event' );
		$columns['wpfa_speaker_org']  = __( 'Organization', 'wpfa-event' );
		return $columns;
	}

	/**
	 * Render content for custom speaker columns.
	 */
	public function render_speaker_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'wpfa_speaker_role':
				echo esc_html( get_post_meta( $post_id, 'wpfa_speaker_role', true ) );
				break;
			case 'wpfa_speaker_org':
				echo esc_html( get_post_meta( $post_id, 'wpfa_speaker_org', true ) );
				break;
		}
	}

	/**
	 * Add contextual help tabs.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || 'wpfa_event_page_wpfa_settings' !== $screen->id ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'wpfa_overview_help_tab',
				'title'   => __( 'Overview', 'wpfa-event' ),
				'content' => '<p>' . __( 'This page contains settings for the FOSSASIA Event Plugin. You can configure default image paths and other options.', 'wpfa-event' ) . '</p>',
			]
		);
	}
}