<?php
/**
 * Event section navigation partial.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $wpfa_event_nav_items ) || ! is_array( $wpfa_event_nav_items ) ) {
	return;
}

$event_id        = isset( $event_id ) ? (int) $event_id : (int) get_the_ID();
$event_permalink = ( $event_id > 0 ) ? get_permalink( $event_id ) : '';
$raw_custom_page = isset( $_GET['custom_page'] ) ? sanitize_title( wp_unslash( $_GET['custom_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_custom_page  = ( '' !== $raw_custom_page );
$current_slug    = $raw_custom_page;
?>
<nav class="wpfa-event-section-nav" aria-label="<?php esc_attr_e( 'Event sections', 'wpfaevent' ); ?>">
	<div class="container">
		<?php foreach ( $wpfa_event_nav_items as $nav_item ) : ?>
			<?php
			if ( empty( $nav_item['text'] ) ) {
				continue;
			}

			$nav_type = isset( $nav_item['type'] ) ? (string) $nav_item['type'] : 'link';
			?>
			<?php if ( 'dropdown' === $nav_type && ! empty( $nav_item['items'] ) && is_array( $nav_item['items'] ) ) : ?>
				<?php
				$has_active_sub = false;
				$rendered_subs  = array();
				foreach ( $nav_item['items'] as $sub_item ) {
					if ( empty( $sub_item['href'] ) || empty( $sub_item['text'] ) ) {
						continue;
					}
					$sub_href = (string) $sub_item['href'];
					if ( 0 === strpos( $sub_href, '?custom_page=' ) && $event_permalink ) {
						$sub_slug = ! empty( $sub_item['slug'] ) ? (string) $sub_item['slug'] : substr( $sub_href, strlen( '?custom_page=' ) );
						$sub_href = add_query_arg( 'custom_page', $sub_slug, $event_permalink );
					} elseif ( 0 === strpos( $sub_href, '#' ) && $is_custom_page && $event_permalink ) {
						$sub_href = $event_permalink . $sub_href;
					}

					$is_sub_active = false;
					if ( ! empty( $sub_item['slug'] ) && $current_slug === $sub_item['slug'] ) {
						$is_sub_active = true;
					}

					if ( $is_sub_active ) {
						$has_active_sub = true;
					}

					$rendered_subs[] = array(
						'href'   => $sub_href,
						'text'   => (string) $sub_item['text'],
						'active' => $is_sub_active ? 'active' : '',
					);
				}

				if ( empty( $rendered_subs ) ) {
					continue;
				}

				$dropdown_class = 'wpfa-event-nav-dropdown nav-dropdown' . ( $has_active_sub ? ' active' : '' );
				$toggle_class   = 'wpfa-event-nav-dropdown-toggle nav-dropdown-toggle' . ( $has_active_sub ? ' active' : '' );
				?>
				<div class="<?php echo esc_attr( $dropdown_class ); ?>">
					<button type="button" class="<?php echo esc_attr( $toggle_class ); ?>" aria-haspopup="true" aria-expanded="false">
						<?php echo esc_html( $nav_item['text'] ); ?>
						<span class="nav-dropdown-caret" aria-hidden="true">&#9662;</span>
					</button>
					<div class="wpfa-event-nav-dropdown-content nav-dropdown-content">
						<?php foreach ( $rendered_subs as $r_sub ) : ?>
							<a href="<?php echo esc_url( $r_sub['href'] ); ?>" class="<?php echo esc_attr( $r_sub['active'] ); ?>"><?php echo esc_html( $r_sub['text'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php else : ?>
				<?php
				$href = ! empty( $nav_item['href'] ) ? (string) $nav_item['href'] : '';
				if ( 0 === strpos( $href, '?custom_page=' ) && $event_permalink ) {
					$item_slug = ! empty( $nav_item['slug'] ) ? (string) $nav_item['slug'] : substr( $href, strlen( '?custom_page=' ) );
					$href      = add_query_arg( 'custom_page', $item_slug, $event_permalink );
				} elseif ( 0 === strpos( $href, '#' ) && $is_custom_page && $event_permalink ) {
					$href = $event_permalink . $href;
				}

				$item_active = '';
				if ( ! empty( $nav_item['slug'] ) && $current_slug === $nav_item['slug'] ) {
					$item_active = 'active';
				}
				?>
				<?php if ( '' !== $href ) : ?>
					<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $item_active ); ?>"><?php echo esc_html( $nav_item['text'] ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</nav>
