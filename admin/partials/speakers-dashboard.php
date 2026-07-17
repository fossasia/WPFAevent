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
		<?php
		$switch_view_url   = $table_view_url;
		$switch_view_label = __( 'Switch to Table View', 'wpfaevent' );
		require WPFAEVENT_PATH . 'admin/partials/speaker-dashboard-header.php';
		?>

		<!-- Statistics Grid -->
		<?php require WPFAEVENT_PATH . 'admin/partials/speaker-dashboard-stats.php'; ?>

		<!-- Split Section (List Previews) -->
		<div class="wpfaevent-dashboard-split">
			<!-- Speakers Preview Card -->
			<div id="wpfaevent-speakers" class="wpfaevent-dashboard-card wpfaevent-dashboard-section">
				<h2><?php esc_html_e( 'Speakers List', 'wpfaevent' ); ?></h2>
				<?php if ( ! empty( $speakers_preview ) ) : ?>
					<div class="wpfaevent-list">
						<?php
						foreach ( $speakers_preview as $sp ) {
							require WPFAEVENT_PATH . 'admin/partials/speaker-dashboard-card.php';
						}
						?>
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
