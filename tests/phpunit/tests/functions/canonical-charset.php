<?php

/*
 * Validate that's badly named charsets always return the correct format for UTF-8 and ISO-8859-1
 *
 *  @since WP-4.8.0
 */

class Tests_Functions_canonical_charset extends WP_UnitTestCase {

	public function test_utf_8_lower() {
		$this->assertSame( 'UTF-8', _canonical_charset( 'utf-8' ) );
	}

	public function test_utf_8_upper() {
		$this->assertSame( 'UTF-8', _canonical_charset( 'UTF-8' ) );
	}

	public function test_utf_8_mixxed() {
		$this->assertSame( 'UTF-8', _canonical_charset( 'Utf-8' ) );
	}

	public function test_utf_8() {
		$this->assertSame( 'UTF-8', _canonical_charset( 'UTF8' ) );
	}

	public function test_iso_lower() {
		$this->assertSame( 'ISO-8859-1', _canonical_charset( 'iso-8859-1' ) );
	}

	public function test_iso_upper() {
		$this->assertSame( 'ISO-8859-1', _canonical_charset( 'ISO-8859-1' ) );
	}

	public function test_iso_mixxed() {
		$this->assertSame( 'ISO-8859-1', _canonical_charset( 'Iso8859-1' ) );
	}

	public function test_iso() {
		$this->assertSame( 'ISO-8859-1', _canonical_charset( 'ISO8859-1' ) );
	}

	public function test_random() {
		$this->assertSame( 'random', _canonical_charset( 'random' ) );
	}

	public function test_empty() {
		$this->assertSame( '', _canonical_charset( '' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/23688
	 */
	function test_update_option_blog_charset() {
		$orig_blog_charset = get_option( 'blog_charset' );

		update_option( 'blog_charset', 'utf8' );
<<<<<<< HEAD:tests/phpunit/tests/functions/canonical-charset.php
		$this->assertEquals( 'UTF-8', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'utf-8' );
		$this->assertEquals( 'UTF-8', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'UTF8' );
		$this->assertEquals( 'UTF-8', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'UTF-8' );
		$this->assertEquals( 'UTF-8', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'ISO-8859-1' );
		$this->assertEquals( 'ISO-8859-1', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'ISO8859-1' );
		$this->assertEquals( 'ISO-8859-1', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'iso8859-1' );
		$this->assertEquals( 'ISO-8859-1', get_option( 'blog_charset') );

		update_option( 'blog_charset', 'iso-8859-1' );
		$this->assertEquals( 'ISO-8859-1', get_option( 'blog_charset') );

		// Arbitrary strings are passed through.
		update_option( 'blog_charset', 'foobarbaz' );
		$this->assertEquals( 'foobarbaz', get_option( 'blog_charset') );
=======
		$this->assertSame( 'UTF-8', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'utf-8' );
		$this->assertSame( 'UTF-8', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'UTF8' );
		$this->assertSame( 'UTF-8', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'UTF-8' );
		$this->assertSame( 'UTF-8', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'ISO-8859-1' );
		$this->assertSame( 'ISO-8859-1', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'ISO8859-1' );
		$this->assertSame( 'ISO-8859-1', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'iso8859-1' );
		$this->assertSame( 'ISO-8859-1', get_option( 'blog_charset' ) );

		update_option( 'blog_charset', 'iso-8859-1' );
		$this->assertSame( 'ISO-8859-1', get_option( 'blog_charset' ) );

		// Arbitrary strings are passed through.
		update_option( 'blog_charset', 'foobarbaz' );
		$this->assertSame( 'foobarbaz', get_option( 'blog_charset' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.):tests/phpunit/tests/functions/canonicalCharset.php

		update_option( 'blog_charset', $orig_blog_charset );
	}

}
