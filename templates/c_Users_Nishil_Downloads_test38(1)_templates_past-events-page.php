<?php
/**
 * Template Name: FOSSASIA Past Events (Plugin)
 * Description: A page to list events that have already concluded.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$theme_settings_file = $data_dir . '/theme-settings.json';
$theme_settings_data = file_exists($theme_settings_file) ? json_decode(file_get_contents($theme_settings_file), true) : [];

$today = date('Y-m-d');

$past_events_query = new WP_Query([
    'post_type'      => 'page',
    'posts_per_page' => -1,
    'meta_key'       => '_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'DESC', // Show most recent past events first
    'meta_query'     => [
        'relation' => 'AND',
        [
            'key'     => '_wp_page_template',
            'value'   => 'fossasia-landing-template.php',
            'compare' => '=',
        ],
        [
            // This logic finds events where the end date is in the past.
            // If no end date, it checks if the start date is in the past.
            'relation' => 'OR',
            [
                'key' => '_event_end_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE'
            ],
            [
                'relation' => 'AND',
                [
                    'key' => '_event_end_date',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_event_date',
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'DATE'
                ]
            ]
        ]
    ],
]);
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
        .btn-secondary { background: #6c757d; color: #fff; padding: .6rem 1rem; border-radius: 999px; font-weight: 700; }
        .hero-ctas { margin-top: 2rem; }

        .main-content { background: #fff; padding: 20px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .main-content-header h1 { margin: 0; }

        #events-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .event-card { background: #fff; border-radius: var(--card-radius); box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(11,11,11,.1); }
        .event-card-link { text-decoration: none; color: inherit; }
        .event-card-image { height: 180px; background-color: #f0f0f0; }
        .event-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .event-card-content { padding: 15px; }
        .event-card-content h3 { margin: 0 0 10px; font-size: 1.25rem; }
        .event-card-content p { margin: 5px 0 0; color: var(--muted); font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .event-card-content p svg { width: 16px; height: 16px; flex-shrink: 0; }
        .placeholder-text { text-align: center; color: var(--muted); padding: 40px 0; }
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
            <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Upcoming Events</a>
            <a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>" style="background: #00000006;">Past Events</a>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-hero">
            <h1>Past FOSSASIA Events</h1>
            <p>A look back at our community events, meetups, and conferences.</p>
            <div class="hero-ctas">
                <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn-secondary">
                    View Upcoming Events
                </a>
            </div>
        </header>

        <div class="container">
            <div class="main-content">
                <div class="main-content-header">
                    <h1>Event Archive</h1>
                </div>

                <div id="events-container">
                    <?php
                    if ( $past_events_query->have_posts() ) :
                        while ( $past_events_query->have_posts() ) : $past_events_query->the_post();
                            $event_date = get_post_meta( get_the_ID(), '_event_date', true );
                            $event_end_date = get_post_meta( get_the_ID(), '_event_end_date', true );
                            $event_place = get_post_meta( get_the_ID(), '_event_place', true );
                            $featured_img_url = get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: plugins_url('../images/hero-image.jpg', __FILE__);
                            
                            $formatted_date = 'Date not set';
                            if (!empty($event_date)) {
                                $start = date_create($event_date);
                                if (!empty($event_end_date) && $event_end_date !== $event_date) {
                                    $end = date_create($event_end_date);
                                    $formatted_date = date_format($start, 'M j') . ' - ' . date_format($end, 'M j, Y');
                                } else {
                                    $formatted_date = date_format($start, 'F j, Y');
                                }
                            }
                    ?>
                        <div class="event-card">
                            <a href="<?php the_permalink(); ?>" class="event-card-link">
                                <div class="event-card-image">
                                    <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php the_title_attribute(); ?>">
                                </div>
                                <div class="event-card-content">
                                    <h3><?php the_title(); ?></h3>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path></svg> <?php echo esc_html($formatted_date); ?></p>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg> <?php echo esc_html($event_place); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p class="placeholder-text">No past events found in the archive.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
