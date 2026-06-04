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
		$('.wpfa-event-timezone-select').on('change', function() {
			if (this.form) {
				this.form.submit();
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
	});

})( jQuery );
