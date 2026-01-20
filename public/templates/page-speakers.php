<?php
/**
 * Template Name: WPFA - Speakers
 * Description: Display a grid of speakers with search and pagination
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
get_header();

/**
 * Filters the number of speakers per page.
 *
 * @since 1.0.0
 * @param int $per_page Number of speakers per page. Default 24.
 */
$per_page = max( 1, (int) apply_filters( 'wpfa_speakers_per_page', 24 ) );
$paged    = max( 1, (int) get_query_var( 'paged', 1 ) );

// Optional search
$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

$args = [
	'post_type'      => 'wpfa_speaker',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
	's'              => $search,
	'fields'         => 'ids',
];

$query = new WP_Query( $args );
?>
<main class="wpfa-speakers">
	<form class="wpfa-search" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
		<label for="q" class="screen-reader-text"><?php esc_html_e( 'Search speakers', 'wpfaevent' ); ?></label>
		<input id="q" type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search speakers…', 'wpfaevent' ); ?>" />
		<button type="submit"><?php esc_html_e( 'Search', 'wpfaevent' ); ?></button>
	</form>

	<?php if ( $query->have_posts() ) : ?>
	<div class="wpfa-speakers-grid">
		<?php
		foreach ( $query->posts as $sid ) :
			include WPFAEVENT_PATH . 'public/partials/speaker-card.php';
		endforeach;
		?>
	</div>

		<?php
		// Pagination
		$total = max( 1, (int) ceil( $query->found_posts / $per_page ) );

		// Preserve search parameter if present
		$pagination_args = array();
		if ( $search ) {
			$pagination_args['q'] = $search;
		}

		wpfa_render_pagination(
			$total,
			$paged,
			__( 'Speakers pagination', 'wpfaevent' ),
			$pagination_args
		);
		?>

	<?php else : ?>
		<p><?php esc_html_e( 'No speakers found.', 'wpfaevent' ); ?></p>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
</main>
<?php get_footer(); ?>
