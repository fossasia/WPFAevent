<?php
/**
 * Dynamic Custom Event Page Template.
 *
 * Displays a custom page configured within an event's navigation.
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

$page_data = class_exists( 'Wpfaevent_Main_Navigation_Helper' )
	? Wpfaevent_Main_Navigation_Helper::get_current_custom_page( $event_id )
	: null;

if ( $page_data ) {
	$page_title = ! empty( $page_data['title'] ) ? (string) $page_data['title'] : (string) $page_data['text'];
	$page_body  = ! empty( $page_data['content'] ) ? (string) $page_data['content'] : '';
} else {
	$page_title = __( 'Information', 'wpfaevent' );
	$page_body  = '';
}

$event_style_attr = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_event_style_attribute( $event_id ) : '';
$event_title      = get_the_title( $event_id );
$event_url        = get_permalink( $event_id );

// Convert markdown-style bullet lines (- item or * item) into <ul><li> only if plain text was entered.
$formatted_content = $page_body;
$is_plain_text     = ( '' !== $formatted_content && wp_strip_all_tags( $formatted_content ) === $formatted_content );

if ( $is_plain_text ) {
	$lines     = explode( "\n", str_replace( "\r\n", "\n", $formatted_content ) );
	$in_list   = false;
	$out_lines = array();

	foreach ( $lines as $line ) {
		$trimmed = trim( $line );
		if ( 0 === strpos( $trimmed, '- ' ) || 0 === strpos( $trimmed, '* ' ) ) {
			if ( ! $in_list ) {
				$out_lines[] = '<ul>';
				$in_list     = true;
			}
			$out_lines[] = '<li>' . esc_html( substr( $trimmed, 2 ) ) . '</li>';
		} else {
			if ( $in_list ) {
				$out_lines[] = '</ul>';
				$in_list     = false;
			}
			$out_lines[] = esc_html( $line );
		}
	}
	if ( $in_list ) {
		$out_lines[] = '</ul>';
	}
	$formatted_content = implode( "\n", $out_lines );
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $page_title . ' - ' . $event_title ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'wpfaevent wpfa-event-template wpfa-custom-page-template' ); ?><?php echo $event_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when built. ?>>
	<?php wp_body_open(); ?>

<div id="page" class="site">
	<?php
	$nav_partial = WPFAEVENT_PATH . 'public/partials/header.php';
	if ( file_exists( $nav_partial ) ) {
		include $nav_partial;
	}
	?>

	<main class="wpfa-event-custom-page" role="main">
		<header class="page-hero">
			<div class="container">
				<p class="wpfa-event-kicker">
					<a href="<?php echo esc_url( $event_url ); ?>">&larr; <?php echo esc_html( $event_title ); ?></a>
				</p>
				<h1>
					<?php echo esc_html( $page_title ); ?>
				</h1>
			</div>
		</header>

		<?php
		$saved_event_nav      = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
		$wpfa_event_nav_items = ( is_array( $saved_event_nav ) && ! empty( $saved_event_nav ) )
			? $saved_event_nav
			: ( class_exists( 'Wpfaevent_Main_Navigation_Helper' ) ? Wpfaevent_Main_Navigation_Helper::get_default_event_nav_items() : array() );

		if ( ! empty( $wpfa_event_nav_items ) ) {
			include WPFAEVENT_PATH . 'public/partials/event-section-nav.php';
		}
		?>

		<div class="container wpfa-event-custom-content-wrap">
			<article class="wpfa-event-rich-text">
				<?php if ( '' !== $formatted_content ) : ?>
					<?php echo wp_kses_post( wpautop( $formatted_content ) ); ?>
				<?php else : ?>
					<p class="wpfa-empty-state">
						<?php esc_html_e( 'No information has been added for this page yet.', 'wpfaevent' ); ?>
					</p>
				<?php endif; ?>
			</article>
		</div>
	</main>

	<?php require WPFAEVENT_PATH . 'public/partials/footer.php'; ?>
</div>

	<?php wp_footer(); ?>
</body>
</html>
