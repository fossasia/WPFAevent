<?php

/**
 * Fired during plugin activation
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::setup_pages();
		flush_rewrite_rules();
	}

	/**
	 * Creates the necessary pages on plugin activation.
	 */
	private static function setup_pages() {
		// Ensure the landing class is available to create pages.
		if ( ! class_exists( 'Wpfaevent_Landing' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-wpfaevent-landing.php';
		}
		$landing = new Wpfaevent_Landing();
		$landing->create_page_if_not_exists( 'FOSSASIA Summit', 'fossasia-summit', 'public/partials/wpfaevent-landing-template.php' );
		$landing->create_page_if_not_exists( 'Speakers', 'speakers', 'public/partials/speakers-page.php' );
		$landing->create_page_if_not_exists( 'Full Schedule', 'full-schedule', 'public/partials/schedule-page.php' );
		$landing->create_page_if_not_exists( 'Admin Dashboard', 'admin-dashboard', 'public/partials/wpfaevent-landing-template.php', 'private' );
		$landing->create_page_if_not_exists( 'Events', 'events', 'public/partials/wpfaevent-landing-template.php' );
		$landing->create_page_if_not_exists( 'Past Events', 'past-events', 'public/partials/past-events-page.php' );
		$landing->create_page_if_not_exists( 'Code of Conduct', 'code-of-conduct', 'public/partials/wpfaevent-landing-template.php' );
	}

}
