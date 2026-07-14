<?php
/**
 * Sponsors and Exhibitors administration/CRUD management page.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wpfaevent_Partner_Dashboard
 */
class Wpfaevent_Partner_Dashboard {

	/**
	 * Storage helper.
	 *
	 * @var Wpfaevent_Eventyay_Dashboard_Store
	 */
	private $store;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->store = new Wpfaevent_Eventyay_Dashboard_Store();
	}

	/**
	 * Register the submenu pages for Sponsors and Exhibitors.
	 */
	public function register_menu_pages() {
		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Sponsors', 'wpfaevent' ),
			esc_html__( 'Sponsors', 'wpfaevent' ),
			'edit_events',
			'wpfaevent-sponsors',
			array( $this, 'render_sponsors_page' )
		);

		add_submenu_page(
			'edit.php?post_type=wpfa_event',
			esc_html__( 'Exhibitors', 'wpfaevent' ),
			esc_html__( 'Exhibitors', 'wpfaevent' ),
			'edit_events',
			'wpfaevent-exhibitors',
			array( $this, 'render_exhibitors_page' )
		);
	}

	/**
	 * Render Sponsors Management Page.
	 */
	public function render_sponsors_page() {
		$this->render_dashboard_page( 'sponsor' );
	}

	/**
	 * Render Exhibitors Management Page.
	 */
	public function render_exhibitors_page() {
		$this->render_dashboard_page( 'exhibitor' );
	}

	/**
	 * Helper function to retrieve all events.
	 *
	 * @return array<int, string> Map of event ID to event title.
	 */
	private function get_events() {
		$events = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$event_options = array();
		foreach ( $events as $event ) {
			$event_options[ $event->ID ] = $event->post_title;
		}

		return $event_options;
	}

	/**
	 * Unified layout generator for both Sponsors and Exhibitors dashboards.
	 *
	 * @param string $type Accepts 'sponsor' or 'exhibitor'.
	 */
	private function render_dashboard_page( $type ) {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$event_options = $this->get_events();
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
		$records = $this->load_records( $type, $event_id );

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

		$filtered_records = array();
		$categories       = array();

		foreach ( $records as $rec ) {
			$rec_cat = 'sponsor' === $type ? ( isset( $rec['type'] ) ? $rec['type'] : '' ) : ( isset( $rec['type'] ) ? $rec['type'] : '' );
			if ( $rec_cat && ! in_array( $rec_cat, $categories, true ) ) {
				$categories[] = $rec_cat;
			}

			// Apply Search.
			if ( $search_query ) {
				$name_match          = isset( $rec['name'] ) && false !== stripos( $rec['name'], $search_query );
				$company_match       = isset( $rec['company'] ) && false !== stripos( $rec['company'], $search_query );
				$email_match         = isset( $rec['email'] ) && false !== stripos( $rec['email'], $search_query );
				$contact_email_match = isset( $rec['contact_email'] ) && false !== stripos( $rec['contact_email'], $search_query );
				if ( ! $name_match && ! $company_match && ! $email_match && ! $contact_email_match ) {
					continue;
				}
			}

			// Apply Status Filter.
			if ( $status_filter ) {
				$status = isset( $rec['status'] ) ? $rec['status'] : 'active';
				if ( $status !== $status_filter ) {
					continue;
				}
			}

			// Apply Category/Package Filter.
			if ( $cat_filter ) {
				if ( $rec_cat !== $cat_filter ) {
					continue;
				}
			}

			$filtered_records[] = $rec;
		}

		// Sorting.
		usort(
			$filtered_records,
			function ( $a, $b ) use ( $orderby, $order ) {
				$val_a = '';
				$val_b = '';

				if ( 'name' === $orderby ) {
					$val_a = isset( $a['name'] ) ? $a['name'] : '';
					$val_b = isset( $b['name'] ) ? $b['name'] : '';
				} elseif ( 'company' === $orderby ) {
					$val_a = isset( $a['company'] ) ? $a['company'] : '';
					$val_b = isset( $b['company'] ) ? $b['company'] : '';
				} elseif ( 'status' === $orderby ) {
					$val_a = isset( $a['status'] ) ? $a['status'] : 'active';
					$val_b = isset( $b['status'] ) ? $b['status'] : 'active';
				} elseif ( 'date' === $orderby ) {
					$val_a = isset( $a['created_at'] ) ? $a['created_at'] : '';
					$val_b = isset( $b['created_at'] ) ? $b['created_at'] : '';
				}

				if ( 'asc' === $order ) {
					return strcasecmp( $val_a, $val_b );
				} else {
					return strcasecmp( $val_b, $val_a );
				}
			}
		);

		// Pagination.
		$total_items = count( $filtered_records );
		$per_page    = 10;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$total_pages  = ceil( $total_items / $per_page );
		$offset       = ( $current_page - 1 ) * $per_page;
		$paged_items  = array_slice( $filtered_records, $offset, $per_page );

		// Stats.
		$active_count   = 0;
		$inactive_count = 0;
		foreach ( $records as $rec ) {
			$status = isset( $rec['status'] ) ? $rec['status'] : 'active';
			if ( 'inactive' === $status ) {
				++$inactive_count;
			} else {
				++$active_count;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		?>
		<div class="wrap">
			<div class="wpfaevent-dashboard-shell">
				<!-- Hero Section -->
				<div class="wpfaevent-dashboard-hero">
					<div class="wpfaevent-dashboard-meta" style="display:flex; justify-content:space-between; align-items:center; width:100%; margin-bottom:10px;">
						<div class="wpfaevent-badge"><?php echo esc_html( $type_label_plural ); ?> Hub</div>
						<?php
						$dashboard_page = new Wpfaevent_Event_Dashboard_Page();
						$dashboard_url  = $dashboard_page->get_dashboard_url( $event_id );
						?>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="wpfaevent-dashboard-tab" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); border-radius:8px; padding:6px 12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
							&larr; <?php esc_html_e( 'Back to Event Dashboard', 'wpfaevent' ); ?>
						</a>
					</div>
					<p>
						<?php
						/* translators: %s: Type label plural in lowercase. */
						printf( esc_html__( 'Manage all %1$s for your selected event. Maintain contact info, company details, website links, and status.', 'wpfaevent' ), esc_html( strtolower( $type_label_plural ) ) );
						?>
					</p>

					<div style="margin-top:16px;">
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
						<p class="wpfaevent-kpi"><?php echo esc_html( (string) count( $records ) ); ?></p>
						<p class="description">
							<?php
							/* translators: %s: Type label plural in lowercase. */
							printf( esc_html__( 'Registered on this event.', 'wpfaevent' ), esc_html( strtolower( $type_label_plural ) ) );
							?>
						</p>
					</div>
					<div class="wpfaevent-dashboard-card">
						<h2><?php esc_html_e( 'Active Records', 'wpfaevent' ); ?></h2>
						<p class="wpfaevent-kpi" style="color: #2e7d32;"><?php echo esc_html( (string) $active_count ); ?></p>
						<p class="description"><?php esc_html_e( 'Currently active and visible.', 'wpfaevent' ); ?></p>
					</div>
					<div class="wpfaevent-dashboard-card">
						<h2><?php esc_html_e( 'Inactive Records', 'wpfaevent' ); ?></h2>
						<p class="wpfaevent-kpi" style="color: #c62828;"><?php echo esc_html( (string) $inactive_count ); ?></p>
						<p class="description"><?php esc_html_e( 'Hidden or legacy profiles.', 'wpfaevent' ); ?></p>
					</div>
				</div>

				<!-- List Table Card -->
				<div class="wpfaevent-dashboard-card">
					<div style="display:flex; justify-content:space-between; flex-wrap:wrap; align-items:center; margin-bottom:15px; gap:12px;">
						<h2>
							<?php
							/* translators: %s: Type label plural. */
							printf( esc_html__( 'All %s List', 'wpfaevent' ), esc_html( $type_label_plural ) );
							?>
						</h2>

						<!-- Filters & Search Form -->
						<form method="get" action="" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
							<input type="hidden" name="post_type" value="wpfa_event">
							<input type="hidden" name="page" value="<?php echo esc_attr( $current_page_slug ); ?>">
							<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">

							<select name="status" aria-label="<?php esc_html_e( 'Filter by status', 'wpfaevent' ); ?>">
								<option value=""><?php esc_html_e( 'All Statuses', 'wpfaevent' ); ?></option>
								<option value="active" <?php selected( $status_filter, 'active' ); ?>><?php esc_html_e( 'Active', 'wpfaevent' ); ?></option>
								<option value="inactive" <?php selected( $status_filter, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wpfaevent' ); ?></option>
							</select>

							<?php if ( ! empty( $categories ) ) : ?>
								<select name="cat" aria-label="<?php esc_html_e( 'Filter by package', 'wpfaevent' ); ?>">
									<option value=""><?php esc_html_e( 'All Packages', 'wpfaevent' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $cat_filter, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>

							<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_html_e( 'Search...', 'wpfaevent' ); ?>" style="max-width:180px;">
							<input type="submit" class="button" value="<?php esc_html_e( 'Filter', 'wpfaevent' ); ?>">
						</form>
					</div>

					<?php if ( empty( $paged_items ) ) : ?>
						<div style="padding:40px 20px; text-align:center; background:#fbfdff; border-radius:12px; border:1px dashed var(--wpfa-border);">
							<span class="dashicons dashicons-id" style="font-size:48px; width:48px; height:48px; color:var(--wpfa-slate); margin-bottom:12px;"></span>
							<h3>
								<?php
								/* translators: %s: Type label plural in lowercase. */
								printf( esc_html__( 'No %s found', 'wpfaevent' ), esc_html( strtolower( $type_label_plural ) ) );
								?>
							</h3>
							<p class="description">
								<?php
								/* translators: 1: Type label plural in lowercase, 2: Type label singular in lowercase. */
								printf( esc_html__( 'There are no %1$s registered for this event matching the filters. Get started by adding a new %2$s.', 'wpfaevent' ), esc_html( strtolower( $type_label_plural ) ), esc_html( strtolower( $type_label ) ) );
								?>
							</p>
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
																	" style="margin-top:8px;">
								+ 
								<?php
								/* translators: %s: Type label singular. */
								printf( esc_html__( 'Add New %s', 'wpfaevent' ), esc_html( $type_label ) );
								?>
							</a>
						</div>
					<?php else : ?>
						<div style="overflow-x:auto;">
							<table class="wp-list-table widefat fixed striped table-view-list" style="border:none; box-shadow:none; background:none;">
								<thead>
									<tr>
										<th scope="col" style="width:70px;"><?php esc_html_e( 'Logo', 'wpfaevent' ); ?></th>
										<th scope="col">
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
													<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?>" style="font-size:16px;"></span>
												<?php endif; ?>
											</a>
										</th>
										<th scope="col">
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
													<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?>" style="font-size:16px;"></span>
												<?php endif; ?>
											</a>
										</th>
										<th scope="col"><?php esc_html_e( 'Contacts', 'wpfaevent' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Package/Category', 'wpfaevent' ); ?></th>
										<th scope="col" style="width:100px;">
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
													<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?>" style="font-size:16px;"></span>
												<?php endif; ?>
											</a>
										</th>
										<th scope="col" style="width:120px;">
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
													<span class="dashicons dashicons-arrow-<?php echo 'asc' === $order ? 'up' : 'down'; ?>" style="font-size:16px;"></span>
												<?php endif; ?>
											</a>
										</th>
										<th scope="col" style="width:120px; text-align:right;"><?php esc_html_e( 'Actions', 'wpfaevent' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $paged_items as $item ) : ?>
										<?php
										$id          = isset( $item['id'] ) ? $item['id'] : '';
										$name        = isset( $item['name'] ) ? $item['name'] : '';
										$company     = isset( $item['company'] ) ? $item['company'] : '';
										$email       = 'sponsor' === $type ? ( isset( $item['email'] ) ? $item['email'] : '' ) : ( isset( $item['contact_email'] ) ? $item['contact_email'] : '' );
										$phone       = isset( $item['phone'] ) ? $item['phone'] : '';
										$logo        = 'sponsor' === $type ? ( isset( $item['image'] ) ? $item['image'] : '' ) : ( isset( $item['logo'] ) ? $item['logo'] : '' );
										$link        = isset( $item['link'] ) ? $item['link'] : '';
										$cat         = isset( $item['type'] ) ? $item['type'] : '';
										$status      = isset( $item['status'] ) ? $item['status'] : 'active';
										$created     = isset( $item['created_at'] ) ? $item['created_at'] : '';
										$is_eventyay = isset( $item['source'] ) && 'eventyay' === $item['source'];

										/* translators: %s: record name */
										$edit_label = sprintf( __( 'Edit %s', 'wpfaevent' ), $name );
										/* translators: %s: record name */
										$delete_label = sprintf( __( 'Delete %s', 'wpfaevent' ), $name );
										/* translators: %s: Type label singular. */
										$confirm_msg = sprintf( __( 'Are you sure you want to delete this %s?', 'wpfaevent' ), strtolower( $type_label ) );
										?>
										<tr>
											<td>
												<?php if ( $logo ) : ?>
													<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" style="width:40px; height:40px; object-fit:contain; border-radius:6px; border:1px solid var(--wpfa-border); background:#fff;">
												<?php else : ?>
													<div style="width:40px; height:40px; background:#eef4f8; border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--wpfa-blue-dark); font-weight:bold; font-size:14px;">
														<?php echo esc_html( strtoupper( substr( $name, 0, 1 ) ) ); ?>
													</div>
												<?php endif; ?>
											</td>
											<td>
												<strong><?php echo esc_html( $name ); ?></strong>
												<?php if ( $is_eventyay ) : ?>
													<span class="wpfaevent-badge is-neutral" style="font-size:10px; padding:2px 6px; margin-left:6px;">Eventyay</span>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $company ); ?></td>
											<td>
												<?php if ( $email ) : ?>
													<div><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
												<?php endif; ?>
												<?php if ( $phone ) : ?>
													<div style="font-size:11px; color:#666;"><?php echo esc_html( $phone ); ?></div>
												<?php endif; ?>
												<?php if ( $link ) : ?>
													<div style="font-size:11px;"><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website ↗', 'wpfaevent' ); ?></a></div>
												<?php endif; ?>
											</td>
											<td><span class="wpfaevent-tag"><?php echo esc_html( $cat ? $cat : __( 'Standard', 'wpfaevent' ) ); ?></span></td>
											<td>
												<?php if ( 'active' === $status ) : ?>
													<span style="display:inline-flex; align-items:center; gap:4px; color:#2e7d32; font-weight:600; font-size:12px;">
														<span style="width:8px; height:8px; border-radius:50%; background:#2e7d32;"></span>
														<?php esc_html_e( 'Active', 'wpfaevent' ); ?>
													</span>
												<?php else : ?>
													<span style="display:inline-flex; align-items:center; gap:4px; color:#c62828; font-weight:600; font-size:12px;">
														<span style="width:8px; height:8px; border-radius:50%; background:#c62828;"></span>
														<?php esc_html_e( 'Inactive', 'wpfaevent' ); ?>
													</span>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $created ? $created : '—' ); ?></td>
											<td style="text-align:right;">
												<a class="button button-small" href="
												<?php
												echo esc_url(
													add_query_arg(
														array(
															'action' => 'edit',
															'id' => $id,
															'event_id' => $event_id,
														)
													)
												);
												?>
																						" aria-label="<?php echo esc_attr( $edit_label ); ?>">
													<?php esc_html_e( 'Edit', 'wpfaevent' ); ?>
												</a>
												<?php
												$delete_url = wp_nonce_url(
													admin_url( 'admin-post.php?action=wpfaevent_delete_partner&type=' . $type . '&event_id=' . $event_id . '&id=' . $id ),
													'wpfaevent_delete_partner_' . $id
												);
												?>
												<a class="button button-link-delete button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( $confirm_msg ); ?>')" style="color:#c62828; margin-left:4px;" aria-label="<?php echo esc_attr( $delete_label ); ?>">
													<?php esc_html_e( 'Delete', 'wpfaevent' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<!-- Pagination UI. -->
						<?php if ( $total_pages > 1 ) : ?>
							<div class="tablenav bottom" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
								<span class="displaying-num">
									<?php
									/* translators: %d: Total items count. */
									printf( esc_html( _n( '%d item', '%d items', $total_items, 'wpfaevent' ) ), absint( $total_items ) );
									?>
								</span>
								<div class="pagination-links" style="display:flex; gap:6px;">
									<?php if ( $current_page > 1 ) : ?>
										<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">&lsaquo; <?php esc_html_e( 'Previous', 'wpfaevent' ); ?></a>
									<?php endif; ?>

									<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
										<a class="button <?php echo $i === $current_page ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( (string) $i ); ?></a>
									<?php endfor; ?>

									<?php if ( $current_page < $total_pages ) : ?>
										<a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'wpfaevent' ); ?> &rsaquo;</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
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
	private function render_form( $type, $event_id, $item = array() ) {
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
			<div class="wpfaevent-dashboard-shell" style="max-width: 800px;">
				<div class="wpfaevent-dashboard-hero">
					<div class="wpfaevent-dashboard-meta" style="display:flex; justify-content:space-between; align-items:center; width:100%; margin-bottom:10px;">
						<div class="wpfaevent-badge"><?php echo esc_html( $type_label_plural ); ?> Hub</div>
						<?php
						$dashboard_page = new Wpfaevent_Event_Dashboard_Page();
						$dashboard_url  = $dashboard_page->get_dashboard_url( $event_id );
						?>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" class="wpfaevent-dashboard-tab" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); border-radius:8px; padding:6px 12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
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

				<div class="wpfaevent-dashboard-card">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 15px;">
						<input type="hidden" name="action" value="wpfaevent_save_partner">
						<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
						<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">
						<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
						<?php wp_nonce_field( 'wpfaevent_save_partner_' . $id ); ?>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="partner-name"><?php esc_html_e( 'Name', 'wpfaevent' ); ?> <span class="description" style="color:#c62828;">*</span></label></th>
								<td>
									<input type="text" id="partner-name" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required>
									<p class="description">
										<?php
										/* translators: %s: Type label singular in lowercase. */
										printf( esc_html__( 'The main profile name of this %s.', 'wpfaevent' ), esc_html( strtolower( $type_label ) ) );
										?>
									</p>
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

						<div style="margin-top:20px; display:flex; gap:10px;">
							<?php submit_button( __( 'Save Profile', 'wpfaevent' ), 'primary', 'submit', false ); ?>
							<a class="button" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Cancel', 'wpfaevent' ); ?></a>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Load flat records array for sponsors or exhibitors of a given event.
	 *
	 * @param string $type     Accepts 'sponsor' or 'exhibitor'.
	 * @param int    $event_id Event ID.
	 * @return array
	 */
	private function load_records( $type, $event_id ) {
		if ( ! $event_id ) {
			return array();
		}

		if ( 'sponsor' === $type ) {
			$raw_groups = $this->store->read_dashboard_json_file( 'sponsors-' . $event_id . '.json', array() );
			$raw_groups = is_array( $raw_groups ) ? $raw_groups : array();
			$sponsors   = array();

			foreach ( $raw_groups as $group ) {
				if ( ! is_array( $group ) || empty( $group['sponsors'] ) || ! is_array( $group['sponsors'] ) ) {
					continue;
				}
				$group_name = isset( $group['group_name'] ) ? $group['group_name'] : '';
				foreach ( $group['sponsors'] as $sponsor ) {
					if ( is_array( $sponsor ) ) {
						if ( ! isset( $sponsor['type'] ) ) {
							$sponsor['type'] = $group_name;
						}
						$sponsors[] = $sponsor;
					}
				}
			}

			return $sponsors;
		} else {
			$exhibitors = $this->store->read_dashboard_json_file( 'exhibitors-' . $event_id . '.json', array() );
			return is_array( $exhibitors ) ? $exhibitors : array();
		}
	}

	/**
	 * POST Handler to Save Sponsor/Exhibitor.
	 */
	public function handle_save_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		check_admin_referer( 'wpfaevent_save_partner_' . $id );

		$type     = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id || ! in_array( $type, array( 'sponsor', 'exhibitor' ), true ) ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$company     = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$logo        = isset( $_POST['logo'] ) ? esc_url_raw( wp_unslash( $_POST['logo'] ) ) : '';
		$link        = isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '';
		$cat         = isset( $_POST['cat'] ) ? sanitize_text_field( wp_unslash( $_POST['cat'] ) ) : '';
		$status      = isset( $_POST['status'] ) && 'inactive' === $_POST['status'] ? 'inactive' : 'active';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		if ( ! $name ) {
			wp_die( esc_html__( 'The Name field is required.', 'wpfaevent' ) );
		}

		$records = $this->load_records( $type, $event_id );

		$is_new = false;
		if ( ! $id ) {
			$is_new = true;
			$id     = 'manual-' . $type . '-' . wp_generate_password( 8, false );
		}

		$new_record = array(
			'id'          => $id,
			'source'      => 'manual',
			'name'        => $name,
			'company'     => $company,
			'phone'       => $phone,
			'link'        => $link,
			'type'        => $cat,
			'status'      => $status,
			'description' => $description,
			'created_at'  => $is_new ? current_time( 'Y-m-d H:i:s' ) : '',
			'updated_at'  => current_time( 'Y-m-d H:i:s' ),
		);

		if ( 'sponsor' === $type ) {
			$new_record['email'] = $email;
			$new_record['image'] = $logo;
		} else {
			$new_record['contact_email'] = $email;
			$new_record['logo']          = $logo;
		}

		$updated_records = array();
		$found           = false;

		foreach ( $records as $rec ) {
			if ( isset( $rec['id'] ) && $rec['id'] === $id ) {
				$new_record['created_at'] = isset( $rec['created_at'] ) ? $rec['created_at'] : current_time( 'Y-m-d H:i:s' );
				$updated_records[]        = $new_record;
				$found                    = true;
			} else {
				$updated_records[] = $rec;
			}
		}

		if ( ! $found ) {
			$updated_records[] = $new_record;
		}

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			// Structure as sponsor groups.
			$groups = array();
			foreach ( $updated_records as $rec ) {
				$group_name = isset( $rec['type'] ) && $rec['type'] ? $rec['type'] : __( 'Sponsors', 'wpfaevent' );
				if ( ! isset( $groups[ $group_name ] ) ) {
					$groups[ $group_name ] = array(
						'group_name' => $group_name,
						'logo_size'  => 160,
						'sponsors'   => array(),
					);
					if ( isset( $rec['source'] ) && 'eventyay' === $rec['source'] ) {
						$groups[ $group_name ]['source'] = 'eventyay';
					}
				}
				$groups[ $group_name ]['sponsors'][] = $rec;
			}

			// Clean array keys for JSON serialization.
			$write_data = array_values( $groups );
			$this->store->write_dashboard_json_file( 'sponsors-' . $event_id . '.json', $write_data );
		} else {
			$this->store->write_dashboard_json_file( 'exhibitors-' . $event_id . '.json', $updated_records );
		}

		// Redirect back.
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'wpfa_event',
					'page'      => 'wpfaevent-' . $type . 's',
					'event_id'  => $event_id,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * GET Handler to Delete Sponsor/Exhibitor.
	 */
	public function handle_delete_partner() {
		if ( ! current_user_can( 'edit_events' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to modify this page.', 'wpfaevent' ) );
		}

		$id = isset( $_GET['id'] ) ? sanitize_key( $_GET['id'] ) : '';
		check_admin_referer( 'wpfaevent_delete_partner_' . $id );

		$type     = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;

		if ( ! $event_id || ! $id || ! in_array( $type, array( 'sponsor', 'exhibitor' ), true ) ) {
			wp_die( esc_html__( 'Invalid request parameters.', 'wpfaevent' ) );
		}

		$records         = $this->load_records( $type, $event_id );
		$updated_records = array();

		foreach ( $records as $rec ) {
			if ( isset( $rec['id'] ) && $rec['id'] === $id ) {
				continue; // Skip the deleted record.
			}
			$updated_records[] = $rec;
		}

		// Save the updated list back to the JSON file.
		if ( 'sponsor' === $type ) {
			// Structure as sponsor groups.
			$groups = array();
			foreach ( $updated_records as $rec ) {
				$group_name = isset( $rec['type'] ) && $rec['type'] ? $rec['type'] : __( 'Sponsors', 'wpfaevent' );
				if ( ! isset( $groups[ $group_name ] ) ) {
					$groups[ $group_name ] = array(
						'group_name' => $group_name,
						'logo_size'  => 160,
						'sponsors'   => array(),
					);
				}
				$groups[ $group_name ]['sponsors'][] = $rec;
			}

			$write_data = array_values( $groups );
			$this->store->write_dashboard_json_file( 'sponsors-' . $event_id . '.json', $write_data );
		} else {
			$this->store->write_dashboard_json_file( 'exhibitors-' . $event_id . '.json', $updated_records );
		}

		// Redirect back.
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'wpfa_event',
					'page'      => 'wpfaevent-' . $type . 's',
					'event_id'  => $event_id,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
