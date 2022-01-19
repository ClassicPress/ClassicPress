<?php

/**
 * @group formatting
 * @group datetime
 */
class Tests_Formatting_Date extends WP_UnitTestCase {

	/**
	 * Unpatched, this test passes only when Europe/London is not observing DST.
	 *
	 * @see https://core.trac.wordpress.org/ticket/20328
	 */
	function test_get_date_from_gmt_outside_of_dst() {
		update_option( 'timezone_string', 'Europe/London' );
		$gmt = $local = '2012-01-01 12:34:56';
		$this->assertEquals( $local, get_date_from_gmt( $gmt ) );
	}

	/**
	 * Unpatched, this test passes only when Europe/London is observing DST.
	 *
	 * @see https://core.trac.wordpress.org/ticket/20328
	 */
	function test_get_date_from_gmt_during_dst() {
		update_option( 'timezone_string', 'Europe/London' );
		$gmt   = '2012-06-01 12:34:56';
		$local = '2012-06-01 13:34:56';
		$this->assertEquals( $local, get_date_from_gmt( $gmt ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/20328
	 */
	function test_get_gmt_from_date_outside_of_dst() {
		update_option( 'timezone_string', 'Europe/London' );
		$local = $gmt = '2012-01-01 12:34:56';
		$this->assertEquals( $gmt, get_gmt_from_date( $local ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/20328
	 */
	function test_get_gmt_from_date_during_dst() {
		update_option( 'timezone_string', 'Europe/London' );
		$local = '2012-06-01 12:34:56';
		$gmt = '2012-06-01 11:34:56';
		$this->assertEquals( $gmt, get_gmt_from_date( $local ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34279
	 */
	function test_get_date_and_time_from_gmt_no_timezone() {
		$gmt = $local = '2012-01-01 12:34:56';
		$this->assertEquals( $gmt, get_date_from_gmt( $local ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34279
	 */
	function test_get_gmt_from_date_no_timezone() {
		$gmt = '2012-12-01 00:00:00';
		$date = '2012-12-01';
		$this->assertEquals( $gmt, get_gmt_from_date( $date ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34279
	 */
	function test_get_gmt_from_date_short_date() {
		update_option( 'timezone_string', 'Europe/London' );
		$local = '2012-12-01';
		$gmt = '2012-12-01 00:00:00';
		$this->assertEquals( $gmt, get_gmt_from_date( $local ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34279
	 */
	function test_get_gmt_from_date_string_date() {
		update_option( 'timezone_string', 'Europe/London' );
		$local = 'now';
		$gmt = gmdate( 'Y-m-d H:i:s' );
		$this->assertEquals( strtotime( $gmt ), strtotime( get_gmt_from_date( $local ) ), 'The dates should be equal', 2 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34279
	 */
	function test_get_gmt_from_date_string_date_no_timezone() {
		$local = 'now';
		$gmt = gmdate( 'Y-m-d H:i:s' );
		$this->assertEquals( strtotime( $gmt ), strtotime( get_gmt_from_date( $local ) ), 'The dates should be equal', 2 );
	}

	/**
	 * @ticket 31809
	 *
	 * @dataProvider timezone_provider
	 */
	public function test_gmt_from_date_correct_time( $timezone_string, $gmt_offset ) {
		update_option( 'timezone_string', $timezone_string );
		update_option( 'gmt_offset', $gmt_offset );

		$local       = new DateTimeImmutable( 'now', wp_timezone() );
		$utc         = $local->setTimezone( new DateTimeZone( 'UTC' ) );
		$mysql_local = $local->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $utc->format( DATE_RFC3339 ), get_gmt_from_date( $mysql_local, DATE_RFC3339 ) );
	}

	/**
	 * @ticket 31809
	 *
	 * @dataProvider timezone_provider
	 */
	public function test_date_from_gmt_correct_time( $timezone_string, $gmt_offset ) {
		update_option( 'timezone_string', $timezone_string );
		update_option( 'gmt_offset', $gmt_offset );

		$local     = new DateTimeImmutable( 'now', wp_timezone() );
		$utc       = $local->setTimezone( new DateTimeZone( 'UTC' ) );
		$mysql_utc = $utc->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $local->format( DATE_RFC3339 ), get_date_from_gmt( $mysql_utc, DATE_RFC3339 ) );
	}

	/**
	 * @ticket 31809
	 *
	 * @dataProvider timezone_provider
	 */
	public function test_is8601_to_datetime_correct_time( $timezone_string, $gmt_offset ) {
		update_option( 'timezone_string', $timezone_string );
		update_option( 'gmt_offset', $gmt_offset );

		$format       = 'Ymd\TH:i:sO';
		$format_no_tz = 'Ymd\TH:i:s';

		$local = new DateTimeImmutable( 'now', wp_timezone() );
		$utc   = $local->setTimezone( new DateTimeZone( 'UTC' ) );

		$this->assertEquals(
			$local->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $local->format( $format ) ),
			'Local time from local time.'
		);
		$this->assertEquals(
			$utc->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $local->format( $format ), 'gmt' ),
			'UTC time from local time.'
		);

		$this->assertEquals(
			$local->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $local->format( $format_no_tz ) ),
			'Local time from local time w/o timezone.'
		);
		$this->assertEquals(
			$utc->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $local->format( $format_no_tz ), 'gmt' ),
			'UTC time from local time w/o timezone.'
		);

		$this->assertEquals(
			$local->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $utc->format( $format ) ),
			'Local time from UTC time.'
		);
		$this->assertEquals(
			$utc->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $utc->format( $format ), 'gmt' ),
			'UTC time from UTC time.'
		);

		$this->assertEquals(
			$local->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $utc->format( $format_no_tz ) . 'Z' ),
			'Local time from UTC w/ Z timezone.'
		);
		$this->assertEquals(
			$utc->format( 'Y-m-d H:i:s' ),
			iso8601_to_datetime( $utc->format( $format_no_tz ) . 'Z', 'gmt' ),
			'UTC time from UTC w/ Z timezone.'
		);
	}

	/**
	 * Data provider to test different timezone modes.
	 *
	 * @return array
	 */
	public function timezone_provider() {
		return array(
			array(
				'timezone_string' => 'Europe/Kiev',
				'gmt_offset'      => 3,
			),
			array(
				'timezone_string' => '',
				'gmt_offset'      => 3,
			),
		);
	}
}
