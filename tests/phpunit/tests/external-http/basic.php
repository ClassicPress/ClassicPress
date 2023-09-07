<?php
/**
 * @group external-http
 */
class Tests_External_HTTP_Basic extends WP_UnitTestCase {

	public function test_readme_recommended_php_version() {
		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommendations.*PHP</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);
		$this->assertNotEmpty( $matches );

		$version_info = __DIR__ . '/../../../../supported-versions.html';
		if ( file_exists( $version_info ) ) {
			$php = file_get_contents( $version_info );
		} else {
			$this->markTestSkipped( 'Could not locate PHP.net clone.' );
		}

		preg_match_all(
			'#<tr class="stable">\s*<td>\s*<a [^>]*>\s*([0-9.]*)#s',
			$php,
			$phpmatches
		);

		$this->assertNotEmpty( $phpmatches );

		// TODO: Enable this check once PHP 8.0 compatibility is achieved.
		/*$this->assertContains(
			$matches[1],
			$phpmatches[1],
			"readme.html's Recommended PHP version is too old."
		);*/
	}

	public function test_readme_recommended_mysql_version() {
		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommendations.*MySQL</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);

		$this->assertNotEmpty( $matches );

		$response = wp_remote_get( "https://dev.mysql.com/doc/relnotes/mysql/{$matches[1]}/en/" );

		$this->skipTestOnTimeout( $response );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			$error_message = sprintf(
				'Could not contact dev.MySQL.com to check versions. Response code: %s. Response body: %s',
				$response_code,
				$response_body
			);

			if ( 503 === $response_code ) {
				$this->markTestSkipped( $error_message );
			}

			$this->fail( $error_message );
		}

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
}
