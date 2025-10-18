<?php
/**
 * Template for displaying the Speakers page.
 */

get_header(); // Include header

$args = array(
    'post_type' => 'speaker',
    'posts_per_page' => -1,
);
$speakers = new WP_Query($args);

if ($speakers->have_posts()) :
    while ($speakers->have_posts()) : $speakers->the_post();
        $speaker_title = get_post_meta(get_the_ID(), 'speaker_title', true);
        ?>
        <div class="speaker-item">
            <h2><?php the_title(); ?></h2>
            <p>Title: <?php echo esc_html($speaker_title); ?></p>
            <div><?php the_content(); ?></div>
        </div>
    <?php
    endwhile;
endif;
wp_reset_postdata();

get_footer(); // Include footer