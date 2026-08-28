(function ($) {
	'use strict';

	$(function () {
		const $importForm = $('#wpfaevent-import-events-form');
		const $updateForm = $('#wpfaevent-update-events-form');
		const sponsorGroupOrderForm = document.getElementById(
			'wpfaevent-sponsor-group-order-form'
		);
		const sponsorGroupList = document.getElementById(
			'wpfaevent-sponsor-group-order-list'
		);
		const sponsorGroupUnsavedIndicator = document.getElementById(
			'wpfaevent-sponsor-group-order-unsaved'
		);

		if (sponsorGroupList) {
			let sponsorGroupOrderDirty = false;
			let sponsorGroupOrderSubmitting = false;

			const markSponsorGroupOrderDirty = function () {
				sponsorGroupOrderDirty = true;
				if (sponsorGroupUnsavedIndicator) {
					sponsorGroupUnsavedIndicator.hidden = false;
				}
			};

			const refreshSponsorGroupButtons = function () {
				const items = sponsorGroupList.querySelectorAll(
					'.wpfaevent-sponsor-group-order-item'
				);

				items.forEach(function (item, index) {
					const upButton = item.querySelector(
						'.wpfaevent-move-group-up'
					);
					const downButton = item.querySelector(
						'.wpfaevent-move-group-down'
					);

					if (upButton) {
						upButton.disabled = index === 0;
					}

					if (downButton) {
						downButton.disabled = index === items.length - 1;
					}
				});
			};

			sponsorGroupList.addEventListener('click', function (event) {
				const button = event.target.closest('button');
				if (!button) {
					return;
				}

				const item = button.closest(
					'.wpfaevent-sponsor-group-order-item'
				);
				if (!item) {
					return;
				}

				if (button.classList.contains('wpfaevent-move-group-up')) {
					const previousItem = item.previousElementSibling;
					if (previousItem) {
						sponsorGroupList.insertBefore(item, previousItem);
						markSponsorGroupOrderDirty();
						refreshSponsorGroupButtons();
					}
				}

				if (button.classList.contains('wpfaevent-move-group-down')) {
					const nextItem = item.nextElementSibling;
					if (nextItem) {
						sponsorGroupList.insertBefore(nextItem, item);
						markSponsorGroupOrderDirty();
						refreshSponsorGroupButtons();
					}
				}
			});

			if (sponsorGroupOrderForm) {
				sponsorGroupOrderForm.addEventListener('submit', function () {
					sponsorGroupOrderSubmitting = true;
					sponsorGroupOrderDirty = false;
					if (sponsorGroupUnsavedIndicator) {
						sponsorGroupUnsavedIndicator.hidden = true;
					}
				});
			}

			window.addEventListener('beforeunload', function (event) {
				if (!sponsorGroupOrderDirty || sponsorGroupOrderSubmitting) {
					return undefined;
				}

				const warningMessage = sponsorGroupOrderForm
					? sponsorGroupOrderForm.getAttribute(
							'data-unsaved-warning'
						) || 'You have unsaved changes.'
					: 'You have unsaved changes.';

				event.preventDefault();
				event.returnValue = warningMessage;
				return warningMessage;
			});

			refreshSponsorGroupButtons();
		}

		function showImportNotice(message) {
			const $container = $('.wrap').first();
			const $existingNotices = $container.find(
				'.notice.wpfaevent-import-notice'
			);
			const $notice = $('<div/>', {
				class: 'notice notice-error is-dismissible wpfaevent-import-notice',
			}).append($('<p/>').text(message));

			$existingNotices.remove();
			$container.prepend($notice);
		}

		if ($importForm.length || $updateForm.length) {
			const $form = $importForm.length ? $importForm : $updateForm;
			const rawReturnPage = $form
				.find('input[name="wpfaevent_eventyay_return_page"]')
				.val();
			const returnPage = /^[a-zA-Z0-9_-]+$/.test(rawReturnPage || '')
				? rawReturnPage
				: 'wpfaevent-import-events';

			$form.on('submit', function (e) {
				e.preventDefault();

				const nonce = $form.find('input[name="_wpnonce"]').val();
				if (!nonce) {
					showImportNotice(
						'Security validation failed: Nonce missing.'
					);
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
						nonce,
					},
					success(response) {
						if (!response.success) {
							saveSummaryAndRedirect(
								'error',
								response.data.message ||
									'Failed to fetch events from the endpoint.',
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
						$status.text(
							'Found ' + fetched + ' event(s). Starting sync...'
						);
						processNextEvent(events, 0);
					},
					error(xhr) {
						let errorMsg =
							'Failed to fetch events from Eventyay endpoint.';
						if (
							xhr.responseJSON &&
							xhr.responseJSON.data &&
							xhr.responseJSON.data.message
						) {
							errorMsg = xhr.responseJSON.data.message;
						}
						saveSummaryAndRedirect(
							'error',
							errorMsg,
							returnPage,
							nonce
						);
					},
				});

				function processNextEvent(events, index) {
					if (index >= events.length) {
						$status.text('Finalizing sync...');
						$bar.css('width', '100%');
						const message =
							'Fetched ' +
							fetched +
							' Eventyay event(s). Created ' +
							created +
							', updated ' +
							updated +
							', skipped ' +
							skipped +
							'.';
						saveSummaryAndRedirect(
							'success',
							message,
							returnPage,
							nonce
						);
						return;
					}

					const event = events[index];
					const percent = Math.round((index / events.length) * 100);
					$bar.css('width', percent + '%');

					const eventTitle = getEventTitle(event);
					$status.text(
						'Importing ' +
							(index + 1) +
							' of ' +
							events.length +
							': ' +
							eventTitle
					);
					$details.text(
						'Saving event details, location, and dates...'
					);

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wpfaevent_import_single_event',
							nonce,
							event: JSON.stringify(event),
						},
						success(response) {
							if (response.success) {
								const res = response.data;
								created += res.created || 0;
								updated += res.updated || 0;
								skipped += res.skipped || 0;
							} else {
								skipped++;
							}
							setTimeout(function () {
								processNextEvent(events, index + 1);
							}, 300);
						},
						error() {
							skipped++;
							setTimeout(function () {
								processNextEvent(events, index + 1);
							}, 300);
						},
					});
				}

				function saveSummaryAndRedirect(
					type,
					message,
					targetPage,
					summaryNonce
				) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wpfaevent_import_save_summary',
							nonce: summaryNonce,
							type,
							message,
						},
						complete() {
							window.location.href =
								'edit.php?post_type=wpfa_event&page=' +
								encodeURIComponent(targetPage);
						},
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
				const preferredKeys = [
					'en',
					'default',
					'value',
					'name',
					'title',
				];
				for (let i = 0; i < preferredKeys.length; i++) {
					const key = preferredKeys[i];
					if (
						typeof val[key] === 'string' &&
						val[key].trim() !== ''
					) {
						return val[key].trim();
					}
				}
				for (const key in val) {
					if (Object.prototype.hasOwnProperty.call(val, key)) {
						if (
							typeof val[key] === 'string' &&
							val[key].trim() !== ''
						) {
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

		if (
			typeof event.identifier === 'string' &&
			event.identifier.trim() !== ''
		) {
			return event.identifier.trim();
		}

		if (typeof event.code === 'string' && event.code.trim() !== '') {
			return event.code.trim();
		}

		return 'Unnamed Event';
	}
})(jQuery);
