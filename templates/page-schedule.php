<?php
/**
 * Template for displaying the Schedule page.
 */

get_header(); // Include header

// Example: Query sessions related to a specific event CPT.
// This assumes 'session' is a CPT and it has a meta field 'event_id' to link it to an event.
$args = array(
    'post_type' => 'session',
    'posts_per_page' => -1,
    // 'meta_key' => 'event_id',
    // 'meta_value' => get_the_ID() // If on an event page.
);
$sessions = new WP_Query($args);

if ($sessions->have_posts()) :
    while ($sessions->have_posts()) : $sessions->the_post();
        the_title('<h3>', '</h3>');
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer