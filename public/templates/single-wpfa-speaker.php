<?php
/**
 * Single Speaker Profile Template.
 *
 * Displays an individual speaker profile and events linked through the
 * `wpfa_event_speakers` relationship.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$speaker_id = get_queried_object_id();

if ( ! $speaker_id || 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
	return;
}

$speaker_name  = get_the_title( $speaker_id );
$position      = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_position', true ) );
$organization  = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_organization', true ) );
$bio           = get_post_meta( $speaker_id, 'wpfa_speaker_bio', true );
$photo_url     = get_post_meta( $speaker_id, 'wpfa_speaker_headshot_url', true );
$linkedin      = get_post_meta( $speaker_id, 'wpfa_speaker_linkedin', true );
$twitter       = get_post_meta( $speaker_id, 'wpfa_speaker_twitter', true );
$github        = get_post_meta( $speaker_id, 'wpfa_speaker_github', true );
$website       = get_post_meta( $speaker_id, 'wpfa_speaker_website', true );
$talk_title    = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_talk_title', true ) );
$talk_date     = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_talk_date', true ) );
$talk_start    = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_talk_time', true ) );
$talk_end      = sanitize_text_field( get_post_meta( $speaker_id, 'wpfa_speaker_talk_end_time', true ) );
$talk_abstract = get_post_meta( $speaker_id, 'wpfa_speaker_talk_abstract', true );
$photo_alt     = sprintf(
	/* translators: %s: Speaker name. */
	__( 'Photo of %s', 'wpfaevent' ),
	$speaker_name
);

if ( empty( $bio ) ) {
	$bio = get_post_field( 'post_content', $speaker_id );
}

if ( empty( $photo_url ) && has_post_thumbnail( $speaker_id ) ) {
	$photo_url = get_the_post_thumbnail_url( $speaker_id, 'large' );
}

$speaker_categories = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
	$terms = wp_get_post_terms( $speaker_id, 'wpfa_speaker_category' );

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$speaker_categories = wp_list_pluck( $terms, 'name' );
	}
}

$session_meta = array();
if ( $talk_date ) {
	$session_meta[] = $talk_date;
}

if ( $talk_start || $talk_end ) {
	$session_meta[] = trim( $talk_start . ( $talk_start && $talk_end ? ' - ' : '' ) . $talk_end );
}

$has_session_details = $talk_title || $talk_date || $talk_start || $talk_end || $talk_abstract;

$stored_event_ids = get_post_meta( $speaker_id, 'wpfa_speaker_events', true );
$stored_event_ids = is_array( $stored_event_ids ) ? array_map( 'absint', $stored_event_ids ) : array();
$stored_event_ids = array_filter( $stored_event_ids );

$relationship_event_ids = array();
if ( empty( $stored_event_ids ) ) {
	$relationship_event_ids = get_posts(
		array(
			'post_type'      => 'wpfa_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Speaker-event links are stored in post meta.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'wpfa_event_speakers',
					'value'   => 'i:' . $speaker_id . ';',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_event_speakers',
					'value'   => '"' . $speaker_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wpfa_event_speakers',
					'value'   => (string) $speaker_id,
					'compare' => '=',
				),
			),
		)
	);
}

$linked_event_ids = array_values(
	array_unique(
		array_filter(
			array_map(
				'absint',
				array_merge( $stored_event_ids, $relationship_event_ids )
			)
		)
	)
);

usort(
	$linked_event_ids,
	static function ( $event_a_id, $event_b_id ) {
		$event_a_start = sanitize_text_field( get_post_meta( $event_a_id, 'wpfa_event_start_date', true ) );
		$event_b_start = sanitize_text_field( get_post_meta( $event_b_id, 'wpfa_event_start_date', true ) );

		if ( $event_a_start === $event_b_start ) {
			return strcasecmp( get_the_title( $event_a_id ), get_the_title( $event_b_id ) );
		}

		if ( '' === $event_a_start ) {
			return 1;
		}

		if ( '' === $event_b_start ) {
			return -1;
		}

		return strcmp( $event_a_start, $event_b_start );
	}
);

