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

$additional_information_page_url = get_permalink();

if ( ! $additional_information_page_url && class_exists( 'Wpfaevent_Additional_Information_Helper' ) ) {
	$additional_information_page_url = Wpfaevent_Additional_Information_Helper::get_additional_information_page_url();
}

$read_filter_value = static function ( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are read-only page filters.
	if ( ! isset( $_GET[ $key ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is sanitized below.
	$value = wp_unslash( $_GET[ $key ] );

	if ( is_array( $value ) ) {
		return '';
	}

	return sanitize_text_field( $value );
};

$resolve_event_filter = static function ( $event_filter ) {
	$event_filter = trim( (string) $event_filter );

	if ( '' === $event_filter ) {
		return 0;
	}

	if ( is_numeric( $event_filter ) ) {
		$event_id = absint( $event_filter );

		return ( $event_id && 'wpfa_event' === get_post_type( $event_id ) && 'publish' === get_post_status( $event_id ) ) ? $event_id : 0;
	}

	$events = get_posts(
		array(
			'name'           => sanitize_title( $event_filter ),
			'post_type'      => 'wpfa_event',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return ! empty( $events[0] ) ? absint( $events[0] ) : 0;
};

$current_event_filter = $read_filter_value( 'event' );
$selected_event_id    = $resolve_event_filter( $current_event_filter );
$selected_event_slug  = $selected_event_id ? get_post_field( 'post_name', $selected_event_id ) : '';
$selected_event_title = $selected_event_id ? get_the_title( $selected_event_id ) : '';
$selected_event_url   = $selected_event_id ? get_permalink( $selected_event_id ) : '';
$venue_information    = $selected_event_id ? trim( (string) get_post_meta( $selected_event_id, 'wpfa_event_venue_information', true ) ) : '';
$has_information      = '' !== trim( wp_strip_all_tags( $venue_information ) );
$schedule_page_url    = class_exists( 'Wpfaevent_Schedule_Helper' ) ? Wpfaevent_Schedule_Helper::get_schedule_page_url() : home_url( '/full-schedule/' );
$event_schedule_url   = $selected_event_id ? add_query_arg( 'event', $selected_event_slug, $schedule_page_url ) : $schedule_page_url;
$event_additional_url = $selected_event_id ? add_query_arg( 'event', $selected_event_slug, $additional_information_page_url ) : $additional_information_page_url;
$event_style_attr     = '';

if ( $selected_event_id && class_exists( 'Wpfaevent_Meta_Event' ) ) {
	$event_colors        = Wpfaevent_Meta_Event::get_event_colors( $selected_event_id );
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

$header_vars = array(
	'site_logo_url'        => $site_logo_url,
	'event_page_url'       => $selected_event_url ? $selected_event_url : home_url( '/events/' ),
	'show_back_button'     => true,
	'show_register_button' => false,
	'back_button_text'     => $selected_event_url ? __( 'Back to Event', 'wpfaevent' ) : __( 'Back to Events', 'wpfaevent' ),
	'register_button_url'  => '',
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
