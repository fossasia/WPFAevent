(function( $ ) {
	'use strict';

	$(function() {
		const $importForm = $('#wpfaevent-import-events-form');
		const $updateForm = $('#wpfaevent-update-events-form');

		if ($importForm.length || $updateForm.length) {
			const $form = $importForm.length ? $importForm : $updateForm;
			const rawReturnPage = $form.find('input[name="wpfaevent_eventyay_return_page"]').val();
			const returnPage = /^[a-zA-Z0-9_-]+$/.test(rawReturnPage || '') ? rawReturnPage : 'wpfaevent-import-events';

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
						const message = 'Fetched ' + fetched + ' Eventyay event(s). Created ' + created + ', updated ' + updated + ', skipped ' + skipped + '.';
						saveSummaryAndRedirect('success', message, returnPage, nonce);
						return;
					}

					const event = events[index];
					const percent = Math.round((index / events.length) * 100);
					$bar.css('width', percent + '%');

					const eventTitle = getEventTitle(event);
					$status.text('Importing ' + (index + 1) + ' of ' + events.length + ': ' + eventTitle);
					$details.text('Saving event details, location, and dates...');

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
							window.location.href = 'edit.php?post_type=wpfa_event&page=' + encodeURIComponent(returnPage);
						}
					});
				}
			});
		}
	});

	function getEventTitle(event) {
		if (!event) {
			return 'Unnamed Event';
		}
		
		// Helper to extract string from localized object/array
		function getStringValue(val) {
			if (typeof val === 'string' && val.trim() !== '') {
				return val.trim();
			}
			if (val && typeof val === 'object') {
				const preferredKeys = ['en', 'default', 'value', 'name', 'title'];
				for (let i = 0; i < preferredKeys.length; i++) {
					const key = preferredKeys[i];
					if (typeof val[key] === 'string' && val[key].trim() !== '') {
						return val[key].trim();
					}
				}
				for (const key in val) {
					if (Object.prototype.hasOwnProperty.call(val, key)) {
						if (typeof val[key] === 'string' && val[key].trim() !== '') {
							return val[key].trim();
						}
					}
				}
			}
			return null;
		}

		const name = getStringValue(event.name);
		if (name) {
			return name;
		}

		const title = getStringValue(event.title);
		if (title) {
			return title;
		}

		if (typeof event.slug === 'string' && event.slug.trim() !== '') {
			return event.slug.trim();
		}

		if (typeof event.identifier === 'string' && event.identifier.trim() !== '') {
			return event.identifier.trim();
		}

		if (typeof event.code === 'string' && event.code.trim() !== '') {
			return event.code.trim();
		}

		return 'Unnamed Event';
	}

})( jQuery );
