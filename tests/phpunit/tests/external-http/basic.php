<?php
/**
 * @group external-http
 */
class Tests_External_HTTP_Basic extends WP_UnitTestCase {

	public function test_readme_recommended_php_version() {
		// This test is designed to only run on develop.
		$this->skipOnAutomatedBranches();

		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommended Setup.*PHP</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);
		$this->assertNotEmpty( $matches );

		$response_body = $this->get_response_body( 'https://www.php.net/supported-versions.php' );

		preg_match_all(
			'#<tr class="stable">\s*<td>\s*<a [^>]*>\s*([0-9.]*)#s',
			$response_body,
			$phpmatches
		);

		$this->assertNotEmpty( $phpmatches );

		$this->assertContains(
			$matches[1],
			$phpmatches[1],
			"readme.html's Recommended PHP version is too old."
		);
	}

	public function test_readme_recommended_mysql_version() {
		// This test is designed to only run on develop.
		$this->skipOnAutomatedBranches();

		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommended Setup.*MySQL</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);

		$this->assertNotEmpty( $matches );

		$response_body = $this->get_response_body( "https://dev.mysql.com/doc/relnotes/mysql/{$matches[1]}/en/" );

		preg_match(
			'#(\d{4}-\d{2}-\d{2}), General Availability#',
			$response_body,
			$mysqlmatches
		);
		$this->assertNotEmpty( $mysqlmatches );

		// Per https://www.mysql.com/support/, Oracle actively supports MySQL
		// releases for 5 years from GA release
		$mysql_eol = strtotime( $mysqlmatches[1] . ' +5 years' );

		$this->assertLessThan(
			$mysql_eol,
			time(),
			"readme.html's Recommended MySQL version is too old."
		);
	}

	/**
	 * Helper function to retrieve the response body or skip the test on HTTP timeout.
	 *
	 * @param string $url The URL to retrieve the response from.
	 * @return string The response body.
	 */
	public function get_response_body( $url ) {
		$response = wp_remote_get( $url );

		$this->skipTestOnTimeout( $response );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			$parsed_url = parse_url( $url );

			$error_message = sprintf(
				'Could not contact %1$s to check versions. Response code: %2$s. Response body: %3$s',
				$parsed_url['host'],
				$response_code,
				$response_body
			);

			if ( 503 === $response_code ) {
				$this->markTestSkipped( $error_message );
			}

			$this->fail( $error_message );
		}

		return $response_body;
	}
}
