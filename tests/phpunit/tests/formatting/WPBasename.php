<?php

/**
 * @group formatting
 */
class Tests_Formatting_WP_Basename extends WP_UnitTestCase {

	function test_wp_basename_unix() {
<<<<<<< HEAD
		$this->assertEquals('file',
			wp_basename('/home/test/file'));
	}

	function test_wp_basename_unix_utf8_support() {
		$this->assertEquals('žluťoučký kůň.txt',
			wp_basename('/test/žluťoučký kůň.txt'));
=======
		$this->assertSame(
			'file',
			wp_basename( '/home/test/file' )
		);
	}

	function test_wp_basename_unix_utf8_support() {
		$this->assertSame(
			'žluťoučký kůň.txt',
			wp_basename( '/test/žluťoučký kůň.txt' )
		);
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22138
	 */
	function test_wp_basename_windows() {
<<<<<<< HEAD
		$this->assertEquals('file.txt',
			wp_basename('C:\Documents and Settings\User\file.txt'));
=======
		$this->assertSame(
			'file.txt',
			wp_basename( 'C:\Documents and Settings\User\file.txt' )
		);
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22138
	 */
	function test_wp_basename_windows_utf8_support() {
<<<<<<< HEAD
		$this->assertEquals('щипцы.txt',
			wp_basename('C:\test\щипцы.txt'));
=======
		$this->assertSame(
			'щипцы.txt',
			wp_basename( 'C:\test\щипцы.txt' )
		);
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

}
