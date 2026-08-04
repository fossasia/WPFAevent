<?php
/**
 * Speaker dashboard header partial.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Header variables.
 *
 * @var string $new_speaker_url   URL to create a new speaker.
 * @var string $switch_view_url   URL to switch views.
 * @var string $switch_view_label Label for the switch view button.
 */
?>
<div class="wpfaevent-dashboard-hero">
	<div class="wpfaevent-dashboard-meta">
		<div class="wpfaevent-badge"><?php esc_html_e( 'Speakers Hub', 'wpfaevent' ); ?></div>
	</div>
	<p><?php esc_html_e( 'Manage all speakers across your site events. Review attached speaker records, standalone profiles, and categories.', 'wpfaevent' ); ?></p>
	<div class="wpfaevent-dashboard-actions">
		<a class="button" href="<?php echo esc_url( $new_speaker_url ); ?>">
			<?php esc_html_e( 'Add New Speaker', 'wpfaevent' ); ?>
		</a>
		<a class="button button-secondary" href="<?php echo esc_url( $switch_view_url ); ?>">
			<?php echo esc_html( $switch_view_label ); ?>
		</a>
	</div>
</div>
