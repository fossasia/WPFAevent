<?php
// Admin partial shim – include the existing admin dashboard template from templates/
if ( ! defined( 'ABSPATH' ) ) { exit; }

$template = dirname( dirname( __FILE__ ) ) . '/../templates/admin-dashboard.php';
if ( file_exists( $template ) ) {
    include_once $template;
} else {
    echo '<div class="wrap"><h2>Admin Dashboard</h2><p>Admin template not found.</p></div>';
}
