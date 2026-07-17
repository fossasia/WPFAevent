<?php
/**
 * Partner Dashboard Renderer helper.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard_Renderer
 */
class Wpfaevent_Partner_Dashboard_Renderer {

	/**
	 * Statistics provider.
	 *
	 * @var Wpfaevent_Partner_Dashboard_Statistics
	 */
	private $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->stats = new Wpfaevent_Partner_Dashboard_Statistics();
	}

	/**
	 * Render Sponsors Management Page callback.
	 */
	public function render_sponsors_page() {
		$this->render_dashboard_page( 'sponsor' );
	}

	/**
	 * Render Exhibitors Management Page callback.
	 */
	public function render_exhibitors_page() {
		$this->render_dashboard_page( 'exhibitor' );
	}

	/**
	 * Unified layout generator for both Sponsors and Exhibitors dashboards.
	 *
	 * @param string $type Accepts 'sponsor' or 'exhibitor'.
	 */
	public function render_dashboard_page( $type ) {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$event_options = $this->stats->get_events();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;

		if ( ! $event_id && ! empty( $event_options ) ) {
			$event_id = (int) key( $event_options );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$target_id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';

		$type_label        = 'sponsor' === $type ? __( 'Sponsor', 'wpfaevent' ) : __( 'Exhibitor', 'wpfaevent' );
		$type_label_plural = 'sponsor' === $type ? __( 'Sponsors', 'wpfaevent' ) : __( 'Exhibitors', 'wpfaevent' );

		// Load records for the event.
		$records = $this->stats->load_records( $type, $event_id );

		// Handle CRUD Routing.
		if ( 'new' === $action || ( 'edit' === $action && $target_id ) ) {
			$item = array();
			if ( 'edit' === $action ) {
				foreach ( $records as $rec ) {
					if ( isset( $rec['id'] ) && $rec['id'] === $target_id ) {
						$item = $rec;
						break;
					}
				}
				if ( empty( $item ) ) {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Record not found.', 'wpfaevent' ) . '</p></div>';
					$action = 'list';
				}
			}
			if ( 'list' !== $action ) {
				$this->render_form( $type, $event_id, $item );
				return;
			}
		}

		// Filter, search, and sort records.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cat_filter = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'name';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['order'] ) && 'desc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'desc' : 'asc';

		$categories = array();
		foreach ( $records as $rec ) {
			$rec_cat = isset( $rec['type'] ) ? $rec['type'] : '';
			if ( $rec_cat && ! in_array( $rec_cat, $categories, true ) ) {
				$categories[] = $rec_cat;
			}
		}

		$filtered_records = $this->stats->filter_and_sort_records(
			$records,
			$type,
			$search_query,
			$status_filter,
			$cat_filter,
			$orderby,
			$order
		);

		// Pagination.
		$total_items = count( $filtered_records );
		$per_page    = 10;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$total_pages  = ceil( $total_items / $per_page );
		$offset       = ( $current_page - 1 ) * $per_page;
		$paged_items  = array_slice( $filtered_records, $offset, $per_page );

		// Stats counts.
		$active_inactive = $this->stats->get_active_inactive_stats( $records );
		$active_count    = $active_inactive['active'];
		$inactive_count  = $active_inactive['inactive'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		?>
		<div class="wrap">
			<div class="wpfaevent-dashboard-shell">
				<!-- Hero Section -->
				<div class="wpfaevent-dashboard-hero">
					<div class="wpfaevent-dashboard-meta wpfaevent-dashboard-meta-header">
						<div class="wpfaevent-badge"><?php echo esc_html( $type_label_plural ); ?> Hub</div>
						<?php
						$dashboard_page = new Wpfaevent_Event_Dashboard_Page();
						$dashboard_url  = $dashboard_page->get_dashboard_url( $event_id );
						?>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="wpfaevent-dashboard-tab is-light-outline">
							&larr; <?php esc_html_e( 'Back to Event Dashboard', 'wpfaevent' ); ?>
						</a>
					</div>
					<p>
						<?php
						/* translators: %s: Type label plural in lowercase. */
						printf( esc_html__( 'Manage all %1$s for your selected event. Maintain contact info, company details, website links, and status.', 'wpfaevent' ), esc_html( strtolower( $type_label_plural ) ) );
						?>
					</p>

					<div class="wpfaevent-hero-actions">
						<form method="get" action="">
							<input type="hidden" name="post_type" value="wpfa_event">
							<input type="hidden" name="page" value="<?php echo esc_attr( $current_page_slug ); ?>">
							<label for="wpfaevent-event-select"><?php esc_html_e( 'Select Event:', 'wpfaevent' ); ?></label>
							<select id="wpfaevent-event-select" name="event_id" onchange="this.form.submit()">
								<?php foreach ( $event_options as $opt_id => $opt_title ) : ?>
									<option value="<?php echo esc_attr( (string) $opt_id ); ?>" <?php selected( $opt_id, $event_id ); ?>><?php echo esc_html( $opt_title ); ?></option>
								<?php endforeach; ?>
							</select>
							<a class="button button-primary" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'action'   => 'new',
										'event_id' => $event_id,
									)
								)
							);
							?>
																	">
								+
								<?php
								/* translators: %s: Type label singular. */
								printf( esc_html__( 'Add New %s', 'wpfaevent' ), esc_html( $type_label ) );
								?>
							</a>
						</form>
					</div>
				</div>

				<!-- Stats Grid -->
				<div class="wpfaevent-dashboard-grid">
					<div class="wpfaevent-dashboard-card">
						<h2>
							<?php
							/* translators: %s: Type label plural. */
							printf( esc_html__( 'Total %s', 'wpfaevent' ), esc_html( $type_label_plural ) );
							?>
						</h2>
						<div class="wpfaevent-kpi"><?php echo esc_html( (string) count( $records ) ); ?></div>
						<div class="description"><?php esc_html_e( 'Registered on this event.', 'wpfaevent' ); ?></div>
					</div>
					<div class="wpfaevent-dashboard-card">
						<h2><?php esc_html_e( 'Active Records', 'wpfaevent' ); ?></h2>
						<div class="wpfaevent-kpi is-success"><?php echo esc_html( (string) $active_count ); ?></div>
						<div class="description"><?php esc_html_e( 'Currently active and visible.', 'wpfaevent' ); ?></div>
					</div>
					<div class="wpfaevent-dashboard-card">
						<h2><?php esc_html_e( 'Inactive Records', 'wpfaevent' ); ?></h2>
						<div class="wpfaevent-kpi is-danger"><?php echo esc_html( (string) $inactive_count ); ?></div>
						<div class="description"><?php esc_html_e( 'Hidden or legacy profiles.', 'wpfaevent' ); ?></div>
					</div>
				</div>

				<!-- CRUD Search & Filter Header -->
				<div class="wpfaevent-list-toolbar">
					<h3>
						<?php
						/* translators: %s: Type label plural. */
						printf( esc_html__( 'All %s List', 'wpfaevent' ), esc_html( $type_label_plural ) );
						?>
					</h3>
					<div class="wpfaevent-list-toolbar-actions">
						<!-- Filter form -->
						<form method="get" action="" class="wpfaevent-list-toolbar-form">
							<input type="hidden" name="post_type" value="wpfa_event">
							<input type="hidden" name="page" value="<?php echo esc_attr( $current_page_slug ); ?>">
							<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">

							<select name="status">
								<option value=""><?php esc_html_e( 'All Statuses', 'wpfaevent' ); ?></option>
								<option value="active" <?php selected( $status_filter, 'active' ); ?>><?php esc_html_e( 'Active', 'wpfaevent' ); ?></option>
								<option value="inactive" <?php selected( $status_filter, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wpfaevent' ); ?></option>
							</select>

							<?php if ( ! empty( $categories ) ) : ?>
								<select name="cat">
									<option value=""><?php echo 'sponsor' === $type ? esc_html__( 'All Packages', 'wpfaevent' ) : esc_html__( 'All Categories', 'wpfaevent' ); ?></option>
									<?php foreach ( $categories as $c ) : ?>
										<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat_filter, $c ); ?>><?php echo esc_html( $c ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>

							<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search...', 'wpfaevent' ); ?>" value="<?php echo esc_attr( $search_query ); ?>">
							<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wpfaevent' ); ?></button>
						</form>
					</div>
				</div>

				<!-- CRUD List Table -->
				<div class="wpfaevent-dashboard-card is-table-wrapper">
					<table class="wp-list-table widefat fixed striped wpfaevent-table-borderless">
						<thead>
							<tr>
								<th class="wpfaevent-col-logo"><?php esc_html_e( 'Logo', 'wpfaevent' ); ?></th>
								<th>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'orderby' => 'name',
												'order'   => 'name' === $orderby && 'asc' === $order ? 'desc' : 'asc',
											)
										)
									);
									?>
												">
										<?php esc_html_e( 'Name', 'wpfaevent' ); ?>
										<?php if ( 'name' === $orderby ) : ?>
											<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?> wpfaevent-sort-icon"></span>
										<?php endif; ?>
									</a>
								</th>
								<th>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'orderby' => 'company',
												'order'   => 'company' === $orderby && 'asc' === $order ? 'desc' : 'asc',
											)
										)
									);
									?>
												">
										<?php esc_html_e( 'Company/Organization', 'wpfaevent' ); ?>
										<?php if ( 'company' === $orderby ) : ?>
											<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?> wpfaevent-sort-icon"></span>
										<?php endif; ?>
									</a>
								</th>
								<th><?php esc_html_e( 'Contacts', 'wpfaevent' ); ?></th>
								<th><?php echo 'sponsor' === $type ? esc_html__( 'Package/Category', 'wpfaevent' ) : esc_html__( 'Package/Category', 'wpfaevent' ); ?></th>
								<th>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'orderby' => 'status',
												'order'   => 'status' === $orderby && 'asc' === $order ? 'desc' : 'asc',
											)
										)
									);
									?>
												">
										<?php esc_html_e( 'Status', 'wpfaevent' ); ?>
										<?php if ( 'status' === $orderby ) : ?>
											<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?> wpfaevent-sort-icon"></span>
										<?php endif; ?>
									</a>
								</th>
								<th>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'orderby' => 'date',
												'order'   => 'date' === $orderby && 'asc' === $order ? 'desc' : 'asc',
											)
										)
									);
									?>
												">
										<?php esc_html_e( 'Date Created', 'wpfaevent' ); ?>
										<?php if ( 'date' === $orderby ) : ?>
											<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?> wpfaevent-sort-icon"></span>
										<?php endif; ?>
									</a>
								</th>
								<th class="wpfaevent-col-actions"><?php esc_html_e( 'Actions', 'wpfaevent' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $paged_items ) ) : ?>
								<tr>
									<td colspan="8" class="wpfaevent-table-no-records">
										<?php esc_html_e( 'No records found matching filters.', 'wpfaevent' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $paged_items as $item ) : ?>
									<?php
									$item_id      = isset( $item['id'] ) ? $item['id'] : '';
									$item_name    = isset( $item['name'] ) ? $item['name'] : '';
									$item_company = isset( $item['company'] ) ? $item['company'] : '';
									$item_phone   = isset( $item['phone'] ) ? $item['phone'] : '';
									$item_link    = isset( $item['link'] ) ? $item['link'] : '';
									$item_cat     = isset( $item['type'] ) ? $item['type'] : '';
									$item_status  = isset( $item['status'] ) ? $item['status'] : 'active';
									$created_date = isset( $item['created_at'] ) && $item['created_at'] ? date_i18n( get_option( 'date_format' ), strtotime( $item['created_at'] ) ) : '&mdash;';
									$is_manual    = isset( $item['source'] ) && 'manual' === $item['source'];

									$item_email = 'sponsor' === $type ? ( isset( $item['email'] ) ? $item['email'] : '' ) : ( isset( $item['contact_email'] ) ? $item['contact_email'] : '' );
									$item_logo  = 'sponsor' === $type ? ( isset( $item['image'] ) ? $item['image'] : '' ) : ( isset( $item['logo'] ) ? $item['logo'] : '' );
									?>
									<tr>
										<td class="wpfaevent-col-logo">
											<?php if ( $item_logo ) : ?>
												<img src="<?php echo esc_url( $item_logo ); ?>" alt="<?php echo esc_attr( $item_name ); ?>" class="wpfaevent-table-logo-img">
											<?php else : ?>
												<div class="wpfaevent-table-logo-fallback">
													<?php echo esc_html( strtoupper( substr( $item_name, 0, 1 ) ) ); ?>
												</div>
											<?php endif; ?>
										</td>
										<td class="wpfaevent-font-semibold">
											<?php if ( $item_link ) : ?>
												<a href="<?php echo esc_url( $item_link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item_name ); ?></a>
											<?php else : ?>
												<?php echo esc_html( $item_name ); ?>
											<?php endif; ?>
											<?php if ( ! $is_manual ) : ?>
												<span class="wpfaevent-badge is-neutral is-small"><?php esc_html_e( 'Eventyay', 'wpfaevent' ); ?></span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( $item_company ); ?></td>
										<td>
											<?php if ( $item_email ) : ?>
												<div class="wpfaevent-contact-email"><?php echo esc_html( $item_email ); ?></div>
											<?php endif; ?>
											<?php if ( $item_phone ) : ?>
												<div class="wpfaevent-contact-phone"><?php echo esc_html( $item_phone ); ?></div>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( $item_cat ) : ?>
												<span class="wpfaevent-tag"><?php echo esc_html( $item_cat ); ?></span>
											<?php else : ?>
												&mdash;
											<?php endif; ?>
										</td>
										<td>
											<?php if ( 'active' === $item_status ) : ?>
												<span class="wpfaevent-status-active"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Active', 'wpfaevent' ); ?></span>
											<?php else : ?>
												<span class="wpfaevent-status-inactive"><span class="dashicons dashicons-no"></span> <?php esc_html_e( 'Inactive', 'wpfaevent' ); ?></span>
											<?php endif; ?>
										</td>
										<td class="wpfaevent-col-date"><?php echo wp_kses_post( $created_date ); ?></td>
										<td class="wpfaevent-col-actions">
											<div class="wpfaevent-row-actions">
												<a class="button button-small" href="
												<?php
												echo esc_url(
													add_query_arg(
														array(
															'action' => 'edit',
															'id' => $item_id,
														)
													)
												);
												?>
																						"><?php esc_html_e( 'Edit', 'wpfaevent' ); ?></a>
												<?php if ( $is_manual ) : ?>
													<?php
													$delete_url = wp_nonce_url(
														add_query_arg(
															array(
																'action'   => 'wpfaevent_delete_partner',
																'id'       => $item_id,
																'type'     => $type,
																'event_id' => $event_id,
															),
															admin_url( 'admin-post.php' )
														),
														'wpfaevent_delete_partner_' . $item_id
													);
													?>
													<a class="button button-small button-link-delete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this profile?', 'wpfaevent' ); ?>');"><?php esc_html_e( 'Delete', 'wpfaevent' ); ?></a>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>

					<!-- Pagination Footer -->
					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav bottom wpfaevent-tablenav-bottom">
							<div class="tablenav-pages">
								<span class="displaying-num">
									<?php
									/* translators: %s: number of items. */
									printf( esc_html( _n( '%s item', '%s items', $total_items, 'wpfaevent' ) ), esc_html( number_format_i18n( $total_items ) ) );
									?>
								</span>
								<span class="pagination-links">
									<?php if ( $current_page > 1 ) : ?>
										<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">&lsaquo; <?php esc_html_e( 'Prev', 'wpfaevent' ); ?></a>
									<?php endif; ?>

									<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
										<a class="button <?php echo $i === $current_page ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( (string) $i ); ?></a>
									<?php endfor; ?>

									<?php if ( $current_page < $total_pages ) : ?>
										<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'wpfaevent' ); ?> &rsaquo;</a>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Create/Edit form for both Sponsors and Exhibitors.
	 *
	 * @param string $type     Accepts 'sponsor' or 'exhibitor'.
	 * @param int    $event_id Event ID.
	 * @param array  $item     Existing item for editing, or empty for creation.
	 */
	public function render_form( $type, $event_id, $item = array() ) {
		$is_edit           = ! empty( $item );
		$type_label        = 'sponsor' === $type ? __( 'Sponsor', 'wpfaevent' ) : __( 'Exhibitor', 'wpfaevent' );
		$type_label_plural = 'sponsor' === $type ? __( 'Sponsors', 'wpfaevent' ) : __( 'Exhibitors', 'wpfaevent' );

		$id      = isset( $item['id'] ) ? $item['id'] : '';
		$name    = isset( $item['name'] ) ? $item['name'] : '';
		$company = isset( $item['company'] ) ? $item['company'] : '';
		$email   = 'sponsor' === $type ? ( isset( $item['email'] ) ? $item['email'] : '' ) : ( isset( $item['contact_email'] ) ? $item['contact_email'] : '' );
		$phone   = isset( $item['phone'] ) ? $item['phone'] : '';
		$logo    = 'sponsor' === $type ? ( isset( $item['image'] ) ? $item['image'] : '' ) : ( isset( $item['logo'] ) ? $item['logo'] : '' );
		$link    = isset( $item['link'] ) ? $item['link'] : '';
		$cat     = isset( $item['type'] ) ? $item['type'] : '';
		$status  = isset( $item['status'] ) ? $item['status'] : 'active';
		$desc    = isset( $item['description'] ) ? $item['description'] : '';

		$back_url = remove_query_arg( array( 'action', 'id' ) );
		?>
		<div class="wrap">
			<div class="wpfaevent-dashboard-shell is-form-width">
				<div class="wpfaevent-dashboard-hero">
					<div class="wpfaevent-dashboard-meta wpfaevent-dashboard-meta-header">
						<div class="wpfaevent-badge"><?php echo esc_html( $type_label_plural ); ?> Hub</div>
						<?php
						$dashboard_page = new Wpfaevent_Event_Dashboard_Page();
						$dashboard_url  = $dashboard_page->get_dashboard_url( $event_id );
						?>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="wpfaevent-dashboard-tab is-light-outline">
							&larr; <?php esc_html_e( 'Back to Event Dashboard', 'wpfaevent' ); ?>
						</a>
					</div>
					<h2>
						<?php
						/* translators: 1: Type label singular, 2: Name of the partner record. */
						echo $is_edit ? sprintf( esc_html__( 'Edit %1$s: %2$s', 'wpfaevent' ), esc_html( $type_label ), esc_html( $name ) ) : sprintf( esc_html__( 'Add New %s', 'wpfaevent' ), esc_html( $type_label ) );
						?>
					</h2>
					<p><?php esc_html_e( 'Provide the details below. All manually created profiles are marked as manual and will be preserved.', 'wpfaevent' ); ?></p>
				</div>

				<div class="wpfaevent-dashboard-card wpfaevent-mt-20">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wpfaevent_save_partner">
						<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
						<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">
						<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
						<?php wp_nonce_field( 'wpfaevent_save_partner_' . $id ); ?>

						<table class="form-table">
							<tr>
								<th scope="row"><label for="partner-name"><?php esc_html_e( 'Name', 'wpfaevent' ); ?> <span class="description wpfaevent-required-star">*</span></label></th>
								<td>
									<input type="text" id="partner-name" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-company"><?php esc_html_e( 'Company/Organization Name', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="text" id="partner-company" name="company" class="regular-text" value="<?php echo esc_attr( $company ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-email"><?php esc_html_e( 'Email Address', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="email" id="partner-email" name="email" class="regular-text" value="<?php echo esc_attr( $email ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-phone"><?php esc_html_e( 'Contact Number', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="text" id="partner-phone" name="phone" class="regular-text" value="<?php echo esc_attr( $phone ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-logo"><?php esc_html_e( 'Logo/Image URL', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="url" id="partner-logo" name="logo" class="regular-text" value="<?php echo esc_attr( $logo ); ?>">
									<p class="description"><?php esc_html_e( 'Provide a direct link to an image file (PNG/JPG).', 'wpfaevent' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-link"><?php esc_html_e( 'Website URL', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="url" id="partner-link" name="link" class="regular-text" value="<?php echo esc_attr( $link ); ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-cat"><?php echo 'sponsor' === $type ? esc_html__( 'Sponsorship Package', 'wpfaevent' ) : esc_html__( 'Exhibitor Category', 'wpfaevent' ); ?></label></th>
								<td>
									<input type="text" id="partner-cat" name="cat" class="regular-text" value="<?php echo esc_attr( $cat ); ?>" placeholder="<?php echo 'sponsor' === $type ? 'Gold, Silver, Bronze, etc.' : 'Standard, Premium, etc.'; ?>">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-status"><?php esc_html_e( 'Status', 'wpfaevent' ); ?></label></th>
								<td>
									<select id="partner-status" name="status">
										<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wpfaevent' ); ?></option>
										<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wpfaevent' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="partner-desc"><?php esc_html_e( 'Description/Biography', 'wpfaevent' ); ?></label></th>
								<td>
									<textarea id="partner-desc" name="description" rows="5" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
								</td>
							</tr>
						</table>

						<div class="wpfaevent-form-actions">
							<?php submit_button( __( 'Save Profile', 'wpfaevent' ), 'primary', 'submit', false ); ?>
							<a class="button" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Cancel', 'wpfaevent' ); ?></a>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
