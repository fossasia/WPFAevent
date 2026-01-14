<?php
/**
 * Template Name: WPFA - Landing
 *
 * Landing page template for FOSSASIA events.
 * Displays a hero section with title, subtitle, call-to-action button,
 * and a partner logo strip.
 *
 * Template content is customizable via filters:
 * - wpfa_landing_hero_title: Main hero title text
 * - wpfa_landing_hero_sub: Hero subtitle text
 * - wpfa_landing_cta_url: Call-to-action button URL
 * - wpfa_landing_cta_label: Call-to-action button text
 * - wpfa_landing_partner_logos: Array of partner logo URLs
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

get_header();
// Fetch customizable pieces (later we can add an Options page; for now use filters or placeholders).

/**
 * Filters the hero title on the landing page.
 *
 * @since 1.0.0
 * @param string $title Default hero title.
 */
$hero_title = apply_filters( 'wpfa_landing_hero_title', __( 'FOSSASIA Events', 'wpfaevent' ) );

/**
 * Filters the hero subtitle on the landing page.
 *
 * @since 1.0.0
 * @param string $sub Default hero subtitle.
 */
$hero_sub = apply_filters( 'wpfa_landing_hero_sub', __( 'Open tech, open community.', 'wpfaevent' ) );

/**
 * Filters the call-to-action button URL on the landing page.
 *
 * @since 1.0.0
 * @param string $url Default CTA URL.
 */
$cta_url = apply_filters( 'wpfa_landing_cta_url', home_url( '/events' ) );

/**
 * Filters the call-to-action button label on the landing page.
 *
 * @since 1.0.0
 * @param string $label Default CTA label.
 */
$cta_label = apply_filters( 'wpfa_landing_cta_label', __( 'Explore Events', 'wpfaevent' ) );

/**
 * Filters the partner logo URLs displayed in the logo strip.
 *
 * @since 1.0.0
 * @param array $logos Array of image URLs for partner logos.
 */
$logos = (array) apply_filters(
	'wpfa_landing_partner_logos',
	[
		'https://via.placeholder.com/120x60?text=Partner+1',
		'https://via.placeholder.com/120x60?text=Partner+2',
		'https://via.placeholder.com/120x60?text=Partner+3',
	]
);
?>
<main class="wpfa-landing">
	<section class="wpfa-hero">
		<h1><?php echo esc_html( $hero_title ); ?></h1>
		<p class="wpfa-hero-sub"><?php echo esc_html( $hero_sub ); ?></p>
		<p><a class="wpfa-btn" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a></p>
	</section>

	<section class="wpfa-logo-strip" aria-label="<?php esc_attr_e( 'Partners', 'wpfaevent' ); ?>">
		<ul>
			<?php foreach ( $logos as $logo ) : ?>
				<li><img src="<?php echo esc_url( $logo ); ?>" alt="" loading="lazy" /></li>
			<?php endforeach; ?>
		</ul>
	</section>
</main>
<?php get_footer(); ?>
