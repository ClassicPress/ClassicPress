<?php

/**
 * @group formatting
 */
class Tests_Formatting_JSEscape extends WP_UnitTestCase {
	function test_js_escape_simple() {
<<<<<<< HEAD
		$out = esc_js('foo bar baz();');
		$this->assertEquals('foo bar baz();', $out);
	}

	function test_js_escape_quotes() {
		$out = esc_js('foo "bar" \'baz\'');
		// does it make any sense to change " into &quot;?  Why not \"?
		$this->assertEquals("foo &quot;bar&quot; \'baz\'", $out);
=======
		$out = esc_js( 'foo bar baz();' );
		$this->assertSame( 'foo bar baz();', $out );
	}

	function test_js_escape_quotes() {
		$out = esc_js( 'foo "bar" \'baz\'' );
		// Does it make any sense to change " into &quot;?  Why not \"?
		$this->assertSame( "foo &quot;bar&quot; \'baz\'", $out );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_js_escape_backslash() {
		$bs = '\\';
		$out = esc_js('foo '.$bs.'t bar '.$bs.$bs.' baz');
		// \t becomes t - bug?
<<<<<<< HEAD
		$this->assertEquals('foo t bar '.$bs.$bs.' baz', $out);
	}

	function test_js_escape_amp() {
		$out = esc_js('foo & bar &baz; &nbsp;');
		$this->assertEquals("foo &amp; bar &amp;baz; &nbsp;", $out);
	}

	function test_js_escape_quote_entity() {
		$out = esc_js('foo &#x27; bar &#39; baz &#x26;');
		$this->assertEquals("foo \\' bar \\' baz &#x26;", $out);
=======
		$this->assertSame( 'foo t bar ' . $bs . $bs . ' baz', $out );
	}

	function test_js_escape_amp() {
		$out = esc_js( 'foo & bar &baz; &nbsp;' );
		$this->assertSame( 'foo &amp; bar &amp;baz; &nbsp;', $out );
	}

	function test_js_escape_quote_entity() {
		$out = esc_js( 'foo &#x27; bar &#39; baz &#x26;' );
		$this->assertSame( "foo \\' bar \\' baz &#x26;", $out );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_js_no_carriage_return() {
		$out = esc_js("foo\rbar\nbaz\r");
		// \r is stripped
		$this->assertequals("foobar\\nbaz", $out);
	}

	function test_js_escape_rn() {
		$out = esc_js("foo\r\nbar\nbaz\r\n");
		// \r is stripped
		$this->assertequals("foo\\nbar\\nbaz\\n", $out);
	}
}
