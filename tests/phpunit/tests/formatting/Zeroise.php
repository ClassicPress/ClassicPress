<?php

/**
 * @group formatting
 */
class Tests_Formatting_Zeroise extends WP_UnitTestCase {
	function test_pads_with_leading_zeroes() {
		$this->assertSame( '00005', zeroise( 5, 5 ) );
	}

	function test_does_nothing_if_input_is_already_longer() {
		$this->assertSame( '5000000', zeroise( 5000000, 2 ) );
	}
}
