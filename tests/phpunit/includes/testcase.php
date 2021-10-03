<?php

require_once dirname( __FILE__ ) . '/abstract-testcase.php';

/**
 * Defines a basic fixture to run multiple tests.
 *
 * Resets the state of the ClassicPress installation before and after every test.
 *
 * Includes utility functions and assertions useful for testing ClassicPress.
 *
 * All ClassicPress unit tests should inherit from this class.
 */

class WP_UnitTestCase extends WP_UnitTestCase_Base {
	/**
	 * Asserts that a condition is not false.
	 *
	 * @param bool   $condition
	 * @param string $message
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNotFalse( $condition, $message = '' ) {
		self::assertThat( $condition, self::logicalNot( self::isFalse() ), $message );
	}
}
