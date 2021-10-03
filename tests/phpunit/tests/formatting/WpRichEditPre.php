<?php

/**
 * @group formatting
 * @expectedDeprecated wp_richedit_pre
 */
class Tests_Formatting_WpRichEditPre extends WP_UnitTestCase {

	function _charset_iso_8859_1() {
		return 'iso-8859-1';
	}

	/*
	 * Only fails in PHP 5.4 onwards
	 * @see https://core.trac.wordpress.org/ticket/23688
	 */
	function test_wp_richedit_pre_charset_iso_8859_1() {
		add_filter( 'pre_option_blog_charset', array( $this, '_charset_iso_8859_1' ) );
<<<<<<< HEAD
		$iso8859_1 = 'Fran' .chr(135) .'ais';
		$this->assertEquals( '&lt;p&gt;' . $iso8859_1 . "&lt;/p&gt;\n", wp_richedit_pre( $iso8859_1 ) );
=======
		$iso8859_1 = 'Fran' . chr( 135 ) . 'ais';
		$this->assertSame( '&lt;p&gt;' . $iso8859_1 . "&lt;/p&gt;\n", wp_richedit_pre( $iso8859_1 ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		remove_filter( 'pre_option_blog_charset', array( $this, '_charset_iso_8859_1' ) );
	}

	function _charset_utf_8() {
		return 'UTF-8';
	}

	/*
	 * @see https://core.trac.wordpress.org/ticket/23688
	 */
	function test_wp_richedit_pre_charset_utf_8() {
		add_filter( 'pre_option_blog_charset', array( $this, '_charset_utf_8' ) );
<<<<<<< HEAD
		$utf8 = 'Fran' .chr(195) . chr(167) .'ais';
		$this->assertEquals( '&lt;p&gt;' . $utf8 . "&lt;/p&gt;\n", wp_richedit_pre( $utf8 ) );
=======
		$utf8 = 'Fran' . chr( 195 ) . chr( 167 ) . 'ais';
		$this->assertSame( '&lt;p&gt;' . $utf8 . "&lt;/p&gt;\n", wp_richedit_pre( $utf8 ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		remove_filter( 'pre_option_blog_charset', array( $this, '_charset_utf_8' ) );
	}
}
