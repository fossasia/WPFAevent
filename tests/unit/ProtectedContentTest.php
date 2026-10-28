<?php
/**
 * Unit tests for password-protected event and speaker content.
 *
 * @package Wpfaevent
 */

/**
 * Protected content unit tests.
 */
class ProtectedContentTest extends WP_UnitTestCase {

	/**
	 * Reset the current user and re-register plugin meta before each test.
	 *
	 * The core test case unregisters all meta keys on teardown.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 0 );
		Wpfaevent_Meta_Event::register();
		Wpfaevent_Meta_Speaker::register();
	}

	/**
	 * Create a password-protected event with lead text.
	 *
	 * @return int
	 */
	private function protected_event() {
		$event_id = $this->factory->post->create(
			array(
				'post_type'     => 'wpfa_event',
				'post_status'   => 'publish',
				'post_password' => 'secret',
				'post_excerpt'  => 'Private excerpt',
			)
		);
		update_post_meta( $event_id, 'wpfa_event_lead_text', 'Private lead text' );
		update_post_meta( $event_id, 'wpfa_event_start_date', '2026-06-05' );

		return $event_id;
	}

	/**
	 * Create a password-protected speaker with a bio and talk abstract.
	 *
	 * @return int
	 */
	private function protected_speaker() {
		$speaker_id = $this->factory->post->create(
			array(
				'post_type'     => 'wpfa_speaker',
				'post_status'   => 'publish',
				'post_password' => 'secret',
			)
		);
		update_post_meta( $speaker_id, 'wpfa_speaker_bio', 'Private speaker bio' );
		update_post_meta( $speaker_id, 'wpfa_speaker_talk_title', 'Public talk title' );
		update_post_meta( $speaker_id, 'wpfa_speaker_talk_abstract', 'Private talk abstract' );

		return $speaker_id;
	}

	/**
	 * Fetch a post through the REST API.
	 *
	 * @param string $route  REST route.
	 * @param array  $params Query parameters.
	 * @param int    $status Expected HTTP status.
	 * @return array
	 */
	private function rest_get( $route, $params = array(), $status = 200 ) {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $params );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( $status, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );

		return $response->get_data();
	}

	/**
	 * Only visitors who can't edit a protected post are locked out.
	 */
	public function test_is_locked_only_for_protected_posts_and_non_editors() {
		$event_id = $this->protected_event();
		$open_id  = $this->factory->post->create( array( 'post_type' => 'wpfa_event' ) );

		$this->assertTrue( Wpfaevent_Protected_Content::is_locked( $event_id ) );
		$this->assertFalse( Wpfaevent_Protected_Content::is_locked( $open_id ) );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertFalse( Wpfaevent_Protected_Content::is_locked( $event_id ) );
	}

	/**
	 * REST responses withhold protected meta until the password is supplied.
	 */
	public function test_rest_hides_protected_meta_until_password_is_supplied() {
		$event_id   = $this->protected_event();
		$speaker_id = $this->protected_speaker();

		$event   = $this->rest_get( '/wp/v2/wpfa_event/' . $event_id );
		$speaker = $this->rest_get( '/wp/v2/wpfa_speaker/' . $speaker_id );

		$this->assertSame( '', $event['meta']['wpfa_event_lead_text'] );
		$this->assertSame( '', $speaker['meta']['wpfa_speaker_bio'] );
		$this->assertSame( '', $speaker['meta']['wpfa_speaker_talk_abstract'] );
		$this->assertSame( 'Public talk title', $speaker['meta']['wpfa_speaker_talk_title'] );

		// Core rejects a wrong password outright.
		$this->rest_get( '/wp/v2/wpfa_event/' . $event_id, array( 'password' => 'wrong' ), 403 );

		$unlocked = $this->rest_get( '/wp/v2/wpfa_event/' . $event_id, array( 'password' => 'secret' ) );
		$this->assertSame( 'Private lead text', $unlocked['meta']['wpfa_event_lead_text'] );
	}

	/**
	 * Event cards in public listings don't print protected text.
	 */
	public function test_event_card_omits_protected_text() {
		$event_id = $this->protected_event();

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/events/event-card.php';
		$html = ob_get_clean();

		$this->assertStringContainsString( 'event-card', $html );
		$this->assertStringNotContainsString( 'Private lead text', $html );
		$this->assertStringNotContainsString( 'Private excerpt', $html );
	}

	/**
	 * Speaker cards don't print a protected bio or talk abstract.
	 */
	public function test_speaker_card_omits_protected_text() {
		$sid = $this->protected_speaker();

		ob_start();
		include WPFAEVENT_PATH . 'public/partials/speakers/speaker-card.php';
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Public talk title', $html );
		$this->assertStringNotContainsString( 'Private speaker bio', $html );
		$this->assertStringNotContainsString( 'Private talk abstract', $html );
	}

	/**
	 * The locked template shows the password form and no protected text.
	 */
	public function test_locked_template_shows_only_password_form() {
		$event_id = $this->protected_event();
		wp_update_post(
			array(
				'ID'           => $event_id,
				'post_content' => 'Private event content',
			)
		);

		foreach ( array( $event_id, $this->protected_speaker() ) as $post_id ) {
			$this->go_to( get_permalink( $post_id ) );

			ob_start();
			include WPFAEVENT_PATH . 'public/templates/single-wpfa-locked.php';
			$html = ob_get_clean();

			$this->assertStringContainsString( 'post-password-form', $html );
			$this->assertStringContainsString( get_the_title( $post_id ), $html );
			$this->assertDoesNotMatchRegularExpression( '/Private (event content|lead text|excerpt|speaker bio|talk abstract)/', $html );
		}
	}
}
