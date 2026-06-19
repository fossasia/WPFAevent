<?php
/**
 * Eventyay Import Settings Page Layout.
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
		<h2><?php esc_html_e( 'Eventyay Event Import', 'wpfaevent' ); ?></h2>
		<p>
			<?php esc_html_e( 'Import events from the current Eventyay REST API endpoint:', 'wpfaevent' ); ?>
			<code>/api/v1/organizers/{organizer}/events/</code>
		</p>
		<p class="description">
			<?php esc_html_e( 'When an event slug is provided, the importer also tries compatible event API URLs before reporting an event as not found.', 'wpfaevent' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'wpfaevent_eventyay_import' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_base_url"><?php esc_html_e( 'Eventyay base URL', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" id="wpfaevent_eventyay_base_url" name="wpfaevent_eventyay_import_settings[base_url]" value="<?php echo esc_attr( $settings['base_url'] ); ?>" placeholder="https://eventyay.com">
						<p class="description"><?php esc_html_e( 'Use the site root, not the API path. Self-hosted Eventyay installs are supported.', 'wpfaevent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_organizer_slug"><?php esc_html_e( 'Organizer slug', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wpfaevent_eventyay_organizer_slug" name="wpfaevent_eventyay_import_settings[organizer_slug]" value="<?php echo esc_attr( $settings['organizer_slug'] ); ?>" placeholder="bigevents">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_event_slug"><?php esc_html_e( 'Event slug', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wpfaevent_eventyay_event_slug" name="wpfaevent_eventyay_import_settings[event_slug]" value="<?php echo esc_attr( $settings['event_slug'] ); ?>" placeholder="sampleconf">
						<p class="description"><?php esc_html_e( 'Leave empty to import all events visible to the token for this organizer.', 'wpfaevent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_api_token"><?php esc_html_e( 'API token', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="password" class="regular-text" id="wpfaevent_eventyay_api_token" name="wpfaevent_eventyay_import_settings[api_token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( ! empty( $settings['api_token'] ) ? __( 'Token saved; leave blank to keep it', 'wpfaevent' ) : __( 'Optional for public endpoints', 'wpfaevent' ) ); ?>">
						<?php if ( ! empty( $settings['api_token'] ) ) : ?>
							<label style="display:block;margin-top:8px;">
								<input type="checkbox" name="wpfaevent_eventyay_import_settings[clear_api_token]" value="1">
								<?php esc_html_e( 'Clear saved token', 'wpfaevent' ); ?>
							</label>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Eventyay API tokens are sent as an Authorization header. The importer uses Token auth first and retries legacy Eventyay endpoints with JWT auth when needed. Keep this token private.', 'wpfaevent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_post_status"><?php esc_html_e( 'Imported post status', 'wpfaevent' ); ?></label></th>
					<td>
						<select id="wpfaevent_eventyay_post_status" name="wpfaevent_eventyay_import_settings[post_status]">
							<option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'wpfaevent' ); ?></option>
							<option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>><?php esc_html_e( 'Published', 'wpfaevent' ); ?></option>
							<option value="pending" <?php selected( $settings['post_status'], 'pending' ); ?>><?php esc_html_e( 'Pending review', 'wpfaevent' ); ?></option>
							<option value="private" <?php selected( $settings['post_status'], 'private' ); ?>><?php esc_html_e( 'Private', 'wpfaevent' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Eventyay Settings', 'wpfaevent' ) ); ?>
		</form>

		<hr>

		<h3><?php esc_html_e( 'Import Events', 'wpfaevent' ); ?></h3>
		<?php if ( is_wp_error( $endpoint_preview ) ) : ?>
			<p><?php echo esc_html( $endpoint_preview->get_error_message() ); ?></p>
		<?php elseif ( $endpoint_preview ) : ?>
			<p>
				<?php esc_html_e( 'Current endpoint:', 'wpfaevent' ); ?>
				<code><?php echo esc_html( $endpoint_preview ); ?></code>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'Save an organizer slug before importing.', 'wpfaevent' ); ?></p>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'Use this to import Eventyay events for the configured organizer. Use the Update Events menu item when Eventyay data changes after the initial import.', 'wpfaevent' ); ?>
		</p>

		<form id="wpfaevent-import-events-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wpfaevent_import_eventyay_events">
			<input type="hidden" name="wpfaevent_eventyay_return_page" value="wpfaevent-import-events">
			<?php wp_nonce_field( 'wpfaevent_import_eventyay_events' ); ?>
			<?php submit_button( __( 'Import Events from Eventyay', 'wpfaevent' ), 'primary', 'submit', false, empty( $settings['organizer_slug'] ) ? array( 'disabled' => 'disabled' ) : array() ); ?>
		</form>
	</div>

	<div class="card wpfaevent-info-card">
		<h2><?php esc_html_e( 'Where Imported Data Shows Up', 'wpfaevent' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Events are saved as Events posts with Eventyay source metadata for repeat imports.', 'wpfaevent' ); ?></li>
			<li><?php esc_html_e( 'Event title, description, dates, timezone, location, and Eventyay URL are updated from the Eventyay API.', 'wpfaevent' ); ?></li>
			<li><?php esc_html_e( 'Speaker, schedule, sponsor, and exhibitor imports are handled by the follow-up Eventyay data import PR.', 'wpfaevent' ); ?></li>
		</ul>
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
