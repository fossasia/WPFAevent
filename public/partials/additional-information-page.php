<?php
/**
 * Legacy proxy for the additional information page template.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template = dirname( __DIR__ ) . '/templates/page-additional-information.php';

if ( file_exists( $template ) ) {
	include $template;
}
