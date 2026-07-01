<?php
/**
 * Class Unit_SampleTest
 *
 * @package Wpfaevent
 */

/**
 * Sample test class to verify environment boot stability.
 */
class Unit_SampleTest extends WP_UnitTestCase {

	/**
	 * Verify that the baseline setup is working and WordPress functions are accessible.
	 */
	public function test_wordpress_environment_boots_successfully() {
		// Create a mock post inside the temporary database.
		$post_id = $this->factory->post->create(
			array(
				'post_title' => 'Test Event Post',
				'post_type'  => 'post',
			)
		);

		$post = get_post( $post_id );

		$this->assertNotEmpty( $post_id );
		$this->assertEquals( 'Test Event Post', $post->post_title );
	}
}
