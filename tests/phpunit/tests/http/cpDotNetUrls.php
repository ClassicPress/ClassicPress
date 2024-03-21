<?php

/**
 * @group http
 * @covers ::wp_get_http_headers
 */
class Tests_HTTP_cpDotNetUrls extends WP_UnitTestCase {

	/**
	 * Set up the environment
	 */
	public function set_up() {
		parent::set_up();

		// Hook a mocked HTTP request response.
		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
	}

	/**
	 * Test with a valid filter
	 */
	public function test_http_request_cp_dot_net_urls_valid() {
		add_filter( 'cp_dot_net_urls', array( $this, 'good_url_filter' ), 10, 2 );

		$result = wp_get_http_headers( 'https://directory.classicpress.net' );
		$this->assertSame( $result, 'https://staging-directory.classicpress.net' );

		$result = wp_get_http_headers( 'https://example.com' );
		$this->assertSame( $result, 'https://example.com' );
	}

	/**
	 * Test with an invalid filter
	 * @expectedIncorrectUsage cp_dot_net_urls
	 */
	public function test_http_request_cp_dot_net_dest_url_invalid() {
		add_filter( 'cp_dot_net_urls', array( $this, 'hijack_cp' ), 10, 2 );

		$url = 'https://directory.classicpress.net';
		$result = wp_get_http_headers( $url );
		$this->assertSame( $result, $url );
	}

	/**
	 * Test with an invalid filter
	 * @expectedIncorrectUsage cp_dot_net_urls
	 */
	public function test_http_request_cp_dot_net_src_url_invalid() {
		add_filter( 'cp_dot_net_urls', array( $this, 'hijack_example' ), 10, 2 );

		$url = 'https://example.com';
		$result = wp_get_http_headers( $url );
		$this->assertSame( $result, $url );
	}

	public function good_url_filter( $url, $parsed_args ) {
		return preg_replace( '/directory\.classicpress\.net/', 'staging-directory.classicpress.net', $url );
	}

	public function hijack_cp( $url, $parsed_args ) {
		return preg_replace( '/classicpress\.net/', 'bad.co', $url );
	}

	public function hijack_example( $url, $parsed_args ) {
		return preg_replace( '/example\.com/', 'classicpress.net', $url );
	}

	/**
	 * Mock the HTTP request response
	 *
	 * @param false|array|WP_Error $response    A preemptive return value of an HTTP request. Default false.
	 * @param array                $parsed_args HTTP request arguments.
	 * @param string               $url         The request URL.
	 * @return false|array|WP_Error Response data.
	 */
	public function mock_http_request( $response, $parsed_args, $url ) {
		return array( 'headers' => $url );
	}
}
