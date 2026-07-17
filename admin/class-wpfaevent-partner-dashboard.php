<?php
/**
 * Sponsors and Exhibitors administration/CRUD management page wrapper.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard
 */
class Wpfaevent_Partner_Dashboard {

	/**
	 * Hooks handler.
	 *
	 * @var Wpfaevent_Partner_Dashboard_Hooks
	 */
	private $hooks;

	/**
	 * Controller handler.
	 *
	 * @var Wpfaevent_Partner_Dashboard_Controller
	 */
	private $controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks      = new Wpfaevent_Partner_Dashboard_Hooks();
		$this->controller = new Wpfaevent_Partner_Dashboard_Controller();
	}

	/**
	 * Register the submenu pages for Sponsors and Exhibitors.
	 */
	public function register_menu_pages() {
		$this->hooks->register_menu_pages();
	}

	/**
	 * POST Handler to Save Sponsor/Exhibitor.
	 */
	public function handle_save_partner() {
		$this->controller->handle_save_partner();
	}

	/**
	 * GET Handler to Delete Sponsor/Exhibitor.
	 */
	public function handle_delete_partner() {
		$this->controller->handle_delete_partner();
	}
}
