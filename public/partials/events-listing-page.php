<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
// Proxy to existing events listing template
$template = dirname( __DIR__ ) . '/../../templates/events-listing-page.php';
if ( file_exists( $template ) ) {
	include_once $template;
} else {
	echo '<!-- events listing template missing -->';
}
