<?php

/**
 * @group pluggable
 * @group formatting
 * @group redirect
 */
class Tests_Formatting_Redirect extends WP_UnitTestCase {
	function set_up() {
		parent::set_up();
		add_filter( 'home_url', array( $this, 'home_url' ) );
	}

	function tear_down() {
		remove_filter( 'home_url', array( $this, 'home_url' ) );
	}

	function home_url() {
		return 'http://example.com/';
	}

	function test_wp_sanitize_redirect() {
		$this->assertSame( 'http://example.com/watchthelinefeedgo', wp_sanitize_redirect( 'http://example.com/watchthelinefeed%0Ago' ) );
		$this->assertSame( 'http://example.com/watchthelinefeedgo', wp_sanitize_redirect( 'http://example.com/watchthelinefeed%0ago' ) );
		$this->assertSame( 'http://example.com/watchthecarriagereturngo', wp_sanitize_redirect( 'http://example.com/watchthecarriagereturn%0Dgo' ) );
		$this->assertSame( 'http://example.com/watchthecarriagereturngo', wp_sanitize_redirect( 'http://example.com/watchthecarriagereturn%0dgo' ) );
		$this->assertSame( 'http://example.com/watchtheallowedcharacters-~+_.?#=&;,/:%!*stay', wp_sanitize_redirect( 'http://example.com/watchtheallowedcharacters-~+_.?#=&;,/:%!*stay' ) );
		$this->assertSame( 'http://example.com/watchtheutf8convert%F0%9D%8C%86', wp_sanitize_redirect( "http://example.com/watchtheutf8convert\xf0\x9d\x8c\x86" ) );
		// Nesting checks.
		$this->assertSame( 'http://example.com/watchthecarriagereturngo', wp_sanitize_redirect( 'http://example.com/watchthecarriagereturn%0%0ddgo' ) );
		$this->assertSame( 'http://example.com/watchthecarriagereturngo', wp_sanitize_redirect( 'http://example.com/watchthecarriagereturn%0%0DDgo' ) );
		$this->assertSame( 'http://example.com/whyisthisintheurl/?param[1]=foo', wp_sanitize_redirect( 'http://example.com/whyisthisintheurl/?param[1]=foo' ) );
		$this->assertSame( 'http://[2606:2800:220:6d:26bf:1447:aa7]/', wp_sanitize_redirect( 'http://[2606:2800:220:6d:26bf:1447:aa7]/' ) );
		$this->assertSame( 'http://example.com/search.php?search=(amistillhere)', wp_sanitize_redirect( 'http://example.com/search.php?search=(amistillhere)' ) );
		$this->assertSame( 'http://example.com/@username', wp_sanitize_redirect( 'http://example.com/@username' ) );
	}

	/**
	 * @dataProvider valid_url_provider
	 */
	function test_wp_validate_redirect_valid_url( $url, $expected ) {
		$this->assertSame( $expected, wp_validate_redirect( $url ) );
	}

	/**
	 * @dataProvider invalid_url_provider
	 */
	function test_wp_validate_redirect_invalid_url( $url ) {
		$this->assertEquals( false, wp_validate_redirect( $url, false ) );
	}

	function valid_url_provider() {
		return array(
			array( 'http://example.com', 'http://example.com' ),
			array( 'http://example.com/', 'http://example.com/' ),
			array( 'https://example.com/', 'https://example.com/' ),
			array( '//example.com', 'http://example.com' ),
			array( '//example.com/', 'http://example.com/' ),
			array( 'http://example.com/?foo=http://example.com/', 'http://example.com/?foo=http://example.com/' ),
			array( 'http://user@example.com/', 'http://user@example.com/' ),
			array( 'http://user:@example.com/', 'http://user:@example.com/' ),
			array( 'http://user:pass@example.com/', 'http://user:pass@example.com/' ),
			array( " \t\n\r\0\x08\x0Bhttp://example.com", 'http://example.com' ),
			array( " \t\n\r\0\x08\x0B//example.com", 'http://example.com' ),
		);
	}

	function invalid_url_provider() {
		return array(
			// parse_url() fails
			array( '' ),
			array( 'http://:' ),

			// non-safelisted domain
			array( 'http://non-safelisted.example/' ),

			// non-safelisted domain (leading whitespace)
			array( " \t\n\r\0\x08\x0Bhttp://non-safelisted.example.com" ),
			array( " \t\n\r\0\x08\x0B//non-safelisted.example.com" ),

			// unsupported schemes
			array( 'data:text/plain;charset=utf-8,Hello%20World!' ),
			array( 'file:///etc/passwd' ),
			array( 'ftp://example.com/' ),

			// malformed input
			array( 'http:example.com' ),
			array( 'http:80' ),
			array( 'http://example.com:1234:5678/' ),
			array( 'http://user:pa:ss@example.com/' ),

			array( 'http://user@@example.com' ),
			array( 'http://user@:example.com' ),
			array( 'http://user?@example.com' ),
			array( 'http://user@?example.com' ),
			array( 'http://user#@example.com' ),
			array( 'http://user@#example.com' ),

			array( 'http://user@@example.com/' ),
			array( 'http://user@:example.com/' ),
			array( 'http://user?@example.com/' ),
			array( 'http://user@?example.com/' ),
			array( 'http://user#@example.com/' ),
			array( 'http://user@#example.com/' ),

			array( 'http://user:pass@@example.com' ),
			array( 'http://user:pass@:example.com' ),
			array( 'http://user:pass?@example.com' ),
			array( 'http://user:pass@?example.com' ),
			array( 'http://user:pass#@example.com' ),
			array( 'http://user:pass@#example.com' ),

			array( 'http://user:pass@@example.com/' ),
			array( 'http://user:pass@:example.com/' ),
			array( 'http://user:pass?@example.com/' ),
			array( 'http://user:pass@?example.com/' ),
			array( 'http://user:pass#@example.com/' ),
			array( 'http://user:pass@#example.com/' ),

			array( 'http://user.pass@@example.com' ),
			array( 'http://user.pass@:example.com' ),
			array( 'http://user.pass?@example.com' ),
			array( 'http://user.pass@?example.com' ),
			array( 'http://user.pass#@example.com' ),
			array( 'http://user.pass@#example.com' ),

			array( 'http://user.pass@@example.com/' ),
			array( 'http://user.pass@:example.com/' ),
			array( 'http://user.pass?@example.com/' ),
			array( 'http://user.pass@?example.com/' ),
			array( 'http://user.pass#@example.com/' ),
			array( 'http://user.pass@#example.com/' ),
		);
	}
}
