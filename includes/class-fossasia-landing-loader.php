<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class FOSSASIA_Landing_Loader
 *
 * Handles the loading of CPT-aware page templates.
 */
class FOSSASIA_Landing_Loader {

    public function __construct() {
        // Placeholder for future hooks
    }

    public function run() {
        // Register template loader filter
        add_filter('template_include', array($this, 'load_template'));
    }

    public function load_template($template) {
        // Correctly map page slugs to their corresponding template files in the `templates` directory.
        $templates = array(
            'landing'           => 'templates/page-landing.php',
            'speakers'          => 'templates/page-speakers.php',
            'events'            => 'templates/page-events.php',
            'past-events'       => 'templates/page-past-events.php',
            'schedule'          => 'templates/page-schedule.php',
            'code-of-conduct'   => 'templates/page-code-of-conduct.php',
        );

        foreach ($templates as $slug => $template_file) {
            if (is_page($slug)) {
                $path = plugin_dir_path( dirname( __FILE__ ) ) . $template_file;
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return $template;
    }

    public static function run_plugin() {
        $plugin = new self();
        $plugin->run();
    }
}

// Hook loader into WordPress lifecycle
add_action('plugins_loaded', array('FOSSASIA_Landing_Loader', 'run_plugin'));