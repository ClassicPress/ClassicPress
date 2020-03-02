<?php

/**
 * A set of unit tests for the functions that read and write .htaccess files.
 *
 * @group rewrite
 */
class Tests_Htaccess_Markers extends WP_UnitTestCase {
	var $tmpfile;

	public function setUp() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		$this->tmpfile = wp_tempnam( 'htaccess' );
		parent::setUp();
	}

	public function tearDown() {
		@unlink( $this->tmpfile );
		parent::tearDown();
	}

	public function write_test_contents( $contents ) {
		file_put_contents( $this->tmpfile, trim( $contents ) . "\n" );
	}

	public function assertTestFileLines( $lines ) {
		$expected = implode( "\n", $lines );
		$actual = file_get_contents( $this->tmpfile );
		if ( $expected !== $actual ) {
			// Debug helper.
			error_log( json_encode( compact( 'expected', 'actual' ) ) );
		}
		$this->assertEquals( $expected, $actual );
	}

	public function test_extract_wp_markers() {
		$this->write_test_contents( '
prefix lines

# BEGIN WordPress
WP rule 1
WP rule 2
# END WordPress

suffix lines
		' );

		$this->assertEquals(
			[ 'WP rule 1', 'WP rule 2' ],
			extract_from_markers( $this->tmpfile, 'WordPress' )
		);

		$this->assertEquals(
			[ 'WP rule 1', 'WP rule 2' ],
			extract_from_markers( $this->tmpfile, '(Word|Classic)Press', true )
		);
	}

	public function test_extract_cp_markers() {
		$this->write_test_contents( '
prefix lines

# BEGIN ClassicPress
CP rule 1
CP rule 2
# END ClassicPress

suffix lines
		' );

		$this->assertEquals(
			[],
			extract_from_markers( $this->tmpfile, 'WordPress' )
		);

		$this->assertEquals(
			[ 'CP rule 1', 'CP rule 2' ],
			extract_from_markers( $this->tmpfile, 'ClassicPress' )
		);

		$this->assertEquals(
			[ 'CP rule 1', 'CP rule 2' ],
			extract_from_markers( $this->tmpfile, '(Word|Classic)Press', true )
		);
	}

	public function test_extract_marker_with_slash() {
		$this->write_test_contents( '
prefix lines

# BEGIN test/marker
test rule 1
test rule 2
# END test/marker

suffix lines
		' );

		$this->assertEquals(
			[ 'test rule 1', 'test rule 2' ],
			extract_from_markers( $this->tmpfile, 'test/marker' )
		);

		$this->assertEquals(
			[ 'test rule 1', 'test rule 2' ],
			extract_from_markers( $this->tmpfile, 'test\\/marker', true )
		);
	}

	public function test_extract_invalid_regex_marker() {
		$this->setExpectedException(
			'PHPUnit_Framework_Error',
			"preg_match(): Unknown modifier 'a'"
		);

		extract_from_markers( $this->tmpfile, 'test/marker', true );
	}

	public function test_update_existing_rules_string_marker() {
		$this->write_test_contents( '
prefix lines

# BEGIN WordPress
WP rule 1
WP rule 2
# END WordPress

suffix lines
		' );

		$this->assertTrue( insert_with_markers(
			$this->tmpfile,
			'WordPress',
			[ 'WP rule 1', 'WP rule 2', 'WP rule 3' ]
		) );

		$this->assertTestFileLines( [
			'prefix lines',
			'',
			'# BEGIN WordPress',
			'WP rule 1',
			'WP rule 2',
			'WP rule 3',
			'# END WordPress',
			'',
			'suffix lines',
			'',
		] );
	}

	public function test_update_existing_rules_regex_marker_1() {
		$this->write_test_contents( '
prefix lines

# BEGIN WordPress
WP rule 1
# END WordPress

suffix lines
		' );

		$this->assertTrue( insert_with_markers(
			$this->tmpfile,
			'(Word|Classic)Press',
			[ 'CP rule 1', 'CP rule 2' ],
			true,
			'ClassicPress'
		) );

		$this->assertTestFileLines( [
			'prefix lines',
			'',
			'# BEGIN ClassicPress',
			'CP rule 1',
			'CP rule 2',
			'# END ClassicPress',
			'',
			'suffix lines',
			'',
		] );
	}

	public function test_update_existing_rules_regex_marker_2() {
		$this->write_test_contents( '
prefix lines

# BEGIN ClassicPress
CP rule 1
# END ClassicPress

suffix lines
		' );

		$this->assertTrue( insert_with_markers(
			$this->tmpfile,
			'(Word|Classic)Press',
			[ 'CP rule 1', 'CP rule 2' ],
			true,
			'ClassicPress'
		) );

		$this->assertTestFileLines( [
			'prefix lines',
			'',
			'# BEGIN ClassicPress',
			'CP rule 1',
			'CP rule 2',
			'# END ClassicPress',
			'',
			'suffix lines',
			'',
		] );
	}

	public function test_update_existing_rules_regex_marker_3() {
		// Sites that migrated from WordPress to ClassicPress 1.0.0-rc1 or
		// earlier may find themselves in this situation.  The resulting
		// behavior is not ideal, but attempting to remove duplicate rules
		// would probably cause other issues.

		$this->write_test_contents( '
prefix lines

# BEGIN WordPress
WP rule 1
# END WordPress

other lines

# BEGIN ClassicPress
CP rule 1
# END ClassicPress
		' );

		for ( $i = 0; $i < 2; $i++ ) {
			$this->assertTrue( insert_with_markers(
				$this->tmpfile,
				'(Word|Classic)Press',
				[ 'CP rule 1', 'CP rule 2' ],
				true,
				'ClassicPress'
			) );

			$this->assertTestFileLines( [
				'prefix lines',
				'',
				'# BEGIN ClassicPress',
				'CP rule 1',
				'CP rule 2',
				'# END ClassicPress',
				'',
				'other lines',
				'',
				'# BEGIN ClassicPress',
				'CP rule 1',
				'# END ClassicPress',
				'',
			] );
		}
	}

	public function test_add_new_rules_string_marker() {
		$this->write_test_contents( '
existing line
		' );

		$this->assertTrue( insert_with_markers(
			$this->tmpfile,
			'testmarker',
			[ 'rule 1', 'rule 2' ]
		) );

		$this->assertTestFileLines( [
			'existing line',
			'',
			'# BEGIN testmarker',
			'rule 1',
			'rule 2',
			'# END testmarker',
		] );
	}

	public function test_add_new_rules_regex_marker() {
		$this->write_test_contents( '
existing line
		' );

		$this->assertTrue( insert_with_markers(
			$this->tmpfile,
			'(Word|Classic)Press',
			[ 'rule 1', 'rule 2' ],
			true,
			'ClassicPress'
		) );

		$this->assertTestFileLines( [
			'existing line',
			'',
			'# BEGIN ClassicPress',
			'rule 1',
			'rule 2',
			'# END ClassicPress',
		] );
	}

	public function test_update_invalid_params() {
		$this->assertFalse( insert_with_markers(
			$this->tmpfile,
			'testmarker',
			[],
			true
			// missing '$marker_out' string
		) );
	}
}
