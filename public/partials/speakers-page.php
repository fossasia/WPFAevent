<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Proxy to existing speakers page template
$template = dirname( dirname( __FILE__ ) ) . '/../../templates/speakers-page.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<!-- speakers page template missing -->';
}
