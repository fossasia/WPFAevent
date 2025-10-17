<?php
/**
 * Plugin Name: FOSSASIA Landing
 * Plugin URI:  https://fossasia.org
 * Description: Base plugin structure for FOSSASIA Landing. Loader included for modular expansion.
 * Version:     1.0.0
 * Author:      Nishil
 * License:     GPL2
 * Text Domain: fossasia-landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-fossasia-landing-loader.php';
run_fossasia_landing();

/*
// Comment out future includes for PR-1
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfa-cli.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fossasia-uninstaller.php';
*/
