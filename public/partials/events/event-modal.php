<?php

/**
 * Event Modal Partial
 * Unified modal for creating and editing events.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials/events
 * @since      1.0.0
 * @author     FOSSASIA <contact@fossasia.org>
 */

/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<!-- Create Event Modal -->
<div id="createEventModal" class="modal">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="createEventForm">
			<h2><?php esc_html_e( 'Create a New Event', 'wpfaevent' ); ?></h2>
			
			<label for="eventName"><?php esc_html_e( 'Event Name:', 'wpfaevent' ); ?></label>
			<input type="text" id="eventName" name="title" required>
			
			<label for="eventDate"><?php esc_html_e( 'Event Date:', 'wpfaevent' ); ?></label>
			<input type="date" id="eventDate" name="start_date" required>
			
			<label for="eventEndDate"><?php esc_html_e( 'Event End Date (optional):', 'wpfaevent' ); ?></label>
			<input type="date" id="eventEndDate" name="end_date">
			
			<label for="eventTime"><?php esc_html_e( 'Event Time:', 'wpfaevent' ); ?></label>
			<input type="time" id="eventTime" name="time" required>
			
			<label for="eventPlace"><?php esc_html_e( 'Event Place:', 'wpfaevent' ); ?></label>
			<input type="text" id="eventPlace" name="location" required>
			
			<label for="eventDescription"><?php esc_html_e( 'Description:', 'wpfaevent' ); ?></label>
			<textarea id="eventDescription" name="excerpt" rows="3" required maxlength="300"></textarea>
			<small class="wpfaevent-char-counter">0 / 300</small>
			
			<label for="eventLeadText"><?php esc_html_e( 'Hero Lead Text:', 'wpfaevent' ); ?></label>
			<textarea id="eventLeadText" name="lead_text" rows="2" required maxlength="160"></textarea>
			<small class="wpfaevent-char-counter">0 / 160</small>
			
			<label for="eventRegistrationLink"><?php esc_html_e( 'Registration Link:', 'wpfaevent' ); ?></label>
			<input type="url" id="eventRegistrationLink" name="registration_link" placeholder="https://eventyay.com/e/..." required>
			
			<label for="eventCfsLink"><?php esc_html_e( 'Call for Speakers Link (optional):', 'wpfaevent' ); ?></label>
			<input type="url" id="eventCfsLink" name="cfs_link" placeholder="https://eventyay.com/e/.../cfs">
			
			<label for="eventPicture"><?php esc_html_e( 'Event Picture (Required):', 'wpfaevent' ); ?></label>
			<input type="file" id="eventPicture" name="featured_image" accept="image/*" required>
			
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Create Card', 'wpfaevent' ); ?></button>
		</form>
	</div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="editEventForm">
			<h2><?php esc_html_e( 'Edit Event', 'wpfaevent' ); ?></h2>
			<input type="hidden" id="editEventId" name="event_id">
			
			<label for="editEventName"><?php esc_html_e( 'Event Name:', 'wpfaevent' ); ?></label>
			<input type="text" id="editEventName" name="title" required>
			
			<label for="editEventDate"><?php esc_html_e( 'Event Date:', 'wpfaevent' ); ?></label>
			<input type="date" id="editEventDate" name="start_date" required>
			
			<label for="editEventEndDate"><?php esc_html_e( 'Event End Date (optional):', 'wpfaevent' ); ?></label>
			<input type="date" id="editEventEndDate" name="end_date">
			
			<label for="editEventTime"><?php esc_html_e( 'Event Time:', 'wpfaevent' ); ?></label>
			<input type="time" id="editEventTime" name="time" required>
			
			<label for="editEventPlace"><?php esc_html_e( 'Event Place:', 'wpfaevent' ); ?></label>
			<input type="text" id="editEventPlace" name="location" required>
			
			<label for="editEventDescription"><?php esc_html_e( 'Description:', 'wpfaevent' ); ?></label>
			<textarea id="editEventDescription" name="excerpt" rows="3" required maxlength="300"></textarea>
			<small class="wpfaevent-char-counter">0 / 300</small>
			
			<label for="editEventLeadText"><?php esc_html_e( 'Hero Lead Text:', 'wpfaevent' ); ?></label>
			<textarea id="editEventLeadText" name="lead_text" rows="2" required maxlength="160"></textarea>
			<small class="wpfaevent-char-counter">0 / 160</small>
			
			<label for="editRegistrationLink"><?php esc_html_e( 'Registration Link:', 'wpfaevent' ); ?></label>
			<input type="url" id="editRegistrationLink" name="registration_link" placeholder="https://eventyay.com/e/..." required>
			
			<label for="editCfsLink"><?php esc_html_e( 'Call for Speakers Link (optional):', 'wpfaevent' ); ?></label>
			<input type="url" id="editCfsLink" name="cfs_link" placeholder="https://eventyay.com/e/.../cfs">
			
			<label for="editEventPicture"><?php esc_html_e( 'Event Picture (optional, only if updating):', 'wpfaevent' ); ?></label>
			<input type="file" id="editEventPicture" name="featured_image" accept="image/*">
			
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save Changes', 'wpfaevent' ); ?></button>
		</form>
	</div>
</div>