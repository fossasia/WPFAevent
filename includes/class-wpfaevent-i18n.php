<?php

/**
 * Define the internationalization functionality
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    WPFA_Event
 * @subpackage WPFA_Event/includes
 */

class WPFA_Event_i18n {

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wpfa-event',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}