<?php
/**
 * Template Name: FOSSASIA Schedule (Plugin)
 * Description: A template for the full, grid-based event schedule.
 */

require_once( ABSPATH . 'wp-admin/includes/template.php' ); // For wp_kses_post

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$navigation_file = $data_dir . '/navigation.json';
$global_settings_file = $data_dir . '/site-settings.json';

// Get event_id from URL to load correct data
$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$schedule_file = $event_id ? $data_dir . '/schedule-' . $event_id . '.json' : '';
$settings_file = $event_id ? $data_dir . '/site-settings-' . $event_id . '.json' : '';

if (!file_exists($schedule_file)) { file_put_contents($schedule_file, '{}'); }
if (!file_exists($global_settings_file)) { file_put_contents($global_settings_file, '{"site_logo_url": ""}'); }
if ($event_id && !file_exists($settings_file)) { file_put_contents($settings_file, '{}'); }
if (!file_exists($navigation_file)) { file_put_contents($navigation_file, '[]'); }

$schedule_tables = json_decode(file_get_contents($schedule_file), true);
$site_settings_data = $event_id && file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$global_settings_data = json_decode(file_get_contents($global_settings_file), true);
$navigation_data = json_decode(file_get_contents($navigation_file), true);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        :root {
            --brand: #D51007; --brand-600: #b70f07; --bg: #f8f9fa; --text: #0b0b0b;
            --muted: #5b636a; --card-radius: 12px; --shadow: 0 5px 20px rgba(11,11,11,.07);
            --container: 1400px;
        }
        html, body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        a { color: var(--brand); text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        .site-logo { height: 36px; width: auto; }
        .site { min-height: 100vh; display: flex; flex-direction: column; }
        .container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 0 24px; }
        .nav { position: sticky; top: 0; background: rgba(255,255,255,.9); backdrop-filter: blur(6px) saturate(120%); border-bottom: 1px solid #00000010; z-index: 60; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; max-width: var(--container); margin: 0 auto; padding: 14px 24px; }
        .nav-links { display: flex; gap: .6rem; align-items: center; }
        .nav-links a { padding: .55rem .75rem; border-radius: 999px; font-weight: 600; color: #222; }
        .nav-links a:hover { background: #00000006; }
        .nav-links a.btn-primary { color: #fff; }
        .btn { display: inline-flex; gap: .6rem; align-items: center; padding: .6rem 1rem; border-radius: 999px; font-weight: 700; border: 2px solid transparent; }
        .btn-primary { background: var(--brand); color: #fff; box-shadow: 0 8px 20px rgba(213,16,7,.14); }
        footer.footer { padding: 28px 0; color: var(--muted); border-top: 1px solid #f3f4f6; text-align: center; background: #fff; margin-top: 40px; }
        .admin-bar .nav { top: 32px; }
        @media (max-width: 782px) { .admin-bar .nav { top: 46px; } }

        /* Page specific styles */
        .page-header { text-align: center; padding: 40px 20px 30px; background: #fff; border-bottom: 1px solid #eee; }
        .page-header h1 { margin: 0 0 10px; font-size: 2.5rem; color: var(--brand); }
        .page-header p { color: var(--muted); font-size: 1.1rem; max-width: 70ch; margin: 0 auto; }

        .schedule-table-wrapper {
            margin-bottom: 40px;
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .schedule-table-wrapper h2 {
            margin: 0;
            padding: 15px 20px;
            background: var(--brand);
            color: #fff;
            font-size: 1.4rem;
        }
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th, .schedule-table td { padding: 12px 15px; border: 1px solid #eee; text-align: left; vertical-align: top; }
        .schedule-table th { background: #f8f9fa; font-weight: 700; }
        .schedule-table td > *:first-child { margin-top: 0; }
        .schedule-table td > *:last-child { margin-bottom: 0; }

        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fefefe; margin: auto; padding: 20px 30px 30px; border: 1px solid #888; width: 80%; max-width: 700px; border-radius: var(--card-radius); position: relative; max-height: 90vh; overflow-y: auto; }
        .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        #session-modal-content h2 { margin-top: 0; color: var(--brand); }
        #session-modal-content .meta { color: var(--muted); font-size: 1rem; margin-bottom: 20px; }
        #session-modal-content h3 { font-size: 1.1rem; margin: 25px 0 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        #session-modal-content .abstract { line-height: 1.6; }
        .speaker-detail-card { display: flex; gap: 20px; margin-top: 15px; }
        .speaker-detail-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .speaker-detail-info .name { font-size: 1.2rem; font-weight: 600; margin: 0 0 5px; }
        .speaker-detail-info .title { font-size: 0.9rem; color: var(--muted); margin: 0 0 10px; }
        .speaker-detail-info .bio { font-size: 0.9rem; line-height: 1.5; }
        .social-links { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; }
        .social-links a { font-size: 0.9rem; text-decoration: none; font-weight: 600; }

        /* Nav Dropdown Styles */
        .nav-dropdown { position: relative; }
        .nav-dropdown-toggle {
            padding: .55rem .75rem; /* Match existing nav-links a padding */
            border-radius: 999px;
            font-weight: 600;
            color: #222;
            background: transparent;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .nav-dropdown-toggle:hover { background: #00000006; }
        .nav-dropdown-content {
            display: none; position: absolute; background-color: #f9f9f9; min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 8px; overflow: hidden;
            left: 0; /* Align dropdown to the left of the toggle */
        }
        .nav-dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; font-weight: 500; }
        .nav-dropdown-content a:hover { background-color: #f1f1f1; }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-links-main .nav-dropdown-content a { padding: .55rem .75rem !important; white-space: nowrap; }


        #no-schedule-info { text-align: center; padding: 50px; background: #fff; border-radius: var(--card-radius); box-shadow: var(--shadow); }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="nav-inner">
        <?php
            $event_page_url = $event_id ? get_permalink($event_id) : esc_url( get_permalink( get_page_by_path( 'events' ) ) );
        ?>
        <?php
            $logo_url = $site_settings_data['event_logo_url'] 
                        ?? $global_settings_data['site_logo_url'] 
                        ?? plugins_url( '../../assets/images/logo.png', __FILE__ );
        ?>
        <a href="<?php echo esc_url($event_page_url); ?>">
            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
          <?php
            $main_page_url = $event_id ? get_permalink($event_id) : esc_url( home_url( '/fossasia-summit/' ) );
            echo '<div class="nav-links-main">';
            if (!empty($navigation_data) && is_array($navigation_data)) {
                foreach ($navigation_data as $nav_item) {
                    $item_text = esc_html($nav_item['text']);
                    $item_type = $nav_item['type'] ?? 'link';

                    if ($item_type === 'link') {
                        $href = esc_url($nav_item['href']);
                        // Only prepend main_page_url if it's a hash link
                        if (strpos($href, '#') === 0) {
                            $href = $main_page_url . $href;
                        }
                        echo "<a href=\"{$href}\">{$item_text}</a>";
                    } elseif ($item_type === 'dropdown' && !empty($nav_item['items']) && is_array($nav_item['items'])) {
                        echo "<div class=\"nav-dropdown\">";
                        echo "<span class=\"nav-dropdown-toggle\">{$item_text}</span>";
                        echo "<div class=\"nav-dropdown-content\">";
                        foreach ($nav_item['items'] as $sub_item) {
                            $sub_href = esc_url($sub_item['href']);
                            if (strpos($sub_href, '#') === 0) {
                                $sub_href = $main_page_url . $sub_href;
                            }
                            echo "<a href=\"{$sub_href}\">" . esc_html($sub_item['text']) . "</a>";
                        }
                        echo "</div></div>";
                    }
                }
            }
            echo '</div>';
          ?>
          <div class="nav-links-secondary">
            <a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="btn btn-secondary">Back to Event</a>
            <a href="<?php echo esc_url( add_query_arg( 'event_id', $event_id, get_permalink( get_page_by_path( WpfaEvent_Public::SPEAKERS_PAGE_SLUG ) ) ) ); ?>">All Speakers</a>
            <?php if ( current_user_can( 'manage_options' ) ) : ?><a href="<?php echo esc_url( get_permalink( get_page_by_path( "admin-dashboard" ) ) ); ?>">Admin Dashboard</a><?php else : ?><a href="<?php echo esc_url( wp_login_url( get_permalink( get_page_by_path( "admin-dashboard" ) ) ) ); ?>">Admin Login</a><?php endif; ?>
            <a href="https://eventyay.com/e/4c0e0c27" target="_blank" rel="noopener" class="btn btn-primary">Register</a>
          </div>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-header">
            <h1>FOSSASIA Summit Schedule</h1>
            <p>Explore the full event schedule. More details will be added as they are confirmed.</p>
        </header>

        <div class="container">
            <?php
            if (empty($schedule_tables) || !isset($schedule_tables['name'])) {
                echo '<div id="no-schedule-info" style="text-align: center; padding: 50px; background: #fff; border-radius: var(--card-radius); box-shadow: var(--shadow);">';
                echo '<h2>Schedule Coming Soon</h2>';
                echo '<p>The detailed schedule will be announced shortly. Please check back later!</p>';
                echo '</div>';
            } else {
                $table = $schedule_tables;
                echo '<div class="schedule-table-wrapper">';
                echo '<h2>' . esc_html($table['name']) . '</h2>';
                echo '<div style="overflow-x:auto;">';
                echo '<table class="schedule-table">';
                
                $is_first_row = true;
                foreach ($table['data'] as $row_data) {
                    echo '<tr>';
                    foreach ($row_data as $cell_content) {
                        // Basic markdown to HTML conversion
                        $cell_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', esc_html($cell_content));
                        $cell_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $cell_html);
                        $cell_html = nl2br($cell_html);

                        $tag = $is_first_row ? 'th' : 'td';
                        echo "<{$tag}>" . wp_kses_post($cell_html) . "</{$tag}>";
                    }
                    echo '</tr>';
                    $is_first_row = false;
                }
                echo '</table>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </main>

    <!-- Session Detail Modal -->
    <div id="session-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="session-modal-content"></div>
        </div>
    </div>

    <footer class="footer">
        <small>© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok</small>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>