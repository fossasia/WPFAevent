<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Proxy to existing past events template
$template = dirname( dirname( __FILE__ ) ) . '/../../templates/past-events-page.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<!-- past events template missing -->';
}
