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
	'base_url'           => 'https://eventyay.com',
	'organizer_slug'     => '',
	'event_slug'         => '',
	'api_token'          => '',
	'post_status'        => 'draft',
	'auto_sync_enabled'  => false,
	'auto_sync_interval' => 'daily',
);
$auto_sync_next   = class_exists( 'Wpfaevent_Cron_Scheduler' ) ? Wpfaevent_Cron_Scheduler::get_next_scheduled() : false;
$auto_sync_result = class_exists( 'Wpfaevent_Cron_Scheduler' ) ? Wpfaevent_Cron_Scheduler::get_last_result() : null;
$endpoint_preview = isset( $endpoint_preview ) ? $endpoint_preview : '';
$notice           = isset( $notice ) ? $notice : false;
$event_url        = '';

if ( ! empty( $settings['base_url'] ) && ! empty( $settings['organizer_slug'] ) && ! empty( $settings['event_slug'] ) ) {
	$event_url = trailingslashit( untrailingslashit( $settings['base_url'] ) ) . rawurlencode( $settings['organizer_slug'] ) . '/' . rawurlencode( $settings['event_slug'] ) . '/';
}
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
					<th scope="row"><label for="wpfaevent_eventyay_event_url"><?php esc_html_e( 'Event URL', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" id="wpfaevent_eventyay_event_url" name="wpfaevent_eventyay_import_settings[event_url]" value="<?php echo esc_attr( $event_url ); ?>" placeholder="https://eventyay.com/bigevents/sampleconf/">
						<p class="description"><?php esc_html_e( 'Imports and updates now run one event at a time. Paste the full public Eventyay event URL here.', 'wpfaevent' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_eventyay_event_slug"><?php esc_html_e( 'Event slug', 'wpfaevent' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wpfaevent_eventyay_event_slug" name="wpfaevent_eventyay_import_settings[event_slug]" value="<?php echo esc_attr( $settings['event_slug'] ); ?>" placeholder="sampleconf">
						<p class="description"><?php esc_html_e( 'This is filled from the Event URL above and is required for single-event imports.', 'wpfaevent' ); ?></p>
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
				<tr>
					<th scope="row"><?php esc_html_e( 'Scheduled auto-sync', 'wpfaevent' ); ?></th>
					<td>
						<label for="wpfaevent_auto_sync_enabled">
							<input type="checkbox" id="wpfaevent_auto_sync_enabled" name="wpfaevent_eventyay_import_settings[auto_sync_enabled]" value="1" <?php checked( ! empty( $settings['auto_sync_enabled'] ) ); ?>>
							<?php esc_html_e( 'Automatically re-import events and speakers on a recurring schedule', 'wpfaevent' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpfaevent_auto_sync_interval"><?php esc_html_e( 'Sync interval', 'wpfaevent' ); ?></label></th>
					<td>
						<select id="wpfaevent_auto_sync_interval" name="wpfaevent_eventyay_import_settings[auto_sync_interval]">
							<option value="hourly" <?php selected( $settings['auto_sync_interval'] ?? 'daily', 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'wpfaevent' ); ?></option>
							<option value="twicedaily" <?php selected( $settings['auto_sync_interval'] ?? 'daily', 'twicedaily' ); ?>><?php esc_html_e( 'Twice daily', 'wpfaevent' ); ?></option>
							<option value="daily" <?php selected( $settings['auto_sync_interval'] ?? 'daily', 'daily' ); ?>><?php esc_html_e( 'Daily', 'wpfaevent' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How often WP-Cron will run the import. Only applies when auto-sync is enabled.', 'wpfaevent' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Eventyay Settings', 'wpfaevent' ) ); ?>
		</form>

		<hr>

		<h3><?php esc_html_e( 'Import Event', 'wpfaevent' ); ?></h3>
		<?php if ( is_wp_error( $endpoint_preview ) ) : ?>
			<p><?php echo esc_html( $endpoint_preview->get_error_message() ); ?></p>
		<?php elseif ( $endpoint_preview ) : ?>
			<p>
				<?php esc_html_e( 'Current endpoint:', 'wpfaevent' ); ?>
				<code><?php echo esc_html( $endpoint_preview ); ?></code>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'Save an Eventyay event URL before importing.', 'wpfaevent' ); ?></p>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'Use this to import the configured Eventyay event. Use the Update Event menu item when that event changes after the initial import.', 'wpfaevent' ); ?>
		</p>

		<form id="wpfaevent-import-events-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wpfaevent_import_eventyay_events">
			<input type="hidden" name="wpfaevent_eventyay_return_page" value="wpfaevent-import-events">
			<?php wp_nonce_field( 'wpfaevent_import_eventyay_events' ); ?>
			<?php submit_button( __( 'Import Event from Eventyay', 'wpfaevent' ), 'primary', 'submit', false, ( empty( $settings['organizer_slug'] ) || empty( $settings['event_slug'] ) ) ? array( 'disabled' => 'disabled' ) : array() ); ?>
		</form>
	</div>

	<div class="card wpfaevent-info-card">
		<h2><?php esc_html_e( 'Auto-Sync Status', 'wpfaevent' ); ?></h2>
		<?php if ( ! empty( $settings['auto_sync_enabled'] ) ) : ?>
			<?php if ( $auto_sync_next ) : ?>
				<p>
					<span class="dashicons dashicons-update" style="color:#00a32a;vertical-align:middle;"></span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: human-readable time until next sync */
							__( 'Next sync in %s.', 'wpfaevent' ),
							human_time_diff( time(), $auto_sync_next )
						)
					);
					?>
					<span class="description"> &mdash; <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $auto_sync_next ) ); ?></span>
				</p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Auto-sync is enabled but not yet scheduled. Save settings to apply.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Auto-sync is disabled. Enable it above and save to activate.', 'wpfaevent' ); ?></p>
		<?php endif; ?>

		<?php if ( $auto_sync_result ) : ?>
			<p style="margin-top:12px;">
				<strong><?php esc_html_e( 'Last auto-sync:', 'wpfaevent' ); ?></strong>
				<span style="color:<?php echo 'error' === $auto_sync_result['type'] ? '#d63638' : '#00a32a'; ?>;">
					<?php echo esc_html( $auto_sync_result['message'] ); ?>
				</span>
				<span class="description">&mdash; <?php echo esc_html( human_time_diff( $auto_sync_result['time'] ) ); ?> <?php esc_html_e( 'ago', 'wpfaevent' ); ?></span>
			</p>
		<?php else : ?>
			<p class="description" style="margin-top:12px;"><?php esc_html_e( 'No auto-sync has run yet.', 'wpfaevent' ); ?></p>
		<?php endif; ?>
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
