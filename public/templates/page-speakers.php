<?php
/**
 * Template Name: WPFA - Speakers
 * Description: Display an event-specific speaker landing page with search and filtering.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wpfaevent_is_embed = ! empty( $GLOBALS['wpfaevent_template_embed'] );

$current_page = max( 1, (int) get_query_var( 'paged', 1 ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end search filtering via query args.
$search_term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end category filtering via query args.
$current_category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : 'all';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end event filtering via query args.
$current_event_filter = isset( $_GET['event'] ) ? sanitize_text_field( wp_unslash( $_GET['event'] ) ) : '';

/**
 * Filters the number of speakers per page.
 *
 * Kept for backwards compatibility with integrations that filter the speakers
 * template; event-specific speaker landing pages show the full selected list.
 *
 * @since 1.0.0
 * @param int $per_page Number of speakers per page. Default 24.
 */
$speakers_per_page = max( 1, (int) apply_filters( 'wpfa_speakers_per_page', 24 ) );

$normalize_post_id_list = static function ( $post_ids ) {
	if ( ! is_array( $post_ids ) ) {
		return array();
	}

	$post_ids = array_map( 'absint', $post_ids );
	$post_ids = array_filter( $post_ids );

	return array_values( array_unique( $post_ids ) );
};

$get_event_speaker_ids = static function ( $event_id ) use ( $normalize_post_id_list ) {
	$event_id = absint( $event_id );

	if ( ! $event_id ) {
		return array();
	}

	return $normalize_post_id_list( get_post_meta( $event_id, 'wpfa_event_speakers', true ) );
};

$get_event_featured_speaker_ids = static function ( $event_id, $speaker_ids ) use ( $normalize_post_id_list ) {
	$event_id    = absint( $event_id );
	$speaker_ids = $normalize_post_id_list( $speaker_ids );

	if ( ! $event_id || empty( $speaker_ids ) ) {
		return array();
	}

	$featured = $normalize_post_id_list( get_post_meta( $event_id, 'wpfa_event_featured_speakers', true ) );
	$featured = array_values( array_intersect( $featured, $speaker_ids ) );

	if ( empty( $featured ) && taxonomy_exists( 'wpfa_speaker_category' ) ) {
		foreach ( $speaker_ids as $speaker_id ) {
			$terms = wp_get_post_terms( $speaker_id, 'wpfa_speaker_category' );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $speaker_term ) {
				if ( preg_match( '/\b(featured|keynote|plenary|highlight)\b/i', $speaker_term->name ) ) {
					$featured[] = $speaker_id;
					break;
				}
			}
		}
	}

	return array_values( array_intersect( $normalize_post_id_list( $featured ), $speaker_ids ) );
};

$selected_event_id = 0;
if ( '' !== trim( $current_event_filter ) ) {
	if ( is_numeric( $current_event_filter ) ) {
		$candidate_event_id = absint( $current_event_filter );
		if ( $candidate_event_id && 'wpfa_event' === get_post_type( $candidate_event_id ) && 'publish' === get_post_status( $candidate_event_id ) ) {
			$selected_event_id = $candidate_event_id;
		}
	} else {
		$candidate_event = get_page_by_path( sanitize_title( $current_event_filter ), OBJECT, 'wpfa_event' );
		if ( $candidate_event instanceof WP_Post ) {
			$selected_event_id = absint( $candidate_event->ID );
		}
	}
}

$event_filter_posts = get_posts(
	array(
		'post_type'      => 'wpfa_event',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

if ( $selected_event_id ) {
	$speaker_ids = $get_event_speaker_ids( $selected_event_id );
	$speaker_ids = array_values(
		array_filter(
			$speaker_ids,
			static function ( $speaker_id ) use ( $search_term ) {
				if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) || 'publish' !== get_post_status( $speaker_id ) ) {
					return false;
				}

				if ( '' === trim( $search_term ) ) {
					return true;
				}

				$haystack = implode(
					' ',
					array(
						get_the_title( $speaker_id ),
						get_post_field( 'post_content', $speaker_id ),
						get_post_meta( $speaker_id, 'wpfa_speaker_organization', true ),
						get_post_meta( $speaker_id, 'wpfa_speaker_position', true ),
					)
				);

				return false !== stripos( $haystack, $search_term );
			}
		)
	);

	usort(
		$speaker_ids,
		static function ( $speaker_a, $speaker_b ) {
			return strcasecmp( get_the_title( $speaker_a ), get_the_title( $speaker_b ) );
		}
	);
} else {
	$speaker_ids = array();
}

