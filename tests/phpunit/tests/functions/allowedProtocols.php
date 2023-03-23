<?php

/**
 * @group formatting
 * @group functions.php
 * @covers ::wp_allowed_protocols
 */
class Tests_Functions_AllowedProtocols extends WP_UnitTestCase {

	/**
	 * @ticket 19354
	 */
	public function test_data_is_not_an_allowed_protocol() {
		$this->assertNotContains( 'data', wp_allowed_protocols() );
	}

	public function test_allowed_protocol_has_an_example() {
		$example_protocols = array();
		foreach ( $this->data_example_urls() as $example ) {
			$example_protocols[] = $example[0];
		}
		$this->assertSameSets( $example_protocols, wp_allowed_protocols() );
	}

	/**
	 * @depends test_allowed_protocol_has_an_example
	 * @dataProvider data_example_urls
	 *
	 * @param string The scheme.
	 * @param string Example URL.
	 */
	public function test_allowed_protocols( $protocol, $url ) {
		$this->assertSame( $url, esc_url( $url, $protocol ) );
		$this->assertSame( $url, esc_url( $url, wp_allowed_protocols() ) );
	}

	/**
	 * @link http://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
	 */
	public function data_example_urls() {
		return array(
			array( 'http', 'http://example.com' ),                                 // RFC7230
			array( 'https', 'https://example.com' ),                               // RFC7230
			array( 'ftp', 'ftp://example.com' ),                                   // RFC1738
			array( 'ftps', 'ftps://example.com' ),
			array( 'mailto', 'mailto://someone@example.com' ),                     // RFC6068
			array( 'news', 'news://news.server.example/example.group.this' ),      // RFC5538
			array( 'irc', 'irc://example.com/wordpress' ),
			array( 'irc6', 'irc6://example.com/wordpress' ),
			array( 'ircs', 'ircs://example.com/wordpress' ),
			array( 'gopher', 'gopher://example.com/7a_gopher_selector%09foobar' ), // RFC4266
			array( 'nntp', 'nntp://news.server.example/example.group.this' ),      // RFC5538
			array( 'feed', 'feed://example.com/rss.xml' ),
			array( 'telnet', 'telnet://user:password@example.com:80/' ),           // RFC4248
			array( 'mms', 'mms://example.com:80/path' ),
			array( 'rtsp', 'rtsp://media.example.com:554/wordpress/audiotrack' ),  // RFC2326
			array( 'svn', 'svn://core.svn.wordpress.org/' ),
			array( 'tel', 'tel:+1-234-567-8910' ),                                 // RFC3966
			array( 'sms', 'sms:+1-234-567-8910' ),                                 // RFC3966
			array( 'fax', 'fax:+123.456.78910' ),                                  // RFC2806/RFC3966
			array( 'xmpp', 'xmpp://guest@example.com' ),                           // RFC5122
			array( 'webcal', 'webcal://example.com/calendar.ics' ),
			array( 'urn', 'urn:uuid:6e8bc430-9c3a-11d9-9669-0800200c9a66' ),       // RFC2141
		);
	}
}
