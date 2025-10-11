<?php
/**
 * Plugin Name: FOSSASIA Landing Page
 * Description: Provides a full-bleed landing page template that renders the provided FOSSASIA Summit single-page site. On activation, creates a page at /fossasia-summit that uses this template.
 * Version: 1.0.0
 * Author: Nishil
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FOSSASIA_Landing_Plugin {
    const TEMPLATE      = 'fossasia-landing-template.php';
    const TEMPLATE_KEY  = 'fossasia-landing-template.php';
    const TEMPLATE_NAME = 'FOSSASIA Full-Page (Plugin)';
    const PAGE_SLUG     = 'fossasia-summit';
    const PAGE_TITLE    = 'FOSSASIA Summit'; // This should be event-calendar

    const SPEAKERS_TEMPLATE      = 'speakers-page.php';
    const SPEAKERS_TEMPLATE_KEY  = 'speakers-page.php';
    const SPEAKERS_TEMPLATE_NAME = 'FOSSASIA Speakers (Plugin)';
    const SPEAKERS_PAGE_SLUG     = 'speakers';
    const SPEAKERS_PAGE_TITLE    = 'FOSSASIA Speakers';

    const SCHEDULE_TEMPLATE      = 'schedule-page.php';
    const SCHEDULE_TEMPLATE_KEY  = 'schedule-page.php';
    const SCHEDULE_TEMPLATE_NAME = 'FOSSASIA Schedule (Plugin)';
    const SCHEDULE_PAGE_SLUG     = 'full-schedule';
    const SCHEDULE_PAGE_TITLE    = 'FOSSASIA Full Schedule';

    const ADMIN_TEMPLATE      = 'admin-dashboard.php';
    const ADMIN_TEMPLATE_KEY  = 'admin-dashboard.php';
    const ADMIN_TEMPLATE_NAME = 'FOSSASIA Admin Dashboard (Plugin)';
    const ADMIN_PAGE_SLUG     = 'admin-dashboard';
    const ADMIN_PAGE_TITLE    = 'Admin Dashboard';

    const EVENTS_TEMPLATE      = 'events-listing-page.php';
    const EVENTS_TEMPLATE_KEY  = 'events-listing-page.php';
    const EVENTS_TEMPLATE_NAME = 'FOSSASIA Events Listing (Plugin)';
    const EVENTS_PAGE_SLUG     = 'events';
    const EVENTS_PAGE_TITLE    = 'FOSSASIA Events';

    const PAST_EVENTS_TEMPLATE      = 'past-events-page.php';
    const PAST_EVENTS_TEMPLATE_KEY  = 'past-events-page.php';
    const PAST_EVENTS_TEMPLATE_NAME = 'FOSSASIA Past Events (Plugin)';
    const PAST_EVENTS_PAGE_SLUG     = 'past-events';
    const PAST_EVENTS_PAGE_TITLE    = 'Past FOSSASIA Events';

    const COC_TEMPLATE      = 'code-of-conduct-page.php';
    const COC_TEMPLATE_KEY  = 'code-of-conduct-page.php';
    const COC_TEMPLATE_NAME = 'FOSSASIA Code of Conduct (Plugin)';
    const COC_PAGE_SLUG     = 'code-of-conduct';
    const COC_PAGE_TITLE    = 'Code of Conduct';

    const SETTINGS_FILE = 'site-settings.json';
    const SECTIONS_FILE = 'custom-sections.json';
    const NAVIGATION_FILE = 'navigation.json';
    const COC_CONTENT_FILE = 'coc-content.json';

    const THEME_SETTINGS_FILE = 'theme-settings.json';
    public function __construct() {
        add_filter( 'theme_page_templates', [ $this, 'register_template' ] );
        add_filter( 'template_include', [ $this, 'load_template' ], 99 );
        register_activation_hook( __FILE__, [ $this, 'on_activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'on_deactivate' ] );
        add_action( 'init', [ $this, 'setup_pages' ] );

        // AJAX handlers for server-side data management
        add_action( 'wp_ajax_fossasia_manage_speakers', [ $this, 'ajax_manage_speakers' ] );
        add_action( 'wp_ajax_fossasia_manage_sponsors', [ $this, 'ajax_manage_sponsors' ] );
        add_action( 'wp_ajax_fossasia_manage_site_settings', [ $this, 'ajax_manage_site_settings' ] );
        add_action( 'wp_ajax_fossasia_manage_sections', [ $this, 'ajax_manage_sections' ] );
        add_action( 'wp_ajax_fossasia_manage_schedule', [ $this, 'ajax_manage_schedule' ] );
        add_action( 'wp_ajax_fossasia_manage_navigation', [ $this, 'ajax_manage_navigation' ] );
        add_action( 'wp_ajax_fossasia_sync_eventyay', [ $this, 'ajax_sync_eventyay' ] );
        add_action( 'wp_ajax_fossasia_create_event_page', [ $this, 'ajax_create_event_page' ] );
        add_action( 'wp_ajax_fossasia_edit_event_page', [ $this, 'ajax_edit_event_page' ] );
        add_action( 'wp_ajax_fossasia_delete_event_page', [ $this, 'ajax_delete_event_page' ] );
        add_action( 'wp_ajax_fossasia_manage_theme_settings', [ $this, 'ajax_manage_theme_settings' ] );
        add_action( 'wp_ajax_fossasia_import_sample_data', [ $this, 'ajax_import_sample_data' ] );
        add_action( 'wp_ajax_fossasia_add_sample_event', [ $this, 'ajax_add_sample_event' ] );
        add_action( 'wp_ajax_fossasia_manage_coc', [ $this, 'ajax_manage_coc' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function register_template( $templates ) {
        $templates[ self::TEMPLATE_KEY ] = self::TEMPLATE_NAME;
        $templates[ self::SPEAKERS_TEMPLATE_KEY ] = self::SPEAKERS_TEMPLATE_NAME;
        $templates[ self::SCHEDULE_TEMPLATE_KEY ] = self::SCHEDULE_TEMPLATE_NAME;
        $templates[ self::ADMIN_TEMPLATE_KEY ] = self::ADMIN_TEMPLATE_NAME;
        $templates[ self::EVENTS_TEMPLATE_KEY ] = self::EVENTS_TEMPLATE_NAME;
        $templates[ self::PAST_EVENTS_TEMPLATE_KEY ] = self::PAST_EVENTS_TEMPLATE_NAME;
        $templates[ self::COC_TEMPLATE_KEY ] = self::COC_TEMPLATE_NAME;
        return $templates;
    }

    public function load_template( $template ) {
        global $post;

        // Primary, most reliable check: Direct slug matching for our pages.
        if ( is_page() && ! empty( $post ) ) {
            $slug = $post->post_name;

            $page_slug_to_template = [
                self::PAGE_SLUG           => self::TEMPLATE,
                self::SPEAKERS_PAGE_SLUG  => self::SPEAKERS_TEMPLATE,
                self::SCHEDULE_PAGE_SLUG  => self::SCHEDULE_TEMPLATE,
                self::ADMIN_PAGE_SLUG     => self::ADMIN_TEMPLATE,
                self::EVENTS_PAGE_SLUG    => self::EVENTS_TEMPLATE,
                self::PAST_EVENTS_PAGE_SLUG => self::PAST_EVENTS_TEMPLATE,
                self::COC_PAGE_SLUG => self::COC_TEMPLATE,
            ];

            if ( isset( $page_slug_to_template[ $slug ] ) ) {
                $template_filename = $page_slug_to_template[ $slug ];
                $base_path = plugin_dir_path( __FILE__ );
                // The schedule page is at the root, others are in /templates
                if ($slug === self::SCHEDULE_PAGE_SLUG) {
                    $file = $base_path . $template_filename;
                } else {
                    $file = $base_path . 'templates/' . $template_filename;
                }

                if ( file_exists( $file ) ) {
                    return $file;
                }
            }
        }

        // Fallback check for manually assigned templates
        if ( is_singular() ) {
            $selected = get_page_template_slug( get_queried_object_id() );
            $template_key_to_file = [
                self::TEMPLATE_KEY          => self::TEMPLATE,
                self::SPEAKERS_TEMPLATE_KEY => self::SPEAKERS_TEMPLATE,
                self::SCHEDULE_TEMPLATE_KEY => self::SCHEDULE_TEMPLATE,
                self::ADMIN_TEMPLATE_KEY    => self::ADMIN_TEMPLATE,
                self::EVENTS_TEMPLATE_KEY   => self::EVENTS_TEMPLATE,
                self::PAST_EVENTS_TEMPLATE_KEY   => self::PAST_EVENTS_TEMPLATE,
                self::COC_TEMPLATE_KEY   => self::COC_TEMPLATE,
            ];

            if ( isset($template_key_to_file[$selected]) ) {
                $template_filename = $template_key_to_file[$selected];
                $base_path = plugin_dir_path( __FILE__ );
                // The schedule page is at the root, others are in /templates
                if ($selected === self::SCHEDULE_TEMPLATE_KEY) {
                    $file = $base_path . $template_filename;
                } else {
                    $file = $base_path . 'templates/' . $template_filename;
                }

                if ( file_exists( $file ) ) {
                    return $file;
                }
            }
        }
        return $template;
    }

    public function enqueue_admin_scripts( $hook ) {
        // Only load scripts on our specific admin page.
        global $post;
        if ( is_admin() && isset($post->post_name) && $post->post_name === self::ADMIN_PAGE_SLUG ) {
            // Enqueue WordPress editor scripts (like TinyMCE).
            wp_enqueue_editor();
        }
    }

    public function on_activate() {
        // Setup data storage directory and files
        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }        
        
        // Seed global data files.
        $this->seed_global_data_files($data_dir);
        
        // Run page setup on activation as well.
        $this->setup_pages();

        // Flush rewrite rules to ensure new page slugs are recognized immediately.
        flush_rewrite_rules();
    }

    /**
     * Runs on plugin deactivation. Flushes rewrite rules.
     */
    public function on_deactivate() {
        // The main uninstall logic is in uninstall.php. Deactivation should be non-destructive.
        flush_rewrite_rules();
    }

    public function setup_pages() {
        $pages_to_create = [
            [ 'slug' => self::SPEAKERS_PAGE_SLUG, 'title' => self::SPEAKERS_PAGE_TITLE, 'template' => self::SPEAKERS_TEMPLATE_KEY, 'content' => '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the speakers-page.php template. -->' ],
            [ 'slug' => self::SCHEDULE_PAGE_SLUG, 'title' => self::SCHEDULE_PAGE_TITLE, 'template' => self::SCHEDULE_TEMPLATE_KEY, 'content' => '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the schedule-page.php template. -->' ],
            [ 'slug' => self::ADMIN_PAGE_SLUG, 'title' => self::ADMIN_PAGE_TITLE, 'template' => self::ADMIN_TEMPLATE_KEY, 'content' => '' ],
            [ 'slug' => self::EVENTS_PAGE_SLUG, 'title' => self::EVENTS_PAGE_TITLE, 'template' => self::EVENTS_TEMPLATE_KEY, 'content' => '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the events-listing-page.php template. -->' ],
            [ 'slug' => self::PAST_EVENTS_PAGE_SLUG, 'title' => self::PAST_EVENTS_PAGE_TITLE, 'template' => self::PAST_EVENTS_TEMPLATE_KEY, 'content' => '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the past-events-page.php template. -->' ],
            [ 'slug' => self::COC_PAGE_SLUG, 'title' => self::COC_PAGE_TITLE, 'template' => self::COC_TEMPLATE_KEY, 'content' => '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the code-of-conduct-page.php template. -->' ],
        ];

        foreach ($pages_to_create as $page_args) {
            $this->create_or_update_page($page_args);
        }
    }

    private function create_or_update_page($args) {
        $existing_page = get_page_by_path($args['slug']);
        $page_data = [
            'post_title'   => $args['title'],
            'post_name'    => $args['slug'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $args['content'],
        ];

        if ($existing_page) {
            $page_data['ID'] = $existing_page->ID;
            wp_update_post($page_data);
            $page_id = $existing_page->ID;
        } else {
            $page_id = wp_insert_post($page_data);
        }

        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', $args['template']);
        }
    }

    private function seed_global_data_files($data_dir) {
        $settings_file = $data_dir . '/' . self::SETTINGS_FILE;
        if (!file_exists($settings_file)) {
            file_put_contents($settings_file, json_encode($this->get_initial_site_settings_data(), JSON_PRETTY_PRINT));
        }

        $sections_file = $data_dir . '/' . self::SECTIONS_FILE;
        if (!file_exists($sections_file)) {
            file_put_contents($sections_file, json_encode([], JSON_PRETTY_PRINT));
        }

        $navigation_file = $data_dir . '/' . self::NAVIGATION_FILE;
        if (!file_exists($navigation_file)) {
            file_put_contents($navigation_file, json_encode($this->get_initial_navigation_data(), JSON_PRETTY_PRINT));
        }

        $theme_settings_file = $data_dir . '/' . self::THEME_SETTINGS_FILE;
        if (!file_exists($theme_settings_file)) {
            file_put_contents($theme_settings_file, json_encode($this->get_initial_theme_settings_data(), JSON_PRETTY_PRINT));
        }

        $coc_content_file = $data_dir . '/' . self::COC_CONTENT_FILE;
        if (!file_exists($coc_content_file)) {
            $initial_coc = [
                'content' => '<p>This is a placeholder for the Code of Conduct. The full text will be added here later. All participants are expected to adhere to the principles of respect, inclusivity, and professional behavior to ensure a safe and welcoming environment for everyone.</p>'
            ];
            file_put_contents($coc_content_file, json_encode($initial_coc, JSON_PRETTY_PRINT));
        }
    }

    private function seed_default_summit_page_data($data_dir) {
        $default_summit_page = get_page_by_path(self::PAGE_SLUG);
        if (!$default_summit_page) {
            // If the page doesn't exist yet, we can't seed its data.
            // setup_pages() will run after this and create it.
            return;
        }
        $page_id = $default_summit_page->ID;

        $speakers_file = $data_dir . '/speakers-' . $page_id . '.json';
        $sponsors_file = $data_dir . '/sponsors-' . $page_id . '.json';

        // Seed speakers and sponsors with sample data for the default page
        file_put_contents($speakers_file, json_encode($this->get_initial_speaker_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($sponsors_file, json_encode($this->get_initial_sponsor_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function ajax_manage_speakers() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) { wp_send_json_error('Event ID is required.', 400); }

        $speakers_file = $data_dir . '/speakers-' . $event_id . '.json';

        $action = sanitize_text_field($_POST['task']);
        
        if (!file_exists($speakers_file)) { file_put_contents($speakers_file, '[]'); }
        $speakers = json_decode(file_get_contents($speakers_file), true) ?: [];

        switch ($action) {
            case 'update_live':
                $speaker_data = json_decode(stripslashes($_POST['speaker']), true);
                $speaker_id = $speaker_data['id'];
                $found = false;
                foreach ($speakers as $i => &$speaker) {
                    if ($speaker['id'] === $speaker_id) {
                        $speaker = $speaker_data;
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    file_put_contents($speakers_file, json_encode($speakers, JSON_PRETTY_PRINT));
                    wp_send_json_success(['message' => 'Speaker updated.']);
                }
                break;
            
            case 'save_all':
                $all_speakers_data = json_decode(stripslashes($_POST['speakers']), true);
                if (file_put_contents($speakers_file, json_encode($all_speakers_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                    wp_send_json_error('Failed to save speakers data.', 500);
                } else {
                    wp_send_json_success(['message' => 'Speakers data saved successfully.']);
                }
                break;
        }

        wp_send_json_error('Invalid action or data.', 400);
    }

    public function ajax_manage_sponsors() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) { wp_send_json_error('Event ID is required.', 400); }

        $sponsors_file = $data_dir . '/sponsors-' . $event_id . '.json';

        $task = sanitize_text_field($_POST['task']);

        if ($task === 'save_all') {
            $new_sponsors_data = json_decode(stripslashes($_POST['sponsors']), true);
            file_put_contents($sponsors_file, json_encode($new_sponsors_data, JSON_PRETTY_PRINT));
            wp_send_json_success(['message' => 'Sponsors updated successfully.']);
        }

        wp_send_json_error('Invalid task.', 400);
    }

    public function ajax_manage_site_settings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $new_settings_data = json_decode(stripslashes($_POST['settings']), true);

        // Check for event-specific settings.
        if (isset($new_settings_data['about_section_content']) || isset($new_settings_data['cfs_button_text']) || isset($new_settings_data['cfs_button_link'])) {
            if (!$event_id) {
                wp_send_json_error('Event ID is required to save these settings.', 400);
            }
            $settings_file = $data_dir . '/site-settings-' . $event_id . '.json';
            if (!file_exists($settings_file)) { file_put_contents($settings_file, '{}'); }
            $existing_settings = json_decode(file_get_contents($settings_file), true) ?: [];
            $merged_settings = array_merge($existing_settings, $new_settings_data);

        } else { // This handles other settings, which might be global or event-specific.
            if (!$event_id) {
                // Handle global settings (like footer text)
                $settings_file = $data_dir . '/' . self::SETTINGS_FILE;
            } else {
                // Handle other event-specific settings (like hero image)
                $settings_file = $data_dir . '/site-settings-' . $event_id . '.json';
            }
            if (!file_exists($settings_file)) { file_put_contents($settings_file, '{}'); }
            $existing_settings = json_decode(file_get_contents($settings_file), true) ?: [];
            $merged_settings = array_merge($existing_settings, $new_settings_data);
        }

        if (file_put_contents($settings_file, json_encode($merged_settings, JSON_PRETTY_PRINT)) === false) {
            wp_send_json_error('Failed to save settings.', 500);
        } else {
            wp_send_json_success(['message' => 'Site settings updated successfully.']);
        }
    }

    public function ajax_manage_sections() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $sections_file = $data_dir . '/' . self::SECTIONS_FILE;

        $task = sanitize_text_field($_POST['task']);

        if ($task === 'save_all') {
            $new_sections_data = json_decode(stripslashes($_POST['sections']), true);
            // Basic validation/sanitization could be added here if needed
            if (file_put_contents($sections_file, json_encode($new_sections_data, JSON_PRETTY_PRINT)) === false) {
                wp_send_json_error('Failed to save sections.', 500);
            } else {
                wp_send_json_success(['message' => 'Custom sections updated successfully.']);
            }
        }
        wp_send_json_error('Invalid task.', 400);
    }

    public function ajax_manage_schedule() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) { wp_send_json_error('Event ID is required.', 400); }

        $schedule_file = $data_dir . '/schedule-' . $event_id . '.json';

        if (isset($_POST['schedule'])) {
            $new_schedule_data = json_decode(stripslashes($_POST['schedule']), true);
            file_put_contents($schedule_file, json_encode($new_schedule_data, JSON_PRETTY_PRINT));
            wp_send_json_success(['message' => 'Schedule updated successfully.']);
        } else {
            wp_send_json_error('Invalid data.', 400);
        }
    }

    public function ajax_manage_navigation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $navigation_file = $data_dir . '/' . self::NAVIGATION_FILE;

        $task = sanitize_text_field($_POST['task']);

        if ($task === 'save_all') {
            $new_nav_data = json_decode(stripslashes($_POST['navigation']), true);
            if (file_put_contents($navigation_file, json_encode($new_nav_data, JSON_PRETTY_PRINT)) === false) {
                wp_send_json_error('Failed to save navigation.', 500);
            }
            wp_send_json_success(['message' => 'Navigation updated successfully.']);
        }
        wp_send_json_error('Invalid task.', 400);
    }

    public function ajax_sync_eventyay() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error('Cannot sync without an event context. Please edit an event to sync its speakers.', 400);
        }

        $result = $this->sync_eventyay_speakers($event_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message(), 500);
        }

        wp_send_json_success(['message' => 'Successfully synced ' . $result . ' speakers from Eventyay.']);
    }

    public function ajax_manage_theme_settings() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';

        if ($event_id) {
            $theme_file = $data_dir . '/theme-settings-' . $event_id . '.json';
        } else {
            // If for some reason no event ID is passed, we could update the global one,
            // but based on the request, we should require an event ID.
            wp_send_json_error('Event ID is required to save theme settings.', 400);
        }

        if (isset($_POST['theme'])) { // The key from saveStore('theme', ...) is 'theme'
            $new_theme_data = json_decode(stripslashes($_POST['theme']), true);
            // Add sanitization for colors here if needed, e.g., using a regex
            if (file_put_contents($theme_file, json_encode($new_theme_data, JSON_PRETTY_PRINT)) === false) {
                wp_send_json_error('Failed to save theme settings.', 500);
            }
            wp_send_json_success(['message' => 'Theme settings updated successfully.']);
        }
        wp_send_json_error('Invalid data.', 400);
    }

    public function ajax_manage_coc() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';
        $coc_file = $data_dir . '/' . self::COC_CONTENT_FILE;

        if (isset($_POST['coc_content'])) {
            $new_content_data = [ 'content' => wp_kses_post(stripslashes($_POST['coc_content'])) ];
            if (file_put_contents($coc_file, json_encode($new_content_data, JSON_PRETTY_PRINT)) === false) {
                wp_send_json_error('Failed to save Code of Conduct.', 500);
            } else {
                wp_send_json_success(['message' => 'Code of Conduct updated successfully.']);
            }
        }

        wp_send_json_error('Invalid data.', 400);
    }

    public function ajax_add_sample_event() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $event_name = 'FOSSASIA Summit (Sample)';
        $slug = sanitize_title($event_name);

        // Check if a sample event already exists to prevent duplicates
        $existing_page = get_page_by_path($slug, OBJECT, 'page');
        if ($existing_page && $existing_page->post_status !== 'trash') {
            wp_send_json_error('A sample event with this name already exists.', 409);
        }

        // Create the event page
        $page_id = wp_insert_post([
            'post_title'   => $event_name,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '<!-- This is a sample event page created by the FOSSASIA plugin. -->',
            'post_excerpt' => 'A sample event to demonstrate the features of the FOSSASIA Event plugin.',
        ]);

        if (is_wp_error($page_id)) {
            wp_send_json_error('Failed to create sample event page: ' . $page_id->get_error_message(), 500);
        }

        // Assign the template and meta data
        update_post_meta($page_id, '_wp_page_template', self::TEMPLATE_KEY);
        update_post_meta($page_id, '_event_date', date('Y-m-d', strtotime('+30 days')));
        update_post_meta($page_id, '_event_end_date', date('Y-m-d', strtotime('+32 days')));
        update_post_meta($page_id, '_event_place', 'Online');
        update_post_meta($page_id, '_event_time', '09:00');
        update_post_meta($page_id, '_event_lead_text', 'Welcome to our sample event. Explore the features!');
        update_post_meta($page_id, '_event_registration_link', '#');
        update_post_meta($page_id, '_event_cfs_link', '#');

        // Create and seed the data files for this new event
        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';

        $speakers_file = $data_dir . '/speakers-' . $page_id . '.json';
        $sponsors_file = $data_dir . '/sponsors-' . $page_id . '.json';
        $settings_file = $data_dir . '/site-settings-' . $page_id . '.json';
        $theme_file = $data_dir . '/theme-settings-' . $page_id . '.json';
        $schedule_file = $data_dir . '/schedule-' . $page_id . '.json';

        // Get initial data
        $initial_speakers = $this->get_initial_speaker_data();
        $initial_sponsors = $this->get_initial_sponsor_data();
        $initial_settings = [
            'about_section_content' => '<p>This is a sample event. You can edit this content in the admin dashboard.</p>',
            'hero_image_url' => plugins_url('images/hero.jpg', __FILE__),
        ];
        $initial_theme = $this->get_initial_theme_settings_data();

        // Write data to files
        file_put_contents($speakers_file, json_encode($initial_speakers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($sponsors_file, json_encode($initial_sponsors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($settings_file, json_encode($initial_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($theme_file, json_encode($initial_theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($schedule_file, '{}'); // Empty schedule for now

        wp_send_json_success([
            'message' => 'Sample event created successfully!',
            'eventData' => [
                'name' => $event_name,
                'permalink' => get_permalink($page_id),
            ]
        ]);
    }

    public function ajax_import_sample_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id) {
            wp_send_json_error('Event ID is required.', 400);
        }

        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/fossasia-data';

        // Define file paths
        $speakers_file = $data_dir . '/speakers-' . $event_id . '.json';
        $sponsors_file = $data_dir . '/sponsors-' . $event_id . '.json';
        $settings_file = $data_dir . '/site-settings-' . $event_id . '.json';
        $theme_file = $data_dir . '/theme-settings-' . $event_id . '.json';

        // Get initial data
        $initial_speakers = $this->get_initial_speaker_data();
        $initial_sponsors = $this->get_initial_sponsor_data();
        $initial_settings = $this->get_initial_site_settings_data();
        $initial_theme = $this->get_initial_theme_settings_data();

        // Write data to files, overwriting existing content
        file_put_contents($speakers_file, json_encode($initial_speakers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($sponsors_file, json_encode($initial_sponsors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($settings_file, json_encode($initial_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($theme_file, json_encode($initial_theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        wp_send_json_success(['message' => 'Sample data imported successfully for this event.']);
    }

    public function ajax_create_event_page() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $event_name = isset($_POST['eventName']) ? sanitize_text_field($_POST['eventName']) : '';
        $event_date = isset($_POST['eventDate']) ? sanitize_text_field($_POST['eventDate']) : '';
        $event_end_date = isset($_POST['eventEndDate']) ? sanitize_text_field($_POST['eventEndDate']) : '';
        $event_place = isset($_POST['eventPlace']) ? sanitize_text_field($_POST['eventPlace']) : '';
        $event_time = isset($_POST['eventTime']) ? sanitize_text_field($_POST['eventTime']) : '';
        $event_description = isset($_POST['eventDescription']) ? wp_kses_post($_POST['eventDescription']) : '';
        $event_lead_text = isset($_POST['eventLeadText']) ? wp_kses_post($_POST['eventLeadText']) : '';
        $event_registration_link = isset($_POST['eventRegistrationLink']) ? esc_url_raw($_POST['eventRegistrationLink']) : '';

        $cfs_button_text = 'Call for Speakers'; // Default text for new events
        $event_cfs_link = isset($_POST['eventCfsLink']) ? esc_url_raw($_POST['eventCfsLink']) : '';
        if (empty($event_name)) {
            wp_send_json_error('Event name is required.', 400);
        }

        $slug = sanitize_title($event_name);
        // Check for an existing page with the same slug that is NOT in the trash.
        $existing_page = get_page_by_path($slug, OBJECT, 'page');

        if ($existing_page && $existing_page->post_status !== 'trash') {
            wp_send_json_error('An event with this name already exists, please choose a different name.', 409);
        }

        $page_id = wp_insert_post([
            'post_title'   => $event_name,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '<!-- This page is for the event: ' . esc_html($event_name) . '. It is rendered by the FOSSASIA Landing Page plugin. -->',
            'post_excerpt' => $event_description, // Save description to the excerpt
        ]);

        if (is_wp_error($page_id)) {
            wp_send_json_error('Failed to create event page: ' . $page_id->get_error_message(), 500);
        }

        // Assign the main FOSSASIA Summit template to the new page
        update_post_meta($page_id, '_wp_page_template', self::TEMPLATE_KEY);

        // Store event-specific details as post meta
        update_post_meta($page_id, '_event_date', $event_date);
        update_post_meta($page_id, '_event_end_date', $event_end_date);
        update_post_meta($page_id, '_event_place', $event_place);
        update_post_meta($page_id, '_event_time', $event_time);
        update_post_meta($page_id, '_event_lead_text', $event_lead_text);
        update_post_meta($page_id, '_event_registration_link', $event_registration_link);
        update_post_meta($page_id, '_event_cfs_link', $event_cfs_link);
        update_post_meta($page_id, '_cfs_button_text', $cfs_button_text);
        update_post_meta($page_id, '_cfs_button_link', $event_cfs_link); // The link is the same for both fields initially

        // Handle image upload and set as featured image
        if (isset($_FILES['eventPicture'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('eventPicture', $page_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($page_id, $attachment_id);
            }
        }

        // Create empty data files for the new event
        $data_dir = wp_upload_dir()['basedir'] . '/fossasia-data';
        file_put_contents($data_dir . '/speakers-' . $page_id . '.json', '[]');
        file_put_contents($data_dir . '/sponsors-' . $page_id . '.json', '[]');
        file_put_contents($data_dir . '/schedule-' . $page_id . '.json', '{}');
        file_put_contents($data_dir . '/theme-settings-' . $page_id . '.json', json_encode($this->get_initial_theme_settings_data(), JSON_PRETTY_PRINT));
        $initial_settings = [
            'about_section_content' => '<p>Welcome to ' . esc_html($event_name) . '! Edit this content in the admin dashboard.</p>',
            'featured_speakers_count' => 8,
            'cfs_button_text' => 'Call for Speakers',
            'cfs_button_link' => $event_cfs_link
        ];
        file_put_contents($data_dir . '/site-settings-' . $page_id . '.json', json_encode($initial_settings));

        $event_data = [
            'postId' => $page_id,
            'name' => $event_name,
            'date' => $event_date,
            'endDate' => $event_end_date,
            'place' => $event_place,
            'time' => $event_time,
            'permalink' => get_permalink($page_id),
            'leadText' => $event_lead_text,
            'description' => $event_description,
            'pictureSrc' => get_the_post_thumbnail_url($page_id, 'large') ?: '',
            'registrationLink' => $event_registration_link,
            'cfsLink' => $event_cfs_link,
            'cfsButtonText' => $cfs_button_text,
            'cfsButtonLink' => $event_cfs_link,
            'year' => !empty($event_date) ? date('Y', strtotime($event_date)) : null
        ];

        wp_send_json_success([
            'message' => 'Event page created successfully!',
            'eventData' => $event_data
        ]);
    }

    public function ajax_edit_event_page() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $post_id = isset($_POST['postId']) ? absint($_POST['postId']) : 0;
        $event_name = isset($_POST['eventName']) ? sanitize_text_field($_POST['eventName']) : '';
        $event_end_date = isset($_POST['eventEndDate']) ? sanitize_text_field($_POST['eventEndDate']) : '';
        $event_date = isset($_POST['eventDate']) ? sanitize_text_field($_POST['eventDate']) : '';
        $event_place = isset($_POST['eventPlace']) ? sanitize_text_field($_POST['eventPlace']) : '';
        $event_time = isset($_POST['eventTime']) ? sanitize_text_field($_POST['eventTime']) : '';
        $event_description = isset($_POST['eventDescription']) ? wp_kses_post($_POST['eventDescription']) : '';
        $event_lead_text = isset($_POST['eventLeadText']) ? wp_kses_post($_POST['eventLeadText']) : '';
        $event_registration_link = isset($_POST['eventRegistrationLink']) ? esc_url_raw($_POST['eventRegistrationLink']) : '';
        $event_cfs_link = isset($_POST['eventCfsLink']) ? esc_url_raw($_POST['eventCfsLink']) : '';
        $cfs_button_text = isset($_POST['cfsButtonText']) ? sanitize_text_field($_POST['cfsButtonText']) : 'Call for Speakers';
        $cfs_button_link = isset($_POST['cfsButtonLink']) ? esc_url_raw($_POST['cfsButtonLink']) : '';

        if (empty($post_id) || empty($event_name)) {
            wp_send_json_error('Post ID and Event name are required.', 400);
        }

        $post_data = [
            'ID'         => $post_id,
            'post_title' => $event_name,
            'post_name'  => sanitize_title($event_name),
            'post_excerpt' => $event_description, // Update the excerpt
        ];
        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            wp_send_json_error('Failed to update event page: ' . $result->get_error_message(), 500);
        }

        update_post_meta($post_id, '_event_date', $event_date);
        update_post_meta($post_id, '_event_end_date', $event_end_date);
        update_post_meta($post_id, '_event_place', $event_place);
        update_post_meta($post_id, '_event_time', $event_time);
        update_post_meta($post_id, '_event_lead_text', $event_lead_text);
        update_post_meta($post_id, '_event_registration_link', $event_registration_link);
        update_post_meta($post_id, '_event_cfs_link', $event_cfs_link);
        update_post_meta($post_id, '_cfs_button_text', $cfs_button_text);
        update_post_meta($post_id, '_cfs_button_link', $cfs_button_link);

        $image_url = get_the_post_thumbnail_url($post_id, 'large');
        if (isset($_FILES['eventPicture'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('eventPicture', $post_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
                $image_url = wp_get_attachment_image_url($attachment_id, 'large');
            }
        }

        $event_data = [
            'name' => $event_name, 'date' => $event_date, 'endDate' => $event_end_date, 'place' => $event_place, 'time' => $event_time, 'description' => $event_description, 'leadText' => $event_lead_text,
            'pictureSrc' => $image_url ?: '', 'registrationLink' => $event_registration_link, 'cfsLink' => $event_cfs_link,
            'cfsButtonText' => $cfs_button_text, 'cfsButtonLink' => $cfs_button_link
        ];

        wp_send_json_success(['message' => 'Event updated successfully!', 'eventData' => $event_data]);
    }

    public function ajax_delete_event_page() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fossasia_admin_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.', 403);
        }

        $post_id = isset($_POST['postId']) ? absint($_POST['postId']) : 0;
        if (empty($post_id)) {
            wp_send_json_error('Post ID is required.', 400);
        }

        $result = wp_delete_post($post_id, true); // true forces permanent deletion

        if ($result) {
            wp_send_json_success(['message' => 'Event deleted successfully.']);
        } else {
            wp_send_json_error('Failed to delete event.', 500);
        }
    }

    private function sync_eventyay_speakers($event_id = 0) {
        if (!$event_id) {
            return new WP_Error('no_event_id', 'An event ID is required to sync speakers.');
        }

        // In a real multi-event system, you would get the Eventyay API URL from the event's post meta.
        // For now, we'll keep using the hardcoded one as an example.
        // $eventyay_api_url = get_post_meta($event_id, '_eventyay_api_url', true);
        // if (!$eventyay_api_url) { return new WP_Error('no_api_url', 'Eventyay API URL not set for this event.'); }
        $api_url = 'https://api.eventyay.com/v1/events/4c0e0c27/sessions?include=speakers,track&page[size]=200';
        
        $response = wp_remote_get($api_url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Failed to connect to Eventyay API: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error('api_error', 'Eventyay API returned status code: ' . $status_code);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
            return new WP_Error('json_error', 'Failed to parse JSON response from Eventyay API.');
        }

        // --- Data Transformation ---
        $included_data = $data['included'] ?? [];
        $speakers_map = [];
        $tracks_map = [];

        foreach ($included_data as $item) {
            if ($item['type'] === 'speaker') {
                $speakers_map[$item['id']] = $item['attributes'];
            }
            if ($item['type'] === 'track') {
                $tracks_map[$item['id']] = $item['attributes']['name'];
            }
        }

        $speakers_with_sessions = [];

        foreach ($data['data'] as $session) {
            if (empty($session['relationships']['speakers']['data'])) {
                continue;
            }

            $track_id = $session['relationships']['track']['data']['id'] ?? null;
            $track_name = $track_id ? ($tracks_map[$track_id] ?? 'General') : 'General';

            $session_details = [
                'title'    => $session['attributes']['title'],
                'abstract' => $session['attributes']['short-abstract'] ?? $session['attributes']['description'] ?? '',
                'date'     => substr($session['attributes']['starts-at'], 0, 10),
                'time'     => substr($session['attributes']['starts-at'], 11, 5),
                'end_time' => substr($session['attributes']['ends-at'], 11, 5),
            ];

            foreach ($session['relationships']['speakers']['data'] as $speaker_ref) {
                $speaker_id = $speaker_ref['id'];

                if (!isset($speakers_map[$speaker_id])) continue;

                if (!isset($speakers_with_sessions[$speaker_id])) {
                    $speaker_attrs = $speakers_map[$speaker_id];

                    $photo_url = $speaker_attrs['photo-url'] ?? null;
                    if (!empty($photo_url)) {
                        // If URL doesn't start with http or //, it's likely a relative path.
                        if (strpos(trim($photo_url), 'http') !== 0 && strpos(trim($photo_url), '//') !== 0) {
                            // Prepend base URL, ensuring no double slashes.
                            $photo_url = 'https://eventyay.com/' . ltrim($photo_url, '/');
                        }
                    } else {
                        $photo_url = ''; // Removed plugin image reference
                    }

                    $speakers_with_sessions[$speaker_id] = [
                        'id'       => 'ev-' . $speaker_id,
                        'image'    => $photo_url,
                        'category' => $track_name,
                        'name'     => $speaker_attrs['name'],
                        'title'    => trim(($speaker_attrs['position'] ?? '') . ($speaker_attrs['position'] && $speaker_attrs['organisation'] ? ', ' : '') . ($speaker_attrs['organisation'] ?? '')),
                        'bio'      => $speaker_attrs['short-biography'] ?? $speaker_attrs['long-biography'] ?? 'No bio available.',
                        'social'   => ['linkedin' => $speaker_attrs['linkedin'] ?? '','twitter'  => $speaker_attrs['twitter'] ?? '','github'   => $speaker_attrs['github'] ?? '','website'  => $speaker_attrs['website'] ?? ''],
                        'sessions' => [],
                    ];
                }
                $speakers_with_sessions[$speaker_id]['sessions'][] = $session_details;
            }
        }

        $final_speakers_list = array_values($speakers_with_sessions);
        usort($final_speakers_list, fn($a, $b) => strcmp($a['name'], $b['name']));

        // --- MERGE LOGIC ---
        $upload_dir = wp_upload_dir();
        $speakers_file = $upload_dir['basedir'] . '/fossasia-data/speakers-' . $event_id . '.json';

        // 1. Read existing speakers from the file, if it exists.
        $existing_speakers = [];
        if (file_exists($speakers_file)) {
            $existing_speakers = json_decode(file_get_contents($speakers_file), true) ?: [];
        }

        // 2. Filter to get only the manually added speakers.
        $manual_speakers = array_filter($existing_speakers, function($speaker) {
            return isset($speaker['id']) && strpos($speaker['id'], 'manual-') === 0;
        });

        // 3. Combine the new list from Eventyay with the existing manual speakers.
        $merged_speakers_list = array_merge($final_speakers_list, array_values($manual_speakers));

        if (file_put_contents($speakers_file, json_encode($merged_speakers_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            return new WP_Error('file_error', 'Failed to write to speakers.json. Check file permissions.');
        }

        return count($final_speakers_list);
    }

    private function get_past_events_block_content() {
        // This is a full-page template, so the post_content can be a simple placeholder.
        return '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the past-events-page.php template. -->';
    }

    private function get_coc_block_content() {
        // This is a full-page template, so the post_content can be a simple placeholder.
        return '<!-- This page is rendered by the FOSSASIA Landing Page plugin using the code-of-conduct-page.php template. -->';
    }

    private function get_initial_speaker_data() {
        return [
            [
                "id" => "manual-1",
                "image" => plugins_url( 'images/speaker1.jpg', __FILE__ ),
                "category" => "Keynote",
                "name" => "Jane Doe",
                "title" => "Lead Developer, FOSSASIA",
                "bio" => "Jane is a passionate open-source contributor and the lead developer for the FOSSASIA event management system. She loves building tools that empower communities.",
                "social" => ["linkedin" => "", "twitter" => "", "github" => "", "website" => ""],
                "sessions" => [["title" => "The Future of Open-Source Events", "date" => "2025-03-13", "time" => "09:00", "end_time" => "09:45"]],
                "featured" => true,
                "featured_order" => 1
            ],
            [
                "id" => "manual-2",
                "image" => plugins_url( 'images/speaker2.jpg', __FILE__ ),
                "category" => "Artificial Intelligence",
                "name" => "John Smith",
                "title" => "AI Researcher, OpenTech University",
                "bio" => "John's work focuses on ethical AI and making machine learning accessible to everyone. He is a core maintainer of several popular open-source AI libraries.",
                "social" => ["linkedin" => "", "twitter" => "", "github" => "", "website" => ""],
                "sessions" => [["title" => "Demystifying Large Language Models", "date" => "2025-03-13", "time" => "10:00", "end_time" => "10:45"]],
                "featured" => true,
                "featured_order" => 2
            ]
        ];
    }

    private function get_initial_site_settings_data() {
        return [
            'hero_image_url' => '', // Removed plugin image reference
            'footer_text' => '© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok',
            'about_section_content' => '<p>Edit this content in the admin dashboard.</p>'
        ];
    }

    private function get_initial_theme_settings_data() {
        return [
            'brand_color' => '#D51007',
            'background_color' => '#f8f9fa',
            'text_color' => '#0b0b0b'
        ];
    }

    private function get_initial_navigation_data() {
        return [
            [ "text" => "Speakers", "href" => "#speakers" ],
            [ "text" => "Schedule", "href" => "#event-calendar" ],
            [ "text" => "Sponsors", "href" => "#sponsors" ],
            [ "text" => "Venue", "href" => "#venue" ]
        ];
    }

    private function get_initial_sponsor_data() {
        return [
            [
                "group_name" => "Platinum Sponsors",
                "sponsors" => [
                    [
                        "name" => "Sample Sponsor A",
                        "link" => "https://fossasia.org",
                        "image" => plugins_url( 'images/sponsor-logo1.png', __FILE__ )
                    ]
                ],
                "centered" => true,
                "logo_size" => 250
            ],
            [
                "group_name" => "Gold Sponsors",
                "sponsors" => [
                    [
                        "name" => "Sample Sponsor B",
                        "link" => "https://eventyay.com",
                        "image" => plugins_url( 'images/sponsor-logo2.png', __FILE__ )
                    ]
                ],
                "centered" => false,
                "logo_size" => 160
            ]
        ];
    }
}


new FOSSASIA_Landing_Plugin();

// Register WP-CLI command if in CLI context.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpfa-cli.php';
	WP_CLI::add_command( 'wpfa', 'WPFA_CLI' );
}
