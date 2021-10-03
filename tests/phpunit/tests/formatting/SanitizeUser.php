<?php

/**
 * @group formatting
 */
class Tests_Formatting_SanitizeUser extends WP_UnitTestCase {
	function test_strips_html() {
		$input = "Captain <strong>Awesome</strong>";
		$expected = is_multisite() ? 'captain awesome' : 'Captain Awesome';
<<<<<<< HEAD
		$this->assertEquals($expected, sanitize_user($input));
=======
		$this->assertSame( $expected, sanitize_user( $input ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	public function test_strips_encoded_ampersand() {
		$expected = 'ATT';

		// Multisite forces user logins to lowercase.
		if ( is_multisite() ) {
			$expected = strtolower( $expected );
		}

<<<<<<< HEAD
		$this->assertEquals( $expected, sanitize_user( "AT&amp;T" ) );
=======
		$this->assertSame( $expected, sanitize_user( 'AT&amp;T' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	public function test_strips_encoded_ampersand_when_followed_by_semicolon() {
		$expected = 'ATT Test;';

		// Multisite forces user logins to lowercase.
		if ( is_multisite() ) {
			$expected = strtolower( $expected );
		}

<<<<<<< HEAD
		$this->assertEquals( $expected, sanitize_user( "AT&amp;T Test;" ) );
=======
		$this->assertSame( $expected, sanitize_user( 'AT&amp;T Test;' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_strips_percent_encoded_octets() {
		$expected = is_multisite() ? 'franois' : 'Franois';
<<<<<<< HEAD
		$this->assertEquals( $expected, sanitize_user( "Fran%c3%a7ois" ) );
	}
	function test_optional_strict_mode_reduces_to_safe_ascii_subset() {
		$this->assertEquals("abc", sanitize_user("()~ab~ˆcˆ!", true));
=======
		$this->assertSame( $expected, sanitize_user( 'Fran%c3%a7ois' ) );
	}
	function test_optional_strict_mode_reduces_to_safe_ascii_subset() {
		$this->assertSame( 'abc', sanitize_user( '()~ab~ˆcˆ!', true ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}
}
