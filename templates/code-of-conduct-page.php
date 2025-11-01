<?php
/**
 * Template Name: FOSSASIA Code of Conduct (Plugin)
 * Description: A page to display the event's Code of Conduct.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$global_settings_file = $data_dir . '/site-settings.json';
$global_theme_settings_file = $data_dir . '/theme-settings.json'; // This is a global page
$coc_content_file = $data_dir . '/coc-content.json';
$navigation_file = $data_dir . '/navigation.json'; // Added for navigation

if (!file_exists($global_settings_file)) { file_put_contents($global_settings_file, '{"site_logo_url": ""}'); }
if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
if (!file_exists($coc_content_file)) { file_put_contents($coc_content_file, '{"content": "<p>Placeholder CoC content.</p>"}'); }

$global_settings_data = json_decode(file_get_contents($global_settings_file), true);
$theme_settings_data = json_decode(file_get_contents($global_theme_settings_file), true);
$coc_content_data = json_decode(file_get_contents($coc_content_file), true);
$navigation_data = file_exists($navigation_file) ? json_decode(file_get_contents($navigation_file), true) : []; // Loaded
$coc_content = $coc_content_data['content'] ?? '<p>The Code of Conduct has not been set. Please add it in the admin dashboard.</p>';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
      :root {
        --brand: <?php echo esc_html($theme_settings_data['brand_color'] ?? '#D51007'); ?>;
        --bg: <?php echo esc_html($theme_settings_data['background_color'] ?? '#f8f9fa'); ?>;
        --text: <?php echo esc_html($theme_settings_data['text_color'] ?? '#0b0b0b'); ?>;
      }
    </style>
    <style>
        html, body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        a { color: var(--brand); text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        .site-logo { height: 36px; width: auto; }
        .container { width: 100%; max-width: 900px; margin: 0 auto; padding: 24px; }
        .nav { position: sticky; top: 0; background: rgba(255,255,255,.9); backdrop-filter: blur(6px) saturate(120%); border-bottom: 1px solid #00000010; z-index: 60; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; max-width: 1150px; margin: 0 auto; padding: 14px 24px;}
        .nav-links { display: flex; gap: .6rem; align-items: center; }
        .nav-links a { padding: .4rem .6rem; border-radius: 999px; font-weight: 600; color: #222; font-size: 0.9rem; }
        .nav-links a:hover { background: #00000006; }
        .admin-bar .nav { top: 32px; }
        @media (max-width: 782px) { .admin-bar .nav { top: 46px; } }

        .page-hero { text-align: center; padding: 60px 20px; background: #fff; margin-bottom: 30px; }
        .page-hero h1 { margin: 0 0 10px; font-size: 2.5rem; color: var(--brand); }
        .page-hero p { color: var(--muted); font-size: 1.1rem; max-width: 70ch; margin: 0 auto; }

        .main-content { background: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(11,11,11,.08); }
        .main-content h2 { font-size: 1.8rem; color: var(--brand); margin-top: 2.5rem; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .main-content h2:first-of-type { margin-top: 0; }
        .main-content p, .main-content li { line-height: 1.7; color: #333; }
        .main-content ul { padding-left: 20px; }
        .main-content strong { color: #111; }
    </style>
    <style>
        /* Nav Dropdown Styles */
        .nav-dropdown { position: relative; }
        .nav-dropdown-toggle {
            padding: .4rem .6rem; /* Match existing nav-links a padding */
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
        .nav-links-main .nav-dropdown-content a { padding: .4rem .6rem !important; white-space: nowrap; }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="nav-inner">
        <?php
            $logo_url = $global_settings_data['site_logo_url'] ?? plugins_url( '../assets/images/logo.png', __DIR__ . '/../wpfa-event.php' );
        ?>
        <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
            <?php
                $main_page_url = esc_url( home_url( '/code-of-conduct/' ) );

                echo '<div class="nav-links-main" style="flex-grow: 1;">';
                if (!empty($navigation_data) && is_array($navigation_data)) {
                    foreach ($navigation_data as $nav_item) {
                        $item_text = esc_html($nav_item['text']);
                        $item_type = $nav_item['type'] ?? 'link';

                        if ($item_type === 'link') {
                            $href = esc_url($nav_item['href']);
                            if (strpos($href, '#') === 0) $href = $main_page_url . $href;
                            $style = ($item_text === 'Code of Conduct') ? 'style="background: #00000006;"' : '';
                            echo "<a href=\"{$href}\" {$style}>{$item_text}</a>";
                        } elseif ($item_type === 'dropdown' && !empty($nav_item['items']) && is_array($nav_item['items'])) {
                            echo "<div class=\"nav-dropdown\">";
                            echo "<span class=\"nav-dropdown-toggle\">{$item_text}</span>";
                            echo "<div class=\"nav-dropdown-content\">";
                            foreach ($nav_item['items'] as $sub_item) {
                                $sub_href = esc_url($sub_item['href']);
                                if (strpos($sub_href, '#') === 0) $sub_href = $main_page_url . $sub_href;
                                echo "<a href=\"{$sub_href}\">" . esc_html($sub_item['text']) . "</a>";
                            }
                            echo "</div></div>";
                        }
                    }
                }
                echo '</div>';
            ?>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-hero">
            <h1>Code of Conduct</h1>
            <p>Our commitment to a safe, respectful, and harassment-free event experience for everyone.</p>
        </header>

        <div class="container">
            <div class="main-content">
                <?php echo wp_kses_post($coc_content); ?>
            </div>
        </div>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>