<?php
/**
 * Single Event Template.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_id = get_queried_object_id();

if ( ! $event_id || 'wpfa_event' !== get_post_type( $event_id ) ) {
	return;
}

if ( have_posts() ) {
	the_post();
}

$format_event_date = static function ( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	if ( false === $timestamp ) {
		return $date;
	}

	return date_i18n( get_option( 'date_format' ), $timestamp );
};

$site_logo_url = get_option( 'wpfa_site_logo_url', WPFAEVENT_URL . 'assets/images/logo.png' );
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$start_date       = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_start_date', true ) );
$end_date         = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_end_date', true ) );
$event_time       = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_time', true ) );
$event_location   = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_location', true ) );
$lead_text        = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_lead_text', true ) );
$event_url        = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_url', true ) );
$registration_url = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_registration_link', true ) );
$cfs_url          = esc_url_raw( get_post_meta( $event_id, 'wpfa_event_cfs_link', true ) );
$image_url        = get_the_post_thumbnail_url( $event_id, 'large' );

$start_label = $format_event_date( $start_date );
$end_label   = $format_event_date( $end_date );
$date_label  = $start_label;

if ( $start_label && $end_label && $end_label !== $start_label ) {
	$date_label = sprintf(
		/* translators: 1: event start date, 2: event end date. */
		__( '%1$s - %2$s', 'wpfaevent' ),
		$start_label,
		$end_label
	);
}

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => home_url( '/events/' ),
	'show_back_button'     => true,
	'show_register_button' => ! empty( $registration_url ),
	'back_button_text'     => __( 'Back to Events', 'wpfaevent' ),
	'register_button_url'  => $registration_url,
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
<body <?php body_class( 'wpfaevent wpfa-single-event-template' ); ?>>
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

	<main class="wpfa-single-event">
		<header class="page-hero wpfa-single-event-hero">
			<div class="hero-bg" aria-hidden="true"></div>
			<div class="container">
				<h1><?php echo esc_html( get_the_title( $event_id ) ); ?></h1>

				<?php if ( ! empty( $lead_text ) ) : ?>
					<p><?php echo esc_html( $lead_text ); ?></p>
				<?php elseif ( has_excerpt( $event_id ) ) : ?>
					<p><?php echo esc_html( get_the_excerpt( $event_id ) ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $registration_url ) || ! empty( $event_url ) || ! empty( $cfs_url ) ) : ?>
					<div class="wpfa-single-event-actions">
						<?php if ( ! empty( $registration_url ) ) : ?>
							<a class="btn btn-primary" href="<?php echo esc_url( $registration_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Register', 'wpfaevent' ); ?>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $event_url ) && ( empty( $registration_url ) || $event_url !== $registration_url ) ) : ?>
							<a class="btn btn-secondary" href="<?php echo esc_url( $event_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Event Website', 'wpfaevent' ); ?>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $cfs_url ) ) : ?>
							<a class="btn btn-secondary" href="<?php echo esc_url( $cfs_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Call for Speakers', 'wpfaevent' ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</header>

		<div class="container">
			<div class="wpfa-single-event-layout">
				<article class="wpfa-single-event-content">
					<?php if ( ! empty( $image_url ) ) : ?>
						<figure class="wpfa-single-event-image">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>">
						</figure>
					<?php endif; ?>

					<div class="wpfa-single-event-body">
						<?php
						echo wp_kses_post(
							apply_filters( 'the_content', get_post_field( 'post_content', $event_id ) )
						);
						?>
					</div>
				</article>

				<aside class="wpfa-single-event-details" aria-label="<?php esc_attr_e( 'Event details', 'wpfaevent' ); ?>">
					<h2><?php esc_html_e( 'Event Details', 'wpfaevent' ); ?></h2>

					<?php if ( ! empty( $date_label ) ) : ?>
						<div class="wpfa-single-event-detail">
							<strong><?php esc_html_e( 'Date', 'wpfaevent' ); ?></strong>
							<span><?php echo esc_html( $date_label ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $event_time ) ) : ?>
						<div class="wpfa-single-event-detail">
							<strong><?php esc_html_e( 'Time', 'wpfaevent' ); ?></strong>
							<span><?php echo esc_html( $event_time ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $event_location ) ) : ?>
						<div class="wpfa-single-event-detail">
							<strong><?php esc_html_e( 'Location', 'wpfaevent' ); ?></strong>
							<span><?php echo esc_html( $event_location ); ?></span>
						</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	</main>

	<?php require WPFAEVENT_PATH . 'public/partials/footer.php'; ?>
</div>

	<?php wp_footer(); ?>
</body>
</html>
