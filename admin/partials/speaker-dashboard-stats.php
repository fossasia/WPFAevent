<?php
/**
 * Speaker dashboard stats partial.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stats variables.
 *
 * @var int $total_speakers_count   Total speakers count.
 * @var int $standalone_count       Standalone speakers count.
 * @var int $event_owned_count      Event-owned speakers count.
 * @var int $total_categories_count Total speaker categories count.
 */
?>
<div id="wpfaevent-overview" class="wpfaevent-dashboard-grid">
	<div class="wpfaevent-dashboard-card">
		<h2><?php esc_html_e( 'Total Speakers', 'wpfaevent' ); ?></h2>
		<p class="wpfaevent-kpi"><?php echo esc_html( (string) $total_speakers_count ); ?></p>
		<p class="description"><?php esc_html_e( 'Speaker posts registered on this site.', 'wpfaevent' ); ?></p>
	</div>
	<div class="wpfaevent-dashboard-card">
		<h2><?php esc_html_e( 'Standalone Speakers', 'wpfaevent' ); ?></h2>
		<p class="wpfaevent-kpi"><?php echo esc_html( (string) $standalone_count ); ?></p>
		<p class="description"><?php esc_html_e( 'Speakers not attached to any event.', 'wpfaevent' ); ?></p>
	</div>
	<div class="wpfaevent-dashboard-card">
		<h2><?php esc_html_e( 'Event-Owned Speakers', 'wpfaevent' ); ?></h2>
		<p class="wpfaevent-kpi"><?php echo esc_html( (string) $event_owned_count ); ?></p>
		<p class="description"><?php esc_html_e( 'Speakers linked to one or more events.', 'wpfaevent' ); ?></p>
	</div>
	<div class="wpfaevent-dashboard-card">
		<h2><?php esc_html_e( 'Speaker Categories', 'wpfaevent' ); ?></h2>
		<p class="wpfaevent-kpi"><?php echo esc_html( (string) $total_categories_count ); ?></p>
		<p class="description"><?php esc_html_e( 'Taxonomy categories used for speakers.', 'wpfaevent' ); ?></p>
	</div>
</div>
