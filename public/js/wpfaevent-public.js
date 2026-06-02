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

		const applySpeakerPlaceholder = function() {
			const placeholderSrc = this.getAttribute('data-wpfa-placeholder-src');

			if (!placeholderSrc || this.src === placeholderSrc) {
				return;
			}

			this.src = placeholderSrc;
			this.removeAttribute('srcset');
			this.removeAttribute('sizes');
			this.classList.add('wpfa-speaker-placeholder-img');

			const placeholderAlt = this.getAttribute('data-wpfa-placeholder-alt');
			if (placeholderAlt) {
				this.alt = placeholderAlt;
			}
		};

		$('.wpfa-speaker-photo img[data-wpfa-placeholder-src], .wpfa-speaker-profile-photo img[data-wpfa-placeholder-src]')
			.on('error', applySpeakerPlaceholder)
			.each(function() {
				if (this.complete && this.naturalWidth === 0) {
					applySpeakerPlaceholder.call(this);
				}
			});
	});

})( jQuery );
