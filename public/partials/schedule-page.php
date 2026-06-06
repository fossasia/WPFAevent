<?php
/**
 * Legacy proxy for the schedule page template.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template = dirname( __DIR__ ) . '/templates/page-schedule.php';

if ( file_exists( $template ) ) {
	include $template;
}