$featured_speaker_ids = $selected_event_id ? $get_event_featured_speaker_ids( $selected_event_id, $speaker_ids ) : array();

$category_source_speaker_ids = $speaker_ids;

if ( 'all' !== $current_category && taxonomy_exists( 'wpfa_speaker_category' ) ) {
	$speaker_ids = array_values(
		array_filter(
			$speaker_ids,
			static function ( $speaker_id ) use ( $current_category ) {
				return has_term( $current_category, 'wpfa_speaker_category', $speaker_id );
			}
		)
	);
}

$featured_speaker_ids = array_values( array_intersect( $featured_speaker_ids, $speaker_ids ) );

if ( ! empty( $featured_speaker_ids ) ) {
	usort(
		$speaker_ids,
		static function ( $speaker_a, $speaker_b ) use ( $featured_speaker_ids ) {
			$speaker_a_is_featured = in_array( absint( $speaker_a ), $featured_speaker_ids, true );
			$speaker_b_is_featured = in_array( absint( $speaker_b ), $featured_speaker_ids, true );

			if ( $speaker_a_is_featured !== $speaker_b_is_featured ) {
				return $speaker_a_is_featured ? -1 : 1;
			}

			return strcasecmp( get_the_title( $speaker_a ), get_the_title( $speaker_b ) );
		}
	);
}

$total_speakers = count( $speaker_ids );

if ( $selected_event_id ) {
	$paged_speaker_ids = $speaker_ids;
} else {
	$speaker_offset    = ( $current_page - 1 ) * $speakers_per_page;
	$paged_speaker_ids = array_slice( $speaker_ids, $speaker_offset, $speakers_per_page );
}

$featured_display_speaker_ids = array_values( array_intersect( $paged_speaker_ids, $featured_speaker_ids ) );
$regular_display_speaker_ids  = array_values( array_diff( $paged_speaker_ids, $featured_display_speaker_ids ) );

$categories           = array();
$category_term_counts = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) && $selected_event_id ) {
	foreach ( $category_source_speaker_ids as $speaker_id ) {
		$terms = wp_get_post_terms( $speaker_id, 'wpfa_speaker_category' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		foreach ( $terms as $speaker_term ) {
			$category_term_counts[ $speaker_term->term_id ] = isset( $category_term_counts[ $speaker_term->term_id ] ) ? $category_term_counts[ $speaker_term->term_id ] + 1 : 1;
		}
	}

	if ( ! empty( $category_term_counts ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'wpfa_speaker_category',
				'include'    => array_keys( $category_term_counts ),
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$categories = $terms;
		}
	}
}

$selected_event_slug      = $selected_event_id ? get_post_field( 'post_name', $selected_event_id ) : '';
$selected_event_title     = $selected_event_id ? get_the_title( $selected_event_id ) : '';
$selected_event_permalink = $selected_event_id ? get_permalink( $selected_event_id ) : home_url( '/events/' );
$selected_event_url       = $selected_event_id ? get_post_meta( $selected_event_id, 'wpfa_event_url', true ) : '';
$selected_event_url       = $selected_event_url ? $selected_event_url : $selected_event_permalink;
$speakers_base_url        = get_post_type_archive_link( 'wpfa_speaker' );
$speakers_base_url        = $speakers_base_url ? $speakers_base_url : get_permalink();

// Get the site logo.
$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

// Set up header variables for the partial.
$register_button_url = $selected_event_id ? $selected_event_url : get_option( 'wpfa_register_button_url', 'https://eventyay.com/e/4c0e0c27' );
$register_button_url = apply_filters( 'wpfa_register_button_url', $register_button_url );
$header_vars         = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => $selected_event_permalink,
	'show_back_button'     => true,
	'show_register_button' => true,
	'back_button_text'     => $selected_event_id ? __( 'Back to Event', 'wpfaevent' ) : __( 'Back to Events', 'wpfaevent' ),
	'register_button_url'  => $register_button_url,
	'register_button_text' => __( 'Register', 'wpfaevent' ),
);

