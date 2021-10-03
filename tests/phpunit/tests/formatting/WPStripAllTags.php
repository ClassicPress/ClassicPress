<?php
/**
 * Test wp_strip_all_tags()
 *
 * @group formatting
 */
class Tests_Formatting_WPStripAllTags extends WP_UnitTestCase {

	function test_wp_strip_all_tags() {

		$text = 'lorem<br />ipsum';
		$this->assertSame( 'loremipsum', wp_strip_all_tags( $text ) );

		$text = "lorem<br />\nipsum";
		$this->assertSame( "lorem\nipsum", wp_strip_all_tags( $text ) );

<<<<<<< HEAD
		// test removing breaks is working
		$text = "lorem<br />ipsum";
		$this->assertEquals( "loremipsum", wp_strip_all_tags( $text, true ) );

		// test script / style tag's contents is removed
		$text = "lorem<script>alert(document.cookie)</script>ipsum";
		$this->assertEquals( "loremipsum", wp_strip_all_tags( $text ) );

		$text = "lorem<style>* { display: 'none' }</style>ipsum";
		$this->assertEquals( "loremipsum", wp_strip_all_tags( $text ) );
=======
		// Test removing breaks is working.
		$text = 'lorem<br />ipsum';
		$this->assertSame( 'loremipsum', wp_strip_all_tags( $text, true ) );

		// Test script / style tag's contents is removed.
		$text = 'lorem<script>alert(document.cookie)</script>ipsum';
		$this->assertSame( 'loremipsum', wp_strip_all_tags( $text ) );

		$text = "lorem<style>* { display: 'none' }</style>ipsum";
		$this->assertSame( 'loremipsum', wp_strip_all_tags( $text ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		// test "marlformed" markup of contents
		$text = "lorem<style>* { display: 'none' }<script>alert( document.cookie )</script></style>ipsum";
<<<<<<< HEAD
		$this->assertEquals( "loremipsum", wp_strip_all_tags( $text ) );
=======
		$this->assertSame( 'loremipsum', wp_strip_all_tags( $text ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}
}

