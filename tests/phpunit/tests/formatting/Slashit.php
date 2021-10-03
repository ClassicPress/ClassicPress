<?php

/**
 * @group formatting
 */
class Tests_Formatting_Slashit extends WP_UnitTestCase {
	function test_backslashes_middle_numbers() {
<<<<<<< HEAD
		$this->assertEquals("\\a-!9\\a943\\b\\c", backslashit("a-!9a943bc"));
	}

	function test_backslashes_alphas() {
		$this->assertEquals("\\a943\\b\\c", backslashit("a943bc"));
	}

	function test_double_backslashes_leading_numbers() {
		$this->assertEquals("\\\\95", backslashit("95"));
	}

	function test_removes_trailing_slashes() {
		$this->assertEquals("a", untrailingslashit("a/"));
		$this->assertEquals("a", untrailingslashit("a////"));
=======
		$this->assertSame( "\\a-!9\\a943\\b\\c", backslashit( 'a-!9a943bc' ) );
	}

	function test_backslashes_alphas() {
		$this->assertSame( "\\a943\\b\\c", backslashit( 'a943bc' ) );
	}

	function test_double_backslashes_leading_numbers() {
		$this->assertSame( '\\\\95', backslashit( '95' ) );
	}

	function test_removes_trailing_slashes() {
		$this->assertSame( 'a', untrailingslashit( 'a/' ) );
		$this->assertSame( 'a', untrailingslashit( 'a////' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22267
	 */
	function test_removes_trailing_backslashes() {
<<<<<<< HEAD
		$this->assertEquals("a", untrailingslashit("a\\"));
		$this->assertEquals("a", untrailingslashit("a\\\\\\\\"));
=======
		$this->assertSame( 'a', untrailingslashit( 'a\\' ) );
		$this->assertSame( 'a', untrailingslashit( 'a\\\\\\\\' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22267
	 */
	function test_removes_trailing_mixed_slashes() {
<<<<<<< HEAD
		$this->assertEquals("a", untrailingslashit("a/\\"));
		$this->assertEquals("a", untrailingslashit("a\\/\\///\\\\//"));
	}

	function test_adds_trailing_slash() {
		$this->assertEquals("a/", trailingslashit("a"));
	}

	function test_does_not_add_trailing_slash_if_one_exists() {
		$this->assertEquals("a/", trailingslashit("a/"));
=======
		$this->assertSame( 'a', untrailingslashit( 'a/\\' ) );
		$this->assertSame( 'a', untrailingslashit( 'a\\/\\///\\\\//' ) );
	}

	function test_adds_trailing_slash() {
		$this->assertSame( 'a/', trailingslashit( 'a' ) );
	}

	function test_does_not_add_trailing_slash_if_one_exists() {
		$this->assertSame( 'a/', trailingslashit( 'a/' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22267
	 */
	function test_converts_trailing_backslash_to_slash_if_one_exists() {
<<<<<<< HEAD
		$this->assertEquals("a/", trailingslashit("a\\"));
=======
		$this->assertSame( 'a/', trailingslashit( 'a\\' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}
}
