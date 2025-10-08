<?php
/**
 *
 * Template Name: FOSSASIA Events Listing (Plugin)
 * Description: A page to list events and show latest news from the FOSSASIA blog.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$navigation_file = $data_dir . '/navigation.json';
$global_theme_settings_file = $data_dir . '/theme-settings.json'; // This page is global, so it uses the global theme.
$settings_file = $data_dir . '/site-settings.json';

if (!file_exists($navigation_file)) { file_put_contents($navigation_file, '[]'); }
$site_settings_data = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
$theme_settings_data = json_decode(file_get_contents($global_theme_settings_file), true);
$navigation_data = json_decode(file_get_contents($navigation_file), true);

$today = date('Y-m-d');

$all_events_query = new WP_Query([
    'post_type'      => 'page',
    'posts_per_page' => -1,
    'meta_key'       => '_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [
        'relation' => 'AND',
        [
            'key'     => '_wp_page_template',
            'value'   => 'fossasia-landing-template.php'
        ],
        [
            // This logic finds events where the end date is today or in the future,
            // OR if there is no end date, where the start date is today or in the future.
            'relation' => 'OR',
            [
                'relation' => 'AND',
                [
                    'key' => '_event_end_date',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => '_event_end_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ],
            [
                'key'     => '_event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            ]
        ]
    ]
]);
$calendar_events = [];
if ($all_events_query->have_posts()) {
    while ($all_events_query->have_posts()) {
        $all_events_query->the_post();
        $event_date = get_post_meta(get_the_ID(), '_event_date', true);
        $event_end_date = get_post_meta(get_the_ID(), '_event_end_date', true);
        $event_place = get_post_meta(get_the_ID(), '_event_place', true);
        $event_time = get_post_meta(get_the_ID(), '_event_time', true);
        $event_description = get_the_excerpt();
        $featured_img_url = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: plugins_url('../images/hero-image.jpg', __FILE__);

        $calendar_events[] = [
            'id'          => get_the_ID(),
            'name'        => get_the_title(),
            'date'        => $event_date,
            'endDate'     => $event_end_date,
            'place'       => $event_place,
            'time'        => $event_time,
            'description' => $event_description,
            'permalink'   => get_the_permalink(),
            'image_url'   => $featured_img_url,
            'year'        => !empty($event_date) ? date('Y', strtotime($event_date)) : null
        ];
    }
}

/**
 * Fetches and renders latest blog posts from FOSSASIA blog.
 */
