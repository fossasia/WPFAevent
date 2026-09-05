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

		// The import posts and reloads, so nothing on the page moved while it ran.
		// Show the overlay for the duration of that request. The form still posts
		// normally, so the import itself is unchanged.
		const $eventyayImportForm = $('.wpfaevent-eventyay-import-form');

		if ($eventyayImportForm.length) {
			let importRunning = false;

			$eventyayImportForm.on('submit', function (e) {
				if (importRunning) {
					e.preventDefault();
					return;
				}

				importRunning = true;

				// Left enabled so the button still posts its own value; the styling
				// and aria state are what stop a second run.
				$(this)
					.find('[type="submit"]')
					.addClass('disabled')
					.attr('aria-disabled', 'true');

				$('#wpfaevent-import-progress-overlay').css('display', 'flex');
			});
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

		// Featured Speakers Manual Ordering
		const $speakersSection = $('#wpfaevent-speakers');
		if ($speakersSection.length) {
			const $sortableList = $('#wpfaevent-featured-speakers-sortable');
			const $allSpeakersList = $('.wpfaevent-all-speakers-container');
			const eventId = $('.wpfaevent-dashboard-shell').data('event-id');
			const speakersNonce = $speakersSection.data('speakers-nonce');
			let saveInProgress = false;
			let queuedSpeakerIds = null;
			let latestSaveRevision = 0;

			if ($sortableList.length && $.fn.sortable) {
				$sortableList.sortable({
					handle: '.dashicons-menu',
					placeholder: 'wpfaevent-sortable-placeholder',
					update() {
						saveFeaturedSpeakersOrder();
					},
				});
			}

			// Handle Toggle Feature Button Click
			$allSpeakersList.on(
				'click',
				'.wpfaevent-toggle-feature-btn',
				function (e) {
					e.preventDefault();
					const $btn = $(this);
					const speakerId = $btn.data('speaker-id');
					const isFeatured = $btn.hasClass('is-featured');

					if (isFeatured) {
						unfeatureSpeaker(speakerId);
					} else {
						featureSpeaker(speakerId);
					}
				}
			);

			// Handle Remove Featured Button Click
			$speakersSection.on(
				'click',
				'.wpfaevent-unfeature-btn',
				function (e) {
					e.preventDefault();
					const speakerId = $(this).data('speaker-id');
					unfeatureSpeaker(speakerId);
				}
			);

			function featureSpeaker(speakerId) {
				speakerId = normalizeSpeakerId(speakerId);
				if (!speakerId) {
					return;
				}

				const $allCard = findSpeakerElement(
					$allSpeakersList.find('.wpfaevent-toggle-feature-btn'),
					speakerId
				).closest('.wpfaevent-list-item');
				if (!$allCard.length) {
					return;
				}

				const $toggleBtn = $allCard.find(
					'.wpfaevent-toggle-feature-btn'
				);
				$toggleBtn.addClass('is-featured').text('Featured');
				$allCard.addClass('is-featured');

				// Remove placeholder if it exists
				$sortableList
					.find('.wpfaevent-sortable-placeholder-item')
					.remove();

				if (
					!findSpeakerElement(
						$sortableList.find('.wpfaevent-sortable-item'),
						speakerId
					).length
				) {
					const name = $allCard.find('strong').text();
					const title = $allCard.find('.description').text();
					const imgUrl = getSafeImageUrl(
						$allCard.find('img').attr('src')
					);
					const $newLi = $('<li>')
						.addClass('wpfaevent-sortable-item')
						.attr('data-speaker-id', speakerId);
					const $speakerSummary = $('<div>').addClass(
						'wpfaevent-featured-speaker-details'
					);

					$speakerSummary.append(
						$('<span>').addClass(
							'dashicons dashicons-menu wpfaevent-featured-speaker-drag-handle'
						)
					);

					if (imgUrl) {
						$speakerSummary.append(
							$('<img>')
								.attr({ src: imgUrl, alt: name })
								.addClass(
									'wpfaevent-featured-speaker-thumbnail wpfaevent-featured-speaker-thumbnail--sortable'
								)
						);
					}

					$speakerSummary.append(
						$('<div>')
							.addClass('wpfaevent-featured-speaker-copy')
							.append(
								$('<strong>')
									.addClass('wpfaevent-featured-speaker-name')
									.text(name)
							)
							.append(
								$('<div>')
									.addClass(
										'description wpfaevent-featured-speaker-title'
									)
									.text(title)
							)
					);

					$newLi.append($speakerSummary).append(
						$('<button>')
							.attr({
								type: 'button',
								'data-speaker-id': speakerId,
							})
							.addClass(
								'button button-small wpfaevent-unfeature-btn'
							)
							.text('Remove Featured')
					);
					$sortableList.append($newLi);
				}

				saveFeaturedSpeakersOrder();
			}

			function unfeatureSpeaker(speakerId) {
				speakerId = normalizeSpeakerId(speakerId);
				if (!speakerId) {
					return;
				}

				const $allCard = findSpeakerElement(
					$allSpeakersList.find('.wpfaevent-toggle-feature-btn'),
					speakerId
				).closest('.wpfaevent-list-item');
				if ($allCard.length) {
					const $toggleBtn = $allCard.find(
						'.wpfaevent-toggle-feature-btn'
					);
					$toggleBtn.removeClass('is-featured').text('Feature');
					$allCard.removeClass('is-featured');
				}

				findSpeakerElement(
					$sortableList.find('.wpfaevent-sortable-item'),
					speakerId
				).remove();

				if (
					$sortableList.find('.wpfaevent-sortable-item').length === 0
				) {
					$sortableList
						.empty()
						.append(
							$('<li>')
								.addClass('wpfaevent-sortable-placeholder-item')
								.text(
									'No featured speakers. Toggle featured status on speakers below to add them.'
								)
						);
				}

				saveFeaturedSpeakersOrder();
			}

			function saveFeaturedSpeakersOrder() {
				const speakerIds = [];
				$sortableList
					.find('.wpfaevent-sortable-item')
					.each(function () {
						const id = normalizeSpeakerId(
							$(this).attr('data-speaker-id')
						);
						if (id) {
							speakerIds.push(id);
						}
					});
				queuedSpeakerIds = speakerIds;
				latestSaveRevision++;
				processFeaturedSpeakersSave();
			}

			function processFeaturedSpeakersSave() {
				if (saveInProgress || queuedSpeakerIds === null) {
					return;
				}

				const speakerIds = queuedSpeakerIds;
				const saveRevision = latestSaveRevision;
				let saveResult = null;
				queuedSpeakerIds = null;
				saveInProgress = true;

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wpfaevent_save_featured_speakers',
						event_id: eventId,
						speaker_ids: speakerIds,
						nonce: speakersNonce,
					},
					success(response) {
						if (response.success) {
							saveResult = {
								type: 'success',
								message: response.data.message,
							};
						} else {
							saveResult = {
								type: 'error',
								message:
									response.data.message ||
									'Failed to save featured speakers.',
							};
						}
					},
					error() {
						saveResult = {
							type: 'error',
							message: 'An error occurred while saving.',
						};
					},
					complete() {
						saveInProgress = false;

						if (queuedSpeakerIds !== null) {
							processFeaturedSpeakersSave();
							return;
						}

						if (saveRevision === latestSaveRevision && saveResult) {
							drawDashboardNotice(
								saveResult.type,
								saveResult.message
							);
						}
					},
				});
			}

			function normalizeSpeakerId(speakerId) {
				const normalized = Number(speakerId);
				return Number.isSafeInteger(normalized) && normalized > 0
					? normalized
					: 0;
			}

			function findSpeakerElement($elements, speakerId) {
				return $elements.filter(function () {
					return (
						normalizeSpeakerId($(this).attr('data-speaker-id')) ===
						speakerId
					);
				});
			}

			function getSafeImageUrl(imageUrl) {
				if (!imageUrl) {
					return '';
				}

				try {
					const parsedUrl = new URL(imageUrl, document.baseURI);
					return ['http:', 'https:'].includes(parsedUrl.protocol)
						? parsedUrl.href
						: '';
				} catch {
					return '';
				}
			}

			function drawDashboardNotice(type, message) {
				const container =
					document.querySelector(
						'.wpfaevent-notification-container'
					) || document.querySelector('.wpfaevent-dashboard-shell');
				if (!container) {
					return;
				}

				const notice = document.createElement('div');
				notice.className = 'notice notice-' + type + ' is-dismissible';
				const p = document.createElement('p');
				p.textContent = message;
				notice.appendChild(p);

				const existing = container.querySelectorAll(
					'.notice.wpfaevent-edit-notice'
				);
				existing.forEach((el) => el.remove());

				notice.classList.add('wpfaevent-edit-notice');
				container.insertBefore(notice, container.firstChild);
				notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}
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
