<?php

/**
 * @group formatting
 *
 * @covers ::sanitize_file_name
 */
class Tests_Formatting_SanitizeFileName extends WP_UnitTestCase {
	public function test_munges_extensions() {
		// r17990
		$file_name = sanitize_file_name( 'test.phtml.txt' );
		$this->assertSame( 'test.phtml_.txt', $file_name );
	}

	public function test_removes_special_chars() {
		$special_chars = array( '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', '’', '«', '»', '”', '“', chr( 0 ) );
		$string        = 'test';
		foreach ( $special_chars as $char ) {
			$string .= $char;
		}
		$string .= 'test';
		$this->assertSame( 'testtest', sanitize_file_name( $string ) );
	}

	/**
	 * @ticket 22363
	 */
	public function test_removes_accents() {
		$in  = 'àáâãäåæçèéêëìíîïñòóôõöøùúûüýÿ';
		$out = 'aaaaaaaeceeeeiiiinoooooouuuuyy';
		$this->assertSame( $out, sanitize_file_name( $in ) );
	}

	/**
	 * Test that spaces are correctly replaced with dashes.
	 *
	 * @ticket 16330
	 */
	public function test_replaces_spaces() {
		$urls = array(
			'unencoded space.png'  => 'unencoded-space.png',
			'encoded-space.jpg'    => 'encoded-space.jpg',
			'plus+space.jpg'       => 'plusspace.jpg',
			'multi %20 +space.png' => 'multi-20-space.png',
		);

		foreach ( $urls as $test => $expected ) {
			$this->assertSame( $expected, sanitize_file_name( $test ) );
		}
	}

	public function test_replaces_any_number_of_hyphens_with_one_hyphen() {
		$this->assertSame( 'a-t-t', sanitize_file_name( 'a----t----t' ) );
	}

	public function test_trims_trailing_hyphens() {
		$this->assertSame( 'a-t-t', sanitize_file_name( 'a----t----t----' ) );
	}

	public function test_replaces_any_amount_of_whitespace_with_one_hyphen() {
		$this->assertSame( 'a-t', sanitize_file_name( 'a          t' ) );
		$this->assertSame( 'a-t', sanitize_file_name( "a    \n\n\nt" ) );
	}

	/**
	 * @ticket 16226
	 */
	public function test_replaces_percent_sign() {
		$this->assertSame( 'a22b.jpg', sanitize_file_name( 'a%22b.jpg' ) );
	}

	public function test_replaces_unnamed_file_extensions() {
		// Test filenames with both supported and unsupported extensions.
		$this->assertSame( 'unnamed-file.exe', sanitize_file_name( '_.exe' ) );
		$this->assertSame( 'unnamed-file.jpg', sanitize_file_name( '_.jpg' ) );
	}

	public function test_replaces_unnamed_file_extensionless() {
		// Test a filenames that becomes extensionless.
		$this->assertSame( 'no-extension', sanitize_file_name( '_.no-extension' ) );
	}

	/**
	 * @dataProvider data_wp_filenames
	 */
	public function test_replaces_invalid_utf8_characters( $input, $expected ) {
		$this->assertSame( $expected, sanitize_file_name( $input ) );
	}

	public function data_wp_filenames() {
		return array(
			array( urldecode( '%B1myfile.png' ), 'myfile.png' ),
			array( urldecode( '%B1myfile' ), 'myfile' ),
			array( 'demo bar.png', 'demo-bar.png' ),
			array( 'demo' . json_decode( '"\u00a0"' ) . 'bar.png', 'demo-bar.png' ),
		);
	}

	/**
	 * Tests that sanitize_file_name() replaces consecutive periods
	 * with a single period.
	 *
	 * @ticket 57242
	 *
	 * @dataProvider data_sanitize_file_name_should_replace_consecutive_periods_with_a_single_period
	 *
	 * @param string $filename A filename with consecutive periods.
	 * @param string $expected The expected filename after sanitization.
	 */
	public function test_sanitize_file_name_should_replace_consecutive_periods_with_a_single_period( $filename, $expected ) {
		$this->assertSame( $expected, sanitize_file_name( $filename ) );
	}

	/**
	 * Data provider for test_sanitize_file_name_should_replace_consecutive_periods_with_a_single_period().
	 *
	 * @return array[]
	 */
	public function data_sanitize_file_name_should_replace_consecutive_periods_with_a_single_period() {
		return array(
			'consecutive periods at the start'         => array(
				'filename' => '...filename.png',
				'expected' => 'filename.png',
			),
			'consecutive periods in the middle'        => array(
				'filename' => 'file.......name.png',
				'expected' => 'file.name_.png',
			),
			'consecutive periods before the extension' => array(
				'filename' => 'filename....png',
				'expected' => 'filename.png',
			),
			'consecutive periods after the extension'  => array(
				'filename' => 'filename.png...',
				'expected' => 'filename.png',
			),
			'consecutive periods at the start, middle, before, after the extension' => array(
				'filename' => '.....file....name...png......',
				'expected' => 'file.name_.png',
			),
			'consecutive periods and no extension'     => array(
				'filename' => 'filename...',
				'expected' => 'filename',
			),
		);
	}
}
