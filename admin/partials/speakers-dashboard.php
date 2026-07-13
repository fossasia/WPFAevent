<?php
/**
 * Speakers dashboard admin view.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Speakers dashboard variables.
 *
 * @var int            $total_speakers_count   Total speakers count.
 * @var int            $standalone_count       Standalone speakers count.
 * @var int            $event_owned_count      Event-owned speakers count.
 * @var int            $total_categories_count Total speaker categories count.
 * @var array<WP_Post> $speakers_preview       Preview list of speakers.
 * @var array<WP_Term> $categories_preview     Preview list of categories.
 * @var int            $event_filter           Event ID to filter by.
 */

$new_speaker_url = admin_url( 'post-new.php?post_type=wpfa_speaker' );
$table_view_url  = add_query_arg( 'wpfa_view', 'table' );
if ( ! empty( $event_filter ) ) {
	$new_speaker_url = add_query_arg( 'wpfa_speaker_event', $event_filter, $new_speaker_url );
	$table_view_url  = add_query_arg( 'wpfa_speaker_event', $event_filter, $table_view_url );
}
?>
<div class="wrap">


	<div class="wpfaevent-dashboard-shell">
		<!-- Hero Section -->
		<div class="wpfaevent-dashboard-hero">
			<div class="wpfaevent-dashboard-meta">
				<div class="wpfaevent-badge"><?php esc_html_e( 'Speakers Hub', 'wpfaevent' ); ?></div>
			</div>
			<p><?php esc_html_e( 'Manage all speakers across your site events. Review attached speaker records, standalone profiles, and categories.', 'wpfaevent' ); ?></p>
			<div class="wpfaevent-dashboard-actions">
				<a class="button" href="<?php echo esc_url( $new_speaker_url ); ?>">
					<?php esc_html_e( 'Add New Speaker', 'wpfaevent' ); ?>
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( $table_view_url ); ?>">
					<?php esc_html_e( 'Switch to Table View', 'wpfaevent' ); ?>
				</a>
			</div>
		</div>


		<!-- Statistics Grid -->
		<div id="wpfaevent-overview" class="wpfaevent-dashboard-grid wpfaevent-dashboard-section">
			<div class="wpfaevent-dashboard-card">
				<h2><?php esc_html_e( 'Total Speakers', 'wpfaevent' ); ?></h2>
				<p class="wpfaevent-kpi"><?php echo esc_html( (string) $total_speakers_count ); ?></p>
				<p class="description"><?php esc_html_e( 'Speaker posts registered on this site.', 'wpfaevent' ); ?></p>
			</div>
			<div class="wpfaevent-dashboard-card">
				<h2><?php esc_html_e( 'Standalone Speakers', 'wpfaevent' ); ?></h2>
				<p class="wpfaevent-kpi"><?php echo esc_html( (string) $standalone_count ); ?></p>
				<p class="description"><?php esc_html_e( 'Speakers not attached to any event.', 'wpfaevent' ); ?></p>
			</div>
			<div class="wpfaevent-dashboard-card">
				<h2><?php esc_html_e( 'Event-Owned Speakers', 'wpfaevent' ); ?></h2>
				<p class="wpfaevent-kpi"><?php echo esc_html( (string) $event_owned_count ); ?></p>
				<p class="description"><?php esc_html_e( 'Speakers linked to one or more events.', 'wpfaevent' ); ?></p>
			</div>
			<div class="wpfaevent-dashboard-card">
				<h2><?php esc_html_e( 'Speaker Categories', 'wpfaevent' ); ?></h2>
				<p class="wpfaevent-kpi"><?php echo esc_html( (string) $total_categories_count ); ?></p>
				<p class="description"><?php esc_html_e( 'Taxonomy categories used for speakers.', 'wpfaevent' ); ?></p>
			</div>
		</div>

		<!-- Split Section (List Previews) -->
		<div class="wpfaevent-dashboard-split">
			<!-- Speakers Preview Card -->
			<div id="wpfaevent-speakers" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
				<h2><?php esc_html_e( 'Speakers List', 'wpfaevent' ); ?></h2>
				<?php if ( ! empty( $speakers_preview ) ) : ?>
					<div class="wpfaevent-list">
						<?php foreach ( $speakers_preview as $sp ) : ?>
							<?php
							$position     = get_post_meta( $sp->ID, 'wpfa_speaker_position', true );
							$organization = get_post_meta( $sp->ID, 'wpfa_speaker_organization', true );
							$headshot_url = get_post_meta( $sp->ID, 'wpfa_speaker_headshot_url', true );
							if ( empty( $headshot_url ) ) {
								$headshot_url = get_the_post_thumbnail_url( $sp->ID, 'thumbnail' );
							}

							$linked_events = class_exists( 'Wpfaevent_Event_Speaker_Relation_Manager' ) ? Wpfaevent_Event_Speaker_Relation_Manager::get_speaker_event_ids( $sp->ID ) : array();

							$initials = '';
							if ( ! $headshot_url ) {
								$name_parts = explode( ' ', $sp->post_title );
								$initials   = strtoupper( substr( $name_parts[0], 0, 1 ) );
								if ( count( $name_parts ) > 1 ) {
									$initials .= strtoupper( substr( end( $name_parts ), 0, 1 ) );
								}
							}
							?>
							<div class="wpfaevent-list-item">
								<?php if ( $headshot_url ) : ?>
									<img src="<?php echo esc_url( $headshot_url ); ?>" alt="<?php echo esc_attr( $sp->post_title ); ?>">
								<?php else : ?>
									<div class="wpfaevent-list-avatar-fallback">
										<?php echo esc_html( $initials ); ?>
									</div>
								<?php endif; ?>
								<div class="wpfaevent-list-copy">
									<strong><?php echo esc_html( $sp->post_title ); ?></strong>
									<div class="description">
										<?php echo esc_html( trim( $position . ( ! empty( $organization ) ? ' - ' . $organization : '' ) ) ); ?>
									</div>
									<?php if ( ! empty( $linked_events ) ) : ?>
										<div class="wpfaevent-tag-list">
											<?php foreach ( $linked_events as $event_id ) : ?>
												<span class="wpfaevent-tag"><?php echo esc_html( get_the_title( $event_id ) ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="wpfaevent-tag-list">
											<span class="wpfaevent-tag is-standalone"><?php esc_html_e( 'Standalone', 'wpfaevent' ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="wpfaevent-dashboard-card-footer is-flex">
						<span class="description">
							<?php
							if ( $total_speakers_count <= 5 ) {
								printf(
									/* translators: %d: count of speakers */
									esc_html( _n( 'Showing %d speaker', 'Showing %d speakers', $total_speakers_count, 'wpfaevent' ) ),
									absint( $total_speakers_count )
								);
							} else {
								printf(
									/* translators: %d: count of speakers */
									esc_html__( 'Showing 5 of %d speakers', 'wpfaevent' ),
									absint( $total_speakers_count )
								);
							}
							?>
						</span>
						<a class="wpfaevent-module-link" href="<?php echo esc_url( $table_view_url ); ?>">
							<?php esc_html_e( 'Manage All Speakers &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No speakers were found yet.', 'wpfaevent' ); ?></p>
					<div class="wpfaevent-dashboard-card-footer">
						<a class="wpfaevent-module-link" href="<?php echo esc_url( $new_speaker_url ); ?>">
							<?php esc_html_e( 'Add New Speaker &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<!-- Speaker Categories Preview Card -->
			<div id="wpfaevent-categories" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
				<h2><?php esc_html_e( 'Speaker Categories', 'wpfaevent' ); ?></h2>
				<?php if ( ! empty( $categories_preview ) ) : ?>
					<div class="wpfaevent-list">
						<?php foreach ( $categories_preview as $speaker_cat ) : ?>
							<div class="wpfaevent-list-item">
								<div class="wpfaevent-list-copy">
									<strong><?php echo esc_html( $speaker_cat->name ); ?></strong>
									<div class="description">
										<?php echo esc_html( ! empty( $speaker_cat->description ) ? $speaker_cat->description : __( 'No description provided.', 'wpfaevent' ) ); ?>
									</div>
								</div>
								<span class="wpfaevent-badge">
									<?php
									printf(
										/* translators: %d: count of speakers in category */
										esc_html( _n( '%d speaker', '%d speakers', $speaker_cat->count, 'wpfaevent' ) ),
										absint( $speaker_cat->count )
									);
									?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="wpfaevent-dashboard-card-footer is-flex">
						<span class="description">
							<?php
							if ( $total_categories_count <= 5 ) {
								printf(
									/* translators: %d: count of categories */
									esc_html( _n( 'Showing %d category', 'Showing %d categories', $total_categories_count, 'wpfaevent' ) ),
									absint( $total_categories_count )
								);
							} else {
								printf(
									/* translators: %d: count of categories */
									esc_html__( 'Showing 5 of %d categories', 'wpfaevent' ),
									absint( $total_categories_count )
								);
							}
							?>
						</span>
						<a class="wpfaevent-module-link" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=wpfa_speaker_category&post_type=wpfa_speaker' ) ); ?>">
							<?php esc_html_e( 'Manage Speaker Categories &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No speaker categories were found yet.', 'wpfaevent' ); ?></p>
					<div class="wpfaevent-dashboard-card-footer">
						<a class="wpfaevent-module-link" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=wpfa_speaker_category&post_type=wpfa_speaker' ) ); ?>">
							<?php esc_html_e( 'Create Speaker Category &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
