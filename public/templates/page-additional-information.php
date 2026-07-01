<?php
/**
 * Template Name: WPFA - Additional Information
 *
 * Displays event-specific attendee information such as nearby hotels,
 * transportation, parking, directions, and venue notes.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/templates
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_data = class_exists( 'Wpfaevent_Additional_Information_Helper' )
	? Wpfaevent_Additional_Information_Helper::get_additional_information_page_data( get_permalink() )
	: array();

if ( empty( $page_data ) ) {
	$site_logo_url = get_option( 'wpfa_site_logo_url', '' );
	if ( empty( $site_logo_url ) ) {
		$site_logo_url = defined( 'WPFAEVENT_URL' ) ? WPFAEVENT_URL . 'assets/images/logo.png' : '';
	}
	$site_logo_url = apply_filters( 'wpfa_site_logo_url', $site_logo_url );

	$page_data = array(
		'selected_event_id'               => 0,
		'selected_event_slug'             => '',
		'selected_event_title'            => '',
		'selected_event_url'              => '',
		'venue_information'               => '',
		'has_information'                 => false,
		'additional_information_page_url' => get_permalink(),
		'event_schedule_url'              => home_url( '/full-schedule/' ),
		'event_additional_url'            => get_permalink(),
		'event_style_attr'                => '',
		'header_vars'                     => array(
			'site_logo_url'        => $site_logo_url,
			'event_page_url'       => home_url( '/events/' ),
			'show_back_button'     => true,
			'show_register_button' => false,
			'back_button_text'     => __( 'Back to Events', 'wpfaevent' ),
			'register_button_url'  => '',
			'register_button_text' => __( 'Register', 'wpfaevent' ),
		),
	);
}

$selected_event_id               = $page_data['selected_event_id'];
$selected_event_slug             = $page_data['selected_event_slug'];
$selected_event_title            = $page_data['selected_event_title'];
$selected_event_url              = $page_data['selected_event_url'];
$venue_information               = $page_data['venue_information'];
$has_information                 = $page_data['has_information'];
$additional_information_page_url = $page_data['additional_information_page_url'];
$event_schedule_url              = $page_data['event_schedule_url'];
$event_additional_url            = $page_data['event_additional_url'];
$event_style_attr                = $page_data['event_style_attr'];
$header_vars                     = $page_data['header_vars'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template wpfa-additional-information-template' ); ?><?php echo $event_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
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

	<main class="wpfa-additional-information">
		<section class="wpfa-additional-information-hero">
			<div class="container">
				<p class="wpfa-event-kicker"><?php esc_html_e( 'Venue and travel', 'wpfaevent' ); ?></p>
				<h1><?php esc_html_e( 'Additional information', 'wpfaevent' ); ?></h1>
				<p>
					<?php
					if ( $selected_event_title ) {
						printf(
							/* translators: %s: event title. */
							esc_html__( 'Hotels, transportation, parking, directions, and other attendee details for %s.', 'wpfaevent' ),
							esc_html( $selected_event_title )
						);
					} else {
						esc_html_e( 'Hotels, transportation, parking, directions, and other attendee details for an event.', 'wpfaevent' );
					}
					?>
				</p>
			</div>
		</section>

		<section class="wpfa-event-section wpfa-additional-information-detail" aria-labelledby="wpfa-additional-information-title">
			<div class="container">
				<?php if ( $selected_event_id ) : ?>
					<nav class="wpfa-schedule-tabs" aria-label="<?php esc_attr_e( 'Event detail navigation', 'wpfaevent' ); ?>">
						<a href="<?php echo esc_url( $selected_event_url ); ?>"><?php esc_html_e( 'Info', 'wpfaevent' ); ?></a>
						<a href="<?php echo esc_url( $event_schedule_url ); ?>"><?php esc_html_e( 'Schedule', 'wpfaevent' ); ?></a>
						<a class="is-active" href="<?php echo esc_url( $event_additional_url ); ?>"><?php esc_html_e( 'Additional Information', 'wpfaevent' ); ?></a>
					</nav>
				<?php endif; ?>

				<div class="wpfa-event-section-head">
					<div>
						<h2 id="wpfa-additional-information-title">
							<?php echo esc_html( $selected_event_title ? $selected_event_title : __( 'Additional information', 'wpfaevent' ) ); ?>
						</h2>
						<p><?php esc_html_e( 'Venue, hotel, and transportation notes for attendees.', 'wpfaevent' ); ?></p>
					</div>
				</div>

				<?php if ( $selected_event_id && $has_information ) : ?>
					<div class="wpfa-event-rich-text wpfa-additional-information-body">
						<?php echo wp_kses_post( wpautop( $venue_information ) ); ?>
					</div>
				<?php elseif ( $selected_event_id ) : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'No additional information has been added for this event yet.', 'wpfaevent' ); ?></p>
				<?php else : ?>
					<p class="wpfa-empty-state"><?php esc_html_e( 'Open this page from an event to view event-specific additional information.', 'wpfaevent' ); ?></p>
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
