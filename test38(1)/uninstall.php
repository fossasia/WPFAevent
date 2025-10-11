<?php
/**
 * FOSSASIA Landing Page Uninstall
 *
 * Uninstalls the plugin and deletes pages, and data files.
 *
 * @package FOSSASIA-Landing-Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the uninstaller class.
require_once plugin_dir_path( __FILE__ ) . 'class-fossasia-uninstaller.php';

// Run the uninstallation process.
FOSSASIA_Uninstaller::uninstall();