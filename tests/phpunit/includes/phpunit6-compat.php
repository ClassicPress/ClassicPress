<?php

if ( class_exists( 'PHPUnit\Runner\Version' ) && version_compare( PHPUnit\Runner\Version::id(), '6.0', '>=' ) ) {

	class_alias( 'PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase' );
	class_alias( 'PHPUnit\Framework\Exception', 'PHPUnit_Framework_Exception' );
	class_alias( 'PHPUnit\Framework\ExpectationFailedException', 'PHPUnit_Framework_ExpectationFailedException' );
	class_alias( 'PHPUnit\Framework\Error\Error', 'PHPUnit_Framework_Error' );
	class_alias( 'PHPUnit\Framework\Error\Notice', 'PHPUnit_Framework_Error_Notice' );
	class_alias( 'PHPUnit\Framework\Error\Warning', 'PHPUnit_Framework_Error_Warning' );
	class_alias( 'PHPUnit\Framework\Test', 'PHPUnit_Framework_Test' );
	class_alias( 'PHPUnit\Framework\Warning', 'PHPUnit_Framework_Warning' );
	class_alias( 'PHPUnit\Framework\AssertionFailedError', 'PHPUnit_Framework_AssertionFailedError' );
	class_alias( 'PHPUnit\Framework\TestSuite', 'PHPUnit_Framework_TestSuite' );
	class_alias( 'PHPUnit\Framework\TestListener', 'PHPUnit_Framework_TestListener' );
	class_alias( 'PHPUnit\Util\GlobalState', 'PHPUnit_Util_GlobalState' );
	class_alias( 'PHPUnit\Util\Getopt', 'PHPUnit_Util_Getopt' );

<<<<<<< HEAD:tests/phpunit/includes/phpunit6-compat.php
	class_alias( 'PHPUnit\Util\Test', 'PHPUnit_Util_Test' );
=======
	class PHPUnit_Util_Test {

		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		public static function getTickets( $class_name, $method_name ) {
			$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( $class_name, $method_name );

			$tickets = array();

			if ( isset( $annotations['class']['ticket'] ) ) {
				$tickets = $annotations['class']['ticket'];
			}

			if ( isset( $annotations['method']['ticket'] ) ) {
				$tickets = array_merge( $tickets, $annotations['method']['ticket'] );
			}

			return array_unique( $tickets );
		}

	}

>>>>>>> c6c78490e2 (Coding Standards: Fix the remaining issues in `/tests`.):tests/phpunit/includes/phpunit6/compat.php
}
