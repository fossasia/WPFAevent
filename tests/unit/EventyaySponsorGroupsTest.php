<?php
/**
 * Unit tests for Eventyay sponsor groups.
 *
 * @package Wpfaevent
 */

/**
 * Covers sponsor group naming and ordering for Eventyay imports.
 */
class EventyaySponsorGroupsTest extends WP_UnitTestCase {

	/**
	 * Mock responses keyed by URL fragment.
	 *
	 * @var array<string, array>
	 */
	private $mock_http_responses = array();

	/**
	 * Temporary uploads base directory for importer tests.
	 *
	 * @var string
	 */
	private $mock_upload_basedir = '';

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mock_http_responses = array();
		$this->mock_upload_basedir = '';
		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
	}

	/**
	 * Teardown.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		parent::tearDown();
	}

	/**
	 * Mock importer HTTP requests.
	 *
	 * @param false|array|WP_Error $pre         Preemptive response.
	 * @param array                $parsed_args Request args.
	 * @param string               $url         Request URL.
	 * @return false|array|WP_Error
	 */
	public function mock_http_request( $pre, $parsed_args, $url ) {
		foreach ( $this->mock_http_responses as $fragment => $response ) {
			if ( false !== strpos( $url, $fragment ) ) {
				return $response;
			}
		}

		return $pre;
	}

	/**
	 * Redirect uploads into a test-specific temporary directory when requested.
	 *
	 * @param array $dirs Upload directory data.
	 * @return array
	 */
	public function filter_upload_dir( $dirs ) {
		if ( '' === $this->mock_upload_basedir ) {
			return $dirs;
		}

		$dirs['basedir'] = $this->mock_upload_basedir;
		$dirs['baseurl'] = 'https://example.com/uploads';

		return $dirs;
	}

	/**
	 * Verify JSON:API resource type does not override sponsor tier labels.
	 */
	public function test_normalize_eventyay_sponsor_resource_prefers_tier_fields_over_resource_type() {
		$parser  = new Wpfaevent_JSONAPI_Parser();
		$sponsor = $parser->normalize_eventyay_sponsor_resource(
			array(
				'type'       => 'sponsor',
				'id'         => '1321',
				'attributes' => array(
					'name'       => 'AWS',
					'level_name' => 'Gold Sponsors',
					'level'      => 1,
				),
			),
			array(
				'base_url' => 'https://eventyay.com',
			)
		);

		$this->assertSame( 'Gold Sponsors', $sponsor['type'] );
		$this->assertSame( 1, $sponsor['level'] );
	}

	/**
	 * Verify all supported sponsor group fields are considered before generic type fallback.
	 */
	public function test_eventyay_sponsor_group_name_supports_legacy_group_fields() {
		$parser = new Wpfaevent_JSONAPI_Parser();
		$cases  = array(
			'sponsor_type'     => 'Gold Sponsors',
			'sponsor-type'     => 'Silver Sponsors',
			'sponsorship_type' => 'Bronze Sponsors',
			'sponsorship-type' => 'Community Sponsors',
			'package'          => 'Startup Sponsors',
			'package_name'     => 'Ecosystem Sponsors',
			'package-name'     => 'Media Sponsors',
		);

		foreach ( $cases as $field => $expected ) {
			$this->assertSame(
				$expected,
				$parser->eventyay_sponsor_group_name(
					array(
						'attributes' => array(
							$field => $expected,
							'type' => 'sponsor',
						),
					)
				)
			);
		}
	}

	/**
	 * Verify generic JSON:API resource type values are ignored when no tier field exists.
	 */
	public function test_normalize_eventyay_sponsor_resource_ignores_generic_sponsor_resource_type() {
		$parser  = new Wpfaevent_JSONAPI_Parser();
		$sponsor = $parser->normalize_eventyay_sponsor_resource(
			array(
				'type'       => 'sponsor',
				'id'         => '1329',
				'attributes' => array(
					'name'  => 'Navicat',
					'level' => 2,
				),
			),
			array(
				'base_url' => 'https://eventyay.com',
			)
		);

		$this->assertSame( '', $sponsor['type'] );
		$this->assertSame( 2, $sponsor['level'] );
	}

	/**
	 * Verify existing sponsor group order survives Eventyay reimports.
	 */
	public function test_merge_eventyay_sponsor_groups_preserves_existing_group_order() {
		$parser   = new Wpfaevent_JSONAPI_Parser();
		$existing = array(
			array(
				'group_name'         => 'Gold Sponsors',
				'source'             => 'eventyay',
				'eventyay_group_key' => 'goldsponsors',
				'sponsors'           => array(),
			),
			array(
				'group_name' => 'Community Sponsors',
				'sponsors'   => array(
					array(
						'name'   => 'Local Partner',
						'type'   => 'Community Sponsors',
						'source' => 'manual',
					),
				),
			),
			array(
				'group_name'         => 'Platinum Sponsors',
				'source'             => 'eventyay',
				'eventyay_group_key' => 'platinumsponsors',
				'sponsors'           => array(),
			),
		);
		$imported = array(
			array(
				'name'   => 'Acme',
				'type'   => 'Platinum Sponsors',
				'level'  => 1,
				'source' => 'eventyay',
			),
			array(
				'name'   => 'Beta',
				'type'   => 'Gold Sponsors',
				'level'  => 2,
				'source' => 'eventyay',
			),
		);

		$merged = $parser->merge_eventyay_sponsor_groups( $imported, $existing );

		$this->assertSame( 'Gold Sponsors', $merged[0]['group_name'] );
		$this->assertSame( 'Community Sponsors', $merged[1]['group_name'] );
		$this->assertSame( 'Platinum Sponsors', $merged[2]['group_name'] );
		$this->assertSame( 'Acme', $merged[2]['sponsors'][0]['name'] );
		$this->assertSame( 'Beta', $merged[0]['sponsors'][0]['name'] );
	}

	/**
	 * Verify manual array-based groups without stable keys are preserved during merges.
	 */
	public function test_merge_eventyay_sponsor_groups_preserves_unkeyed_manual_groups() {
		$parser   = new Wpfaevent_JSONAPI_Parser();
		$existing = array(
			array(
				'group_name'         => 'Gold Sponsors',
				'source'             => 'eventyay',
				'eventyay_group_key' => 'goldsponsors',
				'sponsors'           => array(),
			),
			array(
				'centered' => true,
				'sponsors' => array(
					array(
						'name'   => 'Local Partner',
						'source' => 'manual',
					),
				),
			),
		);
		$imported = array(
			array(
				'name'   => 'Beta',
				'type'   => 'Gold Sponsors',
				'level'  => 2,
				'source' => 'eventyay',
			),
		);

		$merged = $parser->merge_eventyay_sponsor_groups( $imported, $existing );

		$this->assertCount( 2, $merged );
		$this->assertSame( 'Gold Sponsors', $merged[0]['group_name'] );
		$this->assertTrue( $merged[1]['centered'] );
		$this->assertSame( 'Local Partner', $merged[1]['sponsors'][0]['name'] );
	}

	/**
	 * Verify the shared sponsor-group key helper preserves the current normalized format.
	 */
	public function test_shared_sponsor_group_key_helper_matches_current_format() {
		$this->assertSame( 'goldsponsors', Wpfaevent_Partner_Helper::normalize_sponsor_group_key( 'Gold Sponsors' ) );
		$this->assertSame(
			'platinumsponsors',
			Wpfaevent_Partner_Helper::get_sponsor_group_key(
				array(
					'group_name'         => 'Ignored Name',
					'eventyay_group_key' => 'Platinum Sponsors',
				)
			)
		);
	}

	/**
	 * Verify sponsor imports keep all normalized sponsors without public-page filtering.
	 */
	public function test_import_eventyay_event_partner_data_does_not_remove_sponsors_using_public_html() {
		$event_id                 = $this->factory->post->create(
			array(
				'post_type'   => 'wpfa_event',
				'post_status' => 'publish',
				'post_title'  => 'Importer Event',
			)
		);
		$this->mock_upload_basedir = trailingslashit( sys_get_temp_dir() ) . 'wpfaevent-tests-' . wp_generate_password( 8, false );
		wp_mkdir_p( $this->mock_upload_basedir );

		$this->mock_http_responses = array(
			'/sponsors'   => array(
				'headers'  => array(),
				'body'     => wp_json_encode(
					array(
						'results' => array(
							array(
								'id'         => '1',
								'attributes' => array(
									'name'       => 'Alpha Sponsor',
									'level_name' => 'Gold Sponsors',
									'level'      => 1,
								),
							),
							array(
								'id'         => '2',
								'attributes' => array(
									'name'       => 'Beta Sponsor',
									'level_name' => 'Gold Sponsors',
									'level'      => 1,
								),
							),
						),
						'next'    => null,
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
			'/exhibitors' => array(
				'headers'  => array(),
				'body'     => wp_json_encode(
					array(
						'results' => array(),
						'next'    => null,
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			),
		);

		$importer = new Wpfaevent_Eventyay_Importer();
		$method   = new ReflectionMethod( $importer, 'import_eventyay_event_partner_data' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$importer,
			$event_id,
			array(
				'id'   => 'demo-event',
				'slug' => 'demo-event',
			),
			array(
				'base_url'  => 'https://eventyay.example',
				'api_token' => '',
			),
			'demo-event'
		);

		$this->assertIsArray( $result );
		$this->assertSame( 2, $result['sponsor_count'] );

		$stored_groups = ( new Wpfaevent_Eventyay_Dashboard_Store() )->read_dashboard_json_file(
			'sponsors-' . $event_id . '.json',
			array()
		);

		$this->assertCount( 1, $stored_groups );
		$this->assertCount( 2, $stored_groups[0]['sponsors'] );
		$this->assertSame( 'Alpha Sponsor', $stored_groups[0]['sponsors'][0]['name'] );
		$this->assertSame( 'Beta Sponsor', $stored_groups[0]['sponsors'][1]['name'] );
	}
}
