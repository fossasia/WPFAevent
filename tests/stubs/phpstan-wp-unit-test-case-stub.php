<?php
// phpcs:ignoreFile -- PHPStan-only stub definitions for the WordPress test framework.
/**
 * PHPStan stubs for WordPress unit test classes.
 *
 * @package Wpfaevent
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	/**
	 * Lightweight test factory user stub.
	 */
	class Wpfaevent_PHPStan_Test_Factory_User {
		/**
		 * @param array<string, mixed> $args Optional factory arguments.
		 */
		public function create( array $args = array() ): int {
			return 1;
		}
	}

	/**
	 * Lightweight test factory post stub.
	 */
	class Wpfaevent_PHPStan_Test_Factory_Post {
		/**
		 * @param array<string, mixed> $args Optional factory arguments.
		 */
		public function create( array $args = array() ): int {
			return 1;
		}
	}

	/**
	 * Lightweight aggregate factory stub.
	 */
	class Wpfaevent_PHPStan_Test_Factory {
		/**
		 * User factory stub.
		 *
		 * @var Wpfaevent_PHPStan_Test_Factory_User
		 */
		public $user;

		/**
		 * Post factory stub.
		 *
		 * @var Wpfaevent_PHPStan_Test_Factory_Post
		 */
		public $post;
	}

	/**
	 * Minimal WordPress test case stub for static analysis.
	 */
	class WP_UnitTestCase {
		/**
		 * Simulated WordPress test factory.
		 *
		 * @var Wpfaevent_PHPStan_Test_Factory
		 */
		public $factory;

		/**
		 * PHPUnit-compatible setup hook signature.
		 */
		public function setUp(): void {}

		/**
		 * @param mixed $condition Condition to assert.
		 */
		public function assertTrue( $condition ): void {}

		/**
		 * @param mixed $condition Condition to assert.
		 */
		public function assertFalse( $condition ): void {}

		/**
		 * @param mixed $actual Value to assert.
		 */
		public function assertIsArray( $actual ): void {}

		/**
		 * @param mixed $actual Value to assert.
		 */
		public function assertEmpty( $actual ): void {}

		/**
		 * @param mixed $needle   Expected item.
		 * @param mixed $haystack Collection under test.
		 */
		public function assertContains( $needle, $haystack ): void {}

		/**
		 * @param mixed $actual Value to assert.
		 */
		public function assertNotEmpty( $actual ): void {}

		/**
		 * @param mixed $expected Expected value.
		 * @param mixed $actual   Actual value.
		 */
		public function assertEquals( $expected, $actual ): void {}
	}
}
