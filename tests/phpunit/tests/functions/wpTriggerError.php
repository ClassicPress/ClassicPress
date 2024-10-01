<?php

/**
 * Test cases for the `wp_trigger_error()` function.
 *
 * @since 6.4.0
 *
 * @group functions.php
 * @covers ::wp_trigger_error
 */
class Tests_Functions_WpTriggerError extends WP_UnitTestCase {

	/**
	 * @ticket 57686
	 *
	 * @dataProvider data_should_trigger_error
	 *
	 * @param string $function_name    The function name to test.
	 * @param string $message          The message to test.
	 * @param string $expected_message The expected error message.
	 */
	public function test_should_throw_exception( $function_name, $message, $expected_message ) {
		$this->expectException( WP_Exception::class );
		$this->expectExceptionMessage( $expected_message );

		wp_trigger_error( $function_name, $message, E_USER_ERROR );
	}

	/**
	 * @ticket 57686
	 *
	 * @dataProvider data_should_trigger_error
	 *
	 * @param string $function_name    The function name to test.
	 * @param string $message          The message to test.
	 * @param string $expected_message The expected error message.
	 */
	public function test_should_trigger_warning( $function_name, $message, $expected_message ) {
		$this->expectWarning();
		$this->expectWarningMessage( $expected_message );

		wp_trigger_error( $function_name, $message, E_USER_WARNING );
	}

	/**
	 * @ticket 57686
	 *
	 * @dataProvider data_should_trigger_error
	 *
	 * @param string $function_name    The function name to test.
	 * @param string $message          The message to test.
	 * @param string $expected_message The expected error message.
	 */
	public function test_should_trigger_notice( $function_name, $message, $expected_message ) {
		$this->expectNotice();
		$this->expectNoticeMessage( $expected_message );

		wp_trigger_error( $function_name, $message );
	}

	/**
	 * @ticket 57686
	 *
	 * @dataProvider data_should_trigger_error
	 *
	 * @param string $function_name    The function name to test.
	 * @param string $message          The message to test.
	 * @param string $expected_message The expected error message.
	 */
	public function test_should_trigger_deprecation( $function_name, $message, $expected_message ) {
		$this->expectDeprecation();
		$this->expectDeprecationMessage( $expected_message );

		wp_trigger_error( $function_name, $message, E_USER_DEPRECATED );
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_should_trigger_error() {
		return array(
			'function name and message are given' => array(
				'function_name'    => 'some_function',
				'message'          => 'expected the function name and message',
				'expected_message' => 'some_function(): expected the function name and message',
			),
			'message is given'                    => array(
				'function_name'    => '',
				'message'          => 'expect only the message',
				'expected_message' => 'expect only the message',
			),
			'function name is given'              => array(
				'function_name'    => 'some_function',
				'message'          => '',
				'expected_message' => 'some_function(): ',
			),
		);
	}
}