function render_latest_news() {
    include_once( ABSPATH . WPINC . '/feed.php' );

    // Get a SimplePie feed object from the specified feed source.
    $rss = fetch_feed( 'https://blog.fossasia.org/rss/' );

    if ( is_wp_error( $rss ) ) {
        echo '<p>Could not fetch news. Please try again later.</p>';
        return;
    }

    // Figure out how many total items there are, but limit it to 5. 
    $maxitems = $rss->get_item_quantity( 5 ); 

    // Build an array of all the items, starting with element 0 (first element).
    $rss_items = $rss->get_items( 0, $maxitems );

    if ( $maxitems == 0 ) {
        echo '<p>No news items found.</p>';
    } else {
        // Loop through each feed item and display each item as a hyperlink.
        echo '<ul class="news-list">';
        foreach ( $rss_items as $item ) : ?>
            <li class="news-item">
                <a href='<?php echo esc_url( $item->get_permalink() ); ?>'
                title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>' target="_blank" rel="noopener">
                    <?php echo esc_html( $item->get_title() ); ?>
                </a>
                <small class="news-date"><?php echo $item->get_date('F j, Y'); ?></small>
            </li>
        <?php endforeach;
        echo '</ul>';
        echo '<div class="news-cta"><a href="https://blog.fossasia.org/" target="_blank" rel="noopener" class="btn btn-primary">Visit Blog &rarr;</a></div>';
    }
}
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
        :root {
            --brand: #D51007; --bg: #f8f9fa; --text: #0b0b0b; --muted: #5b636a;
            --card-radius: 16px; --shadow: 0 10px 30px rgba(11,11,11,.08); --container: 1150px;
        }
        html, body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        a { color: var(--brand); text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        .site { min-height: 100vh; display: flex; flex-direction: column; }
        .site-logo { height: 36px; width: auto; }
        .container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 24px; }
        .nav { position: sticky; top: 0; background: rgba(255,255,255,.9); backdrop-filter: blur(6px) saturate(120%); border-bottom: 1px solid #00000010; z-index: 60; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; }
        .nav-links { display: flex; gap: .6rem; align-items: center; }
        .nav-links a { padding: .4rem .6rem; border-radius: 999px; font-weight: 600; color: #222; font-size: 0.9rem; }
        .nav-links a:hover { background: #00000006; }
        .nav-links a.btn-primary { font-size: 0.9rem; padding: .5rem 1rem; }
        .nav-links a.btn-primary { color: #fff; }
        .btn { display: inline-flex; gap: .6rem; align-items: center; padding: .6rem 1rem; border-radius: 999px; font-weight: 700; border: 2px solid transparent; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-primary { background: var(--brand); color: #fff; box-shadow: 0 8px 20px rgba(213,16,7,.14); }
        footer.footer { padding: 28px 0; color: var(--muted); border-top: 1px solid #f3f4f6; text-align: center; background: #fff; margin-top: 40px; }
        .admin-bar .nav { top: 32px; }
        .social-icons { display: flex; gap: 1rem; margin-top: 1rem; justify-content: center; }
        .social-icons a { color: var(--muted); }
        .social-icons svg { width: 24px; height: 24px; }
        .social-icons a:hover { color: var(--brand); }
        @media (max-width: 782px) { .admin-bar .nav { top: 46px; } }

        /* Page Layout */
        .page-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            align-items: start; /* Prevent items from stretching vertically */
            gap: 30px;
            margin-top: 40px;
        }
        @media (max-width: 980px) {
            .page-layout { grid-template-columns: 1fr; }
        }
        .main-content { background: #fff; padding: 20px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .sidebar { background: #fff; padding: 20px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .sidebar h2 { margin-top: 0; font-size: 1.5rem; color: var(--brand); }

        /* News List */
        .news-list { list-style: none; padding: 0; margin: 0; }
        .news-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .news-item:last-child { border-bottom: none; }
        .news-item a { font-weight: 600; color: var(--text); display: block; margin-bottom: 5px; }
        .news-item a:hover { color: var(--brand); }
        .news-date { font-size: 0.85rem; color: var(--muted); }
    </style>
    <style>
        /* News CTA button style */
        .news-cta { margin-top: 20px; text-align: center; }
    </style>
    <style> 
        /* Hero Section Styles */
        .page-hero {
            text-align: center; padding: 60px 20px; background: #fff;
            margin-bottom: 30px; position: relative; overflow: hidden;
        }
        .hero-bg {
            position: absolute; inset: 0; z-index: 0;
            background: radial-gradient(40vw 40vw at 10% 50%, #feeceb, transparent),
                        radial-gradient(30vw 30vw at 90% 50%, #fddedc, transparent);
            mix-blend-mode: normal; opacity: .9; pointer-events: none;
        }
        .page-hero > * { position: relative; z-index: 1; }
        .page-hero h1 { margin: 0 0 10px; font-size: 2.5rem; color: var(--brand); }
        .page-hero p { color: var(--muted); font-size: 1.1rem; max-width: 70ch; margin: 0 auto; }
        .page-hero .hero-ctas { display: flex; gap: .7rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; }
        .news-cta .btn { padding: .5rem 1.2rem; }
    </style>
    <style>
        /* Search and Filter Styles */
        .search-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
        }
        .search-input {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 16px;
        }
        .results-info {
            text-align: center;
            margin: 20px 0;
            font-size: 1.1rem;
            color: var(--muted);
            display: none; /* Hidden by default, shown by JS during search */
        }
    </style>
    <style>
        /* Modal and Form Styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background-color: #fefefe; margin: auto; padding: 20px 30px 30px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: var(--card-radius); position: relative; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
        .event-card-actions .btn-edit-event { background: #17a2b8; }
        .event-card-actions .btn-edit-event:hover { background: #138496; }
        .btn-edit-content { background: #ffc107; color: #212529 !important; border: none; border-radius: 4px; padding: 5px 8px; font-size: 12px; cursor: pointer; text-decoration: none; font-weight: bold; display: inline-block; }
        .btn-edit-content:hover { background: #e0a800; }
        .event-card-actions .btn-delete-event { background: #dc3545; }
        .event-card-actions .btn-delete-event:hover { background: #c82333; }
        .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        #createEventForm { display: flex; flex-direction: column; gap: 15px; }
        #createEventForm h2 { margin-top: 0; color: var(--brand); }
        #createEventForm label { font-weight: 600; margin-bottom: -10px; }
        #createEventForm input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem; }
        #createEventForm .char-counter, #editEventForm .char-counter { font-size: 0.85rem; color: var(--muted); text-align: right; margin-top: -10px; }
        #createEventForm button { margin-top: 15px; align-self: flex-start; }

        /* Event Card Styles */
        .main-content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .main-content-header h1 { margin: 0; }
        .event-card-actions { position: absolute; top: 10px; right: 10px; z-index: 5; display: flex; gap: 5px; }
        .event-card-actions button { background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 4px; padding: 5px 8px; font-size: 12px; cursor: pointer; }
        .event-card-actions button:hover { background: rgba(0,0,0,0.8); }
        .event-card-link { text-decoration: none; color: inherit; }
        #events-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .event-card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(11,11,11,.1); }
        .event-card-image { height: 180px; background-color: #f0f0f0; }
        .event-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .event-card-content { padding: 15px; }
        .event-card-content h3 { margin: 0 0 10px; font-size: 1.25rem; }
        .event-card-content p { margin: 5px 0 0; color: var(--muted); font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .event-card-description { font-size: 0.9rem; color: var(--muted); line-height: 1.5; margin-top: 10px; }
        #events-container .placeholder-text {
            grid-column: 1 / -1; /* Span full width */
            text-align: center;
            color: var(--muted);
            padding: 40px 0;
        }
        .calendar-link-section {
            margin-top: 50px;
            grid-column: 1 / -1; /* Span full width */
            text-align: center;
            color: var(--muted);
            padding: 40px 0;
        }
        .event-card-content p svg { width: 16px; height: 16px; flex-shrink: 0; }

    </style>
    <style>
        /* Calendar Styles */
        #calendar-container { max-width: 900px; margin: 40px auto; border: 1px solid #eee; border-radius: 12px; box-shadow: var(--shadow); padding: 10px; }
        #calendar-container .header { height: 50px; width: 100%; background: var(--brand); text-align: center; position:relative; z-index: 100; }
        #calendar-container .header h1 { margin: 0; padding: 0; font-size: 20px; line-height: 50px; font-weight: 600; letter-spacing: 1px; color: #fff; }
        #calendar-container .day { display: inline-block; width: calc(100% / 7); padding: 10px; text-align: center; vertical-align: top; cursor: pointer; background: var(--bg); position: relative; z-index: 100; min-height: 100px; }
        #calendar-container .left, #calendar-container .right {
            position: absolute; width: 0px; height: 0px; border-style: solid;
            top: 50%; margin-top: -7.5px; cursor: pointer;
        }
        #calendar-container .left {
            border-width: 7.5px 10px 7.5px 0;
            border-color: transparent #fff transparent transparent; left: 20px;
        }
        #calendar-container .right {
            border-width: 7.5px 0 7.5px 10px;
            border-color: transparent transparent transparent #fff; right: 20px;
        }
        #calendar-container .day.other { color: #ccc; }
        #calendar-container .day.today { color: var(--brand); }
        #calendar-container .day-name { font-size: 9px; text-transform: uppercase; margin-bottom: 5px; color: var(--muted); letter-spacing: .7px; }
        #calendar-container .day-number { font-size: 24px; letter-spacing: 1.5px; }
        #calendar-container .day .day-events { list-style: none; margin-top: 3px; text-align: center; height: 12px; line-height: 6px; overflow: hidden; }
        #calendar-container .day .day-events .event-dot { display: inline-block; vertical-align: top; width: 5px; height: 5px; margin: 0 1px; border-radius: 50%; background: var(--brand); }
        /* Details popup */
        #calendar-container .details { position: relative; width: 100%; background: #fff; margin-top: 5px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        #calendar-container .arrow { position: absolute; top: -5px; left: 50%; margin-left: -2px; width: 0px; height: 0px; border-style: solid; border-width: 0 5px 5px 5px; border-color: transparent transparent #fff transparent; }
        #calendar-container .events-list { list-style: none; padding: 10px; margin: 0; max-height: 150px; overflow-y: auto; }
        #calendar-container .events-list li { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; }
        #calendar-container .events-list li:last-child { border-bottom: none; }
        #calendar-container .events-list a { color: var(--text); font-weight: 600; font-size: 0.9rem; }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="container nav-inner">
        <a href="<?php echo esc_url( home_url( '/fossasia-summit/' ) ); ?>">
            <img src="<?php echo plugins_url( '../images/logo.png', __FILE__ ); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
            <a href="<?php echo esc_url( home_url( '/fossasia-summit/#about' ) ); ?>">About</a>
            <a href="https://blog.fossasia.org/" target="_blank" rel="noopener">Blog</a>
            <a href="#" title="Contact Us">Contact</a>
            <a href="<?php echo esc_url( home_url( '/fossasia-summit/#sponsors' ) ); ?>">Sponsor</a>
            <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" style="font-weight: bold; color: var(--brand);">Upcoming Events</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'code-of-conduct' ) ) ); ?>" title="Code of Conduct">Code of Conduct</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'past-events' ) ) ); ?>" title="Past Events">Past Events</a>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-hero">
            <div class="hero-bg" aria-hidden="true"></div>
            <h1>FOSSASIA Events</h1>
            <p>Discover upcoming community events, local meetups, and partner conferences from the FOSSASIA network.</p>
        </header>

        <div class="container">
            <div class="page-layout">
                <div class="main-content">
                    <div class="main-content-header">
                        <h1>Events</h1>
                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                            <button id="createEventBtn" class="btn btn-primary">Create Event</button>
                        <?php endif; ?>
                    </div>
                    <div class="search-section" style="padding: 0; margin-bottom: 20px; background: transparent;">
                        <input type="text" id="eventSearchInput" class="search-input" placeholder="Search by name, place, or description...">
                    </div>

                    <div class="results-info">
                        Showing <span id="resultsCount">0</span> matching events.
                    </div>

                    <div id="events-container">
                        <?php
                        if ( $all_events_query->have_posts() ) :
                            while ( $all_events_query->have_posts() ) : $all_events_query->the_post();
                                $event_date = get_post_meta( get_the_ID(), '_event_date', true );
                                $event_end_date = get_post_meta( get_the_ID(), '_event_end_date', true );
                                $event_place = get_post_meta( get_the_ID(), '_event_place', true );
                                $event_description = get_the_excerpt();
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
                            <div class="event-card" 
                                data-post-id="<?php echo get_the_ID(); ?>"
                                data-name="<?php echo esc_attr(get_the_title()); ?>"
                                data-date="<?php echo esc_attr($event_date); ?>"
                                data-place="<?php echo esc_attr($event_place); ?>"
                                data-time="<?php echo esc_attr(get_post_meta( get_the_ID(), '_event_time', true )); ?>"
                                data-end-date="<?php echo esc_attr($event_end_date); ?>"
                                data-description="<?php echo esc_attr($event_description); ?>"
                                data-lead-text="<?php echo esc_attr(get_post_meta( get_the_ID(), '_event_lead_text', true )); ?>"
                                data-registration-link="<?php echo esc_attr(get_post_meta( get_the_ID(), '_event_registration_link', true )); ?>"
                                data-cfs-link="<?php echo esc_attr(get_post_meta( get_the_ID(), '_event_cfs_link', true )); ?>"
                                data-image-url="<?php echo esc_url($featured_img_url); ?>"
                                data-permalink="<?php the_permalink(); ?>">
                                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                <?php
                                    $dashboard_url = get_permalink( get_page_by_path( "admin-dashboard" ) );
                                    $edit_content_url = add_query_arg( 'event_id', get_the_ID(), $dashboard_url );
                                ?>
                                <div class="event-card-actions">
                                    <button class="btn-edit-event">Edit Details</button>
                                    <a href="<?php echo esc_url($edit_content_url); ?>" class="btn-edit-content">Edit Content</a>
                                    <button class="btn-delete-event">Delete</button>
                                </div>
                                <?php endif; ?>

                                <a href="<?php the_permalink(); ?>" class="event-card-link">
                                <div class="event-card-image">
                                    <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php the_title_attribute(); ?>">
                                </div>
                                <div class="event-card-content">
                                    <h3><?php the_title(); ?></h3>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path></svg> <?php echo esc_html($formatted_date); ?></p>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg> <?php echo esc_html($event_place); ?></p><p class="event-card-description"><?php echo esc_html($event_description); ?></p>
                                </div>
                                </a>
                            </div>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                            echo '<p class="placeholder-text">No events created yet. Click "Create Event" to add one!</p>';
                        endif;
                        ?>
                    </div>

                    <div class="calendar-link-section">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'past-events' ) ) ); ?>" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none; font-size: 1.2rem; font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 28px; height: 28px;"><path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"></path></svg>
                            <span>View Past Events</span>
                        </a>
                    </div>
                </div>
                <aside class="sidebar">
                    <h2>Latest News</h2>
                    <?php render_latest_news(); ?>
                </aside>
            </div>
        </div>
    </main>

