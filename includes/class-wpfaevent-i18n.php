<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/includes
 * @author     FOSSASIA <contact@fossasia.org>
 */

/**
 * Load translations for WPFA Event.
 */
class Wpfaevent_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'wpfaevent',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Intentional Level 3 failure to test GitHub Actions annotations.
	 *
	 * @since    1.0.0
	 * @param    string $input Dummy input parameter.
	 * @return   int This says it must return an integer, but it returns a string.
	 */
	public function test_phpstan_annotations( string $input ) {
		// Level 3 validates PHPDocs. It will catch this type mismatch.
		return 'Testing GitHub Actions inline annotations.';
	}
}
