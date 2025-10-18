<?php
/**
 * Template for displaying the main Landing page.
 */

get_header(); // Include header

$args = array(
    'post_type' => 'page',
    'pagename' => 'landing' // Assumes a page with slug 'landing' holds the main content.
);
$landing_page_query = new WP_Query($args);

if ($landing_page_query->have_posts()) :
    while ($landing_page_query->have_posts()) : $landing_page_query->the_post();
        the_content();
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer