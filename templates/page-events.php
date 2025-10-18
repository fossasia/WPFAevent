<?php
/**
 * Template for displaying the Events page.
 */

get_header(); // Include header

$args = array(
    'post_type' => 'event',
    'posts_per_page' => 10,
);
$events = new WP_Query($args);

if ($events->have_posts()) :
    while ($events->have_posts()) : $events->the_post();
        $event_date = get_post_meta(get_the_ID(), 'event_date', true);
        $event_location = get_post_meta(get_the_ID(), 'event_location', true);
        ?>
        <div class="event-item">
            <h2><?php the_title(); ?></h2>
            <p>Date: <?php echo esc_html($event_date); ?></p>
            <p>Location: <?php echo esc_html($event_location); ?></p>
            <div><?php the_content(); ?></div>
        </div>
    <?php
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer
