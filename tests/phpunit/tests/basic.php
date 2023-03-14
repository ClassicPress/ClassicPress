<?php

/**
 * Test the content in some root directory files.
 *
 * @group basic
 */
class Tests_Basic extends WP_UnitTestCase {

	/**
	 * @coversNothing
	 */
	public function test_license_wp_copyright_years() {
		$license   = file_get_contents( ABSPATH . 'license.txt' );
		$this_year = date( 'Y' );

		// Check WordPress copyright years
		preg_match(
			'#Copyright 2003-(\d+) by the WordPress contributors#',
			$license,
			$matches
		);
		$this->assertNotEmpty( $matches );
		$this->assertSame(
			$this_year,
			trim( $matches[1] ),
			"license.txt's year needs to be updated to $this_year : \"{$matches[0]}\""
		);
	}

	function test_license_cp_copyright_years() {
		$license   = file_get_contents( ABSPATH . 'license.txt' );
		$this_year = date( 'Y' );

		// Check ClassicPress copyright years
		preg_match(
			'#Copyright © 2018-(\d+) ClassicPress and contributors#',
			$license,
			$matches
		);
		$this->assertNotEmpty( $matches );
		$this->assertSame(
			$this_year,
			trim( $matches[1] ),
			"license.txt's year needs to be updated to $this_year : \"{$matches[0]}\""
		);
	}

	function test_package_json() {
		global $cp_version;
		$package_json = file_get_contents( dirname( ABSPATH ) . '/package.json' );
		$package_json = json_decode( $package_json, true );
		if ( isset( $cp_version ) ) {
			$this->assertSame(
				$cp_version,
				$package_json['version'],
				"package.json's version needs to be updated to $cp_version."
			);
		} else {
			error_log( 'FIXME after PR https://core.trac.wordpress.org/ticket/32 is merged' );
		}
		return $package_json;
	}
}
