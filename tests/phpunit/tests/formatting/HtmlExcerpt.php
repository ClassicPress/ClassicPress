<?php

/**
 * @group formatting
 */
class Tests_Formatting_HtmlExcerpt extends WP_UnitTestCase {
	function test_simple() {
<<<<<<< HEAD
		$this->assertEquals("Baba", wp_html_excerpt("Baba told me not to come", 4));
	}
	function test_html() {
		$this->assertEquals("Baba", wp_html_excerpt("<a href='http://baba.net/'>Baba</a> told me not to come", 4));
	}
	function test_entities() {
		$this->assertEquals("Baba", wp_html_excerpt("Baba &amp; Dyado", 8));
		$this->assertEquals("Baba", wp_html_excerpt("Baba &#038; Dyado", 8));
		$this->assertEquals("Baba &amp; D", wp_html_excerpt("Baba &amp; Dyado", 12));
		$this->assertEquals("Baba &amp; Dyado", wp_html_excerpt("Baba &amp; Dyado", 100));
=======
		$this->assertSame( 'Baba', wp_html_excerpt( 'Baba told me not to come', 4 ) );
	}
	function test_html() {
		$this->assertSame( 'Baba', wp_html_excerpt( "<a href='http://baba.net/'>Baba</a> told me not to come", 4 ) );
	}
	function test_entities() {
		$this->assertSame( 'Baba', wp_html_excerpt( 'Baba &amp; Dyado', 8 ) );
		$this->assertSame( 'Baba', wp_html_excerpt( 'Baba &#038; Dyado', 8 ) );
		$this->assertSame( 'Baba &amp; D', wp_html_excerpt( 'Baba &amp; Dyado', 12 ) );
		$this->assertSame( 'Baba &amp; Dyado', wp_html_excerpt( 'Baba &amp; Dyado', 100 ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}
}
