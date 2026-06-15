<?php
/**
 * Event Card Partial
 *
 * Reusable template partial for displaying a single event card.
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
	exit;  // Exit if accessed directly.
}

// Use the passed $event_id if it exists; otherwise, fall back to the loop ID.
$event_id = $event_id ?? get_the_ID();

// Exit if we still don't have a valid ID (e.g., if called outside the loop).
if ( ! $event_id ) {
	return;
}

$today = current_time( 'Y-m-d' );

// Get meta data exactly as the main template does.
$event_date        = get_post_meta( $event_id, 'wpfa_event_start_date', true );
$event_end_date    = get_post_meta( $event_id, 'wpfa_event_end_date', true );
$event_start_time  = get_post_meta( $event_id, 'wpfa_event_start_time', true );
$event_end_time    = get_post_meta( $event_id, 'wpfa_event_end_time', true );
$event_legacy_time = get_post_meta( $event_id, 'wpfa_event_time', true );
$event_timezone    = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_timezone( $event_id ) : '';
$event_all_day     = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_all_day( $event_id ) : false;
$event_place       = get_post_meta( $event_id, 'wpfa_event_location', true );
$event_description = get_the_excerpt( $event_id );
$featured_img_url  = get_the_post_thumbnail_url( $event_id, 'large' ) ?? '';
$event_time_value  = $event_start_time ? $event_start_time : $event_legacy_time;

$event_calendar_data = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_calendar_data( $event_id ) : array();
$event_calendar_data = is_wp_error( $event_calendar_data ) ? array() : $event_calendar_data;

// Check if date is valid (Admin Warning Logic).
$is_valid_date = ! empty( $event_date ) && strtotime( $event_date ) !== false;
$is_past_event = $is_valid_date && strtotime( $event_date ) < strtotime( $today );

// Format the date string.
$formatted_date      = __( 'Date not set', 'wpfaevent' );
$formatted_time_meta = '';
if ( ! empty( $event_calendar_data['date_label'] ) ) {
	$formatted_date      = sanitize_text_field( $event_calendar_data['date_label'] );
	$formatted_time_meta = ! empty( $event_calendar_data['time_label'] ) ? sanitize_text_field( $event_calendar_data['time_label'] ) : '';

	if ( $formatted_time_meta && empty( $event_calendar_data['all_day'] ) && ! empty( $event_calendar_data['timezone_label'] ) ) {
		$formatted_time_meta .= ' (' . sanitize_text_field( $event_calendar_data['timezone_label'] ) . ')';
	}
} elseif ( $is_valid_date ) {
	if ( ! empty( $event_end_date ) && $event_end_date !== $event_date && strtotime( $event_end_date ) !== false ) {
		$formatted_date = date_i18n( 'M j', strtotime( $event_date ) ) . ' - ' . date_i18n( 'M j, Y', strtotime( $event_end_date ) );
	} else {
		$formatted_date = date_i18n( 'F j, Y', strtotime( $event_date ) );
	}
}

$is_admin = current_user_can( 'manage_options' );
?>

<div class="event-card"
	data-post-id="<?php echo esc_attr( $event_id ); ?>"
	data-name="<?php echo esc_attr( get_the_title( $event_id ) ); ?>"
	data-date="<?php echo esc_attr( $event_date ); ?>"
	data-place="<?php echo esc_attr( $event_place ); ?>"
	data-end-date="<?php echo esc_attr( $event_end_date ); ?>"
	data-description="<?php echo esc_attr( $event_description ); ?>"
	data-lead-text="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_lead_text', true ) ); ?>"
	data-registration-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_registration_link', true ) ); ?>"
	data-cfs-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_cfs_link', true ) ); ?>"
	data-start-time="<?php echo esc_attr( $event_time_value ); ?>"
	data-end-time="<?php echo esc_attr( $event_end_time ); ?>"
	data-timezone="<?php echo esc_attr( $event_timezone ); ?>"
	data-all-day="<?php echo esc_attr( $event_all_day ? '1' : '0' ); ?>"
	data-time="<?php echo esc_attr( $event_time_value ); ?>">

	<?php if ( $is_admin && ( ! $is_valid_date || $is_past_event ) ) : ?>
		<div class="wpfaevent-admin-warning">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="wpfaevent-warning-icon" aria-hidden="true">
				<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
			</svg>
			<span>
				<?php
				if ( ! $is_valid_date ) {
					esc_html_e( 'Invalid date format', 'wpfaevent' );
				} elseif ( $is_past_event ) {
					esc_html_e( 'Past event', 'wpfaevent' );
				}
				?>
			</span>
		</div>
	<?php endif; ?>

	<?php if ( $is_admin ) : ?>
	<div class="event-card-actions">
			<button class="btn-edit-event"
					data-post-id="<?php echo esc_attr( $event_id ); ?>"
					data-name="<?php echo esc_attr( get_the_title( $event_id ) ); ?>"
					data-date="<?php echo esc_attr( $event_date ); ?>"
					data-end-date="<?php echo esc_attr( $event_end_date ); ?>"
					data-place="<?php echo esc_attr( $event_place ); ?>"
					data-description="<?php echo esc_attr( $event_description ); ?>"
					data-lead-text="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_lead_text', true ) ); ?>"
					data-registration-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_registration_link', true ) ); ?>"
					data-cfs-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_cfs_link', true ) ); ?>"
					data-start-time="<?php echo esc_attr( $event_time_value ); ?>"
					data-end-time="<?php echo esc_attr( $event_end_time ); ?>"
					data-timezone="<?php echo esc_attr( $event_timezone ); ?>"
					data-all-day="<?php echo esc_attr( $event_all_day ? '1' : '0' ); ?>"
					data-time="<?php echo esc_attr( $event_time_value ); ?>">
			<?php esc_html_e( 'Edit Details', 'wpfaevent' ); ?>
		</button>
		<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $event_id . '&action=edit' ) ); ?>" class="btn-edit-content"><?php esc_html_e( 'Edit Content', 'wpfaevent' ); ?></a>
		<button class="btn-delete-event"><?php esc_html_e( 'Delete', 'wpfaevent' ); ?></button>
	</div>
	<?php endif; ?>

	<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="event-card-link">
		<div class="event-card-image">
			<img src="<?php echo esc_url( $featured_img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>">
		</div>
		<div class="event-card-content">
			<h3><?php echo esc_html( get_the_title( $event_id ) ); ?></h3>
			<p>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path>
				</svg>
				<?php echo esc_html( $formatted_date ); ?>
				<?php if ( $formatted_time_meta ) : ?>
					<span class="event-card-time"><?php echo esc_html( ' | ' . $formatted_time_meta ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( ! empty( $event_place ) ) : ?>
			<p>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
				</svg>
				<?php echo esc_html( $event_place ); ?>
			</p>
			<?php endif; ?>
			<?php if ( ! empty( $event_description ) ) : ?>
				<p class="event-card-description"><?php echo esc_html( $event_description ); ?></p>
			<?php endif; ?>
		</div>
	</a>
</div>
