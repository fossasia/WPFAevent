<?php
/**
 * Event dashboard admin view.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event              = isset( $dashboard_data['event'] ) && is_array( $dashboard_data['event'] ) ? $dashboard_data['event'] : array();
$stats              = isset( $dashboard_data['stats'] ) && is_array( $dashboard_data['stats'] ) ? $dashboard_data['stats'] : array();
$sections           = isset( $dashboard_data['sections'] ) && is_array( $dashboard_data['sections'] ) ? $dashboard_data['sections'] : array();
$import             = isset( $dashboard_data['import'] ) && is_array( $dashboard_data['import'] ) ? $dashboard_data['import'] : array();
$sync               = isset( $dashboard_data['sync'] ) && is_array( $dashboard_data['sync'] ) ? $dashboard_data['sync'] : array();
$settings           = isset( $dashboard_data['settings'] ) && is_array( $dashboard_data['settings'] ) ? $dashboard_data['settings'] : array();
$speakers           = isset( $dashboard_data['speakers'] ) && is_array( $dashboard_data['speakers'] ) ? $dashboard_data['speakers'] : array();
$sessions           = isset( $dashboard_data['sessions'] ) && is_array( $dashboard_data['sessions'] ) ? $dashboard_data['sessions'] : array();
$tracks             = isset( $dashboard_data['tracks'] ) && is_array( $dashboard_data['tracks'] ) ? $dashboard_data['tracks'] : array();
$assets             = isset( $dashboard_data['assets'] ) && is_array( $dashboard_data['assets'] ) ? $dashboard_data['assets'] : array();
$resources          = isset( $dashboard_data['resources'] ) && is_array( $dashboard_data['resources'] ) ? $dashboard_data['resources'] : array();
$section_visibility = isset( $sections['visibility'] ) && is_array( $sections['visibility'] ) ? $sections['visibility'] : array();
$about_excerpt      = isset( $sections['about_excerpt'] ) ? (string) $sections['about_excerpt'] : '';
$custom_tab_count   = isset( $sections['custom_tab_count'] ) ? absint( $sections['custom_tab_count'] ) : 0;
?>
<div class="wrap">
	<style>
		.wpfaevent-dashboard-shell { --wpfa-blue: #1683d9; --wpfa-blue-dark: #0d5ea8; --wpfa-slate: #5f6b7a; --wpfa-border: #d9e2ec; --wpfa-bg: #f4f8fb; --wpfa-card: #ffffff; }
		.wpfaevent-dashboard-shell { background: linear-gradient(180deg, #eff6fc 0%, #f9fbfd 240px); margin-left: -20px; padding: 24px 20px 28px; }
		.wpfaevent-dashboard-hero { background: linear-gradient(135deg, var(--wpfa-blue) 0%, #40a1f2 100%); border-radius: 16px; color: #fff; padding: 24px; box-shadow: 0 18px 40px rgba(22, 131, 217, 0.18); margin-bottom: 20px; }
		.wpfaevent-dashboard-hero p { color: rgba(255,255,255,0.88); max-width: 820px; }
		.wpfaevent-dashboard-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
		.wpfaevent-dashboard-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin:20px 0; }
		.wpfaevent-dashboard-card { background: var(--wpfa-card); border: 1px solid var(--wpfa-border); border-radius: 14px; padding: 18px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05); }
		.wpfaevent-dashboard-card h2, .wpfaevent-dashboard-card h3 { margin-top: 0; }
		.wpfaevent-kpi { font-size: 32px; line-height: 1; color: var(--wpfa-blue-dark); font-weight: 700; margin: 10px 0 8px; }
		.wpfaevent-dashboard-tabs { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 18px; padding:12px; background:#fff; border:1px solid var(--wpfa-border); border-radius:14px; box-shadow:0 10px 24px rgba(15, 23, 42, 0.04); position:sticky; top:32px; z-index:5; }
		.wpfaevent-dashboard-tab { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:8px 14px; border-radius:999px; background:#eef5fb; color:var(--wpfa-blue-dark); text-decoration:none; font-weight:600; font-size:13px; border:1px solid transparent; }
		.wpfaevent-dashboard-tab:hover, .wpfaevent-dashboard-tab:focus { background:#dcecfb; color:var(--wpfa-blue-dark); }
		.wpfaevent-dashboard-tab.is-muted { background:#f3f6f8; color:#66788a; }
		.wpfaevent-dashboard-section { scroll-margin-top: 96px; }
		.wpfaevent-dashboard-columns { display:grid; grid-template-columns: 1.2fr 1fr; gap:20px; align-items:start; margin-top:20px; }
		.wpfaevent-dashboard-split { display:grid; grid-template-columns: repeat(2, minmax(280px, 1fr)); gap:20px; margin-top:20px; }
		.wpfaevent-badge { display:inline-flex; align-items:center; gap:6px; background:#e8f4fe; color:var(--wpfa-blue-dark); border-radius:999px; padding:6px 12px; font-weight:600; font-size:12px; }
		.wpfaevent-badge.is-neutral { background:#eef2f5; color:#52606d; }
		.wpfaevent-tag-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
		.wpfaevent-tag { background:#eef4f8; border:1px solid #d7e3ee; border-radius:999px; padding:6px 10px; font-size:12px; }
		.wpfaevent-list { display:grid; gap:12px; margin-top:12px; }
		.wpfaevent-list-item { display:flex; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid #e4ebf3; border-radius:12px; background:#fbfdff; }
		.wpfaevent-list-item img { width:52px; height:52px; border-radius:50%; object-fit:cover; }
		.wpfaevent-list-copy { flex:1; }
		.wpfaevent-assets { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:16px; }
		.wpfaevent-asset img { width:100%; height:140px; object-fit:cover; border-radius:10px; border:1px solid #d9e2ec; background:#fff; }
		.wpfaevent-asset code { display:block; margin-top:8px; }
		.wpfaevent-sync-form { display:grid; gap:12px; margin-top:12px; }
		.wpfaevent-dashboard-shell .notice { margin: 0 0 20px; }
		.wpfaevent-dashboard-module-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; margin-top:20px; }
		.wpfaevent-module-card { min-height:158px; display:flex; flex-direction:column; justify-content:space-between; }
		.wpfaevent-module-card p { margin:0 0 12px; color:#5d6b78; }
		.wpfaevent-module-link { color:var(--wpfa-blue); font-weight:600; text-decoration:none; }
		.wpfaevent-dashboard-meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
		@media (max-width: 1200px) { .wpfaevent-dashboard-module-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
		@media (max-width: 1024px) { .wpfaevent-dashboard-columns, .wpfaevent-dashboard-split, .wpfaevent-dashboard-module-grid { grid-template-columns: 1fr; } .wpfaevent-dashboard-tabs { position:static; } }
	</style>
	<div class="wpfaevent-dashboard-shell">
		<?php if ( ! empty( $dashboard_notice['message'] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( ! empty( $dashboard_notice['type'] ) ? $dashboard_notice['type'] : 'info' ); ?> is-dismissible">
				<p><?php echo esc_html( $dashboard_notice['message'] ); ?></p>
			</div>
		<?php endif; ?>
	<h1>
		<?php
			printf(
				/* translators: %s: event title. */
				esc_html__( 'Event Dashboard: %s', 'wpfaevent' ),
				esc_html( isset( $event['title'] ) ? $event['title'] : '' )
			);
			?>
		</h1>
		<div class="wpfaevent-dashboard-hero">
			<div class="wpfaevent-dashboard-meta">
				<div class="wpfaevent-badge"><?php echo esc_html( ! empty( $import['source'] ) ? $import['source'] : __( 'Event overview', 'wpfaevent' ) ); ?></div>
				<div class="wpfaevent-badge <?php echo empty( $sync['can_sync'] ) ? 'is-neutral' : ''; ?>">
					<?php echo esc_html( ! empty( $sync['status'] ) ? $sync['status'] : __( 'Dashboard ready', 'wpfaevent' ) ); ?>
				</div>
			</div>
			<p>
				<?php
				echo esc_html(
					! empty( $sync['can_sync'] )
						? __( 'This event is linked to Eventyay and can be synchronized from its saved source while keeping the same dashboard surface used for all events.', 'wpfaevent' )
						: __( 'This event uses the same Eventyay-style dashboard layout even when it is being managed locally through WordPress only.', 'wpfaevent' )
				);
				?>
			</p>
			<div class="wpfaevent-dashboard-actions">
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpfa_event' ) ); ?>">
					<?php esc_html_e( 'Back to Events', 'wpfaevent' ); ?>
				</a>
				<?php if ( ! empty( $event['edit_url'] ) ) : ?>
					<a class="button" href="<?php echo esc_url( $event['edit_url'] ); ?>">
						<?php esc_html_e( 'Edit Event', 'wpfaevent' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $event['view_url'] ) ) : ?>
					<a class="button" href="<?php echo esc_url( $event['view_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Event Page', 'wpfaevent' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<nav class="wpfaevent-dashboard-tabs" aria-label="<?php esc_attr_e( 'Event dashboard sections', 'wpfaevent' ); ?>">
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-overview"><?php esc_html_e( 'Overview', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-modules"><?php esc_html_e( 'Modules', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-speakers"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-sessions"><?php esc_html_e( 'Sessions', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-tracks"><?php esc_html_e( 'Tracks', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-settings"><?php esc_html_e( 'Settings', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab" href="#wpfaevent-assets"><?php esc_html_e( 'Assets', 'wpfaevent' ); ?></a>
			<a class="wpfaevent-dashboard-tab <?php echo empty( $sync['can_sync'] ) ? 'is-muted' : ''; ?>" href="#wpfaevent-sync"><?php esc_html_e( 'Synchronization', 'wpfaevent' ); ?></a>
		</nav>

	<div class="wpfaevent-dashboard-grid">
		<?php foreach ( $stats as $stat ) : ?>
			<div class="wpfaevent-dashboard-card">
				<h2><?php echo esc_html( isset( $stat['label'] ) ? $stat['label'] : '' ); ?></h2>
				<p class="wpfaevent-kpi"><?php echo esc_html( (string) ( isset( $stat['value'] ) ? $stat['value'] : 0 ) ); ?></p>
				<p class="description"><?php echo esc_html( isset( $stat['help'] ) ? $stat['help'] : '' ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>

	<div id="wpfaevent-overview" class="wpfaevent-dashboard-columns wpfaevent-dashboard-section">
		<div class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'Event Overview', 'wpfaevent' ); ?></h2>
			<table class="widefat striped" style="margin-top:12px;">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( isset( $event['status'] ) ? (string) $event['status'] : '' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Created', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['created'] ) ? $event['created'] : __( 'Unknown', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Start date', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['start_date'] ) ? $event['start_date'] : __( 'Not set', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'End date', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['end_date'] ) ? $event['end_date'] : __( 'Not set', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Time', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['time'] ) ? $event['time'] : __( 'Not set', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Location', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['location'] ) ? $event['location'] : __( 'Not set', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tracks', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['tracks'] ) ? implode( ', ', $event['tracks'] ) : __( 'None', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tags', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['tags'] ) ? implode( ', ', $event['tags'] ) : __( 'None', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last modified', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $event['modified'] ) ? $event['modified'] : __( 'Unknown', 'wpfaevent' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'Import And Sync', 'wpfaevent' ); ?></h2>
			<p><strong><?php esc_html_e( 'Source', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $import['source'] ) ? $import['source'] : __( 'Unknown', 'wpfaevent' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Status', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $sync['status'] ) ? $sync['status'] : __( 'Unknown', 'wpfaevent' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Last imported', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $import['last_imported_at'] ) ? $import['last_imported_at'] : __( 'Never', 'wpfaevent' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Last synchronized', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $import['last_synced_at'] ) ? $import['last_synced_at'] : __( 'Never', 'wpfaevent' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Last speaker/session sync', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $import['last_program_at'] ) ? $import['last_program_at'] : __( 'Never', 'wpfaevent' ) ); ?></p>
			<?php if ( ! empty( $sync['can_sync'] ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpfaevent-sync-form">
					<input type="hidden" name="action" value="wpfaevent_sync_event_dashboard">
					<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event['id'] ); ?>">
					<?php wp_nonce_field( 'wpfaevent_sync_event_dashboard_' . absint( $event['id'] ), 'wpfaevent_sync_nonce' ); ?>
					<label>
						<input type="checkbox" name="overwrite_existing_logo" value="1">
						<?php esc_html_e( 'Overwrite existing logo', 'wpfaevent' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, a saved event logo in dashboard settings can be replaced with the Eventyay logo if one is available.', 'wpfaevent' ); ?></p>
					<?php submit_button( __( 'Synchronize Event', 'wpfaevent' ), 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'This event can be viewed here, but synchronization is only available for Eventyay-linked events and users with import permission.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div id="wpfaevent-modules" class="wpfaevent-dashboard-module-grid wpfaevent-dashboard-section">
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'Review attached speaker records, featured speakers, and imported speaker profile data for this event.', 'wpfaevent' ); ?></p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-speakers"><?php esc_html_e( 'Go to Speakers', 'wpfaevent' ); ?></a>
		</div>
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Sessions', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'Inspect imported schedule sessions, time slots, track assignments, and room information.', 'wpfaevent' ); ?></p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-sessions"><?php esc_html_e( 'Go to Sessions', 'wpfaevent' ); ?></a>
		</div>
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Tracks', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'See event taxonomy tracks and tracks inferred from imported session data in one place.', 'wpfaevent' ); ?></p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-tracks"><?php esc_html_e( 'Go to Tracks', 'wpfaevent' ); ?></a>
		</div>
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Event Settings', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'Check public URLs, registration settings, dashboard settings, and event-specific content visibility.', 'wpfaevent' ); ?></p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-settings"><?php esc_html_e( 'Go to Settings', 'wpfaevent' ); ?></a>
		</div>
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Import Source', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'View source metadata, Eventyay slugs and IDs, and stored dashboard file references for this event.', 'wpfaevent' ); ?></p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-source"><?php esc_html_e( 'Go to Source', 'wpfaevent' ); ?></a>
		</div>
		<div class="wpfaevent-dashboard-card wpfaevent-module-card">
			<div>
				<h2><?php esc_html_e( 'Synchronization', 'wpfaevent' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						! empty( $sync['can_sync'] )
							? __( 'Run an Eventyay sync and optionally replace the saved dashboard logo from the source event.', 'wpfaevent' )
							: __( 'Synchronization stays visible here even for manually edited events, but only Eventyay-linked events can run it.', 'wpfaevent' )
					);
					?>
				</p>
			</div>
			<a class="wpfaevent-module-link" href="#wpfaevent-sync"><?php esc_html_e( 'Go to Synchronization', 'wpfaevent' ); ?></a>
		</div>
	</div>

	<div id="wpfaevent-source" class="wpfaevent-dashboard-split wpfaevent-dashboard-section">
		<div class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'Import Source Details', 'wpfaevent' ); ?></h2>
			<table class="widefat striped" style="margin-top:12px;">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Source type', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $import['source'] ) ? $import['source'] : __( 'Unknown', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Eventyay organizer slug', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $import['organizer_slug'] ) ? $import['organizer_slug'] : __( 'Not linked', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Eventyay event slug', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $import['event_slug'] ) ? $import['event_slug'] : __( 'Not linked', 'wpfaevent' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Eventyay event ID', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( ! empty( $import['event_id'] ) ? $import['event_id'] : __( 'Not stored', 'wpfaevent' ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="wpfaevent-settings" class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'Event Settings', 'wpfaevent' ); ?></h2>
			<ul style="margin:12px 0 0 18px;list-style:disc;">
				<li>
					<?php if ( ! empty( $event['event_url'] ) ) : ?>
						<a href="<?php echo esc_url( $event['event_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Public event URL', 'wpfaevent' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Public event URL not set.', 'wpfaevent' ); ?>
					<?php endif; ?>
				</li>
				<li>
					<?php if ( ! empty( $event['register_url'] ) ) : ?>
						<a href="<?php echo esc_url( $event['register_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Registration link', 'wpfaevent' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Registration link not set.', 'wpfaevent' ); ?>
					<?php endif; ?>
				</li>
				<li>
					<?php if ( ! empty( $event['cfs_url'] ) ) : ?>
						<a href="<?php echo esc_url( $event['cfs_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Call for speakers link', 'wpfaevent' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Call for speakers link not set.', 'wpfaevent' ); ?>
					<?php endif; ?>
				</li>
				<li>
					<?php if ( ! empty( $settings['eventyay_api_url'] ) ) : ?>
						<a href="<?php echo esc_url( $settings['eventyay_api_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Saved Eventyay API URL', 'wpfaevent' ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Eventyay API URL not saved.', 'wpfaevent' ); ?>
					<?php endif; ?>
				</li>
				<li>
					<?php
					echo esc_html(
						! empty( $settings['reg_button_text'] )
							? sprintf(
								/* translators: %s: registration button label. */
								__( 'Registration button text: %s', 'wpfaevent' ),
								$settings['reg_button_text']
							)
							: __( 'Registration button text not set.', 'wpfaevent' )
					);
					?>
				</li>
			</ul>
		</div>
	</div>

	<div class="wpfaevent-dashboard-split">
		<div class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'Content Visibility', 'wpfaevent' ); ?></h2>
			<table class="widefat striped" style="margin-top:12px;">
				<tbody>
					<?php foreach ( $section_visibility as $section_key => $is_visible ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $section_key ) ) ); ?></th>
							<td><?php echo esc_html( $is_visible ? __( 'Visible', 'wpfaevent' ) : __( 'Hidden', 'wpfaevent' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom tabs', 'wpfaevent' ); ?></th>
						<td><?php echo esc_html( (string) $custom_tab_count ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="wpfaevent-dashboard-card">
			<h2><?php esc_html_e( 'About Section Preview', 'wpfaevent' ); ?></h2>
			<?php if ( '' !== $about_excerpt ) : ?>
				<p><?php echo esc_html( wp_trim_words( $about_excerpt, 40, '...' ) ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No about section content has been saved yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['reg_button_text'] ) || ! empty( $settings['reg_button_link'] ) ) : ?>
				<hr>
				<p><strong><?php esc_html_e( 'CTA button', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $settings['reg_button_text'] ) ? $settings['reg_button_text'] : __( 'Not set', 'wpfaevent' ) ); ?></p>
				<p><strong><?php esc_html_e( 'CTA link', 'wpfaevent' ); ?>:</strong> <?php echo esc_html( ! empty( $settings['reg_button_link'] ) ? $settings['reg_button_link'] : __( 'Not set', 'wpfaevent' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="wpfaevent-dashboard-split">
		<div id="wpfaevent-speakers" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
			<h2><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></h2>
			<?php if ( ! empty( $speakers ) ) : ?>
				<div class="wpfaevent-list">
					<?php foreach ( $speakers as $speaker ) : ?>
						<div class="wpfaevent-list-item">
							<?php if ( ! empty( $speaker['image'] ) ) : ?>
								<img src="<?php echo esc_url( $speaker['image'] ); ?>" alt="<?php echo esc_attr( $speaker['name'] ); ?>">
							<?php endif; ?>
							<div class="wpfaevent-list-copy">
								<strong><?php echo esc_html( $speaker['name'] ); ?></strong>
								<div class="description"><?php echo esc_html( trim( $speaker['title'] . ( $speaker['organization'] ? ' - ' . $speaker['organization'] : '' ) ) ); ?></div>
							</div>
							<?php if ( ! empty( $speaker['featured'] ) ) : ?>
								<span class="wpfaevent-badge"><?php esc_html_e( 'Featured', 'wpfaevent' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No dashboard speakers were found for this event yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>

		<div id="wpfaevent-sessions" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
			<h2><?php esc_html_e( 'Sessions', 'wpfaevent' ); ?></h2>
			<?php if ( ! empty( $sessions ) ) : ?>
				<div class="wpfaevent-list">
					<?php foreach ( $sessions as $session ) : ?>
						<div class="wpfaevent-list-item">
							<div class="wpfaevent-list-copy">
								<strong><?php echo esc_html( $session['title'] ); ?></strong>
								<div class="description"><?php echo esc_html( trim( $session['date'] . ' ' . $session['time'] ) ); ?></div>
								<?php if ( ! empty( $session['speakers'] ) ) : ?>
									<div class="description"><?php echo esc_html( $session['speakers'] ); ?></div>
								<?php endif; ?>
							</div>
							<div class="description"><?php echo esc_html( ! empty( $session['track'] ) ? $session['track'] : __( 'No track', 'wpfaevent' ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No imported sessions were found for this event yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="wpfaevent-dashboard-split">
		<div id="wpfaevent-tracks" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
			<h2><?php esc_html_e( 'Tracks', 'wpfaevent' ); ?></h2>
			<?php if ( ! empty( $tracks ) ) : ?>
				<div class="wpfaevent-tag-list">
					<?php foreach ( $tracks as $track ) : ?>
						<span class="wpfaevent-tag"><?php echo esc_html( $track ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No tracks are attached to this event yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>

		<div id="wpfaevent-assets" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
			<h2><?php esc_html_e( 'Imported Media Assets', 'wpfaevent' ); ?></h2>
			<?php if ( ! empty( $assets ) ) : ?>
				<div class="wpfaevent-assets">
					<?php foreach ( $assets as $asset ) : ?>
						<div class="wpfaevent-asset">
							<img src="<?php echo esc_url( $asset['url'] ); ?>" alt="<?php echo esc_attr( $asset['label'] ); ?>">
							<strong><?php echo esc_html( $asset['label'] ); ?></strong>
							<div class="description"><?php echo esc_html( ! empty( $asset['source'] ) ? $asset['source'] : __( 'Saved asset', 'wpfaevent' ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No imported or configured media assets are available for this event yet.', 'wpfaevent' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div id="wpfaevent-sync" class="wpfaevent-dashboard-card wpfaevent-dashboard-section" style="margin-top:20px;max-width:none;">
		<h2><?php esc_html_e( 'Synchronization Status', 'wpfaevent' ); ?></h2>
		<p><?php echo esc_html( ! empty( $sync['status'] ) ? $sync['status'] : __( 'Unknown', 'wpfaevent' ) ); ?></p>
		<p class="description">
			<?php
			echo esc_html(
				! empty( $sync['can_sync'] )
					? __( 'This event is connected to Eventyay and can be synchronized from the source information stored on the event.', 'wpfaevent' )
					: __( 'This event still gets the same Eventyay-style dashboard modules even if it is edited locally and cannot be synchronized from Eventyay.', 'wpfaevent' )
			);
			?>
		</p>
	</div>

	<div class="wpfaevent-dashboard-card" style="margin-top:20px;max-width:none;">
		<h2><?php esc_html_e( 'Dashboard Data Files', 'wpfaevent' ); ?></h2>
		<table class="widefat striped" style="margin-top:12px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Resource', 'wpfaevent' ); ?></th>
					<th><?php esc_html_e( 'File', 'wpfaevent' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpfaevent' ); ?></th>
					<th><?php esc_html_e( 'Summary', 'wpfaevent' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $resources as $resource ) : ?>
					<tr>
						<td><?php echo esc_html( isset( $resource['label'] ) ? $resource['label'] : '' ); ?></td>
						<td><code><?php echo esc_html( isset( $resource['file'] ) ? $resource['file'] : '' ); ?></code></td>
						<td><?php echo esc_html( ! empty( $resource['present'] ) ? __( 'Available', 'wpfaevent' ) : __( 'Not found', 'wpfaevent' ) ); ?></td>
						<td><?php echo esc_html( isset( $resource['summary'] ) ? $resource['summary'] : '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
</div>