<!-- Create Event Modal -->
<div id="createEventModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="createEventForm">
            <h2>Create a New Event</h2>
            <label for="eventName">Event Name:</label>
            <input type="text" id="eventName" name="eventName" required>

            <label for="eventDate">Event Date:</label>
            <input type="date" id="eventDate" name="eventDate" required>

            <label for="eventEndDate">Event End Date (optional):</label>
            <input type="date" id="eventEndDate" name="eventEndDate">

            <label for="eventTime">Event Time:</label>
            <input type="time" id="eventTime" name="eventTime" required>

            <label for="eventPlace">Event Place:</label>
            <input type="text" id="eventPlace" name="eventPlace" required>

            <label for="eventDescription">Description (2-3 sentences):</label>
            <textarea id="eventDescription" name="eventDescription" rows="3" required maxlength="300"></textarea>
            <small class="char-counter">0 / 300</small>

            <label for="eventLeadText">Hero Lead Text (under title):</label>
            <textarea id="eventLeadText" name="eventLeadText" rows="2" required maxlength="160"></textarea>
            <small class="char-counter">0 / 160</small>

            <label for="eventRegistrationLink">Registration Link:</label>
            <input type="url" id="eventRegistrationLink" name="eventRegistrationLink" placeholder="https://eventyay.com/e/..." required>

            <label for="eventCfsLink">Call for Speakers Link (optional):</label>
            <input type="url" id="eventCfsLink" name="eventCfsLink" placeholder="https://eventyay.com/e/.../cfs">

            <label for="eventPicture">Event Picture:</label>
            <input type="file" id="eventPicture" name="eventPicture" accept="image/*" required>

            <button type="submit" class="btn btn-primary">Create Card</button>
        </form>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="editEventForm">
            <h2>Edit Event</h2>
            <input type="hidden" id="editEventId" name="postId">
            <label for="editEventName">Event Name:</label>
            <input type="text" id="editEventName" name="eventName" required>

            <label for="editEventDate">Event Date:</label>
            <input type="date" id="editEventDate" name="eventDate" required>

            <label for="editEventEndDate">Event End Date (optional):</label>
            <input type="date" id="editEventEndDate" name="eventEndDate">

            <label for="editEventTime">Event Time:</label>
            <input type="time" id="editEventTime" name="eventTime" required>

            <label for="editEventPlace">Event Place:</label>
            <input type="text" id="editEventPlace" name="eventPlace" required>

            <label for="editEventDescription">Description (2-3 sentences):</label>
            <textarea id="editEventDescription" name="eventDescription" rows="3" required maxlength="300"></textarea>
            <small class="char-counter">0 / 300</small>

            <label for="editEventLeadText">Hero Lead Text:</label>
            <textarea id="editEventLeadText" name="eventLeadText" rows="2" required maxlength="160"></textarea>
            <small class="char-counter">0 / 160</small>

            <label for="editRegistrationLink">Registration Link:</label>
            <input type="url" id="editRegistrationLink" name="eventRegistrationLink" placeholder="https://eventyay.com/e/..." required>

            <label for="editCfsLink">Call for Speakers Link (optional):</label>
            <input type="url" id="editCfsLink" name="eventCfsLink" placeholder="https://eventyay.com/e/.../cfs">

            <label for="editEventPicture">Update Picture (optional):</label>
            <input type="file" id="editEventPicture" name="eventPicture" accept="image/*">

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

    <footer class="footer">
        <div class="container">
            <small id="footer-text-display">
                <?php echo esc_html( $site_settings_data['footer_text'] ?? '© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok' ); ?>
            </small>
            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                <button id="editFooterBtn" class="btn btn-secondary" style="margin-left: 15px; padding: 4px 10px; font-size: 12px; border-radius: 8px;">Edit</button>
            <?php endif; ?>
            <div class="social-icons">
                <a href="https://github.com/fossasia" target="_blank" rel="noopener" title="GitHub"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg></a>
                <a href="https://www.facebook.com/fossasia" target="_blank" rel="noopener" title="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg></a>
                <a href="https://www.flickr.com/photos/fossasia/" target="_blank" rel="noopener" title="Flickr"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 7.5c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zm11 0c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5z"/></svg></a>
                <a href="https://www.linkedin.com/company/fossasia" target="_blank" rel="noopener" title="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                <a href="https://www.youtube.com/c/fossasia" target="_blank" rel="noopener" title="YouTube"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                <a href="https://x.com/fossasia" target="_blank" rel="noopener" title="X (Twitter)"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.62l-5.21-6.817-6.022 6.817h-3.308l7.748-8.786-8.3-10.714h6.78l4.596 6.145 5.45-6.145zm-2.46 17.63h1.89l-9.48-12.605h-1.93l9.52 12.605z"/></svg></a>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<!-- Edit Footer Modal -->
