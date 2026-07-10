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
 */
?>
<div class="wrap">
	<style>
		.wpfaevent-dashboard-shell { --wpfa-blue: #1683d9; --wpfa-blue-dark: #0d5ea8; --wpfa-slate: #5f6b7a; --wpfa-border: #d9e2ec; --wpfa-bg: #f4f8fb; --wpfa-card: #ffffff; }
		.wpfaevent-dashboard-shell { background: linear-gradient(180deg, #eff6fc 0%, #f9fbfd 240px); margin-left: -20px; padding: 24px 20px 28px; }
		.wpfaevent-dashboard-hero { background: linear-gradient(135deg, var(--wpfa-blue) 0%, #40a1f2 100%); border-radius: 16px; color: #fff; padding: 24px; box-shadow: 0 18px 40px rgba(22, 131, 217, 0.18); margin-bottom: 20px; }
		.wpfaevent-dashboard-hero p { color: rgba(255,255,255,0.88); max-width: 820px; }
		.wpfaevent-dashboard-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
		.wpfaevent-dashboard-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin:20px 0; }
		.wpfaevent-dashboard-card { background: var(--wpfa-card); border: 1px solid var(--wpfa-border); border-radius: 14px; padding: 18px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05); }
		.wpfaevent-dashboard-card h2, .wpfaevent-dashboard-card h3 { margin-top: 0; }
		.wpfaevent-kpi { font-size: 32px; line-height: 1; color: var(--wpfa-blue-dark); font-weight: 700; margin: 10px 0 8px; }
		.wpfaevent-dashboard-tabs { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 18px; padding:12px; background:#fff; border:1px solid var(--wpfa-border); border-radius:14px; box-shadow:0 10px 24px rgba(15, 23, 42, 0.04); position:sticky; top:32px; z-index:5; }
		.wpfaevent-dashboard-tab { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:8px 14px; border-radius:999px; background:#eef5fb; color:var(--wpfa-blue-dark); text-decoration:none; font-weight:600; font-size:13px; border:1px solid transparent; }
		.wpfaevent-dashboard-tab:hover, .wpfaevent-dashboard-tab:focus { background:#dcecfb; color:var(--wpfa-blue-dark); }
		.wpfaevent-dashboard-tab.is-muted { background:#f3f6f8; color:#66788a; }
		.wpfaevent-dashboard-section { scroll-margin-top: 96px; }
		.wpfaevent-dashboard-columns { display:grid; grid-template-columns: 1.2fr 1fr; gap:20px; align-items:start; margin-top:20px; }
		.wpfaevent-dashboard-split { display:grid; grid-template-columns: repeat(2, minmax(280px, 1fr)); gap:20px; margin-top:20px; }
		.wpfaevent-badge { display:inline-flex; align-items:center; gap:6px; background:#e8f4fe; color:var(--wpfa-blue-dark); border-radius:999px; padding:6px 12px; font-weight:600; font-size:12px; }
		.wpfaevent-badge.is-neutral { background:#eef2f5; color:#52606d; }
		.wpfaevent-tag-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
		.wpfaevent-tag { background:#eef4f8; border:1px solid #d7e3ee; border-radius:999px; padding:6px 10px; font-size:12px; }
		.wpfaevent-list { display:grid; gap:12px; margin-top:12px; }
		.wpfaevent-list-item { display:flex; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid #e4ebf3; border-radius:12px; background:#fbfdff; }
		.wpfaevent-list-item img { width:52px; height:52px; border-radius:50%; object-fit:cover; }
		.wpfaevent-list-avatar-fallback { width:52px; height:52px; border-radius:50%; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:16px; }
		.wpfaevent-list-copy { flex:1; }
		.wpfaevent-module-link { color:var(--wpfa-blue); font-weight:600; text-decoration:none; }
		.wpfaevent-dashboard-meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
		@media (max-width: 1024px) { .wpfaevent-dashboard-columns, .wpfaevent-dashboard-split { grid-template-columns: 1fr; } .wpfaevent-dashboard-tabs { position:static; } }
	</style>

	<div class="wpfaevent-dashboard-shell">
		<!-- Hero Section -->
		<div class="wpfaevent-dashboard-hero">
			<div class="wpfaevent-dashboard-meta">
				<div class="wpfaevent-badge"><?php esc_html_e( 'Speakers Hub', 'wpfaevent' ); ?></div>
			</div>
			<p><?php esc_html_e( 'Manage all speakers across your site events. Review attached speaker records, standalone profiles, and categories.', 'wpfaevent' ); ?></p>
			<div class="wpfaevent-dashboard-actions">
				<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpfa_speaker' ) ); ?>">
					<?php esc_html_e( 'Add New Speaker', 'wpfaevent' ); ?>
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( add_query_arg( 'wpfa_view', 'table' ) ); ?>">
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
										<div class="wpfaevent-tag-list" style="margin-top: 6px;">
											<?php foreach ( $linked_events as $event_id ) : ?>
												<span class="wpfaevent-tag"><?php echo esc_html( get_the_title( $event_id ) ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<div class="wpfaevent-tag-list" style="margin-top: 6px;">
											<span class="wpfaevent-tag" style="background:#f1f5f9; color:#64748b;"><?php esc_html_e( 'Standalone', 'wpfaevent' ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div style="margin-top: 15px; border-top: 1px solid var(--wpfa-border); padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
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
						<a class="wpfaevent-module-link" href="<?php echo esc_url( add_query_arg( 'wpfa_view', 'table' ) ); ?>">
							<?php esc_html_e( 'Manage All Speakers &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No speakers were found yet.', 'wpfaevent' ); ?></p>
					<div style="margin-top: 15px; border-top: 1px solid var(--wpfa-border); padding-top: 10px;">
						<a class="wpfaevent-module-link" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpfa_speaker' ) ); ?>">
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
					<div style="margin-top: 15px; border-top: 1px solid var(--wpfa-border); padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
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
					<div style="margin-top: 15px; border-top: 1px solid var(--wpfa-border); padding-top: 10px;">
						<a class="wpfaevent-module-link" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=wpfa_speaker_category&post_type=wpfa_speaker' ) ); ?>">
							<?php esc_html_e( 'Create Speaker Category &rarr;', 'wpfaevent' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
