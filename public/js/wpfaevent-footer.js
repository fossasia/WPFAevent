/**
 * WPFA Footer JavaScript Module
 * Handles footer functionality across all pages
 *
 * @package
 */

const wpfaFooter = (function () {
	// Private variables
	let config = {};
	let elements = {};

	function showNotice(message, type = 'error') {
		const container =
			document.querySelector('.wpfaevent-notification-container') ||
			document.querySelector('.wpfa-site-footer') ||
			document.body;
		const notice = document.createElement('div');
		const noticeMessage = document.createElement('p');

		notice.className = `notice notice-${type} is-dismissible wpfaevent-footer-notice`;
		noticeMessage.textContent = message;
		notice.appendChild(noticeMessage);

		container
			.querySelectorAll('.wpfaevent-footer-notice')
			.forEach((existingNotice) => existingNotice.remove());
		container.insertBefore(notice, container.firstChild);
	}

	// Private Helper for consistency with events module
	function getErrorMessage(data, fallback) {
		if (
			data &&
			typeof data.data === 'object' &&
			data.data !== null &&
			data.data.message
		) {
			return `${fallback}: ${data.data.message}`;
		}
		if (data && typeof data.data === 'string') {
			return `${fallback}: ${data.data}`;
		}
		return fallback;
	}

	/**
	 * Initialize the footer module
	 *
	 * @param {Object} options Footer page configuration.
	 */
	function init(options) {
		config = options || {};

		// Ensure i18n object exists
		config.i18n = config.i18n || {};

		// Cache DOM elements
		cacheElements();

		// Setup event listeners
		setupEventListeners();
	}

	/**
	 * Cache DOM elements
	 */
	function cacheElements() {
		elements = {
			editFooterBtn: document.getElementById('edit-footer-btn'),
			footerModal: document.getElementById('edit-footer-modal'),
			closeFooterModal: document.querySelector(
				'#edit-footer-modal .close-btn'
			),
			footerForm: document.getElementById('edit-footer-form'),
		};
	}

	/**
	 * Setup event listeners
	 */
	function setupEventListeners() {
		// Edit footer button
		if (elements.editFooterBtn) {
			elements.editFooterBtn.addEventListener('click', openFooterModal);
		}

		// Modal close button
		if (elements.closeFooterModal) {
			elements.closeFooterModal.addEventListener(
				'click',
				closeFooterModal
			);
		}

		// Close modal on background click
		if (elements.footerModal) {
			elements.footerModal.addEventListener('click', function (e) {
				if (e.target === this) {
					closeFooterModal();
				}
			});
		}

		// Form submission
		if (elements.footerForm) {
			elements.footerForm.addEventListener(
				'submit',
				handleFooterFormSubmit
			);
		}
	}

	/**
	 * Open footer modal
	 */
	function openFooterModal() {
		// Get current footer text
		const footerTextElement = document.getElementById(
			'footer-text-display'
		);
		const footerTextInput = document.getElementById('footer-text');

		if (footerTextElement && footerTextInput) {
			footerTextInput.value = footerTextElement.textContent.trim();
		}

		if (elements.footerModal) {
			elements.footerModal.style.display = 'flex';
		}
	}

	/**
	 * Handle footer form submission
	 *
	 * @param {SubmitEvent} e Footer form submit event.
	 */
	function handleFooterFormSubmit(e) {
		e.preventDefault();

		const form = e.target;
		const formData = new FormData(form);
		const submitBtn = form.querySelector('button[type="submit"]');

		// Disable button during submission
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = config.i18n.saving || 'Saving...';
		}

		// Add AJAX action and nonce
		formData.append('action', 'wpfa_update_footer_text');
		formData.append('nonce', config.adminNonce);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					// Update footer text on page
					const footerTextElement = document.getElementById(
						'footer-text-display'
					);
					if (footerTextElement) {
						footerTextElement.textContent =
							formData.get('footer_text');
					}

					showNotice(
						config.i18n.footerSaveSuccess ||
							'Footer text updated successfully.',
						'success'
					);
					closeFooterModal();
				} else {
					const baseMsg =
						config.i18n.footerSaveError ||
						'Error updating footer text';
					showNotice(getErrorMessage(data, baseMsg));
				}

				// Re-enable button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent =
						config.i18n.saveFooter || 'Save Footer';
				}
			})
			.catch(() => {
				showNotice(
					config.i18n.footerSaveError || 'Error updating footer text.'
				);

				// Re-enable button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent =
						config.i18n.saveFooter || 'Save Footer';
				}
			});
	}

	/**
	 * Close footer modal
	 */
	function closeFooterModal() {
		if (elements.footerModal) {
			elements.footerModal.style.display = 'none';
		}
	}

	// Public API
	return {
		init,
		openFooterModal,
		closeFooterModal,
	};
})();

// Export to global
if (typeof window !== 'undefined') {
	Object.assign(window, {
		WPFA_Footer: wpfaFooter,
		wpfaFooter,
	});
}

// Initialize when page loads
if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', function () {
		// Check if config exists (footer exists on all template pages)
		if (typeof wpfaeventFooterConfig !== 'undefined') {
			wpfaFooter.init(wpfaeventFooterConfig);
		}
	});
}
