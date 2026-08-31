<?php
/**
 * Unit tests for partner dashboard controller helpers.
 *
 * @package Wpfaevent
 */

/**
 * Verify manual partner identifiers and sponsor reorder persistence behavior.
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

	/**
	 * Legacy mixed-case IDs should match the normalized IDs used in delete URLs.
	 */
	public function test_remove_partner_record_deletes_legacy_mixed_case_id() {
		$controller = new Wpfaevent_Partner_Dashboard_Controller();
		$method     = new ReflectionMethod( $controller, 'remove_partner_record' );
		$method->setAccessible( true );
		$records = array(
			array(
				'id'     => 'manual-sponsor-AbC12xYz',
				'source' => 'manual',
				'name'   => 'Legacy Sponsor',
			),
			array(
				'id'     => 'manual-sponsor-current123',
				'source' => 'manual',
				'name'   => 'Current Sponsor',
			),
		);

		$remaining = $method->invoke( $controller, $records, 'manual-sponsor-abc12xyz' );

		$this->assertCount( 1, $remaining );
		$this->assertSame( 'manual-sponsor-current123', $remaining[0]['id'] );
	}

	/**
	 * Successful sponsor-group reorders should persist and redirect with a success notice.
	 */
	public function test_process_reorder_sponsor_groups_persists_reordered_groups() {
		$written_filename = '';
		$written_data     = null;
		$store            = $this->getMockBuilder( 'Wpfaevent_Eventyay_Dashboard_Store' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'read_dashboard_json_file', 'write_dashboard_json_file' ) )
			->getMock();

		$store->method( 'read_dashboard_json_file' )->willReturn(
			array(
				array(
					'group_name' => 'Gold Sponsors',
					'sponsors'   => array(),
				),
				array(
					'group_name' => 'Silver Sponsors',
					'sponsors'   => array(),
				),
			)
		);
		$store->method( 'write_dashboard_json_file' )->willReturnCallback(
			static function ( $filename, $data ) use ( &$written_filename, &$written_data ) {
				$written_filename = $filename;
				$written_data     = $data;

				return true;
			}
		);
		$stats = $this->getMockBuilder( 'Wpfaevent_Partner_Dashboard_Statistics' )
			->disableOriginalConstructor()
			->getMock();

		$controller = new Wpfaevent_Partner_Dashboard_Controller(
			$store,
			$stats
		);
		$method     = new ReflectionMethod( $controller, 'process_reorder_sponsor_groups' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, 25, array( 'silversponsors', 'goldsponsors' ) );
		$query  = wp_parse_args( wp_parse_url( $result['redirect_url'], PHP_URL_QUERY ) );

		$this->assertSame( 'sponsors-25.json', $written_filename );
		$this->assertSame( 'Silver Sponsors', $written_data[0]['group_name'] );
		$this->assertSame( 'Gold Sponsors', $written_data[1]['group_name'] );
		$this->assertTrue( $result['write_result'] );
		$this->assertSame( 'success', $query[ Wpfaevent_Partner_Dashboard_Controller::REORDER_NOTICE_QUERY_ARG ] );
	}

	/**
	 * Failed sponsor-group reorders should redirect with an error notice instead of success.
	 */
	public function test_process_reorder_sponsor_groups_returns_error_redirect_when_write_fails() {
		$store = $this->getMockBuilder( 'Wpfaevent_Eventyay_Dashboard_Store' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'read_dashboard_json_file', 'write_dashboard_json_file' ) )
			->getMock();

		$store->method( 'read_dashboard_json_file' )->willReturn(
			array(
				array(
					'group_name' => 'Gold Sponsors',
					'sponsors'   => array(),
				),
			)
		);
		$store->method( 'write_dashboard_json_file' )->willReturn(
			new WP_Error( 'write_failed', 'Could not write dashboard data.' )
		);
		$stats = $this->getMockBuilder( 'Wpfaevent_Partner_Dashboard_Statistics' )
			->disableOriginalConstructor()
			->getMock();

		$controller = new Wpfaevent_Partner_Dashboard_Controller(
			$store,
			$stats
		);
		$method     = new ReflectionMethod( $controller, 'process_reorder_sponsor_groups' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, 31, array( 'goldsponsors' ) );
		$query  = wp_parse_args( wp_parse_url( $result['redirect_url'], PHP_URL_QUERY ) );

		$this->assertTrue( is_wp_error( $result['write_result'] ) );
		$this->assertSame( 'error', $query[ Wpfaevent_Partner_Dashboard_Controller::REORDER_NOTICE_QUERY_ARG ] );
	}

	/**
	 * Reorder notice query args should map to the expected admin notice payloads.
	 */
	public function test_renderer_maps_reorder_notice_status_to_notice_payload() {
		$renderer = new Wpfaevent_Partner_Dashboard_Renderer();
		$method   = new ReflectionMethod( $renderer, 'get_reorder_notice' );
		$method->setAccessible( true );

		$_GET[ Wpfaevent_Partner_Dashboard_Controller::REORDER_NOTICE_QUERY_ARG ] = 'success'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Unit test fixture.
		$success_notice = $method->invoke( $renderer );

		$this->assertSame( 'success', $success_notice['type'] );
		$this->assertSame( 'Sponsor group order saved.', $success_notice['message'] );

		$_GET[ Wpfaevent_Partner_Dashboard_Controller::REORDER_NOTICE_QUERY_ARG ] = 'error'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Unit test fixture.
		$error_notice = $method->invoke( $renderer );

		$this->assertSame( 'error', $error_notice['type'] );
		$this->assertSame( 'Sponsor group order could not be saved. Please try again.', $error_notice['message'] );

		unset( $_GET[ Wpfaevent_Partner_Dashboard_Controller::REORDER_NOTICE_QUERY_ARG ] );
	}
}
