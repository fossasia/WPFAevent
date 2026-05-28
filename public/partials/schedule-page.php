<?php
/**
 * Bridge template for the legacy schedule page.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Proxy to existing schedule page template.
$template = __DIR__ . '/../templates/page-schedule.php';
if ( file_exists( $template ) ) {
	include_once $template;
} else {
	echo '<!-- schedule page template missing -->';
}
