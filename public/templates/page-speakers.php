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
$current_page = max( 1, (int) get_query_var( 'paged', 1 ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end search filtering via query args.
$search_term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end category filtering via query args.
$current_category = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : 'all';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only front-end event filtering via query args.
$current_event_filter = isset( $_GET['event'] ) ? sanitize_text_field( wp_unslash( $_GET['event'] ) ) : '';

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
	$event_id    = absint( $event_id );
	$speaker_ids = $normalize_post_id_list( get_post_meta( $event_id, 'wpfa_event_speakers', true ) );

	if ( ! $event_id ) {
		return array();
	}

	$reverse_speaker_ids = get_posts(
		array(
			'post_type'      => 'wpfa_speaker',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored as post meta.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => 'i:' . $event_id . ';',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => '"' . $event_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_speaker_events',
					'value'   => (string) $event_id,
					'compare' => '=',
				),
			),
		)
	);

	return $normalize_post_id_list( array_merge( $speaker_ids, $reverse_speaker_ids ) );
};

$selected_event_id = 0;
if ( '' !== trim( $current_event_filter ) ) {
	if ( is_numeric( $current_event_filter ) ) {
		$candidate_event_id = absint( $current_event_filter );
		if ( $candidate_event_id && 'wpfa_event' === get_post_type( $candidate_event_id ) ) {
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

				$haystack = get_the_title( $speaker_id ) . ' ' . get_post_field( 'post_content', $speaker_id );

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

$featured_speaker_ids = array();
if ( $selected_event_id && class_exists( 'Wpfaevent_Meta_Event' ) ) {
	$featured_speaker_ids = Wpfaevent_Meta_Event::get_event_featured_speaker_ids( $selected_event_id );
	$featured_speaker_ids = array_values( array_intersect( $featured_speaker_ids, $speaker_ids ) );
}

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

// Event speaker lists should show everyone; the event page keeps a smaller preview set.
if ( $selected_event_id ) {
	$paged_speaker_ids = $speaker_ids;
} else {
	$speaker_offset    = ( $current_page - 1 ) * $speakers_per_page;
	$paged_speaker_ids = array_slice( $speaker_ids, $speaker_offset, $speakers_per_page );
}

// Get categories; when an event is selected, keep category counts event-specific.
$categories = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
	if ( $selected_event_id ) {
		$term_counts = array();

		foreach ( $category_source_speaker_ids as $speaker_id ) {
			$terms = wp_get_post_terms( $speaker_id, 'wpfa_speaker_category' );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $speaker_term ) {
				$term_counts[ $speaker_term->term_id ] = isset( $term_counts[ $speaker_term->term_id ] ) ? $term_counts[ $speaker_term->term_id ] + 1 : 1;
			}
		}

		if ( ! empty( $term_counts ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'wpfa_speaker_category',
					'include'    => array_keys( $term_counts ),
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $speaker_term ) {
					$speaker_term->wpfa_event_count = isset( $term_counts[ $speaker_term->term_id ] ) ? absint( $term_counts[ $speaker_term->term_id ] ) : 0;
				}
				$categories = $terms;
			}
		}
	}
}

$selected_event_slug      = $selected_event_id ? get_post_field( 'post_name', $selected_event_id ) : '';
$selected_event_title     = $selected_event_id ? get_the_title( $selected_event_id ) : '';
$selected_event_permalink = $selected_event_id ? get_permalink( $selected_event_id ) : home_url( '/events/' );
$selected_event_url       = $selected_event_id ? get_post_meta( $selected_event_id, 'wpfa_event_url', true ) : '';
$selected_event_url       = $selected_event_url ? $selected_event_url : $selected_event_permalink;
$speakers_base_url        = get_post_type_archive_link( 'wpfa_speaker' );
$speakers_base_url        = $speakers_base_url ? $speakers_base_url : home_url( '/speakers/' );

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

	<main class="wpfa-speakers">
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
							<a href="<?php echo esc_url( $event_filter_url ); ?>"
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
					<a href="<?php echo esc_url( add_query_arg( $category_base_args, $speakers_base_url ) ); ?>"
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
						$category_count = isset( $category_term->wpfa_event_count ) ? absint( $category_term->wpfa_event_count ) : absint( $category_term->count );
						?>
						<a href="<?php echo esc_url( $category_url ); ?>" 
							class="wpfa-filter-btn <?php echo esc_attr( $is_active ? 'active' : '' ); ?>"
							data-filter="<?php echo esc_attr( $category_slug ); ?>">
							<?php echo esc_html( $category_term->name ); ?>
							<?php if ( $category_count > 0 ) : ?>
								<span class="wpfa-filter-count">(<?php echo absint( $category_count ); ?>)</span>
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
					<div class="wpfa-speakers-grid" id="wpfa-speakers-grid">
						<?php $wpfa_featured_speaker_ids = $featured_speaker_ids; ?>
						<?php foreach ( $paged_speaker_ids as $sid ) : ?>
							<?php include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php'; ?>
						<?php endforeach; ?>
						<?php unset( $wpfa_featured_speaker_ids ); ?>
					</div>

						<?php
						if ( ! $selected_event_id ) {
							// Pagination is only used for the generic chooser state.
							$total           = max( 1, (int) ceil( $total_speakers / $speakers_per_page ) );
							$pagination_args = array();
							if ( $search_term ) {
								$pagination_args['q'] = $search_term;
							}
							if ( 'all' !== $current_category ) {
								$pagination_args['category'] = $current_category;
							}

							wpfa_render_pagination(
								$total,
								$current_page,
								__( 'Speakers pagination', 'wpfaevent' ),
								$pagination_args
							);
						}
						?>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</main>

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

<?php
// Load admin modals if the user is an admin.
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
