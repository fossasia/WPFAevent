<?php
/**
 * Speaker CPT meta box rendering and save handling.
 *
 * @link       https://fossasia.org
 * @since      1.0.0
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials/meta-boxes
 */

/**
 * Registers, renders, and saves the meta box on the Speaker edit screen.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/admin/partials/meta-boxes
 * @author     FOSSASIA <contact@fossasia.org>
 */
class Wpfaevent_Admin_Speaker_Metabox {

	/**
	 * Register meta boxes for the Speaker CPT.
	 *
	 * @since 1.0.0
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'wpfa_speaker_details',
			__( 'Speaker Details', 'wpfaevent' ),
			array( $this, 'render_speaker_meta_box' ),
			'wpfa_speaker',
			'normal',
			'high'
		);

		// Remove the default Custom Fields meta box to avoid UI clutter.
		// since we have enabled 'custom-fields' support for REST API visibility.
		remove_meta_box( 'postcustom', 'wpfa_speaker', 'normal' );
	}

	/**
	 * Render Speaker meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_speaker_meta_box( $post ) {
		wp_nonce_field( 'wpfa_speaker_meta_nonce', 'wpfa_speaker_meta_nonce' );

		$position     = get_post_meta( $post->ID, 'wpfa_speaker_position', true );
		$organization = get_post_meta( $post->ID, 'wpfa_speaker_organization', true );
		$bio          = get_post_meta( $post->ID, 'wpfa_speaker_bio', true );
		$headshot_url = get_post_meta( $post->ID, 'wpfa_speaker_headshot_url', true );
		$events       = $this->sanitize_post_id_list(
			array_merge(
				$this->get_speaker_event_ids( $post->ID ),
				$this->get_events_linked_to_speaker( $post->ID )
			)
		);
		if ( empty( $events ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading referer/query parameter to pre-populate event field in edit screen metabox.
			if ( isset( $_GET['wpfa_speaker_event'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$events[] = absint( $_GET['wpfa_speaker_event'] );
			} elseif ( wp_get_referer() ) {
				$referer_query = wp_parse_url( wp_get_referer(), PHP_URL_QUERY );
				if ( $referer_query ) {
					parse_str( $referer_query, $referer_args );
					if ( ! empty( $referer_args['wpfa_speaker_event'] ) ) {
						$events[] = absint( $referer_args['wpfa_speaker_event'] );
					}
				}
			}
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpfa_speaker_position"><?php esc_html_e( 'Position/Title', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_position" name="wpfa_speaker_position" value="<?php echo esc_attr( $position ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_organization"><?php esc_html_e( 'Organization', 'wpfaevent' ); ?></label></th>
				<td><input type="text" id="wpfa_speaker_organization" name="wpfa_speaker_organization" value="<?php echo esc_attr( $organization ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_bio"><?php esc_html_e( 'Biography', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					wp_editor(
						$bio,
						'wpfa_speaker_bio',
						array(
							'textarea_name' => 'wpfa_speaker_bio',
							'textarea_rows' => 10,
							'media_buttons' => false,
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_headshot_url"><?php esc_html_e( 'Headshot URL', 'wpfaevent' ); ?></label></th>
				<td><input type="url" id="wpfa_speaker_headshot_url" name="wpfa_speaker_headshot_url" value="<?php echo esc_attr( $headshot_url ); ?>" class="regular-text" placeholder="https://"></td>
			</tr>
			<tr>
				<th><label for="wpfa_speaker_events"><?php esc_html_e( 'Related Events', 'wpfaevent' ); ?></label></th>
				<td>
					<?php
					$event_ids = get_posts(
						array(
							'post_type'      => 'wpfa_event',
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'fields'         => 'ids',
							'no_found_rows'  => true,
						)
					);
					if ( $event_ids ) :
						?>
						<select name="wpfa_speaker_events[]" id="wpfa_speaker_events" multiple class="wpfaevent-relationship-select wpfaevent-events-select">
							<?php foreach ( $event_ids as $event_id ) : ?>
								<?php $is_selected = in_array( $event_id, $events, true ); ?>
									<option value="<?php echo esc_attr( sprintf( '%d', absint( $event_id ) ) ); ?>" <?php selected( $is_selected, true ); ?>>
									<?php echo esc_html( get_the_title( $event_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple events.', 'wpfaevent' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No events found. Create events first.', 'wpfaevent' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Speaker meta box data.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 */
	public function save_speaker_meta( $post_id ) {
		$speaker_nonce = isset( $_POST['wpfa_speaker_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_meta_nonce'] ) ) : '';

