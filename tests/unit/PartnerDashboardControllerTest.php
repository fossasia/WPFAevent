<?php
/**
 * Unit tests for manual partner dashboard identifiers.
 *
 * @package Wpfaevent
 */

/**
 * Verify manual partner identifiers stay compatible with CRUD routing.
 */
class PartnerDashboardControllerTest extends WP_UnitTestCase {

	/**
	 * Manual partner IDs should already be normalized for sanitize_key() lookups.
	 */
	public function test_generate_manual_partner_id_returns_a_wordpress_safe_key() {
		$controller = new Wpfaevent_Partner_Dashboard_Controller();
		$method     = new ReflectionMethod( $controller, 'generate_manual_partner_id' );
		$method->setAccessible( true );

		$id = $method->invoke( $controller, 'sponsor' );

		$this->assertStringStartsWith( 'manual-sponsor-', $id );
		$this->assertSame( $id, sanitize_key( $id ) );
		$this->assertMatchesRegularExpression( '/^manual-sponsor-[a-z0-9_-]+$/', $id );
	}
}