<div id="editFooterModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="editFooterForm">
            <h2>Edit Footer Text</h2>
            <label for="footerText">Footer Content:</label>
            <input type="text" id="footerText" name="footerText" required>
            <button type="submit" class="btn btn-primary">Save Footer</button>
        </form>
    </div>
</div>

<style>
    #editFooterForm { display: flex; flex-direction: column; gap: 15px; }
    #editFooterForm h2 { margin-top: 0; color: var(--brand); }
    #editFooterForm label { font-weight: 600; margin-bottom: -10px; }
    #editFooterForm input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem; }
    #editFooterForm button { margin-top: 15px; align-self: flex-start; }
</style>

<?php wp_footer(); ?>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment.min.js"></script>
<script>
/**
 * Manages the UI and logic for the FOSSASIA Events Listing page.
 * This includes handling the "Create Event" modal and form submission.
 */
class EventsPageManager {
    constructor(ajaxUrl, nonce) {
        this.ajaxUrl = ajaxUrl;
        this.nonce = nonce;
        this.modal = document.getElementById('createEventModal');
        this.createBtn = document.getElementById('createEventBtn');
        this.closeBtn = this.modal.querySelector('.close-btn');
        this.eventForm = document.getElementById('createEventForm');
        this.eventsContainer = document.getElementById('events-container');
        this.submitButton = this.eventForm.querySelector('button[type="submit"]');
        this.editModal = document.getElementById('editEventModal');
        this.editForm = document.getElementById('editEventForm');
        this.closeEditBtn = this.editModal.querySelector('.close-btn');
        this.editFooterModal = document.getElementById('editFooterModal');
        this.editFooterBtn = document.getElementById('editFooterBtn');
        this.editFooterForm = document.getElementById('editFooterForm');
        this.searchInput = document.getElementById('eventSearchInput'); // Added for search
        this.resultsInfo = document.querySelector('.results-info'); // Added for search
        this.createDescriptionTextarea = document.getElementById('eventDescription');
        this.editDescriptionTextarea = document.getElementById('editEventDescription');
        this.createLeadTextarea = document.getElementById('eventLeadText');
    }

