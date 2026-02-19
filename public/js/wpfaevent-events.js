/**
 * WPFA Events JavaScript Module
 * Handles search, admin functionality for events page
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/js
 */

const WPFA_Events = (function() {
	// Private variables
	let config = {};
	let elements = {};

	/**
	 * Helper to extract error message from AJAX response
	 */
	function getErrorMessage(data, fallback) {
		if (data && typeof data.data === 'object' && data.data?.message) {
			return `${fallback}: ${data.data.message}`;
		}
		if (data && typeof data.data === 'string') {
			return `${fallback}: ${data.data}`;
		}
		return fallback;
	}
	
	/**
	 * Initialize the events module
	 */
	function init(options) {
		config = options || {};

		// Ensure i18n object exists even if PHP fails to provide it
        config.i18n = config.i18n || {};
		
		// Cache DOM elements
		cacheElements();
		
		// Setup event listeners
		setupEventListeners();
		
		// Setup character counters
		setupCharacterCounters();
		
		// Setup admin functionality if user is admin
		if (config.isAdmin) {
			setupAdminFunctionality();
		}
		
		// Setup search functionality
		setupSearch();
	}
	
	/**
	 * Cache DOM elements
	 */
	function cacheElements() {
		elements = {
			// Search
			searchForm: document.querySelector('.wpfa-search-form'),
			searchInput: document.getElementById('eventSearchInput'),
			
			// Admin buttons - Using MVP IDs
			createEventBtn: document.getElementById('createEventBtn'),
			
			// Modals - Using MVP IDs
			createEventModal: document.getElementById('createEventModal'),
			editEventModal: document.getElementById('editEventModal'),
			
			// Modal close buttons
			closeCreateEventModal: document.querySelector('#createEventModal .close-btn'),
			closeEditEventModal: document.querySelector('#editEventModal .close-btn'),
			
			// Forms - Using MVP IDs
			createEventForm: document.getElementById('createEventForm'),
			editEventForm: document.getElementById('editEventForm'),
			
			// Events container
			eventsContainer: document.getElementById('events-container'),
			resultsInfo: document.querySelector('.results-info'),
			resultsCount: document.getElementById('resultsCount'),
		};
	}
	
	/**
	 * Setup event listeners
	 */
	function setupEventListeners() {
		// Create event button
		if (elements.createEventBtn) {
			elements.createEventBtn.addEventListener('click', openCreateEventModal);
		}
		
		// Modal close buttons
		if (elements.closeCreateEventModal) {
			elements.closeCreateEventModal.addEventListener('click', closeCreateEventModal);
		}
		
		if (elements.closeEditEventModal) {
			elements.closeEditEventModal.addEventListener('click', closeEditEventModal);
		}
		
		// Close modals on background click
		if (elements.createEventModal) {
			elements.createEventModal.addEventListener('click', function(e) {
				if (e.target === this) closeCreateEventModal();
			});
		}
		
		if (elements.editEventModal) {
			elements.editEventModal.addEventListener('click', function(e) {
				if (e.target === this) closeEditEventModal();
			});
		}
		
		// Form submissions
		if (elements.createEventForm) {
			elements.createEventForm.addEventListener('submit', handleCreateEventFormSubmit);
		}
		
		if (elements.editEventForm) {
			elements.editEventForm.addEventListener('submit', handleEditEventFormSubmit);
		}
		
		// Event card actions delegation
		if (elements.eventsContainer) {
			elements.eventsContainer.addEventListener('click', handleCardActions);
		}
	}
	
	/**
	 * Setup character counters
	 */
	function setupCharacterCounters() {
		document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
			const counter = textarea.nextElementSibling;
			
			if (counter?.classList.contains('wpfaevent-char-counter')) {
				const update = () => {
					const currentLength = textarea.value.length;
					const maxLength = textarea.maxLength;
					counter.textContent = `${currentLength} / ${maxLength}`;
					
					if (currentLength >= maxLength) {
						counter.classList.add('limit-reached');
					} else {
						counter.classList.remove('limit-reached');
					}
				};
				
				textarea.addEventListener('input', update);
				
				textarea.form?.addEventListener('reset', () => {
					setTimeout(update, 0); 
				});

				// Call update immediately
				update(); 

				/** * If data is loaded dynamically (e.g. via AJAX or WP Modal),
				 * we wait a tiny bit to catch the filled value.
				 */
				if (textarea.value.length === 0) {
					setTimeout(update, 100);
				}
			}
		});
	}
	
	/**
	 * Setup search functionality
	 */
	function setupSearch() {
		if (elements.searchInput) {
			elements.searchInput.addEventListener('keyup', filterEvents);
		}
	}
	
	/**
	 * Filter events based on search input
	 */
	function filterEvents() {
		const searchTerm = elements.searchInput.value.toLowerCase().trim();
		const upcomingCards = elements.eventsContainer.querySelectorAll('.event-card');
		const pastEventsContainer = document.getElementById('past-events-container');
		const pastCards = pastEventsContainer ? pastEventsContainer.querySelectorAll('.event-card') : [];
		
		let upcomingVisibleCount = 0;
		let pastVisibleCount = 0;

		// Filter upcoming events
		upcomingCards.forEach(card => {
			const name = card.dataset.name.toLowerCase();
			const place = card.dataset.place.toLowerCase();
			const description = card.dataset.description.toLowerCase();
			const isVisible = name.includes(searchTerm) || place.includes(searchTerm) || description.includes(searchTerm);
			
			card.style.display = isVisible ? '' : 'none';
			if (isVisible) {
				upcomingVisibleCount++;
			}
		});

		// Filter past events if they exist
		pastCards.forEach(card => {
			const name = card.dataset.name.toLowerCase();
			const place = card.dataset.place.toLowerCase();
			const description = card.dataset.description.toLowerCase();
			const isVisible = name.includes(searchTerm) || place.includes(searchTerm) || description.includes(searchTerm);
			
			card.style.display = isVisible ? '' : 'none';
			if (isVisible) {
				pastVisibleCount++;
			}
		});

		if (searchTerm) {
			elements.resultsInfo.style.display = 'block';
			elements.resultsCount.textContent = upcomingVisibleCount + pastVisibleCount;
		} else {
			elements.resultsInfo.style.display = 'none';
			elements.resultsCount.textContent = upcomingCards.length;
		}
	}
	
	/**
	 * Setup admin functionality
	 */
	function setupAdminFunctionality() {
		// Add admin class to body for styling
		document.body.classList.add('wpfa-is-admin');
	}
	
	/**
	 * Handle event card actions
	 */
	function handleCardActions(e) {
		const target = e.target;
		
		// Edit event button
		if (target.matches('.btn-edit-event') || target.closest('.btn-edit-event')) {
			e.preventDefault();
			e.stopPropagation();
			
			const button = target.matches('.btn-edit-event') ? target : target.closest('.btn-edit-event');
			const card = button.closest('.event-card');
			
			openEditEventModal(card);
		}
		
		// Delete event button
		else if (target.matches('.btn-delete-event') || target.closest('.btn-delete-event')) {
			e.preventDefault();
			e.stopPropagation();
			
			const button = target.matches('.btn-delete-event') ? target : target.closest('.btn-delete-event');
			const eventId = button.closest('.event-card').dataset.postId;
			const eventName = button.closest('.event-card').dataset.name;
			
			deleteEvent(eventId, eventName);
		}
	}
	
	/**
	 * Open modal for creating new event
	 */
	function openCreateEventModal() {
		// Reset form
		if (elements.createEventForm) {
			elements.createEventForm.reset();
			
			// Set today's date as default
			const today = new Date().toISOString().split('T')[0];
			const eventDateInput = document.getElementById('eventDate');
			if (eventDateInput) {
				eventDateInput.value = today;
				eventDateInput.min = today;
			}
			
			// Use the smart function to reset all counters to "0 / max"
			setupCharacterCounters();
		}
		
		// Show modal
		if (elements.createEventModal) {
			elements.createEventModal.style.display = 'flex';
		}
	}
	
	/**
	 * Open modal for editing event
	 */
	function openEditEventModal(card) {
		// Get data from card
		const eventId = card.dataset.postId;
		
		// Fill form with data from card dataset
		document.getElementById('editEventId').value = eventId;
		document.getElementById('editEventName').value = card.dataset.name || '';
		document.getElementById('editEventDate').value = card.dataset.date || '';
		document.getElementById('editEventEndDate').value = card.dataset.endDate || '';
		document.getElementById('editEventPlace').value = card.dataset.place || '';
		document.getElementById('editEventDescription').value = card.dataset.description || '';
		document.getElementById('editEventLeadText').value = card.dataset.leadText || '';
		document.getElementById('editRegistrationLink').value = card.dataset.registrationLink || '';
		document.getElementById('editCfsLink').value = card.dataset.cfsLink || '';
		document.getElementById('editEventTime').value = card.dataset.time || '';
		
		// Sync all counters at once
		// This looks for all textareas and updates their specific counters
		setupCharacterCounters();
		
		// Show modal
		if (elements.editEventModal) {
			elements.editEventModal.style.display = 'flex';
		}
	}
	
	/**
	 * Handle create event form submission
	 */
	function handleCreateEventFormSubmit(e) {
		e.preventDefault();
		
		if (!config.isAdmin) {
			alert(config.i18n.noPermission || 'You do not have permission to perform this action.');
			return;
		}
		
		const form = e.target;
		const formData = new FormData(form);
		const submitBtn = form.querySelector('button[type="submit"]');
		
		// Validate required fields - using ACTUAL form field names
		const requiredFields = ['title', 'excerpt', 'start_date', 'location', 'registration_link'];
		let missingFields = [];
		
		requiredFields.forEach(field => {
			if (!formData.get(field) || formData.get(field).trim() === '') {
				missingFields.push(field);
			}
		});
		
		if (missingFields.length > 0) {
			alert(config.i18n.missingFields || 'Missing required fields: ' + missingFields.join(', '));
			
			// Re-enable button
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = config.i18n.addEventButton || 'Create Card';
			}
			return;
		}
		
		// Disable button during submission
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = config.i18n.creating || 'Creating...';
		}
		
		// Add nonce and AJAX action
		formData.append('action', 'wpfa_add_event');
		formData.append('nonce', config.adminNonce);
		
		// Send form data
		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert(config.i18n.addSuccess || 'Event created successfully. The page will now reload.');
				window.location.reload();
			} else {
				const baseMsg = config.i18n.addError || 'Error creating event';
				alert(getErrorMessage(data, baseMsg));
					
				// Re-enable button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = config.i18n.addEventButton || 'Create Card';
				}
			}
		})
		.catch(error => {
			alert(config.i18n.addErrorGeneric || 'Error creating event. Please try again.');
			
			// Re-enable button
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = config.i18n.addEventButton || 'Create Card';
			}
		});
	}
		
	/**
	 * Handle edit event form submission
	 */
	function handleEditEventFormSubmit(e) {
		e.preventDefault();
		
		if (!config.isAdmin) {
			alert(config.i18n.noPermission || 'You do not have permission to perform this action.');
			return;
		}
		
		const form = e.target;
		const formData = new FormData(form);
		const submitBtn = form.querySelector('button[type="submit"]');
		
		// Validate required fields - using ACTUAL form field names
		const requiredFields = ['title', 'excerpt', 'start_date', 'location', 'registration_link'];
		let missingFields = [];
		
		requiredFields.forEach(field => {
			if (!formData.get(field) || formData.get(field).trim() === '') {
				missingFields.push(field);
			}
		});
		
		if (missingFields.length > 0) {
			alert(config.i18n.missingFields || 'Missing required fields: ' + missingFields.join(', '));
			
			// Re-enable button
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = config.i18n.editEventButton || 'Save Changes';
			}
			return;
		}
		
		// Disable button during submission
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = config.i18n.saving || 'Saving...';
		}
		
		// Add nonce and AJAX action
		formData.append('action', 'wpfa_update_event');
		formData.append('nonce', config.adminNonce);
		
		// Send form data
		fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert(config.i18n.updateSuccess || 'Event updated successfully. The page will now reload.');
				window.location.reload();
			} else {
				const baseMsg = config.i18n.updateError || 'Error updating event';
				alert(getErrorMessage(data, baseMsg));
					
				// Re-enable button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = config.i18n.editEventButton || 'Save Changes';
				}
			}
		})
		.catch(error => {
			alert(config.i18n.updateErrorGeneric || 'Error updating event. Please try again.');
			
			// Re-enable button
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = config.i18n.editEventButton || 'Save Changes';
			}
		});
	}
	
	/**
	 * Delete event confirmation and AJAX call
	 */
	function deleteEvent(eventId, eventName) {
		const confirmMsg = config.i18n.confirmDelete 
			? config.i18n.confirmDelete.replace('%s', eventName)
			: `Are you sure you want to delete "${eventName}"? This action cannot be undone.`;
		
		if (!confirm(confirmMsg)) {
			return;
		}
		
		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'wpfa_delete_event',
				nonce: config.adminNonce,
				event_id: eventId
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert(config.i18n.deleteSuccess || 'Event deleted successfully. The page will now reload.');
				window.location.reload();
			} else {
				const baseMsg = config.i18n.deleteError || 'Error deleting event';
				alert(getErrorMessage(data, baseMsg));
			}
		})
		.catch(error => {
			alert(config.i18n.deleteErrorGeneric || 'Error deleting event. Please try again.');
		});
	}
	
	/**
	 * Close create event modal
	 */
	function closeCreateEventModal() {
		if (elements.createEventModal) {
			elements.createEventModal.style.display = 'none';
		}
	}
	
	/**
	 * Close edit event modal
	 */
	function closeEditEventModal() {
		if (elements.editEventModal) {
			elements.editEventModal.style.display = 'none';
		}
	}
	
	// Public API
	return {
		init: init,
		openCreateEventModal: openCreateEventModal,
		openEditEventModal: openEditEventModal,
		closeCreateEventModal: closeCreateEventModal,
		closeEditEventModal: closeEditEventModal,
		filterEvents: filterEvents
	};
})();

// Export to global
if (typeof window !== 'undefined') {
	window.WPFA_Events = WPFA_Events;
}

// Initialize when page loads
if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', function() {
		// Check if config exists (only on events page)
		if (typeof wpfaeventEventsConfig !== 'undefined') {
			WPFA_Events.init(wpfaeventEventsConfig);
		}
	});
}