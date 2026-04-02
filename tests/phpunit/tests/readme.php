<?php
/**
 * Validate recommended versions for dependencies referenced in `readme.html`,
 * based on external site support pages.
 *
 * @group external-http
 */
class Tests_Readme extends WP_UnitTestCase {

	/**
	 * @coversNothing
	 */
	public function test_readme_php_version() {
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

	/**
	 * @coversNothing
	 */
	public function test_readme_mysql_version() {
		// This test is designed to only run on develop.
		$this->skipOnAutomatedBranches();

		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommended Setup.*MySQL</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);

		$this->assertNotEmpty( $matches );

		$response_body = json_decode( $this->get_response_body( 'https://endoflife.date/api/mysql.json' ) );
		$eol_date      = '';

		foreach ( $response_body as $version ) {
			if ( $version->cycle === $matches[1] && false !== $version->eol ) {
				$eol_date = $version->eol;
				break;
			}
		}
		$this->assertNotEmpty( $eol_date );

		$mysql_eol    = gmdate( 'Y-m-d', strtotime( $eol_date ) );
		$current_date = gmdate( 'Y-m-d' );

		$this->assertLessThan(
			$mysql_eol,
			$current_date,
			"readme.html's Recommended MySQL version is too old. Remember to update the WordPress.org Requirements page, too."
		);
	}

	/**
	 * @coversNothing
	 */
	public function test_readme_mariadb_version() {
		// This test is designed to only run on develop.
		$this->skipOnAutomatedBranches();

		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommended Setup.*MariaDB</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);

		$this->assertNotEmpty( $matches );

		$response_body = json_decode( $this->get_response_body( 'https://endoflife.date/api/mariadb.json' ) );
		$eol_date      = '';

		foreach ( $response_body as $version ) {
			if ( $version->cycle === $matches[1] ) {
				$eol_date = $version->eol;
				break;
			}
		}
		$this->assertNotEmpty( $eol_date );

		// Per https://mariadb.org/about/#maintenance-policy, MariaDB releases are supported for 5 years.
		$mariadb_eol  = gmdate( 'Y-m-d', strtotime(  $eol_date ) );
		$current_date = gmdate( 'Y-m-d' );

		$this->assertLessThan(
			$mariadb_eol,
			$current_date,
			"readme.html's Recommended MariaDB version is too old. Remember to update the WordPress.org Requirements page, too."
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
