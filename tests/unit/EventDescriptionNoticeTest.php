<?php
/**
 * Unit tests for the Eventyay description notice in the event editor.
 *
 * @package Wpfaevent
 */

/**
 * Covers where the description sync note and its restore action are shown.
 *
 * @covers Wpfaevent_Admin_Event_Metabox
 */
class EventDescriptionNoticeTest extends WP_UnitTestCase {

	/**
	 * Script handle of the block editor notice.
	 *
	 * @var string
	 */
	const HANDLE = 'wpfaevent-description-notice';

	/**
	 * Reset registered scripts, meta boxes and the global post between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';

		$GLOBALS['wp_scripts']    = null;
		$GLOBALS['wp_meta_boxes'] = array();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Clean up global state.
	 *
	 * @return void
	 */
	public function tear_down() {
		$GLOBALS['wp_scripts']    = null;
		$GLOBALS['wp_meta_boxes'] = array();
		$GLOBALS['post']          = null;

		parent::tear_down();
	}

	/**
	 * Import an event through the repository.
	 *
	 * @param string $description Eventyay description.
	 * @return int Event post ID.
	 */
	private function import( $description ) {
		$result = ( new Wpfaevent_Event_Repository() )->upsert_eventyay_event_post(
			array(
				'slug'        => 'notice-event',
				'name'        => 'Notice Event',
				'description' => $description,
			),
			array(
				'base_url'       => 'https://eventyay.example',
				'organizer_slug' => 'fossasia',
				'post_status'    => 'draft',
			)
		);

		$this->assertIsArray( $result );

		return $result['id'];
	}

	/**
	 * Import an event and edit its description in the post editor (post content).
	 *
	 * @return int Event post ID.
	 */
	private function locally_edited_event() {
		$event_id = $this->import( 'Original description.' );
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => 'Written for the website.',
			)
		);

		return $event_id;
	}

	/**
	 * Make the given post the one being edited and run the block editor hook.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function open_in_editor( $post_id ) {
		$GLOBALS['post'] = get_post( $post_id );
		( new Wpfaevent_Admin_Event_Metabox() )->enqueue_description_notice();
	}

	/**
	 * Localized config passed to the notice script.
	 *
	 * @return string
	 */
	private function localized_data() {
		return (string) wp_scripts()->get_data( self::HANDLE, 'data' );
	}

	/**
	 * A locally edited description shows the notice with the note and the restore action.
	 *
	 * @return void
	 */
	public function test_notice_is_enqueued_for_locally_edited_description() {
		$event_id = $this->locally_edited_event();

		$this->open_in_editor( $event_id );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );

		$data = $this->localized_data();
		$this->assertStringContainsString( 'Description edited on this site.', $data );
		$this->assertStringContainsString( 'Restore from Eventyay', $data );
		$this->assertStringContainsString( 'Unsaved changes to this event will be lost.', $data );
	}

	/**
	 * The restore URL given to the script is not HTML-encoded and keeps its nonce.
	 *
	 * @return void
	 */
	public function test_restore_url_is_plain_and_nonce_protected() {
		$event_id = $this->locally_edited_event();

		$this->open_in_editor( $event_id );

		$this->assertMatchesRegularExpression( '/var wpfaeventDescriptionNotice = (\{.*\});/', $this->localized_data() );
		preg_match( '/var wpfaeventDescriptionNotice = (\{.*\});/', $this->localized_data(), $matches );
		$config = json_decode( $matches[1], true );

		$this->assertIsArray( $config );
		$this->assertStringNotContainsString( '&amp;', $config['restoreUrl'] );

		$query = array();
		parse_str( (string) wp_parse_url( $config['restoreUrl'], PHP_URL_QUERY ), $query );
		$this->assertSame( Wpfaevent_Event_Description_Sync::RESTORE_ACTION, $query['action'] );
		$this->assertSame( (string) $event_id, $query['post_id'] );
		$this->assertSame( 1, wp_verify_nonce( $query['_wpnonce'], Wpfaevent_Event_Description_Sync::RESTORE_ACTION . '_' . $event_id ) );
	}

	/**
	 * Events that follow Eventyay get no notice.
	 *
	 * @return void
	 */
	public function test_no_notice_without_local_edit() {
		$event_id = $this->import( 'Original description.' );

		$this->open_in_editor( $event_id );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * Users who cannot edit the event get no notice.
	 *
	 * @return void
	 */
	public function test_no_notice_without_edit_capability() {
		$event_id = $this->locally_edited_event();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->open_in_editor( $event_id );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * Other post types and editors without a post get no notice.
	 *
	 * @return void
	 */
	public function test_no_notice_outside_event_editor() {
		$this->open_in_editor( self::factory()->post->create() );
		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );

		$GLOBALS['post'] = null;
		( new Wpfaevent_Admin_Event_Metabox() )->enqueue_description_notice();
		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * The sidebar meta box is only the classic editor fallback.
	 *
	 * @return void
	 */
	public function test_meta_box_is_classic_editor_fallback() {
		global $wp_meta_boxes;

		$event_id        = $this->locally_edited_event();
		$GLOBALS['post'] = get_post( $event_id );
		$metabox         = new Wpfaevent_Admin_Event_Metabox();

		add_filter( 'use_block_editor_for_post', '__return_true' );
		$metabox->register_meta_boxes();
		remove_filter( 'use_block_editor_for_post', '__return_true' );
		$this->assertArrayNotHasKey( 'wpfa_eventyay_description', $wp_meta_boxes['wpfa_event']['side']['high'] ?? array() );

		$wp_meta_boxes = array();
		add_filter( 'use_block_editor_for_post', '__return_false' );
		$metabox->register_meta_boxes();
		remove_filter( 'use_block_editor_for_post', '__return_false' );
		$this->assertArrayHasKey( 'wpfa_eventyay_description', $wp_meta_boxes['wpfa_event']['side']['high'] );
	}
}
