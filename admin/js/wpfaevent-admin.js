(function ($) {
	'use strict';

	$(function () {
		const $importForm = $('#wpfaevent-import-events-form');
		const $updateForm = $('#wpfaevent-update-events-form');

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

		// Featured Speakers Manual Ordering (Issue #249)
		const $speakersSection = $('#wpfaevent-speakers');
		if ($speakersSection.length) {
			const $sortableList = $('#wpfaevent-featured-speakers-sortable');
			const $allSpeakersList = $('.wpfaevent-all-speakers-container');
			const eventId = $('.wpfaevent-dashboard-shell').data('event-id');
			const speakersNonce = $speakersSection.data('speakers-nonce');

			if ($sortableList.length && $.fn.sortable) {
				$sortableList.sortable({
					handle: '.dashicons-menu',
					placeholder: 'wpfaevent-sortable-placeholder',
					update: function() {
						saveFeaturedSpeakersOrder();
					}
				});
			}

			// Handle Toggle Feature Button Click
			$allSpeakersList.on('click', '.wpfaevent-toggle-feature-btn', function(e) {
				e.preventDefault();
				const $btn = $(this);
				const speakerId = $btn.data('speaker-id');
				const isFeatured = $btn.hasClass('is-featured');

				if (isFeatured) {
					unfeatureSpeaker(speakerId);
				} else {
					featureSpeaker(speakerId);
				}
			});

			// Handle Remove Featured Button Click
			$speakersSection.on('click', '.wpfaevent-unfeature-btn', function(e) {
				e.preventDefault();
				const speakerId = $(this).data('speaker-id');
				unfeatureSpeaker(speakerId);
			});

			function featureSpeaker(speakerId) {
				const $allCard = $(`.wpfaevent-toggle-feature-btn[data-speaker-id="${speakerId}"]`).closest('.wpfaevent-list-item');
				if (!$allCard.length) {
					return;
				}

				const $toggleBtn = $allCard.find('.wpfaevent-toggle-feature-btn');
				$toggleBtn.addClass('is-featured')
					.text('Featured')
					.css({
						'border-color': '#bbf7d0',
						'background': '#e8f5e9',
						'color': '#1b5e20'
					});
				$allCard.css('background', '#f0fdf4');

				// Remove placeholder if it exists
				$sortableList.find('.wpfaevent-sortable-placeholder-item').remove();

				if (!$sortableList.find(`li[data-speaker-id="${speakerId}"]`).length) {
					const name = $allCard.find('strong').text();
					const title = $allCard.find('.description').text();
					const imgUrl = $allCard.find('img').attr('src');

					const newLi = `
						<li class="wpfaevent-sortable-item" data-speaker-id="${speakerId}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border: 1px solid #e4ebf3; border-radius: 8px; margin-bottom: 8px; background: #fff; cursor: move;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-menu" style="color: #a0aec0; cursor: move;"></span>
								${imgUrl ? `<img src="${imgUrl}" alt="${name}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">` : ''}
								<div>
									<strong style="font-size: 13px;">${name}</strong>
									<div class="description" style="font-size: 11px;">${title}</div>
								</div>
							</div>
							<button type="button" class="button button-small wpfaevent-unfeature-btn" data-speaker-id="${speakerId}">Remove Featured</button>
						</li>
					`;
					$sortableList.append(newLi);
				}

				saveFeaturedSpeakersOrder();
			}

			function unfeatureSpeaker(speakerId) {
				const $allCard = $(`.wpfaevent-toggle-feature-btn[data-speaker-id="${speakerId}"]`).closest('.wpfaevent-list-item');
				if ($allCard.length) {
					const $toggleBtn = $allCard.find('.wpfaevent-toggle-feature-btn');
					$toggleBtn.removeClass('is-featured')
						.text('Feature')
						.css({
							'border-color': '#d1d5db',
							'background': '#fff',
							'color': '#374151'
						});
					$allCard.css('background', '#fff');
				}

				$sortableList.find(`li[data-speaker-id="${speakerId}"]`).remove();

				if ($sortableList.find('.wpfaevent-sortable-item').length === 0) {
					const placeholder = `
						<li class="wpfaevent-sortable-placeholder-item" style="padding: 15px; text-align: center; border: 1px dashed #cbd5e0; border-radius: 8px; color: #718096; background: #f8fafc;">
							No featured speakers. Toggle featured status on speakers below to add them.
						</li>
					`;
					$sortableList.html(placeholder);
				}

				saveFeaturedSpeakersOrder();
			}

			function saveFeaturedSpeakersOrder() {
				const speakerIds = [];
				$sortableList.find('.wpfaevent-sortable-item').each(function() {
					const id = $(this).data('speaker-id');
					if (id) {
						speakerIds.push(id);
					}
				});

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wpfaevent_save_featured_speakers',
						event_id: eventId,
						speaker_ids: speakerIds,
						nonce: speakersNonce
					},
					success: function(response) {
						if (response.success) {
							drawDashboardNotice('success', response.data.message);
						} else {
							drawDashboardNotice('error', response.data.message || 'Failed to save featured speakers.');
						}
					},
					error: function() {
						drawDashboardNotice('error', 'An error occurred while saving.');
					}
				});
			}

			function drawDashboardNotice(type, message) {
				const container = document.querySelector('.wpfaevent-notification-container') || document.querySelector('.wpfaevent-dashboard-shell');
				if (!container) return;

				const notice = document.createElement('div');
				notice.className = 'notice notice-' + type + ' is-dismissible';
				notice.style.margin = '0 0 20px';
				const p = document.createElement('p');
				p.textContent = message;
				notice.appendChild(p);

				const existing = container.querySelectorAll('.notice.wpfaevent-edit-notice');
				existing.forEach(el => el.remove());

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
