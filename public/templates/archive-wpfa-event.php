<?php
/**
 * Event Archive Template.
 *
 * Displays a searchable and filterable directory of WPFA events.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_url = get_post_type_archive_link( 'wpfa_event' );
if ( ! $archive_url ) {
	$archive_url = home_url( '/events/' );
}

$read_filter_value = static function ( $key, $type = 'text' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are read-only archive filters.
	if ( ! isset( $_GET[ $key ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized by type below.
	$value = wp_unslash( $_GET[ $key ] );

	if ( is_array( $value ) ) {
		return '';
	}

	if ( 'key' === $type ) {
		return sanitize_key( $value );
	}

	if ( 'slug' === $type ) {
		return sanitize_title( $value );
	}

	if ( 'int' === $type ) {
		return absint( $value );
	}

	return sanitize_text_field( $value );
};

$search_query     = $read_filter_value( 'q' );
$current_track    = $read_filter_value( 'track', 'slug' );
$current_tag      = $read_filter_value( 'tag', 'slug' );
$current_when     = $read_filter_value( 'when', 'key' );
$current_location = $read_filter_value( 'location', 'slug' );
$current_page     = max( 1, absint( get_query_var( 'paged', 1 ) ) );
$query_page       = $read_filter_value( 'paged', 'int' );

if ( $query_page ) {
	$current_page = max( 1, $query_page );
}

if ( ! in_array( $current_when, array( 'all', 'upcoming', 'past' ), true ) ) {
	$current_when = 'all';
}

$today           = current_time( 'Y-m-d' );
$events_per_page = max( 1, absint( apply_filters( 'wpfa_event_archive_events_per_page', 12 ) ) );

$format_event_date = static function ( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $date;
};

$format_event_date_range = static function ( $start_date, $end_date ) use ( $format_event_date ) {
	$start_label = $format_event_date( $start_date );
	$end_label   = $format_event_date( $end_date );

	if ( $start_label && $end_label && $start_label !== $end_label ) {
		return $start_label . ' - ' . $end_label;
	}

	return $start_label ? $start_label : $end_label;
};

$normalize_event_date = static function ( $date ) {
	$date = sanitize_text_field( $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	return $timestamp ? date_i18n( 'Y-m-d', $timestamp ) : '';
};

$get_event_terms = static function ( $event_id, $taxonomy ) {
	$terms = get_the_terms( $event_id, $taxonomy );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	return array_values( $terms );
};

$get_term_names = static function ( $terms ) {
	return array_map(
		static function ( $term ) {
			return $term->name;
		},
		$terms
	);
};

$get_term_slugs = static function ( $terms ) {
	return array_map(
		static function ( $term ) {
			return $term->slug;
		},
		$terms
	);
};

$build_event_excerpt = static function ( $event_id ) {
	$excerpt = trim( (string) get_post_field( 'post_excerpt', $event_id ) );

	if ( '' === $excerpt ) {
		$content = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $event_id ) ) );
		$excerpt = wp_trim_words( $content, 28, '...' );
	}

	return $excerpt;
};

$event_ids = get_posts(
	array(
		'post_type'      => 'wpfa_event',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

$events    = array();
$locations = array();

foreach ( $event_ids as $event_id ) {
	$event_id     = absint( $event_id );
	$event_title  = get_the_title( $event_id );
	$start_date   = $normalize_event_date( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
	$end_date     = $normalize_event_date( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
	$location     = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
	$event_url    = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_url', true ) );
	$track_terms  = $get_event_terms( $event_id, 'wpfa_event_track' );
	$tag_terms    = $get_event_terms( $event_id, 'wpfa_event_tag' );
	$track_names  = $get_term_names( $track_terms );
	$tag_names    = $get_term_names( $tag_terms );
	$track_slugs  = $get_term_slugs( $track_terms );
	$tag_slugs    = $get_term_slugs( $tag_terms );
	$excerpt      = $build_event_excerpt( $event_id );
	$image_url    = get_the_post_thumbnail_url( $event_id, 'large' );
	$date_key     = $end_date ? $end_date : $start_date;
	$event_status = ( $date_key && $date_key < $today ) ? 'past' : 'upcoming';
	$sort_date    = $start_date ? $start_date : $end_date;
	$sort_time    = $sort_date ? strtotime( $sort_date ) : PHP_INT_MAX;
	$speaker_ids  = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_admin_event_speaker_ids( $event_id ) : array();
	$event_slug   = get_post_field( 'post_name', $event_id );

	if ( $location ) {
		$locations[ sanitize_title( $location ) ] = $location;
	}

	$events[] = array(
		'id'           => $event_id,
		'title'        => $event_title,
		'start_date'   => $start_date,
		'end_date'     => $end_date,
		'date_label'   => $format_event_date_range( $start_date, $end_date ),
		'location'     => $location,
		'location_key' => sanitize_title( $location ),
		'event_url'    => $event_url,
		'track_names'  => $track_names,
		'track_slugs'  => $track_slugs,
		'tag_names'    => $tag_names,
		'tag_slugs'    => $tag_slugs,
		'excerpt'      => $excerpt,
		'image_url'    => $image_url,
		'status'       => $event_status,
		'sort_time'    => $sort_time,
		'speaker_ids'  => $speaker_ids,
		'speakers_url' => add_query_arg( 'event', $event_slug, home_url( '/speakers/' ) ),
	);
}

asort( $locations );

$tracks = get_terms(
	array(
		'taxonomy'   => 'wpfa_event_track',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $tracks ) ) {
	$tracks = array();
}

$tags = get_terms(
	array(
		'taxonomy'   => 'wpfa_event_tag',
		'hide_empty' => true,
	)
);

if ( is_wp_error( $tags ) ) {
	$tags = array();
}

$filtered_events = array();

foreach ( $events as $event ) {
	if ( 'all' !== $current_when && $event['status'] !== $current_when ) {
		continue;
	}

	if ( $current_track && ! in_array( $current_track, $event['track_slugs'], true ) ) {
		continue;
	}

	if ( $current_tag && ! in_array( $current_tag, $event['tag_slugs'], true ) ) {
		continue;
	}

	if ( $current_location && $current_location !== $event['location_key'] ) {
		continue;
	}

	if ( $search_query ) {
		$searchable_text = implode(
			' ',
			array_merge(
				array(
					$event['title'],
					$event['excerpt'],
					$event['location'],
					$event['date_label'],
				),
				$event['track_names'],
				$event['tag_names']
			)
		);

		if ( false === stripos( $searchable_text, $search_query ) ) {
			continue;
		}
	}

	$filtered_events[] = $event;
}

usort(
	$filtered_events,
	static function ( $event_a, $event_b ) use ( $current_when ) {
		if ( $event_a['sort_time'] === $event_b['sort_time'] ) {
			return strcasecmp( $event_a['title'], $event_b['title'] );
		}

		if ( 'past' === $current_when ) {
			return ( $event_a['sort_time'] < $event_b['sort_time'] ) ? 1 : -1;
		}

		return ( $event_a['sort_time'] < $event_b['sort_time'] ) ? -1 : 1;
	}
);

$total_events = count( $filtered_events );
$total_pages  = max( 1, (int) ceil( $total_events / $events_per_page ) );
$current_page = min( $current_page, $total_pages );
$offset       = ( $current_page - 1 ) * $events_per_page;
$paged_events = array_slice( $filtered_events, $offset, $events_per_page );

$active_filter_args = array();

if ( '' !== $search_query ) {
	$active_filter_args['q'] = $search_query;
}

if ( '' !== $current_track ) {
	$active_filter_args['track'] = $current_track;
}

if ( '' !== $current_tag ) {
	$active_filter_args['tag'] = $current_tag;
}

if ( 'all' !== $current_when ) {
	$active_filter_args['when'] = $current_when;
}

if ( '' !== $current_location ) {
	$active_filter_args['location'] = $current_location;
}

$has_active_filters = ! empty( $active_filter_args );
$site_logo_url      = get_option( 'wpfa_site_logo_url', '' );

if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}

$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template wpfa-event-archive-page' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
	$show_back_button     = false;
	$show_register_button = false;

	$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
	if ( file_exists( $nav_partial ) ) {
		include $nav_partial;
	}
	?>

	<main class="wpfa-event-archive">
		<section class="wpfa-event-directory-hero">
			<div class="container wpfa-event-directory-hero-inner">
				<div>
					<p class="wpfa-event-kicker"><?php esc_html_e( 'Event Directory', 'wpfaevent' ); ?></p>
					<h1><?php esc_html_e( 'Open source events from the FOSSASIA community', 'wpfaevent' ); ?></h1>
					<p class="wpfa-event-directory-lead">
						<?php esc_html_e( 'Search by event name, topic, track, location, or date and find the right event faster.', 'wpfaevent' ); ?>
					</p>
				</div>
				<div class="wpfa-event-directory-count" aria-label="<?php esc_attr_e( 'Event count', 'wpfaevent' ); ?>">
					<strong><?php echo esc_html( number_format_i18n( count( $events ) ) ); ?></strong>
					<span><?php esc_html_e( 'published events', 'wpfaevent' ); ?></span>
				</div>
			</div>
		</section>

		<section class="wpfa-event-directory-controls" aria-labelledby="wpfa-event-filter-title">
			<div class="container">
				<form class="wpfa-event-filter-form" action="<?php echo esc_url( $archive_url ); ?>" method="get">
					<div class="wpfa-event-filter-head">
						<div>
							<h2 id="wpfa-event-filter-title"><?php esc_html_e( 'Find Events', 'wpfaevent' ); ?></h2>
							<p><?php esc_html_e( 'Filter events by name, topic, track, date, and location.', 'wpfaevent' ); ?></p>
						</div>
						<?php if ( $has_active_filters ) : ?>
							<a class="wpfa-event-reset" href="<?php echo esc_url( $archive_url ); ?>">
								<?php esc_html_e( 'Reset', 'wpfaevent' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<div class="wpfa-event-filter-grid">
						<label class="wpfa-event-field wpfa-event-field-search">
							<span><?php esc_html_e( 'Search events', 'wpfaevent' ); ?></span>
							<input type="search" name="q" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search by event name or topic', 'wpfaevent' ); ?>">
						</label>

						<label class="wpfa-event-field">
							<span><?php esc_html_e( 'Track', 'wpfaevent' ); ?></span>
							<select name="track">
								<option value=""><?php esc_html_e( 'All tracks', 'wpfaevent' ); ?></option>
								<?php foreach ( $tracks as $track ) : ?>
									<option value="<?php echo esc_attr( $track->slug ); ?>" <?php selected( $current_track, $track->slug ); ?>>
										<?php echo esc_html( $track->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>

						<label class="wpfa-event-field">
							<span><?php esc_html_e( 'Topic', 'wpfaevent' ); ?></span>
							<select name="tag">
								<option value=""><?php esc_html_e( 'All topics', 'wpfaevent' ); ?></option>
								<?php foreach ( $tags as $event_tag ) : ?>
									<option value="<?php echo esc_attr( $event_tag->slug ); ?>" <?php selected( $current_tag, $event_tag->slug ); ?>>
										<?php echo esc_html( $event_tag->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>

						<label class="wpfa-event-field">
							<span><?php esc_html_e( 'Location', 'wpfaevent' ); ?></span>
							<select name="location">
								<option value=""><?php esc_html_e( 'All locations', 'wpfaevent' ); ?></option>
								<?php foreach ( $locations as $location_key => $location_label ) : ?>
									<option value="<?php echo esc_attr( $location_key ); ?>" <?php selected( $current_location, $location_key ); ?>>
										<?php echo esc_html( $location_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>

					<div class="wpfa-event-filter-bottom">
						<fieldset class="wpfa-event-when-filter">
							<legend><?php esc_html_e( 'Date', 'wpfaevent' ); ?></legend>
							<label>
								<input type="radio" name="when" value="all" <?php checked( $current_when, 'all' ); ?>>
								<span><?php esc_html_e( 'All', 'wpfaevent' ); ?></span>
							</label>
							<label>
								<input type="radio" name="when" value="upcoming" <?php checked( $current_when, 'upcoming' ); ?>>
								<span><?php esc_html_e( 'Upcoming', 'wpfaevent' ); ?></span>
							</label>
							<label>
								<input type="radio" name="when" value="past" <?php checked( $current_when, 'past' ); ?>>
								<span><?php esc_html_e( 'Past', 'wpfaevent' ); ?></span>
							</label>
						</fieldset>

						<button type="submit" class="wpfa-event-filter-submit">
							<?php esc_html_e( 'Search Events', 'wpfaevent' ); ?>
						</button>
					</div>
				</form>
			</div>
		</section>

		<section class="wpfa-event-directory-results" aria-labelledby="wpfa-event-results-title">
			<div class="container">
				<div class="wpfa-event-results-head">
					<div>
						<h2 id="wpfa-event-results-title"><?php esc_html_e( 'Events', 'wpfaevent' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %1$d: number of shown events, %2$d: total matching events. */
								esc_html__( 'Showing %1$d of %2$d matching events', 'wpfaevent' ),
								absint( count( $paged_events ) ),
								absint( $total_events )
							);
							?>
						</p>
					</div>
				</div>

				<?php if ( $paged_events ) : ?>
					<div class="wpfa-event-card-grid">
						<?php foreach ( $paged_events as $event ) : ?>
							<?php
							$event_month   = '';
							$event_day     = '';
							$event_time    = ! empty( $event['start_date'] ) ? strtotime( $event['start_date'] ) : 0;
							$speaker_count = count( $event['speaker_ids'] );

							if ( $event_time ) {
								$event_month = date_i18n( 'M', $event_time );
								$event_day   = date_i18n( 'j', $event_time );
							}
							?>
							<article class="wpfa-event-directory-card">
								<a class="wpfa-event-directory-card-main" href="<?php echo esc_url( get_permalink( $event['id'] ) ); ?>">
									<div class="wpfa-event-card-media">
										<?php if ( $event['image_url'] ) : ?>
											<img src="<?php echo esc_url( $event['image_url'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" loading="lazy">
										<?php else : ?>
											<div class="wpfa-event-card-date">
												<span><?php echo esc_html( $event_month ? $event_month : __( 'TBA', 'wpfaevent' ) ); ?></span>
												<strong><?php echo esc_html( $event_day ); ?></strong>
											</div>
										<?php endif; ?>
									</div>

									<div class="wpfa-event-card-body">
										<div class="wpfa-event-card-topline">
											<span class="wpfa-event-status <?php echo esc_attr( 'is-' . $event['status'] ); ?>">
												<?php echo esc_html( 'past' === $event['status'] ? __( 'Past', 'wpfaevent' ) : __( 'Upcoming', 'wpfaevent' ) ); ?>
											</span>
											<?php if ( $speaker_count ) : ?>
												<span>
													<?php
													printf(
														/* translators: %d: number of speakers. */
														esc_html( _n( '%d speaker', '%d speakers', $speaker_count, 'wpfaevent' ) ),
														absint( $speaker_count )
													);
													?>
												</span>
											<?php endif; ?>
										</div>

										<h3><?php echo esc_html( $event['title'] ); ?></h3>

										<div class="wpfa-event-card-meta">
											<?php if ( $event['date_label'] ) : ?>
												<span><?php echo esc_html( $event['date_label'] ); ?></span>
											<?php endif; ?>
											<?php if ( $event['location'] ) : ?>
												<span><?php echo esc_html( $event['location'] ); ?></span>
											<?php endif; ?>
										</div>

										<?php if ( $event['excerpt'] ) : ?>
											<p><?php echo esc_html( $event['excerpt'] ); ?></p>
										<?php endif; ?>

										<?php if ( ! empty( $event['track_names'] ) || ! empty( $event['tag_names'] ) ) : ?>
											<div class="wpfa-event-badges" aria-label="<?php esc_attr_e( 'Event categories', 'wpfaevent' ); ?>">
												<?php foreach ( array_slice( array_merge( $event['track_names'], $event['tag_names'] ), 0, 4 ) as $badge_label ) : ?>
													<span><?php echo esc_html( $badge_label ); ?></span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</a>

								<div class="wpfa-event-card-actions">
									<a href="<?php echo esc_url( get_permalink( $event['id'] ) ); ?>"><?php esc_html_e( 'View Event', 'wpfaevent' ); ?></a>
									<?php if ( $speaker_count ) : ?>
										<a href="<?php echo esc_url( $event['speakers_url'] ); ?>"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></a>
									<?php endif; ?>
									<?php if ( $event['event_url'] ) : ?>
										<a href="<?php echo esc_url( $event['event_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Register', 'wpfaevent' ); ?></a>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php
					if ( $total_pages > 1 ) {
						$pagination_url   = ! empty( $active_filter_args ) ? add_query_arg( $active_filter_args, $archive_url ) : $archive_url;
						$pagination_links = paginate_links(
							array(
								'base'      => esc_url_raw( add_query_arg( 'paged', '%#%', $pagination_url ) ),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => __( 'Previous', 'wpfaevent' ),
								'next_text' => __( 'Next', 'wpfaevent' ),
								'type'      => 'array',
							)
						);

						if ( $pagination_links ) {
							echo '<nav class="wpfa-pagination" aria-label="' . esc_attr__( 'Events pagination', 'wpfaevent' ) . '">';
							foreach ( $pagination_links as $pagination_link ) {
								echo wp_kses_post( $pagination_link );
							}
							echo '</nav>';
						}
					}
					?>
				<?php else : ?>
					<div class="wpfa-event-empty-state">
						<h3><?php esc_html_e( 'No events matched your filters', 'wpfaevent' ); ?></h3>
						<p><?php esc_html_e( 'Try a different search term or clear the filters to see every event.', 'wpfaevent' ); ?></p>
						<a href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Clear Filters', 'wpfaevent' ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>

	<footer class="wpfa-footer">
		<div class="container">
			<small>
				<?php
				printf(
					/* translators: %s: Current year. */
					esc_html__( 'FOSSASIA %s - Open Source Community Events', 'wpfaevent' ),
					esc_html( date_i18n( 'Y' ) )
				);
				?>
			</small>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
