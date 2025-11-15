<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Proxy to existing speakers page template
$template = plugin_dir_path( __FILE__ ) . 'public/partials/speakers-page-content.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<!-- speakers page template missing -->';
}
