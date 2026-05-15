<?php
/**
 * Bridge template for the legacy events listing page.
 *
 * @package Wpfaevent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Proxy to existing events listing template.
$template = __DIR__ . '/../../templates/events-listing-page.php';
if ( file_exists( $template ) ) {
	include_once $template;
} else {
	echo '<!-- events listing template missing -->';
}
