<?php
/**
 * Eventyay Update Events Page Layout.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings         = isset( $settings ) ? $settings : array(
	'base_url'       => 'https://eventyay.com',
	'organizer_slug' => '',
	'event_slug'     => '',
	'api_token'      => '',
	'post_status'    => 'draft',
);
$endpoint_preview = isset( $endpoint_preview ) ? $endpoint_preview : '';
$notice           = isset( $notice ) ? $notice : false;
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'wpfaevent_eventyay_import' ); ?>

	<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( ! empty( $notice['type'] ) ? $notice['type'] : 'info' ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="card wpfaevent-settings-card">
		<h2><?php esc_html_e( 'Update Events from Eventyay', 'wpfaevent' ); ?></h2>
		<p><?php esc_html_e( 'Run this when Eventyay data changes after events have already been imported.', 'wpfaevent' ); ?></p>
		<p class="description">
			<?php esc_html_e( 'Existing Eventyay-owned event posts are updated in place while source metadata is preserved for future imports.', 'wpfaevent' ); ?>
		</p>

		<?php if ( is_wp_error( $endpoint_preview ) ) : ?>
			<p><?php echo esc_html( $endpoint_preview->get_error_message() ); ?></p>
		<?php elseif ( $endpoint_preview ) : ?>
			<p>
				<?php esc_html_e( 'Current endpoint:', 'wpfaevent' ); ?>
				<code><?php echo esc_html( $endpoint_preview ); ?></code>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'Save an organizer slug on the Import Events page before updating.', 'wpfaevent' ); ?></p>
		<?php endif; ?>

		<form id="wpfaevent-update-events-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wpfaevent_import_eventyay_events">
			<input type="hidden" name="wpfaevent_eventyay_return_page" value="wpfaevent-update-events">
			<?php wp_nonce_field( 'wpfaevent_import_eventyay_events' ); ?>
			<?php submit_button( __( 'Update Events from Eventyay', 'wpfaevent' ), 'primary', 'submit', false, empty( $settings['organizer_slug'] ) ? array( 'disabled' => 'disabled' ) : array() ); ?>
		</form>

		<p>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpfa_event&page=wpfaevent-import-events' ) ); ?>">
				<?php esc_html_e( 'Edit Eventyay import settings', 'wpfaevent' ); ?>
			</a>
		</p>
	</div>
</div>

<!-- Progress Overlay -->
<div id="wpfaevent-import-progress-overlay" style="display:none;">
	<div class="wpfaevent-progress-card">
		<div class="wpfaevent-spinner-container">
			<div class="wpfaevent-spinner"></div>
		</div>
		<h3 id="wpfaevent-progress-title"><?php esc_html_e( 'Syncing with Eventyay', 'wpfaevent' ); ?></h3>
		<div class="wpfaevent-progress-bar-wrapper">
			<div id="wpfaevent-progress-bar"></div>
		</div>
		<p id="wpfaevent-progress-status"><?php esc_html_e( 'Initializing...', 'wpfaevent' ); ?></p>
		<p id="wpfaevent-progress-details"></p>
	</div>
</div>
