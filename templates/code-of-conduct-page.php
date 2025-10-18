<?php
/**
 * Template Name: FOSSASIA Code of Conduct (Plugin)
 * Description: A page to display the event's Code of Conduct.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$global_theme_settings_file = $data_dir . '/theme-settings.json'; // This is a global page
$coc_content_file = $data_dir . '/coc-content.json';

if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
if (!file_exists($coc_content_file)) { file_put_contents($coc_content_file, '{"content": "<p>Placeholder CoC content.</p>"}'); }

$theme_settings_data = json_decode(file_get_contents($global_theme_settings_file), true);
$coc_content_data = json_decode(file_get_contents($coc_content_file), true);
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
        .container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 24px; }
        .nav { position: sticky; top: 0; background: rgba(255,255,255,.9); backdrop-filter: blur(6px) saturate(120%); border-bottom: 1px solid #00000010; z-index: 60; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; }
        .nav-links { display: flex; gap: .6rem; align-items: center; }
        .nav-links a { padding: .4rem .6rem; border-radius: 999px; font-weight: 600; color: #222; font-size: 0.9rem; }
        .nav-links a:hover { background: #00000006; }
        .admin-bar .nav { top: 32px; }
        @media (max-width: 782px) { .admin-bar .nav { top: 46px; } }

        .page-hero { text-align: center; padding: 60px 20px; background: #fff; margin-bottom: 30px; }
        .page-hero h1 { margin: 0 0 10px; font-size: 2.5rem; color: var(--brand); }
        .page-hero p { color: var(--muted); font-size: 1.1rem; max-width: 70ch; margin: 0 auto; }

        .main-content { background: #fff; padding: 30px 40px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .main-content h2 { font-size: 1.8rem; color: var(--brand); margin-top: 2.5rem; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .main-content h2:first-of-type { margin-top: 0; }
        .main-content p, .main-content li { line-height: 1.7; color: #333; }
        .main-content ul { padding-left: 20px; }
        .main-content strong { color: #111; }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="container nav-inner">
        <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            <img src="<?php echo plugins_url( '../images/logo.png', __FILE__ ); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
            <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Events</a>
            <a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>">Past Events</a>
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
