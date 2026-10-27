<?php
/**
 * Locked Event/Speaker Template.
 *
 * Shown instead of the single event or speaker template while the post is
 * password-protected and not yet unlocked. Only public details are printed:
 * the title, plus the date and location for events.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$locked_post_id = get_queried_object_id();
$locked_type    = get_post_type( $locked_post_id );

if ( ! $locked_post_id || ! in_array( $locked_type, array( 'wpfa_event', 'wpfa_speaker' ), true ) ) {
	return;
}

$is_locked_event = 'wpfa_event' === $locked_type;
$locked_title    = get_the_title( $locked_post_id );
$date_label      = '';
$location        = '';
$body_style_attr = '';

if ( $is_locked_event ) {
	$event_data      = Wpfaevent_Event_Template_Controller::get_event_template_data( $locked_post_id );
	$header_vars     = $event_data['header_vars'];
	$body_style_attr = $event_data['event_style_attr'];
	$date_label      = $event_data['date_label'];
	$location        = $event_data['location'];
} else {
	$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
	if ( empty( $site_logo_url ) ) {
		$site_logo_url = WPFAEVENT_URL . 'assets/images/logo.png';
	}

	$header_vars = array(
		'site_logo_url'        => apply_filters( 'wpfa_site_logo_url', $site_logo_url ),
		'event_page_url'       => apply_filters( 'wpfa_speakers_page_url', home_url( '/speakers/' ) ),
		'show_back_button'     => true,
		'show_register_button' => true,
		'back_button_text'     => __( 'Back to Speakers', 'wpfaevent' ),
		'register_button_url'  => apply_filters( 'wpfa_register_button_url', get_option( 'wpfa_register_button_url', 'https://eventyay.com/e/4c0e0c27' ) ),
		'register_button_text' => __( 'Register', 'wpfaevent' ),
	);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-locked-template ' . ( $is_locked_event ? 'wpfa-event-template' : 'wpfa-speaker-profile-template' ) ); ?><?php echo $body_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
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

	<main class="<?php echo esc_attr( $is_locked_event ? 'wpfa-event-detail' : 'wpfa-speaker-profile' ); ?>">
		<?php if ( $is_locked_event ) : ?>
			<section class="wpfa-event-hero">
				<div class="container wpfa-event-hero-inner">
					<div class="wpfa-event-hero-copy">
						<h1><?php echo esc_html( $locked_title ); ?></h1>
						<?php if ( $date_label || $location ) : ?>
							<div class="wpfa-event-meta-list">
								<?php if ( $date_label ) : ?>
									<span><?php echo esc_html( $date_label ); ?></span>
								<?php endif; ?>
								<?php if ( $location ) : ?>
									<span><?php echo esc_html( $location ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
			<section class="wpfa-event-section wpfa-locked-content" aria-labelledby="wpfa-locked-title">
				<div class="container">
					<div class="wpfa-event-section-layout">
						<header class="wpfa-event-section-label">
							<p><?php esc_html_e( 'Protected', 'wpfaevent' ); ?></p>
							<h2 id="wpfa-locked-title"><?php esc_html_e( 'Enter the password', 'wpfaevent' ); ?></h2>
						</header>
						<div class="wpfa-event-rich-text">
							<?php echo get_the_password_form( $locked_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup. ?>
						</div>
					</div>
				</div>
			</section>
		<?php else : ?>
			<section class="wpfa-speaker-profile-hero">
				<div class="container wpfa-speaker-profile-hero-inner">
					<div class="wpfa-speaker-profile-photo">
						<img
							src="<?php echo esc_url( WPFAEVENT_URL . 'assets/images/speaker-placeholder.svg' ); ?>"
							alt="<?php esc_attr_e( 'Speaker photo placeholder', 'wpfaevent' ); ?>"
							class="wpfa-speaker-placeholder-img"
						>
					</div>
					<div class="wpfa-speaker-profile-summary">
						<h1><?php echo esc_html( $locked_title ); ?></h1>
						<div class="wpfa-speaker-profile-bio wpfa-locked-content">
							<?php echo get_the_password_form( $locked_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup. ?>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</main>

	<?php require WPFAEVENT_PATH . 'public/partials/footer.php'; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
