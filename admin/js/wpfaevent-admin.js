(function ($) {
	'use strict';

	$(function () {
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
})(jQuery);