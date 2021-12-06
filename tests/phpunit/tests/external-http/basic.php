<?php
/**
 * @group external-http
 */
class Tests_External_HTTP_Basic extends WP_UnitTestCase {

	function test_readme_recommended_php_version() {
		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommendations.*PHP</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);
		$this->assertNotEmpty( $matches );

		$response = wp_remote_get(
			'https://secure.php.net/supported-versions.php'
		);
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->fail( 'Could not contact PHP.net to check versions.' );
		}
		$php = wp_remote_retrieve_body( $response );

		preg_match_all(
			'#<tr class="stable">\s*<td>\s*<a [^>]*>\s*([0-9.]*)#s',
			$php,
			$phpmatches
		);

		$this->assertNotEmpty( $phpmatches );

		// TODO: Enable this check once PHP 8.0 compatibility is achieved.
		/*$this->assertContains(
			$matches[1],
			$phpmatches[2],
			"readme.html's Recommended PHP version is too old."
		);*/
	}

	function test_readme_recommended_mysql_version() {
		$readme = file_get_contents( ABSPATH . 'readme.html' );

		preg_match(
			'#Recommendations.*MySQL</a> version <strong>([0-9.]*)#s',
			$readme,
			$matches
		);
		$this->assertNotEmpty( $matches );

		$response = wp_remote_get(
			"https://dev.mysql.com/doc/relnotes/mysql/{$matches[1]}/en/"
		);
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->fail( 'Could not contact dev.mysql.com to check versions.' );
		}
		$mysql = wp_remote_retrieve_body( $response );

		preg_match(
			'#(\d{4}-\d{2}-\d{2}), General Availability#',
			$mysql,
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
