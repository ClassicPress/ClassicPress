<?php

/**
 * just make sure the test framework is working
 *
 * @group testsuite
 */
class Tests_Basic extends WP_UnitTestCase {

	function test_license() {
		// This test is designed to only run on trunk/master
		$this->skipOnAutomatedBranches();

		$license = file_get_contents( ABSPATH . 'license.txt' );
		$this_year = date( 'Y' );

		// Check WordPress copyright years
		// TODO: This only applies if we actually pull changes from WP in 2019 or later!
		preg_match( '#Copyright 2003-(\d+) by the WordPress contributors#', $license, $matches );
		$this->assertEquals( $this_year, trim( $matches[1] ), "license.txt's year needs to be updated to $this_year." );

		preg_match( '#Copyright (2018-)?(\d+) by the contributors#', $license, $matches );
		$this->assertEquals( $this_year, trim( $matches[2] ), "license.txt's year needs to be updated to $this_year." );
	}

	function test_package_json() {
		global $cp_version;
		$package_json = file_get_contents( dirname( ABSPATH ) . '/package.json' );
		$package_json = json_decode( $package_json, true );
		if ( isset( $cp_version ) ) {
			$this->assertEquals(
				$cp_version,
				$package_json['version'],
				"package.json's version needs to be updated to $cp_version."
			);
		} else {
			error_log( 'FIXME after PR #32 is merged' );
		}
		return $package_json;
	}

	// two tests for a lame bug in PHPUnit that broke the $GLOBALS reference
	function test_globals() {
		global $test_foo;
		$test_foo = array('foo', 'bar', 'baz');

		function test_globals_foo() {
			unset($GLOBALS['test_foo'][1]);
		}

		test_globals_foo();

		$this->assertEquals($test_foo, array(0=>'foo', 2=>'baz'));
		$this->assertEquals($test_foo, $GLOBALS['test_foo']);
	}

	function test_globals_bar() {
		global $test_bar;
		$test_bar = array('a', 'b', 'c');
		$this->assertEquals($test_bar, $GLOBALS['test_bar']);
	}

	// test some helper utility functions

	function test_strip_ws() {
		$this->assertEquals('', strip_ws(''));
		$this->assertEquals('foo', strip_ws('foo'));
		$this->assertEquals('', strip_ws("\r\n\t  \n\r\t"));

		$in  = "asdf\n";
		$in .= "asdf asdf\n";
		$in .= "asdf     asdf\n";
		$in .= "\tasdf\n";
		$in .= "\tasdf\t\n";
		$in .= "\t\tasdf\n";
		$in .= "foo bar\n\r\n";
		$in .= "foo\n";

		$expected  = "asdf\n";
		$expected .= "asdf asdf\n";
		$expected .= "asdf     asdf\n";
		$expected .= "asdf\n";
		$expected .= "asdf\n";
		$expected .= "asdf\n";
		$expected .= "foo bar\n";
		$expected .= "foo";

		$this->assertEquals($expected, strip_ws($in));

	}

	function test_mask_input_value() {
		$in = <<<EOF
<h2>Assign Authors</h2>
<p>To make it easier for you to edit and save the imported posts and drafts, you may want to change the name of the author of the posts. For example, you may want to import all the entries as <code>admin</code>s entries.</p>
<p>If a new user is created by ClassicPress, the password will be set, by default, to "changeme". Quite suggestive, eh? ;)</p>
        <ol id="authors"><form action="?import=wordpress&amp;step=2&amp;id=" method="post"><input type="hidden" name="_wpnonce" value="855ae98911" /><input type="hidden" name="_wp_http_referer" value="wp-test.php" /><li>Current author: <strong>Alex Shiels</strong><br />Create user  <input type="text" value="Alex Shiels" name="user[]" maxlength="30"> <br /> or map to existing<select name="userselect[0]">
EOF;
		// _wpnonce value should be replaced with 'xxx'
		$expected = <<<EOF
<h2>Assign Authors</h2>
<p>To make it easier for you to edit and save the imported posts and drafts, you may want to change the name of the author of the posts. For example, you may want to import all the entries as <code>admin</code>s entries.</p>
<p>If a new user is created by ClassicPress, the password will be set, by default, to "changeme". Quite suggestive, eh? ;)</p>
        <ol id="authors"><form action="?import=wordpress&amp;step=2&amp;id=" method="post"><input type="hidden" name="_wpnonce" value="***" /><input type="hidden" name="_wp_http_referer" value="wp-test.php" /><li>Current author: <strong>Alex Shiels</strong><br />Create user  <input type="text" value="Alex Shiels" name="user[]" maxlength="30"> <br /> or map to existing<select name="userselect[0]">
EOF;
		$this->assertEquals($expected, mask_input_value($in));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/17884
	 */
	function test_setting_nonexistent_arrays() {
		$page = 1;
		$field = 'settings';

		$empty_array[$page][$field] = 'foo';

		// Assertion not strictly needed; we mainly want to show that a notice is not thrown.
		unset( $empty_array[$page]['bar']['baz'] );
		$this->assertFalse( isset( $empty_array[ $page ]['bar']['baz'] ) );
	}

	function test_magic_getter() {
		$basic = new Basic_Object();

		$this->assertEquals( 'bar', $basic->foo );
	}

	function test_subclass_magic_getter() {
		$basic = new Basic_Subclass();

		$this->assertEquals( 'bar', $basic->foo );
	}

	function test_call_method() {
		$basic = new Basic_Object();

		$this->assertEquals( 'maybe', $basic->callMe() );
	}

	function test_subclass_call_method() {
		$basic = new Basic_Subclass();

		$this->assertEquals( 'maybe', $basic->callMe() );
	}

	function test_subclass_isset() {
		$basic = new Basic_Subclass();

		$this->assertTrue( isset( $basic->foo ) );
	}

	function test_subclass_unset() {
		$basic = new Basic_Subclass();

		unset( $basic->foo );

		$this->assertFalse( isset( $basic->foo ) );
	}

	function test_switch_order() {
		$return = $this->_switch_order_helper( 1 );
		$this->assertEquals( 'match', $return );
	}

	function _switch_order_helper( $var ) {
		$return = 'no match';
		switch ( $var ) {
		default:
			break;
		case 1:
			$return = 'match';
			break;
		}

		return $return;
	}
}
