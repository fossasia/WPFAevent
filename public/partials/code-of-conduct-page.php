<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Proxy to existing code of conduct template
$template = dirname( dirname( __FILE__ ) ) . '/../../templates/code-of-conduct-page.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<!-- code of conduct template missing -->';
}
