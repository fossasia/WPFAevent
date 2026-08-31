/**
 * WPFA Speakers JavaScript Module
 * Handles search, filtering, and admin functionality for speakers page
 *
 * @package
 */

const wpfaSpeakers = (function () {
	// Private variables
	let config = {};
	let speakers = [];
	let currentFilter = 'all';
	let searchTerm = '';

	// DOM Elements cache
	let elements = {};

	function showNotice(message, type = 'error') {
		const container =
			document.querySelector('.wpfaevent-notification-container') ||
			document.querySelector('.wpfa-speakers-page') ||
			document.body;
		const notice = document.createElement('div');
		const noticeMessage = document.createElement('p');

		notice.className = `notice notice-${type} is-dismissible wpfaevent-speakers-notice`;
		noticeMessage.textContent = message;
		notice.appendChild(noticeMessage);

		container
			.querySelectorAll('.wpfaevent-speakers-notice')
			.forEach((existingNotice) => existingNotice.remove());
		container.insertBefore(notice, container.firstChild);
		notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	function showDeleteConfirmation(message, onConfirm) {
		const overlay = document.createElement('div');
		const dialog = document.createElement('div');
		const text = document.createElement('p');
		const actions = document.createElement('div');
		const cancelButton = document.createElement('button');
		const confirmButton = document.createElement('button');

		overlay.className = 'wpfaevent-confirm-overlay';
		overlay.setAttribute(
			'style',
			'position:fixed;inset:0;background:rgba(15,23,42,0.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:10000;'
		);
		dialog.setAttribute(
			'style',
			'background:#fff;border-radius:12px;box-shadow:0 24px 48px rgba(15,23,42,0.2);max-width:420px;width:100%;padding:24px;'
		);
		text.textContent = message;
		actions.setAttribute(
			'style',
			'display:flex;gap:12px;justify-content:flex-end;margin-top:20px;'
		);
		cancelButton.type = 'button';
		cancelButton.className = 'button';
		cancelButton.textContent = config.i18n?.cancelDelete || 'Cancel';
		confirmButton.type = 'button';
		confirmButton.className = 'button button-primary';
		confirmButton.textContent =
			config.i18n?.confirmDeleteButton || 'Delete';

		const closeDialog = function () {
			overlay.remove();
		};

		cancelButton.addEventListener('click', closeDialog);
		overlay.addEventListener('click', function (event) {
			if (event.target === overlay) {
				closeDialog();
			}
		});
		confirmButton.addEventListener('click', function () {
			closeDialog();
			onConfirm();
		});

		actions.append(cancelButton, confirmButton);
		dialog.append(text, actions);
		overlay.appendChild(dialog);
		document.body.appendChild(overlay);
		confirmButton.focus();
	}

	/**
	 * Initialize the speakers module
	 * @param {Object} options Configuration options
	 */
	function init(options) {
		config = options || {};

		// Collect speakers data from the page if not provided
		if (!config.speakersData || config.speakersData.length === 0) {
			config.speakersData = collectSpeakersFromPage();
		}

		speakers = config.speakersData || [];

		// Cache DOM elements
		cacheElements();

		// Setup event listeners
		setupEventListeners();

		// Setup intersection observer for animations
		setupIntersectionObserver();

		// Setup admin functionality if user is admin
		if (config.isAdmin) {
			setupAdminFunctionality();
		}

		// Initial render with client-side filtering
		filterAndRenderSpeakers();
	}

	/**
	 * Collect speaker data from page DOM
	 */
	function collectSpeakersFromPage() {
		const speakerCards = document.querySelectorAll('.wpfa-speaker-card');
		return Array.from(speakerCards).map((card) => {
			const name =
				card.querySelector('.wpfa-speaker-name')?.textContent?.trim() ||
				'';
			const role =
				card.querySelector('.wpfa-speaker-role')?.textContent?.trim() ||
				'';
			const bio =
				card.querySelector('.wpfa-speaker-bio')?.textContent?.trim() ||
				'';
			const photo = card.querySelector('img')?.src || '';
			const link = card.querySelector('.wpfa-speaker-name a')?.href || '';
			const speakerId = card.dataset.speakerId || '';

			return {
				id: speakerId,
				name,
				position: role,
				bio,
				image: photo,
				link,
				category: '',
				// Extract organization from position string (e.g., "Developer · Company")
				organization: role.includes('·')
					? role.split('·')[1]?.trim()
					: '',
				element: card,
			};
		});
	}

	/**
	 * Cache frequently used DOM elements
	 */
	function cacheElements() {
		elements = {
			speakerGrid: document.getElementById('wpfa-speakers-grid'),
			searchInput: document.getElementById('wpfa-speaker-search'),
			filterButtons: document.querySelectorAll('.wpfa-filter-btn'),
			resultsInfo: document.querySelector('.wpfa-results-info'),
			noResults: document.querySelector('.wpfa-no-results'),
			modal: document.getElementById('wpfa-speaker-modal'),
			modalClose: document.querySelector('.wpfa-modal-close'),
			speakerForm: document.getElementById('wpfa-speaker-form'),
			imageSourceToggle: document.querySelectorAll(
				'input[name="image_source"]'
			),
			imageUrlGroup: document.getElementById('wpfa-image-url-group'),
			imageUploadGroup: document.getElementById(
				'wpfa-image-upload-group'
			),
			addSpeakerBtn: document.getElementById('wpfa-add-speaker-btn'),
		};

		// If search input doesn't exist in form, look for standalone search
		if (!elements.searchInput) {
			elements.searchInput = document.querySelector(
				'input[type="search"]'
			);
		}
	}

	/**
	 * Setup event listeners
	 */
	function setupEventListeners() {
		// Real-time search functionality
		if (elements.searchInput) {
			elements.searchInput.addEventListener('input', handleSearch);
		}

		// Filter buttons
		if (elements.filterButtons.length > 0) {
			elements.filterButtons.forEach((button) => {
				button.addEventListener('click', handleFilterClick);
			});
		}

		// Card click for expand/collapse and admin actions
		if (elements.speakerGrid) {
			elements.speakerGrid.addEventListener('click', handleCardClick);
		} else {
			document.querySelectorAll('.wpfa-speakers-grid').forEach((grid) => {
				grid.addEventListener('click', handleCardClick);
			});
		}

		// Modal functionality
		if (elements.modalClose) {
			elements.modalClose.addEventListener('click', closeModal);
		}

		// Close modal on background click
		if (elements.modal) {
			elements.modal.addEventListener('click', function (e) {
				if (e.target === this) {
					closeModal();
				}
			});
		}

		// Category dropdown toggle for custom category
		const categorySelect = document.getElementById('wpfa-speaker-category');
		const customCategoryInput = document.getElementById(
			'wpfa-speaker-category-custom'
		);

		if (categorySelect && customCategoryInput) {
			categorySelect.addEventListener('change', function () {
				if (this.value === '_custom') {
					customCategoryInput.style.display = 'block';
					customCategoryInput.required = true;
				} else {
					customCategoryInput.style.display = 'none';
					customCategoryInput.required = false;
					customCategoryInput.value = '';
				}
			});
		}

		// Image source toggle
		if (elements.imageSourceToggle.length > 0) {
			elements.imageSourceToggle.forEach((radio) => {
				radio.addEventListener('change', handleImageSourceChange);
			});
		}

		// Form submission
		if (elements.speakerForm) {
			elements.speakerForm.addEventListener('submit', handleFormSubmit);
		}

		// Add speaker button
		if (elements.addSpeakerBtn) {
			elements.addSpeakerBtn.addEventListener('click', openAddModal);
		}
	}

	/**
	 * Handle real-time search input
	 *
	 * @param {Event} e Input event from the speaker search field.
	 */
	function handleSearch(e) {
		searchTerm = e.target.value.toLowerCase().trim();
		filterAndRenderSpeakers();
	}

	/**
	 * Handle filter button click
	 *
	 * @param {Event} e Click event from a filter button.
	 */
	function handleFilterClick(e) {
		// Update active button
		elements.filterButtons.forEach((btn) => btn.classList.remove('active'));
		e.target.classList.add('active');

		// Update current filter
		currentFilter = e.target.dataset.filter;

		// Re-render speakers
		filterAndRenderSpeakers();
	}

	/**
	 * Handle card click for expand/collapse and admin actions
	 *
	 * @param {Event} e Click event from the speaker grid.
	 */
	function handleCardClick(e) {
		// Handle edit button click
		if (
			e.target.matches('.btn-edit-speaker') ||
			e.target.closest('.btn-edit-speaker')
		) {
			e.preventDefault();
			e.stopPropagation();

			const button = e.target.matches('.btn-edit-speaker')
				? e.target
				: e.target.closest('.btn-edit-speaker');
			const speakerId = button.dataset.id;
			openEditModal(speakerId);
			return;
		}

		// Handle delete button click
		if (
			e.target.matches('.btn-delete-speaker') ||
			e.target.closest('.btn-delete-speaker')
		) {
			e.preventDefault();
			e.stopPropagation();

			const button = e.target.matches('.btn-delete-speaker')
				? e.target
				: e.target.closest('.btn-delete-speaker');
			const speakerId = button.dataset.id;
			const speakerName = button.dataset.name;

			deleteSpeaker(speakerId, speakerName);
			return;
		}

		// Handle card expand/collapse on card-meta click
		const cardMeta = e.target.closest('.wpfa-speaker-meta');
		if (cardMeta) {
			const card = cardMeta.closest('.wpfa-speaker-card');
			if (card) {
				card.classList.toggle('expanded');
			}
		}
	}

	/**
	 * Handle image source radio change
	 *
	 * @param {Event} e Change event from the image source radio field.
	 */
	function handleImageSourceChange(e) {
		const source = e.target.value;
		const imageUrlInput = document.getElementById('wpfa-speaker-image-url');
		const imageUploadInput = document.getElementById(
			'wpfa-speaker-image-upload'
		);

		if (source === 'url') {
			elements.imageUrlGroup.style.display = 'block';
			elements.imageUploadGroup.style.display = 'none';
			if (imageUrlInput) {
				imageUrlInput.required = true;
			}
			if (imageUploadInput) {
				imageUploadInput.required = false;
			}
		} else {
			elements.imageUrlGroup.style.display = 'none';
			elements.imageUploadGroup.style.display = 'block';
			if (imageUrlInput) {
				imageUrlInput.required = false;
			}
			if (imageUploadInput) {
				imageUploadInput.required = true;
			}
		}
	}

	/**
	 * Filter speakers based on current filter and search term
	 */
	function filterSpeakers() {
		// Cache active filter value once (optimization)
		const activeFilterBtn = document.querySelector(
			'.wpfa-filter-btn.active'
		);
		const filterValue = activeFilterBtn
			? (activeFilterBtn.getAttribute('data-filter') || '').toLowerCase()
			: '';

		return speakers.filter((speaker) => {
			// Apply category filter
			if (currentFilter !== 'all') {
				const speakerCategory = speaker.category
					? speaker.category.toLowerCase()
					: '';

				if (
					speakerCategory !== filterValue &&
					currentFilter !== 'all'
				) {
					return false;
				}
			}

			// Apply search filter
			if (searchTerm) {
				const searchableFields = [
					speaker.name,
					speaker.position,
					speaker.category,
					speaker.bio,
					speaker.organization,
				];

				return searchableFields.some(
					(field) => field && field.toLowerCase().includes(searchTerm)
				);
			}

			return true;
		});
	}

	/**
	 * Filter and render speakers in real-time
	 */
	function filterAndRenderSpeakers() {
		const filteredSpeakers = filterSpeakers();

		// Hide all speakers first
		speakers.forEach((speaker) => {
			if (speaker.element) {
				speaker.element.style.display = 'none';
			}
		});

		// Show filtered speakers
		filteredSpeakers.forEach((speaker) => {
			if (speaker.element) {
				speaker.element.style.removeProperty('display');
			}
		});

		// Update results count
		updateResultsCount(filteredSpeakers.length);

		// Show/hide no results message
		showNoResults(filteredSpeakers.length === 0);
	}

	/**
	 * Get localized results count text
	 * Falls back to English if localization is not available
	 *
	 * Expects PHP to provide, via wp_localize_script(), an object like:
	 * window.wpfaeventSpeakersL10n = {
	 *     resultsCount: "Showing %d speakers"
	 * };
	 *
	 * @param {number} count
	 * @return {string} Localized speaker count label.
	 */
	function getResultsCountText(count) {
		if (
			typeof window !== 'undefined' &&
			window.wpfaeventSpeakersL10n &&
			typeof window.wpfaeventSpeakersL10n.resultsCount === 'string'
		) {
			return window.wpfaeventSpeakersL10n.resultsCount.replace(
				'%d',
				count
			);
		}

		// Fallback to English if no localized string is available
		return 'Showing ' + count + ' speakers';
	}

	/**
	 * Update results count display
	 *
	 * @param {number} count Number of speakers currently shown.
	 */
	function updateResultsCount(count) {
		if (elements.resultsInfo) {
			elements.resultsInfo.textContent = getResultsCountText(count);
		}
	}

	/**
	 * Show/hide no results message
	 *
	 * @param {boolean} show Whether the empty state should be shown.
	 */
	function showNoResults(show) {
		if (elements.noResults) {
			elements.noResults.style.display = show ? 'block' : 'none';
		}
	}

	/**
	 * Setup intersection observer for animations
	 */
	function setupIntersectionObserver() {
		if (typeof window.IntersectionObserver !== 'function') {
			return;
		}

		const observer = new window.IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						entry.target.classList.add('visible');
						observer.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.1 }
		);

		// Observe all speaker cards
		document
			.querySelectorAll('.wpfa-speaker-card:not(.visible)')
			.forEach((card) => {
				observer.observe(card);
			});
	}

	/**
	 * Setup admin functionality
	 */
	function setupAdminFunctionality() {
		// Add "Add Speaker" button if not already present
		addAddSpeakerButton();
	}

	/**
	 * Add "Add Speaker" button
	 */
	function addAddSpeakerButton() {
		const container = document.querySelector(
			'.wpfa-speakers-hero .container'
		);
		if (!container) {
			return;
		}

		// Check if button already exists
		if (document.getElementById('wpfa-add-speaker-btn')) {
			return;
		}

		const addButton = document.createElement('button');
		addButton.id = 'wpfa-add-speaker-btn';
		addButton.className = 'wpfa-add-speaker-btn';
		addButton.innerHTML = '+ Add New Speaker';

		// Insert after the filters
		const filters = container.querySelector('.wpfa-speakers-filters');
		if (filters) {
			filters.after(addButton);
		} else {
			const searchForm = container.querySelector('.wpfa-speakers-search');
			if (searchForm) {
				searchForm.after(addButton);
			} else {
				container.appendChild(addButton);
			}
		}

		// Update cached element
		elements.addSpeakerBtn = addButton;

		// Add event listener
		addButton.addEventListener('click', openAddModal);
	}

	/**
	 * Open edit modal with speaker data
	 *
	 * @param {string} speakerId Speaker post ID.
	 */
	function openEditModal(speakerId) {
		// Open the modal immediately
		openModal();

		// Set temporary title
		const modalTitle = document.getElementById('wpfa-modal-title');
		if (modalTitle) {
			modalTitle.textContent = 'Loading...';
		}

		// Fetch speaker data via AJAX
		fetchSpeakerData(speakerId)
			.then((speaker) => {
				if (!speaker) {
					closeModal();
					const errorMsg =
						config.i18n && config.i18n.loadError
							? config.i18n.loadError
							: 'Error loading speaker data';
					showNotice(errorMsg);
					return;
				}

				// Update modal title
				if (modalTitle) {
					modalTitle.textContent = 'Edit Speaker: ' + speaker.name;
				}

				// Update form action
				const modalAction =
					document.getElementById('wpfa-modal-action');
				if (modalAction) {
					modalAction.value = 'edit';
				}

				// Set speaker ID
				const speakerIdInput =
					document.getElementById('wpfa-speaker-id');
				if (speakerIdInput) {
					speakerIdInput.value = speakerId;
				}

				// Fill form with speaker data
				fillFormWithSpeakerData(speaker);

				// Update submit button text
				const submitText = document.getElementById('wpfa-submit-text');
				if (submitText) {
					submitText.textContent = 'Save Changes';
				}
			})
			.catch(() => {
				closeModal();
				showNotice('Error loading speaker data');
			});
	}

	/**
	 * Fetch speaker data via AJAX
	 *
	 * @param {string} speakerId Speaker post ID.
	 * @return {Promise<Object|null>} Speaker payload or null when loading fails.
	 */
	function fetchSpeakerData(speakerId) {
		const formData = new URLSearchParams({
			action: 'wpfa_get_speaker',
			nonce: config.adminNonce,
			speaker_id: speakerId,
		});

		return fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					return data.data;
				}
				const errorMsg =
					config.i18n && config.i18n.fetchError
						? config.i18n.fetchError + ': ' + data.data
						: 'Error fetching speaker data: ' + data.data;
				showNotice(errorMsg);
				return null;
			})
			.catch(() => {
				const errorMsg =
					config.i18n && config.i18n.fetchErrorGeneric
						? config.i18n.fetchErrorGeneric
						: 'Error fetching speaker data. Please try again.';
				showNotice(errorMsg);
				return null;
			});
	}

	/**
	 * Fill form with speaker data
	 *
	 * @param {Object} speaker Speaker payload returned by the AJAX endpoint.
	 */
	function fillFormWithSpeakerData(speaker) {
		const form = elements.speakerForm;
		if (!form) {
			return;
		}

		// Fill basic fields
		const basicFields = ['name', 'position', 'organization', 'bio'];
		basicFields.forEach((field) => {
			const input = form.querySelector(`[name="${field}"]`);
			if (input && speaker[field]) {
				input.value = speaker[field];
			}
		});

		// Fill category - UPDATED to use slug
		const categoryInput = form.querySelector('[name="category"]');
		if (categoryInput && speaker.category_slug) {
			// Check if category exists in select options
			const options = Array.from(categoryInput.options);
			const exists = options.some(
				(option) => option.value === speaker.category_slug
			);

			if (exists) {
				categoryInput.value = speaker.category_slug;
			} else if (speaker.category) {
				// If slug doesn't exist but we have a category name, use custom
				categoryInput.value = '_custom';
				const customInput = form.querySelector(
					'[name="category_custom"]'
				);
				if (customInput) {
					customInput.style.display = 'block';
					customInput.value = speaker.category;
					customInput.required = true;
				}
			}
		}

		// Fill session details
		const sessionFields = {
			talk_title: speaker.talk_title,
			talk_date: speaker.talk_date,
			talk_time: speaker.talk_time,
			talk_end_time: speaker.talk_end_time,
			talk_abstract: speaker.talk_abstract,
		};

		Object.entries(sessionFields).forEach(([field, value]) => {
			const input = form.querySelector(`[name="${field}"]`);
			if (input && value) {
				input.value = value;
			}
		});

		// Fill image URL
		const imageUrlInput = form.querySelector('[name="image_url"]');
		if (imageUrlInput && speaker.headshot_url) {
			imageUrlInput.value = speaker.headshot_url;
		}

		// Set image source to URL
		const urlRadio = form.querySelector('input[value="url"]');
		if (urlRadio) {
			urlRadio.checked = true;
			handleImageSourceChange({ target: urlRadio });
		}

		// Fill social links
		const socialFields = ['linkedin', 'twitter', 'github', 'website'];
		socialFields.forEach((field) => {
			const input = form.querySelector(`[name="${field}"]`);
			if (input && speaker[field]) {
				input.value = speaker[field];
			}
		});
	}

	/**
	 * Open modal for adding new speaker
	 */
	function openAddModal() {
		// Open modal immediately
		openModal();

		// Reset form
		if (elements.speakerForm) {
			elements.speakerForm.reset();
		}

		// Update modal title
		const modalTitle = document.getElementById('wpfa-modal-title');
		if (modalTitle) {
			modalTitle.textContent = 'Add New Speaker';
		}

		// Update form action
		const modalAction = document.getElementById('wpfa-modal-action');
		if (modalAction) {
			modalAction.value = 'add';
		}

		// Clear speaker ID
		const speakerIdInput = document.getElementById('wpfa-speaker-id');
		if (speakerIdInput) {
			speakerIdInput.value = '';
		}

		// Set image source to URL by default
		if (elements.speakerForm) {
			const urlRadio =
				elements.speakerForm?.querySelector('input[value="url"]');
			if (urlRadio) {
				urlRadio.checked = true;
				handleImageSourceChange({ target: urlRadio });
			}
		}

		// Update submit button text
		const submitText = document.getElementById('wpfa-submit-text');
		if (submitText) {
			submitText.textContent = 'Add Speaker';
		}
	}

	/**
	 * Open modal
	 */
	function openModal() {
		if (elements.modal) {
			// Use CSS class to show modal
			elements.modal.classList.add('show');
			// Also set inline style as backup
			elements.modal.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}
	}

	/**
	 * Close modal
	 */
	function closeModal() {
		if (elements.modal) {
			// Remove CSS class
			elements.modal.classList.remove('show');
			// Also set inline style
			elements.modal.style.display = 'none';
			document.body.style.overflow = '';
		}
	}

	/**
	 * Delete speaker confirmation and AJAX call
	 *
	 * @param {string} speakerId   Speaker post ID.
	 * @param {string} speakerName Speaker name for the confirmation copy.
	 */
	function deleteSpeaker(speakerId, speakerName) {
		// Get localized confirmation message
		const confirmMsg =
			config.i18n && config.i18n.confirmDelete
				? config.i18n.confirmDelete.replace('%s', speakerName)
				: `Are you sure you want to delete "${speakerName}"? This action cannot be undone.`;

		showDeleteConfirmation(confirmMsg, function () {
			fetch(config.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'wpfa_delete_speaker',
					nonce: config.adminNonce,
					speaker_id: speakerId,
				}),
			})
				.then((response) => response.json())
				.then((data) => {
					if (data.success) {
						window.location.reload();
					} else {
						const errorMsg =
							config.i18n && config.i18n.deleteError
								? config.i18n.deleteError + ': ' + data.data
								: 'Error deleting speaker: ' + data.data;
						showNotice(errorMsg);
					}
				})
				.catch(() => {
					const errorMsg =
						config.i18n && config.i18n.deleteErrorGeneric
							? config.i18n.deleteErrorGeneric
							: 'Error deleting speaker. Please try again.';
					showNotice(errorMsg);
				});
		});
	}

	/**
	 * Handle form submission
	 *
	 * @param {SubmitEvent} e Speaker form submit event.
	 */
	function handleFormSubmit(e) {
		e.preventDefault();

		if (!config.isAdmin) {
			const errorMsg =
				config.i18n && config.i18n.noPermission
					? config.i18n.noPermission
					: 'You do not have permission to perform this action.';
			showNotice(errorMsg);
			return;
		}

		if (!elements.speakerForm) {
			showNotice('Speaker form element not found');
			return;
		}

		const formData = new FormData(elements.speakerForm);
		const action = formData.get('action');
		const submitBtn = elements.speakerForm.querySelector(
			'button[type="submit"]'
		);

		// Disable button during submission
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.innerHTML = action === 'add' ? 'Adding...' : 'Saving...';
		}

		if (action === 'add') {
			addSpeaker(formData);
		} else {
			updateSpeaker(formData);
		}
	}

	/**
	 * Add new speaker via AJAX
	 *
	 * @param {FormData} formData Speaker form data.
	 */
	function addSpeaker(formData) {
		// Add nonce and action to FormData
		formData.append('action', 'wpfa_add_speaker');
		formData.append('nonce', config.adminNonce);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData, // Send FormData directly (supports files)
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					window.location.reload();
				} else {
					const errorMsg =
						config.i18n && config.i18n.addError
							? config.i18n.addError + ': ' + data.data
							: 'Error adding speaker: ' + data.data;
					showNotice(errorMsg);
					const submitBtn = elements.speakerForm?.querySelector(
						'button[type="submit"]'
					);
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.innerHTML = 'Add Speaker';
					}
				}
			})
			.catch(() => {
				const errorMsg =
					config.i18n && config.i18n.addErrorGeneric
						? config.i18n.addErrorGeneric
						: 'Error adding speaker. Please try again.';
				showNotice(errorMsg);
				const submitBtn = elements.speakerForm?.querySelector(
					'button[type="submit"]'
				);
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerHTML = 'Add Speaker';
				}
			});
	}

	/**
	 * Update existing speaker via AJAX
	 *
	 * @param {FormData} formData Speaker form data.
	 */
	function updateSpeaker(formData) {
		// Add nonce and action to FormData
		formData.append('action', 'wpfa_update_speaker');
		formData.append('nonce', config.adminNonce);

		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData, // Send FormData directly (supports files)
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					window.location.reload();
				} else {
					const errorMsg =
						config.i18n && config.i18n.updateError
							? config.i18n.updateError + ': ' + data.data
							: 'Error updating speaker: ' + data.data;
					showNotice(errorMsg);
					const submitBtn = elements.speakerForm?.querySelector(
						'button[type="submit"]'
					);
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.innerHTML = 'Save Changes';
					}
				}
			})
			.catch(() => {
				const errorMsg =
					config.i18n && config.i18n.updateErrorGeneric
						? config.i18n.updateErrorGeneric
						: 'Error updating speaker. Please try again.';
				showNotice(errorMsg);
				const submitBtn = elements.speakerForm?.querySelector(
					'button[type="submit"]'
				);
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerHTML = 'Save Changes';
				}
			});
	}

	// Public API
	return {
		init,
		openAddModal,
		openEditModal,
		closeModal,
		filterAndRenderSpeakers,
	};
})();

// Export to global window object for browser use
if (typeof window !== 'undefined') {
	Object.assign(window, {
		WPFA_Speakers: wpfaSpeakers,
		wpfaSpeakers,
	});
}

// Initialize when page loads
if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', function () {
		// Check if config exists (only on speakers page)
		if (typeof wpfaeventSpeakersConfig !== 'undefined') {
			wpfaSpeakers.init(wpfaeventSpeakersConfig);
		}
	});
}
