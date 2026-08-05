(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(function() {
		// Client-side Schedule View Switch (List vs Calendar)
		$(document).on('click', '.wpfa-schedule-view-switch a', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const href = $btn.attr('href') || '';
			const isCalendar = href.indexOf('view=calendar') !== -1 || href.indexOf('schedule_view=calendar') !== -1;

			const $switch = $btn.closest('.wpfa-schedule-view-switch');
			$switch.find('a').removeClass('is-active').removeAttr('aria-current');
			$btn.addClass('is-active').attr('aria-current', 'page');

			if (isCalendar) {
				$('.wpfa-schedule-calendar').show();
				$('.wpfa-schedule-program').hide();
			} else {
				$('.wpfa-schedule-program').show();
				$('.wpfa-schedule-calendar').hide();
			}

			if (window.history && window.history.replaceState) {
				window.history.replaceState(null, '', href);
			}
		});

		// Client-side Timezone Converter
		$(document).on('change', '.wpfa-event-timezone-select, #wpfa-schedule-timezone', function(e) {
			e.preventDefault();
			const selectedTz = $(this).val();
			if (!selectedTz) return;

			let formatter;
			try {
				formatter = new Intl.DateTimeFormat([], {
					timeZone: selectedTz,
					hour: 'numeric',
					minute: '2-digit',
					hour12: true
				});
			} catch (err) {
				return;
			}

			$('time[data-utc-start]').each(function() {
				const rawStart = $(this).attr('data-utc-start');
				const rawEnd = $(this).attr('data-utc-end');

				try {
					const startObj = rawStart ? new Date(rawStart) : null;
					if (!startObj || isNaN(startObj.getTime())) return;

					const startLabel = formatter.format(startObj);

					if (rawEnd) {
						const endObj = new Date(rawEnd);
						const endLabel = !isNaN(endObj.getTime()) ? formatter.format(endObj) : '';
						$(this).text(endLabel && endLabel !== startLabel ? `${startLabel} - ${endLabel}` : startLabel);
						return;
					}

					$(this).text(startLabel);
				} catch (err) {
					// Timezone fallback if unsupported string
				}
			});

			if (window.history && window.history.replaceState) {
				const currentUrl = new URL(window.location.href);
				currentUrl.searchParams.set('schedule_tz', selectedTz);
				window.history.replaceState(null, '', currentUrl.toString());
			}
		});

		const speakerPlaceholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600" role="img" aria-label="Speaker placeholder"><defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f8d8d6"/><stop offset="0.58" stop-color="#f4f7fb"/><stop offset="1" stop-color="#dfe9f3"/></linearGradient><linearGradient id="accent" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#d51007"/><stop offset="1" stop-color="#b20d06"/></linearGradient></defs><rect width="600" height="600" fill="url(#bg)"/><circle cx="476" cy="118" r="96" fill="#fff" opacity="0.54"/><circle cx="96" cy="486" r="126" fill="#d51007" opacity="0.08"/><circle cx="300" cy="245" r="105" fill="#ffffff"/><circle cx="300" cy="245" r="78" fill="#d8e3ee"/><path d="M128 526c20-108 96-168 172-168s152 60 172 168" fill="#ffffff"/><path d="M164 526c24-77 82-116 136-116s112 39 136 116" fill="#d8e3ee"/><path d="M70 0h92v600H70z" fill="url(#accent)" opacity="0.92"/><path d="M92 120h48v240H92z" fill="#fff" opacity="0.18"/></svg>';
		const speakerPlaceholderSrc = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(speakerPlaceholderSvg);
		const getSpeakerPlaceholderAlt = function() {
			const settings = window.wpfaeventPublic || {};

			if (typeof settings.speakerPlaceholderAlt === 'string' && settings.speakerPlaceholderAlt.trim()) {
				return settings.speakerPlaceholderAlt;
			}

			return 'Speaker photo placeholder';
		};

		const applySpeakerPlaceholder = function() {
			if (!this || this.classList.contains('wpfa-speaker-placeholder-img') || this.src === speakerPlaceholderSrc) {
				return;
			}

			this.removeAttribute('srcset');
			this.removeAttribute('sizes');
			this.classList.add('wpfa-speaker-placeholder-img');
			this.alt = getSpeakerPlaceholderAlt();
			this.src = speakerPlaceholderSrc;
		};

		$('.wpfa-speaker-photo img:not(.wpfa-speaker-placeholder-img), .wpfa-speaker-profile-photo img:not(.wpfa-speaker-placeholder-img)')
			.on('error', applySpeakerPlaceholder)
			.each(function() {
				if (this.complete && this.naturalWidth === 0) {
					applySpeakerPlaceholder.call(this);
				}
			});

		// Bookmark Toggle Handler
		$(document).on('click', '.wpfa-bookmark-btn', function(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(this);
			const eventId = $btn.data('event-id');
			const settings = window.wpfaeventPublic || {};

			if (!settings.isLoggedIn) {
				alert(settings.i18n?.loginRequired || 'Please log in to bookmark events.');
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpfa_toggle_bookmark',
					nonce: settings.nonce,
					event_id: eventId
				},
				success: function(response) {
					$btn.prop('disabled', false);
					if (response.success) {
						const isBookmarked = response.data.bookmarked;

						// Synchronize all bookmark buttons on the page for this specific event
						const $allTargetBtns = $(`.wpfa-bookmark-btn[data-event-id="${eventId}"]`);

						if (isBookmarked) {
							$allTargetBtns.addClass('is-bookmarked');
							$allTargetBtns.each(function() {
								const $thisBtn = $(this);
								if ($thisBtn.hasClass('wpfa-event-bookmark-btn')) {
									$thisBtn.find('.wpfa-bookmark-text').text(settings.i18n?.removeBookmark || 'Remove Bookmark');
								} else {
									$thisBtn.find('.wpfa-bookmark-text').text(settings.i18n?.bookmarked || 'Bookmarked');
								}
							});
						} else {
							$allTargetBtns.removeClass('is-bookmarked');
							$allTargetBtns.each(function() {
								const $thisBtn = $(this);
								if ($thisBtn.hasClass('wpfa-event-bookmark-btn')) {
									$thisBtn.find('.wpfa-bookmark-text').text(settings.i18n?.bookmarkEvent || 'Bookmark Event');
								} else {
									$thisBtn.find('.wpfa-bookmark-text').text(settings.i18n?.bookmark || 'Bookmark');
								}
							});
						}

						// Update dataset attributes on any corresponding event cards
						const $card = $(`.event-card[data-post-id="${eventId}"]`);
						if ($card.length) {
							$card.attr('data-is-bookmarked', isBookmarked ? '1' : '0');
							$card.data('is-bookmarked', isBookmarked ? '1' : '0');
						}

						// Dynamically re-filter events hub if on Favorites tab
						if (window.WPFA_Events && typeof window.WPFA_Events.filterEvents === 'function') {
							const $activeTab = $('.date-filter-btn.active');
							if ($activeTab.length && $activeTab.data('filter') === 'bookmarked') {
								window.WPFA_Events.filterEvents();
							}
						}
					} else {
						alert(response.data?.message || settings.i18n?.error || 'Something went wrong.');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					alert(settings.i18n?.error || 'Something went wrong.');
				}
			});
		});
	});

})( jQuery );
