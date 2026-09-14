<?php
/**
 * Unit tests for embedded template rendering.
 *
 * @package Wpfaevent
 */

/**
 * Shortcodes and blocks render a template inside an existing page, so the
 * output must not carry its own document shell, navigation or footer.
 */
class TemplateEmbedTest extends WP_UnitTestCase {

	/**
	 * Every plugin template key together with a class only its own body emits.
	 *
	 * The marker must not be the wrapper class that render_embed() adds, or the
	 * assertion would pass for a template that rendered nothing at all.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function template_provider() {
		return array(
			'landing'                => array( 'landing', 'wpfa-landing' ),
			'speakers'               => array( 'speakers', 'wpfa-speakers' ),
			'events'                 => array( 'events', 'wpfa-events' ),
			'past_events'            => array( 'past_events', 'wpfa-past-events' ),
			'schedule'               => array( 'schedule', 'wpfa-schedule' ),
			'code_of_conduct'        => array( 'code_of_conduct', 'main-content' ),
			'additional_information' => array( 'additional_information', 'wpfa-additional-information' ),
			'partner'                => array( 'partner', 'wpfa-partner-detail' ),
		);
	}

	/**
	 * Embedded output contains the template body but no document shell.
	 *
	 * @dataProvider template_provider
	 *
	 * @param string $key        Template key.
	 * @param string $body_class Class that proves the template body rendered.
	 */
	public function test_embed_renders_without_document_shell( $key, $body_class ) {
		$output = Wpfaevent_Templates::render_embed( $key );

		$this->assertStringContainsString( 'class="wpfaevent wpfaevent-embed"', $output );
		$this->assertStringContainsString( $body_class, $output );

		foreach ( array( '<!DOCTYPE', '<html', '<head>', '<body', '</html>' ) as $shell_tag ) {
			$this->assertStringNotContainsString( $shell_tag, $output, "Embedded {$key} template must not output {$shell_tag}" );
		}
	}
}
