<?php
/**
 * Partner Dashboard Hooks.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard_Hooks
 */
class Wpfaevent_Partner_Dashboard_Hooks {

	/**
	 * Renderer helper.
	 *
	 * @var Wpfaevent_Partner_Dashboard_Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->renderer = new Wpfaevent_Partner_Dashboard_Renderer();
	}

	/**
	 * Register the submenu pages for Sponsors and Exhibitors.
	 */
	public function register_menu_pages() {
		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Sponsors', 'wpfaevent' ),
			esc_html__( 'Sponsors', 'wpfaevent' ),
			'edit_events',
			'wpfaevent-sponsors',
			array( $this->renderer, 'render_sponsors_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Exhibitors', 'wpfaevent' ),
			esc_html__( 'Exhibitors', 'wpfaevent' ),
			'edit_events',
			'wpfaevent-exhibitors',
			array( $this->renderer, 'render_exhibitors_page' )
		);
	}
}
