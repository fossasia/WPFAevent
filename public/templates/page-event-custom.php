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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_slug = isset( $_GET['custom_page'] ) ? sanitize_title( wp_unslash( $_GET['custom_page'] ) ) : '';

$nav_items  = get_post_meta( $event_id, 'wpfa_event_custom_navigation', true );
$page_title = '';
$page_body  = '';
$found_page = false;

if ( is_array( $nav_items ) ) {
	foreach ( $nav_items as $item ) {
		if ( isset( $item['type'] ) && 'custom_page' === $item['type'] && isset( $item['slug'] ) && $item['slug'] === $page_slug ) {
			$page_title = ! empty( $item['title'] ) ? (string) $item['title'] : (string) $item['text'];
			$page_body  = ! empty( $item['content'] ) ? (string) $item['content'] : '';
			$found_page = true;
			break;
		}

		if ( isset( $item['type'] ) && 'dropdown' === $item['type'] && ! empty( $item['items'] ) && is_array( $item['items'] ) ) {
			foreach ( $item['items'] as $sub ) {
				if ( isset( $sub['type'] ) && 'custom_page' === $sub['type'] && isset( $sub['slug'] ) && $sub['slug'] === $page_slug ) {
					$page_title = ! empty( $sub['title'] ) ? (string) $sub['title'] : (string) $sub['text'];
					$page_body  = ! empty( $sub['content'] ) ? (string) $sub['content'] : '';
					$found_page = true;
					break 2;
				}
			}
		}
	}
}

if ( ! $found_page ) {
	$page_title = __( 'Information', 'wpfaevent' );
}

$event_style_attr = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_event_style_attribute( $event_id ) : '';
$event_title      = get_the_title( $event_id );
$event_url        = get_permalink( $event_id );

// Convert markdown-style bullet lines (- item or * item) into <ul><li> if plain text was entered.
$formatted_content = $page_body;
if ( '' !== $formatted_content && false === strpos( $formatted_content, '<ul' ) && false === strpos( $formatted_content, '<ol' ) ) {
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
			$out_lines[] = $line;
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
		<header class="page-hero" style="text-align: center; padding: 48px 20px 32px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
			<div class="container">
				<p class="wpfa-event-kicker" style="font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--event-primary, #d95700); margin-bottom: 8px;">
					<a href="<?php echo esc_url( $event_url ); ?>" style="color: inherit; text-decoration: none;">&larr; <?php echo esc_html( $event_title ); ?></a>
				</p>
				<h1 style="font-size: clamp(2rem, 3.5vw, 2.75rem); font-weight: 800; color: var(--event-ink, #0f172a); margin: 0;">
					<?php echo esc_html( $page_title ); ?>
				</h1>
			</div>
		</header>

		<div class="container" style="max-width: 860px; margin: 40px auto; padding: 0 20px;">
			<article class="wpfa-event-rich-text" style="font-size: 1.05rem; line-height: 1.8; color: #334155;">
				<?php if ( '' !== $formatted_content ) : ?>
					<?php echo wp_kses_post( wpautop( $formatted_content ) ); ?>
				<?php else : ?>
					<p class="wpfa-empty-state" style="color: #64748b; font-style: italic;">
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