    /**
     * Initializes all event listeners for the page.
     */
    init() {
        this.createBtn?.addEventListener('click', () => this.openModal());
        this.closeBtn?.addEventListener('click', () => this.closeModal());
        window.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.closeModal();
            }
        });
        this.eventForm?.addEventListener('submit', (e) => this.handleFormSubmit(e));
        this.closeEditBtn?.addEventListener('click', () => this.closeEditModal());
        window.addEventListener('click', (event) => {
            if (event.target === this.editModal) {
                this.closeEditModal();
            }
        });
        this.editForm?.addEventListener('submit', (e) => this.handleEditFormSubmit(e));
        this.eventsContainer?.addEventListener('click', (e) => this.handleCardActions(e));

        if (this.editFooterBtn) {
            this.editFooterBtn.addEventListener('click', () => this.openFooterModal());
            this.editFooterModal.querySelector('.close-btn').addEventListener('click', () => this.closeFooterModal());
            window.addEventListener('click', (event) => {
                if (event.target === this.editFooterModal) this.closeFooterModal();
            });
            this.editFooterForm.addEventListener('submit', (e) => this.handleFooterFormSubmit(e));
            this.setupCharCounters();
        }
    }

    initSearch() {
        this.searchInput?.addEventListener('keyup', () => this.filterEvents());
    }    

    openModal() {
        this.eventForm.reset();
        const counter = this.createDescriptionTextarea.nextElementSibling;
        if (counter) counter.textContent = `0 / ${this.createDescriptionTextarea.maxLength}`;
        const leadCounter = this.createLeadTextarea.nextElementSibling;
        if (leadCounter) leadCounter.textContent = `0 / ${this.createLeadTextarea.maxLength}`;
        this.modal.style.display = 'flex';
    }

    closeModal() {
        this.modal.style.display = 'none';
    }

    openEditModal() {
        this.editModal.style.display = 'flex';
    }

    closeEditModal() {
        this.editModal.style.display = 'none';
    }

    setupCharCounters() {
        const setupCounter = (textarea) => {
            if (!textarea) return;
            const counter = textarea.nextElementSibling;
            if (!counter || !counter.classList.contains('char-counter')) return;

            const updateCount = () => {
                counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
            };

            textarea.addEventListener('input', updateCount);
        };

        setupCounter(this.createDescriptionTextarea);
        setupCounter(this.editDescriptionTextarea);
        setupCounter(this.createLeadTextarea);
    }

    openFooterModal() {
        const currentText = document.getElementById('footer-text-display').textContent.trim();
        this.editFooterForm.querySelector('#footerText').value = currentText;
        this.editFooterModal.style.display = 'flex';
    }

    closeFooterModal() {
        this.editFooterModal.style.display = 'none';
    }

    handleFooterFormSubmit(e) {
        e.preventDefault();
        const newFooterText = this.editFooterForm.querySelector('#footerText').value;
        const submitBtn = this.editFooterForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        // We can reuse the existing site settings AJAX handler
        const newSettings = { footer_text: newFooterText };
        const ajaxFormData = new FormData();
        ajaxFormData.append('action', 'fossasia_manage_site_settings');
        ajaxFormData.append('nonce', this.nonce);
        ajaxFormData.append('settings', JSON.stringify(newSettings));

        fetch(this.ajaxUrl, { method: 'POST', body: ajaxFormData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('footer-text-display').textContent = newFooterText;
                    this.closeFooterModal();
                } else {
                    alert('Error: ' + data.data);
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Footer';
            });
    }

    /**
     * Handles the event creation form submission.
     * @param {Event} e The form submission event.
     */
    handleFormSubmit(e) {
        e.preventDefault();
        this.toggleSubmitButton(true, 'Creating...');

        const formData = new FormData(this.eventForm);
        formData.append('action', 'fossasia_create_event_page');
        formData.append('nonce', this.nonce);

        fetch(this.ajaxUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => this.handleFormResponse(data))
            .catch(error => this.handleFormError(error));
    }

    handleFormResponse(data) {
        if (data.success) {
            alert(data.data.message);
            this.closeModal();
            this.eventForm.reset();
            this.addEventCardToUI(data.data.eventData);
            this.updateCalendar(data.data.eventData);
        } else {
            alert('Error: ' + data.data);
        }
        this.toggleSubmitButton(false, 'Create Card');
    }

    handleFormError(error) {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
        this.toggleSubmitButton(false, 'Create Card');
    }

    toggleSubmitButton(isSubmitting, text) {
        this.submitButton.disabled = isSubmitting;
        this.submitButton.textContent = text;
    }

    handleCardActions(e) {
        const target = e.target;
        if (target.matches('.btn-edit-event')) {
            e.preventDefault();
            e.stopPropagation();
            const card = target.closest('.event-card');
            this.populateAndOpenEditModal(card);
        } else if (target.matches('.btn-delete-event')) {
            e.preventDefault();
            e.stopPropagation();
            const card = target.closest('.event-card');
            this.handleDeleteEvent(card);
        }
    }

    populateAndOpenEditModal(card) {
        this.editForm.querySelector('#editEventId').value = card.dataset.postId;
        this.editForm.querySelector('#editEventName').value = card.dataset.name;
        this.editForm.querySelector('#editEventDate').value = card.dataset.date;
        this.editForm.querySelector('#editEventEndDate').value = card.dataset.endDate;
        this.editForm.querySelector('#editEventPlace').value = card.dataset.place;
        this.editForm.querySelector('#editEventTime').value = card.dataset.time;
        this.editForm.querySelector('#editEventDescription').value = card.dataset.description;
        this.editForm.querySelector('#editEventLeadText').value = card.dataset.leadText || '';
        this.editForm.querySelector('#editRegistrationLink').value = card.dataset.registrationLink;
        this.editForm.querySelector('#editCfsLink').value = card.dataset.cfsLink || '';
        // Update character counter for edit modal
        const textarea = this.editForm.querySelector('#editEventDescription');
        const counter = textarea.nextElementSibling;
        if (counter) counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
        this.openEditModal();
    }

    handleEditFormSubmit(e) {
        e.preventDefault();
        const editButton = this.editForm.querySelector('button[type="submit"]');
        editButton.disabled = true;
        editButton.textContent = 'Saving...';

        const formData = new FormData(this.editForm);
        formData.append('action', 'fossasia_edit_event_page');
        formData.append('nonce', this.nonce);

        fetch(this.ajaxUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    const postId = parseInt(formData.get('postId'), 10);
                    const updatedEventData = data.data.eventData;
                    
                    // Find the event in the calendar data using its unique ID
                    const indexToUpdate = calendarEventsData.findIndex(event => event.id == postId);
                    
                    if (indexToUpdate > -1) {
                        // Data is now handled by PHP, a page reload is simplest
                        window.location.reload();
                    }

                    this.closeEditModal();
                } else {
                    alert('Error: ' + data.data);
                }
                editButton.disabled = false;
                editButton.textContent = 'Save Changes';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
                editButton.disabled = false;
                editButton.textContent = 'Save Changes';
            });
    }

    handleDeleteEvent(card) {
        const postId = card.dataset.postId;
        if (!confirm(`Are you sure you want to delete the event "${card.dataset.name}"? This cannot be undone.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'fossasia_delete_event_page');
        formData.append('nonce', this.nonce);
        formData.append('postId', postId);

        fetch(this.ajaxUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    card.remove();
                } else {
                    alert('Error: ' + data.data);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    /**
     * Creates and appends a new event card to the container.
     * @param {object} eventData - The data for the new event.
     */
    addEventCardToUI(eventData) {
        const placeholder = this.eventsContainer.querySelector('.placeholder-text');
        if (placeholder) {
            placeholder.remove();
        }

        const cardDiv = document.createElement('div');
        cardDiv.className = 'event-card';
        // Just reload the page to see the new card in its sorted position.
        window.location.reload();

        let formattedDate = 'Date not set';
        if (date) {
            const start = new Date(date + 'T00:00:00'); // Ensure correct date parsing
            if (endDate && endDate !== date) {
                const end = new Date(endDate + 'T00:00:00');
                formattedDate = `${start.toLocaleString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else {
                formattedDate = start.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
        }

        const imageSource = pictureSrc || '<?php echo plugins_url( '../images/hero-image.jpg', __FILE__ ); ?>';
        cardDiv.innerHTML = `
            <div class="event-card-actions">
                <button class="btn-edit-event">Edit</button>
                <button class="btn-delete-event">Delete</button>
            </div>
            <a href="${permalink}" class="event-card-link">
                <div class="event-card-image">
                    <img src="${imageSource}" alt="${name}">
                </div>
                <div class="event-card-content">
                    <h3>${name}</h3>
                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path></svg> ${formattedDate}</p>
                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg> ${place}</p>
                    <p class="event-card-description">${description}</p>
                </div>
            </a>
        `;
        this.eventsContainer.appendChild(cardDiv);
    }

    updateEventCardInUI(postId, eventData) {
        const card = this.eventsContainer.querySelector(`.event-card[data-post-id="${postId}"]`);
        if (!card) return;

        const { name, date, endDate, place, time, description, pictureSrc } = eventData;
        let formattedDate = 'Date not set';
        if (date) {
            const start = new Date(date + 'T00:00:00');
            if (endDate && endDate !== date) {
                const end = new Date(endDate + 'T00:00:00');
                formattedDate = `${start.toLocaleString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else {
                formattedDate = start.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
        }

        card.dataset.name = name;
        card.dataset.date = date;
        card.dataset.endDate = endDate;
        card.dataset.place = place;
        card.dataset.time = time;
        card.dataset.description = description;
        card.dataset.imageUrl = pictureSrc;
        card.dataset.year = new Date(date).getFullYear();

        card.querySelector('h3').textContent = name;
        const paragraphs = card.querySelectorAll('p');
        paragraphs[0].innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path></svg> ${formattedDate}`;
        paragraphs[1].innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg> ${place}`;
        card.querySelector('.event-card-description').textContent = description;
        if (pictureSrc) {
            card.querySelector('.event-card-image img').src = pictureSrc;
        }
    }

    filterEvents() {
        const searchTerm = this.searchInput.value.toLowerCase();
        const cards = this.eventsContainer.querySelectorAll('.event-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const place = card.dataset.place.toLowerCase();
            const description = card.dataset.description.toLowerCase();
            const isVisible = name.includes(searchTerm) || place.includes(searchTerm) || description.includes(searchTerm);
            
            card.style.display = isVisible ? '' : 'none';
            if (isVisible) {
                visibleCount++;
            }
        });

        if (searchTerm) {
            this.resultsInfo.style.display = 'block';
            this.resultsInfo.querySelector('#resultsCount').textContent = visibleCount;
        } else {
            this.resultsInfo.style.display = 'none';
        }
    }
}

const calendarEventsData = <?php echo json_encode($calendar_events); ?>;
document.addEventListener('DOMContentLoaded', function() {
    const ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
    const adminNonce = '<?php echo wp_create_nonce("fossasia_admin_nonce"); ?>';
    const eventsManager = new EventsPageManager(ajaxUrl, adminNonce);
    eventsManager.init();
    eventsManager.initSearch();
});
</script>
</body>
</html>