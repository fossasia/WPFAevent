<?php
/**
 * Template for displaying the Past Events page.
 */

get_header(); // Include header

$today = date('Y-m-d');
$args = array(
    'post_type' => 'event',
    'posts_per_page' => 10,
    'meta_key' => 'event_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
    'meta_query' => array(
        array(
            'key' => 'event_date',
            'value' => $today,
            'compare' => '<',
            'type' => 'DATE'
        )
    )
);
$events = new WP_Query($args);

if ($events->have_posts()) :
    while ($events->have_posts()) : $events->the_post();
        // Display logic for each past event
        the_title('<h2>', '</h2>');
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer