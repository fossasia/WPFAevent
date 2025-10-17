<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loader for FOSSASIA Landing Plugin
 */
class FOSSASIA_Landing_Loader {
	public function __construct() {
		// Future hooks will be added here
	}
	public function run() {
		// Initialize plugin modules in future PRs
	}
}

function run_fossasia_landing() {
	$plugin = new FOSSASIA_Landing_Loader();
	$plugin->run();
}