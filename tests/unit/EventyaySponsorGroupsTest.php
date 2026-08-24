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
				'eventyay_group_key' => 'gold-sponsors',
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
				'eventyay_group_key' => 'platinum-sponsors',
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
}
