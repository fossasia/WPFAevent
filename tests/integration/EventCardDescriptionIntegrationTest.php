<?php
/**
 * Integration tests for event card description and lead-text data.
 *
 * @package Wpfaevent
 */

/**
 * Verify search and edit payloads remain independent when their values differ.
 */
class EventCardDescriptionIntegrationTest extends WP_UnitTestCase {

	/**
	 * Event card attributes keep displayed search text separate from the raw edit description.
	 */
	public function test_event_card_exposes_distinct_search_and_edit_description_attributes() {
		$description = 'DESCRIPTION-DOCKER-8472';
		$lead_text   = 'LEAD-KUBERNETES-3916';
		$event_id    = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Search regression event',
				'post_excerpt' => $description,
			)
		);

		update_post_meta( $event_id, 'wpfa_event_lead_text', $lead_text );

		ob_start();
		include dirname( __DIR__, 2 ) . '/public/partials/events/event-card.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-description="' . $lead_text . '"', $output );
		$this->assertStringContainsString( 'data-edit-description="' . $description . '"', $output );
		$this->assertStringContainsString( '<p class="event-card-description">' . $lead_text . '</p>', $output );
	}

	/**
	 * Legacy Eventyay lead text remains available to templates and event cards.
	 */
	public function test_legacy_event_lead_text_is_rendered_and_exposed_in_card_attributes() {
		$description = 'Legacy event description';
		$lead_text   = 'LEGACY-EVENTYAY-LEAD-9021';
		$event_id    = $this->factory->post->create(
			array(
				'post_type'    => 'wpfa_event',
				'post_title'   => 'Legacy Eventyay event',
				'post_excerpt' => $description,
			)
		);

		update_post_meta( $event_id, '_event_lead_text', $lead_text );

		$event_data = Wpfaevent_Event_Template_Controller::get_event_template_data( $event_id );

		$this->assertSame( $lead_text, $event_data['event_lead'] );

		ob_start();
		include dirname( __DIR__, 2 ) . '/public/partials/events/event-card.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-description="' . $lead_text . '"', $output );
		$this->assertStringContainsString( 'data-edit-description="' . $description . '"', $output );
		$this->assertStringContainsString( 'data-lead-text="' . $lead_text . '"', $output );
		$this->assertStringContainsString( '<p class="event-card-description">' . $lead_text . '</p>', $output );
	}

	/**
	 * Frontend search and edit-modal wiring use their dedicated card attributes.
	 */
	public function test_event_script_uses_distinct_search_and_edit_description_attributes() {
		$path   = dirname( __DIR__, 2 ) . '/public/js/wpfaevent-events.js';
		$source = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a repository fixture in a test.

		$this->assertNotFalse( $source );
		$this->assertStringContainsString( "const description = (card.dataset.description || '').toLowerCase();", $source );
		$this->assertStringContainsString( "card.dataset.editDescription || '';", $source );
	}
}
