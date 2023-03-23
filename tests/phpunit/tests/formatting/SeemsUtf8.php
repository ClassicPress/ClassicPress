<?php

/**
 * @group formatting
 *
 * @covers ::seems_utf8
 */
class Tests_Formatting_SeemsUtf8 extends WP_UnitTestCase {

	/**
	 * `seems_utf8` returns true for utf-8 strings, false otherwise.
	 *
	 * @dataProvider utf8_strings
	 */
	public function test_returns_true_for_utf8_strings( $utf8_string ) {
		// From http://www.i18nguy.com/unicode-example.html
		$this->assertTrue( seems_utf8( $utf8_string ) );
	}

	public function utf8_strings() {
		$utf8_strings = file( DIR_TESTDATA . '/formatting/utf-8/utf-8.txt' );
		foreach ( $utf8_strings as &$string ) {
			$string = (array) trim( $string );
		}
		unset( $string );
		return $utf8_strings;
	}

	/**
	 * @dataProvider big5_strings
	 */
	public function test_returns_false_for_non_utf8_strings( $big5_string ) {
		$this->assertFalse( seems_utf8( $big5_string ) );
	}

	public function big5_strings() {
		// Get data from formatting/big5.txt.
		$big5_strings = file( DIR_TESTDATA . '/formatting/big5.txt' );
		foreach ( $big5_strings as &$string ) {
			$string = (array) trim( $string );
		}
		unset( $string );
		return $big5_strings;
	}
}

