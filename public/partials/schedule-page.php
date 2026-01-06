<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
// Proxy to existing schedule page template
$template = dirname( __DIR__ ) . '/../../templates/schedule-page.php';
if ( file_exists( $template ) ) {
	include_once $template;
} else {
	echo '<!-- schedule page template missing -->';
}
