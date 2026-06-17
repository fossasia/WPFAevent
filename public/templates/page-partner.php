<?php
/**
 * Template Name: WPFA - Partner
 *
 * Displays full sponsor or exhibitor details for an event.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$partner_request = class_exists( 'Wpfaevent_Partner_Helper' )
	? Wpfaevent_Partner_Helper::resolve_partner_request()
	: array(
		'event_id'      => 0,
		'event_slug'    => '',
		'event_title'   => '',
		'event_url'     => '',
		'type'          => '',
		'partner_key'   => '',
		'partner'       => array(),
		'group_name'    => '',
		'partner_label' => '',
	);

$event_id         = absint( $partner_request['event_id'] );
$event_title      = $partner_request['event_title'];
$event_url        = $partner_request['event_url'];
$partner_type     = $partner_request['type'];
$partner          = $partner_request['partner'];
$group_name       = $partner_request['group_name'];
$partner_label    = $partner_request['partner_label'];
$has_partner      = ! empty( $partner['name'] );
$partner_name     = $has_partner ? sanitize_text_field( $partner['name'] ) : '';
$description      = ! empty( $partner['description'] ) ? wp_kses_post( $partner['description'] ) : '';
$website_link     = ! empty( $partner['link'] ) ? esc_url_raw( $partner['link'] ) : '';
$logo_url         = '';
$banner_url       = '';
$video_url        = ! empty( $partner['video'] ) ? esc_url_raw( $partner['video'] ) : '';
$slides_url       = ! empty( $partner['slides'] ) ? esc_url_raw( $partner['slides'] ) : '';
$contact_link     = ! empty( $partner['contact_link'] ) ? esc_url_raw( $partner['contact_link'] ) : '';
$contact_email    = ! empty( $partner['contact_email'] ) ? sanitize_email( $partner['contact_email'] ) : '';
$event_style_attr = '';

if ( 'sponsor' === $partner_type ) {
	$logo_url = ! empty( $partner['image'] ) ? esc_url_raw( $partner['image'] ) : '';
} else {
	$logo_url   = ! empty( $partner['logo'] ) ? esc_url_raw( $partner['logo'] ) : '';
	$banner_url = ! empty( $partner['banner'] ) ? esc_url_raw( $partner['banner'] ) : '';
}

if ( $event_id && class_exists( 'Wpfaevent_Meta_Event' ) ) {
	$event_colors        = Wpfaevent_Meta_Event::get_event_colors( $event_id );
	$event_color_var_map = array(
		'wpfa_event_primary_color'          => '--event-primary',
		'wpfa_event_hover_button_color'     => '--event-primary-dark',
		'wpfa_event_theme_background_color' => '--event-soft',
		'wpfa_event_theme_success_color'    => '--event-success',
		'wpfa_event_theme_danger_color'     => '--event-danger',
	);
	$event_style_vars    = array();

	foreach ( $event_color_var_map as $meta_key => $css_var ) {
		if ( ! empty( $event_colors[ $meta_key ] ) ) {
			$event_style_vars[] = $css_var . ': ' . $event_colors[ $meta_key ];
		}
	}

	$event_style_attr = $event_style_vars ? ' style="' . esc_attr( implode( '; ', $event_style_vars ) ) . '"' : '';
}

$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
if ( empty( $site_logo_url ) ) {
	$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
}
$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

$back_url = $event_url ? $event_url . '#exhibitors' : home_url( '/events/' );
if ( 'sponsor' === $partner_type && $event_url ) {
	$back_url = $event_url . '#sponsors';
}

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => $event_url ? $event_url : home_url( '/events/' ),
	'show_back_button'     => true,
	'show_register_button' => false,
	'back_button_text'     => $event_url ? __( 'Back to Event', 'wpfaevent' ) : __( 'Back to Events', 'wpfaevent' ),
	'register_button_url'  => '',
	'register_button_text' => __( 'Register', 'wpfaevent' ),
);

$partner_initial = $partner_name ? strtoupper( substr( $partner_name, 0, 1 ) ) : '';
$has_links       = $website_link || $video_url || $slides_url || $contact_link || $contact_email;
$partner_classes = array(
	'wpfa-partner-detail',
	$partner_type ? 'is-' . sanitize_html_class( $partner_type ) : 'is-partner',
	$logo_url ? 'has-logo' : 'no-logo',
	$banner_url ? 'has-banner' : 'no-banner',
	$has_links ? 'has-links' : 'no-links',
);
$partner_label   = $partner_label ? $partner_label : __( 'Partner', 'wpfaevent' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template wpfa-partner-template' ); ?><?php echo $event_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
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

	<main class="<?php echo esc_attr( implode( ' ', $partner_classes ) ); ?>">
		<section class="wpfa-partner-detail-hero">
			<div class="container">
				<div class="wpfa-partner-detail-hero-inner">
					<div class="wpfa-partner-detail-hero-copy">
						<p class="wpfa-event-kicker"><?php echo esc_html( $partner_label ); ?></p>
						<h1><?php echo esc_html( $partner_name ? $partner_name : __( 'Partner', 'wpfaevent' ) ); ?></h1>
						<?php if ( $event_title ) : ?>
							<p class="wpfa-partner-detail-lead">
								<?php
								printf(
									/* translators: %s: event title. */
									esc_html__( 'Details for %s.', 'wpfaevent' ),
									esc_html( $event_title )
								);
								?>
							</p>
						<?php endif; ?>
					</div>
					<?php if ( $has_partner ) : ?>
						<div class="wpfa-partner-detail-hero-meta" aria-label="<?php esc_attr_e( 'Partner summary', 'wpfaevent' ); ?>">
							<span><?php echo esc_html( $partner_label ); ?></span>
							<strong><?php echo esc_html( $event_title ? $event_title : __( 'Event partner', 'wpfaevent' ) ); ?></strong>
							<?php if ( $group_name ) : ?>
								<em><?php echo esc_html( $group_name ); ?></em>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="wpfa-event-section wpfa-partner-detail-body" aria-labelledby="wpfa-partner-detail-title">
			<div class="container">
				<?php if ( $has_partner ) : ?>
					<div class="wpfa-partner-detail-card">
						<div class="wpfa-partner-detail-media">
							<?php if ( $banner_url ) : ?>
								<img class="wpfa-partner-detail-banner" src="<?php echo esc_url( $banner_url ); ?>" alt="<?php echo esc_attr( $partner_name ); ?>" loading="lazy">
							<?php endif; ?>

							<div class="wpfa-partner-detail-identity">
								<?php if ( $logo_url ) : ?>
									<div class="wpfa-partner-detail-logo">
										<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $partner_name ); ?>" loading="lazy">
									</div>
								<?php else : ?>
									<div class="wpfa-event-exhibitor-placeholder" aria-hidden="true">
										<?php echo esc_html( $partner_initial ); ?>
									</div>
								<?php endif; ?>
								<div class="wpfa-partner-detail-identity-copy">
									<span><?php echo esc_html( $partner_label ); ?></span>
									<strong><?php echo esc_html( $partner_name ); ?></strong>
								</div>
							</div>

							<?php if ( $group_name ) : ?>
								<p class="wpfa-partner-detail-group"><?php echo esc_html( $group_name ); ?></p>
							<?php endif; ?>
						</div>

						<div class="wpfa-partner-detail-content">
							<div class="wpfa-partner-detail-header">
								<p class="wpfa-event-kicker"><?php esc_html_e( 'Profile', 'wpfaevent' ); ?></p>
								<h2 id="wpfa-partner-detail-title"><?php echo esc_html( $partner_name ); ?></h2>
								<?php if ( $group_name ) : ?>
									<p class="wpfa-partner-detail-meta"><?php echo esc_html( $group_name ); ?></p>
								<?php endif; ?>
							</div>

							<?php if ( $description ) : ?>
								<div class="wpfa-event-rich-text wpfa-partner-detail-description">
									<?php echo wp_kses_post( wpautop( $description ) ); ?>
								</div>
							<?php else : ?>
								<p class="wpfa-partner-detail-empty"><?php esc_html_e( 'No profile details have been added yet.', 'wpfaevent' ); ?></p>
							<?php endif; ?>

							<?php if ( $has_links ) : ?>
								<div class="wpfa-event-exhibitor-links wpfa-partner-detail-links">
									<?php if ( $website_link ) : ?>
										<a href="<?php echo esc_url( $website_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Website', 'wpfaevent' ); ?></a>
									<?php endif; ?>
									<?php if ( $video_url ) : ?>
										<a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Video', 'wpfaevent' ); ?></a>
									<?php endif; ?>
									<?php if ( $slides_url ) : ?>
										<a href="<?php echo esc_url( $slides_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Slides', 'wpfaevent' ); ?></a>
									<?php endif; ?>
									<?php if ( $contact_link ) : ?>
										<a href="<?php echo esc_url( $contact_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Contact', 'wpfaevent' ); ?></a>
									<?php elseif ( $contact_email ) : ?>
										<a href="<?php echo esc_url( 'mailto:' . $contact_email ); ?>"><?php esc_html_e( 'Contact', 'wpfaevent' ); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<p class="wpfa-partner-detail-back">
						<a href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Back to event partners', 'wpfaevent' ); ?></a>
					</p>
				<?php elseif ( $event_id ) : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'This partner could not be found for the selected event.', 'wpfaevent' ); ?></p>
				<?php else : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'Open a partner card from an event page to view full details.', 'wpfaevent' ); ?></p>
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