?>
<?php if ( ! $wpfaevent_is_embed ) : ?>
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
		// Load the shared navigation header.
		$site_logo_url        = $header_vars['site_logo_url'];
		$event_page_url       = $header_vars['event_page_url'];
		$show_back_button     = $header_vars['show_back_button'];
		$show_register_button = $header_vars['show_register_button'];
		$back_button_text     = $header_vars['back_button_text'];
		$register_button_url  = $header_vars['register_button_url'];
		$register_button_text = $header_vars['register_button_text'];

		$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
		if ( file_exists( $nav_partial ) ) {
			include $nav_partial;
		}
		?>
<?php endif; ?>

	<?php if ( $wpfaevent_is_embed ) : ?>
	<section class="wpfa-speakers">
	<?php else : ?>
	<main class="wpfa-speakers">
	<?php endif; ?>
		<section class="wpfa-speakers-hero">
			<div class="container">
				<h1>
					<?php
					echo esc_html(
						$selected_event_id
							? sprintf(
								/* translators: %s: Event title. */
								__( '%s Speakers', 'wpfaevent' ),
								$selected_event_title
							)
							: apply_filters( 'wpfa_speakers_title', __( 'Event Speakers', 'wpfaevent' ) )
					);
					?>
				</h1>
				<p>
					<?php
					echo esc_html(
						$selected_event_id
							? __( 'Speakers linked to this event.', 'wpfaevent' )
							: apply_filters( 'wpfa_speakers_subtitle', __( 'Choose an event to view its separate speaker list.', 'wpfaevent' ) )
					);
					?>
				</p>

				<?php if ( $selected_event_id ) : ?>
					<form class="wpfa-speakers-search" method="get" action="<?php echo esc_url( $speakers_base_url ); ?>">
						<label for="wpfa-speaker-search" class="screen-reader-text">
							<?php esc_html_e( 'Search speakers', 'wpfaevent' ); ?>
						</label>
						<input
							type="search"
							id="wpfa-speaker-search"
							name="q"
							value="<?php echo esc_attr( $search_term ); ?>"
							placeholder="<?php esc_attr_e( 'Search speakers...', 'wpfaevent' ); ?>"
						/>
						<button type="submit">
							<span class="screen-reader-text"><?php esc_html_e( 'Search', 'wpfaevent' ); ?></span>
							🔍
						</button>
						<input type="hidden" name="category" value="<?php echo esc_attr( $current_category ); ?>">
						<input type="hidden" name="event" value="<?php echo esc_attr( $selected_event_slug ); ?>">
					</form>
				<?php endif; ?>

				<?php if ( ! empty( $event_filter_posts ) ) : ?>
					<div class="wpfa-speakers-filters wpfa-event-filters" aria-label="<?php esc_attr_e( 'Filter speakers by event', 'wpfaevent' ); ?>">
						<a href="<?php echo esc_url( $speakers_base_url ); ?>"
							class="wpfa-filter-btn <?php echo esc_attr( $selected_event_id ? '' : 'active' ); ?>">
							<?php esc_html_e( 'Choose Event', 'wpfaevent' ); ?>
						</a>
						<?php foreach ( $event_filter_posts as $event_filter_post ) : ?>
							<?php
							$event_filter_slug = get_post_field( 'post_name', $event_filter_post->ID );
							$event_filter_args = array(
								'event' => $event_filter_slug,
							);
							if ( $search_term ) {
								$event_filter_args['q'] = $search_term;
							}
							$event_filter_url = add_query_arg( $event_filter_args, $speakers_base_url );
							?>
							<a href="<?php echo htmlentities(esc_url( $event_filter_url )); ?>"
								class="wpfa-filter-btn <?php echo esc_attr( absint( $event_filter_post->ID ) === $selected_event_id ? 'active' : '' ); ?>">
								<?php echo esc_html( get_the_title( $event_filter_post->ID ) ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $categories ) ) : ?>
					<div class="wpfa-speakers-filters">
						<?php
						$category_base_args = array(
							'category' => 'all',
						);
						if ( $search_term ) {
							$category_base_args['q'] = $search_term;
						}
						if ( $selected_event_slug ) {
							$category_base_args['event'] = $selected_event_slug;
						}
						?>
						<a href="<?php echo htmlentities(esc_url( add_query_arg( $category_base_args, $speakers_base_url ) )); ?>"
							class="wpfa-filter-btn <?php echo esc_attr( 'all' === $current_category ? 'active' : '' ); ?>"
							data-filter="all">
							<?php esc_html_e( 'All Speakers', 'wpfaevent' ); ?>
						</a>
						<?php
						foreach ( $categories as $category_term ) :
							$category_slug = $category_term->slug;
							$is_active     = $current_category === $category_slug;
							$category_args = array(
								'category' => $category_slug,
							);
							if ( $search_term ) {
								$category_args['q'] = $search_term;
							}
							if ( $selected_event_slug ) {
								$category_args['event'] = $selected_event_slug;
							}
							$category_url   = add_query_arg( $category_args, $speakers_base_url );
							$category_count = isset( $category_term_counts[ $category_term->term_id ] ) ? absint( $category_term_counts[ $category_term->term_id ] ) : absint( $category_term->count );
							?>
							<a href="<?php echo esc_url( $category_url ); ?>"
								class="wpfa-filter-btn <?php echo esc_attr( $is_active ? 'active' : '' ); ?>"
								data-filter="<?php echo esc_attr( $category_slug ); ?>">
								<?php echo esc_html( $category_term->name ); ?>
								<?php if ( $category_count > 0 ) : ?>
									<span class="wpfa-filter-count">(<?php echo htmlentities(absint( $category_count )); ?>)</span>
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
				if ( $selected_event_id ) {
					printf(
						/* translators: 1: number of speakers shown, 2: event title. */
						esc_html__( 'Showing %1$d speakers for %2$s', 'wpfaevent' ),
						absint( $total_speakers ),
						esc_html( $selected_event_title )
					);
				} else {
					esc_html_e( 'Select an event to view its speaker list.', 'wpfaevent' );
				}
				?>
			</div>

			<?php if ( empty( $paged_speaker_ids ) ) : ?>
				<div class="wpfa-no-results">
					<h3><?php esc_html_e( 'No speakers found', 'wpfaevent' ); ?></h3>
					<p>
						<?php
						echo esc_html(
							$selected_event_id
								? __( 'No speakers are linked to this event yet.', 'wpfaevent' )
								: __( 'Choose an event above to see that event\'s speakers.', 'wpfaevent' )
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="wpfa-speakers-list" id="wpfa-speakers-grid">
					<?php $wpfa_featured_speaker_ids = $featured_speaker_ids; ?>
					<?php if ( ! empty( $featured_display_speaker_ids ) ) : ?>
						<section class="wpfa-speaker-group wpfa-featured-speaker-group" aria-labelledby="wpfa-featured-speakers-title">
							<div class="wpfa-speaker-group-head">
								<h2 id="wpfa-featured-speakers-title"><?php esc_html_e( 'Featured Speakers', 'wpfaevent' ); ?></h2>
							</div>
							<div class="wpfa-speakers-grid wpfa-featured-speakers-grid">
								<?php foreach ( $featured_display_speaker_ids as $sid ) : ?>
									<?php include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php'; ?>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $regular_display_speaker_ids ) ) : ?>
						<section class="wpfa-speaker-group wpfa-regular-speaker-group" aria-labelledby="wpfa-regular-speakers-title">
							<?php if ( ! empty( $featured_display_speaker_ids ) ) : ?>
								<div class="wpfa-speaker-group-head">
									<h2 id="wpfa-regular-speakers-title"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h2>
								</div>
							<?php endif; ?>
							<div class="wpfa-speakers-grid">
								<?php foreach ( $regular_display_speaker_ids as $sid ) : ?>
									<?php include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php'; ?>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>
					<?php unset( $wpfa_featured_speaker_ids ); ?>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	<?php if ( $wpfaevent_is_embed ) : ?>
	</section>
	<?php else : ?>
	</main>
	<?php endif; ?>

<?php if ( ! $wpfaevent_is_embed ) : ?>
	<footer class="wpfa-footer">
		<div class="container">
			<small>
				<?php
				echo esc_html(
					apply_filters(
						'wpfa_footer_text',
						'© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok'
					)
				);
				?>
			</small>
		</div>
	</footer>
</div><!-- #page -->
<?php endif; ?>

<?php
// Load admin modals if the user is an admin.
if ( current_user_can( 'manage_options' ) ) :
	$modal_partial = WPFAEVENT_PATH . 'public/partials/speakers/speaker-modal.php';
	if ( file_exists( $modal_partial ) ) {
		include $modal_partial;
	}
endif;
?>

<?php if ( ! $wpfaevent_is_embed ) : ?>
	<?php wp_footer(); ?>
</body>
</html>
<?php endif; ?>
