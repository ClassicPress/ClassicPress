<?php
/**
 * Basic abstract test class.
 *
 * All ClassicPress unit tests should inherit from this class.
 */

<<<<<<< HEAD
abstract class WP_UnitTestCase extends WP_UnitTestCase_Base {}
=======
	/**
	 * Asserts that a condition is not false.
	 *
	 * This method has been backported from a more recent PHPUnit version,
	 * as tests running on PHP 5.2 use PHPUnit 3.6.x.
	 *
	 * @since 4.7.4
	 *
	 * @param bool   $condition Condition to check.
	 * @param string $message   Optional. Message to display when the assertion fails.
	 *
	 * @throws PHPUnit_Framework_AssertionFailedError
	 */
	public static function assertNotFalse( $condition, $message = '' ) {
		self::assertThat( $condition, self::logicalNot( self::isFalse() ), $message );
	}

	/**
	 * Asserts that two variables are equal (with delta).
	 *
	 * This method has been backported from a more recent PHPUnit version,
	 * as tests running on PHP 5.6 use PHPUnit 5.7.x.
	 *
	 * @since 5.6.0
	 *
	 * @param mixed  $expected First value to compare.
	 * @param mixed  $actual   Second value to compare.
	 * @param float  $delta    Allowed numerical distance between two values to consider them equal.
	 * @param string $message  Optional. Message to display when the assertion fails.
	 *
	 * @throws ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public static function assertEqualsWithDelta( $expected, $actual, $delta, $message = '' ) {
		$constraint = new PHPUnit_Framework_Constraint_IsEqual(
			$expected,
			$delta
		);

		static::assertThat( $actual, $constraint, $message );
	}
}
>>>>>>> 5bad67bccf (Tests: Add a polyfill for `assertEqualsWithDelta()` to `WP_UnitTestCase` and use it where appropriate.)
