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
				<div class="wpfa-event-nav-dropdown nav-dropdown">
					<button type="button" class="wpfa-event-nav-dropdown-toggle nav-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
						<?php echo esc_html( $nav_item['text'] ); ?>
					</button>
					<div class="wpfa-event-nav-dropdown-content nav-dropdown-content">
						<?php foreach ( $nav_item['items'] as $sub_item ) : ?>
							<?php if ( empty( $sub_item['href'] ) || empty( $sub_item['text'] ) ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a href="<?php echo esc_url( $sub_item['href'] ); ?>"><?php echo esc_html( $sub_item['text'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php elseif ( ! empty( $nav_item['href'] ) ) : ?>
				<a href="<?php echo esc_url( $nav_item['href'] ); ?>"><?php echo esc_html( $nav_item['text'] ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</nav>
