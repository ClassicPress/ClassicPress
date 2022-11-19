<?php

/**
 * @group date
 * @group datetime
 */
class Tests_Date_mysql2date extends WP_UnitTestCase {

	function tear_down() {
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( 'UTC' );

		parent::tear_down();
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28310
	 */
	function test_mysql2date_returns_false_with_no_date() {
		$this->assertFalse( mysql2date( 'F j, Y H:i:s', '' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28310
	 */
	function test_mysql2date_returns_gmt_or_unix_timestamp() {
		$this->assertEquals( '441013392', mysql2date( 'G', '1983-12-23 07:43:12' ) );
		$this->assertEquals( '441013392', mysql2date( 'U', '1983-12-23 07:43:12' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
<<<<<<< HEAD
	function test_mysql2date_should_format_time() {
		$timezone = 'Europe/Kiev';
=======
	public function test_mysql2date_should_format_time() {
		$timezone = 'Europe/Helsinki';
>>>>>>> 8127aaed05 (Tests: Replace the timezone used in date/time tests.)
		update_option( 'timezone_string', $timezone );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$rfc3339  = $datetime->format( DATE_RFC3339 );
		$mysql    = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $rfc3339, mysql2date( DATE_RFC3339, $mysql ) );
		$this->assertEquals( $rfc3339, mysql2date( DATE_RFC3339, $mysql, false ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
<<<<<<< HEAD
	function test_mysql2date_should_format_time_with_changed_time_zone() {
		$timezone = 'Europe/Kiev';
=======
	public function test_mysql2date_should_format_time_with_changed_time_zone() {
		$timezone = 'Europe/Helsinki';
>>>>>>> 8127aaed05 (Tests: Replace the timezone used in date/time tests.)
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $timezone );
		update_option( 'timezone_string', $timezone );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$rfc3339  = $datetime->format( DATE_RFC3339 );
		$mysql    = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $rfc3339, mysql2date( DATE_RFC3339, $mysql ) );
		$this->assertEquals( $rfc3339, mysql2date( DATE_RFC3339, $mysql, false ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
<<<<<<< HEAD
	function test_mysql2date_should_return_wp_timestamp() {
		$timezone = 'Europe/Kiev';
=======
	public function test_mysql2date_should_return_wp_timestamp() {
		$timezone = 'Europe/Helsinki';
>>>>>>> 8127aaed05 (Tests: Replace the timezone used in date/time tests.)
		update_option( 'timezone_string', $timezone );
		$datetime     = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$wp_timestamp = $datetime->getTimestamp() + $datetime->getOffset();
		$mysql        = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $wp_timestamp, mysql2date( 'U', $mysql, false ) );
		$this->assertEquals( $wp_timestamp, mysql2date( 'G', $mysql, false ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
<<<<<<< HEAD
	function test_mysql2date_should_return_unix_timestamp_for_gmt_time() {
		$timezone = 'Europe/Kiev';
=======
	public function test_mysql2date_should_return_unix_timestamp_for_gmt_time() {
		$timezone = 'Europe/Helsinki';
>>>>>>> 8127aaed05 (Tests: Replace the timezone used in date/time tests.)
		update_option( 'timezone_string', $timezone );
		$datetime  = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$timestamp = $datetime->getTimestamp();
		$mysql     = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertEquals( $timestamp, mysql2date( 'U', $mysql, false ) );
		$this->assertEquals( $timestamp, mysql2date( 'G', $mysql, false ) );
	}
}
