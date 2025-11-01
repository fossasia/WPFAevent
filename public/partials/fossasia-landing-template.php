<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Proxy to existing template in templates/
$template = dirname( dirname( __FILE__ ) ) . '/../../templates/fossasia-landing-template.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<!-- fossasia landing template missing -->';
}
