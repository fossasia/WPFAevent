<?php
/**
 * Template for displaying the Code of Conduct page.
 */

get_header(); // Include header

$args = array(
    'post_type' => 'page',
    'pagename' => 'code-of-conduct' // Assumes a page with slug 'code-of-conduct' holds the content.
);
$coc_page_query = new WP_Query($args);

if ($coc_page_query->have_posts()) :
    while ($coc_page_query->have_posts()) : $coc_page_query->the_post();
        the_content();
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer