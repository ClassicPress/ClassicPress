<?php

/**
 * @group date
 * @group datetime
 */
class Tests_Date_mysql2date extends WP_UnitTestCase {

	function tear_down() {
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( 'UTC' );

		// Reset the timezone option to the default value.
		update_option( 'timezone_string', '' );

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
		$this->assertSame( 441013392, mysql2date( 'G', '1983-12-23 07:43:12' ) );
		$this->assertSame( 441013392, mysql2date( 'U', '1983-12-23 07:43:12' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
	public function test_mysql2date_should_format_time() {
		$timezone = 'Europe/Helsinki';
		update_option( 'timezone_string', $timezone );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$rfc3339  = $datetime->format( DATE_RFC3339 );
		$mysql    = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql ) );
		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql, false ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
	public function test_mysql2date_should_format_time_with_changed_time_zone() {
		$timezone = 'Europe/Helsinki';
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $timezone );
		update_option( 'timezone_string', $timezone );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$rfc3339  = $datetime->format( DATE_RFC3339 );
		$mysql    = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql ) );
		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql, false ) );
	}

	/**
<<<<<<< HEAD
	 * @see https://core.trac.wordpress.org/ticket/28992
=======
	 * Ensures that deprecated timezone strings are handled correctly.
	 *
	 * @ticket 56468
	 */
	public function test_mysql2date_should_format_time_with_deprecated_time_zone() {
		$timezone = 'America/Buenos_Aires'; // This timezone was deprecated pre-PHP 5.6.
		update_option( 'timezone_string', $timezone );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$rfc3339  = $datetime->format( DATE_RFC3339 );
		$mysql    = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql ) );
		$this->assertSame( $rfc3339, mysql2date( DATE_RFC3339, $mysql, false ) );
	}

	/**
	 * @ticket 28992
>>>>>>> a2faa0c897 (Tests: Add tests with deprecated timezone strings.)
	 */
	public function test_mysql2date_should_return_wp_timestamp() {
		$timezone = 'Europe/Helsinki';
		update_option( 'timezone_string', $timezone );
		$datetime     = new DateTime( 'now', new DateTimeZone( $timezone ) );
		$wp_timestamp = $datetime->getTimestamp() + $datetime->getOffset();
		$mysql        = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertSame( $wp_timestamp, mysql2date( 'U', $mysql, false ) );
		$this->assertSame( $wp_timestamp, mysql2date( 'G', $mysql, false ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/28992
	 */
	public function test_mysql2date_should_return_unix_timestamp_for_gmt_time() {
		$timezone = 'Europe/Helsinki';
		update_option( 'timezone_string', $timezone );
		$datetime  = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$timestamp = $datetime->getTimestamp();
		$mysql     = $datetime->format( 'Y-m-d H:i:s' );

		$this->assertSame( $timestamp, mysql2date( 'U', $mysql, false ) );
		$this->assertSame( $timestamp, mysql2date( 'G', $mysql, false ) );
	}
}
