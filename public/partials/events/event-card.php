<?php
/**
 * Event Card Partial — horizontal layout with status badge.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials/events
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event_id = $event_id ?? get_the_ID();
if ( ! $event_id ) {
	return;
}

// $_is_past may be injected by the parent template for performance; otherwise recalculate.
if ( ! isset( $_is_past ) ) {
	$_today   = current_time( 'Y-m-d' );
	$_start   = get_post_meta( $event_id, 'wpfa_event_start_date', true );
	$_end     = get_post_meta( $event_id, 'wpfa_event_end_date', true );
	$_is_past = ! empty( $_start ) && strtotime( $_start ) < strtotime( $_today );
}
$is_past_event = (bool) $_is_past;

$today             = current_time( 'Y-m-d' );
$event_date        = get_post_meta( $event_id, 'wpfa_event_start_date', true );
$event_end_date    = get_post_meta( $event_id, 'wpfa_event_end_date', true );
$event_start_time  = get_post_meta( $event_id, 'wpfa_event_start_time', true );
$event_end_time    = get_post_meta( $event_id, 'wpfa_event_end_time', true );
$event_legacy_time = get_post_meta( $event_id, 'wpfa_event_time', true );
$event_timezone    = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_timezone( $event_id ) : '';
$event_all_day     = class_exists( 'Wpfaevent_Meta_Event' ) ? Wpfaevent_Meta_Event::get_event_all_day( $event_id ) : false;
$event_place       = get_post_meta( $event_id, 'wpfa_event_location', true );
$event_lead_text   = sanitize_text_field( get_post_meta( $event_id, 'wpfa_event_lead_text', true ) );
$event_description = $event_lead_text ? $event_lead_text : get_the_excerpt( $event_id );
$featured_img_url  = get_the_post_thumbnail_url( $event_id, 'large' );
$featured_img_url  = $featured_img_url ? $featured_img_url : '';
$event_time_value  = $event_start_time ? $event_start_time : $event_legacy_time;

$calendar_data = class_exists( 'Wpfaevent_Calendar' ) ? Wpfaevent_Calendar::get_event_calendar_data( $event_id ) : array();
$calendar_data = is_wp_error( $calendar_data ) ? array() : $calendar_data;

$is_valid_date = ! empty( $event_date ) && strtotime( $event_date ) !== false;

$formatted_date      = __( 'Date not set', 'wpfaevent' );
$formatted_time_meta = '';
if ( ! empty( $calendar_data['date_label'] ) ) {
	$formatted_date      = sanitize_text_field( $calendar_data['date_label'] );
	$formatted_time_meta = ! empty( $calendar_data['time_label'] ) ? sanitize_text_field( $calendar_data['time_label'] ) : '';
	if ( $formatted_time_meta && empty( $calendar_data['all_day'] ) && ! empty( $calendar_data['timezone_label'] ) ) {
		$formatted_time_meta .= ' (' . sanitize_text_field( $calendar_data['timezone_label'] ) . ')';
	}
} elseif ( $is_valid_date ) {
	if ( ! empty( $event_end_date ) && $event_end_date !== $event_date && strtotime( $event_end_date ) !== false ) {
		$formatted_date = date_i18n( 'M j', strtotime( $event_date ) ) . ' - ' . date_i18n( 'M j, Y', strtotime( $event_end_date ) );
	} else {
		$formatted_date = date_i18n( 'F j, Y', strtotime( $event_date ) );
	}
}

// Track taxonomy terms for data-track JS filtering.
$event_tracks = get_the_terms( $event_id, 'wpfa_event_track' );
$track_slugs  = ( ! is_wp_error( $event_tracks ) && $event_tracks ) ? implode( ',', wp_list_pluck( $event_tracks, 'slug' ) ) : '';

// Speaker count via bidirectional relationship meta.
$speaker_ids   = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' )
	? Wpfaevent_Event_Speaker_Relation_Manager::get_admin_event_speaker_ids( $event_id )
	: (array) get_post_meta( $event_id, 'wpfa_event_speakers', true );
$speaker_ids   = array_filter( array_map( 'absint', (array) $speaker_ids ) );
$speaker_count = count( $speaker_ids );

$can_manage_content  = class_exists( 'Wpfaevent_Roles' ) ? Wpfaevent_Roles::current_user_can_manage_dashboard() : current_user_can( 'manage_options' );
$can_delete_content  = class_exists( 'Wpfaevent_Roles' ) ? Wpfaevent_Roles::current_user_can_delete_content() : current_user_can( 'delete_posts' );
$can_edit_this_event = $can_manage_content && current_user_can( 'edit_post', $event_id );
$can_delete_event    = $can_delete_content && current_user_can( 'delete_post', $event_id );
$is_admin            = current_user_can( 'manage_options' );
$event_url           = esc_url( get_permalink( $event_id ) );

// Speakers page URL — link to /speakers/ filtered by event slug.
$speaker_archive_url = get_post_type_archive_link( 'wpfa_speaker' );
$speaker_archive_url = $speaker_archive_url ? $speaker_archive_url : home_url( '/speakers/' );
$speakers_url        = esc_url( add_query_arg( 'event', get_post_field( 'post_name', $event_id ), $speaker_archive_url ) );
$is_bookmarked = is_user_logged_in() && in_array( (int) $event_id, array_map( 'absint', (array) get_user_meta( get_current_user_id(), 'wpfa_bookmarked_events', true ) ), true );
?>

<div class="event-card<?php echo $is_bookmarked ? ' is-bookmarked' : ''; ?>"
	data-post-id="<?php echo esc_attr( $event_id ); ?>"
	data-is-past="<?php echo $is_past_event ? '1' : '0'; ?>"
	data-is-bookmarked="<?php echo $is_bookmarked ? '1' : '0'; ?>"
	data-name="<?php echo esc_attr( get_the_title( $event_id ) ); ?>"
	data-date="<?php echo esc_attr( $event_date ); ?>"
	data-end-date="<?php echo esc_attr( $event_end_date ); ?>"
	data-place="<?php echo esc_attr( $event_place ); ?>"
	data-track="<?php echo esc_attr( $track_slugs ); ?>"
	data-description="<?php echo esc_attr( $event_description ); ?>"
	data-lead-text="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_lead_text', true ) ); ?>"
	data-registration-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_registration_link', true ) ); ?>"
	data-cfs-link="<?php echo esc_attr( get_post_meta( $event_id, 'wpfa_event_cfs_link', true ) ); ?>"
	data-start-time="<?php echo esc_attr( $event_time_value ); ?>"
	data-end-time="<?php echo esc_attr( $event_end_time ); ?>"
	data-timezone="<?php echo esc_attr( $event_timezone ); ?>"
	data-all-day="<?php echo esc_attr( $event_all_day ? '1' : '0' ); ?>"
	data-time="<?php echo esc_attr( $event_time_value ); ?>">

	<?php if ( $can_manage_content && ( ! $is_valid_date || $is_past_event ) ) : ?>
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

	<a href="<?php echo esc_url( $event_url ); ?>" class="event-card-thumb" tabindex="-1" aria-hidden="true">
		<?php if ( $featured_img_url ) : ?>
			<img src="<?php echo esc_url( $featured_img_url ); ?>" alt="<?php echo esc_attr( get_the_title( $event_id ) ); ?>" loading="lazy">
		<?php else : ?>
			<div class="event-card-thumb-placeholder" aria-hidden="true"></div>
		<?php endif; ?>
	</a>

	<div class="event-card-body">
		<div class="event-card-badges">
			<span class="event-badge event-badge--<?php echo $is_past_event ? 'past' : 'upcoming'; ?>">
				<?php echo $is_past_event ? esc_html__( 'Past', 'wpfaevent' ) : esc_html__( 'Upcoming', 'wpfaevent' ); ?>
			</span>
			<?php if ( $speaker_count > 0 ) : ?>
				<span class="event-badge event-badge--speakers">
					<?php
					/* translators: %d: number of speakers */
					echo esc_html( sprintf( _n( '%d speaker', '%d speakers', $speaker_count, 'wpfaevent' ), $speaker_count ) );
					?>
				</span>
			<?php endif; ?>
			<?php if ( $is_admin && ! $is_valid_date ) : ?>
				<span class="event-badge event-badge--warning"><?php esc_html_e( 'No date', 'wpfaevent' ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="event-card-title">
			<a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a>
		</h3>

		<p class="event-card-meta">
			<span class="event-card-meta-date"><?php echo esc_html( $formatted_date ); ?></span>
			<?php if ( ! empty( $event_place ) ) : ?>
				<span class="event-card-meta-sep" aria-hidden="true">  </span>
				<span class="event-card-meta-place"><?php echo esc_html( $event_place ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $event_description ) ) : ?>
			<p class="event-card-description"><?php echo esc_html( $event_description ); ?></p>
		<?php endif; ?>
	</div>

	<div class="event-card-cta">
		<a href="<?php echo esc_url( $event_url ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'View Event', 'wpfaevent' ); ?></a>
		<?php if ( $speaker_count > 0 ) : ?>
			<a href="<?php echo esc_url( $speakers_url ); ?>" class="btn btn-outline-primary btn-sm"><?php esc_html_e( 'Speakers', 'wpfaevent' ); ?></a>
		<?php endif; ?>
		<button class="btn btn-outline-primary btn-sm wpfa-bookmark-btn<?php echo $is_bookmarked ? ' is-bookmarked' : ''; ?>" data-event-id="<?php echo esc_attr( $event_id ); ?>">
			<svg class="wpfa-bookmark-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px; margin-right: 6px; vertical-align: middle;">
				<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
			</svg>
			<span class="wpfa-bookmark-text"><?php echo $is_bookmarked ? esc_html__( 'Bookmarked', 'wpfaevent' ) : esc_html__( 'Bookmark', 'wpfaevent' ); ?></span>
		</button>
		<?php if ( $can_edit_this_event ) : ?>
			<button class="btn btn-secondary btn-sm btn-edit-event"
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
			<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $event_id . '&action=edit' ) ); ?>" class="btn btn-outline-primary btn-sm"><?php esc_html_e( 'Edit Content', 'wpfaevent' ); ?></a>
			<?php if ( $can_delete_event ) : ?>
				<button class="btn btn-secondary btn-sm btn-delete-event"><?php esc_html_e( 'Delete', 'wpfaevent' ); ?></button>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
