<?php
/**
 * Eventyay event import functionality.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 */

/**
 * Handles Eventyay settings and event post imports.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Eventyay_Importer {

	/**
	 * Sanitize Eventyay import options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input Raw option input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_eventyay_import_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->get_eventyay_import_default_settings();
		$current  = $this->get_eventyay_import_settings();
		$settings = $defaults;

		$base_url = isset( $input['base_url'] ) ? trim( (string) wp_unslash( $input['base_url'] ) ) : '';
		$base_url = $base_url ? esc_url_raw( $base_url ) : $defaults['base_url'];

		if ( ! wp_http_validate_url( $base_url ) ) {
			add_settings_error(
				'wpfaevent_eventyay_import',
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL must be a valid HTTP(S) URL.', 'wpfaevent' ),
				'error'
			);
			$base_url = $current['base_url'];
		}

		$settings['organizer_slug'] = isset( $input['organizer_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['organizer_slug'] ) : '';
		$settings['event_slug']     = isset( $input['event_slug'] ) ? $this->sanitize_eventyay_path_segment( $input['event_slug'] ) : '';
		$parsed_event_url           = $this->parse_eventyay_public_event_url( $base_url );

		if ( $parsed_event_url ) {
			$base_url = $parsed_event_url['base_url'];

			if ( empty( $settings['organizer_slug'] ) ) {
				$settings['organizer_slug'] = $parsed_event_url['organizer_slug'];
			}

			if ( empty( $settings['event_slug'] ) ) {
				$settings['event_slug'] = $parsed_event_url['event_slug'];
			}
		}

		$settings['base_url'] = untrailingslashit( $base_url );

		if ( ! empty( $input['clear_api_token'] ) ) {
			$settings['api_token'] = '';
		} elseif ( isset( $input['api_token'] ) && '' !== trim( (string) wp_unslash( $input['api_token'] ) ) ) {
			$settings['api_token'] = sanitize_text_field( wp_unslash( $input['api_token'] ) );
		} else {
			$settings['api_token'] = isset( $current['api_token'] ) ? $current['api_token'] : '';
		}

		$post_status = isset( $input['post_status'] ) ? sanitize_key( wp_unslash( $input['post_status'] ) ) : $defaults['post_status'];
		if ( ! in_array( $post_status, array( 'draft', 'publish', 'pending', 'private' ), true ) ) {
			$post_status = $defaults['post_status'];
		}
		$settings['post_status'] = $post_status;

		return $settings;
	}

	/**
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$settings         = $this->get_eventyay_import_settings();
		$endpoint_preview = ! empty( $settings['organizer_slug'] ) ? $this->build_eventyay_events_endpoint( $settings ) : '';
		$notice_key       = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();
		$notice           = get_transient( $notice_key );

		if ( $notice ) {
			delete_transient( $notice_key );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'wpfaevent_eventyay_import' ); ?>

			<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( ! empty( $notice['type'] ) ? $notice['type'] : 'info' ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="card" style="max-width: 960px;">
				<h2><?php esc_html_e( 'Eventyay Event Import', 'wpfaevent' ); ?></h2>
				<p>
					<?php esc_html_e( 'Import events from the current Eventyay REST API endpoint:', 'wpfaevent' ); ?>
					<code>/api/v1/organizers/{organizer}/events/</code>
				</p>
				<p class="description">
					<?php esc_html_e( 'When an event slug is provided, the importer also tries compatible event API URLs before reporting an event as not found.', 'wpfaevent' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
					<?php settings_fields( 'wpfaevent_eventyay_import' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="wpfaevent_eventyay_base_url"><?php esc_html_e( 'Eventyay base URL', 'wpfaevent' ); ?></label></th>
							<td>
								<input type="url" class="regular-text" id="wpfaevent_eventyay_base_url" name="wpfaevent_eventyay_import_settings[base_url]" value="<?php echo esc_attr( $settings['base_url'] ); ?>" placeholder="https://eventyay.com">
								<p class="description"><?php esc_html_e( 'Use the site root, not the API path. Self-hosted Eventyay installs are supported.', 'wpfaevent' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wpfaevent_eventyay_organizer_slug"><?php esc_html_e( 'Organizer slug', 'wpfaevent' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="wpfaevent_eventyay_organizer_slug" name="wpfaevent_eventyay_import_settings[organizer_slug]" value="<?php echo esc_attr( $settings['organizer_slug'] ); ?>" placeholder="bigevents">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wpfaevent_eventyay_event_slug"><?php esc_html_e( 'Event slug', 'wpfaevent' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="wpfaevent_eventyay_event_slug" name="wpfaevent_eventyay_import_settings[event_slug]" value="<?php echo esc_attr( $settings['event_slug'] ); ?>" placeholder="sampleconf">
								<p class="description"><?php esc_html_e( 'Leave empty to import all events visible to the token for this organizer.', 'wpfaevent' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wpfaevent_eventyay_api_token"><?php esc_html_e( 'API token', 'wpfaevent' ); ?></label></th>
							<td>
								<input type="password" class="regular-text" id="wpfaevent_eventyay_api_token" name="wpfaevent_eventyay_import_settings[api_token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( ! empty( $settings['api_token'] ) ? __( 'Token saved; leave blank to keep it', 'wpfaevent' ) : __( 'Optional for public endpoints', 'wpfaevent' ) ); ?>">
								<?php if ( ! empty( $settings['api_token'] ) ) : ?>
									<label style="display:block;margin-top:8px;">
										<input type="checkbox" name="wpfaevent_eventyay_import_settings[clear_api_token]" value="1">
										<?php esc_html_e( 'Clear saved token', 'wpfaevent' ); ?>
									</label>
								<?php endif; ?>
								<p class="description"><?php esc_html_e( 'Eventyay API tokens are sent as an Authorization header. The importer uses Token auth first and retries legacy Eventyay endpoints with JWT auth when needed. Keep this token private.', 'wpfaevent' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wpfaevent_eventyay_post_status"><?php esc_html_e( 'Imported post status', 'wpfaevent' ); ?></label></th>
							<td>
								<select id="wpfaevent_eventyay_post_status" name="wpfaevent_eventyay_import_settings[post_status]">
									<option value="draft" <?php selected( $settings['post_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'wpfaevent' ); ?></option>
									<option value="publish" <?php selected( $settings['post_status'], 'publish' ); ?>><?php esc_html_e( 'Published', 'wpfaevent' ); ?></option>
									<option value="pending" <?php selected( $settings['post_status'], 'pending' ); ?>><?php esc_html_e( 'Pending review', 'wpfaevent' ); ?></option>
									<option value="private" <?php selected( $settings['post_status'], 'private' ); ?>><?php esc_html_e( 'Private', 'wpfaevent' ); ?></option>
								</select>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Eventyay Settings', 'wpfaevent' ) ); ?>
				</form>

				<hr>

				<h3><?php esc_html_e( 'Import Events', 'wpfaevent' ); ?></h3>
				<?php if ( is_wp_error( $endpoint_preview ) ) : ?>
					<p><?php echo esc_html( $endpoint_preview->get_error_message() ); ?></p>
				<?php elseif ( $endpoint_preview ) : ?>
					<p>
						<?php esc_html_e( 'Current endpoint:', 'wpfaevent' ); ?>
						<code><?php echo esc_html( $endpoint_preview ); ?></code>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'Save an organizer slug before importing.', 'wpfaevent' ); ?></p>
				<?php endif; ?>
				<p class="description">
					<?php esc_html_e( 'Use this to import Eventyay events for the configured organizer. Use the Update Events menu item when Eventyay data changes after the initial import.', 'wpfaevent' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wpfaevent_import_eventyay_events">
					<input type="hidden" name="wpfaevent_eventyay_return_page" value="wpfaevent-import-events">
					<?php wp_nonce_field( 'wpfaevent_import_eventyay_events' ); ?>
					<?php submit_button( __( 'Import Events from Eventyay', 'wpfaevent' ), 'primary', 'submit', false, empty( $settings['organizer_slug'] ) ? array( 'disabled' => 'disabled' ) : array() ); ?>
				</form>
			</div>

				<div class="card" style="max-width: 960px;">
					<h2><?php esc_html_e( 'Where Imported Data Shows Up', 'wpfaevent' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'Events are saved as Events posts with Eventyay source metadata for repeat imports.', 'wpfaevent' ); ?></li>
						<li><?php esc_html_e( 'Event title, description, dates, timezone, location, and Eventyay URL are updated from the Eventyay API.', 'wpfaevent' ); ?></li>
						<li><?php esc_html_e( 'Speaker, schedule, sponsor, and exhibitor imports are handled by the follow-up Eventyay data import PR.', 'wpfaevent' ); ?></li>
					</ul>
				</div>
		</div>
		<?php
	}

	/**
	 * Render the Eventyay update page.
	 *
	 * @since 1.0.0
	 */
	public function render_update_events_page() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpfaevent' ) );
		}

		$settings         = $this->get_eventyay_import_settings();
		$endpoint_preview = ! empty( $settings['organizer_slug'] ) ? $this->build_eventyay_events_endpoint( $settings ) : '';
		$notice_key       = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();
		$notice           = get_transient( $notice_key );

		if ( $notice ) {
			delete_transient( $notice_key );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'wpfaevent_eventyay_import' ); ?>

			<?php if ( is_array( $notice ) && ! empty( $notice['message'] ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( ! empty( $notice['type'] ) ? $notice['type'] : 'info' ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="card" style="max-width: 960px;">
				<h2><?php esc_html_e( 'Update Events from Eventyay', 'wpfaevent' ); ?></h2>
				<p><?php esc_html_e( 'Run this when Eventyay data changes after events have already been imported.', 'wpfaevent' ); ?></p>
				<p class="description">
					<?php esc_html_e( 'Existing Eventyay-owned event posts are updated in place while source metadata is preserved for future imports.', 'wpfaevent' ); ?>
				</p>

				<?php if ( is_wp_error( $endpoint_preview ) ) : ?>
					<p><?php echo esc_html( $endpoint_preview->get_error_message() ); ?></p>
				<?php elseif ( $endpoint_preview ) : ?>
					<p>
						<?php esc_html_e( 'Current endpoint:', 'wpfaevent' ); ?>
						<code><?php echo esc_html( $endpoint_preview ); ?></code>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'Save an organizer slug on the Import Events page before updating.', 'wpfaevent' ); ?></p>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wpfaevent_import_eventyay_events">
					<input type="hidden" name="wpfaevent_eventyay_return_page" value="wpfaevent-update-events">
					<?php wp_nonce_field( 'wpfaevent_import_eventyay_events' ); ?>
					<?php submit_button( __( 'Update Events from Eventyay', 'wpfaevent' ), 'primary', 'submit', false, empty( $settings['organizer_slug'] ) ? array( 'disabled' => 'disabled' ) : array() ); ?>
				</form>

				<p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpfa_event&page=wpfaevent-import-events' ) ); ?>">
						<?php esc_html_e( 'Edit Eventyay import settings', 'wpfaevent' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Import Eventyay events using the saved newer REST API settings.
	 *
	 * @since 1.0.0
	 */
	public function handle_eventyay_events_import() {
		if ( ! Wpfaevent_Roles::current_user_can_import_eventyay() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to import Eventyay events.', 'wpfaevent' ) );
		}

		check_admin_referer( 'wpfaevent_import_eventyay_events' );

		$return_page = 'wpfaevent-import-events';
		if ( isset( $_POST['wpfaevent_eventyay_return_page'] ) ) {
			$return_page = sanitize_key( wp_unslash( $_POST['wpfaevent_eventyay_return_page'] ) );
		}

		if ( ! in_array( $return_page, array( 'wpfaevent-import-events', 'wpfaevent-update-events' ), true ) ) {
			$return_page = 'wpfaevent-import-events';
		}

		$result     = $this->import_eventyay_events_from_settings();
		$notice_key = 'wpfaevent_eventyay_import_notice_' . get_current_user_id();

		if ( is_wp_error( $result ) ) {
			set_transient(
				$notice_key,
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				),
				MINUTE_IN_SECONDS
			);
		} else {
			set_transient(
				$notice_key,
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: fetched events, 2: created events, 3: updated events, 4: skipped events. */
						esc_html__( 'Fetched %1$d Eventyay event(s). Created %2$d, updated %3$d, skipped %4$d.', 'wpfaevent' ),
						absint( $result['fetched'] ),
						absint( $result['created'] ),
						absint( $result['updated'] ),
						absint( $result['skipped'] )
					),
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=wpfa_event&page=' . $return_page ) );
		exit;
	}

	/**
	 * Get default Eventyay import settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_eventyay_import_default_settings() {
		return array(
			'base_url'       => 'https://eventyay.com',
			'organizer_slug' => '',
			'event_slug'     => '',
			'api_token'      => '',
			'post_status'    => 'draft',
		);
	}

	/**
	 * Get Eventyay import settings with defaults applied.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_eventyay_import_settings() {
		$settings = get_option( 'wpfaevent_eventyay_import_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
	}

	/**
	 * Sanitize a path segment used by Eventyay organizer and event slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw path segment.
	 * @return string
	 */
	private function sanitize_eventyay_path_segment( $value ) {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );

		return preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
	}

	/**
	 * Parse a public Eventyay event URL into root URL and path slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Public Eventyay URL.
	 * @return array<string, string>
	 */
	private function parse_eventyay_public_event_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
			return array();
		}

		$path = trim( $parts['path'], '/' );
		if ( '' === $path || 0 === strpos( $path, 'api/' ) || 0 === strpos( $path, 'v1/' ) ) {
			return array();
		}

		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( count( $segments ) < 2 ) {
			return array();
		}

		$organizer_slug = $this->sanitize_eventyay_path_segment( $segments[0] );
		$event_slug     = $this->sanitize_eventyay_path_segment( $segments[1] );

		if ( empty( $organizer_slug ) || empty( $event_slug ) ) {
			return array();
		}

		$base_url = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$base_url .= ':' . absint( $parts['port'] );
		}

		return array(
			'base_url'       => esc_url_raw( $base_url ),
			'organizer_slug' => $organizer_slug,
			'event_slug'     => $event_slug,
		);
	}

	/**
	 * Import Eventyay event resources from saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Import result.
	 */
	private function import_eventyay_events_from_settings() {
		$settings = $this->get_eventyay_import_settings();

		if ( empty( $settings['organizer_slug'] ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'Please save an Eventyay organizer slug before importing.', 'wpfaevent' )
			);
		}

		$fetched = $this->fetch_eventyay_event_resources( $settings );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		$events = isset( $fetched['events'] ) && is_array( $fetched['events'] ) ? $fetched['events'] : array();
		if ( empty( $events ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_no_events',
				esc_html__( 'No Eventyay events were returned by the configured endpoint.', 'wpfaevent' )
			);
		}

		$result = array(
			'fetched' => count( $events ),
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
		);

		foreach ( $events as $event ) {
			$upsert = $this->upsert_eventyay_event_post( $event, $settings );

			if ( is_wp_error( $upsert ) ) {
				++$result['skipped'];
				continue;
			}

			if ( ! empty( $upsert['created'] ) ) {
				++$result['created'];
			} else {
				++$result['updated'];
			}
		}

		return $result;
	}

	/**
	 * Fetch Eventyay event resources, following paginated list responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array|WP_Error Event resources and metadata.
	 */
	private function fetch_eventyay_event_resources( $settings ) {
		$endpoints = $this->build_eventyay_event_endpoint_candidates( $settings );
		if ( is_wp_error( $endpoints ) ) {
			return $endpoints;
		}

		$not_found_errors = array();

		foreach ( $endpoints as $endpoint ) {
			$fetched = $this->fetch_eventyay_event_resources_from_endpoint( $endpoint, $settings );
			if ( ! is_wp_error( $fetched ) ) {
				return $fetched;
			}

			if ( ! $this->eventyay_error_has_http_status( $fetched, 404 ) ) {
				return $fetched;
			}

			$not_found_errors[] = $fetched;
		}

		return $this->eventyay_event_not_found_error( $endpoints, $not_found_errors );
	}

	/**
	 * Fetch Eventyay events from one endpoint, following paginated responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint Event endpoint URL.
	 * @param array  $settings Import settings.
	 * @return array|WP_Error Event resources and metadata.
	 */
	private function fetch_eventyay_event_resources_from_endpoint( $endpoint, $settings ) {
		if ( empty( $endpoint ) || ! wp_http_validate_url( $endpoint ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$events    = array();
		$next_url  = $endpoint;
		$page      = 0;
		$seen_urls = array();
		$max_pages = absint( apply_filters( 'wpfaevent_eventyay_import_max_pages', 20 ) );

		if ( ! $max_pages ) {
			$max_pages = 20;
		}

		while ( $next_url ) {
			if ( isset( $seen_urls[ $next_url ] ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_pagination_loop',
					esc_html__( 'Eventyay pagination returned a repeated next URL.', 'wpfaevent' )
				);
			}

			if ( $page >= $max_pages ) {
				return new WP_Error(
					'wpfaevent_eventyay_page_limit',
					esc_html__( 'Eventyay import stopped before completion because the pagination page limit was reached.', 'wpfaevent' )
				);
			}

			$seen_urls[ $next_url ] = true;
			++$page;

			$current_url = $next_url;
			$payload     = $this->fetch_eventyay_rest_json( $current_url, $settings['api_token'] );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
				foreach ( $payload['results'] as $event ) {
					if ( is_array( $event ) ) {
						$events[] = $this->hydrate_eventyay_event_resource( $event, $settings, true );
					}
				}

				$next_url = ! empty( $payload['next'] ) ? $this->normalize_eventyay_next_url( $payload['next'], $current_url ) : '';
				if ( is_wp_error( $next_url ) ) {
					return $next_url;
				}
				continue;
			}

			foreach ( $this->extract_eventyay_event_resources( $payload ) as $event ) {
				$events[] = $this->hydrate_eventyay_event_resource( $event, $settings, false );
			}
			$next_url = '';
		}

		return array(
			'events'   => $events,
			'pages'    => $page,
			'endpoint' => $endpoint,
		);
	}

	/**
	 * Build Eventyay event endpoints to try for the saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array|WP_Error Endpoint URLs.
	 */
	private function build_eventyay_event_endpoint_candidates( $settings ) {
		$primary_endpoint = $this->build_eventyay_events_endpoint( $settings );
		if ( is_wp_error( $primary_endpoint ) ) {
			return $primary_endpoint;
		}

		$endpoints = array( $primary_endpoint );

		if ( ! empty( $settings['event_slug'] ) ) {
			$endpoints = array_merge(
				$endpoints,
				$this->build_eventyay_legacy_event_endpoint_candidates( $settings )
			);
		}

		return array_values( array_unique( array_filter( $endpoints ) ) );
	}

	/**
	 * Build legacy Open Event API endpoints for event-slug imports.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return array Endpoint URLs.
	 */
	private function build_eventyay_legacy_event_endpoint_candidates( $settings ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $settings['event_slug'] );

		if ( empty( $event_slug ) || empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return array();
		}

		$api_bases   = array( $base_url );
		$api_bases[] = apply_filters( 'wpfaevent_eventyay_legacy_api_base_url', 'https://api.eventyay.com', $settings );
		$endpoints   = array();

		foreach ( array_unique( array_filter( $api_bases ) ) as $api_base ) {
			$api_base = untrailingslashit( esc_url_raw( $api_base ) );
			if ( empty( $api_base ) || ! wp_http_validate_url( $api_base ) ) {
				continue;
			}

			$endpoints[] = esc_url_raw(
				trailingslashit( $this->trim_eventyay_legacy_api_version_path( $api_base ) ) .
				'v1/events/' .
				rawurlencode( $event_slug )
			);
		}

		return $endpoints;
	}

	/**
	 * Trim a trailing /v1 path before building legacy Open Event API URLs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_base API base URL.
	 * @return string Base URL without a trailing /v1 path.
	 */
	private function trim_eventyay_legacy_api_version_path( $api_base ) {
		return preg_replace( '#/v1/?$#', '', untrailingslashit( $api_base ) );
	}

	/**
	 * Check whether a WP_Error represents a given HTTP status.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error       Error object.
	 * @param int      $http_status HTTP status code.
	 * @return bool
	 */
	private function eventyay_error_has_http_status( $error, $http_status ) {
		if ( ! is_wp_error( $error ) ) {
			return false;
		}

		$data = $error->get_error_data();

		return is_array( $data )
			&& isset( $data['http_status'] )
			&& absint( $data['http_status'] ) === absint( $http_status );
	}

	/**
	 * Build a clear event-not-found error after all candidate endpoints fail.
	 *
	 * @since 1.0.0
	 *
	 * @param array $endpoints        Endpoint URLs that were tried.
	 * @param array $not_found_errors 404 errors returned by the endpoints.
	 * @return WP_Error
	 */
	private function eventyay_event_not_found_error( $endpoints, $not_found_errors ) {
		$tried_endpoints = implode( ', ', array_map( 'esc_url_raw', $endpoints ) );

		return new WP_Error(
			'wpfaevent_eventyay_event_not_found',
			sprintf(
				/* translators: %s: comma-separated Eventyay API endpoint URLs. */
				esc_html__( 'Could not find the Eventyay event. Tried: %s. Please confirm the event exists, the event is visible to your API token, and the API base URL, organizer slug, and event slug are correct.', 'wpfaevent' ),
				$tried_endpoints
			),
			array(
				'http_status' => 404,
				'endpoints'   => array_map( 'esc_url_raw', $endpoints ),
				'errors'      => array_map(
					static function ( $error ) {
						return is_wp_error( $error ) ? $error->get_error_message() : '';
					},
					$not_found_errors
				),
			)
		);
	}

	/**
	 * Build an Eventyay events endpoint from saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Import settings.
	 * @return string|WP_Error Endpoint URL.
	 */
	private function build_eventyay_events_endpoint( $settings ) {
		$settings = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url = untrailingslashit( esc_url_raw( $settings['base_url'] ) );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_organizer',
				esc_html__( 'The Eventyay organizer slug is missing.', 'wpfaevent' )
			);
		}

		$path = sprintf(
			'api/v1/organizers/%s/events/',
			rawurlencode( $settings['organizer_slug'] )
		);

		if ( ! empty( $settings['event_slug'] ) ) {
			$path .= rawurlencode( $settings['event_slug'] ) . '/';
		}

			$url        = trailingslashit( $base_url ) . $path;
			$query_args = array(
				'lang' => 'en',
			);

			if ( empty( $settings['event_slug'] ) ) {
				$query_args['page_size'] = absint( apply_filters( 'wpfaevent_eventyay_import_page_size', 100 ) );
			}

			return esc_url_raw( add_query_arg( $query_args, $url ) );
	}

		/**
		 * Build the newer Eventyay event detail endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_event_endpoint( $settings, $event_slug ) {
		$settings               = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$settings['event_slug'] = $this->sanitize_eventyay_path_segment( $event_slug );

		return $this->build_eventyay_events_endpoint( $settings );
	}

		/**
		 * Build the newer Eventyay event settings endpoint for an imported event.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return string|WP_Error Endpoint URL.
		 */
	private function build_eventyay_event_settings_endpoint( $settings, $event_slug ) {
		$settings   = wp_parse_args( $settings, $this->get_eventyay_import_default_settings() );
		$base_url   = untrailingslashit( esc_url_raw( $settings['base_url'] ) );
		$event_slug = $this->sanitize_eventyay_path_segment( $event_slug );

		if ( empty( $base_url ) || ! wp_http_validate_url( $base_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_base_url',
				esc_html__( 'The Eventyay API base URL is invalid.', 'wpfaevent' )
			);
		}

		if ( empty( $settings['organizer_slug'] ) || empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_missing_event_settings_path',
				esc_html__( 'The Eventyay organizer or event slug is missing for settings import.', 'wpfaevent' )
			);
		}

		$url = trailingslashit( $base_url ) . sprintf(
			'api/v1/organizers/%s/events/%s/settings/',
			rawurlencode( $settings['organizer_slug'] ),
			rawurlencode( $event_slug )
		);

		return esc_url_raw( add_query_arg( 'lang', 'en', $url ) );
	}

		/**
		 * Normalize and enrich an Eventyay event returned from the list endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event        Eventyay event resource.
		 * @param array $settings     Import settings.
		 * @param bool  $fetch_detail Whether to fetch the detail endpoint if the list item is sparse.
		 * @return array
		 */
	private function hydrate_eventyay_event_resource( $event, $settings, $fetch_detail ) {
		$event      = $this->normalize_eventyay_event_resource( $event );
		$event_slug = $this->eventyay_event_slug( $event );

		if ( $fetch_detail && $event_slug && $this->eventyay_event_resource_needs_detail( $event ) ) {
			$detail_endpoint = $this->build_eventyay_event_endpoint( $settings, $event_slug );

			if ( ! is_wp_error( $detail_endpoint ) ) {
				$detail_payload = $this->fetch_eventyay_rest_json( $detail_endpoint, $settings['api_token'] );

				if ( ! is_wp_error( $detail_payload ) ) {
					foreach ( $this->extract_eventyay_event_resources( $detail_payload ) as $detail_event ) {
						$event = $this->merge_eventyay_event_resource( $event, $detail_event );
						break;
					}
				}
			}
		}

		if ( $event_slug && apply_filters( 'wpfaevent_eventyay_import_fetch_settings', true, $event, $settings ) ) {
			$settings_payload = $this->fetch_eventyay_event_settings_resource( $settings, $event_slug );

			if ( ! is_wp_error( $settings_payload ) && is_array( $settings_payload ) && ! empty( $settings_payload ) ) {
				$event['_eventyay_settings'] = $settings_payload;
			}
		}

		return $event;
	}

		/**
		 * Extract Eventyay event resources from REST or JSON:API-shaped payloads.
		 *
		 * @since 1.0.0
		 *
		 * @param array $payload API payload.
		 * @return array
		 */
	private function extract_eventyay_event_resources( $payload ) {
		$resources = array();

		if ( ! is_array( $payload ) ) {
			return $resources;
		}

		if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
			foreach ( $payload['results'] as $event ) {
				if ( is_array( $event ) ) {
					$resources[] = $this->normalize_eventyay_event_resource( $event );
				}
			}

			return $resources;
		}

		if ( array_key_exists( 'data', $payload ) && is_array( $payload['data'] ) ) {
			if ( $this->is_jsonapi_resource( $payload['data'] ) ) {
				return array( $this->normalize_eventyay_event_resource( $payload['data'] ) );
			}

			foreach ( $payload['data'] as $event ) {
				if ( is_array( $event ) ) {
					$resources[] = $this->normalize_eventyay_event_resource( $event );
				}
			}

			return $resources;
		}

		return array( $this->normalize_eventyay_event_resource( $payload ) );
	}

		/**
		 * Normalize an Eventyay event resource into a flat field map.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return array
		 */
	private function normalize_eventyay_event_resource( $event ) {
		if ( ! is_array( $event ) ) {
			return array();
		}

		if ( array_key_exists( 'data', $event ) && is_array( $event['data'] ) && $this->is_jsonapi_resource( $event['data'] ) ) {
			$event = $event['data'];
		}

		$normalized = $event;

		if ( isset( $event['attributes'] ) && is_array( $event['attributes'] ) ) {
			$normalized = array_merge( $event['attributes'], $normalized );
		}

		if ( isset( $event['id'] ) && is_scalar( $event['id'] ) ) {
			$normalized['_eventyay_source_id'] = sanitize_text_field( (string) $event['id'] );
		}

		if ( ! empty( $event['links']['self'] ) && is_scalar( $event['links']['self'] ) ) {
			$normalized['_eventyay_api_url'] = esc_url_raw( (string) $event['links']['self'] );
		}

		return $normalized;
	}

		/**
		 * Determine whether a list event should be hydrated through its detail endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Normalized Eventyay event resource.
		 * @return bool
		 */
	private function eventyay_event_resource_needs_detail( $event ) {
		$has_start    = '' !== trim( $this->eventyay_event_datetime( $event, 'start' ) );
		$has_location = '' !== trim( $this->eventyay_event_location( $event ) );

		return ! ( $has_start && $has_location );
	}

		/**
		 * Merge a sparse Eventyay event with a hydrated detail event.
		 *
		 * @since 1.0.0
		 *
		 * @param array $base   Existing event fields.
		 * @param array $detail Detail event fields.
		 * @return array
		 */
	private function merge_eventyay_event_resource( $base, $detail ) {
		$base   = $this->normalize_eventyay_event_resource( $base );
		$detail = $this->normalize_eventyay_event_resource( $detail );

		foreach ( $detail as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = $this->merge_eventyay_event_resource( $base[ $key ], $value );
				continue;
			}

			if ( $this->eventyay_value_is_non_empty( $value ) || ! array_key_exists( $key, $base ) ) {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

		/**
		 * Fetch event-level settings such as frontpage/about text when available.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings   Import settings.
		 * @param string $event_slug Eventyay event slug.
		 * @return array|WP_Error
		 */
	private function fetch_eventyay_event_settings_resource( $settings, $event_slug ) {
		$settings_endpoint = $this->build_eventyay_event_settings_endpoint( $settings, $event_slug );
		if ( is_wp_error( $settings_endpoint ) ) {
			return $settings_endpoint;
		}

		return $this->fetch_eventyay_rest_json( $settings_endpoint, $settings['api_token'] );
	}

		/**
		 * Determine whether an Eventyay value is meaningfully populated.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $value Raw Eventyay value.
		 * @return bool
		 */
	private function eventyay_value_is_non_empty( $value ) {
		if ( is_scalar( $value ) ) {
			return '' !== trim( (string) $value );
		}

		return is_array( $value ) && ! empty( $value );
	}

	/**
	 * Normalize a paginated Eventyay next URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $next_url      Raw next URL.
	 * @param string $reference_url Current request URL used to resolve relative next links.
	 * @return string|WP_Error
	 */
	private function normalize_eventyay_next_url( $next_url, $reference_url ) {
		$next_url = trim( (string) $next_url );

		if ( empty( $next_url ) ) {
			return '';
		}

		$reference_url = untrailingslashit( esc_url_raw( $reference_url ) );
		if ( empty( $reference_url ) || ! wp_http_validate_url( $reference_url ) ) {
			return '';
		}

		$next_parts = wp_parse_url( $next_url );
		if ( ! empty( $next_parts['scheme'] ) || ! empty( $next_parts['host'] ) ) {
			if ( ! $this->eventyay_urls_share_origin( $next_url, $reference_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_untrusted_next_url',
					esc_html__( 'Eventyay pagination returned a next URL outside the configured Eventyay host.', 'wpfaevent' )
				);
			}

			if ( ! wp_http_validate_url( $next_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_invalid_next_url',
					esc_html__( 'Eventyay pagination returned an invalid next URL.', 'wpfaevent' )
				);
			}

			return esc_url_raw( $next_url );
		}

		if ( 0 === strpos( $next_url, '?' ) ) {
			$base_path = preg_replace( '/[?#].*$/', '', $reference_url );
			$next_url  = $base_path . $next_url;

			if ( ! wp_http_validate_url( $next_url ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_invalid_next_url',
					esc_html__( 'Eventyay pagination returned an invalid next URL.', 'wpfaevent' )
				);
			}

			return esc_url_raw( $next_url );
		}

		$base_origin = $this->eventyay_url_origin( $reference_url );
		if ( empty( $base_origin ) ) {
			return '';
		}

		return esc_url_raw( trailingslashit( $base_origin ) . ltrim( $next_url, '/' ) );
	}

	/**
	 * Check whether two URLs share scheme, host, and port.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url      Candidate URL.
	 * @param string $base_url Configured base URL.
	 * @return bool
	 */
	private function eventyay_urls_share_origin( $url, $base_url ) {
		$url_parts  = wp_parse_url( $url );
		$base_parts = wp_parse_url( $base_url );

		if ( empty( $url_parts['scheme'] ) || empty( $url_parts['host'] ) || empty( $base_parts['scheme'] ) || empty( $base_parts['host'] ) ) {
			return false;
		}

		$url_scheme  = strtolower( $url_parts['scheme'] );
		$base_scheme = strtolower( $base_parts['scheme'] );
		$url_port    = isset( $url_parts['port'] ) ? absint( $url_parts['port'] ) : $this->default_port_for_scheme( $url_scheme );
		$base_port   = isset( $base_parts['port'] ) ? absint( $base_parts['port'] ) : $this->default_port_for_scheme( $base_scheme );

		return $url_scheme === $base_scheme
			&& strtolower( $url_parts['host'] ) === strtolower( $base_parts['host'] )
			&& $url_port === $base_port;
	}

	/**
	 * Get the scheme/host/port origin from a URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL or endpoint.
	 * @return string
	 */
	private function eventyay_url_origin( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$origin = strtolower( $parts['scheme'] ) . '://' . strtolower( $parts['host'] );
		if ( isset( $parts['port'] ) ) {
			$origin .= ':' . absint( $parts['port'] );
		}

		return wp_http_validate_url( $origin ) ? esc_url_raw( $origin ) : '';
	}

	/**
	 * Get the default network port for a URL scheme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $scheme URL scheme.
	 * @return int|null
	 */
	private function default_port_for_scheme( $scheme ) {
		if ( 'https' === $scheme ) {
			return 443;
		}

		if ( 'http' === $scheme ) {
			return 80;
		}

		return null;
	}

	/**
	 * Fetch and decode an Eventyay REST API response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url   API endpoint URL.
	 * @param string $api_token Optional API token.
	 * @return array|WP_Error
	 */
	private function fetch_eventyay_rest_json( $api_url, $api_token = '' ) {
		if ( empty( $api_url ) || ! wp_http_validate_url( $api_url ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_invalid_url',
				esc_html__( 'The Eventyay API URL is invalid.', 'wpfaevent' )
			);
		}

		$auth_schemes = $this->get_eventyay_authorization_schemes( $api_token );
		$last_error   = null;

		foreach ( $auth_schemes as $auth_scheme ) {
			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
					'headers'     => $this->build_eventyay_rest_headers( $api_token, $auth_scheme ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'wpfaevent_eventyay_request_failed',
					esc_html__( 'Eventyay request failed.', 'wpfaevent' ),
					array( 'details' => $response->get_error_message() )
				);
			}

			$decoded = $this->decode_eventyay_rest_response( $response, $api_url );
			if ( ! is_wp_error( $decoded ) ) {
				return $decoded;
			}

			$last_error = $decoded;
			if (
				! $this->eventyay_error_has_http_status( $decoded, 401 )
				&& ! $this->eventyay_error_has_http_status( $decoded, 403 )
			) {
				return $decoded;
			}
		}

		return is_wp_error( $last_error ) ? $last_error : new WP_Error(
			'wpfaevent_eventyay_request_failed',
			esc_html__( 'Eventyay request failed.', 'wpfaevent' )
		);
	}

	/**
	 * Decode an Eventyay REST response into an array or structured error.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $response WordPress HTTP response.
	 * @param string $api_url  API endpoint URL.
	 * @return array|WP_Error
	 */
	private function decode_eventyay_rest_response( $response, $api_url ) {
		$status = absint( wp_remote_retrieve_response_code( $response ) );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'wpfaevent_eventyay_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: Eventyay API URL. */
					esc_html__( 'Eventyay API returned HTTP %1$d for %2$s.', 'wpfaevent' ),
					$status,
					esc_url_raw( $api_url )
				),
				array(
					'http_status' => $status,
					'url'         => esc_url_raw( $api_url ),
					'body'        => $this->decode_eventyay_error_body( $body ),
				)
			);
		}

		$decoded = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_malformed_json',
				esc_html__( 'Eventyay API returned malformed JSON.', 'wpfaevent' ),
				array(
					'json_error'      => json_last_error_msg(),
					'response_sample' => $this->truncate_string( $body ),
				)
			);
		}

		return $decoded;
	}

	/**
	 * Decode an Eventyay error body if possible.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body Response body.
	 * @return array|string
	 */
	private function decode_eventyay_error_body( $body ) {
		$decoded = json_decode( $body, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return $decoded;
		}

		return $this->truncate_string( $body );
	}

	/**
	 * Build request headers for Eventyay REST calls.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token   Optional API token.
	 * @param string $auth_scheme Authorization scheme.
	 * @return array<string, string>
	 */
	private function build_eventyay_rest_headers( $api_token, $auth_scheme ) {
		$headers = array(
			'Accept' => 'application/json, application/vnd.api+json, text/javascript',
		);

		$authorization = $this->format_eventyay_authorization_header( $api_token, $auth_scheme );
		if ( $authorization ) {
			$headers['Authorization'] = $authorization;
		}

		return $headers;
	}

	/**
	 * Get authorization schemes to try for Eventyay requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token Optional API token.
	 * @return array<int, string>
	 */
	private function get_eventyay_authorization_schemes( $api_token ) {
		$api_token = trim( (string) $api_token );

		if ( '' === $api_token ) {
			return array( '' );
		}

		if ( preg_match( '/^(Token|JWT|Bearer)\s+/i', $api_token ) ) {
			return array( '' );
		}

		$schemes = apply_filters(
			'wpfaevent_eventyay_import_auth_schemes',
			array( 'Token', 'JWT' ),
			$api_token
		);

		if ( ! is_array( $schemes ) ) {
			$schemes = array( 'Token', 'JWT' );
		}

		$schemes = array_filter(
			array_map(
				static function ( $scheme ) {
					return sanitize_text_field( (string) $scheme );
				},
				$schemes
			)
		);

		return ! empty( $schemes ) ? array_values( array_unique( $schemes ) ) : array( 'Token', 'JWT' );
	}

	/**
	 * Format an Authorization header value for Eventyay requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_token   Optional API token.
	 * @param string $auth_scheme Authorization scheme.
	 * @return string
	 */
	private function format_eventyay_authorization_header( $api_token, $auth_scheme ) {
		$api_token = trim( sanitize_text_field( (string) $api_token ) );

		if ( '' === $api_token ) {
			return '';
		}

		if ( preg_match( '/^(Token|JWT|Bearer)\s+/i', $api_token ) ) {
			return $api_token;
		}

		$auth_scheme = trim( sanitize_text_field( (string) $auth_scheme ) );
		if ( '' === $auth_scheme ) {
			$auth_scheme = 'Token';
		}

		return $auth_scheme . ' ' . $api_token;
	}

		/**
		 * Get the Eventyay slug from a normalized event resource.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return string
		 */
	private function eventyay_event_slug( $event ) {
		$slug = $this->eventyay_first_present_text( $event, array( 'slug', 'identifier', 'code' ) );

		if ( empty( $slug ) ) {
			$url = $this->eventyay_url_value(
				$this->eventyay_first_present_raw( $event, array( 'url', 'frontend_url', 'frontend-url', 'public_url', 'public-url' ) ),
				''
			);

			if ( $url ) {
				$path  = wp_parse_url( $url, PHP_URL_PATH );
				$parts = array_filter( explode( '/', trim( (string) $path, '/' ) ) );
				$slug  = $parts ? end( $parts ) : '';
			}
		}

		return $this->sanitize_eventyay_path_segment( $slug );
	}

		/**
		 * Get the Eventyay title from a normalized event resource.
		 *
		 * @since 1.0.0
		 *
		 * @param array $event Eventyay event resource.
		 * @return string
		 */
	private function eventyay_event_title( $event ) {
		return $this->eventyay_first_present_text( $event, array( 'name', 'title', 'label' ) );
	}

		/**
		 * Get an event date/time value from Eventyay event or nested metadata.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $event Eventyay event resource.
		 * @param string $type  Date type. Accepts start or end.
		 * @return string
		 */
	private function eventyay_event_datetime( $event, $type ) {
		$keys = ( 'end' === $type )
			? array( 'date_to', 'date-to', 'ends_at', 'ends-at', 'end_time', 'end-time', 'end_date', 'end-date', 'end', 'to' )
			: array( 'date_from', 'date-from', 'starts_at', 'starts-at', 'start_time', 'start-time', 'start_date', 'start-date', 'start', 'from' );

		return $this->eventyay_scalar_value( $this->eventyay_event_first_present_raw( $event, $keys, false ) );
	}

	/**
	 * Get the Eventyay event timezone from likely fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_timezone( $event ) {
		return Wpfaevent_Meta_Event::sanitize_timezone(
			$this->eventyay_event_first_present_raw(
				$event,
				array( 'timezone', 'time_zone', 'time-zone', 'tz' ),
				true
			)
		);
	}

	/**
	 * Get the Eventyay event location from likely field shapes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_location( $event ) {
		$location = $this->eventyay_event_first_present_raw(
			$event,
			array(
				'location',
				'location_name',
				'location-name',
				'searchable_location_name',
				'searchable-location-name',
				'venue',
				'venue_name',
				'venue-name',
				'address',
			),
			false
		);

		return sanitize_text_field( $this->eventyay_location_text_value( $location ) );
	}

			/**
			 * Create or update one imported Eventyay event post.
			 *
			 * @since 1.0.0
			 *
			 * @param array $event    Eventyay event resource.
			 * @param array $settings Import settings.
			 * @return array|WP_Error Upsert result.
			 */
	private function upsert_eventyay_event_post( $event, $settings ) {
		$event      = $this->normalize_eventyay_event_resource( $event );
		$event_slug = $this->eventyay_event_slug( $event );
		if ( empty( $event_slug ) ) {
			return new WP_Error(
				'wpfaevent_eventyay_event_missing_slug',
				esc_html__( 'An Eventyay event was skipped because it did not contain a slug.', 'wpfaevent' )
			);
		}

		$organizer_slug = $settings['organizer_slug'];
		$title          = $this->eventyay_event_title( $event );
		$title          = $title ? $title : $event_slug;
		$description    = $this->eventyay_event_description( $event );
		$existing_id    = $this->find_eventyay_event_post( $organizer_slug, $event_slug );
		$post_status    = in_array( $settings['post_status'], array( 'draft', 'publish', 'pending', 'private' ), true ) ? $settings['post_status'] : 'draft';
		$post_data      = array(
			'post_title'   => sanitize_text_field( $title ),
			'post_type'    => 'wpfa_event',
			'post_status'  => $post_status,
			'post_content' => wp_kses_post( $description ),
		);
		$created        = false;

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$post_data['post_name'] = sanitize_title( $organizer_slug . '-' . $event_slug );
			$saved_id               = wp_insert_post( $post_data, true );
			$created                = true;
		}

		if ( is_wp_error( $saved_id ) ) {
			return $saved_id;
		}

		$saved_id = absint( $saved_id );
		if ( ! $saved_id ) {
			return new WP_Error(
				'wpfaevent_eventyay_event_save_failed',
				esc_html__( 'Could not save imported Eventyay event.', 'wpfaevent' )
			);
		}

		$start_datetime       = $this->eventyay_event_datetime( $event, 'start' );
		$end_datetime         = $this->eventyay_event_datetime( $event, 'end' );
		$timezone             = $this->eventyay_event_timezone( $event );
		$timezone_object      = $this->eventyay_timezone_object( $timezone );
		$event_is_all_day     = ! $this->eventyay_datetime_has_time( $start_datetime ) && ! $this->eventyay_datetime_has_time( $end_datetime );
		$start_date           = $this->format_eventyay_date( $start_datetime, $timezone_object );
		$end_date             = $this->format_eventyay_date( $end_datetime, $timezone_object );
		$start_time           = $event_is_all_day ? '' : $this->format_eventyay_time( $start_datetime, $timezone_object );
		$end_time             = $event_is_all_day ? '' : $this->format_eventyay_time( $end_datetime, $timezone_object );
		$normalized_starts_at = $this->normalize_eventyay_datetime( $start_datetime );
		$normalized_ends_at   = $this->normalize_eventyay_datetime( $end_datetime );
		$location             = $this->eventyay_event_location( $event );
		$event_url            = $this->eventyay_public_event_url( $event, $settings, $event_slug );

		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_start_date', $start_date );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_end_date', $end_date );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_start_time', $start_time );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_end_time', $end_time );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_timezone', $timezone );
		update_post_meta( $saved_id, 'wpfa_event_all_day', $event_is_all_day ? '1' : '0' );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_starts_at', $normalized_starts_at );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_ends_at', $normalized_ends_at );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_location', $location );
		$this->update_or_delete_post_meta( $saved_id, 'wpfa_event_url', $event_url );

		// Keep older dashboard/landing metadata in sync with the canonical event meta.
		$this->update_or_delete_post_meta( $saved_id, '_event_date', $start_date );
		$this->update_or_delete_post_meta( $saved_id, '_event_end_date', $end_date );
		$this->update_or_delete_post_meta( $saved_id, '_event_place', $location );
		$this->update_or_delete_post_meta( $saved_id, '_event_registration_link', $event_url );
		$this->update_or_delete_post_meta( $saved_id, '_event_lead_text', wp_strip_all_tags( $description ) );

		update_post_meta( $saved_id, '_wpfa_eventyay_organizer_slug', sanitize_text_field( $organizer_slug ) );
		update_post_meta( $saved_id, '_wpfa_eventyay_event_slug', sanitize_text_field( $event_slug ) );
		update_post_meta( $saved_id, '_wpfa_eventyay_last_imported_at', current_time( 'mysql', true ) );

		$source_id = $this->eventyay_event_first_present_raw( $event, array( '_eventyay_source_id', 'id', 'code', 'identifier' ), false );
		if ( is_scalar( $source_id ) && '' !== trim( (string) $source_id ) ) {
			update_post_meta( $saved_id, '_wpfa_eventyay_event_id', sanitize_text_field( (string) $source_id ) );
		}

		return array(
			'id'         => $saved_id,
			'created'    => $created,
			'event_slug' => $event_slug,
		);
	}

	/**
	 * Determine whether an Eventyay event has a fetched settings payload.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return bool
	 * @phpstan-ignore method.unused
	 */
	private function eventyay_event_has_settings_payload( $event ) {
		foreach ( array( '_eventyay_settings', 'settings' ) as $settings_key ) {
			if ( ! empty( $event[ $settings_key ] ) && is_array( $event[ $settings_key ] ) ) {
				return true;
			}
		}

		return false;
	}

			/**
			 * Return the first non-empty raw event field from top-level data, metadata, or settings.
			 *
			 * @since 1.0.0
			 *
			 * @param array $event            Eventyay event resource.
			 * @param array $keys             Candidate keys.
			 * @param bool  $include_settings Whether to check Eventyay settings payloads.
			 * @return mixed
			 */
	private function eventyay_event_first_present_raw( $event, $keys, $include_settings = false ) {
		$value = $this->eventyay_first_present_raw( $event, $keys );
		if ( $this->eventyay_value_is_non_empty( $value ) ) {
			return $value;
		}

		foreach ( array( 'meta_data', 'metadata', 'meta' ) as $meta_key ) {
			if ( ! empty( $event[ $meta_key ] ) && is_array( $event[ $meta_key ] ) ) {
				$value = $this->eventyay_first_present_raw( $event[ $meta_key ], $keys );

				if ( $this->eventyay_value_is_non_empty( $value ) ) {
					return $value;
				}
			}
		}

		if ( $include_settings ) {
			foreach ( array( '_eventyay_settings', 'settings' ) as $settings_key ) {
				if ( ! empty( $event[ $settings_key ] ) && is_array( $event[ $settings_key ] ) ) {
					$settings_resource = $this->normalize_eventyay_api_resource( $event[ $settings_key ] );
					$value             = $this->eventyay_first_present_raw( $settings_resource, $keys );

					if ( $this->eventyay_value_is_non_empty( $value ) ) {
						return $value;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Find an imported Eventyay event by source organizer and event slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $organizer_slug Eventyay organizer slug.
	 * @param string $event_slug     Eventyay event slug.
	 * @return int
	 */
	private function find_eventyay_event_post( $organizer_slug, $event_slug ) {
		$event_ids = get_posts(
			array(
				'post_type'      => 'wpfa_event',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Imported Eventyay event identity is stored in post meta for idempotency.
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_wpfa_eventyay_organizer_slug',
						'value'   => sanitize_text_field( $organizer_slug ),
						'compare' => '=',
					),
					array(
						'key'     => '_wpfa_eventyay_event_slug',
						'value'   => sanitize_text_field( $event_slug ),
						'compare' => '=',
					),
				),
			)
		);

		return ! empty( $event_ids[0] ) ? absint( $event_ids[0] ) : 0;
	}

	/**
	 * Get a useful string from an Eventyay scalar or multi-language value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_text_value( $value ) {
		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'en', 'default', 'name', 'title' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) && is_scalar( $value[ $preferred_key ] ) && '' !== trim( (string) $value[ $preferred_key ] ) ) {
				return sanitize_text_field( (string) $value[ $preferred_key ] );
			}
		}

		foreach ( $value as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return sanitize_text_field( (string) $candidate );
			}
		}

		return '';
	}

	/**
	 * Get rich text from an Eventyay scalar or multi-language value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_rich_text_value( $value ) {
		if ( is_scalar( $value ) ) {
			return wp_kses_post( (string) $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'en', 'default', 'description', 'text' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) && is_scalar( $value[ $preferred_key ] ) && '' !== trim( (string) $value[ $preferred_key ] ) ) {
				return wp_kses_post( (string) $value[ $preferred_key ] );
			}
		}

		foreach ( $value as $candidate ) {
			if ( is_scalar( $candidate ) && '' !== trim( (string) $candidate ) ) {
				return wp_kses_post( (string) $candidate );
			}
		}

		return '';
	}

	/**
	 * Get a scalar string from an Eventyay value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Eventyay value.
	 * @return string
	 */
	private function eventyay_scalar_value( $value ) {
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( array( 'value', 'date', 'datetime', 'start', 'end', 'en', 'default' ) as $preferred_key ) {
			if ( isset( $value[ $preferred_key ] ) ) {
				$resolved = $this->eventyay_scalar_value( $value[ $preferred_key ] );

				if ( '' !== trim( $resolved ) ) {
					return $resolved;
				}
			}
		}

		foreach ( $value as $candidate ) {
			$resolved = $this->eventyay_scalar_value( $candidate );

			if ( '' !== trim( $resolved ) ) {
				return $resolved;
			}
		}

		return '';
	}

	/**
	 * Extract event description content from an Eventyay event resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Eventyay event resource.
	 * @return string
	 */
	private function eventyay_event_description( $event ) {
		$value = $this->eventyay_event_first_present_raw(
			$event,
			array(
				'description',
				'frontpage_text',
				'frontpage-text',
				'event_info_text',
				'event-info-text',
				'about',
				'about_text',
				'about-text',
				'text',
				'intro',
				'short_description',
				'short-description',
				'subtitle',
				'summary',
			),
			true
		);

		return $this->eventyay_rich_text_value( $value );
	}

	/**
	 * Build the public Eventyay URL for an imported event.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $event      Eventyay event resource.
	 * @param array  $settings   Import settings.
	 * @param string $event_slug Eventyay event slug.
	 * @return string
	 */
	private function eventyay_public_event_url( $event, $settings, $event_slug ) {
		$url = $this->eventyay_url_value(
			$this->eventyay_event_first_present_raw(
				$event,
				array(
					'url',
					'frontend_url',
					'frontend-url',
					'public_url',
					'public-url',
					'web_url',
					'web-url',
					'absolute_url',
					'absolute-url',
					'event_url',
					'event-url',
					'registration_url',
					'registration-url',
				),
				true
			),
			$settings['base_url']
		);

		if ( $url ) {
			return $url;
		}

		$url = trailingslashit( $settings['base_url'] ) . rawurlencode( $settings['organizer_slug'] ) . '/' . rawurlencode( $event_slug ) . '/';

		return esc_url_raw(
			apply_filters(
				'wpfaevent_eventyay_import_event_url',
				$url,
				$event,
				$settings
			)
		);
	}

	/**
	 * Normalize an Eventyay resource into a flat field map.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @return array
	 */
	private function normalize_eventyay_api_resource( $eventyay_resource ) {
		if ( ! is_array( $eventyay_resource ) ) {
			return array();
		}

		if ( isset( $eventyay_resource['data'] ) && is_array( $eventyay_resource['data'] ) && $this->is_jsonapi_resource( $eventyay_resource['data'] ) ) {
			$eventyay_resource = $eventyay_resource['data'];
		}

		$normalized = array();

		if ( isset( $eventyay_resource['attributes'] ) && is_array( $eventyay_resource['attributes'] ) ) {
			$normalized = $eventyay_resource['attributes'];
		}

		foreach ( $eventyay_resource as $key => $value ) {
			if ( in_array( $key, array( 'attributes', 'relationships' ), true ) ) {
				continue;
			}

			$normalized[ $key ] = $value;
		}

		if ( isset( $eventyay_resource['relationships'] ) && is_array( $eventyay_resource['relationships'] ) ) {
			foreach ( $eventyay_resource['relationships'] as $relationship_key => $relationship ) {
				if ( ! is_array( $relationship ) || ! array_key_exists( 'data', $relationship ) ) {
					continue;
				}

				$normalized[ $relationship_key ] = $this->normalize_eventyay_relationship_data( $relationship['data'] );
			}
		}

		return $normalized;
	}

	/**
	 * Normalize JSON:API relationship data when included directly in Eventyay payloads.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $relationship_data Relationship data.
	 * @return mixed
	 */
	private function normalize_eventyay_relationship_data( $relationship_data ) {
		if ( ! is_array( $relationship_data ) ) {
			return $relationship_data;
		}

		if ( $this->is_jsonapi_resource( $relationship_data ) ) {
			return $this->normalize_eventyay_api_resource( $relationship_data );
		}

		$normalized = array();
		foreach ( $relationship_data as $item ) {
			$normalized[] = is_array( $item ) ? $this->normalize_eventyay_api_resource( $item ) : $item;
		}

		return $normalized;
	}

	/**
	 * Get a list value from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 * @phpstan-ignore method.unused
	 */
	private function eventyay_list_value( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		if ( isset( $value['results'] ) && is_array( $value['results'] ) ) {
			return $value['results'];
		}

		if ( array_key_exists( 'data', $value ) && is_array( $value['data'] ) ) {
			if ( $this->is_jsonapi_resource( $value['data'] ) ) {
				return array( $value['data'] );
			}

			return $value['data'];
		}

		if (
			! array_key_exists( 0, $value )
			&& (
				$this->is_jsonapi_resource( $value )
				|| isset( $value['id'] )
				|| isset( $value['name'] )
				|| isset( $value['title'] )
				|| isset( $value['attributes'] )
			)
		) {
			return array( $value );
		}

		return $value;
	}

	/**
	 * Extract a stable identifier from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @return string
	 * @phpstan-ignore method.unused
	 */
	private function eventyay_resource_identifier( $eventyay_resource ) {
		foreach ( array( '_eventyay_source_id', 'code', 'id', 'slug' ) as $key ) {
			if ( isset( $eventyay_resource[ $key ] ) && is_scalar( $eventyay_resource[ $key ] ) && '' !== trim( (string) $eventyay_resource[ $key ] ) ) {
				return sanitize_text_field( (string) $eventyay_resource[ $key ] );
			}
		}

		return '';
	}

	/**
	 * Return the first non-empty plain text field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return string
	 */
	private function eventyay_first_present_text( $eventyay_resource, $keys ) {
		$value = $this->eventyay_first_present_raw( $eventyay_resource, $keys );

		return $this->eventyay_text_value( $value );
	}

	/**
	 * Return the first non-empty rich text field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return string
	 * @phpstan-ignore method.unused
	 */
	private function eventyay_first_present_rich_text( $eventyay_resource, $keys ) {
		$value = $this->eventyay_first_present_raw( $eventyay_resource, $keys );

		return $this->eventyay_rich_text_value( $value );
	}

	/**
	 * Return the first non-empty raw field from an Eventyay resource.
	 *
	 * @since 1.0.0
	 *
	 * @param array $eventyay_resource Eventyay resource.
	 * @param array $keys              Candidate keys.
	 * @return mixed
	 */
	private function eventyay_first_present_raw( $eventyay_resource, $keys ) {
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $eventyay_resource ) ) {
				continue;
			}

			if ( is_scalar( $eventyay_resource[ $key ] ) && '' !== trim( (string) $eventyay_resource[ $key ] ) ) {
				return $eventyay_resource[ $key ];
			}

			if ( is_array( $eventyay_resource[ $key ] ) && ! empty( $eventyay_resource[ $key ] ) ) {
				return $eventyay_resource[ $key ];
			}
		}

		return '';
	}

	/**
	 * Convert an Eventyay URL-ish value into an absolute URL.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $base_url Eventyay base URL.
	 * @return string
	 */
	private function eventyay_url_value( $value, $base_url ) {
		if ( is_array( $value ) ) {
			foreach ( array( 'url', 'href', 'download', 'thumbnail', 'image', 'en', 'default' ) as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					return $this->eventyay_url_value( $value[ $key ], $base_url );
				}
			}

			return '';
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( wp_http_validate_url( $value ) ) {
			return esc_url_raw( $value );
		}

		$base_url = untrailingslashit( esc_url_raw( $base_url ) );
		if ( ! empty( $base_url ) && wp_http_validate_url( $base_url ) && 0 === strpos( $value, '/' ) ) {
			return esc_url_raw( $base_url . $value );
		}

		return '';
	}

		/**
		 * Convert an Eventyay location-ish value into display text.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $value Raw location value.
		 * @return string
		 */
	private function eventyay_location_text_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $this->eventyay_text_value( $value );
		}

		foreach ( array( 'name', 'title', 'label', 'location', 'address', 'full_address', 'full-address', 'formatted_address', 'formatted-address' ) as $key ) {
			if ( ! empty( $value[ $key ] ) ) {
				$text = $this->eventyay_text_value( $value[ $key ] );

				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		$parts = array();
		foreach ( array( 'street', 'street_address', 'street-address', 'address_line_1', 'address-line-1', 'line1', 'postal_code', 'postal-code', 'postcode', 'zip', 'city', 'region', 'state', 'country' ) as $key ) {
			if ( empty( $value[ $key ] ) ) {
				continue;
			}

			$part = $this->eventyay_text_value( $value[ $key ] );
			if ( '' !== $part ) {
				$parts[] = $part;
			}
		}

		if ( ! empty( $parts ) ) {
			return implode( ', ', array_values( array_unique( $parts ) ) );
		}

		return $this->eventyay_text_value( $value );
	}

	/**
	 * Update or delete a post meta value.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	private function update_or_delete_post_meta( $post_id, $key, $value ) {
		if ( '' === $value || null === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Get a JSON:API resource's attributes array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $jsonapi_resource JSON:API resource.
	 * @return array
	 * @phpstan-ignore method.unused
	 */
	private function get_jsonapi_attributes( $jsonapi_resource ) {
		return isset( $jsonapi_resource['attributes'] ) && is_array( $jsonapi_resource['attributes'] ) ? $jsonapi_resource['attributes'] : array();
	}

	/**
	 * Get a scalar attribute value by trying multiple possible keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Attribute map.
	 * @param array $keys       Candidate keys.
	 * @return string
	 * @phpstan-ignore method.unused
	 */
	private function attribute_value( $attributes, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ) {
				return (string) $attributes[ $key ];
			}
		}

		return '';
	}

	/**
	 * Determine whether an array resembles a JSON:API resource.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $maybe_resource Possible resource.
	 * @return bool
	 */
	private function is_jsonapi_resource( $maybe_resource ) {
		return is_array( $maybe_resource ) && isset( $maybe_resource['type'], $maybe_resource['id'] );
	}

	/**
	 * Format an Eventyay date-time value as a date.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone for display fields.
	 * @return string
	 */
	private function format_eventyay_date( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'Y-m-d' );
	}

	/**
	 * Format an Eventyay date-time value as a time.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $value    Date-time value.
	 * @param DateTimeZone|null $timezone Optional timezone for display fields.
	 * @return string
	 */
	private function format_eventyay_time( $value, $timezone = null ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		if ( $timezone instanceof DateTimeZone ) {
			$date = $date->setTimezone( $timezone );
		}

		return $date->format( 'H:i' );
	}

	/**
	 * Determine whether an Eventyay value contains a time component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date or date-time value.
	 * @return bool
	 */
	private function eventyay_datetime_has_time( $value ) {
		return is_scalar( $value ) && 1 === preg_match( '/[T\s]\d{1,2}:\d{2}/', (string) $value );
	}

	/**
	 * Build a timezone object from a sanitized Eventyay timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timezone Timezone identifier.
	 * @return DateTimeZone|null
	 */
	private function eventyay_timezone_object( $timezone ) {
		$timezone = Wpfaevent_Meta_Event::sanitize_timezone( $timezone );

		if ( '' === $timezone ) {
			return null;
		}

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	/**
	 * Normalize an Eventyay date-time value while preserving its timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Date-time value.
	 * @return string
	 */
	private function normalize_eventyay_datetime( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return '';
		}

		return $date->format( DATE_ATOM );
	}

	/**
	 * Truncate a string for structured error output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value String value.
	 * @return string
	 */
	private function truncate_string( $value ) {
		$value = wp_strip_all_tags( (string) $value );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 1000 );
		}

		return substr( $value, 0, 1000 );
	}
}
