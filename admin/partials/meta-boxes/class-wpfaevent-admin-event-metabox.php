<?php
/**
 * Event CPT meta box rendering and save handling.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials/meta-boxes
 */

/**
 * Registers, renders, and saves the meta boxes on the Event edit screen.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials/meta-boxes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Admin_Event_Metabox {

	/**
	 * Temporary event schedule sessions storage.
	 *
	 * @since 1.0.0
	 * @var array<array<string>> $schedule_sessions
	 */
	private $schedule_sessions = array();

	/**
	 * Register meta boxes for the Event CPT.
	 *
	 * @since 1.0.0
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'wpfa_event_details',
			__( 'Event Details', 'wpfaevent' ),
			array( $this, 'render_event_meta_box' ),
			'wpfa_event',
			'normal',
			'high'
		);

		// Eventyay sync meta box on event edit screen (visible to importers only).
		if ( Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			add_meta_box(
				'wpfa_eventyay_sync',
				__( 'Eventyay Speaker Sync', 'wpfaevent' ),
				array( $this, 'render_eventyay_sync_meta_box' ),
				'wpfa_event',
				'side',
				'default'
			);
		}

		// Event schedule meta box.
		add_meta_box(
			'wpfa_event_schedule_box',
			__( 'Event Schedule Sessions', 'wpfaevent' ),
			array( $this, 'render_event_schedule_meta_box' ),
			'wpfa_event',
			'normal',
			'default'
		);

		// Event sponsors meta box.
		add_meta_box(
			'wpfa_event_sponsors_box',
			__( 'Event Sponsors', 'wpfaevent' ),
			array( $this, 'render_event_sponsors_meta_box' ),
			'wpfa_event',
			'normal',
			'default'
		);

		// Event exhibitors meta box.
		add_meta_box(
			'wpfa_event_exhibitors_box',
			__( 'Event Exhibitors', 'wpfaevent' ),
			array( $this, 'render_event_exhibitors_meta_box' ),
			'wpfa_event',
			'normal',
			'default'
		);

		// Remove the default Custom Fields meta box to avoid UI clutter.
		// since we have enabled 'custom-fields' support for REST API visibility.
		remove_meta_box( 'postcustom', 'wpfa_event', 'normal' );
	}

	/**
	 * Render Event meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_event_meta_box( $post ) {
		wp_nonce_field( 'wpfa_event_meta_nonce', 'wpfa_event_meta_nonce' );

		$start_date = get_post_meta( $post->ID, 'wpfa_event_start_date', true );
		$end_date   = get_post_meta( $post->ID, 'wpfa_event_end_date', true );
		$start_time = get_post_meta( $post->ID, 'wpfa_event_start_time', true );
		$end_time   = get_post_meta( $post->ID, 'wpfa_event_end_time', true );

		if ( '' === $start_time ) {
			$start_time = get_post_meta( $post->ID, 'wpfa_event_time', true );
		}

		$timezone   = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_timezone( $post->ID ) : wp_timezone_string();
		$all_day    = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_all_day( $post->ID ) : false;
		$location   = get_post_meta( $post->ID, 'wpfa_event_location', true );
		$url        = get_post_meta( $post->ID, 'wpfa_event_url', true );
		$header_url = get_post_meta( $post->ID, 'wpfa_event_header_image_url', true );
		$logo_url   = get_post_meta( $post->ID, 'wpfa_event_logo_url', true );
		$widget_url = get_post_meta( $post->ID, 'wpfa_event_ticket_widget_url', true );
		$lead_text  = get_post_meta( $post->ID, 'wpfa_event_lead_text', true );
		$reg_link   = get_post_meta( $post->ID, 'wpfa_event_registration_link', true );
		$cfs_link   = get_post_meta( $post->ID, 'wpfa_event_cfs_link', true );
		$speakers   = $this->get_event_speaker_ids( $post->ID );

		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_event_start_date"><?php esc_html_e( 'Start Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_start_date" name="wpfa_event_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_date"><?php esc_html_e( 'End Date', 'wpfaevent' ); ?></label></th>
				<td><input type="date" id="wpfa_event_end_date" name="wpfa_event_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_timezone"><?php esc_html_e( 'Timezone', 'wpfaevent' ); ?></label></th>
				<td>
					<select id="wpfa_event_timezone" name="wpfa_event_timezone" class="regular-text">
						<?php echo wp_timezone_choice( $timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes timezone option markup. ?>
					</select>
					<p class="description"><?php esc_html_e( 'Used to interpret timed events and calendar exports. Leave as the site timezone when the event does not need a separate timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Time Format', 'wpfaevent' ); ?></th>
				<td>
					<label for="wpfa_event_all_day">
						<input type="checkbox" id="wpfa_event_all_day" name="wpfa_event_all_day" value="1" <?php checked( $all_day ); ?>>
						<?php esc_html_e( 'All-day event', 'wpfaevent' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'All-day events export as date-only calendar entries. Timed events use the event timezone.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_start_time"><?php esc_html_e( 'Start Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_start_time" name="wpfa_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_end_time"><?php esc_html_e( 'End Time', 'wpfaevent' ); ?></label></th>
				<td><input type="time" id="wpfa_event_end_time" name="wpfa_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_location"><?php esc_html_e( 'Location', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_location" name="wpfa_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_lead_text"><?php esc_html_e( 'Lead Text', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_event_lead_text" name="wpfa_event_lead_text" value="<?php echo esc_attr( $lead_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Short description for hero section', 'wpfaevent' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_url"><?php esc_html_e( 'Event URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_url" name="wpfa_event_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_header_image_url"><?php esc_html_e( 'Header Image URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_header_image_url" name="wpfa_event_header_image_url" value="<?php echo esc_attr( $header_url ); ?>" class="regular-text" placeholder="https://">
					<p class="description"><?php esc_html_e( 'Imported from Eventyay header, banner, hero, or cover image fields when available.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_logo_url"><?php esc_html_e( 'Event Logo URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_logo_url" name="wpfa_event_logo_url" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" placeholder="https://">
					<p class="description"><?php esc_html_e( 'Imported from Eventyay logo or shop banner image settings when available.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_ticket_widget_url"><?php esc_html_e( 'Ticket Widget URL', 'wpfaevent' ); ?></label></th>
				<td>
					<input type="url" id="wpfa_event_ticket_widget_url" name="wpfa_event_ticket_widget_url" value="<?php echo esc_attr( $widget_url ); ?>" class="regular-text" placeholder="https://eventyay.com/organizer/event/">
					<p class="description"><?php esc_html_e( 'Used to embed the Eventyay ticket purchasing widget on the event page.', 'wpfaevent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_event_registration_link"><?php esc_html_e( 'Registration Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_registration_link" name="wpfa_event_registration_link" value="<?php echo esc_attr( $reg_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/..."></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_cfs_link"><?php esc_html_e( 'Call for Speakers Link', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_event_cfs_link" name="wpfa_event_cfs_link" value="<?php echo esc_attr( $cfs_link ); ?>" class="regular-text" placeholder="https://eventyay.com/e/.../cfs"></td>
			</tr>
			<tr>
				<th><label for="wpfa_event_speakers"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					$speaker_ids = get_posts(
						array(
							'post_type'      => 'wpfa_speaker',
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					if ( $speaker_ids ) :
						?>
						<select name="wpfa_event_speakers[]" id="wpfa_event_speakers" multiple class="wpfaevent-relationship-select wpfaevent-speakers-select">
							<?php foreach ( $speaker_ids as $speaker_id ) : ?>
								<?php $is_selected = is_array( $speakers ) && in_array( $speaker_id, $speakers, true ); ?>
										<option value="<?php echo esc_attr( sprintf( '%d', absint( $speaker_id ) ) ); ?>"
										<?php selected( $is_selected, true ); ?>>
									<?php echo esc_html( get_the_title( $speaker_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple speakers.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No speakers found. Create speakers first.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Eventyay speaker sync meta box on the event edit screen.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_eventyay_sync_meta_box( $post ) {
		$eventyay_id = get_post_meta( $post->ID, '_eventyay_event_slug', true );
		$synced_at   = get_post_meta( $post->ID, '_wpfa_eventyay_speakers_synced_at', true );
		?>
		<p class="description" style="margin-bottom:10px;">
			<?php esc_html_e( 'Re-sync speakers and sessions for this event from the Eventyay API.', 'wpfaevent' ); ?>
		</p>
		<?php if ( $eventyay_id ) : ?>
			<p style="margin-bottom:10px;">
				<strong><?php esc_html_e( 'Eventyay slug:', 'wpfaevent' ); ?></strong>
				<?php echo esc_html( $eventyay_id ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $synced_at ) : ?>
			<p class="description" style="margin-bottom:10px;">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: human-readable time difference */
						__( 'Last synced %s ago.', 'wpfaevent' ),
						human_time_diff( absint( $synced_at ) )
					)
				);
				?>
			</p>
		<?php endif; ?>
			<button type="button" id="wpfa-eventyay-sync-btn" class="button button-secondary" data-event-id="<?php echo esc_attr( sprintf( '%d', absint( $post->ID ) ) ); ?>" style="width:100%;">
			<?php esc_html_e( 'Sync Speakers from Eventyay', 'wpfaevent' ); ?>
		</button>
		<p id="wpfa-eventyay-sync-status" style="margin-top:8px;font-weight:bold;display:none;"></p>
		<script>
		(function() {
			var btn = document.getElementById('wpfa-eventyay-sync-btn');
			var status = document.getElementById('wpfa-eventyay-sync-status');
			if (!btn) return;
			btn.addEventListener('click', function() {
				btn.disabled = true;
				btn.textContent = <?php echo wp_json_encode( __( 'Syncing…', 'wpfaevent' ) ); ?>;
				status.style.display = 'none';
				var data = new FormData();
				data.append('action', 'fossasia_sync_eventyay');
				data.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'fossasia_admin_nonce' ) ); ?>);
				data.append('event_id', btn.dataset.eventId);
				fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: data })
					.then(function(r) { return r.json(); })
					.then(function(r) {
						status.style.display = 'block';
						if (r.success) {
							status.style.color = '#00a32a';
							var d = r.data || {};
							status.textContent = d.message || <?php echo wp_json_encode( __( 'Sync complete.', 'wpfaevent' ) ); ?>;
							<?php if ( $synced_at ) : ?>
							document.querySelector('.description[style*="Last synced"]') && (document.querySelector('.description[style*="Last synced"]').textContent = <?php echo wp_json_encode( __( 'Last synced just now.', 'wpfaevent' ) ); ?>);
							<?php endif; ?>
						} else {
							status.style.color = '#d63638';
							status.textContent = (r.data && r.data.message) || <?php echo wp_json_encode( __( 'Sync failed.', 'wpfaevent' ) ); ?>;
						}
					})
					.catch(function() {
						status.style.display = 'block';
						status.style.color = '#d63638';
						status.textContent = <?php echo wp_json_encode( __( 'Network error.', 'wpfaevent' ) ); ?>;
					})
					.finally(function() {
						btn.disabled = false;
						btn.textContent = <?php echo wp_json_encode( __( 'Sync Speakers from Eventyay', 'wpfaevent' ) ); ?>;
					});
			});
		}());
		</script>
		<?php
	}

	/**
	 * Render Event schedule sessions meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_event_schedule_meta_box( $post ) {
		$sessions = array();
		if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store          = new Wpfaevent_Eventyay_Dashboard_Store();
			$schedule_table = $store->read_dashboard_json_file( 'schedule-' . $post->ID . '.json', array() );
			$schedule_rows  = isset( $schedule_table['data'] ) && is_array( $schedule_table['data'] ) ? $schedule_table['data'] : array();
			$schedule_meta  = isset( $schedule_table['sessions'] ) && is_array( $schedule_table['sessions'] ) ? $schedule_table['sessions'] : array();
			$schedule_body  = ! empty( $schedule_rows ) && is_array( $schedule_rows[0] ) ? array_slice( $schedule_rows, 1 ) : array();

			foreach ( $schedule_body as $row_index => $row ) {
				$row_meta = isset( $schedule_meta[ $row_index + 1 ] ) && is_array( $schedule_meta[ $row_index + 1 ] ) ? $schedule_meta[ $row_index + 1 ] : array();

				$starts_at = isset( $row_meta['starts_at'] ) ? $row_meta['starts_at'] : '';
				$ends_at   = isset( $row_meta['ends_at'] ) ? $row_meta['ends_at'] : '';

				$date_val       = '';
				$start_time_val = '';
				$end_time_val   = '';

				if ( $starts_at ) {
					$tz = get_post_meta( $post->ID, 'wpfa_event_timezone', true );
					if ( ! $tz ) {
						$tz = wp_timezone_string();
					}
					try {
						$dt             = new DateTimeImmutable( $starts_at );
						$dt             = $dt->setTimezone( new DateTimeZone( $tz ) );
						$date_val       = $dt->format( 'Y-m-d' );
						$start_time_val = $dt->format( 'H:i' );
					} catch ( Exception $e ) {
						unset( $e );
					}
				}

				if ( $ends_at ) {
					$tz = get_post_meta( $post->ID, 'wpfa_event_timezone', true );
					if ( ! $tz ) {
						$tz = wp_timezone_string();
					}
					try {
						$dt           = new DateTimeImmutable( $ends_at );
						$dt           = $dt->setTimezone( new DateTimeZone( $tz ) );
						$end_time_val = $dt->format( 'H:i' );
					} catch ( Exception $e ) {
						unset( $e );
					}
				}

				if ( ! $date_val && isset( $row[0] ) ) {
					$date_val = sanitize_text_field( $row[0] );
				}

				$sessions[] = array(
					'date'       => $date_val,
					'start_time' => $start_time_val,
					'end_time'   => $end_time_val,
					'title'      => isset( $row[2] ) ? sanitize_text_field( $row[2] ) : '',
					'speakers'   => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
					'track'      => isset( $row[4] ) ? sanitize_text_field( $row[4] ) : '',
					'room'       => isset( $row[5] ) ? sanitize_text_field( $row[5] ) : '',
				);
			}
		}

		$this->schedule_sessions = $sessions;
		?>
		<table class="wp-list-table widefat fixed striped" id="wpfaevent-schedule-sessions-table">
			<thead>
				<tr>
					<th class="wpfa-col-date"><?php esc_html_e( 'Date', 'wpfaevent' ); ?></th>
					<th class="wpfa-col-start-time"><?php esc_html_e( 'Start Time', 'wpfaevent' ); ?></th>
					<th class="wpfa-col-end-time"><?php esc_html_e( 'End Time', 'wpfaevent' ); ?></th>
					<th><?php esc_html_e( 'Session Title', 'wpfaevent' ); ?></th>
					<th><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></th>
					<th class="wpfa-col-track"><?php esc_html_e( 'Track', 'wpfaevent' ); ?></th>
					<th class="wpfa-col-room"><?php esc_html_e( 'Room', 'wpfaevent' ); ?></th>
					<th class="wpfa-col-action"><?php esc_html_e( 'Action', 'wpfaevent' ); ?></th>
				</tr>
			</thead>
			<tbody id="wpfaevent-schedule-sessions-body">
				<?php
				$sessions = $this->schedule_sessions;
				if ( ! empty( $sessions ) ) :
					foreach ( $sessions as $index => $sess ) :
						?>
						<tr>
							<td><input type="date" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][date]" value="<?php echo esc_attr( $sess['date'] ); ?>" required></td>
							<td><input type="time" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][start_time]" value="<?php echo esc_attr( $sess['start_time'] ); ?>" required></td>
							<td><input type="time" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][end_time]" value="<?php echo esc_attr( $sess['end_time'] ); ?>"></td>
							<td><input type="text" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][title]" value="<?php echo esc_attr( $sess['title'] ); ?>" required></td>
							<td><input type="text" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][speakers]" value="<?php echo esc_attr( $sess['speakers'] ); ?>"></td>
							<td><input type="text" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][track]" value="<?php echo esc_attr( $sess['track'] ); ?>"></td>
							<td><input type="text" name="wpfa_schedule_sessions[<?php echo absint( $index ); ?>][room]" value="<?php echo esc_attr( $sess['room'] ); ?>"></td>
							<td class="wpfa-col-action"><a href="#" class="wpfaevent-remove-session"><?php esc_html_e( 'Remove', 'wpfaevent' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<button type="button" class="button button-primary" id="wpfaevent-add-session-row">
			<?php esc_html_e( 'Add Session', 'wpfaevent' ); ?>
		</button>

		<script>
			jQuery(document).ready(function($) {
				let sessionIndex = $('#wpfaevent-schedule-sessions-body tr').length;

				$('#wpfaevent-add-session-row').on('click', function(e) {
					e.preventDefault();
					const html = `<tr>
						<td><input type="date" name="wpfa_schedule_sessions[\${sessionIndex}][date]" required></td>
						<td><input type="time" name="wpfa_schedule_sessions[\${sessionIndex}][start_time]" required></td>
						<td><input type="time" name="wpfa_schedule_sessions[\${sessionIndex}][end_time]"></td>
						<td><input type="text" name="wpfa_schedule_sessions[\${sessionIndex}][title]" required></td>
						<td><input type="text" name="wpfa_schedule_sessions[\${sessionIndex}][speakers]"></td>
						<td><input type="text" name="wpfa_schedule_sessions[\${sessionIndex}][track]"></td>
						<td><input type="text" name="wpfa_schedule_sessions[\${sessionIndex}][room]"></td>
						<td class="wpfa-col-action"><a href="#" class="wpfaevent-remove-session">Remove</a></td>
					</tr>`;
					$('#wpfaevent-schedule-sessions-body').append(html);
					sessionIndex++;
				});

				$('#wpfaevent-schedule-sessions-body').on('click', '.wpfaevent-remove-session', function(e) {
					e.preventDefault();
					$(this).closest('tr').remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Render Event Sponsors meta box.
	 *
	 * @since 1.0.1
	 * @param WP_Post $post The post object.
	 */
	public function render_event_sponsors_meta_box( $post ) {
		$sponsors = array();
		if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store          = new Wpfaevent_Eventyay_Dashboard_Store();
			$sponsor_groups = $store->read_dashboard_json_file( 'sponsors-' . $post->ID . '.json', array() );
			if ( is_array( $sponsor_groups ) ) {
				foreach ( $sponsor_groups as $group ) {
					$group_name     = isset( $group['group_name'] ) ? $group['group_name'] : '';
					$logo_size      = isset( $group['logo_size'] ) ? $group['logo_size'] : 160;
					$group_sponsors = isset( $group['sponsors'] ) && is_array( $group['sponsors'] ) ? $group['sponsors'] : array();
					foreach ( $group_sponsors as $sp ) {
						$sponsors[] = array(
							'group_name'  => $group_name,
							'logo_size'   => $logo_size,
							'name'        => isset( $sp['name'] ) ? $sp['name'] : '',
							'image'       => isset( $sp['image'] ) ? $sp['image'] : '',
							'link'        => isset( $sp['link'] ) ? $sp['link'] : '',
							'description' => isset( $sp['description'] ) ? $sp['description'] : '',
							'level'       => isset( $sp['level'] ) ? $sp['level'] : 0,
						);
					}
				}
			}
		}

		?>
		<div class="wpfaevent-meta-cards-container" id="wpfaevent-sponsors-container">
			<?php
			if ( ! empty( $sponsors ) ) :
				foreach ( $sponsors as $index => $sp ) :
					?>
					<div class="wpfaevent-meta-card">
						<a href="#" class="wpfaevent-remove-sponsor wpfaevent-remove-card-btn"><?php esc_html_e( 'Remove', 'wpfaevent' ); ?></a>
						<div class="wpfaevent-meta-card-grid">
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Tier/Group Name', 'wpfaevent' ); ?></label>
								<input type="text" name="wpfa_sponsors[<?php echo absint( $index ); ?>][group_name]" value="<?php echo esc_attr( $sp['group_name'] ); ?>" required placeholder="e.g. Gold Sponsors">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Logo Size', 'wpfaevent' ); ?></label>
								<input type="number" name="wpfa_sponsors[<?php echo absint( $index ); ?>][logo_size]" value="<?php echo esc_attr( $sp['logo_size'] ); ?>" required min="50" max="500">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Sponsor Name', 'wpfaevent' ); ?></label>
								<input type="text" name="wpfa_sponsors[<?php echo absint( $index ); ?>][name]" value="<?php echo esc_attr( $sp['name'] ); ?>" required>
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Logo URL', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_sponsors[<?php echo absint( $index ); ?>][image]" value="<?php echo esc_attr( $sp['image'] ); ?>" required placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Website URL', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_sponsors[<?php echo absint( $index ); ?>][link]" value="<?php echo esc_attr( $sp['link'] ); ?>" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Sort Level', 'wpfaevent' ); ?></label>
								<input type="number" name="wpfa_sponsors[<?php echo absint( $index ); ?>][level]" value="<?php echo esc_attr( $sp['level'] ); ?>" min="0">
							</div>
							<div class="wpfaevent-meta-card-field span-2">
								<label><?php esc_html_e( 'Description', 'wpfaevent' ); ?></label>
								<input type="text" name="wpfa_sponsors[<?php echo absint( $index ); ?>][description]" value="<?php echo esc_attr( $sp['description'] ); ?>">
							</div>
						</div>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>

		<button type="button" class="button button-primary" id="wpfaevent-add-sponsor-row">
			<?php esc_html_e( 'Add Sponsor', 'wpfaevent' ); ?>
		</button>

		<script>
			jQuery(document).ready(function($) {
				let sponsorIndex = $('#wpfaevent-sponsors-container .wpfaevent-meta-card').length;

				$('#wpfaevent-add-sponsor-row').on('click', function(e) {
					e.preventDefault();
					const html = `<div class="wpfaevent-meta-card">
						<a href="#" class="wpfaevent-remove-sponsor wpfaevent-remove-card-btn">Remove</a>
						<div class="wpfaevent-meta-card-grid">
							<div class="wpfaevent-meta-card-field">
								<label>Tier/Group Name</label>
								<input type="text" name="wpfa_sponsors[\${sponsorIndex}][group_name]" required placeholder="e.g. Gold Sponsors">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Logo Size</label>
								<input type="number" name="wpfa_sponsors[\${sponsorIndex}][logo_size]" value="160" required min="50" max="500">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Sponsor Name</label>
								<input type="text" name="wpfa_sponsors[\${sponsorIndex}][name]" required>
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Logo URL</label>
								<input type="url" name="wpfa_sponsors[\${sponsorIndex}][image]" required placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Website URL</label>
								<input type="url" name="wpfa_sponsors[\${sponsorIndex}][link]" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Sort Level</label>
								<input type="number" name="wpfa_sponsors[\${sponsorIndex}][level]" value="0" min="0">
							</div>
							<div class="wpfaevent-meta-card-field span-2">
								<label>Description</label>
								<input type="text" name="wpfa_sponsors[\${sponsorIndex}][description]">
							</div>
						</div>
					</div>`;
					$('#wpfaevent-sponsors-container').append(html);
					sponsorIndex++;
				});

				$('#wpfaevent-sponsors-container').on('click', '.wpfaevent-remove-sponsor', function(e) {
					e.preventDefault();
					$(this).closest('.wpfaevent-meta-card').remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Render Event Exhibitors meta box.
	 *
	 * @since 1.0.1
	 * @param WP_Post $post The post object.
	 */
	public function render_event_exhibitors_meta_box( $post ) {
		$exhibitors = array();
		if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store      = new Wpfaevent_Eventyay_Dashboard_Store();
			$exhibitors = $store->read_dashboard_json_file( 'exhibitors-' . $post->ID . '.json', array() );
		}

		?>
		<div class="wpfaevent-meta-cards-container" id="wpfaevent-exhibitors-container">
			<?php
			if ( ! empty( $exhibitors ) && is_array( $exhibitors ) ) :
				foreach ( $exhibitors as $index => $ex ) :
					?>
					<div class="wpfaevent-meta-card">
						<a href="#" class="wpfaevent-remove-exhibitor wpfaevent-remove-card-btn"><?php esc_html_e( 'Remove', 'wpfaevent' ); ?></a>
						<div class="wpfaevent-meta-card-grid">
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Exhibitor Name', 'wpfaevent' ); ?></label>
								<input type="text" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][name]" value="<?php echo esc_attr( $ex['name'] ); ?>" required>
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Logo URL', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][logo]" value="<?php echo esc_attr( isset( $ex['logo'] ) ? $ex['logo'] : '' ); ?>" required placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Banner URL', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][banner]" value="<?php echo esc_attr( isset( $ex['banner'] ) ? $ex['banner'] : '' ); ?>" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Website URL', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][link]" value="<?php echo esc_attr( isset( $ex['link'] ) ? $ex['link'] : '' ); ?>" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Email', 'wpfaevent' ); ?></label>
								<input type="email" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][contact_email]" value="<?php echo esc_attr( isset( $ex['contact_email'] ) ? $ex['contact_email'] : '' ); ?>">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Contact Link', 'wpfaevent' ); ?></label>
								<input type="url" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][contact_link]" value="<?php echo esc_attr( isset( $ex['contact_link'] ) ? $ex['contact_link'] : '' ); ?>" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label><?php esc_html_e( 'Sort Order', 'wpfaevent' ); ?></label>
								<input type="number" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][position]" value="<?php echo esc_attr( isset( $ex['position'] ) ? $ex['position'] : 0 ); ?>" min="0">
							</div>
							<div class="wpfaevent-meta-card-field span-2">
								<label><?php esc_html_e( 'Description', 'wpfaevent' ); ?></label>
								<input type="text" name="wpfa_exhibitors[<?php echo absint( $index ); ?>][description]" value="<?php echo esc_attr( isset( $ex['description'] ) ? $ex['description'] : '' ); ?>">
							</div>
						</div>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>

		<button type="button" class="button button-primary" id="wpfaevent-add-exhibitor-row">
			<?php esc_html_e( 'Add Exhibitor', 'wpfaevent' ); ?>
		</button>

		<script>
			jQuery(document).ready(function($) {
				let exhibitorIndex = $('#wpfaevent-exhibitors-container .wpfaevent-meta-card').length;

				$('#wpfaevent-add-exhibitor-row').on('click', function(e) {
					e.preventDefault();
					const html = `<div class="wpfaevent-meta-card">
						<a href="#" class="wpfaevent-remove-exhibitor wpfaevent-remove-card-btn">Remove</a>
						<div class="wpfaevent-meta-card-grid">
							<div class="wpfaevent-meta-card-field">
								<label>Exhibitor Name</label>
								<input type="text" name="wpfa_exhibitors[\${exhibitorIndex}][name]" required>
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Logo URL</label>
								<input type="url" name="wpfa_exhibitors[\${exhibitorIndex}][logo]" required placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Banner URL</label>
								<input type="url" name="wpfa_exhibitors[\${exhibitorIndex}][banner]" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Website URL</label>
								<input type="url" name="wpfa_exhibitors[\${exhibitorIndex}][link]" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Email</label>
								<input type="email" name="wpfa_exhibitors[\${exhibitorIndex}][contact_email]">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Contact Link</label>
								<input type="url" name="wpfa_exhibitors[\${exhibitorIndex}][contact_link]" placeholder="https://">
							</div>
							<div class="wpfaevent-meta-card-field">
								<label>Sort Order</label>
								<input type="number" name="wpfa_exhibitors[\${exhibitorIndex}][position]" value="0" min="0">
							</div>
							<div class="wpfaevent-meta-card-field span-2">
								<label>Description</label>
								<input type="text" name="wpfa_exhibitors[\${exhibitorIndex}][description]">
							</div>
						</div>
					</div>`;
					$('#wpfaevent-exhibitors-container').append(html);
					exhibitorIndex++;
				});

				$('#wpfaevent-exhibitors-container').on('click', '.wpfaevent-remove-exhibitor', function(e) {
					e.preventDefault();
					$(this).closest('.wpfaevent-meta-card').remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Save Event meta box data.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 */
	public function save_event_meta( $post_id ) {
		$event_nonce = isset( $_POST['wpfa_event_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_meta_nonce'] ) ) : '';

		if ( ! $event_nonce || ! wp_verify_nonce( $event_nonce, 'wpfa_event_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$posted_start_date = isset( $_POST['wpfa_event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_date'] ) ) : '';
		$posted_end_date   = isset( $_POST['wpfa_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_date'] ) ) : '';
		$posted_timezone   = isset( $_POST['wpfa_event_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_timezone'] ) ) : '';
		$start_date        = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_date_value( $posted_start_date ) : $posted_start_date;
		$end_date          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_date_value( $posted_end_date ) : $posted_end_date;
		$timezone          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_timezone( $posted_timezone ) : $posted_timezone;

		if ( isset( $_POST['wpfa_event_start_date'] ) ) {
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_date', $start_date );
		}

		if ( isset( $_POST['wpfa_event_end_date'] ) ) {
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_date', $end_date );
		}

		if ( '' !== $timezone ) {
			update_post_meta( $post_id, 'wpfa_event_timezone', $timezone );
		} else {
			delete_post_meta( $post_id, 'wpfa_event_timezone' );
		}

		$all_day = isset( $_POST['wpfa_event_all_day'] );
		update_post_meta( $post_id, 'wpfa_event_all_day', $all_day ? '1' : '0' );

		$posted_start_time = isset( $_POST['wpfa_event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_start_time'] ) ) : '';
		$posted_end_time   = isset( $_POST['wpfa_event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_event_end_time'] ) ) : '';
		$start_time        = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_time_value( $posted_start_time ) : $posted_start_time;
		$end_time          = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::sanitize_time_value( $posted_end_time ) : $posted_end_time;

		if ( $all_day ) {
			delete_post_meta( $post_id, 'wpfa_event_start_time' );
			delete_post_meta( $post_id, 'wpfa_event_time' );
			delete_post_meta( $post_id, 'wpfa_event_end_time' );
			delete_post_meta( $post_id, 'wpfa_event_starts_at' );
			delete_post_meta( $post_id, 'wpfa_event_ends_at' );
		} else {
			$end_date_for_datetime = '' !== $end_date ? $end_date : $start_date;

			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_start_time', $start_time );
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_time', $start_time );
			$this->update_or_delete_post_meta( $post_id, 'wpfa_event_end_time', $end_time );
			$this->update_or_delete_post_meta(
				$post_id,
				'wpfa_event_starts_at',
				class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_datetime_value( $start_date, $start_time, $timezone ) : ''
			);
			$this->update_or_delete_post_meta(
				$post_id,
				'wpfa_event_ends_at',
				class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::build_datetime_value( $end_date_for_datetime, $end_time, $timezone ) : ''
			);
		}

		$meta_fields = array(
			'wpfa_event_location',
			'wpfa_event_lead_text',
			'wpfa_event_url',
			'wpfa_event_registration_link',
			'wpfa_event_cfs_link',
			'wpfa_event_header_image_url',
			'wpfa_event_logo_url',
			'wpfa_event_ticket_widget_url',
		);

		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$raw_value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

				if ( in_array( $field, array( 'wpfa_event_url', 'wpfa_event_registration_link', 'wpfa_event_cfs_link', 'wpfa_event_header_image_url', 'wpfa_event_logo_url', 'wpfa_event_ticket_widget_url' ), true ) ) {
					$value = esc_url_raw( $raw_value );
				} else {
					$value = $raw_value;
				}

				update_post_meta( $post_id, $field, $value );
			}
		}

		$previous_speakers = $this->get_event_speaker_ids( $post_id );
		$speakers          = array();

		// Handle speakers array.
		if ( isset( $_POST['wpfa_event_speakers'] ) && is_array( $_POST['wpfa_event_speakers'] ) ) {
			$speakers = $this->sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_event_speakers'] )
				)
			);
		}

		$this->update_post_id_list_meta( $post_id, 'wpfa_event_speakers', $speakers );

		Wpfaevent_Meta_Event::sync_event_speaker_relationships( $post_id, $previous_speakers, $speakers );

		// Handle schedule sessions saving.
		if ( isset( $_POST['wpfa_schedule_sessions'] ) && is_array( $_POST['wpfa_schedule_sessions'] ) ) {
			$raw_sessions       = wp_unslash( $_POST['wpfa_schedule_sessions'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			$formatted_data     = array();
			$formatted_sessions = array();

			// Add headers to data array.
			$formatted_data[]     = array( 'Date', 'Time', 'Session', 'Speakers', 'Track', 'Room' );
			$formatted_sessions[] = array(); // Header row meta is empty.

			$event_tz = get_post_meta( $post_id, 'wpfa_event_timezone', true );
			if ( ! $event_tz ) {
				$event_tz = wp_timezone_string();
			}
			$timezone_obj = new DateTimeZone( $event_tz );

			foreach ( $raw_sessions as $sess ) {
				$date       = isset( $sess['date'] ) ? sanitize_text_field( $sess['date'] ) : '';
				$time_start = isset( $sess['start_time'] ) ? sanitize_text_field( $sess['start_time'] ) : '';
				$time_end   = isset( $sess['end_time'] ) ? sanitize_text_field( $sess['end_time'] ) : '';
				$title      = isset( $sess['title'] ) ? sanitize_text_field( $sess['title'] ) : '';
				$speakers   = isset( $sess['speakers'] ) ? sanitize_text_field( $sess['speakers'] ) : '';
				$track      = isset( $sess['track'] ) ? sanitize_text_field( $sess['track'] ) : '';
				$room       = isset( $sess['room'] ) ? sanitize_text_field( $sess['room'] ) : '';

				if ( ! $date || ! $time_start || ! $title ) {
					continue;
				}

				// Build ISO 8601 UTC date-time.
				$starts_at_iso = '';
				$ends_at_iso   = '';
				$start_dt      = null;
				$end_dt        = null;

				try {
					$start_dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $time_start, $timezone_obj );
					if ( $start_dt ) {
						$starts_at_iso = $start_dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' );
					}
				} catch ( Exception $e ) {
					unset( $e );
				}

				try {
					if ( $time_end ) {
						$end_dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $time_end, $timezone_obj );
						if ( $end_dt ) {
							$ends_at_iso = $end_dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' );
						}
					}
				} catch ( Exception $e ) {
					unset( $e );
				}

				// Build time range string (12-hour format).
				$start_time_12 = $start_dt ? $start_dt->format( 'g:i A' ) : '';
				$end_time_12   = ( isset( $end_dt ) && $end_dt ) ? $end_dt->format( 'g:i A' ) : '';
				$time_label    = $start_time_12 . ( $end_time_12 ? ' - ' . $end_time_12 : '' );

				$formatted_data[] = array(
					$date,
					$time_label,
					$title,
					$speakers,
					$track,
					$room,
				);

				$formatted_sessions[] = array(
					'starts_at' => $starts_at_iso,
					'ends_at'   => $ends_at_iso,
				);
			}

			if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
				$store = new Wpfaevent_Eventyay_Dashboard_Store();
				$store->write_dashboard_json_file(
					'schedule-' . $post_id . '.json',
					array(
						'data'     => $formatted_data,
						'sessions' => $formatted_sessions,
					)
				);
			}
		} elseif ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store = new Wpfaevent_Eventyay_Dashboard_Store();
			$store->write_dashboard_json_file(
				'schedule-' . $post_id . '.json',
				array(
					'data'     => array( array( 'Date', 'Time', 'Session', 'Speakers', 'Track', 'Room' ) ),
					'sessions' => array( array() ),
				)
			);
		}

		// Handle sponsors saving.
		if ( isset( $_POST['wpfa_sponsors'] ) && is_array( $_POST['wpfa_sponsors'] ) ) {
			$raw_sponsors = wp_unslash( $_POST['wpfa_sponsors'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			$groups       = array();

			foreach ( $raw_sponsors as $sp ) {
				$group_name  = isset( $sp['group_name'] ) ? sanitize_text_field( $sp['group_name'] ) : '';
				$logo_size   = isset( $sp['logo_size'] ) ? absint( $sp['logo_size'] ) : 160;
				$name        = isset( $sp['name'] ) ? sanitize_text_field( $sp['name'] ) : '';
				$image       = isset( $sp['image'] ) ? esc_url_raw( $sp['image'] ) : '';
				$link        = isset( $sp['link'] ) ? esc_url_raw( $sp['link'] ) : '';
				$level       = isset( $sp['level'] ) ? absint( $sp['level'] ) : 0;
				$description = isset( $sp['description'] ) ? sanitize_text_field( $sp['description'] ) : '';

				if ( ! $group_name || ! $name || ! $image ) {
					continue;
				}

				if ( ! isset( $groups[ $group_name ] ) ) {
					$groups[ $group_name ] = array(
						'group_name' => $group_name,
						'logo_size'  => $logo_size,
						'sponsors'   => array(),
					);
				}

				$groups[ $group_name ]['sponsors'][] = array(
					'id'          => 'sponsor-' . sanitize_title( $name ),
					'source'      => 'manual',
					'name'        => $name,
					'image'       => $image,
					'link'        => $link,
					'level'       => $level,
					'description' => $description,
					'type'        => $group_name,
				);
			}

			// Sort sponsors inside each group by level ASC, name ASC.
			foreach ( $groups as &$g ) {
				usort(
					$g['sponsors'],
					static function ( $a, $b ) {
						if ( $a['level'] !== $b['level'] ) {
							return $a['level'] <=> $b['level'];
						}
						return strcasecmp( $a['name'], $b['name'] );
					}
				);
			}
			unset( $g );

			// Reindex groups.
			$formatted_sponsors = array_values( $groups );

			if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
				$store = new Wpfaevent_Eventyay_Dashboard_Store();
				$store->write_dashboard_json_file(
					'sponsors-' . $post_id . '.json',
					$formatted_sponsors
				);
			}
		} elseif ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store = new Wpfaevent_Eventyay_Dashboard_Store();
			$store->write_dashboard_json_file(
				'sponsors-' . $post_id . '.json',
				array()
			);
		}

		// Handle exhibitors saving.
		if ( isset( $_POST['wpfa_exhibitors'] ) && is_array( $_POST['wpfa_exhibitors'] ) ) {
			$raw_exhibitors       = wp_unslash( $_POST['wpfa_exhibitors'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below.
			$formatted_exhibitors = array();

			foreach ( $raw_exhibitors as $ex ) {
				$name          = isset( $ex['name'] ) ? sanitize_text_field( $ex['name'] ) : '';
				$logo          = isset( $ex['logo'] ) ? esc_url_raw( $ex['logo'] ) : '';
				$banner        = isset( $ex['banner'] ) ? esc_url_raw( $ex['banner'] ) : '';
				$link          = isset( $ex['link'] ) ? esc_url_raw( $ex['link'] ) : '';
				$contact_email = isset( $ex['contact_email'] ) ? sanitize_email( $ex['contact_email'] ) : '';
				$contact_link  = isset( $ex['contact_link'] ) ? esc_url_raw( $ex['contact_link'] ) : '';
				$position      = isset( $ex['position'] ) ? absint( $ex['position'] ) : 0;
				$description   = isset( $ex['description'] ) ? sanitize_text_field( $ex['description'] ) : '';

				if ( ! $name || ! $logo ) {
					continue;
				}

				$formatted_exhibitors[] = array(
					'id'            => 'exhibitor-' . sanitize_title( $name ),
					'source'        => 'manual',
					'name'          => $name,
					'logo'          => $logo,
					'banner'        => $banner,
					'link'          => $link,
					'contact_email' => $contact_email,
					'contact_link'  => $contact_link,
					'position'      => $position,
					'description'   => $description,
				);
			}

			// Sort exhibitors by position ASC, name ASC.
			usort(
				$formatted_exhibitors,
				static function ( $a, $b ) {
					if ( $a['position'] !== $b['position'] ) {
						return $a['position'] <=> $b['position'];
					}
					return strcasecmp( $a['name'], $b['name'] );
				}
			);

			if ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
				$store = new Wpfaevent_Eventyay_Dashboard_Store();
				$store->write_dashboard_json_file(
					'exhibitors-' . $post_id . '.json',
					$formatted_exhibitors
				);
			}
		} elseif ( class_exists( 'Wpfaevent_Eventyay_Dashboard_Store' ) ) {
			$store = new Wpfaevent_Eventyay_Dashboard_Store();
			$store->write_dashboard_json_file(
				'exhibitors-' . $post_id . '.json',
				array()
			);
		}
	}

	/**
	 * Get normalized speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int> Speaker post IDs.
	 */
	private function get_event_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_speakers', true );

		return $this->sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int> Sanitized post IDs.
	 */
	private function sanitize_post_id_list( $post_ids ) {
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array( $post_ids );
		}

		$post_ids = array_map( 'absint', $post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Save a normalized post ID list as post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $post_id  Post ID.
	 * @param string     $meta_key Meta key.
	 * @param array<int> $post_ids Post IDs to save.
	 * @return void
	 */
	private function update_post_id_list_meta( $post_id, $meta_key, $post_ids ) {
		$post_ids = $this->sanitize_post_id_list( $post_ids );

		if ( empty( $post_ids ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $post_ids );
	}

	/**
	 * Update a meta key when it has content, otherwise delete it.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param string $value   Meta value.
	 * @return void
	 */
	private function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
