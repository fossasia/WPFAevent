(function( $ ) {
	'use strict';

	$(function() {
		const $importForm = $('#wpfaevent-import-events-form');
		const $updateForm = $('#wpfaevent-update-events-form');

		if ($importForm.length || $updateForm.length) {
			const $form = $importForm.length ? $importForm : $updateForm;
			const returnPage = $form.find('input[name="wpfaevent_eventyay_return_page"]').val() || 'wpfaevent-import-events';

			$form.on('submit', function(e) {
				e.preventDefault();

				const nonce = $form.find('input[name="_wpnonce"]').val();
				if (!nonce) {
					alert('Security validation failed: Nonce missing.');
					return;
				}

				// Initialize stats
				let fetched = 0;
				let created = 0;
				let updated = 0;
				let skipped = 0;
				let sessions = 0;
				let speakers = 0;
				let sponsors = 0;
				let exhibitors = 0;
				let created_speakers = 0;
				let updated_speakers = 0;
				let about_updates = 0;
				let schedule_rows = 0;
				let program_skipped = 0;
				let partner_skipped = 0;

				// Show overlay
				const $overlay = $('#wpfaevent-import-progress-overlay');
				const $title = $('#wpfaevent-progress-title');
				const $bar = $('#wpfaevent-progress-bar');
				const $status = $('#wpfaevent-progress-status');
				const $details = $('#wpfaevent-progress-details');

				$overlay.css('display', 'flex');
				$title.text('Syncing with Eventyay');
				$bar.css('width', '0%');
				$status.text('Connecting to Eventyay...');
				$details.text('');

				// Fetch all events first
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wpfaevent_import_get_events',
						nonce: nonce
					},
					success: function(response) {
						if (!response.success) {
							saveSummaryAndRedirect(
								'error',
								response.data.message || 'Failed to fetch events from the endpoint.',
								returnPage,
								nonce
							);
							return;
						}

						const events = response.data.events;
						if (!events || !events.length) {
							saveSummaryAndRedirect(
								'error',
								'No Eventyay events were returned by the configured endpoint.',
								returnPage,
								nonce
							);
							return;
						}

						fetched = events.length;
						$status.text('Found ' + fetched + ' event(s). Starting sync...');
						processNextEvent(events, 0);
					},
					error: function(xhr) {
						let errorMsg = 'Failed to fetch events from Eventyay endpoint.';
						if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
							errorMsg = xhr.responseJSON.data.message;
						}
						saveSummaryAndRedirect('error', errorMsg, returnPage, nonce);
					}
				});

				function processNextEvent(events, index) {
					if (index >= events.length) {
						$status.text('Finalizing sync...');
						$bar.css('width', '100%');
						const message = 'Fetched ' + fetched + ' Eventyay event(s). Created ' + created + ', updated ' + updated + ', skipped ' + skipped + '. Imported ' + sessions + ' session(s), ' + speakers + ' speaker(s), ' + sponsors + ' sponsor(s), ' + exhibitors + ' exhibitor(s), ' + schedule_rows + ' schedule row(s), and updated ' + about_updates + ' about section(s); skipped program import for ' + program_skipped + ' event(s) and sponsor/exhibitor import for ' + partner_skipped + ' event(s).';
						saveSummaryAndRedirect('success', message, returnPage, nonce);
						return;
					}

					const event = events[index];
					const percent = Math.round((index / events.length) * 100);
					$bar.css('width', percent + '%');
					
					const eventTitle = event.name || event.title || event.event_slug || 'Unnamed Event';
					$status.text('Importing ' + (index + 1) + ' of ' + events.length + ': ' + eventTitle);
					$details.text('Processing speakers, sessions, schedules, and partners...');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wpfaevent_import_single_event',
							nonce: nonce,
							event: JSON.stringify(event)
						},
						success: function(response) {
							if (response.success) {
								const res = response.data;
								created += res.created || 0;
								updated += res.updated || 0;
								skipped += res.skipped || 0;
								sessions += res.sessions || 0;
								speakers += res.speakers || 0;
								sponsors += res.sponsors || 0;
								exhibitors += res.exhibitors || 0;
								created_speakers += res.created_speakers || 0;
								updated_speakers += res.updated_speakers || 0;
								about_updates += res.about_updates || 0;
								schedule_rows += res.schedule_rows || 0;
								program_skipped += res.program_skipped || 0;
								partner_skipped += res.partner_skipped || 0;
							} else {
								skipped++;
							}
							setTimeout(function() {
								processNextEvent(events, index + 1);
							}, 300);
						},
						error: function() {
							skipped++;
							setTimeout(function() {
								processNextEvent(events, index + 1);
							}, 300);
						}
					});
				}

				function saveSummaryAndRedirect(type, message, returnPage, nonce) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wpfaevent_import_save_summary',
							nonce: nonce,
							type: type,
							message: message
						},
						complete: function() {
							window.location.href = 'edit.php?post_type=wpfa_event&page=' + returnPage;
						}
					});
				}
			});
		}
	});

})( jQuery );