		if ( ! $speaker_nonce || ! wp_verify_nonce( $speaker_nonce, 'wpfa_speaker_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wpfa_speaker_position'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_position', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_position'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_organization'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_organization', sanitize_text_field( wp_unslash( $_POST['wpfa_speaker_organization'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_bio'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_bio', wp_kses_post( wp_unslash( $_POST['wpfa_speaker_bio'] ) ) );
		}

		if ( isset( $_POST['wpfa_speaker_headshot_url'] ) ) {
			update_post_meta( $post_id, 'wpfa_speaker_headshot_url', esc_url_raw( wp_unslash( $_POST['wpfa_speaker_headshot_url'] ) ) );
		}

		$previous_events = $this->get_speaker_event_ids( $post_id );
		$events          = array();

		if ( isset( $_POST['wpfa_speaker_events'] ) && is_array( $_POST['wpfa_speaker_events'] ) ) {
			$events = $this->sanitize_post_id_list(
				array_map(
					'sanitize_text_field',
					wp_unslash( $_POST['wpfa_speaker_events'] )
				)
			);
		}

		$this->update_post_id_list_meta( $post_id, 'wpfa_speaker_events', $events );
		$this->sync_speaker_event_relationships( $post_id, $previous_events, $events );
	}

	/**
	 * Sync event-side speaker relationship meta after a speaker is saved.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $speaker_id      Speaker post ID.
	 * @param array<int> $previous_events Event IDs before save.
	 * @param array<int> $current_events  Event IDs after save.
	 * @return void
	 */
	private function sync_speaker_event_relationships( $speaker_id, $previous_events, $current_events ) {
		$speaker_id      = absint( $speaker_id );
		$previous_events = $this->sanitize_post_id_list( $previous_events );
		$current_events  = $this->sanitize_post_id_list( $current_events );

		if ( ! $speaker_id || 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$previous_events = array_values(
			array_unique(
				array_merge(
					$previous_events,
					$this->get_events_linked_to_speaker( $speaker_id )
				)
			)
		);

		$removed_events = array_diff( $previous_events, $current_events );

		foreach ( $removed_events as $event_id ) {
			$this->remove_speaker_from_event( $event_id, $speaker_id );
		}

		foreach ( $current_events as $event_id ) {
			$this->add_speaker_to_event( $event_id, $speaker_id );
		}
	}

	/**
	 * Find events whose event-side speaker meta includes a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int> Event post IDs.
	 */
	private function get_events_linked_to_speaker( $speaker_id ) {
		$speaker_id = absint( $speaker_id );

		if ( ! $speaker_id ) {
			return array();
		}

		$batch_size   = 100;
		$current_page = 1;
		$event_ids    = array();

		do {
			$batch_ids = get_posts(
				array(
					'post_type'              => 'wpfa_event',
					'post_status'            => 'any',
					'posts_per_page'         => $batch_size,
					'paged'                  => $current_page,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( empty( $batch_ids ) ) {
				break;
			}

			$batch_count = count( $batch_ids );
			update_meta_cache( 'post', $batch_ids );

			foreach ( $batch_ids as $event_id ) {
				if ( in_array( $speaker_id, $this->get_event_speaker_ids( $event_id ), true ) ) {
					$event_ids[] = $event_id;
				}
			}

			++$current_page;
		} while ( $batch_count === $batch_size );

		return $this->sanitize_post_id_list( $event_ids );
	}

	/**
	 * Add a speaker ID to an event's related speakers.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
	private function add_speaker_to_event( $event_id, $speaker_id ) {
		$event_id   = absint( $event_id );
		$speaker_id = absint( $speaker_id );

		if ( ! $event_id || ! $speaker_id ) {
			return;
		}

		if ( 'wpfa_event' !== get_post_type( $event_id ) ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$speaker_ids   = $this->get_event_speaker_ids( $event_id );
		$speaker_ids[] = $speaker_id;

		$this->update_post_id_list_meta( $event_id, 'wpfa_event_speakers', $speaker_ids );
	}

	/**
	 * Remove a speaker ID from an event's related speakers.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $speaker_id Speaker post ID.
	 * @return void
	 */
	private function remove_speaker_from_event( $event_id, $speaker_id ) {
		$event_id   = absint( $event_id );
		$speaker_id = absint( $speaker_id );

		if ( ! $event_id || ! $speaker_id ) {
			return;
		}

		if ( 'wpfa_event' !== get_post_type( $event_id ) ) {
			return;
		}

		if ( 'wpfa_speaker' !== get_post_type( $speaker_id ) ) {
			return;
		}

		$speaker_ids = array_diff( $this->get_event_speaker_ids( $event_id ), array( $speaker_id ) );

		$this->update_post_id_list_meta( $event_id, 'wpfa_event_speakers', $speaker_ids );
	}

	/**
	 * Get normalized speaker IDs assigned to an event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int> Speaker post IDs.
	 */
	private function get_event_speaker_ids( $event_id ) {
		$speaker_ids = get_post_meta( $event_id, 'wpfa_event_speakers', true );

		return $this->sanitize_post_id_list( $speaker_ids );
	}

	/**
	 * Get normalized event IDs assigned to a speaker.
	 *
	 * @since 1.0.0
	 *
	 * @param int $speaker_id Speaker post ID.
	 * @return array<int> Event post IDs.
	 */
	private function get_speaker_event_ids( $speaker_id ) {
		$event_ids = get_post_meta( $speaker_id, 'wpfa_speaker_events', true );

		return $this->sanitize_post_id_list( $event_ids );
	}

	/**
	 * Sanitize, deduplicate, and reindex a list of post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_ids Raw post IDs.
	 * @return array<int> Sanitized post IDs.
	 */
	private function sanitize_post_id_list( $post_ids ) {
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array( $post_ids );
		}

		$post_ids = array_map( 'absint', $post_ids );
		$post_ids = array_filter( $post_ids );

		return array_values( array_unique( $post_ids ) );
	}

	/**
	 * Save a normalized post ID list as post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int        $post_id  Post ID.
	 * @param string     $meta_key Meta key.
	 * @param array<int> $post_ids Post IDs to save.
	 * @return void
	 */
	private function update_post_id_list_meta( $post_id, $meta_key, $post_ids ) {
		$post_ids = $this->sanitize_post_id_list( $post_ids );

		if ( empty( $post_ids ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $post_ids );
	}
}