$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$speakers_page_url   = apply_filters( 'wpfa_speakers_page_url', home_url( '/speakers/' ) );
$register_button_url = get_option( 'wpfa_register_button_url', 'https://eventyay.com/e/4c0e0c27' );
$register_button_url = apply_filters( 'wpfa_register_button_url', $register_button_url );

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => $speakers_page_url,
	'show_back_button'     => true,
	'show_register_button' => true,
	'back_button_text'     => __( 'Back to Speakers', 'wpfaevent' ),
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
<body <?php body_class( 'wpfaevent wpfa-speaker-profile-template' ); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
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

	<main class="wpfa-speaker-profile" itemscope itemtype="https://schema.org/Person">
		<section class="wpfa-speaker-profile-hero">
			<div class="container wpfa-speaker-profile-hero-inner">
				<div class="wpfa-speaker-profile-photo">
					<?php if ( $photo_url ) : ?>
						<img
							src="<?php echo esc_url( $photo_url ); ?>"
							alt="<?php echo esc_attr( $photo_alt ); ?>"
							itemprop="image"
						>
					<?php else : ?>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 300 300"
							class="wpfa-placeholder-svg"
							role="img"
							aria-label="<?php esc_attr_e( 'Speaker photo placeholder', 'wpfaevent' ); ?>"
						>
							<rect width="100%" height="100%" fill="#eee" />
							<text
								x="50%"
								y="50%"
								dominant-baseline="middle"
								text-anchor="middle"
								font-family="sans-serif"
								font-size="20"
								fill="#999"
							>
								<?php esc_html_e( 'Speaker', 'wpfaevent' ); ?>
							</text>
						</svg>
					<?php endif; ?>
				</div>

				<div class="wpfa-speaker-profile-summary">
					<?php if ( ! empty( $speaker_categories ) ) : ?>
						<div class="wpfa-speaker-profile-categories">
							<?php foreach ( $speaker_categories as $speaker_category ) : ?>
								<span class="pill"><?php echo esc_html( $speaker_category ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<h1 itemprop="name"><?php echo esc_html( $speaker_name ); ?></h1>

					<?php if ( $position || $organization ) : ?>
						<p class="wpfa-speaker-profile-role">
							<?php echo esc_html( trim( $position . ( $position && $organization ? ' | ' : '' ) . $organization ) ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $bio ) : ?>
						<div class="wpfa-speaker-profile-bio" itemprop="description">
							<?php echo wp_kses_post( wpautop( $bio ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $linkedin || $twitter || $github || $website ) : ?>
						<div class="wpfa-speaker-social wpfa-speaker-profile-social">
							<?php if ( $linkedin ) : ?>
								<a
									href="<?php echo esc_url( $linkedin ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="wpfa-social-link"
									itemprop="sameAs"
								>
									<?php esc_html_e( 'LinkedIn', 'wpfaevent' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $twitter ) : ?>
								<a
									href="<?php echo esc_url( $twitter ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="wpfa-social-link"
									itemprop="sameAs"
								>
									<?php esc_html_e( 'Twitter', 'wpfaevent' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $github ) : ?>
								<a
									href="<?php echo esc_url( $github ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="wpfa-social-link"
									itemprop="sameAs"
								>
									<?php esc_html_e( 'GitHub', 'wpfaevent' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $website ) : ?>
								<a
									href="<?php echo esc_url( $website ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="wpfa-social-link"
									itemprop="sameAs"
								>
									<?php esc_html_e( 'Website', 'wpfaevent' ); ?>
								</a>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<?php if ( $has_session_details ) : ?>
				<section class="wpfa-speaker-sessions" aria-labelledby="wpfa-speaker-sessions-title">
					<div class="container">
						<h2 id="wpfa-speaker-sessions-title"><?php esc_html_e( 'Sessions by this speaker', 'wpfaevent' ); ?></h2>

						<article class="wpfa-speaker-session-card" itemprop="performerIn" itemscope itemtype="https://schema.org/Event">
							<?php if ( $talk_title ) : ?>
								<h3 itemprop="name"><?php echo esc_html( $talk_title ); ?></h3>
							<?php endif; ?>

							<?php if ( ! empty( $session_meta ) ) : ?>
								<p class="wpfa-speaker-session-meta"><?php echo esc_html( implode( ' | ', $session_meta ) ); ?></p>
							<?php endif; ?>

							<?php if ( $talk_abstract ) : ?>
								<div class="wpfa-speaker-session-abstract" itemprop="description">
									<?php echo wp_kses_post( wpautop( $talk_abstract ) ); ?>
								</div>
							<?php endif; ?>
						</article>
					</div>
				</section>
			<?php endif; ?>

			<section class="wpfa-linked-events" aria-labelledby="wpfa-linked-events-title">
				<div class="container">
					<h2 id="wpfa-linked-events-title"><?php esc_html_e( 'Linked Events', 'wpfaevent' ); ?></h2>

					<?php if ( ! empty( $linked_event_ids ) ) : ?>
					<div class="wpfa-linked-events-grid">
						<?php foreach ( $linked_event_ids as $event_id ) : ?>
							<?php
							$event_title = get_the_title( $event_id );
							$event_start = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
							$event_end   = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
							$event_loc   = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
							$event_url   = get_post_meta( $event_id, 'wpfa_event_url', true );
							$event_url   = $event_url ? $event_url : get_permalink( $event_id );
							$event_meta  = array();

							if ( $event_start ) {
								$event_meta[] = $event_start . ( $event_end ? ' - ' . $event_end : '' );
							}

							if ( $event_loc ) {
								$event_meta[] = $event_loc;
							}
							?>
							<article class="wpfa-linked-event-card">
								<h3><a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( $event_title ); ?></a></h3>

								<?php if ( ! empty( $event_meta ) ) : ?>
									<p class="wpfa-linked-event-meta"><?php echo esc_html( implode( ' | ', $event_meta ) ); ?></p>
								<?php endif; ?>

								<?php if ( has_excerpt( $event_id ) ) : ?>
									<p><?php echo esc_html( get_the_excerpt( $event_id ) ); ?></p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'No linked events found for this speaker yet.', 'wpfaevent' ); ?></p>
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
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
