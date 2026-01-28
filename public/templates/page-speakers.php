<?php
/**
 * Template Name: WPFA - Speakers
 * Description: Display a grid of speakers with search and pagination
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters the number of speakers per page.
 *
 * @since 1.0.0
 * @param int $per_page Number of speakers per page. Default 24.
 */
$paged  = max( 1, (int) get_query_var( 'paged', 1 ) );
$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

// Query speakers from CPT
$per_page = max( 1, (int) apply_filters( 'wpfa_speakers_per_page', 24 ) );
$args     = array(
	'post_type'      => 'wpfa_speaker',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
	's'              => $search,
	'fields'         => 'ids',
);

// Add category filter if set
if ( isset( $_GET['category'] ) && ! empty( $_GET['category'] ) ) {
	$category = sanitize_text_field( wp_unslash( $_GET['category'] ) );
	if ( $category !== 'all' ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'wpfa_speaker_category',
				'field'    => 'slug',
				'terms'    => sanitize_title( $category ),
			),
		);
	}
}

$query = new WP_Query( $args );

// Get ALL categories (including empty ones for admin)
$categories = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'wpfa_speaker_category',
			'hide_empty' => false, // Show all categories for admin filtering
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$categories = $terms;
	}
}

// Get current category from URL
$current_category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : 'all';

// Get site logo
$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

// Set up header variables for the partial
$register_button_url = get_option( 'wpfa_register_button_url', 'https://eventyay.com/e/4c0e0c27' );
$register_button_url = apply_filters( 'wpfa_register_button_url', $register_button_url );
$header_vars         = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => true,
	'show_register_button' => true,
	'back_button_text'     => __( 'Back to Event', 'wpfaevent' ),
	'register_button_url'  => $register_button_url,
	'register_button_text' => __( 'Register', 'wpfaevent' ),
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
	// Load shared navigation header
	$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
	if ( file_exists( $nav_partial ) ) {
		include $nav_partial;
	}
	?>

	<main class="wpfa-speakers">
		<section class="wpfa-speakers-hero">
			<div class="container">
				<h1><?php esc_html_e( 'FOSSASIA Summit Speakers', 'wpfaevent' ); ?></h1>
				<p><?php esc_html_e( 'Discover all the amazing speakers joining us at FOSSASIA Summit', 'wpfaevent' ); ?></p>
				
				<form class="wpfa-speakers-search" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
					<label for="wpfa-speaker-search" class="screen-reader-text">
						<?php esc_html_e( 'Search speakers', 'wpfaevent' ); ?>
					</label>
					<input 
						type="search" 
						id="wpfa-speaker-search" 
						name="q" 
						value="<?php echo esc_attr( $search ); ?>" 
						placeholder="<?php esc_attr_e( 'Search speakers...', 'wpfaevent' ); ?>"
					/>
					<button type="submit">
						<span class="screen-reader-text"><?php esc_html_e( 'Search', 'wpfaevent' ); ?></span>
						🔍
					</button>
					<input type="hidden" name="category" value="<?php echo esc_attr( $current_category ); ?>">
				</form>

				<?php if ( ! empty( $categories ) ) : ?>
				<div class="wpfa-speakers-filters">
					<a href="<?php echo esc_url( add_query_arg( array( 'category' => 'all' ), remove_query_arg( 'category' ) ) ); ?>" 
						class="wpfa-filter-btn <?php echo $current_category === 'all' ? 'active' : ''; ?>"
						data-filter="all">
						<?php esc_html_e( 'All Speakers', 'wpfaevent' ); ?>
					</a>
					<?php
					foreach ( $categories as $category_term ) :
						$category_slug = sanitize_title( $category_term->name );
						$is_active     = $current_category === $category_slug;
						$category_url  = add_query_arg(
							array( 'category' => $category_slug ),
							remove_query_arg( array( 'paged', 'category' ) )
						);
						?>
						<a href="<?php echo esc_url( $category_url ); ?>" 
							class="wpfa-filter-btn <?php echo $is_active ? 'active' : ''; ?>"
							data-filter="<?php echo esc_attr( $category_slug ); ?>">
							<?php echo esc_html( $category_term->name ); ?>
							<?php if ( $category_term->count > 0 ) : ?>
								<span class="wpfa-filter-count">(<?php echo intval( $category_term->count ); ?>)</span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</section>

		<div class="container">
			<div class="wpfa-results-info">
				<?php
				printf(
					/* translators: %d: number of speakers found, %d: total speakers */
					esc_html__( 'Showing %1$d of %2$d speakers', 'wpfaevent' ),
					count( $query->posts ),
					$query->found_posts
				);
				?>
			</div>

			<?php if ( ! $query->have_posts() ) : ?>
				<div class="wpfa-no-results">
					<h3><?php esc_html_e( 'No speakers found', 'wpfaevent' ); ?></h3>
					<p><?php esc_html_e( 'Try adjusting your search or filters', 'wpfaevent' ); ?></p>
				</div>
			<?php else : ?>
				<div class="wpfa-speakers-grid" id="wpfa-speakers-grid">
					<?php foreach ( $query->posts as $sid ) : ?>
						<?php include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php'; ?>
					<?php endforeach; ?>
				</div>

				<?php
				// Pagination
				$total           = max( 1, (int) ceil( $query->found_posts / $per_page ) );
				$pagination_args = array();
				if ( $search ) {
					$pagination_args['q'] = $search;
				}
				if ( $current_category !== 'all' ) {
					$pagination_args['category'] = $current_category;
				}

				wpfa_render_pagination(
					$total,
					$paged,
					__( 'Speakers pagination', 'wpfaevent' ),
					$pagination_args
				);
				?>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</main>

	<footer class="wpfa-footer">
		<div class="container">
			<small>
				© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok
			</small>
		</div>
	</footer>
</div><!-- #page -->

<?php
// Load admin modals if user is admin
if ( current_user_can( 'manage_options' ) ) :
	$modal_partial = WPFAEVENT_PATH . 'public/partials/speakers/speaker-modal.php';
	if ( file_exists( $modal_partial ) ) {
		include $modal_partial;
	}
endif;
?>

<?php wp_footer(); ?>
</body>
</html>
