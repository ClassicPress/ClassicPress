<?php

/**
 * @group date
 * @group datetime
 * @covers ::current_time
 */
class Tests_Date_CurrentTime extends WP_UnitTestCase {

	/**
	 * Cleans up.
	 */
	public function tear_down() {
		// Reset changed options to their default value.
		update_option( 'gmt_offset', 0 );
		update_option( 'timezone_string', '' );

		parent::tear_down();
	}

	/**
	 * @ticket 34378
	 */
	public function test_current_time_with_date_format_string() {
		update_option( 'gmt_offset', 6 );

		$format       = 'F j, Y, g:i a';
		$timestamp    = time();
		$wp_timestamp = $timestamp + 6 * HOUR_IN_SECONDS;

		$this->assertEqualsWithDelta( strtotime( gmdate( $format ) ), strtotime( current_time( $format, true ) ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( gmdate( $format, $wp_timestamp ) ), strtotime( current_time( $format ) ), 2, 'The dates should be equal' );
	}

	/**
	 * @ticket 34378
	 */
	public function test_current_time_with_mysql_format() {
		update_option( 'gmt_offset', 6 );

		$format       = 'Y-m-d H:i:s';
		$timestamp    = time();
		$wp_timestamp = $timestamp + 6 * HOUR_IN_SECONDS;

		$this->assertEqualsWithDelta( strtotime( gmdate( $format ) ), strtotime( current_time( 'mysql', true ) ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( gmdate( $format, $wp_timestamp ) ), strtotime( current_time( 'mysql' ) ), 2, 'The dates should be equal' );
	}

	/**
	 * @ticket 34378
	 */
	public function test_current_time_with_timestamp() {
		update_option( 'gmt_offset', 6 );

		$timestamp    = time();
		$wp_timestamp = $timestamp + 6 * HOUR_IN_SECONDS;

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.RequestedUTC
		$this->assertEqualsWithDelta( $timestamp, current_time( 'timestamp', true ), 2, 'The dates should be equal' );
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$this->assertEqualsWithDelta( $wp_timestamp, current_time( 'timestamp' ), 2, 'The dates should be equal' );
	}

	/**
	 * @ticket 37440
	 */
	public function test_should_work_with_changed_timezone() {
		$format          = 'Y-m-d H:i:s';
		$timezone_string = 'America/Regina';
		update_option( 'timezone_string', $timezone_string );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone_string ) );

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $timezone_string );

		$current_time_custom_timezone_gmt = current_time( $format, true );
		$current_time_custom_timezone     = current_time( $format );

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( 'UTC' );

		$current_time_gmt = current_time( $format, true );
		$current_time     = current_time( $format );

		$this->assertEqualsWithDelta( strtotime( gmdate( $format ) ), strtotime( $current_time_custom_timezone_gmt ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( $datetime->format( $format ) ), strtotime( $current_time_custom_timezone ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( gmdate( $format ) ), strtotime( $current_time_gmt ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( $datetime->format( $format ) ), strtotime( $current_time ), 2, 'The dates should be equal' );
	}

	/**
	 * @ticket 40653
	 */
	public function test_should_return_wp_timestamp() {
		update_option( 'timezone_string', 'Europe/Helsinki' );

		$timestamp = time();
		$datetime  = new DateTime( '@' . $timestamp );
		$datetime->setTimezone( wp_timezone() );
		$wp_timestamp = $timestamp + $datetime->getOffset();

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.RequestedUTC
		$this->assertEqualsWithDelta( $timestamp, current_time( 'timestamp', true ), 2, 'The dates should be equal' );
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.RequestedUTC
		$this->assertEqualsWithDelta( $timestamp, current_time( 'U', true ), 2, 'The dates should be equal' );

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$this->assertEqualsWithDelta( $wp_timestamp, current_time( 'timestamp' ), 2, 'The dates should be equal' );
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$this->assertEqualsWithDelta( $wp_timestamp, current_time( 'U' ), 2, 'The dates should be equal' );

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$this->assertIsInt( current_time( 'timestamp' ) );
	}

	/**
	 * @ticket 40653
	 */
	public function test_should_return_correct_local_time() {
		update_option( 'timezone_string', 'Europe/Helsinki' );

		$timestamp      = time();
		$datetime_local = new DateTime( '@' . $timestamp );
		$datetime_local->setTimezone( wp_timezone() );
		$datetime_utc = new DateTime( '@' . $timestamp );
		$datetime_utc->setTimezone( new DateTimeZone( 'UTC' ) );

		$this->assertEqualsWithDelta( strtotime( $datetime_local->format( DATE_W3C ) ), strtotime( current_time( DATE_W3C ) ), 2, 'The dates should be equal' );
		$this->assertEqualsWithDelta( strtotime( $datetime_utc->format( DATE_W3C ) ), strtotime( current_time( DATE_W3C, true ) ), 2, 'The dates should be equal' );
	}

	/**
	 * Ensures that deprecated timezone strings are handled correctly.
	 *
	 * @ticket 56468
	 */
	public function test_should_work_with_deprecated_timezone() {
		$format          = 'Y-m-d H:i';
		$timezone_string = 'America/Buenos_Aires'; // This timezone was deprecated pre-PHP 5.6.
		update_option( 'timezone_string', $timezone_string );
		$datetime = new DateTime( 'now', new DateTimeZone( $timezone_string ) );

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $timezone_string );

		$current_time_custom_timezone_gmt = current_time( $format, true );
		$current_time_custom_timezone     = current_time( $format );

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( 'UTC' );

		$current_time_gmt = current_time( $format, true );
		$current_time     = current_time( $format );

		$this->assertSame( gmdate( $format ), $current_time_custom_timezone_gmt, 'The dates should be equal [1]' );
		$this->assertSame( $datetime->format( $format ), $current_time_custom_timezone, 'The dates should be equal [2]' );
		$this->assertSame( gmdate( $format ), $current_time_gmt, 'The dates should be equal [3]' );
		$this->assertSame( $datetime->format( $format ), $current_time, 'The dates should be equal [4]' );
	}
}
